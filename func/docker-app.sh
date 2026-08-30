#===========================================================================#
# NexviaCP Docker Compose application helpers (admin-only feature)          #
#                                                                           #
# Sourced by the v-*-docker-app* CLI scripts. A "docker app" is a single    #
# git repository containing a compose file that runs several services      #
# together (e.g. postgresql + api + admin panel). Language agnostic: the    #
# compose file fully describes the stack.                                   #
#                                                                           #
# Registry layout ($HESTIA/data/docker-apps/<app>/):                        #
#   app.conf             sourceable key='val' file (see below)              #
#   repo/                git clone of the application repository            #
#   nexvia-override.yml  generated port override — loopback-only binds     #
#   .env                 compose substitution vars (panel managed)          #
#   deploy.log           output of the async build/up worker                #
#   .lock                per-app flock for deploy/update operations         #
#                                                                           #
# app.conf keys:                                                            #
#   APP, REPO, BRANCH, COMPOSE_FILE, PROJECT, STATE (deploying|running|     #
#   failed|suspended), TIME, UPDATE_TIME, MAPPINGS ('svc:target:host ...'), #
#   DOMAINS ('user@domain:svc:host ...')                                    #
#===========================================================================#

DOCKER_APPS_DIR="$HESTIA/data/docker-apps"

# Name rules: compose project names must be lowercase [a-z0-9_-]; also used
# as a directory name, so keep it conservative and collision-free.
is_docker_app_format_valid() {
	[[ "$1" =~ ^[a-z0-9][a-z0-9_-]{0,39}$ ]]
}

is_docker_app_valid() {
	[ -f "$DOCKER_APPS_DIR/$1/app.conf" ]
}

# Source app.conf of an app (sets $REPO, $BRANCH, $COMPOSE_FILE, $STATE,
# $MAPPINGS, $DOMAINS, ... in the current shell).
docker_app_load() {
	# shellcheck disable=SC1090
	source "$DOCKER_APPS_DIR/$1/app.conf"
}

# Create / verify the registry root.
docker_app_init_root() {
	[ -d "$DOCKER_APPS_DIR" ] || mkdir -p "$DOCKER_APPS_DIR"
	chmod 700 "$DOCKER_APPS_DIR"
}

