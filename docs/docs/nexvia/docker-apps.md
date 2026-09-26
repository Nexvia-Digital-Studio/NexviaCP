---
title: Docker Compose Apps
description: Deploy single-repo docker-compose projects (e.g. PostgreSQL + API + admin panel) from GitHub, publish services on subdomains and let GitHub Actions trigger redeploys through the panel API. Language agnostic.
---


NexviaCP can run full multi-service stacks the same way you run them locally: one git repository containing a compose file (`docker-compose.yml` / `compose.yml`). Whatever the compose file starts is what runs — PostgreSQL, an API written in Go, an admin panel in React, a landing page, anything. There is no stack detection and no language restriction; the compose file is the single source of truth.

Every redeploy is **zero-downtime and resource-friendly by default** (blue-green with automatic rollback — see below), and every app you add is **automatically covered by the GitHub webhook**: push to `main` and the server pulls, rebuilds and switches over on its own. Nothing extra to configure per app.

## How it works

```
GitHub repo (docker-compose.yml)
        │  v-add-docker-app / v-update-docker-app / webhook push
        ▼
/usr/local/hestia/data/docker-apps/<app>/
    repo/                  git clone (token-authenticated)
    nexvia-override.yml    generated: every published port re-bound to 127.0.0.1
    .env                   compose substitution vars, edited from the panel
        │  blue-green deploy worker (background, deploy.log)
        ▼
containers on 127.0.0.1:9100-9999
        │  nginx (docker-app template, %app_port%)
        ▼
api.example.com ─ admin.example.com ─ example.com   (+ Let's Encrypt)
```

Security model:

- Containers never listen on a public interface. The generated `nexvia-override.yml` forces every published port onto `127.0.0.1` with a collision-free host port from the shared 9100-9999 range (the same range `v-add-web-domain-app` uses; conflicts are checked against live sockets and both registries).
- Public traffic only enters through nginx (80/443). No firewall changes are needed.
- A preflight scan refuses `privileged: true`, `/var/run/docker.sock` mounts, `network_mode: host` and `pid: host` unless you explicitly pass `FORCE=yes` (checkbox in the panel).
- Container log rotation (`max-size=10m`, `max-file=3`) is enabled on the first install while no containers are running.

## Commands

### Add an app

```bash
v-add-docker-app APP REPO [BRANCH] [COMPOSE_FILE] [FORCE]
```

```bash
v-add-docker-app mercanadisyon https://github.com/user/MercanAdisyon.git main
```

Clones the repository, resolves the compose file, allocates loopback ports and starts the build **in the background**. Watch the state:

```bash
v-list-docker-app mercanadisyon
```

`STATE` moves `deploying -> running` (or `failed`; see `v-list-docker-app-logs mercanadisyon`). Private repositories work when a GitHub token is configured (`v-set-sys-github-token`); the token is passed through a `GIT_ASKPASS` helper and never appears on a command line.

### Publish a service on a domain

```bash
v-add-docker-app-domain APP DOMAIN SERVICE[:CONTAINER_PORT] [USER]
```

```bash
v-add-docker-app-domain mercanadisyon api.mercanadisyon.com api
v-add-docker-app-domain mercanadisyon app.mercanadisyon.com web:3000 nexvia
v-add-letsencrypt-domain nexvia app.mercanadisyon.com   # optional SSL
```

Creates a web domain using the `docker-app` nginx template proxying to the service's loopback port. The service must publish a port in its compose file (`ports:`); check available mappings with `v-list-docker-app`. DNS for the (sub)domain must point at the server first.

### Update / redeploy — zero-downtime by default

```bash
v-update-docker-app mercanadisyon
```

Fetches the tracked branch (`fetch` + `reset --hard` — local edits inside `repo/` are discarded, managed files live outside the clone), regenerates the override — **existing port assignments are preserved**, so nginx mappings keep working — and hands over to the deploy worker, which runs a **blue-green deployment**:

