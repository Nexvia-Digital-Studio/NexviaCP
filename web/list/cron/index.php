<?php
$TAB = "CRON";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Data
if (is_admin_overview()) {
	$data = list_records_for_all_users("v-list-cron-jobs", true);
} else {
	exec(HESTIA_CMD . "v-list-cron-jobs $user json", $output, $return_var);
	$data = json_decode(implode("", $output), true);
}
if ($_SESSION["userSortOrder"] == "name") {
	ksort($data);
} else {
	$data = array_reverse($data, true);
}
unset($output);

// Render page
render_page($user, $TAB, "list_cron");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
