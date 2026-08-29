#!/usr/bin/env python3
# nexvia repo structure analyzer — turns a cloned repo into a deployment plan
# usage: repo_scan.py REPO_DIR [BRANCH] -> single JSON object on stdout
import json
import os
import re
import subprocess
import sys

MAX_WALK_DEPTH = 3
BIG_FILE_MB = 20
DIR_SIZE_WARN_MB = 100
GENERATED_DIRS = {"node_modules", "vendor", "dist", "build", "bin", "obj", ".next", "coverage"}
ENV_FILES = [".env.example", ".env.sample", ".env.template", "env.example", ".env.example.txt"]
SCHEMA_FILES = ["schema.sql", "database.sql", "db.sql", "install.sql", "build/schema.sql"]
SEED_SQL_FILES = ["seed.sql", "seeds.sql", "database/seed.sql", "database/seeds.sql",
                  "build/seed.sql", "db/seed.sql", "db/seeds.sql", "data/seed.sql"]
SEED_SQL_DIRS = ["seeds", "database/seeds", "build/seeds", "db/seeds"]
DB_IMAGES = re.compile(r"(postgres|mysql|mariadb|mongo|redis|memcached|clickhouse)", re.I)
API_DIR_HINTS = re.compile(r"(api|backend|server)", re.I)
WEB_DIR_HINTS = re.compile(r"(web|frontend|client|ui|app|admin|panel|site)", re.I)


def rel(root, path):
    return os.path.relpath(path, root).replace(os.sep, "/")


def read_text(path, limit=200_000):
    try:
        with open(path, "r", encoding="utf-8", errors="replace") as fh:
            return fh.read(limit)
    except OSError:
        return ""


def parse_env_file(path):
    """Parse a .env.example-style file into display-friendly var metadata."""
    vars_, desc = [], ""
    for line in read_text(path).splitlines():
        line = line.strip()
        if not line:
            desc = ""
            continue
        if line.startswith("#"):
            desc = line.lstrip("# ").strip()
            continue
        m = re.match(r"(?:export\s+)?([A-Za-z_][A-Za-z0-9_]*)\s*=\s*(.*)$", line)
        if not m:
            continue
        key, val = m.group(1), m.group(2).strip().strip("'\"")
        placeholder = re.search(
            r"^(|changeme|change_me|xxx+|<.*>|\{\{.*\}\}|your[-_ ].*|.*-here|placeholders?.*|example.*)$",
            val, re.I)
        if re.match(r"^[0-9]+$", val) or val.lower() in ("true", "false"):
            kind = "bool" if val.lower() in ("true", "false") else "number"
        elif key.endswith("PORT") or key in ("PORT", "HTTP_PORT"):
            kind = "port"
        elif re.search(r"(KEY|SECRET|TOKEN|PASSWORD|PASS|CREDENTIAL)", key):
            kind = "secret"
        elif re.search(r"(URL|URI|HOST|ENDPOINT|ORIGIN|DSN)", key) or val.startswith(("http://", "https://")):
            kind = "url"
        elif "@" in val and "." in val:
            kind = "email"
        else:
            kind = "text"
        group = ("database" if re.search(r"^(DB_|DATABASE_|POSTGRES_|MYSQL_|MONGO_|REDIS_)", key)
                 else "mail" if re.search(r"(MAIL|SMTP)", key)
                 else "app" if re.match(r"^APP_|^NEXT_PUBLIC|^VITE_", key)
                 else "other")
        vars_.append({"key": key, "example": val, "required": bool(placeholder),
                      "kind": kind, "group": group, "description": desc})
        desc = ""
    return vars_


def find_env_template(root, subdir=""):
    base = os.path.join(root, subdir) if subdir else root
    for name in ENV_FILES:
        p = os.path.join(base, name)
        if os.path.isfile(p):
            return rel(root, p), parse_env_file(p)
    return None, []


