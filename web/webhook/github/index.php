<?php
// GitHub Webhook Auto-Deploy Listener
// Responds to GitHub push / release events to automatically update connected domains.

header("Content-Type: application/json");

$payload_raw = file_get_contents("php://input");
if (!empty($_POST["payload"])) {
	$payload_raw = $_POST["payload"];
}

$payload = json_decode($payload_raw, true);
$repo_name = $payload["repository"]["name"] ?? "";

if (empty($repo_name)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Repository name not found in payload"]);
	exit();
}

// Log webhook trigger
$log_msg = date("[Y-m-d H:i:s]") . " GitHub Webhook triggered for repository: " . $repo_name . "\n";
@file_put_contents("/var/log/hestia/github-webhook.log", $log_msg, FILE_APPEND);

// Execute sync
$cmd = "/usr/local/hestia/bin/v-sync-github-repos " . escapeshellarg($repo_name) . " >> /var/log/hestia/github-webhook.log 2>&1 &";
exec($cmd);

echo json_encode([
	"status" => "success",
	"message" => "Webhook received. Deployment triggered for repository: " . $repo_name,
	"timestamp" => date("c")
]);