1. **Build & pull first, serve all along.** New images are built/pulled while the old containers keep answering traffic. The build is niced (`nice 19` / `ionice idle`) and serialized behind a **global build lock** — several pushes arriving together take turns instead of stampeding the CPU/disk. Slow is fine; the site never waits on the build.
2. **Changed stateless services** (web/API fronts — anything without a data volume) get a *green* copy started on a fresh loopback port, on the same docker networks with the same env, so `DB_HOST=db`-style links keep working.
3. **Health gate.** Each green container must accept connections (and report `healthy` when it defines a healthcheck). A container that never comes up is **removed and the deploy fails — the live stack was never touched**. Rollback is simply "nothing happened".
4. **The switch.** nginx is flipped to the green ports (registry rewrite + `nginx -t` + graceful reload). Reload is graceful: established connections finish on the old container, new ones hit green — **no dropped requests**.
5. **Retirement.** The old blue containers and the previous green generation are stopped and removed, replaced images' build cache is pruned.

Changed **stateful services** (databases, queues — anything with a named volume or a `db`-like name) cannot be blue-green'd; compose recreates them directly when their image actually changed, and the deploy log says so loudly (`expect a short blip`). When nothing changed at all the deploy is a no-op.

Readymade images (`image: nginx:1.27` without a `build:` section) are **pulled on every deploy**, so pushing a new tag or an updated `latest` actually updates the running stack.

### Automatic updates from GitHub (webhook) — works for every app

There is nothing to enable per app: `v-sync-github-repos` scans the docker-app registry on every push, so **an app added with `v-add-docker-app` is covered the moment it exists**. Setup is one-time, server-wide:

1. Configure the shared webhook secret on the server:

   ```bash
   v-set-sys-global-vault GITHUB_WEBHOOK_SECRET <random-secret>
   ```

2. In GitHub (organization → Settings → Webhooks → Add webhook):
   - **Payload URL:** `https://panel.example.com/webhook/github/`
   - **Content type:** `application/json`
   - **Secret:** the same secret
   - **Events:** Just the `push` event

Every push now triggers, for every domain / demo site / docker app whose repository matches the pushed repo name: fetch + the zero-downtime redeploy above. Delivered payloads are HMAC-SHA256 verified, replayed deliveries are rejected, and pushes are rate-limited per repository (15 s cooldown). Deploy output lands in the app's `deploy.log`; failures raise a panel notification for `admin` while the live version keeps running.

Prefer CI-driven deploys? The panel API route is still available — see [Deploy from GitHub Actions](#deploy-from-github-actions).

### Operate

```bash
v-restart-docker-app mercanadisyon [SERVICE]
v-suspend-docker-app mercanadisyon       # compose stop
v-unsuspend-docker-app mercanadisyon     # compose start
v-list-docker-app-logs mercanadisyon [SERVICE] [LINES]
```

Without `SERVICE` the deploy log is shown; with it, that container's logs.

### Environment variables

```bash
v-list-docker-app-env mercanadisyon
echo 'DB_PASSWORD=secret' | v-save-docker-app-env mercanadisyon
```

The managed `.env` feeds compose variable substitution (`${VAR}` in the compose file). On first add it is seeded from the repository's own `.env`; afterwards it is never overwritten by updates — edit it from the panel or here, then redeploy.

### Delete

```bash
v-delete-docker-app mercanadisyon                        # volumes kept, linked domains deleted
v-delete-docker-app mercanadisyon volumes                # also deletes docker volumes (DB data!)
v-delete-docker-app mercanadisyon volumes keep-domains   # leave the web domains in place
```

## Deploy from GitHub Actions

Create an API access key once (as the admin, in the panel **API** tab or):

```bash
v-add-access-key   # prints access_key / secret_key; allow the API IP (GitHub runners: 0.0.0.0/0)
```

Then in your repository, after your build/test jobs:

```yaml
name: deploy
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - name: Trigger NexviaCP redeploy
        run: |
          curl -sS -X POST https://panel.example.com/api/ \
            -d "access_key=${{ secrets.NEXVIA_ACCESS_KEY }}" \
            -d "secret_key=${{ secrets.NEXVIA_SECRET_KEY }}" \
            -d "cmd=v-update-docker-app" \
            -d "arg1=mercanadisyon" \
            -d "returncode=yes"
```

The API call returns as soon as the update is queued (`STATE=deploying`); the build itself runs on the server in the background. Add `cmd=v-list-docker-app&arg1=mercanadisyon` polling if your workflow should wait for `STATE=running`. Store the keys as repository secrets; the API is rate-limited per key and every call is audit-logged.

## Panel

**Docker** (admin only) lists every app with live state and per-service health. From an app's detail page you can watch deploy/container logs, restart single services, map new domains to services (with Let's Encrypt), edit the `.env`, update, suspend or delete the app. The Portainer shortcut remains available when a `docker-ui` domain is configured.
