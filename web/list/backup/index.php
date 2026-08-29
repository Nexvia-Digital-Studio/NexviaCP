<?php
use function Hestiacp\quoteshellarg\quoteshellarg;
$TAB = "BACKUP";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Admin overview: target user comes from the link (?user=...) so an
// administrator can open records of any account directly.
if (($_SESSION["userContext"] ?? "") === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
}


// Data & Render page
if (empty($_GET["backup"])) {
	if (is_admin_overview()) {
		$data = list_records_for_all_users("v-list-user-backups");
	} else {
		exec(HESTIA_CMD . "v-list-user-backups $user json", $output, $return_var);
		$data = json_decode(implode("", $output), true);
	}
	if ($_SESSION["userSortOrder"] == "name") {
		ksort($data);
	} else {
		$data = array_reverse($data, true);
	}
	unset($output);
	render_page($user, $TAB, "list_backup");
} else {
	exec(
		HESTIA_CMD . "v-list-user-backup $user " . quoteshellarg($_GET["backup"]) . " json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	$data = array_reverse($data, true);
	unset($output);

	render_page($user, $TAB, "list_backup_detail");
}

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
