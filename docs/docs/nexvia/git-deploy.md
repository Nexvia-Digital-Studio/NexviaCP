---
title: Git Deploy & PR Preview
description: One-command GitHub deployments for PHP, Node.js, React, .NET and multi-service apps, intelligent stack auto-detection, package manager matrix, ORM migrations, deploy snapshots with rollback, and PR preview environments.
---

NexviaCP deploys PHP, Laravel, Node.js (Express, Next.js, NestJS, Fastify), React/Vite SPAs, Python, and .NET 8/9/10 sites and APIs straight from GitHub (public and private repositories). It performs zero-downtime pulls, installs dependencies using auto-detected package managers, executes ORM and SQL migrations, wires reverse proxies and systemd app units, and takes release snapshots before every deploy. Pushes are applied automatically through an HMAC-SHA256-verified webhook receiver, and every pull request can get its own disposable preview subdomain.

---

## 🔍 Smart Repository Analysis (`v-analyze-repo`)

Before deploying, NexviaCP can inspect any Git repository (or local workspace) using its built-in analyzer engine. The analyzer clones the repository into a secure temporary directory, performs a deep multi-level scan, and emits a structured deployment plan.

### CLI Usage

```bash
v-analyze-repo REPO [BRANCH] [FORMAT]
```

```bash
# Analyze a GitHub repository (JSON format by default)
v-analyze-repo Nexvia-Digital-Studio/my-app main json

# Output a concise human-readable one-liner summary
v-analyze-repo https://github.com/org/project.git dev summary

# Local directory inspection (useful in CI pipelines or test suites)
v-analyze-repo /var/www/my-project main
```

### Supported Output Formats

- **`json` / `--json`** *(default)*: Outputs a complete JSON object containing:
  - `platform`: Detected primary stack, runtime mode, confidence level, and entry points.
  - `components`: Inventory of all detected services (web, API, database) across monorepos and subdirectories.
  - `database`: Database requirements (`mysql`, `postgres`, `sqlite`), provisioning mechanism (`schema.sql`, Laravel migrations, ORM).
  - `seeds`: Discovered SQL seeds and database seeder scripts.
  - `env_template`: Parsed `.env.example` variables with inferred types (`port`, `secret`, `url`, `email`, `database`, `mail`, `app`), default placeholders, and required status.
  - `communication`: Service-to-service communication edges and internal DNS links.
  - `warnings`: Actionable repository hygiene and security risk alerts.
- **`summary` / `--summary`**: Outputs a single-line summary string (e.g. `Laravel (PHP) · veritabanı otomatik · seed verisi var` or `Next.js + 1 bileşen`).

### Repository Hygiene & Security Inspection

The analysis engine audits repositories for common deployment mistakes and security issues:

| Risk Alert | Level | Description & Resolution |
|---|---|---|
| **Real `.env` in Repository** | `error` | Actual secrets found in `.env`. Warns to remove sensitive tokens and retain only `.env.example`. |
| **Committed Artifacts** | `warn` | `node_modules/`, `vendor/`, or `dist/` committed in git. Recommends adding them to `.gitignore`. |
| **Root SQL Dumps** | `warn` | Raw database dumps (`database.sql`, `dump.sql`) in the repository root. Recommends moving to `schema.sql` or excluding sensitive data. |
| **Oversized Files** | `warn` | Single files exceeding 20 MB or total repo size > 100 MB that slow down shallow cloning. |
| **Insecure Compose Directives** | `error` | Compose services with `privileged: true`, `/var/run/docker.sock` volume mounts, or `network_mode: host`. Requires explicit `FORCE=yes` override. |
| **Unpinned Docker Tags** | `warn` | Images using `:latest` or `:main` tags instead of immutable semantic versions. |

---

## ⚡ Enriched Stack & Framework Detection

NexviaCP uses an intelligent detection engine that scans manifests, configuration files, and directory signatures to classify project runtimes automatically.

```
Repository Source
       │
       ├── docker-compose.yml / compose.yml ──► [Docker Compose Apps](/docs/nexvia/docker-apps)
       ├── artisan / composer.json (Laravel) ─► PHP-FPM + Nginx (Laravel Mode, APP_KEY generation)
       ├── *.csproj / *.sln ──────────────────► .NET Kestrel App Unit + dotnet publish
       ├── package.json ──────────────────────┬── "next" in deps ────► Next.js SSR Systemd Unit
       │                                      ├── "@nestjs/core" ────► NestJS API Systemd Unit
       │                                      ├── "vite" + "react" ──► Static SPA (dist/ export)
       │                                      └── Express/Fastify ───► Node.js API Systemd Unit
       ├── requirements.txt / pyproject.toml ─► Python Service
       └── index.php / index.html ────────────► Native PHP / Static Web
```

### Detected Frameworks & Defaults

- **Laravel (PHP)**:
  - *Trigger*: `artisan` file or `composer.json` containing `laravel/framework`.
  - *Setup*: Nginx vhost pointing to `public/index.php`, dependency installation via Composer, automatic `php artisan key:generate --force`, automated database migration, and seed execution.
