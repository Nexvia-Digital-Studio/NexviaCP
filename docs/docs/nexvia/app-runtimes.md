---
title: App Runtimes (Node.js / .NET / Web Services)
description: Run Node.js, .NET and WebSocket backends as isolated per-domain systemd services behind nginx, with kernel cgroup limits and zero-downtime reloads.
---


Next to PHP-FPM sites, NexviaCP runs application backends — Node.js (Express, Next.js, NestJS, Fastify), .NET 8/9/10 ASP.NET Core (Kestrel) and WebSocket services — as dedicated systemd units, one per domain, reverse-proxied by nginx. Each unit gets its own kernel-level `MemoryHigh`, `MemoryMax` and `CPUQuota`, so a crashing or leaking backend can never take down its neighbours.

## Commands

### Provision a backend for a domain

```bash
v-add-web-domain-app USER DOMAIN APP_TYPE [START_CMD]
```

```bash
v-add-web-domain-app admin acme.com node "npm start"
v-add-web-domain-app admin api.acme.com dotnet "dotnet run --no-launch-profile --project App.csproj"
```

This creates the `hestia-app-<user>-<domain>.service` systemd unit, assigns the backend port (`APP_BACKEND_PORT`), and wires the nginx vhost to proxy to it. `APP_TYPE` values include `node`, `dotnet` and WebSocket services.

### Restart (zero-downtime reload)

```bash
v-restart-web-domain-app USER DOMAIN
v-restart-web-domain-app admin acme.com
```

Restarts the backend cleanly — the nginx proxy keeps serving while the service reloads, so updates applied via Git deploy cause no dropped requests.

### Remove a backend

```bash
v-delete-web-domain-app USER DOMAIN
v-delete-web-domain-app admin acme.com
```

Stops and removes the unit; the domain stays in the panel (as a plain site) with its proxy configuration cleared.

### Resource limits for a backend

```bash
v-change-web-domain-cgroup admin example.com 256M 2G 100%
v-update-web-domain-cgroup admin example.com
```

`v-update-web-domain-cgroup` applies the limits as persistent `MemoryHigh` / `MemoryMax` / `CPUQuota` properties on the app's systemd unit — full kernel-level per-site isolation, unlike shared PHP-FPM pools (see [Resource governance](/docs/nexvia/resource-governance)).

## Proxy templates

When adding or editing the web domain in the panel, pick one of these proxy templates (available on the nginx + PHP-FPM stack):

- **`node-js`** — Node.js apps (Express, Next.js, NestJS, Fastify).
- **`dotnet`** — .NET/ASP.NET Core apps on Kestrel behind nginx.
- **`websocket`** — Socket.io and live messaging apps (nginx `Upgrade` handling).
- **`docker-ui`** — admin-only Portainer front end (see [Docker & Portainer](/docs/nexvia/docker-portainer)).

Templates listen on the standard web ports and forward to the backend's local port; `v-deploy-github-repo` selects the right one automatically in `auto` mode (see [Git deploy](/docs/nexvia/git-deploy)).

## Notes

- Logs: use `journalctl -u hestia-app-<user>-<domain>.service` for backend stdout/stderr, plus the domain's nginx access/error logs.
- The healing engine watches crashed app units and restarts them — see [AI healing](/docs/nexvia/ai-healing).
- If a vhost ends up with an empty `proxy_pass http://127.0.0.1:;` the backend port was never assigned — re-run `v-add-web-domain-app` or `v-update-web-domain-cgroup` to re-render the template.
