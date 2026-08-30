#!/bin/bash
#===========================================================================#
# NexviaCP Docker app deploy worker (internal)                              #
# Called in the background by docker_app_deploy_async() from                #
# func/docker-app.sh. Holds the per-app lock, runs the compose build/up and #
# records the resulting STATE. All output goes to the app's deploy.log.     #
#===========================================================================#

app="$1"

# Includes
# shellcheck source=/etc/hestiacp/hestia.conf
source /etc/hestiacp/hestia.conf
# shellcheck source=/usr/local/hestia/func/main.sh
source $HESTIA/func/main.sh
# shellcheck source=/usr/local/hestia/func/docker-app.sh
source $HESTIA/func/docker-app.sh
# load config file
source_conf "$HESTIA/conf/hestia.conf"

app_dir="$DOCKER_APPS_DIR/$app"
repo_dir="$app_dir/repo"
[ -f "$app_dir/app.conf" ] || {
	echo "[nexvia] app '$app' not found"
	exit 1
}

# Serialize deploys per app.
exec 9>>"$app_dir/.lock"
flock 9

echo ""
echo "===== $(date '+%Y-%m-%d %H:%M:%S') deploy start (pid $$) ====="

docker_app_load "$app"

# Authenticate to GHCR when a GitHub token is configured, so private
# images (ghcr.io/...) can be pulled. Best-effort: public images work
# without it. The token never appears in command lines or this log.
# GHCR_TOKEN (classic PAT with read:packages) takes precedence: fine-grained
# PATs cannot pull private images even when they can read the repository.
ghcr_token="${GHCR_TOKEN:-$GITHUB_TOKEN}"
if [ -n "$ghcr_token" ]; then
	printf '%s' "$ghcr_token" | docker login ghcr.io \
		-u "${GHCR_USER:-${GITHUB_ORG:-github}}" --password-stdin >/dev/null 2>&1 \
		|| echo "[nexvia] ghcr.io login failed (continuing; public images only)"
fi

# Expose the managed compose file set so custom deploy scripts can reuse
# it (docker compose $NEXVIA_COMPOSE_FILES <cmd>).
override_args=""
[ -f "$app_dir/nexvia-override.yml" ] && override_args="-f $app_dir/nexvia-override.yml"
export NEXVIA_COMPOSE_FILES="--env-file $app_dir/.env -f $repo_dir/$COMPOSE_FILE $override_args -p nexvia-$app"

deploy_rc=0
if [ -n "$DEPLOY_CMD" ]; then
	echo "[nexvia] running custom deploy command: $DEPLOY_CMD"
	( cd "$repo_dir" && bash -c "$DEPLOY_CMD" ) || deploy_rc=1
else
	docker_app_compose "$app" up -d --build --remove-orphans || deploy_rc=1
fi

if [ "$deploy_rc" -eq 0 ]; then
	docker_app_set "$app" STATE "running"
	docker_app_set "$app" UPDATE_TIME "$(date +%s)"
	# Reclaim build cache left behind by replaced images (project-scoped).
	docker image prune -f \
		--filter "label=com.docker.compose.project=nexvia-$app" >/dev/null 2>&1
	echo "[nexvia] deploy OK — state=running"
	$BIN/v-log-action "system" "Info" "Docker" "Docker app deployed (App: $app)."
else
	docker_app_set "$app" STATE "failed"
	echo "[nexvia] deploy FAILED — see compose output above"
	$BIN/v-log-action "system" "Error" "Docker" "Docker app deployment failed (App: $app)."
fi

echo "===== $(date '+%Y-%m-%d %H:%M:%S') deploy end ====="
