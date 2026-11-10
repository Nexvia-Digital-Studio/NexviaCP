#!/usr/bin/env python3
"""
Customer (non-admin) panel authorization regression tests.

Part 1 (PHP, always runs): boots the real panel PHP code under the PHP
built-in web server with every v-* command replaced by a recording mock,
logs in as a regular customer and checks that

  * admin-only pages and actions are refused server-side (not only hidden),
  * the customer cannot change admin-owned settings (resource limits,
    admin-only templates, .env secret manager, DB auto-sync, demo sites),
  * ownership gates stop the request instead of running on,
  * suspended/read-only and demoted sessions are enforced on every request,
  * the menu/buttons a customer sees match what the backend allows.

Part 2 (bash, runs only as root because it needs a real unix user for
user_exec/setpriv): runs the hardened root-side scripts against a planted
symlink and checks that root never reads, chowns or rewrites the target.

Usage: python3 tests/test_customer_panel_authz.py
"""

import http.client
import json
import os
import pwd
import re
import shutil
import socket
import subprocess
import sys
import tempfile
import time
import urllib.parse

REPO = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))

PASSED = 0
FAILED = []


def check(cond, name, details=""):
    global PASSED
    if cond:
        PASSED += 1
        print(f"  PASS  {name}")
    else:
        FAILED.append((name, details))
        print(f"  FAIL  {name}")
        if details:
            print(f"        {details}")


# ---------------------------------------------------------------------------
# Part 1: PHP panel under a mocked command layer
# ---------------------------------------------------------------------------

MOCK_SCRIPT = r'''#!/usr/bin/env python3
import json, os, sys
cmd = os.path.basename(sys.argv[0])
args = sys.argv[1:]
with open(os.environ["NX_MOCK_LOG"], "a") as fh:
    fh.write(json.dumps([cmd] + args) + "\n")
fx = json.load(open(os.environ["NX_MOCK_FIXTURES"]))
def out(obj, rc=0):
    print(json.dumps(obj) if not isinstance(obj, str) else obj)
    sys.exit(rc)
users = fx["users"]
if cmd == "v-list-sys-config":
    out({"config": fx["config"]})
if cmd == "v-list-users":
    out(users)
if cmd == "v-list-user":
    u = args[0] if args else ""
    if u in users:
        out({u: users[u]})
    out("Error: user doesn't exist", 3)
if cmd == "v-list-web-domains":
    out(fx["web"].get(args[0], {}))
if cmd == "v-list-web-domain":
    u, d = args[0], args[1]
    doms = fx["web"].get(u, {})
    if d in doms:
        out({d: doms[d]})
    out("Error: web domain " + d + " doesn't exist", 3)
if cmd in ("v-list-web-templates", "v-list-web-templates-proxy"):
    out(fx["templates"])
if cmd in ("v-list-web-templates-backend", "v-list-web-stats", "v-list-user-ips",
           "v-list-sys-ips", "v-list-databases", "v-list-web-domain-apps",
           "v-list-docker-app-databases", "v-list-user-notifications"):
    out({})
if cmd == "v-get-sys-sso-key":
    sys.exit(2)
out({})
'''

VENDOR_SHIM = r'''<?php
namespace Hestiacp\quoteshellarg {
	if (!function_exists(__NAMESPACE__ . "\\quoteshellarg")) {
		function quoteshellarg($arg) { return escapeshellarg((string) $arg); }
	}
}
'''

LOGIN_HELPER = r'''<?php
// Test-only session bootstrap (never shipped): logs the client in as ?u=
session_start();
$_SESSION = [];
$u = $_GET["u"] ?? "musteri";
$_SESSION["user"] = $u;
$_SESSION["userContext"] = $_GET["ctx"] ?? "user";
$_SESSION["look"] = $_GET["look"] ?? "";
$_SESSION["token"] = "testtoken0123456789abcdef";
$_SESSION["LAST_ACTIVITY"] = time();
$_SESSION["INACTIVE_SESSION_TIMEOUT"] = 60;
$_SESSION["VERSION"] = "1.10.0";
$_SESSION["DISABLE_IP_CHECK"] = "yes";
$_SESSION["userSortOrder"] = "name";
$_SESSION["language"] = "en";
echo "ok";
'''

TOKEN = "testtoken0123456789abcdef"


