---
title: Resource Governance & cgroups
description: Per-domain RAM/CPU limits, priority-based adaptive tuning and memory-pressure scaling in NexviaCP.
---


NexviaCP gives every web domain its own kernel-enforced resource envelope. PHP sites get limits injected into their PHP-FPM pool (`memory_limit`, `pm.max_children`), while Node.js/.NET/WebSocket backends run in a dedicated systemd unit with `MemoryHigh`, `MemoryMax` and `CPUQuota` applied. On top of the static limits, an adaptive tuner re-allocates CPU, RAM and I/O based on traffic, memory pressure and a per-site priority.

## Commands

### Set explicit per-domain limits

```bash
v-change-web-domain-cgroup USER DOMAIN MEMORY_HIGH MEMORY_MAX CPU_QUOTA
```

```bash
v-change-web-domain-cgroup admin example.com 256M 2G 100%
```

`MEMORY_HIGH` is the baseline (soft) limit, `MEMORY_MAX` the peak limit, and `CPU_QUOTA` a percentage (`100%` = one full core, `200%` = two cores). After changing limits, apply them with:

```bash
v-update-web-domain-cgroup USER DOMAIN
v-update-web-domain-cgroup admin example.com
```

For user-level kernel CPU limits and disk quotas:

```bash
v-update-user-cgroup USER
v-update-user-cgroup admin
```

### Adaptive auto-tuner

```bash
v-tune-sys-resources [USER] [DOMAIN]
```

```bash
v-tune-sys-resources
v-tune-sys-resources admin neredeyasanir.localhost
```

With no arguments it tunes all domains. It inspects traffic rates, memory pressure and the assigned priority (0-5) to dynamically allocate or throttle CPU, RAM and SSD I/O — idle sites get throttled aggressively ("deep eco-idle"), busy ones get boosted.

### Priority (0-5)

```bash
v-set-web-domain-priority USER DOMAIN PRIORITY
v-set-web-domain-priority admin neredeyasanir.localhost 4
```

Priority 0 is "Auto" and lets the tuner decide; higher values bias allocation towards the domain.

### Dynamic RAM scaling

```bash
v-monitor-memory-pressure
```

Scales each domain's `MemoryHigh` up and down between its baseline and peak, driven by the kernel's `memory.pressure` reading — useful as a frequent cron job on hosts with mixed workloads.

### Reporting

```bash
v-list-resource-governance [FORMAT]
v-list-resource-governance json
```

Returns governance metrics and comparative stats for all web domains (current limits, pressure, tuner state).

## Panel

The **Resources** page (`/list/resources/`) exposes the same flow without SSH: per-domain RAM/CPU inputs (`256M`, `512M`, `1G`, `2G` or unlimited), the priority selector and one-click invocations of the tuner (`v-tune-sys-resources`) and limit changes (`v-change-web-domain-cgroup`).

## Notes

- PHP CPU isolation is per-user at the kernel level, because all PHP sites share one FPM master; RAM limits are per-site. Node.js/.NET backends get full per-site kernel CPU+RAM isolation via their own systemd unit.
- cgroup limits for app backends are applied by `v-update-web-domain-cgroup` onto the `hestia-app-<user>-<domain>.service` unit created by [App runtimes](/docs/nexvia/app-runtimes).
