#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""NexviaCP Demand Engine — multi-signal traffic scoring & adaptive resource sizing.

Replaces the naive "requests > 60 => boosted" rule. For each domain it blends:
  * active (non-bot, unique-IP) visitors in the last 10 minutes,
  * dynamic (non-asset) request load, weighted by real request duration,
  * deviation from the site's own time-of-day baseline (past 7 days),
  * memory pressure (per-domain cgroup PSI/RAM for app backends,
    system-wide PSI at half weight for shared PHP-FPM domains),
into a 0-100 demand score. The score, with hysteresis and a cooldown on
downgrades, picks a state (idle/active/busy/boosted) and sizes RAM/CPU:

  mem_high = per-PHP-worker memory_limit  (php_admin_value)
  mem_max  = worker_limit * max_children needed  (Little's law + visitors)
  cpu      = quota scaled to required workers

State JSON (governance dashboard) is written atomically; the same JSON is
echoed to stdout so v-tune-sys-resources can apply the cgroup limits.

CLI:
  nexvia-demand-engine.py --user U --domain D [--priority 0]
                          [--gov-dir DIR] [--homedir DIR] [--log FILE]
                          [--dry-run] [--self-test]
"""
import argparse
import fcntl
import glob
import json
import math
import os
import re
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

# --- scoring curves (piecewise-linear x->y) -------------------------------
CURVE_USERS = [(0, 0), (1, 12), (2, 20), (3, 30), (5, 45), (8, 60),
               (12, 75), (20, 88), (30, 95), (50, 100)]
CURVE_LOAD = [(0, 0), (20, 15), (60, 30), (150, 45), (400, 60),
              (1000, 80), (2500, 92), (5000, 100)]
CURVE_SPIKE = [(1.5, 0), (2.0, 20), (3.0, 45), (5.0, 70), (8.0, 95), (15.0, 100)]
CURVE_PSI = [(0, 0), (5, 5), (15, 20), (30, 45), (60, 80), (90, 100)]
CURVE_RAM = [(0.50, 0), (0.70, 15), (0.85, 45), (0.95, 80), (1.00, 100)]

W_USERS, W_LOAD, W_SPIKE, W_PRESSURE = 0.30, 0.25, 0.20, 0.25

# thresholds: up (enter) / down (leave, hysteresis) + 10-min downgrade cooldown
TH_UP = {"active": 12, "busy": 38, "boosted": 62}
TH_DOWN = {"boosted": 45, "busy": 28, "active": 10}
STATE_RANK = {"idle": 0, "active": 1, "busy": 2, "boosted": 3}
COOLDOWN_SEC = 600
LEARNING_SPIKE_PTS = 25  # neutral-ish score while baseline history builds up

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


def interp(x, pts):
    if x <= pts[0][0]:
        return pts[0][1]
    for (x0, y0), (x1, y1) in zip(pts, pts[1:]):
        if x <= x1:
            if x1 == x0:
                return y1
            return y0 + (y1 - y0) * (x - x0) / (x1 - x0)
    return pts[-1][1]


def is_private_ip(ip):
    if not ip or ip in ("-", ""):
        return True
    try:
        parts = ip.split(".")
        if len(parts) == 4 and all(p.isdigit() and 0 <= int(p) <= 255 for p in parts):
            a, b = int(parts[0]), int(parts[1])
            return (a == 10 or a == 127 or (a == 172 and 16 <= b <= 31)
                    or (a == 192 and b == 168) or (a == 169 and b == 254))
        low = ip.lower()
        return low == "::1" or low.startswith("fc") or low.startswith("fd")
    except Exception:
        return False


def first_public_ip(candidate):
    for ip in re.split(r"[, ]+", candidate.strip()):
        if ip and not is_private_ip(ip):
            return ip
    return candidate.split(",")[0].strip() if candidate else ""


def parse_ts(ts):
    m = re.match(r"(\d{2})/([A-Za-z]{3})/(\d{4}):(\d{2}):(\d{2}):(\d{2})\s+([+-]\d{4})", ts)
    if not m:
        return None
    day, mon, year, hh, mm, ss, tz = m.groups()
    if mon not in MONTHS:
        return None
    sign = 1 if tz[0] == "+" else -1
    try:
        dt = datetime(int(year), MONTHS[mon], int(day), int(hh), int(mm), int(ss),
                      tzinfo=timezone(sign * timedelta(hours=int(tz[1:3]),
                                                       minutes=int(tz[3:5]))))
    except Exception:
        return None
    return int(dt.timestamp())


def analyze_log(log_file, now_ts):
    """Count traffic signals over the last 10/1 minutes from a domain log."""
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


def read_pressure(user, domain):
    """(psi 0-100, ram_bytes, ram_limit_bytes, per_domain?) for app backends."""
    psi, ram, limit, per_domain = 0.0, 0, 0, False
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
                with open(os.path.join(cg, "memory.pressure")) as f:
                    for ln in f:
                        if ln.startswith("some "):
                            mm = re.search(r"avg10=([0-9.]+)", ln)
                            if mm:
                                psi = float(mm.group(1))
                            break
        except Exception:
            pass
    if not per_domain:  # shared PHP-FPM: fall back to system pressure, half weight
        try:
            with open("/proc/pressure/memory") as f:
                for ln in f:
                    if ln.startswith("some "):
                        mm = re.search(r"avg10=([0-9.]+)", ln)
                        if mm:
                            psi = float(mm.group(1)) * 0.5
                        break
        except Exception:
            pass
    return psi, ram, limit, per_domain


def load_history(path, now_ts):
    """Baseline: same-hour samples (±90m) of the last 7 days, weekday-aware."""
    if not os.path.isfile(path):
        return 0.0, 0
    now = datetime.fromtimestamp(now_ts)
    vals = []
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
    except Exception:
        return 0.0, 0
    return (sum(vals) / len(vals)) if vals else 0.0, len(vals)


def _rec_newer(ln, cutoff):
    try:
        return json.loads(ln).get("t", 0) >= cutoff
    except Exception:
        return False


def append_history(path, now_ts, req10, dyn10, users):
    os.makedirs(os.path.dirname(path), exist_ok=True)
    try:
        with open(path, "a") as f:
            fcntl.flock(f, fcntl.LOCK_EX)
            f.write(json.dumps({"t": int(now_ts), "req": req10, "dyn": dyn10,
                                "usr": users}) + "\n")
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


def decide(prev_state, score, req10, idle_sec, last_change, now_ts, learning):
    """State machine: upward thresholds, hysteresis band + cooldown downward."""
    reasons = []
    if idle_sec is None or idle_sec > 600 or req10 == 0:
        return "idle", now_ts, ["idle_no_traffic"]
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
    """RAM/CPU sizing. mem_high is the per-worker memory_limit; mem_max covers
    the max_children workers needed (Little's law + concurrent visitors)."""
    s = SIZE[state]
    dyn_rps = dyn10 / 600.0
    rt = clamp(avg_rt if avg_rt else 0.35, 0.05, 2.0)
    workers = max(2, math.ceil(dyn_rps * rt * 2.0 + users / 6.0))
    if state == "busy":
        workers = max(workers, 4)
    elif state == "boosted":
        workers = max(workers, 8)
    worker_mb = s["worker"]
    # heavy pages on a busy site deserve a roomier per-worker limit
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
    hist_file = os.path.join(gov_dir, "history", "%s_%s.jsonl" % (args.user, args.domain))

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
    avg_rt = (tr["rt_sum"] / tr["rt_n"]) if tr["rt_n"] else None
    psi, ram_b, limit_b, per_domain = read_pressure(args.user, args.domain)

    append_history(hist_file, now_ts, tr["req10"], tr["dyn10"], users)
    base_mean, base_n = load_history(hist_file, now_ts)
    learning = base_n < 6
    spike_ratio = (tr["dyn10"] / max(base_mean, 2.0)) if not learning else 1.0

    u_pts = interp(users, CURVE_USERS)
    l_pts = interp(tr["dyn10"], CURVE_LOAD)
    s_pts = interp(spike_ratio, CURVE_SPIKE) if not learning else LEARNING_SPIKE_PTS
    psi_pts = interp(psi, CURVE_PSI)
    ram_ratio = (ram_b / limit_b) if (per_domain and limit_b) else 0.0
    p_pts = max(psi_pts, interp(ram_ratio, CURVE_RAM))
    score = int(round(W_USERS * u_pts + W_LOAD * l_pts + W_SPIKE * s_pts
                      + W_PRESSURE * p_pts))
    score = clamp(score, 0, 100)

    idle_sec = (now_ts - tr["last_hit"]) if tr["last_hit"] else None
    status, last_change, reasons = decide(prev_state, score, tr["req10"],
                                          idle_sec, last_change, now_ts, learning)
    mem_high, mem_max, cpu, io, workers = size_resources(
        status, users, tr["dyn10"], avg_rt, score)

    out = dict(
        DOMAIN=args.domain, USER=args.user, PRIORITY=0, STATUS=status,
        MEMORY_USAGE_MB=ram_b // 1048576, MEMORY_HIGH=mem_high, MEMORY_MAX=mem_max,
        CPU_QUOTA=cpu, IO_WEIGHT=io,
        REQ_COUNT_10M=tr["req10"], DYN_REQ_10M=tr["dyn10"],
        STATIC_REQ_10M=tr["static10"], BOT_REQ_10M=tr["bot10"],
        ACTIVE_USERS_10M=users, REQ_COUNT_1M=tr["req1"],
        AVG_RT_MS=int(avg_rt * 1000) if avg_rt else 0,
        PSI_PRESSURE=int(psi), DEMAND_SCORE=score,
        SCORE_USERS=round(u_pts), SCORE_LOAD=round(l_pts),
        SCORE_SPIKE=round(s_pts), SCORE_PRESSURE=round(p_pts),
        BASELINE_MEAN_10M=round(base_mean, 1), BASELINE_SAMPLES=base_n,
        SPIKE_RATIO=round(spike_ratio, 2), WORKERS_EST=workers,
        REASONS=reasons,
        LAST_TUNED=_utcnow_str(),
        LAST_CHANGE=datetime.fromtimestamp(last_change, timezone.utc)
        .strftime("%Y-%m-%dT%H:%M:%SZ"),
    )
    if not args.dry_run:
        _atomic_json(out, state_file)
    return out, status != prev_state


def _atomic_json(obj, path):
    tmp = tempfile.NamedTemporaryFile("w", dir=os.path.dirname(path), delete=False,
                                      prefix=".gov-", suffix=".json")
    try:
        json.dump(obj, tmp, indent=1)
        tmp.close()
        os.chmod(tmp.name, 0o644)
        os.replace(tmp.name, path)
    except Exception:
        try:
            tmp.close()
            os.unlink(tmp.name)
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
    ua_bot = "Mozilla/5.0 (compatible; ForestEngine/1.0)"

    def check(label, cond, res):
        nonlocal ok
        ok = ok and cond
        print("%-24s %-8s score=%-3s users=%-3s ram=%s/%s cpu=%-4s -> %s"
              % (label, res["STATUS"], res["DEMAND_SCORE"], res["ACTIVE_USERS_10M"],
                 res["MEMORY_HIGH"], res["MEMORY_MAX"], res["CPU_QUOTA"],
                 "OK" if cond else "FAIL"))

    # 1) the user's exact case: 1 human browsing, ~95 hits (half static),
    #    plus a crawler -> must stay ACTIVE with small RAM (not "boosted").
    lines = [("78.174.100.76", m / 10.0, "/assets/app.css" if m % 2 else "/list/api/",
              ua_human, 0.02 if m % 2 else 0.25) for m in range(1, 96)]
    lines += [("66.249.66.1", m / 10.0, "/some-page", ua_bot, 0.3) for m in range(1, 6)]
    res, _ = run(Args(user="t1", domain="quiet.test", gov_dir=gov, dry_run=True,
                      log=mklog("a.log", lines)))
    check("single-user-browse",
          res["STATUS"] == "active" and res["ACTIVE_USERS_10M"] == 1
          and res["BOT_REQ_10M"] == 5 and res["MEMORY_MAX"] == "256M"
          and res["DEMAND_SCORE"] < 38, res)

    # 2) real steady crowd: 25 humans, 1500 dynamic reqs/10m -> BUSY, 1G RAM
    #    (hestia "main" log layout; first 100 hits arrive via a local tunnel
    #    proxy -> real client IP must be recovered from X-Forwarded-For)
    lines = []
    for i in range(1500):
        real = "203.0.113.%d" % (i % 25 + 1)
        if i < 100:  # local tunnel hop: remote is 127.0.0.1, client in XFF
            lines.append(("127.0.0.1", (i % 590) / 60.0 + 0.1, "/page/%d" % i,
                          ua_human, 0.9, real))
        else:
            lines.append((real, (i % 590) / 60.0 + 0.1, "/page/%d" % i,
                          ua_human, 0.9))
    lines += [("203.0.113.%d" % (i + 1), i / 50.0, "/static/x.js", ua_human, 0.01)
              for i in range(25)]
    res, _ = run(Args(user="t2", domain="crowd.test", gov_dir=gov, dry_run=True,
                      log=mklog("b.log", lines, fmt="main")))
    check("steady-crowd",
          res["STATUS"] in ("busy", "boosted") and res["ACTIVE_USERS_10M"] == 25
          and res["MEMORY_MAX"] in ("1G", "1536M", "2G"), res)

    # 3) empty log -> idle, deep eco limits
    res, _ = run(Args(user="t3", domain="none.test", gov_dir=gov, dry_run=True,
                      log=mklog("c.log", [])))
    check("no-traffic",
          res["STATUS"] == "idle" and res["MEMORY_HIGH"] == "64M"
          and res["CPU_QUOTA"] == "25%", res)

    # 4) established site (baseline 15 dyn/10m) hit by a 60x viral spike
    #    -> BOOSTED via the spike component
    hist = os.path.join(gov, "history", "t4_spike.test.jsonl")
    os.makedirs(os.path.dirname(hist), exist_ok=True)
    with open(hist, "w") as f:
        for i in range(12):
            f.write(json.dumps({"t": now - i * 350, "req": 40, "dyn": 15,
                                "usr": 3}) + "\n")
    lines = [("203.0.113.%d" % (i % 20 + 1), (i % 580) / 60.0 + 0.1,
              "/viral/%d" % i, ua_human, 0.6) for i in range(900)]
    res, _ = run(Args(user="t4", domain="spike.test", gov_dir=gov, dry_run=True,
                      log=mklog("d.log", lines, fmt="main")))
    check("viral-spike",
          res["STATUS"] == "boosted" and res["DEMAND_SCORE"] >= 62
          and res["SPIKE_RATIO"] >= 5, res)

    # 5) scoring curve sanity anchors
    c = (interp(0, CURVE_USERS) == 0 and interp(30, CURVE_USERS) == 95
         and interp(600, CURVE_LOAD) > 60 and interp(4, CURVE_SPIKE) > 50)
    print("%-24s -> %s" % ("curve-anchors", "OK" if c else "FAIL"))
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
