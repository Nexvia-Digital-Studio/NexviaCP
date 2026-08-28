<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "API_AUDIT";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user (admin only)
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action: set the per-key API rate limit
if (!empty($_POST["action"]) && $_POST["action"] === "set_limit") {
	verify_csrf($_POST);

	$v_limit = trim($_POST["v_limit"] ?? "");
	if ($v_limit === "" || !ctype_digit($v_limit) || (int) $v_limit < 1 || (int) $v_limit > 100000) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz limit. 1 ile 100000 arasında bir tam sayı girin."
			: "Invalid limit. Enter an integer between 1 and 100000.";
	} else {
		exec(HESTIA_CMD . "v-set-sys-api-rate-limit " . quoteshellarg($v_limit), $output, $return_var);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "API hız limiti güncellendi: " : "API rate limit updated: ") .
				$v_limit .
				($is_tr ? " istek / 60 saniye" : " requests / 60 seconds");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Hız limiti güncellenemedi: " : "Error updating rate limit: ") .
				implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/api-audit/");
	exit();
}

// Rate limit status card (active limit, window, active keys, requests)
exec(HESTIA_CMD . "v-list-sys-api-stats json", $output, $return_var);
$api_stats = json_decode(implode("", $output), true);
unset($output);
if (!is_array($api_stats)) {
	$api_stats = [];
}
$api_stats = array_merge([
	"limit" => 120,
	"limit_source" => "default",
	"window_seconds" => 60,
	"active_keys" => 0,
	"total_requests" => 0,
	"counter_dir" => "",
	"counter_files" => 0,
], $api_stats);

// Auth / API log ($HESTIA/log/auth.log via v-list-sys-audit-log): last N lines, filterable
$audit_lines = intval($_GET["lines"] ?? 100);
if ($audit_lines < 10 || $audit_lines > 500) {
	$audit_lines = 100;
}
$audit_filter = trim($_GET["filter"] ?? "");
$audit_status = $_GET["status"] ?? "all";
if (!in_array($audit_status, ["all", "success", "failed"], true)) {
	$audit_status = "all";
}

$auth_log_rows = [];
exec(
	HESTIA_CMD . "v-list-sys-audit-log " . quoteshellarg((string) $audit_lines) . " json",
	$output,
	$return_var,
);
$auth_log_data = json_decode(implode("", $output), true);
unset($output);
if (!empty($auth_log_data["lines"]) && is_array($auth_log_data["lines"])) {
	// Newest first (the CLI returns oldest -> newest)
	foreach (array_reverse($auth_log_data["lines"]) as $row) {
		$message = (string) ($row["MESSAGE"] ?? "");
		$is_failed = stripos($message, "failed") !== false;
		$is_success = stripos($message, "successfully") !== false;
		if ($audit_status === "failed" && !$is_failed) {
			continue;
		}
		if ($audit_status === "success" && !$is_success) {
			continue;
		}
		if ($audit_filter !== "" && stripos($message, $audit_filter) === false) {
			continue;
		}
		$auth_log_rows[] = [
			"DATE" => (string) ($row["DATE"] ?? ""),
			"TIME" => (string) ($row["TIME"] ?? ""),
			"MESSAGE" => $message,
			"STATUS" => $is_failed ? "failed" : ($is_success ? "success" : "info"),
		];
	}
}

// System activity log ($HESTIA/log/activity.log via v-list-user-log system)
$activity_filter = trim($_GET["activity_filter"] ?? "");
$activity_category = $_GET["category"] ?? "all";
$activity_categories = ["all", "api", "system", "auth", "security", "backup", "updates"];
if (!in_array($activity_category, $activity_categories, true)) {
	$activity_category = "all";
}

$activity_rows = [];
exec(HESTIA_CMD . "v-list-user-log system json", $output, $return_var);
$activity_data = json_decode(implode("", $output), true);
unset($output);
if (is_array($activity_data)) {
	// Newest first, same as the list/log page
	$activity_data = array_reverse($activity_data);
	foreach ($activity_data as $row) {
		$category = (string) ($row["CATEGORY"] ?? "");
		if ($activity_category !== "all" && strtolower($category) !== $activity_category) {
			continue;
		}
		$message = (string) ($row["MESSAGE"] ?? "");
		if ($activity_filter !== "" && stripos($message, $activity_filter) === false) {
			continue;
		}
		$activity_rows[] = [
			"DATE" => (string) ($row["DATE"] ?? ""),
			"TIME" => (string) ($row["TIME"] ?? ""),
			"LEVEL" => (string) ($row["LEVEL"] ?? ""),
			"CATEGORY" => $category,
			"MESSAGE" => $message,
		];
		if (count($activity_rows) >= $audit_lines) {
			break;
		}
	}
}

// Render page
render_page($user, $TAB, "list_api_audit");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
