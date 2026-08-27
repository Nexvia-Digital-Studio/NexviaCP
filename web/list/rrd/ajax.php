<?php
use function Hestiacp\quoteshellarg\quoteshellarg;
// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$requestPayload = json_decode(file_get_contents("php://input"), true);

$allowedPeriods = ["daily", "weekly", "monthly", "yearly", "biennially", "triennially"];

if (!empty($requestPayload["period"]) && in_array($requestPayload["period"], $allowedPeriods)) {
	$period = $requestPayload["period"];
} else {
	$period = "daily";
}

if (!empty($requestPayload["service"])) {
	$service = $requestPayload["service"];
} else {
	$service = "la";
}

// Data
exec(
	HESTIA_CMD . "v-export-rrd " . quoteshellarg($service) . " " . quoteshellarg($period),
	$output,
	$return_var,
);

if ($return_var != 0) {
	http_response_code(500);
	exit("Error fetching RRD data");
}

$serviceUnits = [
	"la" => "Yük",
	"mem" => "MB",
	"apache2" => "Bağlantı",
	"nginx" => "Bağlantı",
	"mail" => "Kuyruk",
	"ftp" => "Bağlantı",
	"ssh" => "Bağlantı",
];

if (preg_match("/^net_/", $service)) {
	$serviceUnits[$service] = "KB/s";
}
if (preg_match("/^pgsql_/", $service)) {
	$serviceUnits[$service] = "Sorgu/sn";
}
if (preg_match("/^mysql_/", $service)) {
	$serviceUnits[$service] = "Sorgu/sn";
}

$data = json_decode(implode("", $output), true);
$data["service"] = $service;
$data["unit"] = $serviceUnits[$service] ?? null;
echo json_encode($data);
