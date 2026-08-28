---
title: Notifications & Ops Tooling
description: Notification channels, heartbeats, certificate checks, maintenance runs, CVE scanning, log search, mail queue control, AIDE, metrics, API protection and remote server commands.
---


This page collects the day-2 operations commands added by NexviaCP: alert routing, health heartbeats and certificate checks, one-pass maintenance, CVE scanning, log search, exim queue control, AIDE file-integrity monitoring, Prometheus-style metrics, API protection and an SSH remote-server registry. Most of these also have dedicated panel pages.

## Notification channels

```bash
v-set-sys-notify-channel NAME TYPE TARGET [EVENTS]
v-set-sys-notify-channel ops-telegram telegram "123456789:AAH4g9...x@987654321"
v-set-sys-notify-channel ops-hook webhook "https://example.com/hook" "healing,anomaly"

v-list-sys-notify-channels json
v-delete-sys-notify-channel ops-telegram
```

Send a message to one channel or all of them:

```bash
v-notify-sys-channel 'MESSAGE' [CHANNEL|all] [TITLE]
v-notify-sys-channel "Disk usage critical" all "NexviaCP Alert"
```

## Health: heartbeat and certificates

```bash
v-set-sys-heartbeat URL [DAKIKA] | delete
v-set-sys-heartbeat "https://hc-ping.com/0f9f5b7e-5b0d-4c2a-9a1b-7d0e6c9f2a3d" 10

v-ping-sys-heartbeat [status]     # send a manual ping (or a failure signal)

v-check-sys-certs [FORMAT]        # SSL expiry for all web domains + the panel
```

The heartbeat is healthchecks.io-style dead-man switching: if the server stops pinging the URL, your external monitor raises the alarm.

## Maintenance and CVEs

```bash
v-run-sys-maintenance [--security-updates] [json|plain]
v-run-sys-maintenance --security-updates plain

v-list-sys-maintenance [FORMAT]   # last report and recent history

v-check-sys-cves [--update] [FORMAT]
v-check-sys-cves --update shell
```

`v-check-sys-cves` reports pending OS security updates (CVE exposure) and saves a report; `--update` applies them.

## Log search

```bash
v-search-sys-logs PATTERN [USER] [DOMAIN] [TYPE] [LIMIT] [FORMAT]
v-search-sys-logs 'upstream timed out' admin example.com access 100 json
```

Fixed-string search across web domain access/error logs with user/domain filters.

## Mail queue (exim)

```bash
v-list-mail-queue json

v-ctrl-mail-queue ACTION ID|all
v-ctrl-mail-queue retry 19tVDi-0006gA-Ne
v-ctrl-mail-queue remove all
v-ctrl-mail-queue flush
```

## AIDE file-integrity monitoring

```bash
v-add-sys-aide [--remove]     # install + build baseline + install cron
v-run-sys-aide-check          # run a check and store the report
v-list-sys-aide [FORMAT]      # status of the last check
```

`v-add-sys-aide` builds the initial database (takes several minutes) and installs `/etc/cron.d/nexvia-aide` for a daily 03:40 integrity check. `--remove` deletes the cron job and managed config (AIDE stays installed).

## Metrics

```bash
v-list-sys-metrics [FORMAT]
v-list-sys-metrics prometheus
```

Exports system and panel metrics as JSON or Prometheus text for your scraper.

## API protection and audit

```bash
v-set-sys-api-rate-limit 300        # per-key requests per 60s window
v-list-sys-api-stats [json|shell]   # rate limit status + usage counters

v-list-sys-audit-log [LINES] [FORMAT]
```

`v-list-sys-audit-log` shows recent login/auth and API events from the system auth log.

## Remote servers (SSH, key-only)

```bash
v-add-remote-server NAME HOST [PORT] [USER] [KEY] [NOTE]
v-add-remote-server web1 203.0.113.10 22 root /root/.ssh/id_ed25519 "primary web node"

v-list-remote-servers json
v-check-remote-server web1              # test SSH connectivity
v-run-remote-server web1 'uptime'       # run a command over SSH
v-delete-remote-server web1
```

Key-only authentication — passwords are never stored for remote servers.

## Panel pages

Most groups have a dedicated admin page: **Notifications** (`/list/notify/`), **Health** (`/list/health/`), **CVEs** (`/list/cves/`), **Log search** (`/list/log-search/`), **Mail queue** (`/list/mail_queue/`), **Maintenance** (`/list/maintenance/`), **API audit** (`/list/api-audit/`) and **Servers** (`/list/servers/`).
