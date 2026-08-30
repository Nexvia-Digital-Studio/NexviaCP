#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""NexviaCP Demand Engine — distress-first resource scaling.

Philosophy: scale when the system *struggles*, not when traffic merely
looks busy. Request counts say nothing about saturation — a busy site at
200ms/request is healthy, a quiet API at 3s/request is drowning.

Signals (0-100 each, blended):
  * latency distress  — dynamic-request response time vs the domain's own
                        learned same-hour baseline, with an absolute curve
                        fallback (cold start / no baseline yet)
  * memory distress   — docker containers: real usage/limit ratio (docker
                        stats); PHP app backends: cgroup RAM + PSI; shared
                        PHP: system memory PSI at reduced weight
  * cpu distress      — per-cgroup CPU PSI (throttling), system CPU PSI
                        fallback
  * load context      — small weight: dynamic load + spike vs baseline,
                        kept for headroom sizing and viral context

The score drives the same state machine (idle/active/busy/boosted) with
hysteresis + downgrade cooldown. API/backend domains (APP_TYPE set —
docker apps, dotnet/node services) never fall below "active": a silent
API is not an idle API, and visitor counts are meaningless for them.

State JSON (governance dashboard) is written atomically; the same JSON is
echoed to stdout so v-tune-sys-resources can apply the cgroup limits.

CLI:
  nexvia-demand-engine.py --user U --domain D [--priority 0]
                          [--gov-dir DIR] [--homedir DIR] [--log FILE]
                          [--app-type T] [--docker-app A] [--docker-service S]
                          [--dry-run] [--self-test]