# Set (replace or append) a key='value' line in an app.conf. Single quotes
# in the value are escaped so the file stays sourceable.
docker_app_set() {
	local app="$1" key="$2" value="$3"
	local conf="$DOCKER_APPS_DIR/$app/app.conf"
	value=${value//\'/\'\\\'\'}
	if grep -q "^${key}=" "$conf" 2>/dev/null; then
		sed -i "s|^${key}=.*|${key}='${value}'|" "$conf"
	else
		printf "%s='%s'\n" "$key" "$value" >>"$conf"
	fi
}

# Remove a key line from an app.conf.
docker_app_unset() {
	local app="$1" key="$2"
	sed -i "\|^${key}=|d" "$DOCKER_APPS_DIR/$app/app.conf" 2>/dev/null
}

# Allocate the first free loopback port in the shared app range 9100-9999.
# The range is shared with v-add-web-domain-app backends and is checked
# against live listeners, web.conf claims and docker-app claims. The
# optional argument lists ports claimed earlier in the same run (not yet
# persisted) so batch allocation never hands out a duplicate.
# Prints the port on stdout; returns 1 when the range is exhausted.
docker_app_alloc_port() {
	local candidate extra=" $1 "
	for candidate in $(seq 9100 9999); do
		if [ -n "$1" ] && [[ "$extra" == *" $candidate "* ]]; then
			continue # claimed earlier in this run
		fi
		if ss -ltn 2>/dev/null | awk '{print $4}' | grep -q ":${candidate}$"; then
			continue # in use by a listening socket
		fi
		if grep -rq "APP_BACKEND_PORT='${candidate}'" "$HESTIA/data/users/" 2>/dev/null; then
			continue # claimed by a web domain backend
		fi
		if grep -rqE "MAPPINGS='[^']*\b${candidate}\b'" "$DOCKER_APPS_DIR" 2>/dev/null; then
			continue # claimed by another docker app
		fi
		echo "$candidate"
		return 0
	done
	return 1
}

# Is a specific port claimed by any docker app mapping? (used when releasing)
docker_app_port_in_use() {
	local port="$1"
	grep -rqE "MAPPINGS='[^']*\b${port}\b'" "$DOCKER_APPS_DIR" 2>/dev/null
}

# Is a port available for THIS app to claim right now? Checks live sockets,
# web-domain backend claims and OTHER docker apps (own app.conf excluded —
# during regeneration it still holds the previous mappings).
docker_app_port_free_for_app() {
	local app="$1" port="$2" conf
	if ss -ltn 2>/dev/null | awk '{print $4}' | grep -q ":${port}$"; then
		return 1
	fi
	if grep -rq "APP_BACKEND_PORT='${port}'" "$HESTIA/data/users/" 2>/dev/null; then
		return 1
	fi
	for conf in "$DOCKER_APPS_DIR"/*/app.conf; do
		[ -f "$conf" ] || continue
		[ "$conf" = "$DOCKER_APPS_DIR/$app/app.conf" ] && continue
		if grep -qE "MAPPINGS='[^']*\b${port}\b'" "$conf" 2>/dev/null; then
			return 1
		fi
	done
	return 0
}

# Run docker compose for an app with the standard NexviaCP file set:
# panel-managed .env, the repo compose file and (when present) the generated
# loopback override. Runs inside the repo dir so build contexts resolve.
docker_app_compose() {
	local app="$1"
	shift
	local app_dir="$DOCKER_APPS_DIR/$app"
	local repo_dir="$app_dir/repo"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	(
		cd "$repo_dir" || exit 1
		local args=(--env-file "$app_dir/.env" -f "$conf_compose")
		[ -f "$app_dir/nexvia-override.yml" ] && args+=(-f "$app_dir/nexvia-override.yml")
		docker compose "${args[@]}" -p "nexvia-$app" "$@"
	)
}

# Print the compose file inventory (published ports per service) as JSON:
#   [{"service": "api", "target": 3000, "published": 3000, "host_ip": ""}, ...]
# Resolves the compose file exactly like docker_app_compose does, so any
# ${VAR} substitution inside it is honoured.
docker_app_inventory() {
	local app="$1"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local repo_dir="$app_dir/repo"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	[ -f "$repo_dir/$conf_compose" ] || conf_compose="compose.yml"
	python3 - "$repo_dir" "$conf_compose" "$app_dir/.env" <<'PYEOF'
import json, subprocess, sys, os
try:
    import yaml
except ImportError:
    yaml = None

repo, compose_file, env_file = sys.argv[1], sys.argv[2], sys.argv[3]

# Repos commonly reference an env_file that is gitignored and therefore
# missing in a fresh clone; create empty placeholders so `compose config`
# can resolve (values still come from the managed app .env via --env-file).
if yaml is not None:
    compose_path = os.path.join(repo, compose_file)
    if os.path.isfile(compose_path):
        try:
            doc = yaml.safe_load(open(compose_path)) or {}
            base = os.path.dirname(compose_path)
            for svc in (doc.get("services") or {}).values():
                ef = (svc or {}).get("env_file") or []
                if isinstance(ef, str):
                    ef = [ef]
                for entry in ef:
                    if not entry or os.path.isabs(entry):
                        continue
                    p = os.path.normpath(os.path.join(base, entry))
                    if not os.path.exists(p):
                        open(p, "a").close()
        except yaml.YAMLError:
            pass

cmd = ["docker", "compose", "--env-file", env_file, "-f", compose_file,
       "config", "--format", "json"]
p = subprocess.run(cmd, cwd=repo, capture_output=True, text=True)
if p.returncode != 0:
    sys.stderr.write(p.stderr)
    sys.exit(1)
try:
    cfg = json.loads(p.stdout)
except ValueError:
    sys.stderr.write("cannot parse 'docker compose config' output\n")
    sys.exit(1)
inv = []
for name, svc in sorted((cfg.get("services") or {}).items()):
    for pm in svc.get("ports") or []:
        # compose v2 emits {"Target","Published"}, v5 lowercase — support both
        target = pm.get("Target", pm.get("target"))
        published = pm.get("Published", pm.get("published"))
        host_ip = pm.get("HostIp", pm.get("host_ip", "")) or ""
        if published in (None, ""):
            continue  # container-internal port, not published to the host
        inv.append({"service": name, "target": target,
                    "published": str(published), "host_ip": host_ip})
print(json.dumps(inv))
PYEOF
}

# (Re)generate nexvia-override.yml from the live compose inventory.
# Existing (service,target)->host port assignments are PRESERVED so nginx
# domain mappings stay valid across deploys; new mappings get fresh ports
# from the allocator; vanished mappings release theirs. Updates MAPPINGS.
docker_app_regenerate_override() {
	local app="$1"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local inventory existing new_mappings line
	local svc target port svc2 rest tgt2 p2
	local current_svc="" wrote_services=0

	inventory=$(docker_app_inventory "$app") || return 1
	existing=$(grep -m1 '^MAPPINGS=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	new_mappings=""
	used_ports=""

	local tmp_override="$app_dir/.override.tmp"
	{
		printf '# Generated by NexviaCP — do not edit. Regenerated on every deploy.\n'
		printf '# Forces every published port onto 127.0.0.1 with collision-free\n'
		printf '# host ports (see v-add-docker-app / v-update-docker-app).\n'
	} > "$tmp_override" || return 1

	# Inventory entries arrive sorted by service name; iterate once and
	# resolve a host port per entry:
	#   1. reuse the existing (service,target)->port assignment (stability
	#      across deploys — nginx mappings and external scripts keep working)
	#   2. else keep the compose-declared port unchanged when it already
	#      binds loopback AND is free (e.g. "127.0.0.1:5002:8080")
	#   3. else allocate a fresh collision-free port (required for anything
	#      binding 0.0.0.0, e.g. plain "80:80" which would fight host nginx)
	# Then emit the override YAML grouped per service.
	while IFS=' ' read -r svc target host_ip published; do
		[ -n "$svc" ] || continue
		port=""
		for line in $existing; do
			svc2="${line%%:*}"
			rest="${line#*:}"
			tgt2="${rest%%:*}"
			p2="${rest##*:}"
			if [ "$svc2" = "$svc" ] && [ "$tgt2" = "$target" ]; then
				port="$p2"
				break
			fi
		done
		if [ -z "$port" ]; then
			case "$host_ip" in
				127.0.0.1|localhost|::1)
					case " $used_ports " in
						*" $published "*) : ;;
						*)
							if [ -n "$published" ] \
								&& [[ "$published" =~ ^[0-9]+$ ]] \
								&& docker_app_port_free_for_app "$app" "$published"; then
								port="$published"
							fi
							;;
					esac
					;;
			esac
		fi
		if [ -z "$port" ]; then
			port=$(docker_app_alloc_port "$used_ports") || {
				echo "ERROR: no free port in range 9100-9999" >&2
				rm -f "$tmp_override"
				return 1
			}
		fi
		used_ports="${used_ports:+$used_ports }${port}"
		new_mappings="${new_mappings}${svc}:${target}:${port} "
		if [ "$svc" != "$current_svc" ]; then
			if [ "$wrote_services" -eq 0 ]; then
				printf 'services:\n' >> "$tmp_override"
				wrote_services=1
			fi
			printf '  %s:\n' "$svc" >> "$tmp_override"
			printf '    ports: !override\n' >> "$tmp_override"
			current_svc="$svc"
		fi
		printf '      - "127.0.0.1:%s:%s"\n' "$port" "$target" >> "$tmp_override"
	done < <(python3 -c '
import json, sys
for e in json.loads(sys.argv[1]):
    print(e["service"], e["target"], e.get("host_ip", ""), e.get("published", ""))
' "$inventory")

	# An app may legitimately publish nothing; keep an empty (but valid)
	# override in that case so the compose file set stays uniform.
	if [ "$wrote_services" -eq 0 ]; then
		printf '# no published ports in this compose file\n' >> "$tmp_override"
	fi

	mv "$tmp_override" "$app_dir/nexvia-override.yml"
	docker_app_set "$app" MAPPINGS "${new_mappings% }"
}

# Grep the compose file (and common override files) for risky constructs.
# Prints one warning line per finding; returns 0 always (caller decides).
docker_app_preflight() {
	local repo_dir="$1" compose_file="$2"
	local f
	for f in "$compose_file" docker-compose.override.yml compose.override.yml; do
		[ -f "$repo_dir/$f" ] || continue
		if grep -qiE 'privileged:[[:space:]]*true' "$repo_dir/$f" 2>/dev/null; then
			echo "WARNING: $f contains 'privileged: true' containers"
		fi
		if grep -q '/var/run/docker\.sock' "$repo_dir/$f" 2>/dev/null; then
			echo "WARNING: $f mounts /var/run/docker.sock (host root equivalent)"
		fi
		if grep -qiE 'network_mode:[[:space:]]*host' "$repo_dir/$f" 2>/dev/null; then
			echo "WARNING: $f uses network_mode: host (bypasses port isolation)"
		fi
		if grep -qiE '^[[:space:]]*pid:[[:space:]]*host' "$repo_dir/$f" 2>/dev/null; then
			echo "WARNING: $f uses pid: host (sees all host processes)"
		fi
	done
	return 0
}

# Ensure sane container log rotation. Only writes daemon.json when it does
# not exist yet, and only restarts docker while no container is running
# (a restart would bounce live apps otherwise).
docker_app_ensure_log_rotation() {
	if [ -f /etc/docker/daemon.json ]; then
		return 0
	fi
	local running
	running=$(docker ps -q 2>/dev/null | head -1)
	if [ -n "$running" ]; then
		echo "NOTE: /etc/docker/daemon.json missing; not enabling log rotation while containers run"
		return 0
	fi
	cat > /etc/docker/daemon.json <<'EOF'
{
	"log-driver": "json-file",
	"log-opts": { "max-size": "10m", "max-file": "3" }
}
EOF
	systemctl restart docker
}

# Kick the async deploy worker (build + up). Sets STATE=deploying first so
# the panel/API can poll the transition. All output lands in deploy.log.
docker_app_deploy_async() {
	local app="$1"
	local app_dir="$DOCKER_APPS_DIR/$app"
	docker_app_set "$app" STATE "deploying"
	nohup /bin/bash "$HESTIA/func/docker-app-deploy-worker.sh" "$app" \
		>>"$app_dir/deploy.log" 2>&1 &
}

# Authenticated git fetch helper. Never puts the token on a command line:
# a GIT_ASKPASS script feeds it (same pattern as v-deploy-github-repo).
# Usage: docker_app_git CLONE_URL BRANCH DEST
docker_app_git() {
	local repo_url="$1" branch="$2" dest="$3"
	local askpass=""
	if [ -n "$GITHUB_TOKEN" ] && [[ "$repo_url" =~ ^https://github\.com/ ]]; then
		askpass=$(mktemp /tmp/nexvia-askpass.XXXXXX)
		printf '#!/bin/sh\nprintf "%%s\\n" "$NEXVIA_GIT_TOKEN"\n' > "$askpass"
		chmod 700 "$askpass"
	fi
	local rc=0
	if [ -n "$askpass" ]; then
		NEXVIA_GIT_TOKEN="$GITHUB_TOKEN" GIT_ASKPASS="$askpass" GIT_TERMINAL_PROMPT=0 \
			git -c credential.helper= clone --depth 1 -b "$branch" "$repo_url" "$dest" 2>/dev/null \
			|| NEXVIA_GIT_TOKEN="$GITHUB_TOKEN" GIT_ASKPASS="$askpass" GIT_TERMINAL_PROMPT=0 \
				git -c credential.helper= clone --depth 1 "$repo_url" "$dest" 2>/dev/null \
			|| rc=1
		rm -f "$askpass"
	else
		GIT_TERMINAL_PROMPT=0 git -c credential.helper= clone --depth 1 -b "$branch" "$repo_url" "$dest" 2>/dev/null \
			|| GIT_TERMINAL_PROMPT=0 git -c credential.helper= clone --depth 1 "$repo_url" "$dest" 2>/dev/null \
			|| rc=1
	fi
	return $rc
}

# Authenticated in-place update of an existing clone: fetch + hard reset
# to origin/BRANCH (automation-first: local edits in repo/ are discarded,
# the managed files live outside the clone). Same token safety as above.
docker_app_git_update() {
	local repo_dir="$1" branch="$2"
	local askpass=""
	if [ -n "$GITHUB_TOKEN" ] && grep -q 'https://github.com/' \
		"$repo_dir/.git/config" 2>/dev/null; then
		askpass=$(mktemp /tmp/nexvia-askpass.XXXXXX)
		printf '#!/bin/sh\nprintf "%%s\\n" "$NEXVIA_GIT_TOKEN"\n' > "$askpass"
		chmod 700 "$askpass"
	fi
	local rc=0
	if [ -n "$askpass" ]; then
		NEXVIA_GIT_TOKEN="$GITHUB_TOKEN" GIT_ASKPASS="$askpass" GIT_TERMINAL_PROMPT=0 \
			git -C "$repo_dir" fetch --depth 1 origin "$branch" 2>/dev/null \
			&& NEXVIA_GIT_TOKEN="$GITHUB_TOKEN" GIT_ASKPASS="$askpass" \
				GIT_TERMINAL_PROMPT=0 \
				git -C "$repo_dir" reset --hard FETCH_HEAD 2>/dev/null \
			|| rc=1
		rm -f "$askpass"
	else
		GIT_TERMINAL_PROMPT=0 git -C "$repo_dir" fetch --depth 1 origin "$branch" 2>/dev/null \
			&& GIT_TERMINAL_PROMPT=0 git -C "$repo_dir" reset --hard FETCH_HEAD 2>/dev/null \
			|| rc=1
	fi
	return $rc
}

# Print the live status of apps as JSON, keyed by app name:
#   {"<app>": {"state": "...",
#              "services": [{"name","state","status","ports"}],
#              "mappings": "...", "domains": "...",
#              "repo": "...", "branch": "..."}}
# With an app argument only that app is reported. Falls back to
# config-derived expectations when nothing is running yet.
docker_app_status() {
	local app="$1"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	python3 - "$app" "$app_dir" "$conf_compose" <<'PYEOF'
import json, os, subprocess, sys

app, app_dir, compose_file = sys.argv[1], sys.argv[2], sys.argv[3]

def conf_get(key, default=""):
    try:
        with open(os.path.join(app_dir, "app.conf")) as fh:
            for line in fh:
                if line.startswith(key + "="):
                    return line.split("=", 1)[1].strip().strip("'")
    except OSError:
        pass
    return default

repo = os.path.join(app_dir, "repo")
args = ["docker", "compose", "--env-file", os.path.join(app_dir, ".env"),
        "-f", compose_file]
override = os.path.join(app_dir, "nexvia-override.yml")
if os.path.isfile(override):
    args += ["-f", override]
args += ["-p", "nexvia-" + app, "ps", "--all", "--format", "json"]
p = subprocess.run(args, cwd=repo, capture_output=True, text=True)
services = []
raw = []
if p.returncode == 0 and p.stdout.strip():
    # compose v2 emits a JSON array; v5 emits newline-delimited JSON objects
    try:
        parsed = json.loads(p.stdout)
        raw = parsed if isinstance(parsed, list) else [parsed]
    except ValueError:
        for line in p.stdout.splitlines():
            line = line.strip()
            if not line:
                continue
            try:
                raw.append(json.loads(line))
            except ValueError:
                pass
    if isinstance(raw, dict):
        raw = [raw]
    for c in raw:
        # compose v2 emits Capitalized keys, v5 lowercase — support both
        def g(key):
            return c.get(key, c.get(key.lower(), "")) or ""
        services.append({
            "name": g("Service") or "?",
            "state": g("State") or "unknown",
            "status": g("Status"),
            "ports": g("Ports"),
        })

mappings = conf_get("MAPPINGS")
expected = {}
for m in mappings.split():
    if m.count(":") == 2:
        svc, target, host = m.split(":")
        expected.setdefault(svc, []).append(host)

have = {s["name"] for s in services}
for svc in expected:
    if svc not in have:
        services.append({"name": svc, "state": "not-created",
                         "status": "", "ports": ""})

print(json.dumps({
    "state": conf_get("STATE", "unknown"),
    "services": sorted(services, key=lambda s: s["name"]),
    "mappings": mappings,
    "domains": conf_get("DOMAINS"),
    "repo": conf_get("REPO"),
    "branch": conf_get("BRANCH"),
    "compose_file": conf_get("COMPOSE_FILE"),
    "time": conf_get("TIME"),
    "update_time": conf_get("UPDATE_TIME"),
}))
PYEOF
}

# Print the status of every registered app as a single JSON object keyed
# by app name (see docker_app_status for the per-app shape).
docker_apps_status() {
	local conf app first=1
	echo "{"
	for conf in "$DOCKER_APPS_DIR"/*/app.conf; do
		[ -f "$conf" ] || continue
		app=$(grep -m1 '^APP=' "$conf" | cut -d"'" -f2)
		[ -n "$app" ] || continue
		[ "$first" -eq 1 ] || echo ","
		first=0
		printf '"%s": ' "$app"
		docker_app_status "$app"
	done
	echo "}"
}
