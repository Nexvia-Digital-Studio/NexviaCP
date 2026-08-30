#!/usr/bin/env python3
"""
Automated QA test suite for NexviaCP Auto-Detection Engine (lib/nexvia-repo-scan.py).
Tests all supported frameworks, lockfiles, runtime versions, ORMs, monorepos, and ports.
"""
import json
import os
import shutil
import subprocess
import sys
import tempfile
import unittest

SCANNER_PATH = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", "lib", "nexvia-repo-scan.py"))


def run_scanner(repo_dir, branch="main"):
    cmd = [sys.executable, SCANNER_PATH, repo_dir, branch]
    proc = subprocess.run(cmd, capture_output=True, text=True, encoding="utf-8", errors="replace")
    if proc.returncode != 0:
        raise RuntimeError(f"Scanner exited with code {proc.returncode}: {proc.stderr}")
    return json.loads(proc.stdout)


class TestRepoScanEngine(unittest.TestCase):
    def setUp(self):
        self.tmp_dir = tempfile.mkdtemp(prefix="nexvia_test_scan_")

    def tearDown(self):
        shutil.rmtree(self.tmp_dir, ignore_errors=True)

    def test_nextjs_ssr(self):
        """Test Next.js SSR repository with NPM lockfile."""
        pkg = {
            "name": "my-next-app",
            "dependencies": {"next": "^14.2.0", "react": "^18.2.0"},
            "scripts": {"build": "next build", "start": "next start"}
        }
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        open(os.path.join(self.tmp_dir, "package-lock.json"), "w", encoding="utf-8").close()
        with open(os.path.join(self.tmp_dir, ".nvmrc"), "w", encoding="utf-8") as f:
            f.write("v20.12.0\n")

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Next.js")
        self.assertEqual(res["platform"]["mode"], "next")
        comp = res["components"][0]
        self.assertEqual(comp["package_manager"], "npm")
        self.assertEqual(comp["install_command"], "npm ci")
        self.assertEqual(comp["output_dir"], ".next")
        self.assertEqual(comp["runtime_version"], "20.12.0")

    def test_nextjs_static_export_bun(self):
        """Test Next.js Static Export repository with Bun lockfile."""
        pkg = {
            "name": "my-static-next",
            "dependencies": {"next": "^14.2.0", "react": "^18.2.0"},
            "scripts": {"build": "next build"}
        }
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        with open(os.path.join(self.tmp_dir, "next.config.js"), "w", encoding="utf-8") as f:
            f.write("module.exports = { output: 'export' };\n")
        open(os.path.join(self.tmp_dir, "bun.lockb"), "w", encoding="utf-8").close()

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Next.js (Static Export)")
        self.assertEqual(res["platform"]["mode"], "react")
        comp = res["components"][0]
        self.assertEqual(comp["package_manager"], "bun")
        self.assertEqual(comp["output_dir"], "out")
        self.assertEqual(comp["entry"], "out/ (build)")

    def test_vite_react_pnpm(self):
        """Test Vite + React app with pnpm and custom dev/build port."""
        pkg = {
            "name": "vite-react-app",
            "dependencies": {"react": "^18.2.0", "react-dom": "^18.2.0"},
            "devDependencies": {"vite": "^5.2.0"},
            "scripts": {"build": "vite build", "dev": "vite"}
        }
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        open(os.path.join(self.tmp_dir, "pnpm-lock.yaml"), "w", encoding="utf-8").close()
        with open(os.path.join(self.tmp_dir, "vite.config.ts"), "w", encoding="utf-8") as f:
            f.write("export default { server: { port: 5173 } };\n")

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "React / Vite")
        comp = res["components"][0]
        self.assertEqual(comp["package_manager"], "pnpm")
        self.assertEqual(comp["install_command"], "pnpm install --frozen-lockfile")
        self.assertEqual(comp["output_dir"], "dist")
        self.assertEqual(comp["port"], 5173)

    def test_laravel_composer_mysql(self):
        """Test Laravel project with composer.json, migrations, and PHP 8.3."""
        comp = {
            "name": "laravel/laravel",
            "require": {"php": "^8.3", "laravel/framework": "^11.0"}
        }
        with open(os.path.join(self.tmp_dir, "composer.json"), "w", encoding="utf-8") as f:
            json.dump(comp, f)
        open(os.path.join(self.tmp_dir, "composer.lock"), "w", encoding="utf-8").close()
        open(os.path.join(self.tmp_dir, "artisan"), "w", encoding="utf-8").close()
        os.makedirs(os.path.join(self.tmp_dir, "database", "migrations"), exist_ok=True)
        open(os.path.join(self.tmp_dir, "database", "migrations", "0001_01_01_000000_create_users_table.php"), "w", encoding="utf-8").close()

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Laravel (PHP)")
        self.assertEqual(res["platform"]["mode"], "php")
        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "mysql")
        self.assertEqual(res["components"][0]["runtime_version"], "^8.3")

    def test_dotnet_9_web_api(self):
        """Test .NET 9 Web API targeting net9.0 and port 5000 in Program.cs."""
        csproj = """<Project Sdk="Microsoft.NET.Sdk.Web">
  <PropertyGroup>
    <TargetFramework>net9.0</TargetFramework>
  </PropertyGroup>
</Project>"""
        with open(os.path.join(self.tmp_dir, "Backend.csproj"), "w", encoding="utf-8") as f:
            f.write(csproj)
        with open(os.path.join(self.tmp_dir, "Program.cs"), "w", encoding="utf-8") as f:
            f.write("var app = builder.Build(); app.Run(\"http://0.0.0.0:5000\");")

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], ".NET / ASP.NET Core")
        self.assertEqual(res["platform"]["mode"], "dotnet")
        comp = res["components"][0]
        self.assertEqual(comp["runtime_version"], "net9.0")
        self.assertEqual(comp["output_dir"], "publish")
        self.assertEqual(comp["port"], 5000)

    def test_prisma_orm_postgresql(self):
        """Test Node.js API with Prisma ORM and PostgreSQL."""
        pkg = {
            "name": "prisma-api",
            "dependencies": {"express": "^4.19.0", "@prisma/client": "^5.14.0"},
            "scripts": {"start": "node server.js"}
        }
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        with open(os.path.join(self.tmp_dir, "server.js"), "w", encoding="utf-8") as f:
            f.write("const PORT = process.env.PORT || 8080;\napp.listen(PORT);")
        os.makedirs(os.path.join(self.tmp_dir, "prisma"), exist_ok=True)
        prisma = """datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}"""
        with open(os.path.join(self.tmp_dir, "prisma", "schema.prisma"), "w", encoding="utf-8") as f:
            f.write(prisma)

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "postgresql")
        self.assertEqual(res["database"]["orm"], "prisma")
        self.assertEqual(res["components"][0]["port"], 8080)

    def test_drizzle_orm_mysql(self):
        """Test Drizzle ORM detection."""
        pkg = {"name": "drizzle-app", "dependencies": {"drizzle-orm": "^0.30.0"}}
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        with open(os.path.join(self.tmp_dir, "drizzle.config.ts"), "w", encoding="utf-8") as f:
            f.write('export default { dialect: "mysql", schema: "./src/schema.ts" };\n')

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "mysql")
        self.assertEqual(res["database"]["orm"], "drizzle")

    def test_docker_compose_stack(self):
        """Test Docker Compose stack detection."""
        compose = """services:
  db:
    image: postgres:16.2
  api:
    build: .
    ports:
      - "8000:8000"
"""
        with open(os.path.join(self.tmp_dir, "compose.yaml"), "w", encoding="utf-8") as f:
            f.write(compose)

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Docker Compose")
        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "postgres")
        self.assertEqual(len(res["components"]), 2)

    def test_monorepo_discovery(self):
        """Test monorepo with apps/web (Next.js) and apps/api (NestJS)."""
        web_dir = os.path.join(self.tmp_dir, "apps", "web")
        api_dir = os.path.join(self.tmp_dir, "apps", "api")
        os.makedirs(web_dir, exist_ok=True)
        os.makedirs(api_dir, exist_ok=True)

        with open(os.path.join(web_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump({"name": "web", "dependencies": {"next": "^14.0.0"}}, f)
        with open(os.path.join(api_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump({"name": "api", "dependencies": {"@nestjs/core": "^10.0.0"}}, f)

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        comp_paths = [c["path"] for c in res["components"]]
        self.assertIn("apps/web", comp_paths)
        self.assertIn("apps/api", comp_paths)

    def test_env_parsing_and_types(self):
        """Test .env.example parsing with types and groups."""
        env_content = """# Database connection
DB_HOST=127.0.0.1
DB_PORT=5432
DB_PASS=changeme

# Application secret
JWT_SECRET=your-secret-here
PORT=3000
IS_ACTIVE=true
ADMIN_EMAIL=admin@example.com
API_URL=https://api.example.com
"""
        with open(os.path.join(self.tmp_dir, ".env.example"), "w", encoding="utf-8") as f:
            f.write(env_content)
        open(os.path.join(self.tmp_dir, "index.html"), "w", encoding="utf-8").close()

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        vars_map = {v["key"]: v for v in res["env_template"]["vars"]}
        self.assertEqual(vars_map["DB_PASS"]["kind"], "secret")
        self.assertEqual(vars_map["DB_PASS"]["group"], "database")
        self.assertTrue(vars_map["DB_PASS"]["required"])
        self.assertEqual(vars_map["PORT"]["kind"], "port")
        self.assertEqual(vars_map["IS_ACTIVE"]["kind"], "bool")
        self.assertEqual(vars_map["ADMIN_EMAIL"]["kind"], "email")
        self.assertEqual(vars_map["API_URL"]["kind"], "url")

    def test_security_risks(self):
        """Test security risk detection for real .env and safe handling of CLI bin/."""
        open(os.path.join(self.tmp_dir, ".env"), "w", encoding="utf-8").close()
        bin_dir = os.path.join(self.tmp_dir, "bin")
        os.makedirs(bin_dir, exist_ok=True)
        open(os.path.join(bin_dir, "v-add-user"), "w", encoding="utf-8").close()

        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        warnings = [w["message"] for w in res["warnings"]]
        self.assertTrue(any("Repoda gerçek .env var" in w for w in warnings))
        # bin/ with v-* scripts should NOT be reported as generated build artifact
        self.assertFalse(any("bin/" in w for w in warnings))

    def test_unicode_and_utf8(self):
        """Test UTF-8 emoji and Turkish summary generation."""
        pkg = {"name": "vue-app", "dependencies": {"vue": "^3.4.0"}}
        with open(os.path.join(self.tmp_dir, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg, f)
        res = run_scanner(self.tmp_dir)
        self.assertTrue(res["ok"])
        self.assertIn("Vue", res["summary_tr"])


if __name__ == "__main__":
    unittest.main(verbosity=2)
