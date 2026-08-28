---
title: Core Updates
description: Check and apply updates from the NexviaCP fork and upstream HestiaCP, manage package updates and autoupdate settings.
---


NexviaCP tracks two upstreams: its own repository (`Nexvia-Digital-Studio/NexviaCP`) and the HestiaCP project it is forked from. The update tooling lets you preview pending updates from both, apply the fork's core files, update Hestia packages and schedule automatic updates.

## Commands

### Check for updates

```bash
v-check-sys-nexvia-updates [FORMAT]
v-check-sys-nexvia-updates json
```

Checks for new commits/releases in `Nexvia-Digital-Studio/NexviaCP` **and** upstream HestiaCP, and reports what is pending.

### Apply NexviaCP core updates

```bash
v-update-sys-nexvia [BRANCH]
v-update-sys-nexvia
```

Updates the NexviaCP core from the repository. On a server originally installed from packages, the equivalent flow is to re-run the overlay script from a local checkout:

```bash
cd /root/NexviaCP && git pull
bash install/nexvia-apply-source.sh . --docker --portainer --redis
```

### Hestia package updates

```bash
v-list-sys-hestia-updates [FORMAT]
v-list-sys-hestia-updates

v-update-sys-hestia PACKAGE
v-update-sys-hestia hestia-php

v-update-sys-hestia-all        # update all hestia packages
```

### Update straight from a git repository

```bash
v-update-sys-hestia-git REPOSITORY BRANCH INSTALL
v-update-sys-hestia-git hestiacp staging/beta install
```

### Automatic updates

```bash
v-add-cron-hestia-autoupdate MODE      # install the autoupdate cron
v-delete-cron-hestia-autoupdate        # remove it
v-list-sys-hestia-autoupdate [FORMAT]  # show current settings
v-list-sys-hestia-autoupdate
```

## Panel

The **Updates** page (`/list/updates/`) shows the combined update status by calling `v-check-sys-nexvia-updates` and `v-list-sys-hestia-updates`, exposes one-click apply buttons for `v-update-sys-nexvia`, and displays the autoupdate configuration (`v-list-sys-hestia-autoupdate`).

## Notes

- Apply NexviaCP core updates and Hestia package updates separately and check both afterwards — the fork overlay (`v-update-sys-nexvia`) writes the fork's `bin/`, `func/`, `web/` and templates over `/usr/local/hestia`, while package updates replace upstream files.
- After a core update, re-run `nexvia-apply-source.sh` flags only for the extensions you actually use (`--docker`, `--portainer`, `--redis`, ...) — omitted flags leave those services untouched.
- Security-wise, pending OS-level CVEs are checked by a separate tool, `v-check-sys-cves` — see [Notifications & tooling](/docs/nexvia/notifications-tooling).
