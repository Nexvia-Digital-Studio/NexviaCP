#!/usr/bin/env python3
"""
NexviaCP Repository Structure Analyzer & Detection Engine Automated Test Suite
Covers framework detection, lockfiles, runtime versions, Prisma/ORM detection,
Docker Compose stacks, monorepo discovery, .env.example parsing, risk audits,
and UTF-8 encoding validation.
"""

import json
import os
import subprocess
import sys
import tempfile
import unittest

# Ensure repo root and lib/ directory are on sys.path
REPO_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), ".."))
LIB_DIR = os.path.join(REPO_ROOT, "lib")
if LIB_DIR not in sys.path:
    sys.path.insert(0, LIB_DIR)

import importlib.util

scanner_spec = importlib.util.spec_from_file_location("nexvia_repo_scan", os.path.join(LIB_DIR, "nexvia-repo-scan.py"))
scanner_mod = importlib.util.module_from_spec(scanner_spec)
scanner_spec.loader.exec_module(scanner_mod)

scan_repo = scanner_mod.scan_repo
parse_env_file = scanner_mod.parse_env_file
detect_framework = scanner_mod.detect_framework
detect_component = scanner_mod.detect_component
detect_package_manager = scanner_mod.detect_package_manager
detect_runtime_version = scanner_mod.detect_runtime_version
extract_heuristic_port = scanner_mod.extract_heuristic_port
detect_database_orm = scanner_mod.detect_database_orm
parse_compose = scanner_mod.parse_compose
scan_risks = scanner_mod.scan_risks

SCANNER_SCRIPT = os.path.join(LIB_DIR, "nexvia-repo-scan.py")
V_ANALYZE_REPO_BIN = os.path.join(REPO_ROOT, "bin", "v-analyze-repo")


