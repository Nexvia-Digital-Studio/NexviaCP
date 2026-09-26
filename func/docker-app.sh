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
#                                                                           #
# Live blue-green containers are tracked by the docker labels               #
# nexvia.green=1 / nexvia.app / nexvia.svc (never by compose labels —      #
# `up --remove-orphans` must not reap them), see docker-app-deploy-worker.  #                                    #
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
# Set NEXVIA_NICE=1 to deprioritize the call (build/pull — "slow is fine").
docker_app_compose() {
	local app="$1"
	shift
	local app_dir="$DOCKER_APPS_DIR/$app"
	local repo_dir="$app_dir/repo"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	local nice_cmd=""
	[ -n "$NEXVIA_NICE" ] && nice_cmd=$(docker_app_nice_prefix)
	(
		cd "$repo_dir" || exit 1
		local args=(--env-file "$app_dir/.env" -f "$conf_compose")
		[ -f "$app_dir/nexvia-override.yml" ] && args+=(-f "$app_dir/nexvia-override.yml")
		[ -f "$app_dir/nexvia-user-override.yml" ] && args+=(-f "$app_dir/nexvia-user-override.yml")
		# shellcheck disable=SC2086
		$nice_cmd docker compose "${args[@]}" -p "nexvia-$app" "$@"
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
                        # Eksik env_file → panele yönetilen .env'e symlink.
                        # (Boş placeholder sessiz misconfig'tü: compose env_file
                        # repodaki dosyaya çözümlenir, --env-file yalnızca
                        # ${...} yerine koyma yaptığından değerler konteynere
                        # hiç ulaşmıyordu.)
                        try:
                            os.symlink(env_file, p)
                        except OSError:
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

# Green (blue-green) containers live outside compose's view; a running
# green replaces its compose entry (it IS the live container), otherwise
# the panel would list the retired blue one or "not-created".
p2 = subprocess.run(["docker", "ps", "-a", "--filter", "label=nexvia.app=" + app,
                     "--filter", "label=nexvia.green=1", "--format", "{{json .}}"],
                    capture_output=True, text=True)
if p2.returncode == 0:
    for line in p2.stdout.splitlines():
        line = line.strip()
        if not line:
            continue
        try:
            c = json.loads(line)
        except ValueError:
            continue
        if c.get("State") != "running":
            continue
        labels = dict(kv.split("=", 1) for kv in (c.get("Labels") or "").split(",") if "=" in kv)
        svc = labels.get("nexvia.svc", "")
        if not svc:
            continue
        services = [s for s in services if s["name"] != svc]
        services.append({"name": svc, "state": "running",
                         "status": (c.get("Status") or "") + " (green)",
                         "ports": c.get("Ports", "")})

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

#===========================================================================#
# Zero-downtime / resource-friendly deploy helpers                          #
#                                                                           #
# The deploy worker builds the NEW images while the OLD stack keeps         #
# serving, then brings up a "green" copy of every changed stateless         #
# service on a fresh loopback port, waits for it to answer, and only then   #
# flips nginx to the new port (graceful reload) and retires the old         #
# containers. Stateful services (databases etc.) are never copied: they     #
# are only recreated by compose itself when their image/config changed.    #
#===========================================================================#

# Prefix that deprioritizes builds: "slow is fine, do not disturb the box".
# nice -> CPU scheduling, ionice idle-class -> disk scheduling.
docker_app_nice_prefix() {
	local prefix="nice -n 19"
	if command -v ionice >/dev/null 2>&1; then
		prefix="$prefix ionice -c3"
	fi
	echo "$prefix"
}

# Serialize builds/pulls ACROSS apps: several webhooks firing together must
# not start parallel builds (the exact resource storm this feature avoids).
# Hold the lock for the build phase only, not the whole deploy.
docker_app_global_build_lock() {
	local lock="$DOCKER_APPS_DIR/.build.lock"
	exec 8>"$lock"
	flock 8
}

# Pull ready-made images (prebuilt registry tags), best-effort and niced.
# --ignore-pull-failures keeps pulling the remaining services when one has
# no registry image; older/newer compose plugins without that flag fall
# back to a plain pull. Offline/failed pulls never abort the deploy —
# builds and cached images still work.
docker_app_pull_nice() {
	local app="$1"
	NEXVIA_NICE=1 docker_app_compose "$app" pull --ignore-pull-failures >/dev/null 2>&1 \
		|| NEXVIA_NICE=1 docker_app_compose "$app" pull >/dev/null 2>&1 \
		|| true
}

# Split services into stateful vs stateless from the resolved compose
# config. A service is stateful (never blue-green'd) when it mounts a named
# volume or its name matches the usual data-store suspects. Prints JSON:
#   {"stateless": ["web","api"], "stateful": ["db"]}
docker_app_classify_services() {
	local app="$1"
	docker_app_compose "$app" config --format json 2>/dev/null | python3 -c '
import json, re, sys

cfg = json.load(sys.stdin)
stateful_re = re.compile(
    r"(postgres|pgsql|mysql|mariadb|mongo|redis|memcached|rabbit|mq$|"
    r"elasticsearch|clickhouse|neo4j|influx|prometheus|minio|nats$|"
    r"kafka|cockroach|etcd$|.*[-_]db$|.*[-_]database$|^db$|^database$)"
)
stateless, stateful = [], []
for name, svc in sorted((cfg.get("services") or {}).items()):
    has_volume = False
    for vol in svc.get("volumes") or []:
        # long syntax: {"type": "volume"/"bind", ...}; short: "name:/path"
        if isinstance(vol, dict):
            if vol.get("type") == "volume":
                has_volume = True
        elif isinstance(vol, str) and not vol.startswith(("/", ".")) \
                and not vol.startswith("~") and ":" in vol:
            has_volume = True
    if has_volume or stateful_re.search(name):
        stateful.append(name)
    else:
        stateless.append(name)
print(json.dumps({"stateless": stateless, "stateful": stateful}))
'
}

# Which services would `docker compose up -d` recreate right now? Uses the
# --dry-run preview when the compose plugin supports it (parsing its human
# output — container names carry the project prefix with '-' or '_' as the
# separator depending on compose version) and falls back to "every stateless
# service" on older plugins (worst case: an unnecessary, but still
# zero-downtime, green flip). Prints one service name per line.
docker_app_recreate_plan() {
	local app="$1"
	if docker compose up --help 2>&1 | grep -q -- '--dry-run'; then
		local services
		services=$(docker_app_compose "$app" config --services 2>/dev/null)
		[ -n "$services" ] || return 1
		# dry-run mocks its output on STDERR — merge it into the pipe.
		docker_app_compose "$app" up -d --dry-run 2>&1 | SERVICES="$services" \
			PROJ="nexvia-$app" python3 -c '
import os, sys

services = os.environ["SERVICES"].split()
proj = os.environ["PROJ"]
changed = []
for line in sys.stdin:
    if "Recreate" not in line.split():  # exact word: not "Recreated"
        continue
    for svc in services:
        # container name: <project>[-_]<service>[-_]<index>
        if f"{proj}-{svc}-" in line or f"{proj}_{svc}_" in line:
            changed.append(svc)
            break
print("\n".join(changed))
'
	else
		# No dry-run support: conservatively report every service (compose's
		# own up -d is a no-op for unchanged ones anyway).
		docker_app_compose "$app" config --services 2>/dev/null
	fi
}

# Green containers that no longer run the image the compose config asks
# for. `up --dry-run` cannot see this: with the blue container retired the
# preview shows "Create", not "Recreate", so an image change would be
# silently skipped. Prints one service name per line.
docker_app_stale_green_services() {
	local app="$1"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local repo_dir="$app_dir/repo"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	python3 - "$repo_dir" "$conf_compose" "$app_dir/.env" "$app" <<'PYEOF'
import json, os, subprocess, sys

repo, compose_file, env_file, app = sys.argv[1:5]

cmd = ["docker", "compose", "--env-file", env_file, "-f", compose_file]
for extra in ("nexvia-override.yml", "nexvia-user-override.yml"):
    if os.path.isfile(os.path.join(os.path.dirname(env_file), extra)):
        cmd += ["-f", extra]
cmd += ["-p", "nexvia-" + app, "config", "--format", "json"]
r = subprocess.run(cmd, cwd=repo, capture_output=True, text=True)
if r.returncode != 0:
    sys.exit(0)  # config problems surface in the main deploy path
try:
    cfg = json.loads(r.stdout)
except ValueError:
    sys.exit(0)

def image_id(ref):
    p = subprocess.run(["docker", "image", "inspect", ref, "-f", "{{.Id}}"],
                       capture_output=True, text=True)
    return p.stdout.strip() if p.returncode == 0 else None

# Green (blue-green) containers of this app, with their service label and
# the image id they currently run.
ps = subprocess.run(["docker", "ps", "-a", "--filter", "label=nexvia.app=" + app,
                     "--filter", "label=nexvia.green=1",
                     "--format", "{{.Names}}\t{{.Label \"nexvia.svc\"}}\t{{.Image}}"],
                    capture_output=True, text=True)
for line in ps.stdout.splitlines():
    parts = line.split("\t")
    if len(parts) != 3:
        continue
    name, svc, _img = parts
    want = (cfg.get("services") or {}).get(svc, {}).get("image")
    if not want:
        continue  # service vanished from the compose file — not this helper's job
    running = subprocess.run(["docker", "inspect", "-f", "{{.Image}}", name],
                             capture_output=True, text=True)
    if running.returncode != 0:
        continue
    if running.stdout.strip() != image_id(want):
        print(svc)
PYEOF
}

# Start a green copy of SERVICE on a fresh loopback port. The container is
# derived from the RESOLVED compose config (env, volumes, entrypoint, health
# checks — not from the possibly stale running container) and joins the same
# networks, so DB_HOST=db style links keep working. It is tagged with
# nexvia.* labels instead of compose labels so `up --remove-orphans` never
# reaps it. Prints the container id; exits non-zero on failure.
docker_app_green_run() {
	local app="$1" svc="$2" port="$3" name="$4"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local repo_dir="$app_dir/repo"
	local conf_compose
	conf_compose=$(grep -m1 '^COMPOSE_FILE=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	[ -n "$conf_compose" ] || conf_compose="docker-compose.yml"
	python3 - "$repo_dir" "$conf_compose" "$app_dir/.env" \
		"$app" "$svc" "$port" "$name" <<'PYEOF'
import json, os, subprocess, sys

repo, compose_file, env_file, app, svc, port, name = sys.argv[1:8]

# Same file set as docker_app_compose (override files are optional).
cmd = ["docker", "compose", "--env-file", env_file, "-f", compose_file]
for extra in ("nexvia-override.yml", "nexvia-user-override.yml"):
    p = os.path.join(os.path.dirname(env_file), extra)
    if os.path.isfile(p):
        cmd += ["-f", extra]
cmd += ["-p", "nexvia-" + app, "config", "--format", "json"]

r = subprocess.run(cmd, cwd=repo, capture_output=True, text=True)
if r.returncode != 0:
    sys.stderr.write(r.stderr)
    sys.exit(1)
try:
    cfg = json.loads(r.stdout)
except ValueError:
    sys.stderr.write("cannot parse 'docker compose config' output\n")
    sys.exit(1)

service = (cfg.get("services") or {}).get(svc)
if not service:
    sys.stderr.write(f"service {svc} not found in compose config\n")
    sys.exit(1)

args = ["docker", "run", "-d", "--name", name,
        "--restart", "unless-stopped",
        "--label", f"nexvia.app={app}",
        "--label", f"nexvia.svc={svc}",
        "--label", "nexvia.green=1"]

# The resolved config ports carry the override host ports; rebind onto the
# fresh green port, keeping the container-side target port.
target = None
for pm in service.get("ports") or []:
    target = pm.get("target")
    if target:
        break
if not target:
    sys.stderr.write(f"service {svc} publishes no port; cannot green-flip\n")
    sys.exit(1)
args += ["-p", f"127.0.0.1:{port}:{target}"]

env = service.get("environment") or {}
if isinstance(env, dict):
    env = [f"{k}={v if v is not None else ''}" for k, v in env.items()]
for e in env:
    args += ["-e", e]

for vol in service.get("volumes") or []:
    if isinstance(vol, dict):
        src = vol.get("source") or vol.get("target")
        dst = vol.get("target") or ""
        ro = ":ro" if vol.get("read_only") else ""
        if src and dst:
            args += ["-v", f"{src}:{dst}{ro}"]
    elif isinstance(vol, str) and ":" in vol:
        args += ["-v", vol]

networks = service.get("networks") or {}
# Resolve compose service-network keys to real docker network names via the
# top-level "networks" section (compose key "default" is NOT the docker
# "default" bridge — its real name is e.g. "nexvia-app_default").
top_nets = cfg.get("networks") or {}
net_keys = []
if isinstance(networks, dict) and networks:
    net_keys = list(networks.keys())
elif isinstance(networks, list):
    net_keys = list(networks)
for key in net_keys:
    real = (top_nets.get(key) or {}).get("name") or key
    args += ["--network", real]

if service.get("user"):
    args += ["--user", str(service["user"])]
if service.get("working_dir"):
    args += ["-w", service["working_dir"]]
if service.get("entrypoint"):
    ep = service["entrypoint"]
    args += ["--entrypoint", ep[0] if isinstance(ep, list) else ep]

hc = service.get("healthcheck") or {}
if hc:
    test_cmd = hc.get("test") or []
    if isinstance(test_cmd, list):
        test_cmd = " ".join(test_cmd)
    if test_cmd:
        args += ["--health-cmd", test_cmd]
    if hc.get("interval"):
        args += ["--health-interval", hc["interval"]]
    if hc.get("timeout"):
        args += ["--health-timeout", hc["timeout"]]
    if hc.get("retries"):
        args += ["--health-retries", str(hc["retries"])]

args.append(service.get("image") or f"nexvia-{app}-{svc}")
cmd = service.get("command")
if isinstance(cmd, list):
    args += cmd
elif cmd:
    args += [cmd]

p = subprocess.run(args, capture_output=True, text=True)
if p.returncode != 0:
    sys.stderr.write(p.stderr)
    sys.exit(1)
print(p.stdout.strip())
PYEOF
}

# Remove stale green containers of an app (all of them, or one service).
docker_app_green_rm() {
	local app="$1" svc="$2"
	local filter_args=(--filter "label=nexvia.app=$app" --filter "label=nexvia.green=1")
	[ -n "$svc" ] && filter_args+=(--filter "label=nexvia.svc=$svc")
	docker ps -aq "${filter_args[@]}" 2>/dev/null \
		| xargs -r docker rm -f >/dev/null 2>&1
}

# List green containers (names only) — empty when none are running.
docker_app_green_list() {
	local app="$1"
	docker ps --format '{{.Names}}' \
		--filter "label=nexvia.app=$app" --filter "label=nexvia.green=1" 2>/dev/null
}

# Wait until every given green container answers. TCP-probes its loopback
# port and, when the service defines a healthcheck, additionally waits for
# docker to report it healthy. Returns non-zero on timeout (caller must
# roll back — the live stack is still untouched at that point).
# Usage: docker_app_wait_green "name:port name:port ..."
docker_app_wait_green() {
	local entry name port i healthy
	for entry in $1; do
		name="${entry%%:*}"
		port="${entry##*:}"
		for i in $(seq 1 45); do
			if (exec 3<>"/dev/tcp/127.0.0.1/$port") 2>/dev/null; then
				break
			fi
			sleep 2
		done
		if ! (exec 3<>"/dev/tcp/127.0.0.1/$port") 2>/dev/null; then
			echo "[nexvia] green container $name did not open port $port" >&2
			return 1
		fi
		# Optional docker healthcheck: wait for healthy (max 60s)
		if [ "$(docker inspect -f '{{if .Config.Healthcheck}}1{{end}}' "$name" 2>/dev/null)" = "1" ]; then
			for i in $(seq 1 30); do
				healthy=$(docker inspect -f '{{.State.Health.Status}}' "$name" 2>/dev/null)
				[ "$healthy" = "healthy" ] && break
				[ "$healthy" = "unhealthy" ] && {
					echo "[nexvia] green container $name reports unhealthy" >&2
					return 1
				}
				sleep 2
			done
			[ "$healthy" = "healthy" ] || {
				echo "[nexvia] green container $name never became healthy" >&2
				return 1
			}
		fi
		echo "[nexvia] green $name is up on 127.0.0.1:$port"
	done
	return 0
}

# Flip nginx (and the registries) from the old to the new port for every
# domain linked to SERVICE. Rewrites web.conf APP_BACKEND_PORT, the rendered
# nginx conf files, app.conf MAPPINGS/DOMAINS and finishes with a single
# graceful reload — established connections survive, new ones hit green.
docker_app_switch_domains() {
	local app="$1" svc="$2" new_port="$3"
	local app_dir="$DOCKER_APPS_DIR/$app"
	local user domain old_port conf_file changed=0 entry rest link_svc link_port

	# MAPPINGS: svc:target:old -> svc:target:new
	local mappings new_mappings=""
	mappings=$(grep -m1 '^MAPPINGS=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	for entry in $mappings; do
		rest="${entry#*:}"
		if [ "${entry%%:*}" = "$svc" ] && [ "${rest##*:}" != "$new_port" ]; then
			entry="${entry%:*}:$new_port"
		fi
		new_mappings="${new_mappings}${entry} "
	done
	docker_app_set "$app" MAPPINGS "${new_mappings% }"

	# Linked domains: user@domain:svc:old -> new, web.conf + nginx confs
	local new_domains=""
	local domains_line
	domains_line=$(grep -m1 '^DOMAINS=' "$app_dir/app.conf" 2>/dev/null | cut -d"'" -f2)
	for entry in $domains_line; do
		user="${entry%%@*}"
		rest="${entry#*@}"
		domain="${rest%%:*}"
		rest="${rest#*:}"
		link_svc="${rest%%:*}"
		link_port="${rest##*:}"
		if [ "$link_svc" = "$svc" ] && [ "$link_port" != "$new_port" ]; then
			link_port="$new_port"
			USER_DATA="$HESTIA/data/users/$user" update_object_value 'web' 'DOMAIN' \
				"$domain" '$APP_BACKEND_PORT' "$new_port" 2>/dev/null
			for conf_file in \
				"$HOMEDIR/$user/conf/web/$domain/nginx.conf" \
				"$HOMEDIR/$user/conf/web/$domain/nginx.ssl.conf"; do
				[ -f "$conf_file" ] || continue
				sed -i "s/127\.0\.0\.1:[0-9]\+/127.0.0.1:$new_port/g" "$conf_file"
				changed=1
			done
		fi
		new_domains="${new_domains}${user}@${domain}:${link_svc}:${link_port} "
	done
	[ -n "$new_domains" ] && docker_app_set "$app" DOMAINS "${new_domains% }"

	if [ "$changed" -eq 1 ]; then
		if nginx -t >/dev/null 2>&1; then
			nginx -s reload \
				&& echo "[nexvia] nginx reloaded — $svc now served from :$new_port"
		else
			echo "[nexvia] WARNING: nginx config test failed after port switch — NOT reloaded"
		fi
	fi
}