def user_record(role="user", suspended="no"):
    rec = {
        "NAME": "Test", "ROLE": role, "SUSPENDED": suspended, "SHELL": "nologin",
        "LANGUAGE": "en", "THEME": "dark", "PACKAGE": "default", "CONTACT": "t@example.com",
        "WEB_TEMPLATE": "default", "BACKEND_TEMPLATE": "default", "PROXY_TEMPLATE": "default",
        "SUSPENDED_USERS": "0", "U_USERS": "1", "U_DISK": "0", "DISK_QUOTA": "unlimited",
        "U_BANDWIDTH": "0", "BANDWIDTH": "unlimited", "NOTIFICATIONS": "no",
    }
    for k in ("WEB_DOMAINS", "WEB_ALIASES", "DNS_DOMAINS", "DNS_RECORDS", "MAIL_DOMAINS",
              "MAIL_ACCOUNTS", "DATABASES", "CRON_JOBS", "BACKUPS"):
        rec[k] = "unlimited"
        rec["U_" + k] = "0"
        rec["SUSPENDED_" + k.split("_")[0]] = "0"
    return rec


def web_record(tpl="default"):
    return {
        "IP": "192.0.2.10", "TPL": tpl, "ALIAS": "", "SSL": "no", "LETSENCRYPT": "no",
        "PROXY": "default", "PROXY_EXT": "", "STATS": "", "STATS_USER": "",
        "BACKEND": "default", "SUSPENDED": "no", "TIME": "00:00:00", "DATE": "2026-01-01",
        "REDIRECT": "", "FTP_USER": "", "FTP_PATH": "", "CUSTOM_DOCROOT": "",
        "WEB_CGROUP_HIGH": "512M", "WEB_CGROUP_MAX": "1G", "WEB_CPU_QUOTA": "50%",
    }


def fixtures():
    return {
        "users": {
            "admin": user_record("admin"),
            "musteri": user_record("user"),
            "askida": user_record("user", "yes"),
            "eskiadmin": user_record("user"),
        },
        "web": {
            "admin": {"admin.example": web_record()},
            "musteri": {"musteri.example": web_record()},
            "askida": {"askida.example": web_record()},
            "baskasi": {"baskasi.example": web_record()},
        },
        "templates": ["default", "hosting", "docker-ui", "docker-app", "panelproxy"],
        "config": {
            "VERSION": "1.10.0", "RELEASE_BRANCH": "release", "DEBUG_MODE": "false",
            "POLICY_CSRF_STRICTNESS": "0", "POLICY_USER_VIEW_SUSPENDED": "yes",
            "POLICY_USER_EDIT_WEB_TEMPLATES": "yes", "POLICY_SYSTEM_PROTECTED_ADMIN": "no",
            "POLICY_SYSTEM_HIDE_SERVICES": "no", "INACTIVE_SESSION_TIMEOUT": "60",
            "ROOT_USER": "admin", "WEB_SYSTEM": "nginx", "PROXY_SYSTEM": "", "WEB_BACKEND": "",
            "DNS_SYSTEM": "", "MAIL_SYSTEM": "", "DB_SYSTEM": "mysql", "FILE_MANAGER": "false",
            "WEB_TERMINAL": "false", "HIDE_DOCS": "yes", "LANGUAGE": "en", "THEME": "dark",
            "DISABLE_IP_CHECK": "yes", "LOGIN_STYLE": "default", "APP_NAME": "NexviaCP",
        },
    }