class TestNextJsSSRApp(unittest.TestCase):
    """Test Case a: Next.js SSR app (with package.json, next dependency, npm lockfile)."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_nextjs_ssr_detection(self):
        pkg_content = {
            "name": "my-nextjs-ssr-app",
            "version": "1.0.0",
            "scripts": {
                "dev": "next dev",
                "build": "next build",
                "start": "next start -p 3000"
            },
            "dependencies": {
                "next": "^14.2.5",
                "react": "^18.3.1",
                "react-dom": "^18.3.1"
            },
            "engines": {
                "node": ">=20.0.0"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg_content, f, indent=2)

        with open(os.path.join(self.root, "package-lock.json"), "w", encoding="utf-8") as f:
            json.dump({"name": "my-nextjs-ssr-app", "lockfileVersion": 3}, f)

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Next.js SSR Project\nProduction deployment for NexviaCP.\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Next.js")
        self.assertEqual(res["platform"]["mode"], "node")
        self.assertEqual(res["platform"]["icon"], "▲")
        self.assertEqual(res["platform"]["entry"], "next start")
        self.assertEqual(res["platform"]["channel"], "git")
        self.assertEqual(res["platform"]["package_manager"], "npm")
        self.assertEqual(res["platform"]["output_dir"], ".next")
        self.assertEqual(res["platform"]["runtime_version"], ">=20.0.0")

        self.assertGreaterEqual(len(res["components"]), 1)
        comp = res["components"][0]
        self.assertEqual(comp["type"], "web")
        self.assertEqual(comp["tech"], "Next.js")
        self.assertEqual(comp["mode"], "node")
        self.assertTrue(comp.get("next"))
        self.assertEqual(comp["install_command"], "npm ci")
        self.assertIn("Next.js", res["summary_tr"])


class TestNextJsStaticExportApp(unittest.TestCase):
    """Test Case b: Next.js Static Export app (with next.config.js output: "export", bun.lockb, output dir "out")."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_nextjs_static_export_detection(self):
        pkg_content = {
            "name": "nextjs-static-export-app",
            "version": "1.0.0",
            "scripts": {
                "build": "next build",
                "start": "serve out"
            },
            "dependencies": {
                "next": "^14.2.5",
                "react": "^18.3.1",
                "react-dom": "^18.3.1"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg_content, f, indent=2)

        with open(os.path.join(self.root, "next.config.js"), "w", encoding="utf-8") as f:
            f.write("/** @type {import('next').NextConfig} */\nconst nextConfig = { output: 'export' };\nmodule.exports = nextConfig;\n")

        with open(os.path.join(self.root, "bun.lockb"), "wb") as f:
            f.write(b"BUN_LOCKB_MOCK_BINARY_DATA")

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Next.js Static Export App\nDeployed to out/ directory.\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Next.js (Static Export)")
        self.assertEqual(res["platform"]["mode"], "react")
        self.assertEqual(res["platform"]["icon"], "▲")
        self.assertEqual(res["platform"]["entry"], "out/ (build)")
        self.assertEqual(res["platform"]["output_dir"], "out")
        self.assertEqual(res["platform"]["package_manager"], "bun")
        self.assertEqual(res["package_manager"], "bun")

        comp = res["components"][0]
        self.assertEqual(comp["type"], "web")
        self.assertEqual(comp["install_command"], "bun install")
        self.assertEqual(comp["build_command"], "bun run build")
        self.assertEqual(comp["output_dir"], "out")
        self.assertIn("Next.js (Static Export)", res["summary_tr"])


class TestViteReactApp(unittest.TestCase):
    """Test Case c: Vite + React app with pnpm-lock.yaml, scripts.build, port 5173."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_vite_react_pnpm_detection(self):
        pkg_content = {
            "name": "vite-react-spa",
            "version": "0.0.0",
            "type": "module",
            "scripts": {
                "dev": "vite",
                "build": "tsc && vite build",
                "preview": "vite preview --port 5173"
            },
            "dependencies": {
                "react": "^18.3.1",
                "react-dom": "^18.3.1",
                "lucide-react": "^0.395.0"
            },
            "devDependencies": {
                "@vitejs/plugin-react": "^4.3.1",
                "vite": "^5.3.4"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg_content, f, indent=2)

        with open(os.path.join(self.root, "pnpm-lock.yaml"), "w", encoding="utf-8") as f:
            f.write("lockfileVersion: '6.0'\nsettings:\n  autoInstallPeers: true\n")

        with open(os.path.join(self.root, "vite.config.ts"), "w", encoding="utf-8") as f:
            f.write("import { defineConfig } from 'vite';\nimport react from '@vitejs/plugin-react';\nexport default defineConfig({ plugins: [react()], server: { port: 5173 } });\n")

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Vite React Frontend\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "React / Vite")
        self.assertEqual(res["platform"]["mode"], "react")
        self.assertEqual(res["platform"]["icon"], "⚛️")
        self.assertEqual(res["platform"]["entry"], "dist/ (build)")
        self.assertEqual(res["platform"]["output_dir"], "dist")
        self.assertEqual(res["platform"]["package_manager"], "pnpm")

        comp = res["components"][0]
        self.assertEqual(comp["type"], "web")
        self.assertEqual(comp["package_manager"], "pnpm")
        self.assertEqual(comp["install_command"], "pnpm install --frozen-lockfile")
        self.assertEqual(comp["port"], 5173)
        self.assertIn("React / Vite", res["summary_tr"])


class TestLaravelApp(unittest.TestCase):
    """Test Case d: Laravel app with composer.json (php ^8.3), artisan, database/migrations."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_laravel_php83_detection(self):
        composer_content = {
            "name": "laravel/laravel",
            "type": "project",
            "description": "The skeleton application for the Laravel framework.",
            "require": {
                "php": "^8.3",
                "laravel/framework": "^11.0"
            }
        }
        with open(os.path.join(self.root, "composer.json"), "w", encoding="utf-8") as f:
            json.dump(composer_content, f, indent=2)

        with open(os.path.join(self.root, "composer.lock"), "w", encoding="utf-8") as f:
            f.write('{"_readme": ["Lockfile for test"]}')

        with open(os.path.join(self.root, "artisan"), "w", encoding="utf-8") as f:
            f.write("#!/usr/bin/env php\n<?php define('LARAVEL_START', microtime(true));\n")

        os.makedirs(os.path.join(self.root, "public"), exist_ok=True)
        with open(os.path.join(self.root, "public", "index.php"), "w", encoding="utf-8") as f:
            f.write("<?php // Laravel front controller\n")

        os.makedirs(os.path.join(self.root, "database", "migrations"), exist_ok=True)
        with open(os.path.join(self.root, "database", "migrations", "2024_01_01_000001_create_users_table.php"), "w", encoding="utf-8") as f:
            f.write("<?php // Migration file\n")

        os.makedirs(os.path.join(self.root, "database", "seeders"), exist_ok=True)
        with open(os.path.join(self.root, "database", "seeders", "DatabaseSeeder.php"), "w", encoding="utf-8") as f:
            f.write("<?php // Seeder\n")

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Laravel Enterprise API\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Laravel (PHP)")
        self.assertEqual(res["platform"]["mode"], "php")
        self.assertEqual(res["platform"]["icon"], "🐘")
        self.assertEqual(res["platform"]["entry"], "public/index.php")
        self.assertEqual(res["platform"]["output_dir"], "public")
        self.assertEqual(res["platform"]["package_manager"], "composer")
        self.assertEqual(res["platform"]["runtime_version"], "^8.3")

        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "mysql")
        self.assertEqual(res["database"]["orm"], "laravel")
        self.assertTrue(res["database"]["auto"])
        self.assertIn("php artisan migrate", res["database"]["provision"])

        self.assertIn("database/seeders (Laravel db:seed)", res["seeds"])
        self.assertIn("veritabanı otomatik", res["summary_tr"])
        self.assertIn("seed verisi var", res["summary_tr"])