- **Node.js & Next.js**:
  - *Trigger*: `package.json` with dependencies like `next`, `@nestjs/core`, `express`, `fastify`, `koa`, or `hapi`.
  - *Setup*: Assigned isolated loopback port (9100–9999), managed via systemd unit (`hestia-app-<user>-<domain>.service`), proxied through Nginx `node-js` template (see [App runtimes](/docs/nexvia/app-runtimes)).
- **Static SPA & Frontend (React, Vite, Vue, Nuxt, Svelte)**:
  - *Trigger*: `package.json` with `vite`, `react`, `vue`, `nuxt`, or `svelte`.
  - *Setup*: Automated build step (`build` script), compiled assets deployed from `dist/` or `build/` behind high-performance Nginx static hosting.
- **.NET / ASP.NET Core**:
  - *Trigger*: `*.csproj` or `*.sln` files in repository root or sub-folders.
  - *Setup*: Compiled via `dotnet publish -c Release`, executed under Kestrel systemd unit, proxied through Nginx `dotnet` template.
- **Docker Compose (Multi-Service)**:
  - *Trigger*: `docker-compose.yml`, `docker-compose.yaml`, `compose.yml`, `compose.yaml`.
  - *Setup*: Automatically routes to the container orchestration engine (see [Docker Compose Apps](/docs/nexvia/docker-apps)).

---

## 📦 Package Manager Matrix

NexviaCP detects and prioritizes package managers based on repository lockfiles. If a lockfile is found, the package manager uses frozen lockfile installation to guarantee deterministic, reproducible builds:

| Package Manager | Lockfile / Manifest | Install / Build Command |
|---|---|---|
| **Bun** | `bun.lockb`, `bun.lock` | `bun install --frozen-lockfile` |
| **pnpm** | `pnpm-lock.yaml` | `pnpm install --frozen-lockfile` *(fallback: `pnpm install`)* |
| **Yarn** | `yarn.lock` | `yarn install --frozen-lockfile` *(or `yarn install --immutable`)* |
| **npm** | `package-lock.json`, `package.json` | `npm ci` *(fallback: `npm install --production`)* |
| **Composer** | `composer.lock`, `composer.json` | `composer install --no-interaction --prefer-dist --no-dev -o` |
| **.NET CLI** | `*.csproj`, `*.sln` | `dotnet publish -c Release -o publish/` |

When multiple package managers are installed on the host, NexviaCP resolves them according to the repository's lockfile hierarchy. If no lockfile exists, it falls back to the default platform tool (`npm` for Node.js, `composer` for PHP).

---

## ⚙️ Runtime Version Resolution

To prevent runtime mismatches between development and production, NexviaCP inspects standard version pinning files:

- **Node.js**:
  - `.nvmrc` (e.g. `20`, `v22.11.0`, `lts/*`)
  - `.node-version`
  - `package.json` (`engines.node` field, e.g. `>=18.0.0`)
- **.NET SDK**:
  - `global.json` (`sdk.version` and `sdk.rollForward` policies)
- **PHP**:
  - `composer.json` (`require.php` version constraints, e.g. `^8.2`, `8.3.*`)
- **Python**:
  - `.python-version`, `runtime.txt`, `pyproject.toml` (`requires-python`)

The deployment process verifies and resolves system runtime versions before initiating builds, ensuring compatible execution environments.

---

## 🗄️ ORM Auto-Discovery & Database Migrations

NexviaCP features zero-config database provisioning and ORM migration discovery. When a database is required, the deployment engine automatically creates an isolated database and unprivileged user, generates a secure password, and sets up corresponding environment variables (`DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASSWORD`).

### Supported ORMs & Migration Pipelines

```
                    ┌── Prisma (schema.prisma) ────► prisma generate && prisma migrate deploy
                    ├── Drizzle (drizzle.config.*) ─► drizzle-kit migrate
Database Discovery ─┼── Laravel (artisan migrate) ──► php artisan migrate --force && db:seed
                    ├── EF Core (*.csproj / bundle) ► dotnet ef database update
                    └── Schema SQL (schema.sql) ────► Dedicated user import + seeders
```

- **Prisma ORM**:
  - *Discovery*: `prisma/schema.prisma` or `@prisma/client` in `package.json`.
  - *Action*: Runs `npx prisma generate` to build the client and `npx prisma migrate deploy` to apply pending migrations during deployment.
- **Drizzle ORM**:
  - *Discovery*: `drizzle.config.ts`, `drizzle.config.js`, or `drizzle-orm` in dependencies.
  - *Action*: Runs migration scripts (`drizzle-kit migrate` or custom migration runner).
- **Laravel Eloquent**:
  - *Discovery*: `artisan` and `database/migrations/` directory.
  - *Action*: Automatically runs `php artisan migrate --force`. If seeder files exist in `database/seeders`, runs `php artisan db:seed --force`.
- **Entity Framework Core (EF Core)**:
  - *Discovery*: EF Core migrations assembly or executable bundle.
  - *Action*: Executes `dotnet ef database update` or executes the migration bundle.
