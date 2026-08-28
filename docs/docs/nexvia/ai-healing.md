---
title: AI Healing
description: Self-healing engine that detects 5xx bursts, stuck PHP-FPM pools and crashed services, restarts them and reports root-cause analysis.
---

# AI Healing

NexviaCP's healing engine watches your sites for 500/502 Bad Gateway bursts, stuck PHP-FPM pools and crashed Node.js/.NET systemd services. When a failure is detected it heals the service (restart it safely), generates a root-cause diagnosis and dispatches a responsive HTML email to the admin. Every action is recorded as an event you can review later.

## Commands

### Run the healing engine

```bash
v-monitor-sys-healing [ACTION]
```

```bash
v-monitor-sys-healing
v-monitor-sys-healing force
```

The default action is `check`; `force` re-runs detection immediately regardless of the usual interval. This is the script you wire into cron (or the panel daemon) — one run performs inspection, healing, diagnosis and notification.

### Review past events

```bash
v-list-sys-healing-events [FORMAT]
```

```bash
v-list-sys-healing-events
v-list-sys-healing-events json
```

Returns the timeline of past healing events, AI diagnosis reports, live service states and recent email alert logs as JSON or plain text.

### Alert routing

Configure the recipient and threshold for healing emails:

```bash
v-set-sys-notification-email EMAIL [LEVEL] [SENDER_NAME] [SENDER_EMAIL] [ENABLED]
v-set-sys-notification-email admin@example.com WARNING "NexviaCP AI Ops" alerts@example.com yes
```

Send an HTML email notification yourself (also used internally by deployments and alerts):

```bash
v-send-sys-notification SUBJECT MESSAGE [LEVEL] [RECIPIENT_EMAIL] [EVENT_TYPE] [EXTRA_JSON]
```

```bash
v-send-sys-notification "PHP-FPM Pool Auto-Healed" \
  "PHP-FPM 8.2 pool hung due to worker exhaustion and was restarted successfully." \
  "SUCCESS" "admin@example.com" "healing"
```

For chat/URL destinations (Telegram, Discord, Slack, webhooks) use the notification channels described in [Notifications & tooling](/docs/nexvia/notifications-tooling).

## Panel

The **AI Healing** page (`/list/ai-healing/`) shows the healing timeline (`v-list-sys-healing-events`), live service states, and forms that call `v-set-sys-notification-email` and trigger `v-monitor-sys-healing` — including a manual "force run" button.

## Notes

- Healing event data is stored under `/var/lib/hestia/healing` (root-only); events can contain journalctl snippets, so the directory is never world-writable.
- Healing works best when metrics collection is enabled — see [Anomaly detection](/docs/nexvia/anomaly-detection) for the baseline collection cron.
