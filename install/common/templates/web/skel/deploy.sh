#!/bin/bash
#
# NexviaCP Auto-Deploy Script (per-domain)
#
# Invoked by public_html/deploy.php after a verified GitHub/GitLab webhook.
# Performs a zero-downtime pull + dependency install + service reload.
#
# This file is owned by the domain user and runs as that user (via runuser).
# Customize freely: add build steps, migrations, cache clears, etc.
#
# %branch% is replaced by v-add-web-domain-git with the configured branch.

set -e

BRANCH="%branch%"
# Resolve docroot from script location (script lives in /home/<u>/web/<d>/).
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
DOCROOT="$SCRIPT_DIR/public_html"

cd "$DOCROOT"

echo "[$(date '+%F %T')] Starting deploy for $(basename "$SCRIPT_DIR") (branch: $BRANCH)..."

# 1. Pull latest code from origin (zero-downtime: keeps working tree in place).
if [ -d "$DOCROOT/.git" ]; then
    git fetch origin "$BRANCH"
    git reset --hard "origin/$BRANCH"
else
    echo "[deploy] Not a git repository yet; skipping pull."
fi

# 2. Install dependencies based on project type (only if manifests exist).
#    PHP (Composer)
if [ -f "$DOCROOT/composer.json" ] && command -v composer >/dev/null 2>&1; then
    echo "[deploy] composer install..."
    composer install --no-interaction --prefer-dist --no-dev || true
fi

#    Node.js (npm / pnpm / yarn)
if [ -f "$DOCROOT/package.json" ]; then
    if command -v pnpm >/dev/null 2>&1; then
        echo "[deploy] pnpm install..."
        pnpm install --frozen-lockfile || pnpm install || true
    elif command -v yarn >/dev/null 2>&1 && [ -f "$DOCROOT/yarn.lock" ]; then
        echo "[deploy] yarn install..."
        yarn install --frozen-lockfile || true
    elif command -v npm >/dev/null 2>&1; then
        echo "[deploy] npm ci..."
        npm ci || npm install || true
    fi
fi

#    .NET (dotnet publish)
if [ -f "$DOCROOT/*.csproj" ] || ls "$DOCROOT"/*.csproj >/dev/null 2>&1; then
    if command -v dotnet >/dev/null 2>&1; then
        echo "[deploy] dotnet publish..."
        dotnet publish -c Release -o "$DOCROOT/bin/publish" || true
    fi
fi

# 3. Zero-downtime reload of the managed app backend (Node.js / .NET / Next.js).
# NexviaCP runs each app backend in its own systemd unit (v-add-web-domain-app)
# so this triggers a kernel-isolated restart on the same allocated port. For
# plain PHP sites this is a no-op. Legacy PM2 (if installed) still reloaded too.
DOCROOT_BASE="$(basename "$SCRIPT_DIR")"
if command -v v-restart-web-domain-app >/dev/null 2>&1; then
    # Resolve the domain owner from the path: /home/<user>/web/<domain>/
    PATH_USER="$(echo "$SCRIPT_DIR" | awk -F/ '{print $3}')"
    if [ -n "$PATH_USER" ] && [ "$PATH_USER" != "home" ]; then
        /usr/local/hestia/bin/v-restart-web-domain-app "$PATH_USER" "$DOCROOT_BASE" 2>/dev/null || true
    fi
fi
if command -v pm2 >/dev/null 2>&1; then
    pm2 reload all --silent 2>/dev/null || true
fi

echo "[$(date '+%F %T')] Deploy finished."
exit 0
