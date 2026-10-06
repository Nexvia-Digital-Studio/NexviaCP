#!/bin/bash
# Nexvia demo sites — shared helpers for bin/v-*-demo-site scripts.
#
# Demo sites publish a git repository under a secret path of the panel
# domain, e.g. https://panel.example.com/onlymutfak-1a2b3c4d5e6f7a8b/
# The random 16-hex suffix makes the URL unguessable; that IS the access
# control (client demos — no accounts, no passwords).
#
# Disk layout:
#   /var/lib/hestia/demos/<slug>/        published tree (served by nginx)
#   /var/lib/hestia/demos/<slug>/.src    git clone (pulled on every update)
# Registry (one file per demo, sourceable key='val'):
#   $HESTIA/data/users/<user>/demo-sites/<slug>.conf
# nginx: one location file per demo in /etc/nginx/demo-sites/<slug>.conf,
# included from the panelproxy SSL template.

DEMO_DEMOS_DIR="/var/lib/hestia/demos"
DEMO_NGINX_DIR="/etc/nginx/demo-sites"
DEMO_POOL_USER="nexviademo"
DEMO_POOL_GROUP="nexviademo"

# Socket of the demo php-fpm pool: derived from where v-ensure-demo-sites
# actually installed it (highest installed FPM when first created), so
# generated nginx confs stay in sync with the deployed pool.
demo_php_sock() {
	local p v
	for p in /etc/php/*/fpm/pool.d/nexvia-demo.conf; do
		if [ -f "$p" ]; then
			v="${p#/etc/php/}"
			v="${v%%/*}"
			echo "/run/php/php${v}-fpm-nexviademo.sock"
			return
		fi
	done
	echo "/run/php/php8.5-fpm-nexviademo.sock"
}
DEMO_PHP_SOCK=$(demo_php_sock)

# <name>-<16 hex>: name is lowercase letters/digits/dashes.
demo_slug_format_valid() {
	[[ "$1" =~ ^[a-z0-9][a-z0-9-]{0,40}-[a-f0-9]{16}$ ]]
}

# Lowercase, dashes, max 30 chars — safe for URLs and file names.
demo_sanitize_name() {
	printf '%s' "$1" | tr '[:upper:]' '[:lower:]' | sed -E 's/[^a-z0-9]+/-/g; s/^[-]+|[-]+$//g' | cut -c1-30
}

# Run git as the demo pool user (files it creates must be owned by it so
# PHP can write caches/uploads inside its own demo tree). The GitHub token
# is fed via GIT_ASKPASS, never in the URL or on the command line.
demo_git() {
	local use_token=0 a askpass="" rc dir_next=0 repo_dir=""
	for a in "$@"; do
		[[ "$a" =~ ^https://github\.com/ ]] && use_token=1
		# On fetch/reset the remote URL is NOT an argument — it lives in the
		# repo's .git/config. Probe the -C <dir> repo for a github remote,
		# otherwise private-repo fetches run without credentials and 401.
		if [ "$dir_next" = "1" ]; then
			repo_dir="$a"
			dir_next=0
		elif [ "$a" = "-C" ]; then
			dir_next=1
		fi
	done
	if [ "$use_token" = "0" ] && [ -n "$repo_dir" ] \
		&& grep -q 'https://github\.com/' "$repo_dir/.git/config" 2>/dev/null; then
		use_token=1
	fi
	if [ "$use_token" = "1" ] && [ -n "$GITHUB_TOKEN" ]; then
		askpass=$(mktemp /tmp/nexvia-demo-askpass.XXXXXX)
		printf '#!/bin/sh\nprintf "%%s\\n" "$NEXVIA_GIT_TOKEN"\n' > "$askpass"
		chmod 700 "$askpass"
		chown "$DEMO_POOL_USER:$DEMO_POOL_GROUP" "$askpass"
		runuser -u "$DEMO_POOL_USER" -- env HOME="$DEMO_DEMOS_DIR" \
			NEXVIA_GIT_TOKEN="$GITHUB_TOKEN" GIT_ASKPASS="$askpass" \
			GIT_TERMINAL_PROMPT=0 git -c credential.helper= "$@"
		rc=$?
		rm -f "$askpass"
	else
		runuser -u "$DEMO_POOL_USER" -- env HOME="$DEMO_DEMOS_DIR" \
			GIT_TERMINAL_PROMPT=0 git -c credential.helper= "$@"
		rc=$?
	fi
	return $rc
}

# Publish the clone (.src[/subdir]) into the served tree, then normalize
# permissions: dirs 755 / files 644 owned by the pool user (nginx reads
# statics, PHP may write inside its own tree). .src is locked to 700 so
# nginx can never traverse into the git clone.
demo_publish_tree() {
	local slug="$1" subdir="$2"
	local src="$DEMO_DEMOS_DIR/$slug/.src"
	[ -n "$subdir" ] && src="$src/$subdir"
	[ -d "$src" ] || return 1
	rsync -a --delete \
		--exclude='.git*' --exclude='.src' \
		"$src"/ "$DEMO_DEMOS_DIR/$slug"/ || return 1
	demo_fix_perms "$slug"
}

demo_fix_perms() {
	local slug="$1" base="$DEMO_DEMOS_DIR/$slug"
	chown -R "$DEMO_POOL_USER:$DEMO_POOL_GROUP" "$base"
	find "$base" -type d -exec chmod 755 {} +
	find "$base" -type f -exec chmod 644 {} +
	chmod 700 "$base/.src" 2>/dev/null || true
	chmod 755 "$DEMO_DEMOS_DIR/$slug"
}

# One nginx location file per demo. `root` (not alias!) so the URI path
# /<slug>/... maps straight onto $DEMO_DEMOS_DIR/<slug>/... and the nested
# php regex stays correct. try_files falls back to the site's own
# index.php so front-controller PHP apps work out of the box.
#
# $5 (optional): extra fastcgi_param lines — per-demo environment for
# DB-backed apps (fastcgi params are NOT inherited into the php location
# once it declares its own, so they must be injected inside it).
demo_gen_nginx_conf() {
	local slug="$1" owner="$2" repo="$3" branch="$4" env_params="${5:-}"
	cat > "$DEMO_NGINX_DIR/$slug.conf" <<NXV_DEMO_EOF
# Nexvia demo site: $slug (owner: $owner) — $repo @$branch
# Managed by v-add-demo-site / v-delete-demo-site — DO NOT EDIT.
location = /$slug {
	return 301 /$slug/;
}
location ^~ /$slug/ {
	root $DEMO_DEMOS_DIR;
	index index.php index.html index.htm;

	add_header X-Robots-Tag "noindex, nofollow, noarchive" always;
	add_header Cache-Control "no-cache, must-revalidate" always;

	access_log /var/log/nginx/demos.access.log main;
	error_log  /var/log/nginx/demos.error.log error;

	try_files \$uri \$uri/ \$uri.html /$slug/index.php;

	# dotfiles: .git, .env, .src …
	location ~ /\. {
		deny all;
		return 404;
	}
	# sensitive file types
	location ~* \.(sql|bak|old|orig|save|swp|log|ini|env|sqlite|db|sh|md|lock|yml|yaml|toml|conf)$ {
		deny all;
		return 404;
	}

	location ~ [^/]\.php(/|\$) {
		try_files \$fastcgi_script_name =404;
		include /etc/nginx/fastcgi_params;
		fastcgi_pass unix:$DEMO_PHP_SOCK;
		fastcgi_index index.php;
		fastcgi_param SCRIPT_FILENAME \$request_filename;
		# Per-slug jail: overrides the pool-wide open_basedir (request-time
		# admin value wins over pool default) so one demo can never read
		# another demo's tree.
		fastcgi_param PHP_ADMIN_VALUE "open_basedir=$DEMO_DEMOS_DIR/$slug:/var/lib/php/sessions:/tmp:/usr/share/php";
$env_params
		fastcgi_read_timeout 60s;
	}
}
NXV_DEMO_EOF
	# The conf may carry per-demo DB credentials — keep it root-only.
	chmod 640 "$DEMO_NGINX_DIR/$slug.conf"
}

# Validate config, then graceful reload (never a hard restart — the panel
# itself is proxied through this nginx instance; restart = CF 521).
demo_nginx_apply() {
	nginx -t >/dev/null 2>&1 || return 1
	systemctl reload nginx || return 1
}

demo_reg_file() {
	echo "$HESTIA/data/users/$1/demo-sites/$2.conf"
}

# Purge the panel domain's caches (nginx/OPcache/Cloudflare) after a demo
# changed, mirroring v-update-web-domain-git's deploy flow. The panel
# domain is discovered from the live conf that includes demo-sites.
demo_purge_cache() {
	local conf domain owner uc
	for conf in /etc/nginx/conf.d/domains/*.ssl.conf; do
		[ -f "$conf" ] || continue
		grep -q "demo-sites" "$conf" || continue
		domain=$(basename "$conf" .ssl.conf)
		for uc in "$HESTIA"/data/users/*/web.conf; do
			if grep -q "^DOMAIN='$domain' " "$uc" 2>/dev/null; then
				# /usr/local/hestia/data/users/<user>/web.conf -> <user>
				owner=$(basename "$(dirname "$uc")")
				break
			fi
		done
		break
	done
	if [ -z "$owner" ] || [ -z "$domain" ] || [ ! -x "$BIN/v-purge-web-domain-cache" ]; then
		return 0
	fi
	$BIN/v-purge-web-domain-cache "$owner" "$domain" all >/dev/null 2>&1 \
		&& echo "[*] Panel domain cache purged ($domain)." \
		|| echo "[!] Panel domain cache purge reported an error ($domain)."
}

