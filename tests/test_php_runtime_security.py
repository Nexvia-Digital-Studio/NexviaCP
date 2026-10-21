"""Boundary and updater safety tests for the PHP core security rollout."""
import importlib.util
import subprocess
import unittest
from pathlib import Path
from unittest.mock import patch

SPEC = importlib.util.spec_from_file_location(
    "php_security", Path(__file__).resolve().parents[1] / "ops/php-security.py")
security = importlib.util.module_from_spec(SPEC)
SPEC.loader.exec_module(security)


class RuntimeSecurityTests(unittest.TestCase):
    def test_release_boundaries(self):
        for branch, minimum in security.MINIMUM.items():
            with self.subTest(branch=branch):
                self.assertTrue(security.patched(".".join(map(str, minimum))))
                self.assertTrue(security.patched(".".join(map(str, minimum)) + "-1+ubuntu"))
                old = (*branch, minimum[2] - 1)
                self.assertFalse(security.patched(".".join(map(str, old))))

    def test_unknown_eol_and_prerelease_fail_closed(self):
        for version in ("unknown", "8.1.99", "7.4.99", "8.6.0RC4", "8.5.12RC1",
                        "8.5.12-dev", "9.0.0", "8.6.0"):
            self.assertFalse(security.patched(version), version)

    @patch.object(security, "run")
    def test_fpm_version_banner(self, run):
        run.return_value = subprocess.CompletedProcess([], 0, "PHP 8.5.12 (fpm-fcgi)\n", "")
        self.assertTrue(security.inspect("/usr/sbin/php-fpm8.5")["patched"])
        run.return_value = subprocess.CompletedProcess([], 1, "PHP 8.5.12", "broken")
        self.assertFalse(security.inspect("/usr/sbin/php-fpm8.5")["patched"])

    @patch.object(security, "run")
    def test_package_selection_preserves_installed_branches(self, run):
        run.return_value = subprocess.CompletedProcess([], 0,
            "php8.1-fpm\tinstalled\nphp8.5-common\tinstalled\n"
            "libapache2-mod-php8.3:amd64\tinstalled\nhestia-php\tinstalled\n"
            "php8.2-cli\tconfig-files\nnginx\tinstalled\nphp-malicious\tinstalled\n", "")
        self.assertEqual(security.packages(), ["hestia-php", "libapache2-mod-php8.3:amd64",
                                               "php8.1-fpm", "php8.5-common"])

    @patch.object(security.os, "geteuid", return_value=1000)
    @patch.object(security, "run")
    def test_update_requires_root_before_mutation(self, run, uid):
        with self.assertRaises(RuntimeError):
            security.update()
        run.assert_not_called()

    @patch.object(security.os, "geteuid", return_value=0)
    @patch.object(security.shutil, "which", return_value="/usr/bin/apt-get")
    @patch.object(security, "packages", return_value=["php8.3-fpm", "php8.3-cli"])
    @patch.object(security, "run")
    def test_upgrade_restarts_all_active_workers_only(self, run, packages, which, uid):
        run.return_value = subprocess.CompletedProcess([], 0,
            "php8.3-fpm.service loaded active running PHP\n"
            "apache2.service loaded active running Apache\n"
            "hestia.service loaded active running Panel\n"
            "nginx.service loaded active running Nginx\n", "")
        security.update()
        calls = [call.args[0] for call in run.call_args_list]
        self.assertIn(["apt-get", "install", "--only-upgrade", "--no-remove", "-y",
                       "php8.3-fpm", "php8.3-cli"], calls)
        for unit in ("php8.3-fpm.service", "apache2.service", "hestia.service"):
            self.assertIn(["systemctl", "restart", unit], calls)
        self.assertNotIn(["systemctl", "restart", "nginx.service"], calls)


if __name__ == "__main__":
    unittest.main()
