---
title: Git Deploy & PR Preview
description: One-command GitHub deployments for PHP/Node/.NET apps, automatic webhook pulls, deploy snapshots with rollback, and disposable PR preview environments.
---

# Git Deploy & PR Preview

This module deploys PHP, Node.js, React, Django/Python and .NET sites and APIs straight from GitHub (private repos included), installs dependencies, wires the reverse proxy and creates a snapshot of the previous release before every deploy. Pushes are applied automatically through an HMAC-SHA256-verified webhook receiver, and every pull request can get its own disposable preview subdomain.

## Commands

### Connect your GitHub account

```bash
v-set-sys-github-token ORG_NAME GITHUB_TOKEN
v-set-sys-github-token Nexvia-Digital-Studio github_pat_11A...
```

Stores the organisation and Personal Access Token so NexviaCP can list private repositories and deploy them. Verify with:

```bash
v-list-github-repos [FORMAT]
v-list-github-repos json
```

### Deploy a repository

```bash
v-deploy-github-repo USER DOMAIN REPO [BRANCH] [APP_MODE]
```

```bash
v-deploy-github-repo admin neredeyasanir.localhost Nexvia-Nerede-Yasanir main auto
```

`APP_MODE` defaults to `auto` (detected from the repo); explicit values select the runtime (PHP, Node.js, .NET, ...). The script installs dependencies (`npm install`, `composer install`, `dotnet publish` as applicable) and generates a per-site webhook secret for the `deploy.php` receiver.

### Update and sync

```bash
v-update-web-domain-git USER DOMAIN        # pull + rebuild one domain
v-sync-github-repos [REPO_NAME]           # pull updates in parallel for all connected domains
```

### Snapshots and rollback

```bash
v-list-web-domain-deploys USER DOMAIN [FORMAT]
v-list-web-domain-deploys admin example.com json

v-rollback-web-domain-deploy USER DOMAIN [RELEASE|previous]
v-rollback-web-domain-deploy admin example.com previous
v-rollback-web-domain-deploy admin example.com release-20260131235959
```

Snapshots are created automatically before every deploy; running `v-rollback-web-domain-deploy` with no release rolls back to `previous`. A rollback itself is undoable — the state before it is snapshotted as `release-<ts>-pre-rollback.tar.gz`.

### PR preview environments

```bash
v-deploy-github-pr USER BASE_DOMAIN PR_NUMBER [REPO] [BRANCH] [APP_MODE]
v-deploy-github-pr admin example.com 12 Nexvia-Nerede-Yasanir
v-deploy-github-pr admin acme.com 42 my-app pr-42 node

v-delete-github-pr USER BASE_DOMAIN [PR_NUMBER]
v-delete-github-pr admin example.com 12
v-delete-github-pr admin pr-12.example.com
```

Creates (or tears down) an isolated preview such as `pr-12.example.com`. Preview domains can be locked down with the global whitelist (`v-set-sys-global-whitelist`, see [WAF & malware](/docs/nexvia/waf-malware)).

## Panel

Deployment is available from the web domain edit forms (deploy from GitHub, deploy history with one-click rollback calling `v-list-web-domain-deploys` / `v-rollback-web-domain-deploy`, and PR preview create/delete actions).

## Notes

- **Snapshot retention is the newest 5 plain snapshots** per domain (fixed-width timestamp rotation); `pre-rollback` safety snapshots do not count toward the keep-5 rotation.
- Webhook calls are verified with `X-Hub-Signature-256` HMAC using the per-site secret; `v-list-webhook-secret` is the internal, sudo-whitelisted helper the PHP receiver uses to fetch it (panel user cannot read root-owned config directly).
- For Node.js/.NET deploys, the backend process runs as an isolated systemd unit — see [App runtimes](/docs/nexvia/app-runtimes).