# Read one key='val' pair from a registry file without sourcing it
# (sourcing would clobber the caller's shell variables).
demo_reg_key() {
	sed -n "s/^$2='\(.*\)'$/\1/p" "$1" | head -1
}

demo_reg_set_key() {
	sed -i "s|^$2='.*'|$2='$3'|" "$1"
}

# ---------------------------------------------------------------------
# MySQL-backed demos (admin panels, DB-requiring PHP apps)
# ---------------------------------------------------------------------
# A demo can request its own MariaDB database + user, created with the
# demo and dropped with it. Credentials reach the app two ways:
#   * per-request FastCGI params (getenv()/$_SERVER in PHP):
#       DB_HOST, DB_NAME, DB_USER, DB_PASS
#       (+ <PREFIX>_DB_HOST/… copies when --db=PREFIX was given)
#   * the install hook's environment (demo_install_run).
# mysql runs as root over the unix socket (MariaDB socket auth). The
# database and user are named after the slug's 16-hex suffix, so they are
# unique per demo and regenerated on re-create.

demo_db_host() {
	# 'localhost' (not 127.0.0.1!): the demo PHP jail (nxv_phpjail nftables)
	# blocks ALL TCP egress for the pool user, but cannot filter unix
	# sockets — and PHP's mysqlnd routes host=localhost through
	# /run/mysqld/mysqld.sock. DB users are provisioned at 'localhost'
	# (and 127.0.0.1 for good measure) accordingly.
	echo "${DEMO_DB_HOST:-localhost}"
}

