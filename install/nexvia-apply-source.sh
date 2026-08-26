#!/bin/bash
# info: apply the NexviaCP fork source onto an installed Nexvia Control Panel
# options: [SRC_DIR] [FLAGS]
#
# example: bash install/nexvia-apply-source.sh /root/NexviaCP --docker --portainer --redis
#
# The base installer (hst-install-ubuntu.sh / hst-install-debian.sh) installs
# the upstream Hestia packages from apt.hestiacp.com because NexviaCP does not
# (yet) publish its own .deb repository. This script overlays the fork's own
# code (bin/, func/, web/, install/ + templates) onto /usr/local/hestia and
# re-runs the NexviaCP extension setup that the installer had to skip.
#
# Run it AS ROOT on the server, from inside the NexviaCP repository checkout,
# AFTER the base installer finished successfully.
#
# Flags:
#   --docker      enable Docker userns-remap hardening (v-add-sys-docker-hardening)
#   --portainer   install Portainer CE admin UI (implies --docker)
#   --redis       enable Redis object cache (v-add-sys-redis)
#   --memcached   enable Memcached object cache (v-add-sys-memcached)
#   --pga-sso     install the phpPgAdmin SSO bridge (needs PostgreSQL)
#   --skip-build  do not build web/js + web/css assets with npm

#----------------------------------------------------------#
#                Variables & Functions                      #
#----------------------------------------------------------#

HESTIA='/usr/local/hestia'
SRC_DIR="$(cd "$(dirname "$0")/.." && pwd)"
SKIP_BUILD='no'
RUN_DOCKER='no'
RUN_PORTAINER='no'
RUN_REDIS='no'
RUN_MEMCACHED='no'
RUN_PGA_SSO='no'

for arg in "$@"; do
	case "$arg" in
		--docker) RUN_DOCKER='yes' ;;
		--portainer) RUN_PORTAINER='yes' ;;
		--redis) RUN_REDIS='yes' ;;
		--memcached) RUN_MEMCACHED='yes' ;;
		--pga-sso) RUN_PGA_SSO='yes' ;;
		--skip-build) SKIP_BUILD='yes' ;;
		-*) echo "Unknown option: $arg"; exit 1 ;;
		*) SRC_DIR="$(cd "$arg" && pwd)" ;;
	esac
done
[ "$RUN_PORTAINER" = 'yes' ] && RUN_DOCKER='yes'

fail() {
	echo "[!] ERROR: $1"
	exit 1
}

[ "x$(id -u)" = 'x0' ] || fail 'this script must be run as root'
[ -d "$SRC_DIR/bin" ] && [ -d "$SRC_DIR/web" ] || fail "$SRC_DIR does not look like the NexviaCP repository"
[ -d "$HESTIA" ] || fail "$HESTIA not found - run the base installer first"

echo "[*] Applying NexviaCP source from $SRC_DIR onto $HESTIA ..."

#----------------------------------------------------------#
#                 1. Build web UI assets                    #
#----------------------------------------------------------#
# The repository ships no pre-built web/js/dist or minified CSS, so the fork
# UI has to be compiled with npm before it is copied over the panel.
if [ "$SKIP_BUILD" != 'yes' ] && [ ! -f "$SRC_DIR/web/js/dist/main.min.js" ]; then
	if ! command -v npm >/dev/null 2>&1; then
		echo "[ * ] npm not found, installing Node.js from distro repositories..."
		apt-get update -qq
		apt-get install -y -qq nodejs npm >/dev/null 2>&1 || fail 'could not install nodejs/npm (use --skip-build to proceed with upstream UI assets)'
	fi
	echo "[ * ] Building web UI assets (npm ci + npm run build)..."
	(
		cd "$SRC_DIR"
		npm ci --no-audit --no-fund >/dev/null 2>&1 || npm install --no-audit --no-fund >/dev/null 2>&1 || fail 'npm install failed'
		npm run build >/dev/null 2>&1 || fail 'npm run build failed'
	)
	[ -f "$SRC_DIR/web/js/dist/main.min.js" ] || fail 'web asset build produced no output'
elif [ "$SKIP_BUILD" != 'yes' ]; then
	echo "[ * ] Web UI assets already built, skipping npm build."
fi

