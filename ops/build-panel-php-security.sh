#!/bin/bash
set -Eeuo pipefail
umask 022
# Build only. Install the resulting package after reviewing its validation output.
# Source checksum: https://www.php.net/releases/index.php?json&version=8.5.12
[ "$(id -u)" = 0 ] || { echo 'Run as root' >&2; exit 1; }
[ "$(dpkg --print-architecture)" = amd64 ] || exit 1
. /etc/os-release
[ "$ID" = debian ] && [ "$VERSION_ID" = 13 ] || exit 1
work=/root/php-security-20261021
install -d -m 700 "$work"
cd "$work"
if [ ! -f panel-php-before.tar.xz ]; then
    tar -cJf panel-php-before.tar.xz /usr/local/hestia/php
fi
if [ ! -f php-8.5.12.tar.xz ]; then
    curl -fL --connect-timeout 15 --max-time 120 https://www.php.net/distributions/php-8.5.12.tar.xz -o php-8.5.12.tar.xz
fi
export DEBIAN_FRONTEND=noninteractive
apt-get install --no-install-recommends --no-remove -y make pkg-config autoconf bison re2c libxml2-dev libsqlite3-dev libssl-dev libcurl4-openssl-dev libzip-dev libgmp-dev libonig-dev > build-dependencies.log 2>&1
/usr/local/hestia/php/bin/php -m > modules-before.txt
printf '%s  %s\n' '08bc2eba2a588654ade1357174aa646b761556c862dda0678345ee29ed481209' php-8.5.12.tar.xz | sha256sum -c -
tar -xf php-8.5.12.tar.xz
cd php-8.5.12
./configure --prefix=/usr/local/hestia/php --with-libdir=lib/x86_64-linux-gnu --enable-fpm --with-fpm-user=admin --with-fpm-group=admin --with-openssl --with-mysqli --with-gettext --with-curl --with-zip --with-gmp --enable-mbstring > "$work/configure.log" 2>&1
nice -n 10 make -j4 > "$work/make.log" 2>&1
sapi/cli/php -v
sapi/cli/php -m > "$work/modules-after.txt"
diff -u "$work/modules-before.txt" "$work/modules-after.txt"
sapi/fpm/php-fpm -t -y /usr/local/hestia/php/etc/php-fpm.conf > "$work/fpm-test.log" 2>&1
stage="$work/package"
make INSTALL_ROOT="$stage" install > "$work/stage-install.log" 2>&1
cp "$stage/usr/local/hestia/php/sbin/php-fpm" "$stage/usr/local/hestia/php/sbin/hestia-php"
# Preserve the live panel's configuration in this narrowly scoped security package.
cp /usr/local/hestia/php/lib/php.ini "$stage/usr/local/hestia/php/lib/php.ini"
cp /usr/local/hestia/php/etc/php-fpm.conf "$stage/usr/local/hestia/php/etc/php-fpm.conf"
printf '\n; PHP2Shell defense in depth; runtime is patched to 8.5.12\nsession.upload_progress.enabled = Off\n' >> "$stage/usr/local/hestia/php/lib/php.ini"
install -d -m 755 "$stage/DEBIAN"
{
 echo 'Package: hestia-php'
 echo 'Version: 8.5.12-1+debian13'
 echo 'Architecture: amd64'
 echo 'Maintainer: Nexvia Digital Studio'
 printf 'Depends: '; dpkg-query -W -f='${Depends}\n' hestia-php
 echo 'Description: Hestia PHP security rebuild for CVE-2026-109618 and CVE-2026-109619'
} > "$stage/DEBIAN/control"
dpkg-deb --build "$stage" "$work/hestia-php_8.5.12-1+debian13_amd64.deb"
echo BUILD_READY