def detect_framework(pkg_path):
    """Map a package.json to (tech key, deploy mode, icon)."""
    try:
        pkg = json.loads(read_text(pkg_path))
    except ValueError:
        return "Node.js", "node", "🟢"
    deps = {**(pkg.get("dependencies") or {}), **(pkg.get("devDependencies") or {})}
    if "next" in deps:
        return "Next.js", "next", "▲"
    if "@nestjs/core" in deps:
        return "NestJS", "node", "🟢"
    if "vite" in deps and "react" in deps:
        return "React / Vite", "react", "⚛️"
    if "react-scripts" in deps or ("react" in deps and "vite" not in deps):
        return "React", "react", "⚛️"
    if "vue" in deps or "nuxt" in deps:
        return ("Nuxt" if "nuxt" in deps else "Vue"), "react", "💚"
    if "svelte" in deps:
        return "Svelte", "react", "🧡"
    if any(d in deps for d in ("express", "fastify", "koa", "@hapi/hapi", "hapi")):
        return "Node.js API", "node", "🟢"
    scripts = pkg.get("scripts") or {}
    if "build" in scripts and "start" in scripts:
        return "Node.js", "node", "🟢"
    return "Node.js", "node", "🟢"


def node_entry(subdir_path):
    for name in ("server.js", "app.js", "index.js"):
        if os.path.isfile(os.path.join(subdir_path, name)):
            return name
    return None


def detect_component(root, subdir):
    """Detect one deployable component in root/subdir -> dict or None."""
    base = os.path.join(root, subdir) if subdir else root
    comp = {"name": subdir or "root", "path": subdir or "."}
    if os.path.isfile(os.path.join(base, "docker-compose.yml")) or \
       os.path.isfile(os.path.join(base, "compose.yml")) or \
       os.path.isfile(os.path.join(base, "compose.yaml")):
        return None  # handled by compose channel
    if os.path.isfile(os.path.join(base, "artisan")):
        comp.update({"type": "web", "tech": "Laravel (PHP)", "mode": "php",
                     "icon": "🐘", "entry": "public/index.php"})
    elif glob_first(base, "*.csproj") or glob_first(base, "*.sln"):
        cs = glob_first(base, "*.csproj") or glob_first(base, "*.sln")
        comp.update({"type": "api", "tech": ".NET / ASP.NET Core", "mode": "dotnet",
                     "icon": "🟣", "entry": cs})
    elif os.path.isfile(os.path.join(base, "package.json")):
        tech, mode, icon = detect_framework(os.path.join(base, "package.json"))
        entry = node_entry(base) or ("npm start" if mode == "next" else None)
        if mode in ("react",):
            comp.update({"type": "web", "tech": tech, "mode": mode, "icon": icon,
                         "entry": "dist/ (build)"})
        elif mode == "next":
            comp.update({"type": "web", "tech": tech, "mode": "node", "icon": icon,
                         "entry": "next start", "next": True})
        else:
            comp.update({"type": "api", "tech": tech, "mode": "node", "icon": icon,
                         "entry": entry})
    elif os.path.isfile(os.path.join(base, "index.php")) or os.path.isfile(os.path.join(base, "composer.json")):
        comp.update({"type": "web", "tech": "PHP", "mode": "php", "icon": "🐘",
                     "entry": "index.php"})
    elif os.path.isfile(os.path.join(base, "index.html")):
        comp.update({"type": "web", "tech": "Statik HTML", "mode": "php", "icon": "📄",
                     "entry": "index.html"})
    elif os.path.isfile(os.path.join(base, "requirements.txt")) or os.path.isfile(os.path.join(base, "pyproject.toml")):
        comp.update({"type": "api", "tech": "Python", "mode": "python", "icon": "🐍",
                     "entry": "app.py / main.py"})
    else:
        return None
    env_file, env_vars = find_env_template(root, subdir)
    if env_file:
        comp["env_file"] = env_file
        comp["env_vars"] = env_vars
    return comp


def glob_first(base, pattern):
    import glob as g
    hits = sorted(g.glob(os.path.join(base, pattern)))
    return rel(base, hits[0]) if hits else None


