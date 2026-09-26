#!/bin/bash
#===========================================================================#
# NexviaCP Docker app deploy worker (internal)                              #
# Called in the background by docker_app_deploy_async() from                #
# func/docker-app.sh. Holds the per-app lock, runs the deploy and records   #
# the resulting STATE. All output goes to the app's deploy.log.             #
#                                                                           #
# Deploy strategy (when no DEPLOY_CMD is set and the stack is running):    #
#   zero-downtime blue-green with automatic rollback.                       #
#                                                                           #
#   1. Build + pull the NEW images while the OLD stack keeps serving —     #
#      niced (nice 19 / ionice idle) and behind a GLOBAL build lock so     #
#      concurrent webhooks never stampede the CPU/disk. Slow is fine.      #
#   2. Recreate changed STATEFUL services (databases etc.) directly —      #
#      unavoidable brief restart, loudly logged.                            #
#   3. For each changed STATELESS service: start a "green" copy on a fresh #
#      loopback port (same networks, env, volumes — derived from the       #
#      resolved compose config, labels nexvia.green=1).                    #
#   4. Health-gate the green containers; on failure remove them and fail   #
#      the deploy — the live stack was never touched (rollback by design). #
#   5. Flip nginx to the green ports (registry rewrite + graceful reload). #
#   6. Retire the old blue containers and the previous green generation.   #
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

# A suspended app stays suspended — an update must not silently resume it.
if [ "$STATE" = "suspended" ]; then
	echo "[nexvia] app '$app' is suspended; skipping deploy"
	exit 0
fi

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
[ -f "$app_dir/nexvia-user-override.yml" ] && override_args="$override_args -f $app_dir/nexvia-user-override.yml"
export NEXVIA_COMPOSE_FILES="--env-file $app_dir/.env -f $repo_dir/$COMPOSE_FILE $override_args -p nexvia-$app"

deploy_fail() {
	docker_app_set "$app" STATE "failed"
	echo "[nexvia] deploy FAILED — see output above"
	$BIN/v-log-action "system" "Error" "Docker" "Docker app deployment failed (App: $app)."
	$BIN/v-add-user-notification admin "Docker Deploy" \
		"'${app}' dağıtımı BAŞARISIZ. Ayrıntılar: $app_dir/deploy.log — canlı sürüm çalışmaya devam ediyor (değişiklik uygulanmadı)." \
		2>/dev/null || true
}

deploy_ok() {
	docker_app_set "$app" STATE "running"
	docker_app_set "$app" UPDATE_TIME "$(date +%s)"
	# Reclaim build cache left behind by replaced images (project-scoped).
	docker image prune -f \
		--filter "label=com.docker.compose.project=nexvia-$app" >/dev/null 2>&1
	echo "[nexvia] deploy OK — state=running"
	$BIN/v-log-action "system" "Info" "Docker" "Docker app deployed (App: $app)."
}

deploy_rc=0
if [ -n "$DEPLOY_CMD" ]; then
	echo "[nexvia] running custom deploy command: $DEPLOY_CMD"
	if ( cd "$repo_dir" && bash -c "$DEPLOY_CMD" ); then
		deploy_ok
	else
		deploy_fail
	fi
elif [ -z "$(docker_app_compose "$app" ps -q 2>/dev/null)" ] \
	&& [ -z "$(docker_app_green_list "$app")" ]; then
	# Nothing is running at all (first deploy / after full stop): plain up.
	# When only green containers exist (e.g. after a reboot) the blue-green
	# path runs instead — a plain up would start a second copy beside them.
	echo "[nexvia] no running containers — first deploy via plain compose up"
	docker_app_global_build_lock
	docker_app_pull_nice "$app"
	NEXVIA_NICE=1 docker_app_compose "$app" up -d --build --remove-orphans || deploy_rc=1
	exec 8>&-
	if [ "$deploy_rc" -eq 0 ]; then deploy_ok; else deploy_fail; fi
else
	# ---------------- zero-downtime blue-green redeploy -----------------
	# Remember the live green generation before we start a new one.
	prev_green=$(docker_app_green_list "$app")

	# 1) Build/pull the new images while the old stack keeps serving.
	#    Niced + globally serialized: concurrent deploys on other apps wait
	#    their turn instead of stampeding the CPU/disk.
	echo "[nexvia] building new images (niced, globally serialized)..."
	docker_app_global_build_lock
	docker_app_pull_nice "$app"
	if ! NEXVIA_NICE=1 docker_app_compose "$app" build; then
		exec 8>&-
		deploy_fail
		exit 1
	fi
	exec 8>&-

	# 2) What would compose recreate with the freshly built images?
	#    Plus: green containers already running an outdated image — compose's
	#    dry-run cannot detect those (it would say "Create", not "Recreate").
	plan=$(docker_app_recreate_plan "$app") || plan=""
	stale=$(docker_app_stale_green_services "$app") || stale=""
	plan=$(printf '%s\n%s\n' "$plan" "$stale" | awk 'NF && !seen[$0]++')
	classify=$(docker_app_classify_services "$app") || classify='{"stateless": [], "stateful": []}'
	eval "$(python3 - "$plan" "$classify" <<'PYEOF'
