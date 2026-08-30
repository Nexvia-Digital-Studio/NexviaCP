#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
NexviaCP Cloudflare DDoS Guard
==============================
Distress-first, baseline-learning DDoS detector with automatic Cloudflare
"Under Attack" escalation. Companion philosophy to lib/nexvia-demand-engine.py:
never a single hard request threshold — the decision emerges from multiple
signals compared against the server's OWN learned normal.

Signals (weighted into a 0-100 attack score):
  40%  request-wave deviation from hour-of-day baseline (log-scaled ratio)
  20%  client concentration (single real client dominating traffic)
  35%  system distress (CPU/mem PSI, load vs cores, response-time regression)
  15%  error burst (4xx/5xx share during abnormal load)
  +    emergency SYN flood booster (bypasses debate, caps the score)

Decision machine (hysteretic, anti-flapping):
  calm -> attack : score >= ATTACK_ON (55) or SYN emergency
  attack -> calm : score < ATTACK_OFF (25) sustained RELEASE_MIN (10 min)
                   AND at least STAY_MIN (15 min) in attack mode

Cloudflare actions:
  ON  : snapshot current security_level per zone, then PATCH all zones to
        under_attack (zones discovered dynamically from the API)
  OFF : restore the snapshotted levels (fallback: medium)

State    : /var/lib/hestia/governance/ddos-guard.json
History  : /var/lib/hestia/governance/ddos-history.jsonl (retained 8 days)
Log      : /var/log/hestia/ddos-guard.log  (+ v-log-action + panel notification)

Usage:
  v-ddos-guard --run         cron entry: measure, decide, act
  v-ddos-guard --status      human-readable state/score report
  v-ddos-guard --force-on    manual: engage under attack NOW (marker, --auto clears)
  v-ddos-guard --force-off   manual: disengage and restore levels
  v-ddos-guard --auto        clear manual override, return to automatic
  v-ddos-guard --self-test   synthetic scenarios, no network/system changes
