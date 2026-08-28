---
title: DB Studio
description: Browse database schemas and table rows, run safe queries from the panel, adopt unmapped databases and use one-click phpMyAdmin/phpPgAdmin SSO.
---


DB Studio is NexviaCP's in-panel database explorer. Instead of copying passwords into a separate tool, you browse schemas and table rows and run safe queries directly from the panel, with per-user database isolation enforced (users only ever see their own `prefix_` databases). It coexists with the one-click phpMyAdmin and phpPgAdmin SSO buttons, and a sync command adopts databases that exist on the SQL server but are not yet mapped to a panel user.

## Commands

### Explore a database

```bash
v-explore-sys-database USER DATABASE [TABLE] [SQL_QUERY]
```

Used by the panel's DB page to list tables, page through rows and execute safe (read-oriented) queries against a user's database. It runs with the permissions of that database only, so a user cannot touch another customer's data.

### Adopt unmapped databases

```bash
v-sync-sys-databases [TARGET_USER]
v-sync-sys-databases admin
```

Auto-discovers MySQL/MariaDB databases on the server that are not mapped to any panel user and adopts them for the target user. Useful after manual imports, migrations from other panels, or when a database was created outside the panel.

### List databases

```bash
v-list-databases USER [FORMAT]
```

Core listing command (also used by the DB page) showing databases, sizes and disk usage per user. Database disk usage is refreshed by the `v-update-database-disk` / `v-update-databases-disk` maintenance tasks.

## Panel

The **DB** page (`/list/db/`) is the DB Studio front end:

- **Explore**: table browser and query box backed by `v-explore-sys-database`, with result rendering inside the panel.
- **Adopt**: a sync action calling `v-sync-sys-databases` for admins.
- **SSO buttons**: one-click phpMyAdmin (MariaDB/MySQL) and phpPgAdmin (PostgreSQL) login without re-entering credentials. For phpPgAdmin, a temporary PostgreSQL role with a 60-minute TTL is generated per click instead of using a shared login.

## Notes

- Database names carry the owning user's prefix (e.g. `customer_db`), and the explorer enforces that boundary — this is the isolation layer between customers.
- The query box is intended for inspection and safe operations, not for schema-destroying migrations; take a restic snapshot first (see [Cloud backup](/docs/nexvia/cloud-backup)) before risky changes.
- Enable the MySQL slow query log (`v-change-sys-mysql-slowlog on 1`, see [Cache governance](/docs/nexvia/cache-governance)) to find the queries worth optimising in DB Studio.
- If a database created outside the panel is missing from the list, run `v-sync-sys-databases` before debugging permissions — unmapped databases are the most common cause.
