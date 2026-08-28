---
title: NexviaCP Overview
description: What NexviaCP is, a map of the modules added on top of HestiaCP, and how to get started.
---


NexviaCP is a HestiaCP fork by [Nexvia Digital Studio](https://github.com/Nexvia-Digital-Studio), optimised for hosting many isolated websites (PHP, Node.js, .NET) on a single VPS. It keeps everything HestiaCP does and adds a layer of operational modules: per-site resource governance with cgroups, AI self-healing, anomaly detection, a WAF and malware scanner, Docker/Portainer integration, Git auto-deploy with rollbacks, cache governance, encrypted cloud backups, DB Studio, a secrets vault and a set of day-2 ops tools.

## Module map

| Module | What it does | CLI entry points |
| --- | --- | --- |
| Resource governance | Per-site RAM/CPU limits and adaptive tuning | `v-tune-sys-resources`, `v-change-web-domain-cgroup` |
| AI healing | Detects 5xx bursts / stuck services and restarts them | `v-monitor-sys-healing` |
| Anomaly detection | Z-score anomaly detection over nginx traffic metrics | `v-detect-domain-anomalies` |
| WAF & malware | Nginx WAF modes plus webshell/malware scanning | `v-add-web-domain-waf`, `v-scan-web-domain-malware` |
| Docker / Portainer | Hardened Docker (userns-remap) and admin-only Portainer | `v-add-sys-portainer` |
| Git deploy & PR preview | 1-click GitHub deploys, snapshots, rollbacks, PR previews | `v-deploy-github-repo`, `v-rollback-web-domain-deploy` |
| Cache governance | Redis/Memcached per domain, FastCGI cache, purge tooling | `v-list-cache-governance`, `v-purge-web-domain-cache` |
| Cloud backup | AES-256 sync to R2/S3/Drive and restic repositories | `v-backup-cloud-sync`, `v-backup-user-restic` |
| DB Studio | Browse schema/rows and run safe queries from the panel | `v-explore-sys-database` |
| Secrets vault | Central store for API keys and tokens | `v-set-sys-global-vault` |
| Core updates | Fork and upstream update management | `v-update-sys-nexvia` |
| App runtimes | Node.js / .NET / WebSocket backends as isolated services | `v-add-web-domain-app` |
| Ops tooling | Notifications, heartbeats, CVEs, AIDE, metrics, remote servers | `v-notify-sys-channel`, `v-check-sys-cves` |

Each module has its own page under this section with command signatures and real examples.

## Quick start

Installation is two steps: the base Hestia installer lays down the system services, then the NexviaCP overlay script applies the fork's own code to the panel.

1. Install the base system (Ubuntu 22.04/24.04 or Debian 12, run as root on a clean server):

   ```bash
   git clone https://github.com/Nexvia-Digital-Studio/NexviaCP.git /root/NexviaCP
   cd /root/NexviaCP
   bash install/hst-install.sh \
     --apache no --phpfpm yes --multiphp yes \
     --mysql yes --postgresql yes \
     --vsftpd yes --named yes \
     --exim yes --dovecot yes --clamav no --spamassassin no \
     --iptables yes --fail2ban yes \
     --resourcelimit yes \
     --docker yes --portainer yes --redis yes \
     --hostname panel.example.com \
     --email admin@example.com \
     --interactive no --force
   ```

2. Apply the NexviaCP source on top (do not skip this — without it the Nexvia modules do not appear in the panel):

   ```bash
   bash install/nexvia-apply-source.sh . --docker --portainer --redis
   ```

The overlay script copies the fork's `bin/`, `func/`, `web/` and template files over `/usr/local/hestia`, builds the web UI assets and restarts the panel. Re-run the same command after a `git pull` to refresh an installed server.

## Notes

- `--hostname` must be a FQDN with at least two dots (`panel.example.com`); short names are rejected.
- `--portainer yes` implies `--docker yes`.
- All `v-*` commands in this section must be run as root, either from a shell or through the panel's API/web terminal.
