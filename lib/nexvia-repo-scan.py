#!/usr/bin/env python3
# nexvia repo structure analyzer — turns a cloned repo into a deployment plan
# usage: repo_scan.py REPO_DIR [BRANCH] -> single JSON object on stdout
import json
import os
import re
import subprocess
import sys

# Ensure UTF-8 stdout/stderr across Windows, Linux and BSD environments
try:
    if hasattr(sys.stdout, "reconfigure"):
        sys.stdout.reconfigure(encoding="utf-8", errors="replace")
    if hasattr(sys.stderr, "reconfigure"):
        sys.stderr.reconfigure(encoding="utf-8", errors="replace")
except Exception:
    pass

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

        # Kind inference (port evaluated before plain number)
        if key.endswith("PORT") or key in ("PORT", "HTTP_PORT", "APP_PORT"):
            kind = "port"
        elif val.lower() in ("true", "false"):
            kind = "bool"
        elif re.match(r"^[0-9]+$", val):
            kind = "number"
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


def detect_package_manager(base_dir):
    """Detect package manager and default commands from lockfiles."""
    if os.path.isfile(os.path.join(base_dir, "bun.lockb")) or os.path.isfile(os.path.join(base_dir, "bun.lock")):
        return {
            "name": "bun",
            "install": "bun install",
            "build": "bun run build",
            "start": "bun run start",
            "run": "bun run"
        }
    if os.path.isfile(os.path.join(base_dir, "pnpm-lock.yaml")):
        return {
            "name": "pnpm",
            "install": "pnpm install --frozen-lockfile",
            "build": "pnpm build",
            "start": "pnpm start",
            "run": "pnpm"
        }
    if os.path.isfile(os.path.join(base_dir, "yarn.lock")):
        return {
            "name": "yarn",
            "install": "yarn install --immutable",
            "build": "yarn build",
            "start": "yarn start",
            "run": "yarn"
        }
    if os.path.isfile(os.path.join(base_dir, "package-lock.json")):
        return {
            "name": "npm",
            "install": "npm ci",
            "build": "npm run build",
            "start": "npm start",
            "run": "npm run"
        }
    if os.path.isfile(os.path.join(base_dir, "composer.lock")) or os.path.isfile(os.path.join(base_dir, "composer.json")):
        return {
            "name": "composer",
            "install": "composer install --no-dev -o",
            "build": "",
            "start": "",
            "run": "composer"
        }
    if os.path.isfile(os.path.join(base_dir, "uv.lock")):
        return {
            "name": "uv",
            "install": "uv pip install -r requirements.txt",
            "build": "",
            "start": "uv run python main.py",
            "run": "uv"
        }
    if os.path.isfile(os.path.join(base_dir, "poetry.lock")):
        return {
            "name": "poetry",
            "install": "poetry install --no-dev",
            "build": "",
            "start": "poetry run python main.py",
            "run": "poetry"
        }
    if os.path.isfile(os.path.join(base_dir, "Pipfile.lock")) or os.path.isfile(os.path.join(base_dir, "Pipfile")):
        return {
            "name": "pipenv",
            "install": "pipenv install --deploy",
            "build": "",
            "start": "pipenv run python main.py",
            "run": "pipenv"
        }
    return {
        "name": "npm",
        "install": "npm install",
        "build": "npm run build",
        "start": "npm start",
        "run": "npm run"
    }