import json, shlex, sys

plan = set(filter(None, sys.argv[1].split()))
try:
    c = json.loads(sys.argv[2])
except ValueError:
    c = {"stateless": [], "stateful": []}
print("stateless_plan=" + shlex.quote(" ".join(s for s in c["stateless"] if s in plan)))
print("stateful_plan=" + shlex.quote(" ".join(s for s in c["stateful"] if s in plan)))
PYEOF
)"

	if [ -z "$stateless_plan" ] && [ -z "$stateful_plan" ]; then
		echo "[nexvia] nothing to recreate — stack already up to date"
		# Deliberately NO `up -d` here: compose does not know about the
		# green containers serving traffic and would resurrect a second
		# (retired) copy of the service next to them.
		deploy_ok
		echo "===== $(date '+%Y-%m-%d %H:%M:%S') deploy end ====="
		exit 0
	fi

	# 3) Changed stateful services (databases): compose recreates them
	#    directly — a brief restart is unavoidable there. Log it loudly.
	if [ -n "$stateful_plan" ]; then
		echo "[nexvia] NOTE: stateful service(s) changed: $stateful_plan"
		echo "         (these restart in place — expect a short blip)"
		docker_app_compose "$app" up -d --no-deps --no-build --remove-orphans $stateful_plan \
			|| deploy_rc=1
		# Let the fresh database finish opening its sockets before greens
		# start connecting to it.
		sleep 5
	fi

	# 4) Changed stateless services: green copies on fresh loopback ports.
	green_specs=""
	green_names=""
	used_ports=""
	gen=$(date +%s)
	for svc in $stateless_plan; do
		port=$(docker_app_alloc_port "$used_ports") || {
			echo "[nexvia] no free loopback port for green $svc" >&2
			for g in $green_names; do
				docker rm -f "$g" >/dev/null 2>&1 || true
			done
			deploy_fail
			exit 1
		}
		used_ports="${used_ports:+$used_ports }${port}"
		gname="nexvia-${app}-${svc}-g${gen}"
		docker rm -f "$gname" >/dev/null 2>&1 || true
		echo "[nexvia] starting green $svc on 127.0.0.1:$port..."
		if docker_app_green_run "$app" "$svc" "$port" "$gname" >>"$app_dir/green.log" 2>&1; then
			green_specs="${green_specs}${gname}:${port} "
			green_names="${green_names}${gname} "
		else
			echo "[nexvia] green $svc failed to start (see $app_dir/green.log)"
		fi
	done

	if [ -n "$green_specs" ]; then
		# 5) Health gate — on failure remove the greens and bail out;
		#    the live stack has not been touched at all.
		if ! docker_app_wait_green "$green_specs"; then
			for gname in $green_names; do
				docker rm -f "$gname" >/dev/null 2>&1 || true
			done
			deploy_fail
			exit 1
		fi

		# 6) Flip nginx to the green ports (graceful reload).
		for entry in $green_specs; do
			gname="${entry%%:*}"
			port="${entry##*:}"
			svc="${gname#nexvia-${app}-}"
			svc="${svc%-g*}"
			docker_app_switch_domains "$app" "$svc" "$port"
		done

		# 7) Give nginx a moment, then retire the old containers.
		sleep 3
		for svc in $stateless_plan; do
			docker_app_compose "$app" rm -sf "$svc" >/dev/null 2>&1 || true
		done
		# Previous green generation — but ONLY of the services this deploy
		# replaced. Greens of untouched services are still the LIVE
		# containers (nginx still points at them); removing those would
		# defeat the whole point.
		for gname in $prev_green; do
			gsvc="${gname#nexvia-${app}-}"
			gsvc="${gsvc%-g*}"
			case " $stateless_plan " in
				*" $gsvc "*) docker rm -f "$gname" >/dev/null 2>&1 || true ;;
			esac
		done
	fi

	if [ "$deploy_rc" -eq 0 ]; then deploy_ok; else deploy_fail; fi
fi

echo "===== $(date '+%Y-%m-%d %H:%M:%S') deploy end ====="
