<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "BACKUP";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$conf_file = "/usr/local/hestia/conf/cloud-backup.conf";
if (!file_exists($conf_file) && defined('HESTIA')) {
	$conf_file = HESTIA . "/conf/cloud-backup.conf";
}

// Security gate: the cloud backup credentials are global (server-wide), so
// only administrators may read or change them.
if (($_SESSION['userContext'] ?? '') !== 'admin') {
	header("Location: /list/user/");
	exit();
}

// Load Cloud Backup Settings (before the actions so the save handler can
// fall back to the currently stored secrets when the form leaves them blank)
$cloud_settings = [
	"PROVIDER" => "r2",
	"ENDPOINT" => "",
	"BUCKET" => "nexvia-backups",
	"ACCESS_KEY" => "",
	"SECRET_KEY" => "",
	"ACCOUNT_ID" => "",
	"ENCRYPTION_ENABLED" => "yes",
	"ENCRYPTION_KEY" => "",
	"RETENTION_COUNT" => "14",
	"AUTO_SYNC" => "daily",
	"STATUS" => "unconfigured",
	"LAST_SYNC" => ""
];

if (file_exists($conf_file)) {
	$conf_lines = file($conf_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($conf_lines as $cline) {
		if (strpos($cline, '=') !== false && !preg_match('/^\s*#/', $cline)) {
			list($k, $v) = explode('=', $cline, 2);
			$k = trim($k);
			$v = trim($v, " \t\n\r\0\x0B\"'");
			if (isset($cloud_settings[$k])) {
				$cloud_settings[$k] = $v;
			}
		}
	}
}
unset($conf_lines, $cline, $k, $v);

// 1. Action: Save Cloud Backup Settings
if (!empty($_POST["save_settings"])) {
	if (verify_csrf($_POST)) {
		$provider = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["provider"] ?? "r2");
		$endpoint = trim($_POST["endpoint"] ?? "");
		$bucket = trim($_POST["bucket"] ?? "nexvia-backups");
		$access_key = trim($_POST["access_key"] ?? "");
		$secret_key = trim($_POST["secret_key"] ?? "");
		$account_id = trim($_POST["account_id"] ?? "");
		$encryption_enabled = (!empty($_POST["encryption_enabled"]) && $_POST["encryption_enabled"] === "yes") ? "yes" : "no";
		$encryption_key = trim($_POST["encryption_key"] ?? "");
		$retention_count = intval($_POST["retention_count"] ?? 14);
		if ($retention_count < 1) $retention_count = 14;
		$auto_sync = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST["auto_sync"] ?? "daily");

		// Secret fields are write-only in the UI: an empty POST value means
		// "keep the currently stored secret" instead of wiping it.
		if ($secret_key === "") {
			$secret_key = $cloud_settings["SECRET_KEY"] ?? "";
		}
		if ($encryption_key === "") {
			$encryption_key = $cloud_settings["ENCRYPTION_KEY"] ?? "";
		}

		// If Cloudflare R2 and endpoint is empty, construct from account_id
		if ($provider === "r2" && !empty($account_id) && empty($endpoint)) {
			$endpoint = "https://" . $account_id . ".r2.cloudflarestorage.com";
		}

		$config_content = "# NexviaCP Cloud Backup Configuration\n";
		$config_content .= "PROVIDER='" . addslashes($provider) . "'\n";
		$config_content .= "ENDPOINT='" . addslashes($endpoint) . "'\n";
		$config_content .= "BUCKET='" . addslashes($bucket) . "'\n";
		$config_content .= "ACCESS_KEY='" . addslashes($access_key) . "'\n";
		$config_content .= "SECRET_KEY='" . addslashes($secret_key) . "'\n";
		$config_content .= "ACCOUNT_ID='" . addslashes($account_id) . "'\n";
		$config_content .= "ENCRYPTION_ENABLED='" . addslashes($encryption_enabled) . "'\n";
		$config_content .= "ENCRYPTION_KEY='" . addslashes($encryption_key) . "'\n";
		$config_content .= "RETENTION_COUNT='" . addslashes($retention_count) . "'\n";
		$config_content .= "AUTO_SYNC='" . addslashes($auto_sync) . "'\n";
		$config_content .= "STATUS='configured'\n";

		// Keep existing LAST_SYNC if available (date-shaped values only)
		if (preg_match("/LAST_SYNC='([0-9]{4}-[0-9]{2}-[0-9]{2}[ T][0-9:]+[0-9+:-]*)'/", (string)@file_get_contents($conf_file), $m)) {
			$config_content .= "LAST_SYNC='" . $m[1] . "'\n";
		}

		@file_put_contents($conf_file, $config_content);
		@chmod($conf_file, 0600);

		$_SESSION["ok_msg"] = $is_tr
			? "Bulut yedekleme ayarları başarıyla kaydedildi."
			: _("Cloud backup configuration has been saved successfully.");

		header("Location: /list/cloud-backup/");
		exit();
	}
}