"""
import argparse
import fcntl
import glob
import json
import math
import os
import re
import subprocess
import sys
import tempfile
import time
from datetime import datetime, timedelta, timezone

STATIC_EXT = {
    "css", "js", "mjs", "map", "png", "jpg", "jpeg", "gif", "svg", "svgz",
    "ico", "woff", "woff2", "ttf", "otf", "eot", "webp", "avif", "mp4",
    "webm", "mp3", "ogg", "wav", "txt", "pdf", "zip", "rar", "7z", "gz",
}
STATIC_PREFIX = ("/cdn-cgi/", "/static/", "/assets/", "/.well-known/")
BOT_UA = re.compile(
    r"bot|crawl|spider|slurp|scrap|monitor|uptime|pingdom|httrack|headless|"
    r"phantomjs|python-requests|python-urllib|curl/|wget|go-http-client|"
    r"java/|okhttp|libwww|httpclient|forestengine|semrush|ahrefs|majestic|"
    r"mj12|dotbot|petalbot|dataforseo|bytespider|gptbot|claudebot|amazonbot|"
    r"yandex|bingpreview|facebookexternalhit|qwantify|coccoc",
    re.IGNORECASE,
)
# Two layout variants seen in the wild:
#   A (combined-like): request quoted, status bare
#   B (hestia "main"): request bare, status quoted
LINE_RE_A = re.compile(
    r'^(\S+) \S+ \S+ \[([^\]]+)\] "([^"]*)" (\d{3}) (\S+) '
    r'"([^"]*)" "([^"]*)"(?: "((?:[^"\\]|\\.)*)")?(?: (\d+\.\d+))?\s*$'
)
LINE_RE_B = re.compile(
    r'^(\S+) \S+ \S+ \[([^\]]+)\] ([^"]*?) "?(\d{3})"? (\S+) '
    r'"([^"]*)" "([^"]*)"(?: "((?:[^"\\]|\\.)*)")?(?: (\d+\.\d+))?\s*$'
)
LINE_RES = (LINE_RE_A, LINE_RE_B)
MONTHS = {m: i + 1 for i, m in enumerate(
    ["Jan", "Feb", "Mar", "Apr", "May", "Jun",
     "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"])}

# --- distress / context curves (piecewise-linear x->y) --------------------
# latency, absolute (seconds) — cold-start fallback & hard sanity floor
CURVE_LAT_ABS = [(0.15, 0), (0.40, 10), (0.80, 30), (1.50, 60),
                 (2.50, 85), (4.00, 100)]
# latency, relative to the domain's own learned baseline (ratio)
CURVE_LAT_REL = [(1.00, 0), (1.20, 15), (1.50, 40), (2.00, 70), (3.00, 100)]
CURVE_PSI = [(0, 0), (5, 5), (15, 20), (30, 45), (60, 80), (90, 100)]
CURVE_RAM = [(0.50, 0), (0.70, 15), (0.85, 45), (0.95, 80), (1.00, 100)]
# load context (secondary): dynamic request load + spike vs baseline
CURVE_LOAD = [(0, 0), (20, 15), (60, 30), (150, 45), (400, 60),
              (1000, 80), (2500, 92), (5000, 100)]
CURVE_SPIKE = [(1.5, 0), (2.0, 20), (3.0, 45), (5.0, 70), (8.0, 95), (15.0, 100)]

# distress-first blend: latency is the primary "system slowed down" signal,
# memory/CPU saturation next; traffic is context only
W_LAT, W_MEM, W_CPU, W_CTX = 0.35, 0.30, 0.20, 0.15

# thresholds: up (enter) / down (leave, hysteresis) + 10-min downgrade cooldown
TH_UP = {"active": 12, "busy": 38, "boosted": 62}
TH_DOWN = {"boosted": 45, "busy": 28, "active": 10}
STATE_RANK = {"idle": 0, "active": 1, "busy": 2, "boosted": 3}
COOLDOWN_SEC = 600
LEARNING_SPIKE_PTS = 25  # neutral-ish context while the baseline builds up

# sizing per state: (worker memory_limit, min/max mem_max, min/max cpu, io weight)
SIZE = {
    "idle":    dict(worker=64,  floor=256,  ceil=256,  cpu_min=25,  cpu_max=25,  io=50),
    "active":  dict(worker=128, floor=256,  ceil=512,  cpu_min=50,  cpu_max=100, io=100),
    "busy":    dict(worker=128, floor=512,  ceil=1024, cpu_min=100, cpu_max=200, io=300),
    "boosted": dict(worker=192, floor=1536, ceil=2048, cpu_min=200, cpu_max=400, io=500),
}
STATIC_TIERS = {  # manual priorities 1-5 keep the documented fixed profile
    1: ("throttled", "64M", "256M", "25%", 50),
    2: ("active", "256M", "1G", "50%", 100),
    3: ("active", "512M", "2G", "100%", 300),
    4: ("boosted", "1G", "4G", "200%", 700),
    5: ("vip", "2G", "unlimited", "unlimited", 1000),
}

# docker stats snapshot (shared across domains of one tuning pass)
DOCKER_SNAPSHOT = "/var/lib/hestia/governance/docker-stats.snapshot.json"
DOCKER_SNAPSHOT_TTL = 60


def interp(x, pts):
    """Piecewise-linear curve interpolation with edge clamping."""
    if x <= pts[0][0]:
        return pts[0][1]
    for (x0, y0), (x1, y1) in zip(pts, pts[1:]):
        if x <= x1:
            if x1 == x0:
                return y1
            return y0 + (y1 - y0) * (x - x0) / (x1 - x0)
    return pts[-1][1]


def is_private_ip(ip):
    return (ip.startswith("10.") or ip.startswith("192.168.")
            or ip.startswith("127.") or ip.startswith("169.254.")
            or ip.startswith("172.") and 15 < int(ip.split(".")[1]) < 32
            or ip == "::1")


def first_public_ip(candidate):
    for part in re.split(r"[, ]+", candidate or ""):
        if part and not is_private_ip(part):
            return part
    return None


def parse_ts(ts):
    m = re.match(r"(\d+)/(\w+)/(\d+):(\d+):(\d+):(\d+)", ts or "")
    if not m or m.group(2) not in MONTHS:
        return None
    d, mon, y, h, mi, s = m.groups()
    try:
        return int(datetime(int(y), MONTHS[mon], int(d), int(h), int(mi),
                            int(s), tzinfo=timezone.utc).timestamp())
    except ValueError:
        return None


def analyze_log(log_file, now_ts):
    """Count traffic + dynamic-request latency over the last 10/1 minutes."""
    res = dict(req10=0, dyn10=0, static10=0, bot10=0, users=set(),
               req1=0, rt_sum=0.0, rt_n=0, last_hit=0)
    if not log_file or not os.path.isfile(log_file):
        return res
    try:
        with open(log_file, "r", encoding="utf-8", errors="ignore") as f:
            f.seek(0, os.SEEK_END)
            size = f.tell()
            f.seek(max(0, size - 300000), os.SEEK_SET)
            lines = f.readlines()
        if size > 300000 and lines:
            lines = lines[1:]
        for line in lines[-4000:]:
            m = None
            for rx in LINE_RES:
                m = rx.match(line.rstrip("\n"))
                if m:
                    break
            if not m:
                continue
            remote, ts, req, status, _b, _ref, ua, xff, rt = m.groups()
            t = parse_ts(ts)
            if t is None:
                continue
            if t > res["last_hit"]:
                res["last_hit"] = t
            age = now_ts - t
            if age < 0 or age > 900:
                continue
            ip = remote
            if xff and is_private_ip(remote):
                ip = first_public_ip(xff)
            is_bot = bool(BOT_UA.search(ua or ""))
            uri = (req.split(" ", 2)[1] if " " in req else req).split("?")[0].lower()
            ext = uri.rsplit(".", 1)[-1] if "." in uri else ""
            is_static = ext in STATIC_EXT or uri.startswith(tuple(STATIC_PREFIX)) \
                or status == "304" or req.startswith("HEAD ")
            if age <= 600:
                res["req10"] += 1
                if is_bot:
                    res["bot10"] += 1
                elif is_static:
                    res["static10"] += 1
                else:
                    res["dyn10"] += 1
                    if rt:
                        try:
                            res["rt_sum"] += float(rt)
                            res["rt_n"] += 1
                        except ValueError:
                            pass
                if not is_bot:
                    res["users"].add(ip)
            if age <= 60:
                res["req1"] += 1
    except Exception:
        pass
    return res


def find_log(user, domain, homedir):
    for p in ("/var/log/nginx/domains/%s.log" % domain,
              "%s/%s/web/%s/logs/%s.log" % (homedir, user, domain, domain),
              "/var/log/apache2/domains/%s.log" % domain,
              "%s/%s/web/%s/logs/%s.bytes.log" % (homedir, user, domain, domain)):
        if os.path.isfile(p) and os.path.getsize(p) > 0:
            return p
    return ""


def _read_psi_some(path, weight=1.0):
    try:
        with open(path) as f:
            for ln in f:
                if ln.startswith("some "):
                    mm = re.search(r"avg10=([0-9.]+)", ln)
                    if mm:
                        return float(mm.group(1)) * weight
                    break
    except Exception:
        pass
    return 0.0


def read_pressure(user, domain):
    """(mem_psi, ram_bytes, ram_limit_bytes, per_domain?, cpu_psi)."""
    psi, ram, limit, per_domain, cpu_psi = 0.0, 0, 0, False, 0.0
    cg = "/sys/fs/cgroup/system.slice/hestia-app-%s-%s.service" % (user, domain)
    if not os.path.isdir(cg):
        cands = sorted(glob.glob("/sys/fs/cgroup/system.slice/php*-fpm.service"))
        cg = cands[0] if cands else ""
    else:
        per_domain = True
    if cg:
        try:
            with open(os.path.join(cg, "memory.current")) as f:
                ram = int(f.read().strip())
            if per_domain:
                with open(os.path.join(cg, "memory.max")) as f:
                    mx = int(f.read().strip())
                limit = mx if 0 < mx < (1 << 62) else 0
                psi = _read_psi_some(os.path.join(cg, "memory.pressure"))
                cpu_psi = _read_psi_some(os.path.join(cg, "cpu.pressure"))
        except Exception:
            pass
    if not per_domain:  # shared PHP-FPM: fall back to system pressure, reduced
        psi = _read_psi_some("/proc/pressure/memory", 0.5)
        cpu_psi = _read_psi_some("/proc/pressure/cpu", 0.6)
    return psi, ram, limit, per_domain, cpu_psi


def _parse_mem_str(s):
    """'297.1MiB' / '1.5GiB' / '812KiB' -> MB (float)."""
    m = re.match(r"([\d.]+)\s*([KMGT]?i?B)", (s or "").strip())
    if not m:
        return 0.0
    val = float(m.group(1))
    unit = m.group(2).rstrip("iB").rstrip("B") or "K"
    mul = {"": 1 / 1024, "K": 1 / 1024, "M": 1.0, "G": 1024.0, "T": 1024.0 * 1024}
    return val * mul.get(unit, 1 / 1024)


def read_docker_mem(app, service):
    """(usage_mb, mem_ratio 0..1) for a docker-app service. Uses a shared,
    60s-cached snapshot of `docker ps` labels + `docker stats`."""
    key = "%s/%s" % (app, service)
    if not key or key == "/":
        return 0.0, 0.0
    snap = {}
    fresh = False
    try:
        if os.path.isfile(DOCKER_SNAPSHOT) and \
                time.time() - os.path.getmtime(DOCKER_SNAPSHOT) < DOCKER_SNAPSHOT_TTL:
            with open(DOCKER_SNAPSHOT) as f:
                snap = json.load(f)
            fresh = key in snap or snap.get("_complete_apps", {}).get(app)
    except Exception:
        snap = {}
    if not fresh:
        try:
            mapping = subprocess.run(
                ["docker", "ps", "--filter",
                 "label=com.docker.compose.project=nexvia-" + app,
                 "--format",
                 '{{.Label "com.docker.compose.service"}}|{{.Names}}|{{.ID}}'],
                capture_output=True, text=True, timeout=15).stdout
            ids = [ln.split("|")[2] for ln in mapping.splitlines()
                   if len(ln.split("|")) == 3]
            if ids:
                stats = subprocess.run(
                    ["docker", "stats", "--no-stream", "--format",
                     "{{.Name}}|{{.MemUsage}}|{{.MemPerc}}"] + ids,
                    capture_output=True, text=True, timeout=30).stdout
                mem_by_name, pct_by_name = {}, {}
                for ln in stats.splitlines():
                    p = ln.split("|")
                    if len(p) == 3:
                        mem_by_name[p[0]] = p[1]
                        pct_by_name[p[0]] = p[2]
                complete = bool(mapping.strip())
                for ln in mapping.splitlines():
                    p = ln.split("|")
                    if len(p) == 3:
                        snap["%s/%s" % (app, p[0])] = {
                            "usage": mem_by_name.get(p[1], ""),
                            "pct": pct_by_name.get(p[1], ""),
                        }
                snap.setdefault("_complete_apps", {})[app] = complete
                os.makedirs(os.path.dirname(DOCKER_SNAPSHOT), exist_ok=True)
                tmp = DOCKER_SNAPSHOT + ".tmp"
                with open(tmp, "w") as f:
                    json.dump(snap, f)
                os.replace(tmp, DOCKER_SNAPSHOT)
        except Exception:
            pass
    ent = snap.get(key) or {}
    pct = 0.0
    try:
        pct = float((ent.get("pct") or "0").replace("%", "").strip() or 0)
    except ValueError:
        pass
    usage = _parse_mem_str((ent.get("usage") or "").split("/")[0])
    return usage, clamp(pct / 100.0, 0.0, 1.0)


def load_history(path, now_ts):
    """Baselines: same-hour samples (±90m) of the last 7 days, weekday-aware.
    Returns (dyn_baseline, samples, rt_baseline_seconds)."""
    if not os.path.isfile(path):
        return 0.0, 0, 0.0
    now = datetime.fromtimestamp(now_ts)
    vals, rts = [], []
    try:
        with open(path) as f:
            for ln in f:
                try:
                    rec = json.loads(ln)
                except Exception:
                    continue
                t = rec.get("t", 0)
                if now_ts - t > 7 * 86400:
                    continue
                dt = datetime.fromtimestamp(t)
                mins = (dt.hour - now.hour) * 60 + (dt.minute - now.minute)
                if abs(mins) > 90 or (dt.weekday() >= 5) != (now.weekday() >= 5):
                    continue
                vals.append(float(rec.get("dyn", rec.get("req", 0))))
                rt = float(rec.get("rt", 0) or 0)
                if rt > 0.02:
                    rts.append(rt)
    except Exception:
        return 0.0, 0, 0.0
    rt_base = (sorted(rts)[len(rts) // 2] if rts else 0.0)  # median: robust
    return ((sum(vals) / len(vals)) if vals else 0.0, len(vals), rt_base)


def _rec_newer(ln, cutoff):
    try:
        return json.loads(ln).get("t", 0) >= cutoff
    except Exception:
        return False


def append_history(path, now_ts, req10, dyn10, users, rt):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    try:
        with open(path, "a") as f:
            fcntl.flock(f, fcntl.LOCK_EX)
            f.write(json.dumps({"t": int(now_ts), "req": req10, "dyn": dyn10,
                                "usr": users, "rt": round(rt, 4) if rt else 0}) + "\n")
            fcntl.flock(f, fcntl.LOCK_UN)
        cutoff = now_ts - 8 * 86400
        with open(path) as f:
            lines = [ln for ln in f if _rec_newer(ln, cutoff)]
        if len(lines) < 1500:  # only rewrite while the file is still small
            with open(path, "w") as f:
                fcntl.flock(f, fcntl.LOCK_EX)
                f.writelines(lines)
                fcntl.flock(f, fcntl.LOCK_UN)
    except Exception:
        pass


def fmt_mb(mb):
    if mb >= 1024 and mb % 1024 == 0:
        return "%dG" % (mb // 1024)
    return "%dM" % mb


def clamp(v, lo, hi):
    return max(lo, min(hi, v))


def decide(prev_state, score, req10, idle_sec, last_change, now_ts, learning,
           api_mode=False):
    """State machine: upward thresholds, hysteresis band + cooldown downward.
    API/backend domains never go idle: silence is not idleness for a service
    that frontends call in bursts."""
    reasons = []
    if not api_mode and (idle_sec is None or idle_sec > 600 or req10 == 0):
        return "idle", now_ts, ["idle_no_traffic"]
    if api_mode and (idle_sec is None or idle_sec > 600 or req10 == 0):
        reasons.append("api_floor_active")
    if score >= TH_UP["boosted"]:
        cand = "boosted"
    elif score >= TH_UP["busy"]:
        cand = "busy"
    else:
        cand = "active"
    prev = STATE_RANK.get(prev_state, 1)
    if STATE_RANK.get(cand, 1) < prev:  # potential downgrade
        leave = TH_DOWN.get(prev_state, TH_DOWN["active"])
        if score >= leave:
            cand = prev_state  # inside the hysteresis band -> hold
            reasons.append("hold_hysteresis")
        elif now_ts - last_change < COOLDOWN_SEC:
            cand = prev_state  # too soon after the last transition -> hold
            reasons.append("hold_cooldown")
    if learning:
        reasons.append("learning_baseline")
    reasons.append({"active": "active_light", "busy": "busy_steady",
                    "boosted": "boost_burst"}[cand])
    changed = cand != prev_state
    return cand, (now_ts if changed else last_change), reasons


def size_resources(state, users, dyn10, avg_rt, score):
    """RAM/CPU sizing. Traffic feeds *sizing* (Little's law concurrency),
    the distress score feeds the *state*; a slow-but-quiet domain still
    deserves roomier per-worker limits."""
    s = SIZE[state]
    dyn_rps = dyn10 / 600.0
    rt = clamp(avg_rt if avg_rt else 0.35, 0.05, 4.0)
    workers = max(2, math.ceil(dyn_rps * rt * 2.0 + users / 6.0))
    if state == "busy":
        workers = max(workers, 4)
    elif state == "boosted":
        workers = max(workers, 8)
    worker_mb = s["worker"]
    # slow responses on a distressed site deserve a roomier per-worker limit
    if avg_rt and avg_rt > 1.0 and state in ("active", "busy"):
        worker_mb = 192
    mem_max = clamp(round((worker_mb * workers) / 64.0) * 64, s["floor"], s["ceil"])
    if mem_max < worker_mb * 2:
        mem_max = worker_mb * 2
    cpu = clamp(round(workers * (100 if state == "boosted" else 75) / 25) * 25,
                s["cpu_min"], s["cpu_max"])
    return fmt_mb(worker_mb), fmt_mb(mem_max), "%d%%" % cpu, s["io"], workers


def _utcnow_str():
    return datetime.now(timezone.utc).strftime("%Y-%m-%dT%H:%M:%SZ")


def run(args):
    now_ts = int(time.time())
    gov_dir = args.gov_dir or "/var/lib/hestia/governance"
    os.makedirs(gov_dir, exist_ok=True)
    state_file = os.path.join(gov_dir, "%s_%s.json" % (args.user, args.domain))
    hist_file = os.path.join(gov_dir, "history",
                             "%s_%s.jsonl" % (args.user, args.domain))

    app_type = (getattr(args, "app_type", "") or "").strip()
    api_mode = bool(app_type)
    docker_app = (getattr(args, "docker_app", "") or "").strip()
    docker_service = (getattr(args, "docker_service", "") or "").strip()

    prev_state, last_change = "active", now_ts - COOLDOWN_SEC
    try:
        with open(state_file) as f:
            old = json.load(f)
        prev_state = old.get("STATUS", "active")
        if old.get("LAST_CHANGE"):
            t = datetime.strptime(old["LAST_CHANGE"], "%Y-%m-%dT%H:%M:%SZ")
            last_change = int(t.replace(tzinfo=timezone.utc).timestamp())
    except Exception:
        pass

    if args.priority in STATIC_TIERS:  # manual tier: fixed profile, no scoring
        status, mem_high, mem_max, cpu, io = STATIC_TIERS[args.priority]
        out = dict(DOMAIN=args.domain, USER=args.user, PRIORITY=args.priority,
                   STATUS=status, MEMORY_HIGH=mem_high, MEMORY_MAX=mem_max,
                   CPU_QUOTA=cpu, IO_WEIGHT=io, MEMORY_USAGE_MB=0,
                   REQ_COUNT_10M=0, ACTIVE_USERS_10M=0, DEMAND_SCORE=0,
                   WORKERS_EST=0, LAST_TUNED=_utcnow_str(),
                   LAST_CHANGE=datetime.fromtimestamp(last_change, timezone.utc)
                   .strftime("%Y-%m-%dT%H:%M:%SZ"))
        if not args.dry_run:
            _atomic_json(out, state_file)
        return out, status != prev_state

    log_file = args.log if getattr(args, "log", None) else \
        find_log(args.user, args.domain, args.homedir)
    tr = analyze_log(log_file, now_ts)
    users = len(tr["users"])
    dyn_rt = (tr["rt_sum"] / tr["rt_n"]) if tr["rt_n"] else None
    avg_rt = dyn_rt

    append_history(hist_file, now_ts, tr["req10"], tr["dyn10"], users, dyn_rt or 0)
    base_mean, base_n, base_rt = load_history(hist_file, now_ts)
    learning = base_n < 6
    spike_ratio = (tr["dyn10"] / max(base_mean, 2.0)) if not learning else 1.0

    # --- distress signals -------------------------------------------------
    # latency: relative to own baseline when established, absolute floor always
    lat_abs = interp(dyn_rt, CURVE_LAT_ABS) if dyn_rt else 0.0
    lat_rel = 0.0
    if dyn_rt and base_rt >= 0.05 and not learning:
        lat_rel = interp(dyn_rt / base_rt, CURVE_LAT_REL)
    lat_dist = max(lat_abs, lat_rel)

    # memory: docker containers report real usage; PHP falls back to cgroup/PSI
    docker_mem_mb, docker_ratio = (None, 0.0)
    if docker_app:
        docker_mem_mb, docker_ratio = read_docker_mem(docker_app, docker_service)
        mem_dist = interp(docker_ratio, CURVE_RAM)
        psi, cpu_psi = 0.0, 0.0
        ram_b = int(docker_mem_mb * 1048576)
        per_domain = True
    else:
        psi, ram_b, limit_b, per_domain, cpu_psi = read_pressure(
            args.user, args.domain)
        ram_ratio = (ram_b / limit_b) if (per_domain and limit_b) else 0.0
        mem_dist = max(interp(psi, CURVE_PSI), interp(ram_ratio, CURVE_RAM))

    cpu_dist = interp(cpu_psi, CURVE_PSI)

    # traffic context (secondary): steady load + spike, for headroom context
    l_pts = interp(tr["dyn10"], CURVE_LOAD)
    s_pts = interp(spike_ratio, CURVE_SPIKE) if not learning else LEARNING_SPIKE_PTS
    ctx = 0.6 * l_pts + 0.4 * s_pts

    score = int(round(W_LAT * lat_dist + W_MEM * mem_dist
                      + W_CPU * cpu_dist + W_CTX * ctx))
    # acute saturation overrides: a domain at >=90% memory or >=3s average
    # response time is struggling regardless of what the blend says
    mem_ratio_now = docker_ratio if docker_app else (
        (ram_b / limit_b) if (per_domain and limit_b) else 0.0)
    if mem_ratio_now >= 0.90:
        score = max(score, TH_UP["busy"])
    if dyn_rt and dyn_rt >= 3.0:
        score = max(score, 55)
    score = clamp(score, 0, 100)

    idle_sec = (now_ts - tr["last_hit"]) if tr["last_hit"] else None
    status, last_change, reasons = decide(prev_state, score, tr["req10"],
                                          idle_sec, last_change, now_ts,
                                          learning, api_mode=api_mode)
    mem_high, mem_max, cpu, io, workers = size_resources(
        status, users, tr["dyn10"], avg_rt, score)

    out = dict(
        DOMAIN=args.domain, USER=args.user, PRIORITY=0, STATUS=status,
        APP_TYPE=app_type,
        MEMORY_USAGE_MB=ram_b // 1048576, MEMORY_HIGH=mem_high, MEMORY_MAX=mem_max,
        CPU_QUOTA=cpu, IO_WEIGHT=io,
        REQ_COUNT_10M=tr["req10"], DYN_REQ_10M=tr["dyn10"],
        STATIC_REQ_10M=tr["static10"], BOT_REQ_10M=tr["bot10"],
        ACTIVE_USERS_10M=users, REQ_COUNT_1M=tr["req1"],
        AVG_RT_MS=int(dyn_rt * 1000) if dyn_rt else 0,
        RT_BASELINE_MS=int(base_rt * 1000) if base_rt else 0,
        PSI_PRESSURE=int(psi), DEMAND_SCORE=score,
        SCORE_LATENCY=round(lat_dist), SCORE_MEMORY=round(mem_dist),
        SCORE_CPU=round(cpu_dist), SCORE_CONTEXT=round(ctx),
        BASELINE_MEAN_10M=round(base_mean, 1), BASELINE_SAMPLES=base_n,
        SPIKE_RATIO=round(spike_ratio, 2), WORKERS_EST=workers,
        REASONS=reasons,
        LAST_TUNED=_utcnow_str(),
        LAST_CHANGE=datetime.fromtimestamp(last_change, timezone.utc)
        .strftime("%Y-%m-%dT%H:%M:%SZ"),
    )
    if docker_mem_mb is not None:
        out["CONTAINER_MEM_MB"] = int(docker_mem_mb)
    if not args.dry_run:
        _atomic_json(out, state_file)
    return out, status != prev_state


def _atomic_json(obj, path):
    fd, tmp = tempfile.mkstemp(dir=os.path.dirname(path))
    try:
        with os.fdopen(fd, "w") as f:
            json.dump(obj, f)
        os.replace(tmp, path)
    except Exception:
        try:
            os.unlink(tmp)
        except Exception:
            pass


class Args(object):
    def __init__(self, **kw):
        self.user = kw.get("user", "")
        self.domain = kw.get("domain", "")
        self.priority = kw.get("priority", 0)
        self.gov_dir = kw.get("gov_dir", "/var/lib/hestia/governance")
        self.homedir = kw.get("homedir", "/home")
        self.dry_run = kw.get("dry_run", False)
        self.log = kw.get("log", None)
        self.app_type = kw.get("app_type", "")
        self.docker_app = kw.get("docker_app", "")
        self.docker_service = kw.get("docker_service", "")


def self_test():
    import shutil
    d = tempfile.mkdtemp(prefix="nexvia-engine-test-")
    gov = os.path.join(d, "gov")
    os.makedirs(gov, exist_ok=True)
    now = int(time.time())
    ok = True

    def mklog(name, lines, fmt="combined"):
        p = os.path.join(d, name)
        with open(p, "w") as f:
            for entry in lines:
                ip, mins_ago, uri, ua, rt = entry[:5]
                xff = entry[5] if len(entry) > 5 else None
                ts = datetime.fromtimestamp(now - mins_ago * 60, tz=timezone.utc)\
                    .strftime("%d/%b/%Y:%H:%M:%S +0000")
                rt_s = (" %.3f" % rt) if rt is not None else ""
                if fmt == "combined":  # request quoted, status bare
                    f.write('%s - - [%s] "GET %s HTTP/2.0" 200 1234 "-" "%s" "%s"%s\n'
                            % (ip, ts, uri, ua, "", rt_s if rt is not None else ""))
                else:  # hestia "main": request bare, status quoted, XFF quoted
                    f.write('%s - - [%s] GET %s HTTP/2.0 "200" 1234 "-" "%s" "%s"%s\n'
                            % (ip, ts, uri, ua, xff or "-",
                               (" %.3f" % rt) if rt is not None else ""))
        return p

    ua_human = "Mozilla/5.0 (Windows NT 10.0) Chrome/126.0"

    def check(label, cond, res):
        nonlocal ok
        ok = ok and cond
        print("%-26s %-8s score=%-3s lat=%-3s mem=%-3s cpu=%-3s ram=%s -> %s"
              % (label, res["STATUS"], res["DEMAND_SCORE"],
                 res.get("SCORE_LATENCY", 0), res.get("SCORE_MEMORY", 0),
                 res.get("SCORE_CPU", 0), res["MEMORY_MAX"],
                 "OK" if cond else "FAIL"))

    # 1) busy but HEALTHY: 1 human, ~95 reqs at fast 0.25s — traffic alone
    #    must not scale anything (distress-first).
    lines = [("78.174.100.76", m / 10.0, "/assets/app.css" if m % 2 else "/list/api/",
              ua_human, 0.02 if m % 2 else 0.25) for m in range(1, 96)]
    res, _ = run(Args(user="t1", domain="quiet.test", gov_dir=gov, dry_run=True,
                      log=mklog("a.log", lines)))
    check("busy-but-healthy",
          res["STATUS"] == "active" and res["ACTIVE_USERS_10M"] == 1
          and res["DEMAND_SCORE"] < 38, res)

    # 2) heavy crowd at healthy 0.9s — steady, not distressed -> ACTIVE.
    lines = []
    for i in range(1500):
        real = "203.0.113.%d" % (i % 25 + 1)
        if i < 100:  # local tunnel hop: real client IP in X-Forwarded-For
            lines.append(("127.0.0.1", (i % 590) / 60.0 + 0.1, "/page/%d" % i,
                          ua_human, 0.9, real))
        else:
            lines.append((real, (i % 590) / 60.0 + 0.1, "/page/%d" % i,
                          ua_human, 0.9))
    res, _ = run(Args(user="t2", domain="crowd.test", gov_dir=gov, dry_run=True,
                      log=mklog("b.log", lines, fmt="main")))
    check("crowd-but-fast",
          res["STATUS"] == "active" and res["ACTIVE_USERS_10M"] == 25
          and res["SCORE_LATENCY"] < 50, res)

    # 2b) same crowd but the server is drowning: 3.5s responses -> BUSY+
    lines = [(e[0], e[1], e[2], e[3], 3.5) + ((e[5],) if len(e) > 5 else ())
             for e in lines]
    res, _ = run(Args(user="t2b", domain="slow.test", gov_dir=gov, dry_run=True,
                      log=mklog("b2.log", lines, fmt="main")))
    check("crowd-and-slow",
          res["STATUS"] in ("busy", "boosted") and res["SCORE_LATENCY"] >= 70, res)

    # 3) empty log -> idle for plain sites...
    res, _ = run(Args(user="t3", domain="none.test", gov_dir=gov, dry_run=True,
                      log=mklog("c.log", [])))
    check("no-traffic",
          res["STATUS"] == "idle" and res["MEMORY_HIGH"] == "64M"
          and res["CPU_QUOTA"] == "25%", res)

    # 3b) ...but an API domain never sleeps, even with zero traffic.
    res, _ = run(Args(user="t3b", domain="api-none.test", gov_dir=gov,
                      dry_run=True, log=mklog("c2.log", []), app_type="docker"))
    check("api-never-idle",
          res["STATUS"] == "active" and "api_floor_active" in res["REASONS"], res)

    # 4) latency regression vs own baseline: same load as usual, but
    #    responses degraded 3x (150ms -> 450ms) -> relative latency distress.
    hist = os.path.join(gov, "history", "t4_lat.test.jsonl")
    os.makedirs(os.path.dirname(hist), exist_ok=True)
    with open(hist, "w") as f:
        for i in range(12):
            f.write(json.dumps({"t": now - i * 350, "req": 140, "dyn": 120,
                                "usr": 3, "rt": 0.15}) + "\n")
    lines = [("203.0.113.%d" % (i % 5 + 1), (i % 580) / 60.0 + 0.1,
              "/page/%d" % i, ua_human, 0.45) for i in range(120)]
    res, _ = run(Args(user="t4", domain="lat.test", gov_dir=gov, dry_run=True,
                      log=mklog("d.log", lines, fmt="main")))
    check("latency-regression",
          res["SCORE_LATENCY"] >= 40 and res["SPIKE_RATIO"] < 2
          and res["STATUS"] in ("active", "busy"), res)

    # 5) docker container memory pressure drives the memory component.
    global DOCKER_SNAPSHOT
    real_snap = DOCKER_SNAPSHOT
    try:
        DOCKER_SNAPSHOT = os.path.join(d, "snap.json")
        with open(DOCKER_SNAPSHOT, "w") as f:
            json.dump({"mercan/api": {"usage": "7.4GiB / 8GiB", "pct": "92.50%"}},
                      f)
        os.utime(DOCKER_SNAPSHOT, (time.time(), time.time()))
        res, _ = run(Args(user="t5", domain="api.mercan.test", gov_dir=gov,
                          dry_run=True, log=mklog("e.log", []),
                          app_type="docker", docker_app="mercan",
                          docker_service="api"))
        check("docker-mem-pressure",
              res.get("CONTAINER_MEM_MB", 0) >= 7000 and res["SCORE_MEMORY"] >= 15
              and res["STATUS"] == "busy", res)
    finally:
        DOCKER_SNAPSHOT = real_snap

    # 6) curve anchors
    c = (interp(0.15, CURVE_LAT_ABS) == 0 and interp(4.0, CURVE_LAT_ABS) == 100
         and interp(3.0, CURVE_LAT_REL) == 100 and interp(0.95, CURVE_RAM) == 80
         and interp(600, CURVE_LOAD) > 60)
    print("%-26s -> %s" % ("curve-anchors", "OK" if c else "FAIL"))
    ok = ok and c

    shutil.rmtree(d, ignore_errors=True)
    return ok


def main():
    ap = argparse.ArgumentParser()
    ap.add_argument("--user", default="")
    ap.add_argument("--domain", default="")
    ap.add_argument("--priority", type=int, default=0)
    ap.add_argument("--gov-dir", default="/var/lib/hestia/governance")
    ap.add_argument("--homedir", default="/home")
    ap.add_argument("--log", default=None,
                    help="override log file (used by self-test)")
    ap.add_argument("--app-type", default="",
                    help="backend domain type (docker / dotnet / node-js): "
                         "API mode — never idle, visitor counts ignored")
    ap.add_argument("--docker-app", default="",
                    help="docker app name for container memory metrics")
    ap.add_argument("--docker-service", default="",
                    help="compose service of the mapped container")
    ap.add_argument("--dry-run", action="store_true")
    ap.add_argument("--self-test", action="store_true")
    args = ap.parse_args()
    if args.self_test:
        sys.exit(0 if self_test() else 1)
    if not args.user or not args.domain:
        sys.stderr.write("--user and --domain are required\n")
        sys.exit(1)
    out, _ = run(args)
    print(json.dumps(out))


if __name__ == "__main__":
    main()
