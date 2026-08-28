<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "HEALTH";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user (admin only)
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION["language"] ?? "") === "tr" || ($_SESSION["LANGUAGE"] ?? "") === "tr");

// Validate a heartbeat URL against the same strict rules the CLI enforces:
// https:// host[:port]/path only - no query string, no fragment, no userinfo
function is_valid_heartbeat_url(string $url): bool {
	if (strlen($url) > 2048 || strpos($url, "..") !== false) {
		return false;
	}
	if (filter_var($url, FILTER_VALIDATE_URL) === false) {
		return false;
	}
	return (bool)preg_match(
		'#^https://[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]*[a-zA-Z0-9])?)*(:[0-9]{1,5})?(/[a-zA-Z0-9._~-]+)*/?$#',
		$url
	);
}

// Action: save / update the heartbeat configuration
if (!empty($_POST["action"]) && $_POST["action"] === "save_heartbeat") {
	verify_csrf($_POST);

	$v_url = trim($_POST["v_url"] ?? "");
	$v_minutes = trim($_POST["v_minutes"] ?? "5");
	if ($v_minutes === "") {
		$v_minutes = "5";
	}

	if (!is_valid_heartbeat_url($v_url)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz URL. Sadece katı https://host[:port]/path biçimi kabul edilir (sorgu dizesi ve fragment yok)."
			: "Invalid URL. Only the strict https://host[:port]/path form is accepted (no query string or fragment).";
	} elseif (
		!preg_match('/^[0-9]{1,4}$/', $v_minutes) ||
		(int)$v_minutes < 1 ||
		(int)$v_minutes > 1440
	) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz dakika aralığı. 1-1440 arası bir tam sayı girin."
			: "Invalid interval. Enter an integer between 1 and 1440 minutes.";
	} else {
		exec(
			HESTIA_CMD . "v-set-sys-heartbeat " .
				quoteshellarg($v_url) . " " .
				quoteshellarg($v_minutes),
			$output,
			$return_var
		);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Heartbeat izleyici kaydedildi: " . implode(" ", $output)
				: "Heartbeat watchdog saved: " . implode(" ", $output);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Heartbeat kaydedilirken hata oluştu: " : "Error saving heartbeat: ") . implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/health/");
	exit();
}

// Action: delete the heartbeat configuration
if (!empty($_POST["action"]) && $_POST["action"] === "delete_heartbeat") {
	verify_csrf($_POST);

	exec(HESTIA_CMD . "v-set-sys-heartbeat " . quoteshellarg("delete"), $output, $return_var);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = $is_tr
			? "Heartbeat izleyici kaldırıldı (konfigürasyon, cron ve durum dosyası silindi)."
			: "Heartbeat watchdog removed (config, cron entry and state file deleted).";
	} else {
		$_SESSION["error_msg"] = ($is_tr ? "Heartbeat silinirken hata oluştu: " : "Error deleting heartbeat: ") . implode(" ", $output);
	}
	unset($output);
	header("Location: /list/health/");
	exit();
}

// Fetch heartbeat status (config + state; read via the CLI as root)
exec(HESTIA_CMD . "v-ping-sys-heartbeat " . quoteshellarg("status"), $output, $return_var);
$heartbeat_status = json_decode(implode("", $output), true);
unset($output);
if (!is_array($heartbeat_status)) {
	$heartbeat_status = [
		"configured" => false,
		"url" => "",
		"interval" => 5,
		"cron_present" => false,
		"cron_file" => "/etc/cron.d/nexvia-heartbeat",
		"state" => [
			"last_ping" => "",
			"last_status" => "",
			"fail_count" => 0,
			"notified" => false,
		],
	];
}

// Fetch certificate report (also refreshes $HESTIA/data/health/certs-report.json)
exec(HESTIA_CMD . "v-check-sys-certs " . quoteshellarg("json"), $output, $return_var);
$certs_data = json_decode(implode("", $output), true);
unset($output);
if (!is_array($certs_data)) {
	$certs_data = ["summary" => [], "certs" => []];
}

// Render page
render_page($user, $TAB, "list_health");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