def find_compose(root, subdir=""):
    base = os.path.join(root, subdir) if subdir else root
    for name in ("docker-compose.yml", "docker-compose.yaml", "compose.yml", "compose.yaml"):
        p = os.path.join(base, name)
        if os.path.isfile(p):
            return rel(root, p)
    return None


def compose_config(root, compose_rel, workdir):
    """Normalize the compose file through `docker compose config` (handles env
    substitution + anchors). Returns (config dict or None, error string)."""
    env_file = os.path.join(root, ".env")
    if not os.path.isfile(env_file):
        env_file = os.path.join(root, ".env.example")
        if not os.path.isfile(env_file):
            env_file = "/dev/null"
    # services may reference env_file paths (commonly .env) that don't exist in
    # a fresh clone: create them empty so `compose config` can normalize, and
    # remove anything we created afterwards
    compose_dir = os.path.dirname(os.path.join(root, compose_rel))
    text = read_text(os.path.join(root, compose_rel))
    created = []
    for m in re.finditer(r"^\s+(?:-\s+)?env_file:\s*([^\s#]+)", text, re.M):
        target = os.path.join(compose_dir, m.group(1).strip("'\""))
        if not os.path.exists(target):
            open(target, "w").close()
            created.append(target)
    cmd = ["docker", "compose", "--env-file", env_file, "-f", compose_rel, "config", "--format", "json"]
    p = subprocess.run(cmd, cwd=workdir, capture_output=True, text=True, timeout=60)
    cfg = None
    err = None
    if p.returncode != 0:
        err = (p.stderr or "").strip().splitlines()[-1:] or ["compose config failed"]
    else:
        try:
            cfg = json.loads(p.stdout)
        except ValueError:
            err = ["cannot parse compose config output"]
    for path in created:
        try:
            os.unlink(path)
        except OSError:
            pass
    return cfg, err