def detect_runtime_version(base_dir, tech_mode):
    """Extract runtime version from .nvmrc, .node-version, composer.json, .csproj, pyproject.toml."""
    if tech_mode in ("node", "react", "next"):
        for f in (".node-version", ".nvmrc"):
            p = os.path.join(base_dir, f)
            if os.path.isfile(p):
                val = read_text(p).strip().lstrip("v").split()[0]
                if val:
                    return val
        pkg_p = os.path.join(base_dir, "package.json")
        if os.path.isfile(pkg_p):
            try:
                pkg = json.loads(read_text(pkg_p))
                eng = pkg.get("engines", {}).get("node")
                if eng:
                    return str(eng)
            except ValueError:
                pass
    elif tech_mode == "php":
        comp_p = os.path.join(base_dir, "composer.json")
        if os.path.isfile(comp_p):
            try:
                comp = json.loads(read_text(comp_p))
                req_php = comp.get("require", {}).get("php")
                if req_php:
                    return str(req_php)
            except ValueError:
                pass
    elif tech_mode == "dotnet":
        glob_p = os.path.join(base_dir, "global.json")
        if os.path.isfile(glob_p):
            try:
                gj = json.loads(read_text(glob_p))
                sdk_v = gj.get("sdk", {}).get("version")
                if sdk_v:
                    return str(sdk_v)
            except ValueError:
                pass
        for f in os.listdir(base_dir):
            if f.endswith((".csproj", ".fsproj")):
                txt = read_text(os.path.join(base_dir, f))
                m = re.search(r"<TargetFramework>(net[0-9.]+)</TargetFramework>", txt, re.I)
                if m:
                    return m.group(1)
    elif tech_mode == "python":
        for f in (".python-version", "runtime.txt"):
            p = os.path.join(base_dir, f)
            if os.path.isfile(p):
                val = read_text(p).strip()
                if val:
                    return val
    return None


def extract_heuristic_port(base_dir, entry_file=""):
    """Search code files for common port bindings (process.env.PORT || 3000, app.listen, EXPOSE)."""
    candidates = []
    if entry_file and os.path.isfile(os.path.join(base_dir, entry_file)):
        candidates.append(os.path.join(base_dir, entry_file))

    for fn in ("server.js", "app.js", "index.js", "main.js", "src/main.ts", "src/index.ts",
               "src/server.ts", "main.py", "app.py", "Program.cs", "Dockerfile", "vite.config.ts", "vite.config.js"):
        p = os.path.join(base_dir, fn)
        if os.path.isfile(p) and p not in candidates:
            candidates.append(p)

    port_patterns = [
        re.compile(r"process\.env\.PORT\s*\|\|\s*(\d{2,5})"),
        re.compile(r"app\.listen\(\s*(\d{2,5})"),
        re.compile(r"server\.listen\(\s*(\d{2,5})"),
        re.compile(r"PORT\s*=\s*(\d{2,5})"),
        re.compile(r"EXPOSE\s+(\d{2,5})"),
        re.compile(r"port:\s*(\d{2,5})"),
        re.compile(r"uvicorn.*--port\s*(\d{2,5})"),
        re.compile(r"https?://[^:]+:(\d{2,5})"),
        re.compile(r"UseUrls\(.*:(\d{2,5})"),
    ]

    for p in candidates:
        txt = read_text(p, limit=50_000)
        for pat in port_patterns:
            m = pat.search(txt)
            if m:
                try:
                    port_val = int(m.group(1))
                    if 80 <= port_val <= 65535:
                        return port_val
                except ValueError:
                    pass
    return None


