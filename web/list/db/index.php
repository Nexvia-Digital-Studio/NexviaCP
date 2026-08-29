<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "DB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Sync & Auto-Discover Unmapped Databases (admin only — adopting
// databases into an account must never be triggerable by regular users)
if (!empty($_POST["action_sync_db"])) {
	verify_csrf($_POST);
	if ($_SESSION["userContext"] != "admin") {
		$_SESSION["error_msg"] = $is_tr ? "Bu işlem yalnızca yönetici tarafından yapılabilir." : _("Access denied: administrator only.");
		header("Location: /list/db/");
		exit();
	}
	exec(HESTIA_CMD . "v-sync-sys-databases " . $user, $s_out, $s_code);
	if ($s_code === 0) {
		$_SESSION["ok_msg"] = $is_tr ? "Veritabanları tarandı ve tüm aktif veritabanları sisteme eşitlendi." : _("Databases scanned and all active databases synchronized successfully.");
	} else {
		$err_text = trim(implode(" ", $s_out));
		if (stripos($err_text, "doesn't exist") !== false || stripos($err_text, "bulunamadı") !== false) {
			$_SESSION["error_msg"] = $is_tr ? "Hesap henüz oluşturulmamış veya sistemde bulunamadı ($user)." : sprintf(_("User account '%s' does not exist yet."), $user);
		} else {
			$_SESSION["error_msg"] = $err_text ?: ($is_tr ? "Veritabanları taranırken bir hata oluştu." : _("An error occurred while scanning databases."));
		}
	}
	header("Location: /list/db/");
	exit();
}

// Action 2: DB Studio AJAX Explorer Endpoint (POST only + CSRF; the backend
// script additionally verifies the database belongs to the requesting user)
if (!empty($_POST["action_explore_db"])) {
	verify_csrf($_POST);

	$target_db = $_POST["db_name"] ?? "";
	$target_table = $_POST["table_name"] ?? "";
	$target_sql = $_POST["custom_sql"] ?? "";

	// Defense in depth: reject anything the backend sanitizer would mangle
	if (!preg_match('/^[a-zA-Z0-9_-]*$/', $target_db) || !preg_match('/^[a-zA-Z0-9_-]*$/', $target_table)) {
		header('Content-Type: application/json; charset=utf-8');
		echo json_encode(["status" => "error", "error" => "Invalid database or table name."]);
		exit();
	}

	header('Content-Type: application/json; charset=utf-8');
	exec(
		HESTIA_CMD . "v-explore-sys-database " . $user . " " . quoteshellarg($target_db) . " " . quoteshellarg($target_table) . " " . quoteshellarg($target_sql),
		$exp_out,
		$exp_code
	);
	echo implode("\n", $exp_out);
	exit();
}

// Database auto-discovery is intentionally NOT auto-run anymore: silently
// adopting unmapped server databases into whoever opens the page was a
// cross-tenant takeover vector. Admins can run it explicitly via the button.
exec(HESTIA_CMD . "v-list-databases " . $user . " json", $output, $return_var);
$data = json_decode(implode("", $output), true) ?: [];

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
