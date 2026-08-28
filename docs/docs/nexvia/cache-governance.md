---
title: Cache Governance
description: Install Redis/Memcached, assign per-domain Redis databases, manage nginx FastCGI cache, purge caches and inspect hit ratios and slow SQL.
---

# Cache Governance

Object caching and full-page caching in NexviaCP are first-class citizens: Redis or Memcached can be installed with one command, each domain can get a dedicated Redis database (auto-injected into its `.env`), nginx FastCGI caching can be toggled per domain, and a governance view collects Redis memory stats, hit/miss ratios, domain cache mappings and slow SQL summaries in one place.

## Commands

### Install object cache backends

```bash
v-add-sys-redis      # Redis server + PHP redis extension
v-add-sys-memcached  # Memcached server + PHP memcached extension
v-delete-sys-redis   # remove Redis
```

### Assign a Redis database to a domain

```bash
v-add-web-domain-redis USER DOMAIN [REDIS_DB] [RESTART]
v-add-web-domain-redis admin mysite.com auto yes
```

With `auto`, the next free Redis DB index is picked. The assignment (and credentials) are auto-injected into the domain's `.env` file. Remove it again with `v-delete-web-domain-redis USER DOMAIN`.

### Nginx FastCGI cache

```bash
v-add-fastcgi-cache USER DOMAIN [DURATION] [RESTART]
v-add-fastcgi-cache user domain.tld 30m

v-delete-fastcgi-cache USER DOMAIN [RESTART]
v-delete-fastcgi-cache user domain.tld
```

### Purge caches

```bash
v-purge-nginx-cache USER DOMAIN
v-purge-nginx-cache user domain.tld

v-purge-web-domain-cache USER DOMAIN [CACHE_TYPE]
v-purge-web-domain-cache admin mysite.com all
```

`v-purge-web-domain-cache` clears Redis keys **and** flushes the nginx FastCGI/proxy microcache for the domain in one call — use it after deploys or content changes.

### Governance view and slow query log

```bash
v-list-cache-governance [FORMAT]
v-list-cache-governance json
```

Returns Redis memory stats, hit/miss ratios, domain cache mappings and slow SQL summaries.

```bash
v-change-sys-mysql-slowlog [STATUS] [LONG_QUERY_TIME]
v-change-sys-mysql-slowlog on 1
```

Safely enables/disables the MariaDB/MySQL slow query log that feeds the slow SQL section of the governance report.

## Panel

The **Cache** page (`/list/cache/`) lists domains with their cache mappings (`v-list-web-domains`, `v-list-cache-governance`) and offers per-domain actions: assign Redis (`v-add-web-domain-redis`), enable/remove FastCGI cache, purge (`v-purge-web-domain-cache`) and toggling the MySQL slowlog (`v-change-sys-mysql-slowlog`).

## Notes

- Domain-scoped cache actions on the panel page verify domain ownership before running, so users can only purge their own caches.
- Enabling FastCGI cache for logged-in/dynamic applications needs care: pages containing user-specific responses should bypass the cache (configure cookie-based bypass rules in the vhost if needed).
- Pair purges with deploys — [Git deploy](/docs/nexvia/git-deploy) workflows work best when `v-purge-web-domain-cache` runs right after `v-update-web-domain-git`.