def parse_compose(root, compose_rel):
    """Extract services, ports, health, links and risks from a compose file."""
    cfg, err = compose_config(root, compose_rel, root)
    services, warnings = [], []
    if cfg is None:
        # fallback: shallow section-aware scan so the wizard still shows something
        services = []
        text = read_text(os.path.join(root, compose_rel))
        section = ""
        cur = None
        for line in text.splitlines():
            if not line.strip() or line.strip().startswith("#"):
                continue
            if not line[0].isspace():
                section = line.split(":")[0].strip()
                cur = None
                continue
            if section != "services":
                continue
            ind = len(line) - len(line.lstrip())
            keyval = line.strip()
            if ind == 2 and keyval.endswith(":"):
                cur = {"name": keyval[:-1], "image": "", "build": "", "ports": [],
                       "publishes": False, "healthcheck": False,
                       "depends_on": [], "db": False}
                services.append(cur)
            elif cur is not None and ind >= 4:
                m = re.match(r"(image|healthcheck):\s*(.+)$", keyval)
                if m and m.group(1) == "image":
                    cur["image"] = m.group(2).strip()
                    cur["db"] = bool(DB_IMAGES.search(cur["image"]))
                elif m:
                    cur["healthcheck"] = True
                if keyval.startswith("- "):
                    seg = keyval[2:].strip()
                    if re.match(r'^"?\d+[:-]', seg):
                        cur["publishes"] = True
        warnings.append({"level": "warn",
                         "message": "Compose dosyası docker ile doğrulanamadı (basit tarama yapıldı)",
                         "hint": " ".join(err or [])})
        return services, [], warnings
    comm, ports_map = [], {}
    svc_cfg = cfg.get("services") or {}
    for name, svc in sorted(svc_cfg.items()):
        ports, publishes = [], False
        for pm in svc.get("ports") or []:
            target = pm.get("Target", pm.get("target"))
            pub = pm.get("Published", pm.get("published"))
            hip = pm.get("HostIp", pm.get("host_ip", "")) or ""
            if pub in (None, ""):
                ports.append({"target": target, "published": "", "host_ip": ""})
            else:
                publishes = True
                ports.append({"target": target, "published": str(pub), "host_ip": hip})
        image = svc.get("image") or ""
        build = ""
        b = svc.get("build")
        if isinstance(b, dict):
            build = b.get("context") or ""
        elif isinstance(b, str):
            build = b
        dep = list((svc.get("depends_on") or {}).keys()) if isinstance(svc.get("depends_on"), dict) \
            else list(svc.get("depends_on") or [])
        entry = {"name": name, "image": image, "build": build, "ports": ports,
                 "publishes": publishes, "healthcheck": bool(svc.get("healthcheck")),
                 "depends_on": dep,
                 "db": bool(DB_IMAGES.search(image or ""))}
        services.append(entry)
        ports_map[name] = ports
        if image.endswith(":latest") or image.endswith(":main"):
            warnings.append({"level": "warn",
                             "message": f"{name}: image etiketi sabit değil ({image})",
                             "hint": "Sürümü sabitleyin (örn. postgres:16.4); latest kullanımı güncellemede davranış değiştirir."})
        if svc.get("privileged"):
            warnings.append({"level": "error",
                             "message": f"{name}: privileged:true — kurulum FORCE ister",
                             "hint": "Gerçekten gerekli mi? Normalde kaldırılmalı."})
        for mount in (svc.get("volumes") or []):
            vol = mount if isinstance(mount, str) else ""
            if "docker.sock" in vol:
                warnings.append({"level": "error",
                                 "message": f"{name}: docker.sock bağlanıyor — kurulum FORCE ister",
                                 "hint": "Container host'un kontrolünü ele geçirebilir; kaldırın."})
        if svc.get("network_mode") == "host" or svc.get("pid") == "host":
            warnings.append({"level": "error",
                             "message": f"{name}: host network/pid modu — kurulum FORCE ister",
                             "hint": "İzolasyonu tamamen kaldırır; publish+domain eşlemesi kullanın."})
        if not svc.get("healthcheck") and not entry["db"]:
            warnings.append({"level": "info",
                             "message": f"{name}: healthcheck yok",
                             "hint": "healthcheck eklerseniz deploy, servis gerçekten sağlıklı olduğunda 'running' işaretlenir (--wait)."})
    # communication edges: depends_on + http://service refs in env
    names = {s["name"] for s in services}
    for s in services:
        for dep in s["depends_on"]:
            comm.append({"from": s["name"], "to": dep,
                         "via": "compose ağı (servis adı DNS)",
                         "note": f"{s['name']} → {dep}: başlatma sırası/bağımlılık"})
    env_file = os.path.join(root, ".env.example")
    for var in (parse_env_file(env_file) if os.path.isfile(env_file) else []):
        m = re.search(r"https?://([a-zA-Z0-9_-]+)", var.get("example") or "")
        if m and m.group(1) in names:
            comm.append({"from": "*", "to": m.group(1), "via": var["example"],
                         "note": f"{var['key']} bu servise işaret ediyor"})
    return services, comm, warnings