def detect_framework(pkg_path, base_dir=""):
    """Map a package.json to (tech key, deploy mode, icon, output_dir, is_static_export)."""
    try:
        pkg = json.loads(read_text(pkg_path))
    except ValueError:
        return "Node.js", "node", "🟢", "dist", False
    deps = {**(pkg.get("dependencies") or {}), **(pkg.get("devDependencies") or {})}
    scripts = pkg.get("scripts") or {}

    # Next.js detection (SSR vs Static Export)
    if "next" in deps:
        is_export = False
        if base_dir:
            for cfg in ("next.config.js", "next.config.mjs", "next.config.ts"):
                cfg_p = os.path.join(base_dir, cfg)
                if os.path.isfile(cfg_p):
                    txt = read_text(cfg_p)
                    if re.search(r"output\s*:\s*['\"]export['\"]", txt):
                        is_export = True
                        break
        build_scr = scripts.get("build", "")
        if "next export" in build_scr:
            is_export = True
        if is_export:
            return "Next.js (Static Export)", "react", "▲", "out", True
        return "Next.js", "next", "▲", ".next", False

    if "@nestjs/core" in deps:
        return "NestJS", "node", "🟢", "dist", False
    if "vite" in deps and "react" in deps:
        return "React / Vite", "react", "⚛️", "dist", True
    if "react-scripts" in deps or ("react" in deps and "vite" not in deps):
        return "React", "react", "⚛️", "build", True
    if "nuxt" in deps:
        return "Nuxt", "node", "💚", ".output/server", False
    if "vue" in deps:
        return "Vue", "react", "💚", "dist", True
    if "@sveltejs/kit" in deps:
        if "@sveltejs/adapter-static" in deps:
            return "SvelteKit (Static)", "react", "🧡", "build", True
        return "SvelteKit", "node", "🧡", ".svelte-kit/output", False
    if "svelte" in deps:
        return "Svelte", "react", "🧡", "dist", True
    if "astro" in deps:
        return "Astro", "react", "🚀", "dist", True
    if "remix" in deps or "@remix-run/react" in deps:
        return "Remix", "node", "💿", "build", False
    if "@angular/core" in deps:
        return "Angular", "react", "🅰️", "dist", True
    if "gatsby" in deps:
        return "Gatsby", "react", "🟣", "public", True
    if any(d in deps for d in ("express", "fastify", "koa", "@hapi/hapi", "hapi")):
        return "Node.js API", "node", "🟢", "dist", False
    if "build" in scripts and "start" in scripts:
        return "Node.js", "node", "🟢", "dist", False
    return "Node.js", "node", "🟢", "dist", False


def node_entry(subdir_path):
    for name in ("server.js", "app.js", "index.js", "dist/main.js", "dist/index.js", "src/index.js", "src/main.js"):
        if os.path.isfile(os.path.join(subdir_path, name)):
            return name
    return None


def detect_database_orm(root, base_dir=""):
    """Detect modern ORMs (Prisma, Drizzle, EF Core, Laravel) and schema files."""
    base = base_dir if base_dir else root

    # 1. Prisma ORM
    for bp in (base, root):
        if not os.path.isdir(bp):
            continue
        prisma_schema = os.path.join(bp, "prisma", "schema.prisma")
        if os.path.isfile(prisma_schema):
            txt = read_text(prisma_schema)
            m = re.search(r'provider\s*=\s*["\']([^"\']+)["\']', txt)
            prov = m.group(1).lower() if m else "postgresql"
            engine = "postgresql" if prov in ("postgres", "postgresql") else "mysql" if prov == "mysql" else prov
            return {
                "needed": True,
                "engine": engine,
                "orm": "prisma",
                "provision": "npx prisma migrate deploy (Prisma)",
                "auto": True
            }

    # 2. Drizzle ORM
    for bp in (base, root):
        if not os.path.isdir(bp):
            continue
        for cfg in ("drizzle.config.ts", "drizzle.config.js", "drizzle.config.json"):
            p = os.path.join(bp, cfg)
            if os.path.isfile(p):
                txt = read_text(p)
                m = re.search(r'dialect\s*:\s*["\']([^"\']+)["\']', txt) or re.search(r'driver\s*:\s*["\']([^"\']+)["\']', txt)
                dialect = m.group(1).lower() if m else "mysql"
                engine = "postgresql" if ("pg" in dialect or "postgres" in dialect) else "mysql"
                return {
                    "needed": True,
                    "engine": engine,
                    "orm": "drizzle",
                    "provision": "npx drizzle-kit migrate (Drizzle)",
                    "auto": True
                }

    # 3. .NET Entity Framework Core
    for bp in (base, root):
        if os.path.isdir(bp):
            for f in os.listdir(bp):
                if f.endswith((".csproj", ".fsproj")):
                    txt = read_text(os.path.join(bp, f))
                    if "Microsoft.EntityFrameworkCore" in txt:
                        eng = "postgresql" if "Npgsql" in txt else "sqlserver" if "SqlServer" in txt else "mysql"
                        return {
                            "needed": True,
                            "engine": eng,
                            "orm": "efcore",
                            "provision": "dotnet ef database update (EF Core)",
                            "auto": True
                        }

    # 4. SQL Schema file
    for s in SCHEMA_FILES:
        for bp in (base, root):
            sp = os.path.join(bp, s)
            if os.path.isfile(sp):
                return {
                    "needed": True,
                    "engine": "mysql",
                    "orm": "sql",
                    "provision": rel(root, sp),
                    "auto": True
                }

    # 5. Laravel migrations
    for bp in (base, root):
        if os.path.isdir(bp) and os.path.isfile(os.path.join(bp, "artisan")) and (
            os.path.isdir(os.path.join(bp, "database", "migrations")) or os.path.isdir(os.path.join(root, "database", "migrations"))
        ):
            return {
                "needed": True,
                "engine": "mysql",
                "orm": "laravel",
                "provision": "php artisan migrate --force (Laravel)",
                "auto": True
            }

    return {"needed": False}


