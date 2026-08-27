<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "ANOMALIES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Get filter parameters
$domain_filter = !empty($_GET["domain"]) ? $_GET["domain"] : "all";
$period_filter = !empty($_GET["period"]) ? $_GET["period"] : "7d";

// Validate period
if (!in_array($period_filter, ["24h", "7d", "30d", "90d", "all"])) {
	$period_filter = "7d";
}

// Sanitize domain filter
$domain_filter = preg_replace('/[^a-zA-Z0-9.\-_]/', '', $domain_filter);

// Fetch anomaly data
exec(HESTIA_CMD . "v-list-domain-anomalies " . quoteshellarg($domain_filter) . " " . quoteshellarg($period_filter) . " json", $output, $return_var);
$anomaly_data = json_decode(implode("", $output), true);
unset($output);

if (empty($anomaly_data)) {
	$anomaly_data = [
		"anomalies" => [],
		"metrics" => [],
		"domains" => [],
		"summary" => [
			"total" => 0,
			"critical" => 0,
			"warning" => 0,
			"unresolved" => 0,
			"most_affected" => null
		]
	];
}

// Handle AJAX: manual metric collection trigger
if (!empty($_POST["action"]) && $_POST["action"] === "collect_metrics") {
	verify_csrf($_POST);
	$target = !empty($_POST["domain"]) ? preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_POST["domain"]) : "all";
	if ($target === "" || $target === "." || $target === ".." || str_contains($target, "..")) {
		$target = "all";
	}
	exec(HESTIA_CMD . "v-collect-domain-metrics " . quoteshellarg($target), $col_out, $col_rc);
	exec(HESTIA_CMD . "v-detect-domain-anomalies " . quoteshellarg($target), $det_out, $det_rc);
	header("Content-Type: application/json");
	echo json_encode(["ok" => ($col_rc === 0), "message" => implode("\n", array_merge($col_out, $det_out))]);
	exit();
}

// Handle AJAX: get metric timeline for a specific domain
if (!empty($_POST["action"]) && $_POST["action"] === "get_timeline") {
	verify_csrf($_POST);
	$target = !empty($_POST["domain"]) ? preg_replace('/[^a-zA-Z0-9.\-_]/', '', $_POST["domain"]) : "all";
	if ($target === "" || $target === "." || $target === ".." || str_contains($target, "..")) {
		$target = "all";
	}
	exec(HESTIA_CMD . "v-list-domain-anomalies " . quoteshellarg($target) . " 30d json", $tl_out, $tl_rc);
	header("Content-Type: application/json");
	echo implode("", $tl_out);
	exit();
}

// Render page
render_page($user, $TAB, "list_anomalies");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
