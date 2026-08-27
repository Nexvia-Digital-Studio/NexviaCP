<?php

$TAB = "RRD";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Data
exec(HESTIA_CMD . "v-list-sys-rrd json", $output, $return_var);
$data = json_decode(implode("", $output), true);
unset($output);

/*
if (empty($_GET["period"])) {
	$period = "day";
} elseif (!in_array($_GET["period"], ["day", "week", "month", "year"])) {
	$period = "day";
} else {
	$period = $_GET["period"];
}
*/
if (empty($_GET["period"])) {
	$period = "daily";
} elseif (
	!in_array($_GET["period"], [
		"daily",
		"weekly",
		"monthly",
		"yearly",
		"biennially",
		"triennially",
	])
) {
	$period = "daily";
} else {
	$period = $_GET["period"];
}

// Live System Telemetry
$sys_load = sys_getloadavg() ?: [0.1, 0.1, 0.1];
$cpu_cores = (int) shell_exec("nproc 2>/dev/null || grep -c ^processor /proc/cpuinfo 2>/dev/null") ?: 1;

// Memory calculation
$mem_info = [];
if (file_exists("/proc/meminfo")) {
	$m_lines = file("/proc/meminfo") ?: [];
	foreach ($m_lines as $ml) {
		if (preg_match('/^(\w+):\s+(\d+)/', $ml, $matches)) {
			$mem_info[$matches[1]] = (int)$matches[2];
		}
	}
}
$mem_total_mb = round(($mem_info["MemTotal"] ?? 1024 * 1024) / 1024);
$mem_avail_mb = round(($mem_info["MemAvailable"] ?? ($mem_info["MemFree"] ?? 512 * 1024)) / 1024);
$mem_used_mb = max(0, $mem_total_mb - $mem_avail_mb);
$mem_percent = $mem_total_mb > 0 ? round(($mem_used_mb / $mem_total_mb) * 100, 1) : 0;

// Disk calculation
$disk_total_bytes = @disk_total_space("/") ?: (50 * 1024 * 1024 * 1024);
$disk_free_bytes = @disk_free_space("/") ?: (35 * 1024 * 1024 * 1024);
$disk_used_bytes = max(0, $disk_total_bytes - $disk_free_bytes);
$disk_percent = $disk_total_bytes > 0 ? round(($disk_used_bytes / $disk_total_bytes) * 100, 1) : 0;
$disk_total_gb = round($disk_total_bytes / 1024 / 1024 / 1024, 1);
$disk_used_gb = round($disk_used_bytes / 1024 / 1024 / 1024, 1);

// Uptime calculation
$raw_uptime = @file_get_contents("/proc/uptime");
$uptime_seconds = (int) ($raw_uptime ? explode(" ", $raw_uptime)[0] : 3600);
$uptime_days = floor($uptime_seconds / 86400);
$uptime_hours = floor(($uptime_seconds % 86400) / 3600);
$uptime_mins = floor(($uptime_seconds % 3600) / 60);

$live_metrics = [
	"load" => $sys_load,
	"cores" => $cpu_cores,
	"load_status" => ($sys_load[0] < $cpu_cores * 0.7) ? "ideal" : (($sys_load[0] < $cpu_cores) ? "moderate" : "high"),
	"mem_total_mb" => $mem_total_mb,
	"mem_used_mb" => $mem_used_mb,
	"mem_avail_mb" => $mem_avail_mb,
	"mem_percent" => $mem_percent,
	"disk_total_gb" => $disk_total_gb,
	"disk_used_gb" => $disk_used_gb,
	"disk_percent" => $disk_percent,
	"uptime_str" => ($uptime_days > 0 ? $uptime_days . "g " : "") . $uptime_hours . "s " . $uptime_mins . "dk"
];

// Render page
render_page($user, $TAB, "list_rrd");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