class Panel:
    def __init__(self):
        self.tmp = tempfile.mkdtemp(prefix="nx-authz-")
        self.web = os.path.join(self.tmp, "web")
        shutil.copytree(os.path.join(REPO, "web"), self.web,
                        ignore=shutil.ignore_patterns("node_modules", "fm"))
        mockbin = os.path.join(self.tmp, "mockbin")
        os.makedirs(mockbin)
        dispatcher = os.path.join(self.tmp, "mock.py")
        with open(dispatcher, "w") as fh:
            fh.write(MOCK_SCRIPT)
        os.chmod(dispatcher, 0o755)
        names = set(os.listdir(os.path.join(REPO, "bin")))
        for root, _, files in os.walk(os.path.join(REPO, "web")):
            for f in files:
                if f.endswith(".php"):
                    with open(os.path.join(root, f), errors="ignore") as fh:
                        names.update(re.findall(r"\bv-[a-z0-9-]+", fh.read()))
        for n in names:
            if re.match(r"^v-[a-z0-9-]+$", n):
                os.symlink(dispatcher, os.path.join(mockbin, n))
        main = os.path.join(self.web, "inc", "main.php")
        src = open(main).read()
        src = src.replace('define("HESTIA_CMD", "/usr/bin/sudo /usr/local/hestia/bin/");',
                          'define("HESTIA_CMD", "' + mockbin + '/");')
        open(main, "w").write(src)
        os.makedirs(os.path.join(self.web, "inc", "vendor"), exist_ok=True)
        with open(os.path.join(self.web, "inc", "vendor", "autoload.php"), "w") as fh:
            fh.write(VENDOR_SHIM)
        os.makedirs(os.path.join(self.web, "__t"))
        with open(os.path.join(self.web, "__t", "login.php"), "w") as fh:
            fh.write(LOGIN_HELPER)
        self.log = os.path.join(self.tmp, "calls.log")
        self.fx = os.path.join(self.tmp, "fixtures.json")
        self.write_fixtures(fixtures())
        prepend = os.path.join(self.tmp, "prepend.php")
        with open(prepend, "w") as fh:
            fh.write('<?php $_SERVER["HESTIA"] = ' + json.dumps(self.tmp) + ';\n')
        sess = os.path.join(self.tmp, "sessions")
        os.makedirs(sess)
        with socket.socket() as s:
            s.bind(("127.0.0.1", 0))
            self.port = s.getsockname()[1]
        env = dict(os.environ, NX_MOCK_LOG=self.log, NX_MOCK_FIXTURES=self.fx, HESTIA=self.tmp)
        self.proc = subprocess.Popen(
            ["php", "-d", "session.save_path=" + sess, "-d", "auto_prepend_file=" + prepend, "-d", "display_errors=0",
             "-d", "log_errors=1", "-d", "error_log=" + os.path.join(self.tmp, "php.log"),
             "-S", f"127.0.0.1:{self.port}", "-t", self.web],
            env=env, stdout=subprocess.DEVNULL, stderr=subprocess.DEVNULL)
        for _ in range(50):
            try:
                socket.create_connection(("127.0.0.1", self.port), timeout=0.2).close()
                break
            except OSError:
                time.sleep(0.1)
        self.cookie = ""

    def write_fixtures(self, fx):
        with open(self.fx, "w") as fh:
            json.dump(fx, fh)

    def close(self):
        self.proc.terminate()
        self.proc.wait(timeout=5)
        if os.environ.get("NX_KEEP"):
            print("  (kept " + self.tmp + ")")
            return
        shutil.rmtree(self.tmp, ignore_errors=True)

    def request(self, method, path, data=None):
        conn = http.client.HTTPConnection("127.0.0.1", self.port, timeout=30)
        headers = {"Host": f"127.0.0.1:{self.port}"}
        if self.cookie:
            headers["Cookie"] = self.cookie
        body = None
        if data is not None:
            body = urllib.parse.urlencode(data, doseq=True)
            headers["Content-Type"] = "application/x-www-form-urlencoded"
        conn.request(method, path, body=body, headers=headers)
        resp = conn.getresponse()
        text = resp.read().decode("utf-8", "replace")
        for k, v in resp.getheaders():
            if k.lower() == "set-cookie" and v.startswith("PHPSESSID="):
                self.cookie = v.split(";", 1)[0]
        return resp.status, resp.getheader("Location") or "", text

    def login(self, user="musteri", ctx="user", look=""):
        self.cookie = ""
        q = urllib.parse.urlencode({"u": user, "ctx": ctx, "look": look})
        self.request("GET", "/__t/login.php?" + q)

    def reset_log(self):
        open(self.log, "w").close()

    def calls(self):
        try:
            with open(self.log) as fh:
                return [json.loads(line) for line in fh if line.strip()]
        except FileNotFoundError:
            return []

    def called(self, cmd):
        return [c for c in self.calls() if c[0] == cmd]