"""

import glob
import json
import math
import os
import re
import subprocess
import sys
import time
import urllib.error
import urllib.request
from collections import Counter
from datetime import datetime, timedelta

HESTIA = "/usr/local/hestia"
GOV_DIR = "/var/lib/hestia/governance"
STATE_FILE = os.path.join(GOV_DIR, "ddos-guard.json")
HISTORY_FILE = os.path.join(GOV_DIR, "ddos-history.jsonl")
LOG_FILE = "/var/log/hestia/ddos-guard.log"
CONF_FILE = os.path.join(HESTIA, "conf", "hestia.conf")

CF_API = "https://api.cloudflare.com/client/v4"
NGINX_LOGS = ["/var/log/nginx/access.log"] + sorted(
    glob.glob("/var/log/nginx/domains/*.log")
)

ATTACK_ON = 55.0
ATTACK_OFF = 25.0
RELEASE_MIN = 10 * 60        # sustained calm seconds required to release
STAY_MIN = 15 * 60           # minimum time in attack mode
HISTORY_RETENTION = 8 * 86400
FORCE_TTL = 6 * 3600         # manual override expires after 6h

# Two nginx formats seen on this stack (status quoted vs unquoted).
LINE_RE_QUOTED_STATUS = re.compile(
    r'^(?P<ip>\S+) \S+ \S+ \[(?P<ts>[^\]]+)\] (?P<req>\S+) (?P<path>\S+) [^"]+ "(?P<status>\d{3})" '
    r'(?P<bytes>\S+) "(?P<ref>[^"]*)" "(?P<ua>[^"]*)" "(?P<xff>[^"]*)" (?P<rt>[\d.]+)'
)
LINE_RE_PLAIN_STATUS = re.compile(
    r'^(?P<ip>\S+) \S+ \S+ \[(?P<ts>[^\]]+)\] (?P<req>\S+) (?P<path>\S+) [^"]* (?P<status>\d{3}) '
    r'(?P<bytes>\S+) "(?P<ref>[^"]*)" "(?P<ua>[^"]*)" "(?P<xff>[^"]*)" (?P<rt>[\d.]+)'
)


def log(msg):
    line = "[%s] %s" % (datetime.now().strftime("%Y-%m-%d %H:%M:%S"), msg)
    try:
        with open(LOG_FILE, "a") as fh:
            fh.write(line + "\n")
    except OSError:
        pass
    print(line)


def notify_panel(severity, msg):
    """v-log-action + admin panel notification; best-effort."""
    for cmd in (
        [os.path.join(HESTIA, "bin", "v-log-action"), "system", severity, "Security", msg],
        [os.path.join(HESTIA, "bin", "v-add-user-notification"), "admin", msg, "yes"],
    ):
        try:
            subprocess.run(cmd, timeout=20, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        except Exception:
            pass


def load_state():
    try:
        with open(STATE_FILE) as fh:
            return json.load(fh)
    except Exception:
        return {}


def save_state(state):
    os.makedirs(GOV_DIR, exist_ok=True)
    tmp = STATE_FILE + ".tmp"
    with open(tmp, "w") as fh:
        json.dump(state, fh, indent=2)
    os.replace(tmp, STATE_FILE)


# ----------------------------------------------------------------------------
# Signal collection
# ----------------------------------------------------------------------------

def collect_nginx(window=60):
    """Aggregate the last `window` seconds of nginx logs across all domains."""
    cutoff = datetime.now() - timedelta(seconds=window + 5)
    m = {
        "req": 0, "clients": Counter(), "rt": [], "err": 0,
        "err4": 0, "err5": 0, "bot_ua": 0,
    }
    parse_ts = lambda s: datetime.strptime(s, "%d/%b/%Y:%H:%M:%S %z")
    for path in NGINX_LOGS:
        try:
            with open(path, errors="replace") as fh:
                for line in fh:
                    # cheap prefilter on the minute part
                    try:
                        ts_part = line.split("[", 1)[1].split("]", 1)[0]
                        if parse_ts(ts_part) < cutoff:
                            continue
                    except Exception:
                        continue
                    mo = LINE_RE_QUOTED_STATUS.match(line) or LINE_RE_PLAIN_STATUS.match(line)
                    if not mo:
                        continue
                    m["req"] += 1
                    client = mo.group("xff").strip()
                    if not client or client == "-":
                        client = mo.group("ip")
                    # XFF chains: first hop is the real client
                    client = client.split(",")[0].strip()
                    m["clients"][client] += 1
                    try:
                        m["rt"].append(float(mo.group("rt")))
                    except ValueError:
                        pass
                    status = mo.group("status")
                    if status.startswith("4"):
                        m["err4"] += 1
                        m["err"] += 1
                    elif status.startswith("5"):
                        m["err5"] += 1
                        m["err"] += 1
                    ua = mo.group("ua").lower()
                    if any(k in ua for k in ("python-requests", "curl/", "wget", "go-http-client", "libwww")):
                        m["bot_ua"] += 1
        except OSError:
            continue
    rts = sorted(m["rt"])
    m["rt_med"] = rts[len(rts) // 2] if rts else 0.0
    m["uniq"] = len(m["clients"])
    m["top_share"] = (m["clients"].most_common(1)[0][1] / m["req"]) if m["req"] else 0.0
    m["err_rate"] = (m["err"] / m["req"]) if m["req"] else 0.0
    del m["clients"]
    del m["rt"]
    return m


def collect_system():
    def psi(name):
        try:
            with open("/proc/pressure/%s" % name) as fh:
                head = fh.readline()
            for part in head.split():
                if part.startswith("some"):
                    for kv in part.split(","):
                        if kv.startswith("avg60="):
                            return float(kv.split("=")[1])
        except Exception:
            pass
        return 0.0

    syn = 0
    try:
        out = subprocess.run(["ss", "-H", "-t", "state", "syn-recv"],
                             capture_output=True, text=True, timeout=10).stdout
        syn = len([l for l in out.splitlines() if l.strip()])
    except Exception:
        pass
    load1 = 0.0
    try:
        with open("/proc/loadavg") as fh:
            load1 = float(fh.readline().split()[0])
    except Exception:
        pass
    cores = os.cpu_count() or 1
    return {
        "psi_cpu": psi("cpu"),
        "psi_mem": psi("memory"),
        "syn_recv": syn,
        "load1": load1,
        "cores": cores,
    }


# ----------------------------------------------------------------------------
# Baseline (hour-of-day, learned from history)
# ----------------------------------------------------------------------------

def load_history():
    rows = []
    try:
        with open(HISTORY_FILE) as fh:
            for line in fh:
                try:
                    rows.append(json.loads(line))
                except Exception:
                    continue
    except OSError:
        pass
    return rows


def append_history(sample):
    os.makedirs(GOV_DIR, exist_ok=True)
    cutoff = time.time() - HISTORY_RETENTION
    rows = load_history()
    rows.append(sample)
    with open(HISTORY_FILE, "w") as fh:
        for r in rows:
            if r.get("ts", 0) >= cutoff:
                fh.write(json.dumps(r) + "\n")


def baseline_for(rows, now=None):
    """Median of same-hour samples (±2h window) from the last 7 days."""
    now = now or datetime.now()
    refs = []
    for r in rows:
        ts = r.get("ts", 0)
        if ts < time.time() - 7 * 86400:
            continue
        dt = datetime.fromtimestamp(ts)
        delta = abs(dt.hour - now.hour)
        if min(delta, 24 - delta) <= 2:
            refs.append(r)
    def med(key, default):
        vals = sorted(v for v in (r.get(key) for r in refs) if v is not None)
        return vals[len(vals) // 2] if vals else default
    n = len(refs)
    return {
        "req": med("req", 45.0),          # cold-start virtual normal (low-traffic box)
        "rt_med": med("rt_med", 0.05),
        "syn_recv": med("syn_recv", 0.0),
        "load1": med("load1", None),
        "samples": n,
    }


# ----------------------------------------------------------------------------
# Scoring
# ----------------------------------------------------------------------------

def compute_score(m, s, base):
    reasons = []

    # 1) request-wave deviation (0-40), log-scaled: 1x=0, ~5.5x=20, >=30x=40
    ratio = m["req"] / max(base["req"], 5.0)
    spike = max(0.0, min(40.0, 40.0 * math.log10(max(ratio, 1.0)) / math.log10(30.0)))
    if ratio >= 3:
        reasons.append("istek dalgası x%.1f (baseline %.0f/dk)" % (ratio, base["req"]))

    # 2) client concentration (0-20)
    conc = 0.0
    if m["req"] >= 60:
        if m["top_share"] >= 0.8:
            conc = 20.0
        elif m["top_share"] >= 0.5:
            conc = 12.0
        if conc and m["uniq"] <= 3 and ratio >= 2:
            conc = 20.0
    if conc >= 12:
        reasons.append("tek istemci trafiğin %%%.0f'i" % (m["top_share"] * 100))

    # 3) system distress (0-25)
    distress = 0.0
    distress += min(10.0, s["psi_cpu"] / 10.0 * 5.0)         # 20% some-CPU -> 10
    distress += min(10.0, s["psi_mem"] / 10.0 * 5.0)
    lr = s["load1"] / s["cores"]
    distress += min(8.0, max(0.0, lr - 1.0) * 8.0)           # saturated cores
    rt_ratio = m["rt_med"] / max(base["rt_med"], 0.05)
    distress += min(7.0, max(0.0, rt_ratio - 2.0) / 10.0 * 7.0)  # 12x slower -> 7
    if distress >= 12:
        reasons.append("sistem sıkışması (PSI cpu %.0f mem %.0f load %.1f RT x%.1f)"
                       % (s["psi_cpu"], s["psi_mem"], s["load1"], rt_ratio))

    # 4) error burst (0-15) — only meaningful under abnormal load
    err = 0.0
    if ratio >= 2 and m["req"] >= 100:
        err = min(15.0, max(0.0, m["err_rate"] - 0.25) / 0.5 * 15.0)
    if err >= 8:
        reasons.append("hata patlaması %%%.0f" % (m["err_rate"] * 100))

    # component maxima already encode the weights: 40+20+25+15 = 100
    score = spike + conc + distress + err

    # 5) SYN flood emergency booster
    syn = s["syn_recv"]
    if syn >= 1000:
        score += 60
        reasons.append("SYN flood (%d SYN_RECV)" % syn)
    elif syn >= 200 and syn >= max(base["syn_recv"] * 20, 100):
        score += 20
        reasons.append("SYN yığılması (%d)" % syn)

    score = min(100.0, score)
    return score, reasons


# ----------------------------------------------------------------------------
# Cloudflare
# ----------------------------------------------------------------------------

def cf_token():
    try:
        with open(CONF_FILE) as fh:
            for line in fh:
                if line.startswith("CF_API_TOKEN="):
                    return line.split("=", 1)[1].strip().strip("'")
    except OSError:
        pass
    return None


def cf_request(method, path, body=None, retries=2):
    token = cf_token()
    if not token:
        raise RuntimeError("CF_API_TOKEN not configured")
    data = json.dumps(body).encode() if body is not None else None
    last_err = None
    for _ in range(retries + 1):
        req = urllib.request.Request(CF_API + path, data=data, method=method)
        req.add_header("Authorization", "Bearer " + token)
        req.add_header("Content-Type", "application/json")
        try:
            with urllib.request.urlopen(req, timeout=20) as resp:
                out = json.load(resp)
                if out.get("success"):
                    return out.get("result")
                raise RuntimeError("CF API error: %s" % out.get("errors"))
        except urllib.error.HTTPError as e:
            last_err = "HTTP %s: %s" % (e.code, e.read()[:200])
        except Exception as e:
            last_err = str(e)
        time.sleep(1)
    raise RuntimeError("CF API failed (%s %s): %s" % (method, path, last_err))


def cf_zones():
    return [(z["id"], z["name"]) for z in cf_request("GET", "/zones?status=active&per_page=50")]


def cf_get_level(zone_id):
    return cf_request("GET", "/zones/%s/settings/security_level" % zone_id)["value"]


def cf_set_level(zone_id, level):
    return cf_request("PATCH", "/zones/%s/settings/security_level" % zone_id, {"value": level})


def engage(state):
    zones = cf_zones()
    prev = state.get("prev_levels") or {}
    changed, kept, failed = [], [], 0
    for zid, zname in zones:
        try:
            cur = cf_get_level(zid)
            if cur == "under_attack":
                kept.append(zname)
                continue
            if zid not in prev:
                prev[zid] = cur
            cf_set_level(zid, "under_attack")
            changed.append(zname)
        except Exception as e:
            failed += 1
            log("ERROR: under_attack uygulanamadı %s: %s" % (zname, e))
    state["prev_levels"] = prev
    # zones already under_attack (kept) also prove the API works
    return changed, kept, failed


def release(state):
    prev = state.get("prev_levels") or {}
    restored, failed = [], 0
    for zid, zname in cf_zones():
        try:
            target = prev.get(zid, "medium")
            cf_set_level(zid, target)
            restored.append("%s→%s" % (zname, target))
        except Exception as e:
            failed += 1
            log("ERROR: geri yükleme başarısız %s: %s" % (zname, e))
    state["prev_levels"] = {}
    return restored, failed


# ----------------------------------------------------------------------------
# Main decision loop
# ----------------------------------------------------------------------------

def run(once=True, dry=False):
    state = load_state()
    m = collect_nginx()
    s = collect_system()
    base = baseline_for(load_history())
    score, reasons = compute_score(m, s, base)
    now = int(time.time())
    forced = state.get("forced")  # "on" | "off" | None
    if forced and now - state.get("forced_at", 0) > FORCE_TTL:
        forced = None
        state["forced"] = None
        log("Manuel mod süresi doldu, otomatik moda dönüldü.")

    mode = state.get("mode", "calm")
    since = state.get("since", now)
    calm_since = state.get("calm_since")

    if forced == "on":
        want = "attack"
    elif forced == "off":
        want = "calm"
    else:
        want = "attack" if score >= ATTACK_ON or s["syn_recv"] >= 1000 else "calm"
        if want == "calm" and mode == "attack":
            # hysteretic release: score must stay under ATTACK_OFF for
            # RELEASE_MIN, and the attack mode must have lasted STAY_MIN
            if score >= ATTACK_OFF:
                want = "attack"
                calm_since = None
            else:
                if calm_since is None:
                    calm_since = now
                if (now - calm_since) < RELEASE_MIN or (now - since) < STAY_MIN:
                    want = "attack"

    if not dry:
        if want == "attack" and mode == "calm":
            log("SALDIRI TESPİTİ (skor %.0f): %s" % (score, "; ".join(reasons) or "acil"))
            try:
                changed, kept, failed = engage(state)
                if not changed and not kept:
                    # API rejected every zone (e.g. missing "Zone Settings:
                    # Edit" permission) — do NOT fake an attack state.
                    if now - state.get("last_perm_warn", 0) > 3600:
                        state["last_perm_warn"] = now
                        notify_panel("Error", "DDoS guard: Cloudflare API under_attack "
                                     "uygulayamadı (skor %.0f). Token'a 'Zone → Zone Settings → Edit' "
                                     "izni gerekiyor." % score)
                    log("CRITICAL: hiçbir zone'a under_attack uygulanamadı — CF token iznini kontrol et")
                else:
                    state.update({"mode": "attack", "since": now, "calm_since": None,
                                  "last_score": round(score, 1), "last_reasons": reasons})
                    notify_panel("Warning", "DDoS guard: Under Attack modu AÇILDI "
                                 "(skor %.0f — %s). Etkilenen: %s%s" %
                                 (score, "; ".join(reasons) or "acil",
                                  ", ".join(changed + kept) or "?",
                                  " (%d zone başarısız)" % failed if failed else ""))
            except Exception as e:
                log("ERROR: engage başarısız: %s" % e)
        elif want == "calm" and mode == "attack":
            log("Sakinleşme doğrulandı (skor %.0f) — Under Attack kapatılıyor" % score)
            try:
                restored, failed = release(state)
                state.update({"mode": "calm", "since": now, "calm_since": None,
                              "last_score": round(score, 1)})
                notify_panel("Info", "DDoS guard: saldırı geçti, güvenlik seviyeleri "
                             "geri yüklendi (skor %.0f). %s%s" %
                             (score, ", ".join(restored) or "-",
                              " (%d zone başarısız!)" % failed if failed else ""))
            except Exception as e:
                log("ERROR: release başarısız: %s" % e)
        else:
            # hold: maintain the calm counter for the attack-mode release timer
            if mode == "attack":
                if score >= ATTACK_OFF:
                    calm_since = None
                elif calm_since is None:
                    calm_since = now
            state["last_score"] = round(score, 1)
        state["calm_since"] = calm_since
        state["updated"] = now
        save_state(state)
        append_history({
            "ts": now, "req": m["req"], "uniq": m["uniq"], "top_share": round(m["top_share"], 3),
            "rt_med": m["rt_med"], "err_rate": round(m["err_rate"], 3),
            "syn_recv": s["syn_recv"], "load1": s["load1"], "score": round(score, 1),
        })
    return {"score": round(score, 1), "mode": state.get("mode", "?"), "want": want,
            "reasons": reasons, "metrics": m, "system": s, "base": base}


def status():
    state = load_state()
    r = run(dry=True)
    print("== NexviaCP DDoS Guard ==")
    print("Mod           : %s%s" % (state.get("mode", "calm"),
          " (manuel: %s)" % state["forced"] if state.get("forced") else ""))
    print("Şu anki skor  : %.1f / 100  (açılma >=%d, kapanma <%d)" % (r["score"], ATTACK_ON, ATTACK_OFF))
    print("Karar         : %s" % r["want"])
    if r["reasons"]:
        print("Sinyaller     :")
        for x in r["reasons"]:
            print("  - " + x)
    m, s, b = r["metrics"], r["system"], r["base"]
    print("Ölçüm (1 dk)  : %d istek, %d tekil istemci, en yoğun %%%.0f, hata %%%.0f" %
          (m["req"], m["uniq"], m["top_share"] * 100, m["err_rate"] * 100))
    print("Sistem        : load %.2f/%d çekirdek, PSI cpu %.1f mem %.1f, SYN_RECV %d" %
          (s["load1"], s["cores"], s["psi_cpu"], s["psi_mem"], s["syn_recv"]))
    print("Baseline      : %.0f istek/dk, RT %.3fs (%d örnek)" % (b["req"], b["rt_med"], b["samples"]))
    if state.get("prev_levels"):
        print("Kayıtlı eski seviyeler: %s" % state["prev_levels"])


# ----------------------------------------------------------------------------
# Self-test (synthetic scenarios, CF mocked, no writes)
# ----------------------------------------------------------------------------

class FakeCF:
    def __init__(self):
        self.levels = {"z1": "medium", "z2": "high"}
        self.calls = []
    def zones(self): return [("z1", "a.test"), ("z2", "b.test")]
    def get(self, z): return self.levels[z]
    def set(self, z, v): self.levels[z] = v; self.calls.append((z, v))


def self_test():
    global cf_zones, cf_get_level, cf_set_level
    fake = FakeCF()
    cf_zones, cf_get_level, cf_set_level = fake.zones, fake.get, fake.set

    ok = [0, 0]
    def check(name, cond):
        ok[0] += 1
        ok[1] += 1 if cond else 0
        print("  [%s] %s" % ("PASS" if cond else "FAIL", name))

    base = {"req": 40.0, "rt_med": 0.05, "syn_recv": 0.0, "load1": None, "samples": 200}
    def M(req=40, top=0.1, uniq=30, rt=0.05, err=0.0, psi_cpu=0, psi_mem=0, syn=0, load=0.5):
        return ({"req": req, "uniq": uniq, "top_share": top, "rt_med": rt,
                 "err_rate": err, "err4": 0, "err5": 0, "bot_ua": 0},
                {"psi_cpu": psi_cpu, "psi_mem": psi_mem, "syn_recv": syn,
                 "load1": load, "cores": 8})

    print("Senaryo 1: sakin düşük trafik → skor düşük, tetiklenmez")
    sc, rs = compute_score(*M(), base)
    check("skor < 20 (%.1f)" % sc, sc < 20)

    print("Senaryo 2: yüksek ama çok-istemcili meşru dalga → tek istemci cezası yok")
    sc, rs = compute_score(*M(req=400, uniq=200, top=0.05), base)
    check("skor < 55 (%.1f)" % sc, sc < ATTACK_ON)

    print("Senaryo 3: tek IP bot dalgası (30x, %95 pay) → saldırı")
    sc, rs = compute_score(*M(req=1200, uniq=3, top=0.95), base)
    check("skor >= 55 (%.1f)" % sc, sc >= ATTACK_ON)

    print("Senaryo 4: SYN flood acil yol → saldırı")
    sc, rs = compute_score(*M(syn=1500), base)
    check("skor >= 55 (%.1f)" % sc, sc >= ATTACK_ON)

    print("Senaryo 5: hacim orta ama sistem boğuluyor → saldırı")
    sc, rs = compute_score(*M(req=300, rt=0.6, psi_cpu=25, psi_mem=20, load=14), base)
    check("skor >= 55 (%.1f)" % sc, sc >= ATTACK_ON)

    print("Senaryo 6: error burst → katkı")
    sc1, _ = compute_score(*M(req=500, err=0.1), base)
    sc2, _ = compute_score(*M(req=500, err=0.8), base)
    check("hata skoru yükseltiyor (%.1f < %.1f)" % (sc1, sc2), sc2 > sc1 + 5)

    print("Senaryo 7: engage → under_attack + eski seviye kaydı")
    fake.levels = {"z1": "medium", "z2": "high"}
    st = {}
    changed, kept, _f = engage(st)
    check("her iki zone under_attack", fake.levels == {"z1": "under_attack", "z2": "under_attack"})
    check("eski seviyeler kaydedildi", st["prev_levels"] == {"z1": "medium", "z2": "high"})
    restored, _f2 = release(st)
    check("release geri yükler", fake.levels == {"z1": "medium", "z2": "high"})
    check("prev temizlendi", st["prev_levels"] == {})

    print("\nSELF-TEST: %d/%d PASS" % (ok[1], ok[0]))
    return 0 if ok[1] == ok[0] else 1


def main():
    arg = sys.argv[1] if len(sys.argv) > 1 else "--run"
    if arg == "--self-test":
        sys.exit(self_test())
    if arg == "--status":
        status()
        return
    if arg in ("--force-on", "--force-off", "--auto"):
        state = load_state()
        if arg == "--auto":
            state["forced"] = None
            log("Manuel mod kaldırıldı, otomatik karar devam ediyor.")
        else:
            state["forced"] = "on" if arg == "--force-on" else "off"
            state["forced_at"] = int(time.time())
            log("Manuel mod: %s" % state["forced"])
        state["updated"] = int(time.time())
        save_state(state)
        run()  # apply immediately
        return
    run()


if __name__ == "__main__":
    main()