class TestDotNet9WebAPI(unittest.TestCase):
    """Test Case e: .NET 9 Web API (with .csproj targeting net9.0, Program.cs listening on port 5000)."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_dotnet9_web_api_detection(self):
        csproj_xml = """<Project Sdk="Microsoft.NET.Sdk.Web">
  <PropertyGroup>
    <TargetFramework>net9.0</TargetFramework>
    <Nullable>enable</Nullable>
    <ImplicitUsings>enable</ImplicitUsings>
  </PropertyGroup>
</Project>"""
        with open(os.path.join(self.root, "Nexvia.WebAPI.csproj"), "w", encoding="utf-8") as f:
            f.write(csproj_xml)

        program_cs = """using Microsoft.AspNetCore.Builder;
var builder = WebApplication.CreateBuilder(args);
var app = builder.Build();
app.MapGet("/", () => "NexviaCP .NET 9 Running");
app.Run("http://0.0.0.0:5000");
"""
        with open(os.path.join(self.root, "Program.cs"), "w", encoding="utf-8") as f:
            f.write(program_cs)

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# .NET 9 Web API Backend\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], ".NET / ASP.NET Core")
        self.assertEqual(res["platform"]["mode"], "dotnet")
        self.assertEqual(res["platform"]["icon"], "🟣")
        self.assertEqual(res["platform"]["entry"], "Nexvia.WebAPI.csproj")
        self.assertEqual(res["platform"]["runtime_version"], "net9.0")
        self.assertEqual(res["platform"]["target_framework"], "net9.0")
        self.assertEqual(res["target_framework"], "net9.0")

        comp = res["components"][0]
        self.assertEqual(comp["type"], "api")
        self.assertEqual(comp["mode"], "dotnet")
        self.assertEqual(comp["entry"], "Nexvia.WebAPI.csproj")
        self.assertEqual(comp["port"], 5000)
        self.assertEqual(comp["build_command"], "dotnet publish -c Release -o publish")
        self.assertEqual(comp["output_dir"], "publish")


class TestPrismaORMApp(unittest.TestCase):
    """Test Case f: Prisma ORM app (prisma/schema.prisma with provider = "postgresql")."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_prisma_postgresql_detection(self):
        pkg_content = {
            "name": "prisma-node-backend",
            "version": "1.0.0",
            "scripts": {
                "start": "node dist/index.js",
                "build": "tsc && prisma generate"
            },
            "dependencies": {
                "@prisma/client": "^5.17.0",
                "express": "^4.19.2"
            },
            "devDependencies": {
                "prisma": "^5.17.0",
                "typescript": "^5.5.3"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg_content, f, indent=2)

        os.makedirs(os.path.join(self.root, "prisma"), exist_ok=True)
        schema_prisma = """datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

generator client {
  provider = "prisma-client-js"
}

model User {
  id        Int      @id @default(autoincrement())
  email     String   @unique
  name      String?
  createdAt DateTime @default(now())
}
"""
        with open(os.path.join(self.root, "prisma", "schema.prisma"), "w", encoding="utf-8") as f:
            f.write(schema_prisma)

        with open(os.path.join(self.root, "server.js"), "w", encoding="utf-8") as f:
            f.write("const express = require('express'); const app = express(); app.listen(process.env.PORT || 4000);\n")

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Prisma Node API\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "postgresql")
        self.assertEqual(res["database"]["orm"], "prisma")
        self.assertTrue(res["database"]["auto"])
        self.assertIn("prisma migrate", res["database"]["provision"])
        self.assertIn("veritabanı otomatik", res["summary_tr"])