def detect_component(root, subdir):
    """Detect one deployable component in root/subdir -> dict or None."""
    base = os.path.join(root, subdir) if subdir else root
    comp = {"name": subdir or "root", "path": subdir or "."}
    if os.path.isfile(os.path.join(base, "docker-compose.yml")) or \
       os.path.isfile(os.path.join(base, "compose.yml")) or \
       os.path.isfile(os.path.join(base, "compose.yaml")):
        return None  # handled by compose channel

    pkg_mgr = detect_package_manager(base)
    comp["package_manager"] = pkg_mgr["name"]
    comp["install_command"] = pkg_mgr["install"]

    if os.path.isfile(os.path.join(base, "artisan")):
        rt_v = detect_runtime_version(base, "php")
        comp.update({"type": "web", "tech": "Laravel (PHP)", "mode": "php",
                     "icon": "🐘", "entry": "public/index.php", "output_dir": "public", "output_directory": "public"})
        if rt_v:
            comp["runtime_version"] = rt_v
    elif glob_first(base, "*.csproj") or glob_first(base, "*.sln"):
        cs = glob_first(base, "*.csproj") or glob_first(base, "*.sln")
        rt_v = detect_runtime_version(base, "dotnet")
        port = extract_heuristic_port(base, cs)
        comp.update({"type": "api", "tech": ".NET / ASP.NET Core", "mode": "dotnet",
                     "icon": "🟣", "entry": cs, "build_command": "dotnet publish -c Release -o publish",
                     "output_dir": "publish", "output_directory": "publish"})
        if rt_v:
            comp["runtime_version"] = rt_v
            comp["target_framework"] = rt_v
        if port:
            comp["port"] = port
    elif os.path.isfile(os.path.join(base, "package.json")):
        tech, mode, icon, out_dir, is_static = detect_framework(os.path.join(base, "package.json"), base)
        entry = node_entry(base) or ("next start" if mode == "next" else None)
        rt_v = detect_runtime_version(base, mode)
        port = extract_heuristic_port(base, entry or "")

        try:
            pkg_data = json.loads(read_text(os.path.join(base, "package.json")))
            scripts = pkg_data.get("scripts") or {}
        except Exception:
            scripts = {}

        build_cmd = f"{pkg_mgr['run']} build" if "build" in scripts else ""
        start_cmd = f"{pkg_mgr['run']} start" if "start" in scripts else (f"node {entry}" if entry else "")
        dev_cmd = f"{pkg_mgr['run']} dev" if "dev" in scripts else ""

        comp.update({
            "type": "web" if mode in ("react", "next") or is_static else "api",
            "tech": tech,
            "mode": mode,
            "icon": icon,
            "output_dir": out_dir,
            "output_directory": out_dir,
            "build_command": build_cmd,
            "start_command": start_cmd,
            "dev_command": dev_cmd
        })
        if rt_v:
            comp["runtime_version"] = rt_v
        if port:
            comp["port"] = port

        if mode == "react" or is_static:
            comp["entry"] = f"{out_dir}/ (build)"
        elif mode == "next":
            comp.update({"entry": "next start", "next": True})
        else:
            comp["entry"] = entry or "app.js"
    elif os.path.isfile(os.path.join(base, "index.php")) or os.path.isfile(os.path.join(base, "composer.json")):
        rt_v = detect_runtime_version(base, "php")
        comp.update({"type": "web", "tech": "PHP", "mode": "php", "icon": "🐘",
                     "entry": "index.php", "output_dir": ".", "output_directory": "."})
        if rt_v:
            comp["runtime_version"] = rt_v
    elif os.path.isfile(os.path.join(base, "index.html")):
        comp.update({"type": "web", "tech": "Statik HTML", "mode": "php", "icon": "📄",
                     "entry": "index.html", "output_dir": ".", "output_directory": "."})
    elif os.path.isfile(os.path.join(base, "requirements.txt")) or os.path.isfile(os.path.join(base, "pyproject.toml")):
        rt_v = detect_runtime_version(base, "python")
        port = extract_heuristic_port(base, "main.py")
        comp.update({"type": "api", "tech": "Python", "mode": "python", "icon": "🐍",
                     "entry": "app.py / main.py", "output_dir": ".", "output_directory": "."})
        if rt_v:
            comp["runtime_version"] = rt_v
        if port:
            comp["port"] = port
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
    """Normalize the compose file through `docker compose config` (handles env substitution + anchors)."""
    env_file = os.path.join(root, ".env")
    if not os.path.isfile(env_file):
        env_file = os.path.join(root, ".env.example")
        if not os.path.isfile(env_file):
            env_file = "/dev/null"
    compose_dir = os.path.dirname(os.path.join(root, compose_rel))
    text = read_text(os.path.join(root, compose_rel))
    created = []
    for m in re.finditer(r"^\s+(?:-\s+)?env_file:\s*([^\s#]+)", text, re.M):
        target = os.path.join(compose_dir, m.group(1).strip("'\""))
        if not os.path.exists(target):
            try:
                open(target, "w").close()
                created.append(target)
            except OSError:
                pass
    cmd = ["docker", "compose", "--env-file", env_file, "-f", compose_rel, "config", "--format", "json"]
    try:
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
    except Exception as exc:
        cfg = None
        err = [str(exc)]

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
    comm, ports_map = [], {}

    if cfg is None:
        # Pure-Python fallback parser when docker CLI is not present/running
        text = read_text(os.path.join(root, compose_rel))
        section = ""
        cur = None
        sub_key = ""
        for line in text.splitlines():
            sline = line.strip()
            if not sline or sline.startswith("#"):
                continue
            if not line[0].isspace():
                section = sline.split(":")[0].strip()
                cur = None
                sub_key = ""
                continue
            if section != "services":
                continue
            ind = len(line) - len(line.lstrip())
            keyval = sline
            if ind == 2 and keyval.endswith(":"):
                cur = {"name": keyval[:-1], "image": "", "build": "", "ports": [],
                       "publishes": False, "healthcheck": False,
                       "depends_on": [], "db": False, "volumes": [],
                       "privileged": False, "network_mode": "", "pid": ""}
                services.append(cur)
                sub_key = ""
            elif cur is not None and ind >= 4:
                if keyval.startswith("image:"):
                    cur["image"] = keyval.split(":", 1)[1].strip().strip("'\"")
                    cur["db"] = bool(DB_IMAGES.search(cur["image"]))
                elif keyval.startswith("build:"):
                    b_val = keyval.split(":", 1)[1].strip().strip("'\"")
                    if b_val:
                        cur["build"] = b_val
                    sub_key = "build"
                elif keyval.startswith("context:") and sub_key == "build":
                    cur["build"] = keyval.split(":", 1)[1].strip().strip("'\"")
                elif keyval.startswith("healthcheck:"):
                    cur["healthcheck"] = True
                elif keyval.startswith("privileged:"):
                    cur["privileged"] = "true" in keyval.lower()
                elif keyval.startswith("network_mode:"):
                    cur["network_mode"] = keyval.split(":", 1)[1].strip().strip("'\"")
                elif keyval.startswith("pid:"):
                    cur["pid"] = keyval.split(":", 1)[1].strip().strip("'\"")
                elif keyval.endswith(":"):
                    sub_key = keyval[:-1].strip()
                elif keyval.startswith("- "):
                    item = keyval[2:].strip().strip("'\"")
                    if sub_key == "ports" or re.match(r'^\d+[:-]', item):
                        parts = item.split(":")
                        if len(parts) == 2:
                            pub, tgt = parts[0], parts[1]
                            cur["ports"].append({"target": tgt, "published": pub, "host_ip": ""})
                            cur["publishes"] = True
                        elif len(parts) == 3:
                            hip, pub, tgt = parts[0], parts[1], parts[2]
                            cur["ports"].append({"target": tgt, "published": pub, "host_ip": hip})
                            cur["publishes"] = True
                        else:
                            cur["ports"].append({"target": item, "published": "", "host_ip": ""})
                    elif sub_key == "depends_on":
                        cur["depends_on"].append(item)
                    elif sub_key == "volumes":
                        cur["volumes"].append(item)
            elif cur is not None and ind >= 6 and sub_key == "depends_on" and keyval.endswith(":"):
                dep_name = keyval[:-1].strip()
                if dep_name not in cur["depends_on"]:
                    cur["depends_on"].append(dep_name)

        warnings.append({"level": "warn",
                         "message": "Compose dosyası docker ile doğrulanamadı (basit tarama yapıldı)",
                         "hint": " ".join(err or [])})

        for s in services:
            image = s["image"]
            name = s["name"]
            if image.endswith(":latest") or image.endswith(":main"):
                warnings.append({"level": "warn",
                                 "message": f"{name}: image etiketi sabit değil ({image})",
                                 "hint": "Sürümü sabitleyin (örn. postgres:16.4); latest kullanımı güncellemede davranış değiştirir."})
            if s.get("privileged"):
                warnings.append({"level": "error",
                                 "message": f"{name}: privileged:true — kurulum FORCE ister",
                                 "hint": "Gerçekten gerekli mi? Normalde kaldırılmalı."})
            for mount in (s.get("volumes") or []):
                vol = mount if isinstance(mount, str) else ""
                if "docker.sock" in vol:
                    warnings.append({"level": "error",
                                     "message": f"{name}: docker.sock bağlanıyor — kurulum FORCE ister",
                                     "hint": "Container host'un kontrolünü ele geçirebilir; kaldırın."})
            if s.get("network_mode") == "host" or s.get("pid") == "host":
                warnings.append({"level": "error",
                                 "message": f"{name}: host network/pid modu — kurulum FORCE ister",
                                 "hint": "İzolasyonu tamamen kaldırır; publish+domain eşlemesi kullanın."})
            if not s.get("healthcheck") and not s["db"]:
                warnings.append({"level": "info",
                                 "message": f"{name}: healthcheck yok",
                                 "hint": "healthcheck eklerseniz deploy, servis gerçekten sağlıklı olduğunda 'running' işaretlenir (--wait)."})

        for s in services:
            for dep in s["depends_on"]:
                comm.append({"from": s["name"], "to": dep,
                             "via": "compose ağı (servis adı DNS)",
                             "note": f"{s['name']} → {dep}: başlatma sırası/bağımlılık"})
        env_file = os.path.join(root, ".env.example")
        for var in (parse_env_file(env_file) if os.path.isfile(env_file) else []):
            m = re.search(r"https?://([a-zA-Z0-9_-]+)", var.get("example") or "")
            if m and m.group(1) in {s["name"] for s in services}:
                comm.append({"from": "*", "to": m.group(1), "via": var["example"],
                             "note": f"{var['key']} bu servise işaret ediyor"})
        return services, comm, warnings

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

    for s in services:
        for dep in s["depends_on"]:
            comm.append({"from": s["name"], "to": dep,
                         "via": "compose ağı (servis adı DNS)",
                         "note": f"{s['name']} → {dep}: başlatma sırası/bağımlılık"})
    env_file = os.path.join(root, ".env.example")
    for var in (parse_env_file(env_file) if os.path.isfile(env_file) else []):
        m = re.search(r"https?://([a-zA-Z0-9_-]+)", var.get("example") or "")
        if m and m.group(1) in {s["name"] for s in services}:
            comm.append({"from": "*", "to": m.group(1), "via": var["example"],
                         "note": f"{var['key']} bu servise işaret ediyor"})
    return services, comm, warnings


