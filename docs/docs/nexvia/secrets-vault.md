---
title: Secrets Vault
description: Central root-only vault for global secrets such as API keys and tokens, with masked listing and clean deletion.
---

# Secrets Vault

The vault is NexviaCP's central store for global secrets — API keys (for example a Gemini key for AI features), provider tokens and similar credentials that scripts need but should not live world-readable in config files or command history. Secrets are stored in the root-owned vault config, exposed to internal scripts, and always masked when listed.

## Commands

### Store or update a secret

```bash
v-set-sys-global-vault KEY VALUE
v-set-sys-global-vault GLOBAL_GEMINI_API_KEY AIzaSy...
```

If the key already exists its value is updated in place; re-running the command is safe.

### List secrets (masked)

```bash
v-list-sys-global-vault [FORMAT]
v-list-sys-global-vault json
```

Returns the stored keys with values masked — enough to verify a key exists and when it was set, without ever printing the secret itself.

### Delete a secret

```bash
v-delete-sys-global-vault KEY
```

Removes the key from the vault. Scripts depending on it will report the missing value rather than silently using a stale credential.

## Panel

There is no dedicated vault page — the vault is managed from the CLI (or the web terminal) as root. Related secrets that do have panel flows are managed elsewhere: the GitHub organisation/PAT via `v-set-sys-github-token` ([Git deploy](/docs/nexvia/git-deploy)) and backup credentials via the restic/cloud-sync commands ([Cloud backup](/docs/nexvia/cloud-backup)).

## Notes

- The vault file is root-only; panel-facing endpoints that need a secret read it through sudo-whitelisted helpers (the same pattern `v-list-webhook-secret` uses for per-site webhook secrets), never by direct read.
- `v-list-sys-global-vault` never prints raw values — use it to confirm a key exists, not to recover a lost one.
- Avoid passing long-lived secrets as raw CLI arguments in shared shells — shell history keeps them. Prefer running these commands from the root web terminal or a root shell with history disabled.
- Use one key per service (`GLOBAL_<SERVICE>_<PURPOSE>` naming) so rotation with `v-set-sys-global-vault` never requires untangling shared credentials.
- Global vault keys are separate from per-site secrets such as a domain's `GIT_DEPLOY_SECRET`, which is stored on the domain object itself (see [Git deploy](/docs/nexvia/git-deploy)).
- Rotation flow: generate the new key at the provider, run `v-set-sys-global-vault KEY <new-value>` (updates in place), then verify with `v-list-sys-global-vault` and finally revoke the old key at the provider.
- Scripts read vault values at invocation time — after rotating a key, restart any long-running service that caches it.