ADMIN_ONLY_PAGES = [
    "/list/demo/", "/list/cache/", "/list/waf/", "/list/docker/", "/list/docker-app/",
    "/list/api/", "/list/resources/", "/list/ai-healing/", "/list/anomalies/",
    "/list/health/", "/list/notify/", "/list/domain-expiry/", "/list/cves/",
    "/list/maintenance/", "/list/mail_queue/", "/list/api-audit/", "/list/servers/",
    "/list/templates/", "/list/cloud-backup/", "/edit/server/github/", "/edit/template/",
    "/add/docker/", "/list/server/", "/list/rrd/", "/list/package/", "/add/user/",
    "/list/firewall/", "/list/ip/", "/list/updates/",
]

# Root-side commands a customer must never be able to trigger from the panel.
FORBIDDEN_FOR_CUSTOMER = {
    "v-add-demo-site", "v-update-demo-site", "v-delete-demo-site", "v-list-demo-sites",
    "v-apply-mysql-index", "v-add-web-domain-redis", "v-delete-web-domain-redis",
    "v-purge-web-domain-cache", "v-change-sys-mysql-slowlog", "v-list-cache-governance",
    "v-set-web-domain-env", "v-delete-web-domain-env", "v-list-web-domain-env",
    "v-change-web-domain-cgroup", "v-sync-sys-databases", "v-list-github-repos",
    "v-add-docker-app", "v-deploy-github-repo", "v-set-sys-global-whitelist",
}


