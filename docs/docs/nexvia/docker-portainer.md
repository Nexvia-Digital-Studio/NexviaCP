---
title: Docker & Portainer
description: Hardened Docker (userns-remap) and an admin-only Portainer CE installation bound to 127.0.0.1 behind an nginx basic-auth vhost.
---

# Docker & Portainer

NexviaCP installs Portainer CE in a hardened, admin-only configuration. Containers run under Docker `userns-remap` (so a compromised container never runs as real root), the Portainer Agent talks to Docker over the `portainer-agent-net` network instead of a mounted `docker.sock`, and the Portainer ports are bound to `127.0.0.1` only — the single entry point is an nginx vhost (`docker-ui` template) protected by HTTP basic auth plus Portainer's own login.

## Commands

### Install Portainer

```bash
v-add-sys-portainer
```

Installs Portainer CE and the Portainer Agent as separate containers, applies the userns-remap hardening and prints the generated nginx `auth_basic` username and password at the end — save them, they are the first of the two login layers.

### Docker hardening

```bash
v-add-sys-docker-hardening
```

Enables `userns-remap` so containers run as mapped, non-root UID/GID pairs (container breakout prevention). The Portainer Agent is started with `--userns=host` so it can still read the Docker socket.

```bash
v-delete-sys-docker-hardening
```

Removes the userns-remap setting from `/etc/docker/daemon.json` and restarts Docker. **Warning:** containers created under userns-remap will not run afterwards until recreated — this is a disruptive operation.

### Or let the installer do it

```bash
bash install/nexvia-apply-source.sh . --docker --portainer
```

The `--docker` and `--portainer` flags of the overlay script enable the same hardening and Portainer setup post-install (`--portainer` implies `--docker`).

## Panel

The **Docker** page (`/list/docker/`) is visible to admin users only. The full flow to get a browser UI for containers:

1. Run `v-add-sys-portainer` as root (note the printed basic-auth credentials).
2. In the panel, add the domain `portainer.example.com` under `WEB`.
3. Pick the **`docker-ui`** proxy template for it and enable Let's Encrypt SSL. The template is only offered to admin accounts.
4. Browse to `https://portainer.example.com` — you are prompted for the nginx basic-auth password first, then the Portainer login.

## Notes

- Customer (non-admin) accounts never see the Docker menu or the `docker-ui` template.
- Portainer ports stay on loopback; do not expose them in the firewall — the nginx vhost is the only supported entry point.
- If the Portainer Agent restarts forever with "permission denied" under userns-remap, confirm it was started with `--userns=host` (recent `v-add-sys-portainer` versions do this automatically).