def scan_risks(root):
    risks = []
    env_real = [".env"] + [f"{d}/.env" for d in os.listdir(root)
                           if os.path.isdir(os.path.join(root, d)) and not d.startswith(".")]
    for candidate in env_real:
        if os.path.isfile(os.path.join(root, candidate)):
            risks.append({"level": "error",
                          "message": f"Repoda gerçek .env var: {candidate}",
                          "hint": "Şifreler GitHub'da! İçeriği temizleyip dosyayı silin, sadece .env.example kalsın."})
    for name in os.listdir(root):
        p = os.path.join(root, name)
        if os.path.isdir(p) and name in GENERATED_DIRS and name not in ("build",):
            risks.append({"level": "warn",
                          "message": f"Üretilmiş dizin repoda: {name}/",
                          "hint": f"{name}/ kurulumda sunucuda otomatik üretilir; repodan çıkarıp .gitignore'a ekleyin."})
        if os.path.isdir(p) and name == "build":
            # build/ dizini schema/seed için meşru olabilir; sadece node çıktısıysa uyar
            if os.path.isfile(os.path.join(p, "index.html")) and not any(
                    os.path.isfile(os.path.join(p, s)) for s in SCHEMA_FILES + ["seed.php"]):
                risks.append({"level": "info",
                              "message": "build/ dizininde derlenmiş çıktı gibi dosyalar var",
                              "hint": "Derleme çıktıları repoda tutulmasa da deploy çalışır (sunucuda derlenir)."})
    for f in ("database.sql", "db.sql", "install.sql", "dump.sql"):
        if os.path.isfile(os.path.join(root, f)):
            risks.append({"level": "warn",
                          "message": f"Kök dizinde SQL dosyası: {f}",
                          "hint": "Şema dosyası olarak kalacaksa adı schema.sql olsun; gerçek veri dökümüyse repodan çıkarın (web'den indirilebilir)."})
    total = 0
    for dirpath, dirnames, filenames in os.walk(root):
        if any(part in GENERATED_DIRS for part in rel(root, dirpath).split("/")):
            dirnames[:] = []
            continue
        dirnames[:] = [d for d in dirnames if not d.startswith(".")]
        for fn in filenames:
            fp = os.path.join(dirpath, fn)
            try:
                sz = os.path.getsize(fp)
            except OSError:
                continue
            total += sz
            if sz > BIG_FILE_MB * 1024 * 1024:
                risks.append({"level": "warn",
                              "message": f"Büyük dosya: {rel(root, fp)} ({sz // (1024 * 1024)} MB)",
                              "hint": "Büyük medya/ikili dosyalar depoyu yavaşlatır; CDN/object storage kullanın."})
    if not os.path.isfile(os.path.join(root, "README.md")):
        risks.append({"level": "info", "message": "README.md yok",
                      "hint": "Kurulum ve .env gereksinimlerini 5 satırla anlatan bir README ekleyin."})
    if total > DIR_SIZE_WARN_MB * 1024 * 1024:
        risks.append({"level": "warn",
                      "message": f"Repo boyutu büyük (~{total // (1024 * 1024)} MB)",
                      "hint": "Her deploy depth-1 klon yapar; büyük repolar kurulumu yavaşlatır."})
    return risks