class TestDockerComposeApp(unittest.TestCase):
    """Test Case g: Docker Compose app (docker-compose.yml with postgres and web service)."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_docker_compose_stack_detection(self):
        compose_yaml = """version: '3.8'
services:
  web:
    build: ./web
    restart: unless-stopped
    ports:
      - "8080:8080"
    depends_on:
      - postgres
    healthcheck:
      test: ["CMD", "curl", "-fsS", "http://localhost:8080/health"]
      interval: 30s
      timeout: 5s
      retries: 3
    environment:
      - DATABASE_URL=postgres://postgres:secret@postgres:5432/appdb

  postgres:
    image: postgres:16.4
    restart: unless-stopped
    ports:
      - "5432:5432"
    environment:
      POSTGRES_DB: appdb
      POSTGRES_USER: postgres
      POSTGRES_PASSWORD: secretpassword
    volumes:
      - db_data:/var/lib/postgresql/data
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U postgres"]
      interval: 10s
      retries: 5

volumes:
  db_data:
"""
        with open(os.path.join(self.root, "docker-compose.yml"), "w", encoding="utf-8") as f:
            f.write(compose_yaml)

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Full-Stack Compose Stack\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        self.assertEqual(res["platform"]["name"], "Docker Compose")
        self.assertEqual(res["platform"]["channel"], "docker")
        self.assertEqual(res["platform"]["icon"], "🐳")
        self.assertEqual(res["platform"]["confidence"], "high")

        self.assertIn("docker", res)
        self.assertEqual(res["docker"]["compose"], "docker-compose.yml")

        service_names = {s["name"] for s in res["docker"]["services"]}
        self.assertIn("web", service_names)
        self.assertIn("postgres", service_names)

        web_svc = next(s for s in res["docker"]["services"] if s["name"] == "web")
        self.assertTrue(web_svc["publishes"])
        self.assertTrue(web_svc["healthcheck"])
        self.assertIn("postgres", web_svc["depends_on"])

        pg_svc = next(s for s in res["docker"]["services"] if s["name"] == "postgres")
        self.assertTrue(pg_svc["db"])
        self.assertEqual(pg_svc["image"], "postgres:16.4")

        self.assertTrue(res["database"]["needed"])
        self.assertEqual(res["database"]["engine"], "postgres")
        self.assertEqual(res["database"]["provision"], "compose servisi")

        comm_pairs = [(c["from"], c["to"]) for c in res["communication"]]
        self.assertIn(("web", "postgres"), comm_pairs)


class TestMonorepoStructure(unittest.TestCase):
    """Test Case h: Monorepo structure (apps/web with Next.js, apps/api with NestJS, packages/ui)."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_monorepo_discovery(self):
        root_pkg = {
            "name": "enterprise-monorepo",
            "private": True,
            "workspaces": ["apps/*", "packages/*"],
            "scripts": {
                "build": "turbo run build"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(root_pkg, f, indent=2)

        with open(os.path.join(self.root, "pnpm-lock.yaml"), "w", encoding="utf-8") as f:
            f.write("lockfileVersion: '6.0'\n")

        # apps/web -> Next.js
        os.makedirs(os.path.join(self.root, "apps", "web"), exist_ok=True)
        web_pkg = {
            "name": "@repo/web",
            "version": "1.0.0",
            "scripts": {"build": "next build", "start": "next start"},
            "dependencies": {"next": "^14.2.0", "react": "^18.3.0"}
        }
        with open(os.path.join(self.root, "apps", "web", "package.json"), "w", encoding="utf-8") as f:
            json.dump(web_pkg, f, indent=2)

        # apps/api -> NestJS
        os.makedirs(os.path.join(self.root, "apps", "api"), exist_ok=True)
        api_pkg = {
            "name": "@repo/api",
            "version": "1.0.0",
            "scripts": {"build": "nest build", "start": "node dist/main.js"},
            "dependencies": {"@nestjs/core": "^10.3.0", "@nestjs/common": "^10.3.0"}
        }
        with open(os.path.join(self.root, "apps", "api", "package.json"), "w", encoding="utf-8") as f:
            json.dump(api_pkg, f, indent=2)

        # packages/ui -> React component library
        os.makedirs(os.path.join(self.root, "packages", "ui"), exist_ok=True)
        ui_pkg = {
            "name": "@repo/ui",
            "version": "1.0.0",
            "dependencies": {"react": "^18.3.0"}
        }
        with open(os.path.join(self.root, "packages", "ui", "package.json"), "w", encoding="utf-8") as f:
            json.dump(ui_pkg, f, indent=2)

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Turborepo Monorepo Architecture\n")

        res = scan_repo(self.root)

        self.assertTrue(res["ok"])
        comp_paths = [c["path"] for c in res["components"]]
        self.assertIn("apps/web", comp_paths)
        self.assertIn("apps/api", comp_paths)

        web_comp = next(c for c in res["components"] if c["path"] == "apps/web")
        self.assertEqual(web_comp["tech"], "Next.js")
        self.assertEqual(web_comp["type"], "web")

        api_comp = next(c for c in res["components"] if c["path"] == "apps/api")
        self.assertEqual(api_comp["tech"], "NestJS")
        self.assertEqual(api_comp["type"], "api")

        self.assertIn("bileşen", res["summary_tr"])


class TestEnvExampleParsing(unittest.TestCase):
    """Test Case i: .env.example parsing with types, placeholder detection, and database hints."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_env_example_parsing_rules(self):
        env_content = """# Server Listening Port
PORT=3000

# Database Credentials
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=nexviadb
DB_USERNAME=changeme
DB_PASSWORD=your_secret_password_here

# JWT Authentication Secret
JWT_SECRET=changeme

# Redis Caching
REDIS_URL=redis://localhost:6379

# Mail Transport
MAIL_HOST=smtp.mailgun.org
MAIL_PORT=587

# App Settings
NEXT_PUBLIC_API_URL=https://api.nexvia.com
APP_DEBUG=true
MAX_WORKERS=8
SUPPORT_EMAIL=support@nexvia.com
"""
        env_path = os.path.join(self.root, ".env.example")
        with open(env_path, "w", encoding="utf-8") as f:
            f.write(env_content)

        vars_ = parse_env_file(env_path)
        var_map = {v["key"]: v for v in vars_}

        self.assertEqual(var_map["PORT"]["kind"], "port")
        self.assertEqual(var_map["PORT"]["description"], "Server Listening Port")

        self.assertEqual(var_map["DB_HOST"]["group"], "database")
        self.assertEqual(var_map["DB_PORT"]["kind"], "port")
        self.assertEqual(var_map["DB_PORT"]["group"], "database")
        self.assertEqual(var_map["DB_USERNAME"]["required"], True)
        self.assertEqual(var_map["DB_PASSWORD"]["kind"], "secret")
        self.assertEqual(var_map["DB_PASSWORD"]["required"], True)

        self.assertEqual(var_map["JWT_SECRET"]["kind"], "secret")
        self.assertEqual(var_map["JWT_SECRET"]["required"], True)

        self.assertEqual(var_map["REDIS_URL"]["kind"], "url")
        self.assertEqual(var_map["REDIS_URL"]["group"], "database")

        self.assertEqual(var_map["MAIL_HOST"]["group"], "mail")
        self.assertEqual(var_map["MAIL_PORT"]["kind"], "port")
        self.assertEqual(var_map["MAIL_PORT"]["group"], "mail")

        self.assertEqual(var_map["NEXT_PUBLIC_API_URL"]["group"], "app")
        self.assertEqual(var_map["NEXT_PUBLIC_API_URL"]["kind"], "url")

        self.assertEqual(var_map["APP_DEBUG"]["kind"], "bool")
        self.assertEqual(var_map["MAX_WORKERS"]["kind"], "number")
        self.assertEqual(var_map["SUPPORT_EMAIL"]["kind"], "email")


class TestRiskDetection(unittest.TestCase):
    """Test Case j: Risk detection (uncommitted .env, huge files, generated dirs, root SQL dumps)."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_security_and_hygiene_risk_audit(self):
        # 1. Uncommitted real .env in root
        with open(os.path.join(self.root, ".env"), "w", encoding="utf-8") as f:
            f.write("DB_PASSWORD=super_secret_real_password\n")

        # 2. Uncommitted .env in subfolder
        os.makedirs(os.path.join(self.root, "api"), exist_ok=True)
        with open(os.path.join(self.root, "api", ".env"), "w", encoding="utf-8") as f:
            f.write("API_KEY=leak_key\n")

        # 3. Committed node_modules directory
        os.makedirs(os.path.join(self.root, "node_modules", "express"), exist_ok=True)
        with open(os.path.join(self.root, "node_modules", "express", "index.js"), "w") as f:
            f.write("// node_modules file\n")

        # 4. Root SQL database dump
        with open(os.path.join(self.root, "database.sql"), "w", encoding="utf-8") as f:
            f.write("DUMP TABLE users;\n")

        # 5. Large file (> 20 MB)
        huge_file_path = os.path.join(self.root, "large_dataset.bin")
        with open(huge_file_path, "wb") as f:
            f.seek(22 * 1024 * 1024 - 1)
            f.write(b"\0")

        # Note: README.md intentionally omitted to trigger missing README warning

        risks = scan_risks(self.root)
        risk_messages = [r["message"] for r in risks]
        risk_levels = [r["level"] for r in risks]

        # Assert real .env is flagged as error
        self.assertIn("error", risk_levels)
        self.assertTrue(any(".env" in m for m in risk_messages))

        # Assert node_modules/ is flagged as warn
        self.assertTrue(any("node_modules/" in m for m in risk_messages))

        # Assert root SQL is flagged as warn
        self.assertTrue(any("database.sql" in m for m in risk_messages))

        # Assert big file is flagged as warn
        self.assertTrue(any("Büyük dosya" in m for m in risk_messages))

        # Assert missing README is flagged as info
        self.assertTrue(any("README.md yok" in m for m in risk_messages))


class TestUtf8EncodingValidation(unittest.TestCase):
    """Test Case k: UTF-8 output encoding validation & subprocess CLI execution."""

    def setUp(self):
        self.test_dir = tempfile.TemporaryDirectory()
        self.root = self.test_dir.name

    def tearDown(self):
        self.test_dir.cleanup()

    def test_utf8_cli_execution_and_characters(self):
        pkg_content = {
            "name": "türkçe-proje-adı",
            "version": "1.0.0",
            "scripts": {
                "start": "node server.js"
            },
            "dependencies": {
                "express": "^4.19.2"
            }
        }
        with open(os.path.join(self.root, "package.json"), "w", encoding="utf-8") as f:
            json.dump(pkg_content, f, ensure_ascii=False, indent=2)

        server_js = """// Türkçe karakter testi: ç, ğ, ı, ö, ş, ü, İ, Ğ, Ş, Ç, Ö, Ü
const express = require('express');
const app = express();
app.get('/sağlık', (req, res) => res.json({ durum: 'çalışıyor', mesaj: 'Harika!' }));
app.listen(process.env.PORT || 3000);
"""
        with open(os.path.join(self.root, "server.js"), "w", encoding="utf-8") as f:
            f.write(server_js)

        with open(os.path.join(self.root, "schema.sql"), "w", encoding="utf-8") as f:
            f.write("CREATE TABLE IF NOT EXISTS `kullanıcılar` (`id` INT, `ad` VARCHAR(255));\n")

        with open(os.path.join(self.root, "README.md"), "w", encoding="utf-8") as f:
            f.write("# Türkçe NexviaCP Test Deposu\nİçerik: 🚀 ✨ 🇹🇷\n")

        # Execute via CLI subprocess to test real stdout encoding handling
        proc = subprocess.run(
            [sys.executable, SCANNER_SCRIPT, self.root, "ana-dal"],
            capture_output=True,
            text=True,
            encoding="utf-8",
            errors="replace"
        )

        self.assertEqual(proc.returncode, 0, f"Scanner CLI failed with stderr: {proc.stderr}")

        try:
            output_json = json.loads(proc.stdout)
        except json.JSONDecodeError as exc:
            self.fail(f"Scanner stdout is not valid JSON: {exc}\nRaw stdout:\n{proc.stdout}")

        self.assertTrue(output_json.get("ok"))
        self.assertEqual(output_json.get("branch"), "ana-dal")
        self.assertEqual(output_json.get("platform", {}).get("icon"), "🟢")
        self.assertTrue(output_json.get("database", {}).get("needed"))
        self.assertIn("veritabanı otomatik", output_json.get("summary_tr", ""))


class TestCLIAnalyzeRepoScript(unittest.TestCase):
    """Test CLI tool bin/v-analyze-repo syntax and compatibility."""

    def test_bin_v_analyze_repo_exists(self):
        self.assertTrue(os.path.isfile(V_ANALYZE_REPO_BIN), "bin/v-analyze-repo does not exist!")

    def test_bin_v_analyze_repo_syntax(self):
        # Locate available bash executable on Windows/Linux
        bash_bins = ["C:\\Program Files\\Git\\bin\\bash.exe", "C:\\Program Files\\Git\\usr\\bin\\bash.exe", "bash", "sh"]
        bash_cmd = None
        for b in bash_bins:
            try:
                test_proc = subprocess.run([b, "--version"], capture_output=True, text=True)
                if test_proc.returncode == 0:
                    bash_cmd = b
                    break
            except (OSError, subprocess.SubprocessError):
                continue

        if bash_cmd:
            res = subprocess.run([bash_cmd, "-n", V_ANALYZE_REPO_BIN], capture_output=True, text=True)
            self.assertEqual(res.returncode, 0, f"Bash syntax check failed: {res.stderr}")
        else:
            # Fallback check: verify shebang and valid shell structure
            with open(V_ANALYZE_REPO_BIN, "r", encoding="utf-8", errors="ignore") as f:
                first_line = f.readline()
                content = f.read()
            self.assertTrue(first_line.startswith("#!/bin/bash") or first_line.startswith("#!/bin/sh"))
            self.assertIn("v-analyze-repo", content)
            self.assertIn("nexvia-repo-scan.py", content)


if __name__ == "__main__":
    print("\n======================================================")
    print("🚀 NexviaCP Repository Detection & CLI Test Suite")
    print("======================================================\n")
    unittest.main(verbosity=2)
