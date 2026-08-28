---
title: Anomaly Detection
description: Collect per-domain nginx metrics and flag traffic anomalies with Z-score analysis, with email alerts and a queryable event store.
---

# Anomaly Detection

This module turns nginx access logs into hourly per-domain metrics (request counts, error rates, bandwidth, unique IPs, response codes) kept in a rolling 30-day window, then compares current values against a rolling 7-day baseline using Z-score statistics. Statistically significant deviations are flagged as anomalies (WARNING / CRITICAL), stored with before/after context and emailed to the admin.

## Commands

### Collect metrics

```bash
v-collect-domain-metrics [DOMAIN] [PERIOD]
```

```bash
v-collect-domain-metrics
v-collect-domain-metrics neredeyasanir.localhost
```

Parses nginx logs per domain and stores hourly metrics. Run it hourly to build the baseline (the cron helper below does this for you).

### Detect anomalies

```bash
v-detect-domain-anomalies [DOMAIN] [SENSITIVITY]
```

```bash
v-detect-domain-anomalies
v-detect-domain-anomalies neredeyasanir.localhost 2.5
```

Analyses collected metrics against the 7-day baseline. `SENSITIVITY` is the Z-score threshold: Z > 3.0 is CRITICAL, Z > 2.0 is WARNING with the default of 2.5. Lower the number to get more sensitive (noisier) detection, raise it to only catch extreme spikes. Sends email alerts and stores anomaly events with before/after context.

### Query anomalies

```bash
v-list-domain-anomalies [DOMAIN] [PERIOD] [FORMAT]
```

```bash
v-list-domain-anomalies json
v-list-domain-anomalies neredeyasanir.localhost 7d json
v-list-domain-anomalies all 24h json
```

Supported periods: `24h`, `7d`, `30d`, `90d`, `all`.

### Schedule both automatically

```bash
v-add-cron-anomaly-scan
```

Registers two hourly cron jobs (at minute offsets 17/19 to avoid clashing with other hourly tasks): `v-collect-domain-metrics` to build the baseline and `v-detect-domain-anomalies` for detection plus email alerts. It is idempotent — safe to run multiple times.

## Panel

The **Anomalies** page (`/list/anomalies/`) renders the anomaly timeline with severity filters (it calls `v-list-domain-anomalies`) and offers manual buttons to run `v-collect-domain-metrics` and `v-detect-domain-anomalies` for on-demand scans.

## Notes

- Metric and anomaly data live under `$HESTIA/data/anomalies` (rolling 30-day window).
- Without at least a few days of collected baseline, Z-scores are meaningless — install the cron early and let it run.
- Anomaly alerts share the healing notification email settings configured via `v-set-sys-notification-email` (see [AI healing](/docs/nexvia/ai-healing)).