def main():
    root = os.path.abspath(sys.argv[1])
    branch = sys.argv[2] if len(sys.argv) > 2 else ""
    if not os.path.isdir(root):
        print(json.dumps({"ok": False, "error": "repo dir not found"}))
        return

    compose_rel = find_compose(root)
    result = {"ok": True, "branch": branch, "components": [], "communication": [],
              "warnings": [], "database": {"needed": False}, "seeds": [],
              "env_template": {"file": None, "vars": []}}

    if compose_rel:
        services, comm, warn = parse_compose(root, compose_rel)
        db_svcs = [s for s in services if s["db"]]
        result["platform"] = {"name": "Docker Compose", "icon": "🐳", "channel": "docker",
                              "compose": compose_rel, "confidence": "high"}
        result["docker"] = {"compose": compose_rel, "services": services}
        result["components"] = [{
            "name": s["name"], "type": ("db" if s["db"] else
                                        "api" if (API_DIR_HINTS.search(s["name"]) or (s["build"] and not WEB_DIR_HINTS.search(s["name"]))) else "web"),
            "tech": s["image"] or f"build: {os.path.basename(s['build']) or 'dockerfile'}", "icon": "🗄️" if s["db"] else "🐳",
            "publishes": s["publishes"],
            "ports": [p for p in s["ports"] if p["published"]],
            "healthcheck": s["healthcheck"]} for s in services]
        result["communication"] = comm
        result["warnings"] += warn
        if db_svcs:
            result["database"] = {"needed": True, "engine": db_svcs[0]["image"].split(":")[0],
                                  "provision": "compose servisi", "auto": True}
        env_file, env_vars = find_env_template(root)
        result["env_template"] = {"file": env_file, "vars": env_vars}
        for var in env_vars:
            if var["group"] == "database":
                var["auto"] = "DB compose'da yönetiliyor — bu değer .env'e deployda aynen yazılır"
    else:
        comp = detect_component(root, "")
        comps = [comp] if comp else []
        # monorepo scan: marker dirs 1-2 levels deep
        marker_dirs = ("apps", "packages", "services", "src/apps")
        candidates = []
        for md in marker_dirs:
            mdp = os.path.join(root, md)
            if os.path.isdir(mdp):
                for sub in sorted(os.listdir(mdp)):
                    if os.path.isdir(os.path.join(mdp, sub)):
                        candidates.append(f"{md}/{sub}")
        if not candidates:
            for sub in sorted(os.listdir(root))[:40]:
                if sub.startswith(".") or sub in GENERATED_DIRS:
                    continue
                if os.path.isdir(os.path.join(root, sub)) and (
                        WEB_DIR_HINTS.fullmatch(sub) or API_DIR_HINTS.fullmatch(sub)):
                    candidates.append(sub)
        seen_types = set()
        for cand in candidates:
            c = detect_component(root, cand)
            if c and (c["type"] not in seen_types or True):
                comps.append(c)
                seen_types.add(c["type"])
        comps = comps[:12]
        result["components"] = comps
        primary = comps[0] if comps else None
        if primary:
            chan_mode = "python-unsupported" if primary.get("mode") == "python" else "git"
            result["platform"] = {"name": primary["tech"], "icon": primary["icon"],
                                  "channel": chan_mode, "confidence": "high" if len(comps) == 1 else "medium",
                                  "mode": primary.get("mode"), "entry": primary.get("entry")}
        else:
            result["platform"] = {"name": "Bilinmeyen", "icon": "❓", "channel": "git",
                                  "confidence": "low", "mode": "php", "entry": ""}
            result["warnings"].append({"level": "warn",
                                       "message": "Bilinen bir platform bulunamadı (PHP/Node/.NET/React/compose)",
                                       "hint": "Statik dosyalar olarak yine kurulur; ilk sayfa index.html olmalı."})
        if primary and primary.get("env_file"):
            result["env_template"] = {"file": primary["env_file"], "vars": primary.get("env_vars", [])}
        elif not (primary and primary.get("env_file")):
            env_file, env_vars = find_env_template(root)
            if env_file:
                result["env_template"] = {"file": env_file, "vars": env_vars}

        # database via schema / laravel migrations
        schema = next((f for f in SCHEMA_FILES if os.path.isfile(os.path.join(root, f))), None)
        has_artisan = os.path.isfile(os.path.join(root, "artisan"))
        has_migrations = os.path.isdir(os.path.join(root, "database/migrations"))
        if schema:
            result["database"] = {"needed": True, "engine": "mysql", "provision": schema, "auto": True}
        elif has_artisan and has_migrations:
            result["database"] = {"needed": True, "engine": "mysql",
                                  "provision": "php artisan migrate (Laravel)", "auto": True}
        for var in result["env_template"]["vars"]:
            if var["group"] == "database" and result["database"].get("auto"):
                var["auto"] = "DB otomatik açılır — bu değerler deployda .env'e otomatik yazılır"

    # seeds (both channels)
    seeds = []
    for f in SEED_SQL_FILES:
        if os.path.isfile(os.path.join(root, f)):
            seeds.append(f)
    for d in SEED_SQL_DIRS:
        dp = os.path.join(root, d)
        if os.path.isdir(dp):
            seeds += [f"{d}/{f}" for f in sorted(os.listdir(dp)) if f.endswith(".sql")]
    if os.path.isdir(os.path.join(root, "database/seeders")):
        seeds.append("database/seeders (Laravel db:seed)")
    for f in sorted(os.listdir(os.path.join(root, "build"))) if os.path.isdir(os.path.join(root, "build")) else []:
        if re.match(r"seed.*\.php$", f):
            seeds.append(f"build/{f}")
    result["seeds"] = seeds

    result["warnings"] += scan_risks(root)

    # human one-liner (TR)
    plat = result["platform"]["name"]
    n = len(result["components"])
    extra = f" + {n - 1} bileşen" if n > 1 else ""
    db = " · veritabanı otomatik" if result["database"].get("auto") else ""
    seeds_s = " · seed verisi var" if result["seeds"] else ""
    result["summary_tr"] = f"{plat}{extra}{db}{seeds_s}"
    print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