def scan_risks(root):
    """Scan security risks, committed secrets and repository hygiene."""
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
        if os.path.isdir(p) and name in GENERATED_DIRS and name not in ("build", "bin"):
            risks.append({"level": "warn",
                          "message": f"Üretilmiş dizin repoda: {name}/",
                          "hint": f"{name}/ kurulumda sunucuda otomatik üretilir; repodan çıkarıp .gitignore'a ekleyin."})
        # Check bin/ only if it contains compiled binaries instead of CLI scripts
        if os.path.isdir(p) and name == "bin":
            bin_files = os.listdir(p) if os.path.isdir(p) else []
            if not any(f.startswith("v-") or f.endswith(".sh") for f in bin_files):
                risks.append({"level": "warn",
                              "message": "Üretilmiş dizin repoda: bin/",
                              "hint": "bin/ derleme çıktıları repoda tutulmamalı; .gitignore'a ekleyin."})
        if os.path.isdir(p) and name == "build":
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


def scan_repo(root, branch=""):
    """Scan a repository directory and emit a complete deployment plan dict."""
    root = os.path.abspath(root)
    if not os.path.isdir(root):
        return {"ok": False, "error": f"repo dir not found: {root}"}

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
            if c:
                comps.append(c)
                seen_types.add(c["type"])
        comps = comps[:12]
        result["components"] = comps
        primary = comps[0] if comps else None
        if primary:
            chan_mode = "python-unsupported" if primary.get("mode") == "python" else "git"
            plat_dict = {"name": primary["tech"], "icon": primary["icon"],
                         "channel": chan_mode, "confidence": "high" if len(comps) == 1 else "medium",
                         "mode": primary.get("mode"), "entry": primary.get("entry")}
            if primary.get("package_manager"):
                plat_dict["package_manager"] = primary["package_manager"]
            if primary.get("output_dir"):
                plat_dict["output_directory"] = primary["output_dir"]
                plat_dict["output_dir"] = primary["output_dir"]
            if primary.get("target_framework"):
                plat_dict["target_framework"] = primary["target_framework"]
            if primary.get("runtime_version"):
                plat_dict["runtime_version"] = primary["runtime_version"]
            result["platform"] = plat_dict
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

        # Deep database & ORM resolution
        db_info = detect_database_orm(root, primary["path"] if primary else "")
        result["database"] = db_info

        if primary:
            result["package_manager"] = primary.get("package_manager")
            result["output_directory"] = primary.get("output_dir") or primary.get("output_directory")
            result["target_framework"] = primary.get("target_framework")
        if db_info.get("orm"):
            result["orm"] = db_info["orm"]
            if "platform" in result:
                result["platform"]["orm"] = db_info["orm"]

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
    return result


def main():
    flags = [a for a in sys.argv[1:] if a.startswith("--")]
    args = [a for a in sys.argv[1:] if not a.startswith("--")]

    root = os.path.abspath(args[0]) if len(args) > 0 else "."
    branch = args[1] if len(args) > 1 else ""

    result = scan_repo(root, branch)
    if "--summary" in flags:
        print(result.get("summary_tr", ""))
    else:
        print(json.dumps(result, ensure_ascii=False))


if __name__ == "__main__":
    main()