// 2. Action: Test Connection
if (isset($_GET["test_connection"]) || isset($_POST["test_connection"])) {
	if (verify_csrf($_REQUEST)) {
		exec(HESTIA_CMD . "v-backup-cloud-sync " . quoteshellarg($user) . " test", $test_output, $ret_code);
		if ($ret_code === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Bağlantı Başarılı: Bulut depolama alanı doğrulandı ve yazma yetkisi onaylandı."
				: _("Connection Success: Cloud storage authentication and write permissions verified.");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Bağlantı Hatası: " : _("Connection Error: ")) . implode(" ", $test_output);
		}
		header("Location: /list/cloud-backup/");
		exit();
	}
}

// 3. Action: Manual Sync
if (isset($_GET["sync_now"]) || isset($_POST["sync_now"])) {
	if (verify_csrf($_REQUEST)) {
		exec(HESTIA_CMD . "v-backup-cloud-sync " . quoteshellarg($user) . " sync", $sync_output, $ret_code);
		if ($ret_code === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Bulut senkronizasyonu tamamlandı: Tüm yerel yedekler şifrelenerek buluta aktarıldı."
				: _("Cloud synchronization completed: Local backups encrypted and uploaded to cloud.");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Senkronizasyon Hatası: " : _("Sync Error: ")) . implode(" ", $sync_output);
		}
		header("Location: /list/cloud-backup/");
		exit();
	}
}

// 4. Action: Backup & Sync
if (isset($_POST["backup_and_sync"])) {
	if (verify_csrf($_POST)) {
		exec(HESTIA_CMD . "v-backup-cloud-sync " . quoteshellarg($user) . " backup-and-sync", $sync_output, $ret_code);
		if ($ret_code === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Yeni yedek alındı ve güvenli şekilde bulut depolamaya aktarıldı."
				: _("New backup generated and securely synchronized to cloud storage.");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Yedekleme Hatası: " : _("Backup Error: ")) . implode(" ", $sync_output);
		}
		header("Location: /list/cloud-backup/");
		exit();
	}
}

// 5. Action: Restore Cloud Backup
if (!empty($_GET["restore_file"])) {
	if (verify_csrf($_GET)) {
		$v_file = quoteshellarg($_GET["restore_file"]);
		exec(HESTIA_CMD . "v-backup-cloud-sync " . quoteshellarg($user) . " restore " . $v_file, $res_output, $ret_code);
		if ($ret_code === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Bulut yedeği indirildi ve şifresi çözülerek yerel sisteme aktarıldı: " . htmlspecialchars($_GET["restore_file"])
				: _("Cloud backup downloaded and decrypted to local storage: ") . htmlspecialchars($_GET["restore_file"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Geri Yükleme Hatası: " : _("Restore Error: ")) . implode(" ", $res_output);
		}
		header("Location: /list/cloud-backup/");
		exit();
	}
}

// Fetch Remote Cloud Backups
exec(HESTIA_CMD . "v-backup-cloud-sync " . quoteshellarg($user) . " list json", $list_output, $ret_val);
$cloud_backups = json_decode(implode("\n", $list_output), true) ?: [];

// Fetch Local Backups count
exec(HESTIA_CMD . "v-list-user-backups " . quoteshellarg($user) . " json", $local_b_out, $ret_val);
$local_backups = json_decode(implode("", $local_b_out), true) ?: [];

// Secrets are write-only: hand the template "is set" flags only and strip the
// raw values so they can never end up in the rendered HTML.
$secret_set = !empty($cloud_settings["SECRET_KEY"]);
$enc_set = !empty($cloud_settings["ENCRYPTION_KEY"]);
$cloud_settings["SECRET_KEY"] = "";
$cloud_settings["ENCRYPTION_KEY"] = "";

// Render page
render_page($user, $TAB, "list_cloud_backup");