def run_php_tests():
    print("\n[1] Customer panel (PHP, mocked command layer)")
    p = Panel()
    try:
        # --- admin-only pages are refused server-side -------------------
        p.login("musteri")
        for page in ADMIN_ONLY_PAGES:
            status, loc, body = p.request("GET", page)
            check(status in (301, 302, 303) and "/login" not in loc,
                  f"customer GET {page} is redirected away", f"status={status} loc={loc}")

        # --- admin-only actions do not reach a root command -------------
        p.reset_log()
        p.request("POST", "/list/demo/", {"token": TOKEN, "action": "add",
                                          "demo_repo_name": "Nexvia-private-repo",
                                          "demo_build": "1", "demo_build_cmd": "id"})
        p.request("POST", "/list/cache/", {"token": TOKEN, "apply_sql_index": "1",
                                           "target_db": "baskasi_db", "target_user": "musteri",
                                           "index_sql": "CREATE INDEX i ON t (c);"})
        p.request("POST", "/list/cache/", {"token": TOKEN, "assign_redis_db": "1",
                                           "domain_name": "musteri.example", "redis_db": "3"})
        p.request("POST", "/list/db/", {"token": TOKEN, "action_sync_db": "1"})
        p.request("POST", "/edit/web/?domain=musteri.example",
                  {"token": TOKEN, "save_env_secret": "1", "env_key": "X", "env_value": "y"})
        p.request("GET", "/edit/web/?domain=musteri.example&delete_env=1&key=X&token=" + TOKEN)
        bad = sorted({c[0] for c in p.calls()} & FORBIDDEN_FOR_CUSTOMER)
        check(not bad, "customer actions never reach admin-only root commands", f"called: {bad}")

        # --- .env manager card hidden + listing not fetched -------------
        p.reset_log()
        status, _, body = p.request("GET", "/edit/web/?domain=musteri.example")
        check(status == 200, "customer can open own domain edit page", f"status={status}")
        check("save_env_secret" not in body, "edit web: .env secret manager not rendered for customer")
        check(not p.called("v-list-web-domain-env"), "edit web: customer page does not read .env")
        opts = re.findall(r'<option value="([^"]+)"', body)
        check("docker-ui" not in opts and "panelproxy" not in opts and "docker-app" not in opts,
              "edit web: admin-only templates not offered to customer", f"options={opts}")

        # --- save without cgroup fields must not wipe limits ------------
        p.reset_log()
        save = {"token": TOKEN, "save": "1", "v_domain": "musteri.example", "v_ip": "192.0.2.10",
                "v_template": "default", "v_stats": "none"}
        p.request("POST", "/edit/web/?domain=musteri.example", save)
        check(not p.called("v-change-web-domain-cgroup"),
              "edit web: customer save does not touch RAM/CPU limits")
        p.reset_log()
        p.request("POST", "/edit/web/?domain=musteri.example",
                  dict(save, v_web_cgroup_high="unlimited", v_web_cgroup_max="unlimited",
                       v_web_cpu_quota="unlimited"))
        check(not p.called("v-change-web-domain-cgroup"),
              "edit web: customer cannot lift RAM/CPU limits with forged fields")

        # --- admin-only template rejected ---------------------------------
        p.reset_log()
        status, loc, _ = p.request("POST", "/edit/web/?domain=musteri.example",
                                   dict(save, v_template="docker-ui"))
        check(not p.called("v-change-web-domain-tpl"),
              "edit web: customer cannot switch to the docker-ui (Portainer) template")

        # --- foreign domain: ownership gate stops the request -------------
        p.reset_log()
        status, loc, _ = p.request("POST", "/edit/web/?domain=baskasi.example",
                                   dict(save, v_domain="baskasi.example", git_pull="1"))
        names = [c[0] for c in p.calls()]
        after = names[names.index("v-list-web-domain") + 1:] if "v-list-web-domain" in names else names
        check(status in (301, 302, 303) and "/list/web" in loc,
              "edit web: foreign domain redirects to /list/web/", f"status={status} loc={loc}")
        check(not [n for n in after if n not in ("v-list-sys-config", "v-list-user")],
              "edit web: nothing runs after the ownership check fails", f"after={after}")

        # --- menus/buttons match backend rules ----------------------------
        status, _, body = p.request("GET", "/list/web/")
        check(status == 200, "customer can open web list", f"status={status}")
        for href in ("/list/demo/", "/list/cache/", "/list/waf/", "/list/docker/", "/list/api/"):
            check(f'href="{href}"' not in body, f"menu: customer does not see {href}")
        check('href="/list/log-search/"' in body, "menu: customer still sees own Log Search")
        status, _, body = p.request("GET", "/list/db/")
        check("action_sync_db" not in body, "db list: admin-only Auto-Sync button hidden for customer")

        # --- admin still has the features ----------------------------------
        p.login("admin", "admin")
        status, _, body = p.request("GET", "/list/web/")
        check('href="/list/demo/"' in body, "menu: admin still sees Demo Sites")
        status, _, _ = p.request("GET", "/list/cache/")
        check(status == 200, "admin can still open the cache page", f"status={status}")
        p.reset_log()
        p.request("POST", "/edit/web/?domain=admin.example",
                  dict(save, v_domain="admin.example", v_web_cgroup_high="256M",
                       v_web_cgroup_max="512M", v_web_cpu_quota="100%"))
        check(bool(p.called("v-change-web-domain-cgroup")), "admin can still change RAM/CPU limits")
        p.reset_log()
        p.request("POST", "/edit/web/?domain=admin.example",
                  {"token": TOKEN, "save_env_secret": "1", "env_key": "X", "env_value": "y"})
        check(bool(p.called("v-set-web-domain-env")), "admin can still manage .env secrets")

        # --- suspended customer: read-only session ------------------------
        p.login("askida")
        p.reset_log()
        status, loc, _ = p.request("POST", "/add/web/", {"token": TOKEN, "ok": "1",
                                                          "v_domain": "yeni.example"})
        check(not p.called("v-add-web-domain"), "suspended (read-only) customer cannot add a domain")
        status, _, _ = p.request("GET", "/list/web/")
        check(status == 200, "suspended (read-only) customer can still view pages", f"status={status}")
        p.reset_log()
        p.request("GET", "/delete/web/?domain=askida.example&token=" + TOKEN)
        check(not p.called("v-delete-web-domain"), "suspended (read-only) customer cannot delete")

        # --- demoted admin loses access on the next request ---------------
        p.login("eskiadmin", "admin")
        p.reset_log()
        status, loc, _ = p.request("GET", "/list/demo/")
        check(status in (301, 302, 303) and "/login" in loc,
              "admin demoted while logged in is logged out on next request", f"loc={loc}")
        check(not p.called("v-list-demo-sites"), "demoted admin cannot reach admin data")

        # --- deleted user loses the session --------------------------------
        p.login("silinmis")
        status, loc, _ = p.request("GET", "/list/web/")
        check(status in (301, 302, 303) and "/login" in loc,
              "deleted account's session is destroyed", f"loc={loc}")
    finally:
        p.close()


# ---------------------------------------------------------------------------
# Part 2: root-side scripts vs. planted symlinks (root only)
# ---------------------------------------------------------------------------