demo_db_provision() {
	# $1 = slug → drops & (re)creates db+user, echoes "db user pass"
	local hex dbn dbu pass
	hex="${1##*-}"
	[[ "$hex" =~ ^[a-f0-9]{16}$ ]] || return 1
	dbn="demo_${hex}"
	dbu="d${hex}"
	pass=$(openssl rand -hex 16) || return 1
	mysql <<SQL || return 1
DROP DATABASE IF EXISTS \`$dbn\`;
DROP USER IF EXISTS \`$dbu\`@'localhost';
DROP USER IF EXISTS \`$dbu\`@'127.0.0.1';
CREATE DATABASE \`$dbn\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER \`$dbu\`@'localhost' IDENTIFIED BY '$pass';
CREATE USER \`$dbu\`@'127.0.0.1' IDENTIFIED BY '$pass';
GRANT ALL PRIVILEGES ON \`$dbn\`.* TO \`$dbu\`@'localhost';
GRANT ALL PRIVILEGES ON \`$dbn\`.* TO \`$dbu\`@'127.0.0.1';
SQL
	echo "$dbn $dbu $pass"
}

demo_db_drop() {
	# $1 = slug — idempotent, never fails the caller
	local hex dbn dbu
	hex="${1##*-}"
	[[ "$hex" =~ ^[a-f0-9]{16}$ ]] || return 0
	dbn="demo_${hex}"
	dbu="d${hex}"
	mysql <<SQL 2>/dev/null
DROP DATABASE IF EXISTS \`$dbn\`;
DROP USER IF EXISTS \`$dbu\`@'localhost';
DROP USER IF EXISTS \`$dbu\`@'127.0.0.1';
SQL
	return 0
}

demo_db_fastcgi_params() {
	# $1 db, $2 user, $3 pass, $4 prefix (optional, e.g. URBANA)
	# → fastcgi_param lines injected into the demo's php location.
	local db="$1" usr="$2" pass="$3" pfx="${4:-}" host
	host=$(demo_db_host)
	printf '\t\tfastcgi_param DB_HOST "%s";\n' "$host"
	printf '\t\tfastcgi_param DB_NAME "%s";\n' "$db"
	printf '\t\tfastcgi_param DB_USER "%s";\n' "$usr"
	printf '\t\tfastcgi_param DB_PASS "%s";\n' "$pass"
	if [ -n "$pfx" ]; then
		printf '\t\tfastcgi_param %s_DB_HOST "%s";\n' "$pfx" "$host"
		printf '\t\tfastcgi_param %s_DB_NAME "%s";\n' "$pfx" "$db"
		printf '\t\tfastcgi_param %s_DB_USER "%s";\n' "$pfx" "$usr"
		printf '\t\tfastcgi_param %s_DB_PASS "%s";\n' "$pfx" "$pass"
	fi
	printf '\t\tfastcgi_param NEXVIA_DEMO "1";\n'
}

demo_install_run() {
	# $1 slug, $2 install_cmd, $3 db, $4 dbuser, $5 dbpass
	# Runs the command as the demo pool user inside the demo's .src clone
	# (repo code — same trust level as the demo's PHP itself) with the
	# demo's DB credentials in the environment. db args may be empty.
	local dir="$DEMO_DEMOS_DIR/$1/.src"
	[ -d "$dir" ] || return 1
	runuser -u "$DEMO_POOL_USER" -- env HOME="$DEMO_DEMOS_DIR" \
		PATH="/usr/local/bin:/usr/bin:/bin" NEXVIA_DEMO=1 \
		DB_HOST="$(demo_db_host)" DB_NAME="$3" DB_USER="$4" DB_PASS="$5" \
		bash -c "cd '$dir' && $2"
}
