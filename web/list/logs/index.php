<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "LOG";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Fetch user's web domains
exec(HESTIA_CMD . "v-list-web-domains " . $user . " json", $domains_output, $return_var);
$user_domains = json_decode(implode("", $domains_output), true) ?: [];

// Get list of domain names
$domain_names = array_keys($user_domains);
$active_domain = $_GET["domain"] ?? ($domain_names[0] ?? "");

// If user requested a domain they do not own (and not admin), redirect
if ($_SESSION["userContext"] !== "admin" && !empty($active_domain) && !isset($user_domains[$active_domain])) {
	header("Location: /list/web/");
	exit();
}

$log_type = $_GET["type"] ?? "access";
if (!in_array($log_type, ["access", "error", "app", "php-error", "php"])) {
	$log_type = "access";
}

$lines = intval($_GET["lines"] ?? 100);
if ($lines < 10 || $lines > 2000) {
	$lines = 100;
}

// 1. AJAX / Streaming Endpoint
if (isset($_GET["ajax"]) || isset($_GET["stream"])) {
	header("Content-Type: application/json; charset=utf-8");
	header("Cache-Control: no-cache, no-store, must-revalidate");

	if (empty($active_domain)) {
		echo json_encode([
			"status" => "error",
			"message" => $is_tr ? "Seçili domain bulunamadı." : "No web domain selected.",
			"lines" => []
		]);
		exit();
	}

	$v_user = $user;
	$v_domain = quoteshellarg($active_domain);
	$v_type = quoteshellarg($log_type);
	$v_lines = quoteshellarg($lines);

	exec(HESTIA_CMD . "v-stream-domain-logs " . $v_user . " " . $v_domain . " " . $v_type . " " . $v_lines . " no json", $raw_out, $ret_val);
	$json_str = implode("\n", $raw_out);
	$parsed_logs = json_decode($json_str, true) ?: [];

	echo json_encode([
		"status" => "success",
		"domain" => $active_domain,
		"type" => $log_type,
		"count" => count($parsed_logs),
		"timestamp" => date("c"),
		"lines" => $parsed_logs
	]);
	exit();
}

// 2. Download Raw Log Action
if (isset($_GET["download"])) {
	if (verify_csrf($_GET) && !empty($active_domain)) {
		$v_user = $user;
		$v_domain = quoteshellarg($active_domain);
		$v_type = quoteshellarg($log_type);
		$v_lines = quoteshellarg(2000);

		exec(HESTIA_CMD . "v-stream-domain-logs " . $v_user . " " . $v_domain . " " . $v_type . " " . $v_lines . " no text", $raw_lines, $ret_val);

		header("Content-Type: text/plain; charset=utf-8");
		header("Content-Disposition: attachment; filename=\"" . $active_domain . "_" . $log_type . "_log_" . date("Ymd_His") . ".txt\"");
		echo implode("\n", $raw_lines);
		exit();
	}
}

// 3. Initial Log Fetch for Page Render
$initial_logs = [];
if (!empty($active_domain)) {
	$v_user = $user;
	$v_domain = quoteshellarg($active_domain);
	$v_type = quoteshellarg($log_type);
	$v_lines = quoteshellarg($lines);

	exec(HESTIA_CMD . "v-stream-domain-logs " . $v_user . " " . $v_domain . " " . $v_type . " " . $v_lines . " no json", $raw_out, $ret_val);
	$initial_logs = json_decode(implode("\n", $raw_out), true) ?: [];
}

// Render the Live Logs Console page
render_page($user, $TAB, "list_logs");