- **Native SQL Schema & Seeds**:
  - *Discovery*: `schema.sql`, `database.sql`, `db.sql`, `install.sql`, or `build/schema.sql`.
  - *Action*: Automatically provisions MySQL/MariaDB database, imports the schema as the dedicated database user (never as root), and imports seed files (`seed.sql`, `seeds/*.sql`, `build/seed*.php`).

---

## 🏗️ Monorepo & Sub-Directory Scanning

NexviaCP scans multi-project repositories and workspaces up to 3 directory levels deep:

- **Monorepo Patterns**: Automatically inspects `apps/`, `packages/`, `services/`, `src/apps/`, `frontend/`, `backend/`, `client/`, `server/`, `api/`, `web/`, `ui/`, `admin/`.
- **Component Classification**: Identifies each sub-folder as a distinct component (`web`, `api`, or `db`) with its own framework and entry points.
- **Inter-Service Map**: Resolves inter-component dependencies using service directory names, `depends_on` relationships, and URL references in `.env.example` templates.

---

## 💻 CLI Commands

### 1. Connect GitHub Account

```bash
v-set-sys-github-token ORG_NAME GITHUB_TOKEN
v-set-sys-github-token Nexvia-Digital-Studio github_pat_11A...
```

Stores the organisation name and Personal Access Token (PAT) so NexviaCP can list private repositories and deploy them securely without exposing credentials. Verify with:

```bash
v-list-github-repos [FORMAT]
v-list-github-repos json
```

### 2. Analyze a Repository

```bash
v-analyze-repo REPO [BRANCH] [FORMAT]
v-analyze-repo Nexvia-Digital-Studio/my-app main summary
v-analyze-repo https://github.com/org/repo.git main json
```

### 3. Deploy a Repository

```bash
v-deploy-github-repo USER DOMAIN REPO [BRANCH] [APP_MODE] [ENV_FILE]
```

```bash
# Auto-detect stack and deploy
v-deploy-github-repo admin neredeyasanir.localhost Nexvia-Nerede-Yasanir main auto

# Explicit runtime deployment
v-deploy-github-repo admin api.example.com my-org/api-service main node
```

`APP_MODE` defaults to `auto`. Explicit values (`php`, `node`, `react`, `dotnet`, `api`) override auto-detection.

### 4. Update and Sync

```bash
v-update-web-domain-git USER DOMAIN        # Pull + rebuild one domain
v-sync-github-repos [REPO_NAME]           # Pull updates in parallel for all connected domains
```

### 5. Release Snapshots & Instant Rollback

```bash
# List snapshots
v-list-web-domain-deploys USER DOMAIN [FORMAT]
v-list-web-domain-deploys admin example.com json

# Roll back to the previous release
v-rollback-web-domain-deploy USER DOMAIN [RELEASE|previous]
v-rollback-web-domain-deploy admin example.com previous
v-rollback-web-domain-deploy admin example.com release-20260131235959
```

Snapshots are created automatically before every deploy. A rollback itself is undoable — the state before rolling back is preserved as `release-<ts>-pre-rollback.tar.gz`.

### 6. PR Preview Environments

```bash
# Create a disposable PR preview environment
v-deploy-github-pr USER BASE_DOMAIN PR_NUMBER [REPO] [BRANCH] [APP_MODE]
v-deploy-github-pr admin example.com 12 Nexvia-Nerede-Yasanir
v-deploy-github-pr admin acme.com 42 my-app pr-42 node

# Tear down a preview environment
v-delete-github-pr USER BASE_DOMAIN [PR_NUMBER]
v-delete-github-pr admin example.com 12
v-delete-github-pr admin pr-12.example.com
```

Creates (or tears down) an isolated preview environment such as `pr-12.example.com`. Preview domains can be locked down with the global whitelist (`v-set-sys-global-whitelist`, see [WAF & malware](/docs/nexvia/waf-malware)).

---

## 🖥️ Panel Integration

- **"Deploy from GitHub" Modal**: Features the **"Analyze Repository"** button that runs `v-analyze-repo` in real-time, displaying detected platform badges, service topology, required environment variables, and risk checks before deployment.
- **Post-Deploy Notifications**: If the repository's `.env.example` requires keys that were not supplied during deployment, NexviaCP provides an alert prompting the user to populate the missing values in the File Manager.
- **Deploy History & Rollback**: Full release history with one-click rollback buttons directly inside the domain edit screen.

---

## 🔒 Security & Best Practices

- **Snapshot Retention**: NexviaCP keeps the newest 5 plain snapshots per domain (`release-<timestamp>.tar.gz`). Safety snapshots (`pre-rollback`) are never deleted by the retention cleaner.
- **Credential Protection**: Personal Access Tokens (PATs) are supplied via an ephemeral `GIT_ASKPASS` helper and are never written to disk or visible in process lists (`/proc`).
- **HMAC-SHA256 Webhook Verification**: Inbound GitHub webhooks are verified using per-domain secrets (`GIT_DEPLOY_SECRET`).
- **Process Isolation**: Node.js and .NET backends run as dedicated per-domain systemd units with kernel cgroup resource controls (see [App runtimes](/docs/nexvia/app-runtimes) and [Resource governance](/docs/nexvia/resource-governance)).

