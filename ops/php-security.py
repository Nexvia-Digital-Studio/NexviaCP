#!/usr/bin/env python3
"""Audit PHP2Shell runtimes; optionally upgrade installed Debian/Ubuntu packages.

No application-level configuration is a complete fix. Upstream advisory:
https://www.php.net/archive/2026.php#2026-10-21-1
"""
import argparse
import glob
import json
import os
import re
import shutil
import subprocess
import sys

MINIMUM = {(8, 2): (8, 2, 35), (8, 3): (8, 3, 36),
           (8, 4): (8, 4, 27), (8, 5): (8, 5, 12)}


def patched(version):
    # Only explicitly reviewed stable branches qualify; RC/dev builds do not.
    match = re.fullmatch(r"(\d+)\.(\d+)\.(\d+)(?:[-+~].*)?", version)
    if not match or re.search(r"(?:dev|alpha|beta|rc)", version, re.I):
        return False
    number = tuple(map(int, match.groups()))
    return number[:2] in MINIMUM and number >= MINIMUM[number[:2]]


def run(args, check=True):
    return subprocess.run(args, check=check, text=True, capture_output=True,
                          timeout=900)


def inspect(binary):
    result = run([binary, "-v"], check=False)
    match = re.search(r"^PHP\s+(\S+)", result.stdout + result.stderr, re.M)
    version = match.group(1) if match and result.returncode == 0 else "unknown"
    return {"binary": binary, "version": version, "patched": patched(version)}


def binaries():
    candidates = []
    for name in ("php", "php-cgi", "php-fpm"):
        path = shutil.which(name)
        if path:
            candidates.append(path)
    for pattern in ("/usr/bin/php[0-9]*", "/usr/bin/php-cgi[0-9]*",
                    "/usr/sbin/php-fpm*", "/usr/local/hestia/php/bin/php",
                    "/usr/local/hestia/php/sbin/php-fpm",
                    "/usr/local/hestia/php/sbin/hestia-php",
                    "/usr/local/bin/php", "/usr/local/sbin/php-fpm"):
        candidates.extend(glob.glob(pattern))
    return sorted({os.path.realpath(p) for p in candidates
                   if os.path.isfile(p) and os.access(p, os.X_OK)})


def packages():
    result = run(["dpkg-query", "-W", "-f=${binary:Package}\t${db:Status-Status}\n"])
    return sorted({line.split("\t")[0] for line in result.stdout.splitlines()
                   if line.endswith("\tinstalled") and re.fullmatch(
                       r"(?:php\d+\.\d+(?:-[a-z0-9-]+)?|libapache2-mod-php\d+\.\d+|hestia-php)(?::[a-z0-9]+)?",
                       line.split("\t")[0])})


def update():
    if os.geteuid() != 0 or not shutil.which("apt-get"):
        raise RuntimeError("--update requires root on Debian/Ubuntu")
    installed = packages()
    if not installed:
        raise RuntimeError("No installed PHP packages; rebuild the custom/container runtime")
    # Remember active services BEFORE apt maintainer scripts can restart them.
    units = run(["systemctl", "list-units", "--type=service", "--state=active",
                 "--no-legend", "--plain"]).stdout.splitlines()
    active = [line.split()[0] for line in units if line.split() and re.fullmatch(
        r"(?:php\d+\.\d+-fpm|apache2|hestia)\.service", line.split()[0])]
    print("Updating installed packages: " + ", ".join(installed), file=sys.stderr)
    run(["apt-get", "update"])
    # --only-upgrade preserves installed branches; never silently migrates 8.1.
    try:
        run(["apt-get", "install", "--only-upgrade", "--no-remove", "-y", *installed])
    finally:
        # Even a partial package upgrade must retire the old workers.
        failures = []
        for unit in active:
            try:
                run(["systemctl", "restart", unit])
                run(["systemctl", "is-active", "--quiet", unit])
            except (OSError, subprocess.SubprocessError):
                failures.append(unit)
        if failures:
            raise RuntimeError("Worker restart failed: " + ", ".join(failures))


def main():
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument("--binary", help="Check one explicit binary (e.g. CI CLI)")
    parser.add_argument("--update", action="store_true",
                        help="Upgrade installed OS PHP packages and restart active workers")
    args = parser.parse_args()
    if args.update and args.binary:
        parser.error("--update cannot be restricted to one binary")
    if args.update:
        update()
    runtimes = [inspect(args.binary)] if args.binary else [inspect(p) for p in binaries()]
    report = {"cves": ["CVE-2026-109618", "CVE-2026-109619"], "runtimes": runtimes,
              "scope": "Selected binary only" if args.binary else "Host binaries on disk",
              "notes": ["Lower vendor versions require documented fixes for BOTH CVEs; this tool conservatively fails them.",
                        "Containers, remote hosts, LiteSpeed and FrankenPHP require separate runtime checks and rebuilds.",
                        "Disk versions do not prove running worker versions. Restart all workers and verify live routes."]}
    print(json.dumps(report, indent=2))
    return 0 if runtimes and all(item["patched"] for item in runtimes) else 1


if __name__ == "__main__":
    try:
        sys.exit(main())
    except (OSError, RuntimeError, subprocess.SubprocessError) as error:
        # Do not print subprocess stdout/stderr: package hooks may contain secrets.
        print("PHP security check failed: " + type(error).__name__, file=sys.stderr)
        sys.exit(2)
