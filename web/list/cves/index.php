<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "CVES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Handle AJAX: rescan (optionally refreshing the apt cache first).
// With update_cache this runs "apt-get update" server-side and may take
// up to ~2 minutes, so it is only ever triggered through this POST action.
if (!empty($_POST["action"]) && $_POST["action"] === "rescan") {
	verify_csrf($_POST);
	$update_flag = !empty($_POST["update_cache"]) ? "--update" : "";
	exec(
		HESTIA_CMD . "v-check-sys-cves " . quoteshellarg($update_flag) . " json",
		$output,
		$return_var,
	);
	$report = json_decode(implode("", $output), true);
	unset($output);
	header("Content-Type: application/json");
	echo json_encode([
		"ok" => $return_var === 0,
		"report" => is_array($report) ? $report : null,
	]);
	exit();
}

// Fetch CVE report data
exec(HESTIA_CMD . "v-check-sys-cves json", $output, $return_var);
$cve_data = json_decode(implode("", $output), true);
unset($output);

if (empty($cve_data) || !is_array($cve_data)) {
	$cve_data = [
		"generated_at" => "",
		"apt_update_ran" => false,
		"security_updates" => 0,
		"total_updates" => 0,
		"security_packages" => [],
		"upgradable" => [],
		"services" => [],
		"error_notes" => [],
	];
}

// Render page
render_page($user, $TAB, "list_cves");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