def run_script_tests():
    print("\n[2] Root-side scripts vs. customer-planted symlinks")
    if os.geteuid() != 0 or not shutil.which("setpriv") or not shutil.which("useradd"):
        print("  SKIP  needs root + setpriv/useradd (run inside a disposable container)")
        return
    uname = "nxauthz"
    try:
        pwd.getpwnam(uname)
    except KeyError:
        subprocess.run(["useradd", "-M", "-s", "/usr/sbin/nologin", uname], check=True)
    tmp = tempfile.mkdtemp(prefix="nx-scripts-")
    os.chmod(tmp, 0o755)
    try:
        hestia = os.path.join(tmp, "hestia")
        homedir = os.path.join(tmp, "home")
        os.makedirs(os.path.join(hestia, "conf"))
        os.makedirs(os.path.join(hestia, "data", "users", uname))
        os.makedirs(os.path.join(hestia, "log"))
        shutil.copytree(os.path.join(REPO, "func"), os.path.join(hestia, "func"))
        with open(os.path.join(hestia, "conf", "hestia.conf"), "w") as fh:
            fh.write("WEB_SYSTEM='nginx'\n")
        conf = os.path.join(tmp, "hestia.etc.conf")
        with open(conf, "w") as fh:
            fh.write(f"HESTIA='{hestia}'\n")
        udata = os.path.join(hestia, "data", "users", uname)
        open(os.path.join(udata, "user.conf"), "w").write("SUSPENDED='no'\n")
        open(os.path.join(udata, "web.conf"), "w").write("DOMAIN='site.example' SUSPENDED='no'\n")
        pw = pwd.getpwnam(uname)
        dom = os.path.join(homedir, uname, "web", "site.example")
        docroot = os.path.join(dom, "public_html")
        os.makedirs(os.path.join(dom, "logs"))
        os.makedirs(docroot)
        for d in (os.path.join(homedir, uname), os.path.join(homedir, uname, "web"), dom,
                  docroot, os.path.join(dom, "logs")):
            os.chown(d, pw.pw_uid, pw.pw_gid)
        os.chmod(homedir, 0o755)

        secret = os.path.join(tmp, "root-secret")
        with open(secret, "w") as fh:
            fh.write("ROOT_ONLY_SECRET=topsecret\n")
        os.chown(secret, 0, 0)
        os.chmod(secret, 0o600)

        def script(name):
            src = open(os.path.join(REPO, "bin", name)).read().lstrip("﻿")
            src = src.replace("/etc/hestiacp/hestia.conf", conf)
            path = os.path.join(tmp, name)
            open(path, "w").write(src)
            os.chmod(path, 0o755)
            return path

        def run(name, *args):
            env = dict(os.environ, HESTIA=hestia)
            return subprocess.run(["bash", script(name), *args], env=env,
                                  capture_output=True, text=True, timeout=60)

        # Make HOMEDIR stick even though func/main.sh sets it.
        main_sh = os.path.join(hestia, "func", "main.sh")
        txt = open(main_sh).read()
        txt = re.sub(r"^HOMEDIR=.*$", f"HOMEDIR='{homedir}'", txt, flags=re.M)
        if f"HOMEDIR='{homedir}'" not in txt:
            txt = f"HOMEDIR='{homedir}'\n" + txt
        open(main_sh, "w").write(txt)

        def secret_intact():
            st = os.stat(secret)
            return st.st_uid == 0 and (st.st_mode & 0o777) == 0o600 and \
                open(secret).read() == "ROOT_ONLY_SECRET=topsecret\n"

        env_path = os.path.join(docroot, ".env")

        # v-set-web-domain-env: planted .env -> root file
        os.symlink(secret, env_path)
        r = run("v-set-web-domain-env", uname, "site.example", "APP_KEY", "value")
        check(secret_intact(), "v-set-web-domain-env does not chown/chmod/rewrite a symlink target",
              r.stdout + r.stderr)
        check(not os.path.islink(env_path) and "APP_KEY=\"value\"" in open(env_path).read()
              and "topsecret" not in open(env_path).read(),
              "v-set-web-domain-env writes the user's own .env without leaking the target")

        # v-list-web-domain-env must not reveal keys of a root file
        os.unlink(env_path)
        os.symlink(secret, env_path)
        r = run("v-list-web-domain-env", uname, "site.example", "json")
        check("ROOT_ONLY_SECRET" not in r.stdout, "v-list-web-domain-env does not read a root file",
              r.stdout[:200])

        # v-delete-web-domain-env
        r = run("v-delete-web-domain-env", uname, "site.example", "ROOT_ONLY_SECRET")
        check(secret_intact(), "v-delete-web-domain-env leaves the symlink target alone",
              r.stdout + r.stderr)
        if os.path.lexists(env_path):
            os.unlink(env_path)

        # v-stream-domain-logs: php-error.log -> root file
        os.symlink(secret, os.path.join(dom, "logs", "php-error.log"))
        r = run("v-stream-domain-logs", uname, "site.example", "php-error", "50", "no", "text")
        check("topsecret" not in r.stdout, "v-stream-domain-logs does not read a symlinked log as root",
              r.stdout[:200])

        # DB Studio: customer SQL must not run with the MySQL root account
        fakebin = os.path.join(tmp, "fakebin")
        os.makedirs(fakebin)
        mysql_log = os.path.join(tmp, "mysql.log")
        with open(os.path.join(fakebin, "mysql"), "w") as fh:
            fh.write("#!/bin/bash\nprintf '%s\\n' \"$*\" >> " + mysql_log + "\necho 'c'\necho '1'\n")
        os.chmod(os.path.join(fakebin, "mysql"), 0o755)
        open(os.path.join(udata, "db.conf"), "w").write("DB='nxauthz_db' TYPE='mysql'\n")
        env = dict(os.environ, HESTIA=hestia, PATH=fakebin + ":" + os.environ["PATH"])
        r = subprocess.run(["bash", script("v-explore-sys-database"), uname, "nxauthz_db", "",
                            "SELECT * FROM other_db.wp_users"], env=env, capture_output=True,
                           text=True, timeout=60)
        calls = open(mysql_log).read().splitlines() if os.path.exists(mysql_log) else []
        query_calls = [c for c in calls if "other_db.wp_users" in c]
        check(query_calls and all("/root/.my.cnf" not in c for c in query_calls),
              "DB Studio custom SQL never runs as MySQL root", "\n".join(calls)[-600:])
        check(any("GRANT SELECT, SHOW VIEW ON `nxauthz_db`.*" in c for c in calls)
              and any("DROP USER IF EXISTS" in c for c in calls),
              "DB Studio uses a throw-away account limited to the own database")
        r = subprocess.run(["bash", script("v-explore-sys-database"), uname, "baskasi_db", "",
                            "SELECT 1"], env=env, capture_output=True, text=True, timeout=60)
        check("Only your own databases" in r.stdout, "DB Studio refuses another tenant's database")

        # v-apply-mysql-index: foreign database is refused before any SQL
        open(mysql_log, "w").close()
        r = subprocess.run(["bash", script("v-apply-mysql-index"), uname, "baskasi_db",
                            "CREATE INDEX i ON t (c);"], env=env, capture_output=True, text=True,
                           timeout=60)
        check(r.returncode != 0 and not open(mysql_log).read(),
              "v-apply-mysql-index refuses a database the user does not own", r.stderr[-300:])

        # Symlinked docroot is refused
        shutil.rmtree(docroot)
        os.symlink(tmp, docroot)
        r = run("v-set-web-domain-env", uname, "site.example", "K", "v")
        check(r.returncode != 0 and secret_intact(),
              "env scripts refuse a docroot that is a symlink", r.stdout + r.stderr)
        for name, args in (("v-update-web-domain-git", ()),
                           ("v-add-web-domain-git", ("https://github.com/o/r.git", "main"))):
            r = run(name, uname, "site.example", *args)
            check(r.returncode != 0 and "unsafe path" in (r.stdout + r.stderr),
                  f"{name} refuses a symlinked docroot", (r.stdout + r.stderr)[-300:])
    finally:
        shutil.rmtree(tmp, ignore_errors=True)


def main():
    if not shutil.which("php"):
        print("php not found")
        return 1
    run_php_tests()
    run_script_tests()
    print(f"\n{PASSED} passed, {len(FAILED)} failed")
    for name, details in FAILED:
        print(f"  - {name}: {details}")
    return 1 if FAILED else 0


if __name__ == "__main__":
    sys.exit(main())
