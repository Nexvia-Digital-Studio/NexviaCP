<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "DB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Sync & Auto-Discover Unmapped Databases
if (!empty($_POST["action_sync_db"])) {
	verify_csrf($_POST);
	exec(HESTIA_CMD . "v-sync-sys-databases " . quoteshellarg($user), $s_out, $s_code);
	if ($s_code === 0) {
		$_SESSION["ok_msg"] = $is_tr ? "Veritabanları tarandı ve tüm aktif veritabanları sisteme eşitlendi." : _("Databases scanned and all active databases synchronized successfully.");
	} else {
		$_SESSION["error_msg"] = implode(" ", $s_out);
	}
	header("Location: /list/db/");
	exit();
}

// Action 2: DB Studio AJAX Explorer Endpoint
if (!empty($_POST["action_explore_db"]) || !empty($_GET["action_explore_db"])) {
	$req = !empty($_POST["action_explore_db"]) ? $_POST : $_GET;
	
	// CSRF check
	if (!empty($_POST["action_explore_db"])) {
		verify_csrf($_POST);
	}
	
	$target_db = quoteshellarg($req["db_name"] ?? "");
	$target_table = quoteshellarg($req["table_name"] ?? "");
	$target_sql = quoteshellarg($req["custom_sql"] ?? "");

	header('Content-Type: application/json; charset=utf-8');
	exec(HESTIA_CMD . "v-explore-sys-database " . quoteshellarg($user) . " " . $target_db . " " . $target_table . " " . $target_sql, $exp_out, $exp_code);
	echo implode("\n", $exp_out);
	exit();
}

// Auto-run discovery if user has 0 databases
exec(HESTIA_CMD . "v-list-databases " . quoteshellarg($user) . " json", $output, $return_var);
$data = json_decode(implode("", $output), true) ?: [];

if (empty($data)) {
	exec(HESTIA_CMD . "v-sync-sys-databases " . quoteshellarg($user), $sync_out, $sync_code);
	unset($output);
	exec(HESTIA_CMD . "v-list-databases " . quoteshellarg($user) . " json", $output, $return_var);
	$data = json_decode(implode("", $output), true) ?: [];
}

if ($_SESSION["userSortOrder"] == "name") {
	ksort($data);
} else {
	$data = array_reverse($data, true);
}
unset($output);

// Render page
render_page($user, $TAB, "list_db");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