#----------------------------------------------------------#
#                 2. Overlay fork source                    #
#----------------------------------------------------------#
cp -a "$SRC_DIR/bin/." "$HESTIA/bin/"
cp -a "$SRC_DIR/func/." "$HESTIA/func/"
cp -a "$SRC_DIR/install/." "$HESTIA/install/"
cp -a "$SRC_DIR/web/." "$HESTIA/web/"

chown -R root:root "$HESTIA/bin" "$HESTIA/func" "$HESTIA/install" "$HESTIA/web"
find "$HESTIA/bin" -type d -exec chmod 755 {} \;
find "$HESTIA/bin" -type f -exec chmod 755 {} \;
find "$HESTIA/func" -type d -exec chmod 755 {} \;
find "$HESTIA/func" -type f -exec chmod 644 {} \;
find "$HESTIA/install" -type d -exec chmod 755 {} \;
find "$HESTIA/install" -type f -exec chmod 644 {} \;
find "$HESTIA/install" -name '*.sh' -exec chmod 755 {} \;
find "$HESTIA/web" -type d -exec chmod 755 {} \;
find "$HESTIA/web" -type f -exec chmod 644 {} \;
# mail-wrapper.php is executed by v-restart-* via sudo, it MUST stay executable
chmod 755 "$HESTIA/web/inc/mail-wrapper.php" 2>/dev/null || true

#----------------------------------------------------------#
#                 3. Refresh runtime templates              #
#----------------------------------------------------------#
# Copy the fork's nginx/apache/dns templates into the live template store so
# the node-js / dotnet / websocket / docker-ui templates show up in the panel
# for BOTH stacks (nginx as proxy, and nginx + php-fpm as web server).
mkdir -p "$HESTIA/data/templates"
cp -rf "$HESTIA/install/deb/templates/." "$HESTIA/data/templates/"
cp -rf "$HESTIA/install/common/templates/web/." "$HESTIA/data/templates/web/" 2>/dev/null || true
cp -rf "$HESTIA/install/common/templates/dns/." "$HESTIA/data/templates/dns/" 2>/dev/null || true
chown -R root:root "$HESTIA/data/templates"
find "$HESTIA/data/templates" -type d -exec chmod 755 {} \;
find "$HESTIA/data/templates" -type f ! -name '*.sh' -exec chmod 644 {} \;
find "$HESTIA/data/templates" -type f -name '*.sh' -exec chmod 755 {} \;

#----------------------------------------------------------#
#                 4. Restart the panel                      #
#----------------------------------------------------------#
systemctl restart hestia || fail 'could not restart hestia service'
echo "[+] NexviaCP source applied and the panel restarted."

#----------------------------------------------------------#
#        5. Optional NexviaCP extension services            #
#----------------------------------------------------------#
# These run the dedicated bin/ scripts that the base installer could not
# call (the upstream .deb does not contain them).
if [ "$RUN_REDIS" = 'yes' ]; then
	echo "[*] Enabling Redis object cache..."
	"$HESTIA/bin/v-add-sys-redis" || echo "[!] Redis setup reported an issue (continuing)"
fi
if [ "$RUN_MEMCACHED" = 'yes' ]; then
	echo "[*] Enabling Memcached object cache..."
	"$HESTIA/bin/v-add-sys-memcached" || echo "[!] Memcached setup reported an issue (continuing)"
fi
if [ "$RUN_DOCKER" = 'yes' ]; then
	echo "[*] Enabling Docker userns-remap hardening..."
	"$HESTIA/bin/v-add-sys-docker-hardening" || echo "[!] Docker hardening reported an issue (continuing)"
fi
if [ "$RUN_PORTAINER" = 'yes' ]; then
	echo "[*] Installing Portainer CE (admin-only, locked to 127.0.0.1)..."
	"$HESTIA/bin/v-add-sys-portainer" || echo "[!] Portainer setup reported an issue (continuing)"
fi
if [ "$RUN_PGA_SSO" = 'yes' ] && [ -x "$HESTIA/bin/v-add-sys-pga-sso" ]; then
	"$HESTIA/bin/v-add-sys-pga-sso" || true
fi

echo ""
echo "[+] Done. Login at your panel URL and verify:"
echo "    - Server Settings, the WEB template list (node-js / dotnet / websocket / docker-ui)"
echo "    - add a test domain + v-add-web-domain-app for a Node.js backend"
echo "    - v-change-web-domain-cgroup for per-site RAM/CPU limits"
exit 0
