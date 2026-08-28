---
title: Cloud Backup (Restic)
description: AES-256 encrypted sync of backups to Cloudflare R2, AWS S3 or Google Drive, plus incremental restic repositories with per-object restores.
---

# Cloud Backup (Restic)

NexviaCP layers two backup systems on top of Hestia's local backups. **Cloud sync** encrypts local backup archives with AES-256 and ships them to Cloudflare R2, AWS S3 or Google Drive (via rclone). **Restic repositories** provide deduplicated, incremental, end-to-end encrypted backups of whole users or individual objects (web domain, database, mail domain, DNS domain, cron job, single file), restorable with one command.

## Cloud sync

```bash
v-backup-cloud-sync [USER] [ACTION] [PROVIDER] [ENCRYPT]
```

```bash
v-backup-cloud-sync admin sync
v-backup-cloud-sync all backup-and-sync r2 yes
v-backup-cloud-sync admin list json
v-backup-cloud-sync admin restore admin.2026-08-27_12-00-00.tar.aes
v-backup-cloud-sync admin test
```

`PROVIDER` selects the target (`r2`, `s3`, Google Drive via rclone; defaults to `r2`). `test` validates the credentials/endpoint, `list` shows remote archives, `restore` pulls a specific archive back. Cloudflare R2 endpoints are built automatically from your account ID when not set explicitly.

## Restic repositories

### Configure the repository host

```bash
v-add-backup-host-restic TYPE HOST USERNAME PASSWORD [PATH] [PORT]
v-add-backup-host-restic sftp backup.acme.com admin 'P4$$w@rD'

v-list-backup-host-restic TYPE [FORMAT]
v-list-backup-host-restic local

v-delete-backup-host-restic          # removes the configured restic host
```

Supported `TYPE` values include `sftp` and `local` (other rclone backends work via the restic/rclone toolchain — FTP, Backblaze B2, Google Drive, S3 and similar).

### Back up

```bash
v-backup-user-restic USER NOTIFY
v-backup-user-restic admin yes

v-schedule-user-backup-restic USER    # queue a backup (cron-friendly)

v-list-user-backups-restic USER       # list snapshots
v-list-user-files-restic USER SNAPSHOT [PATH]   # browse files inside a snapshot
```

### Restore

Whole user, or only what you need:

```bash
v-restore-user-restic USER SNAPSHOT WEB DNS MAIL DB CRON UDIR
v-restore-user-restic user snapshot yes yes no yes no no

v-restore-user-full-restic USER SNAPSHOT KEY
v-restore-database-restic USER SNAPSHOT DATABASE
v-restore-database-restic user snapshot 'user_database,user_database2'
v-restore-database-restic user snapshot '*'

v-restore-web-domain-restic USER SNAPSHOT DOMAIN
v-restore-web-domain-restic user snapshot 'domain.com,domain2.com'

v-restore-file-restic USER SNAPSHOT PATH
v-restore-mail-domain-restic USER SNAPSHOT DOMAIN
v-restore-dns-domain-restic USER SNAPSHOT DOMAIN
v-restore-cron-job-restic USER SNAPSHOT JOB
```

Queue a restore instead of running it synchronously:

```bash
v-schedule-user-restore-restic USER BACKUP [WEB] [DNS] [MAIL] [DB] [CRON] [UDIR]
```

## Panel

The **Cloud Backup** page (`/list/cloud-backup/`) drives `v-backup-cloud-sync` (test connection, list remote archives, sync now, restore an archive by name) and shows the user's restic snapshots (`v-list-user-backups`) with restore actions.

## Notes

- Restic snapshots are incremental — only changed data is transferred, so the first backup is large and slow, subsequent ones are small and fast.
- Cloud sync archives are AES-256 encrypted before upload; without the encryption key a leaked bucket is useless, but **losing the key means losing the backups** — store it separately from the backup target.
- Per-object restores (`v-restore-*-restic`) are the safest option for "undo one bad change" scenarios; full-user restores overwrite the user's current objects selected by the WEB/DNS/MAIL/DB/CRON/UDIR flags.
