<?php
// GitHub Webhook Auto-Deploy Listener
// Responds to GitHub push / release events to automatically update connected domains.

header("Content-Type: application/json");

$payload_raw = file_get_contents("php://input");
if (!empty($_POST["payload"])) {
	$payload_raw = $_POST["payload"];
}

if (empty($payload_raw)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Empty payload"]);
	exit();
}

// 1. Signature Verification (HMAC-SHA256) if GITHUB_WEBHOOK_SECRET is set
$headers = function_exists('getallheaders') ? getallheaders() : [];
$signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

$webhook_secret = "";
if (file_exists("/usr/local/hestia/conf/vault.conf")) {
	$vault_lines = file("/usr/local/hestia/conf/vault.conf", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
	foreach ($vault_lines as $vline) {
		if (strpos($vline, 'GITHUB_WEBHOOK_SECRET=') === 0) {
			list(, $webhook_secret) = explode('=', $vline, 2);
			$webhook_secret = trim($webhook_secret, " \t\n\r\0\x0B\"'");
			break;
		}
	}
}

if (!empty($webhook_secret)) {
	if (empty($signature) || strpos($signature, 'sha256=') !== 0) {
		http_response_code(401);
		echo json_encode(["status" => "error", "message" => "Missing or invalid signature header"]);
		exit();
	}
	$expected_signature = 'sha256=' . hash_hmac('sha256', $payload_raw, $webhook_secret);
	if (!hash_equals($expected_signature, $signature)) {
		http_response_code(403);
		echo json_encode(["status" => "error", "message" => "Signature verification failed"]);
		exit();
	}
}

// 2. Payload Parsing & Sanitization
$payload = json_decode($payload_raw, true);
$repo_name = $payload["repository"]["name"] ?? "";

if (empty($repo_name) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $repo_name)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Invalid or missing repository name in payload"]);
	exit();
}

// 3. Rate Limit / Cooldown per repository (prevents flood DoS attacks)
$lock_dir = "/tmp/nexvia_webhook_locks";
if (!is_dir($lock_dir)) {
	@mkdir($lock_dir, 0700, true);
}
$lock_file = $lock_dir . "/lock_" . md5($repo_name);

if (file_exists($lock_file) && (time() - filemtime($lock_file) < 15)) {
	http_response_code(429);
	echo json_encode(["status" => "error", "message" => "Sync already in progress or cooldown active for this repository"]);
	exit();
}
@touch($lock_file);

// 4. Log and Execute Background Sync
$log_msg = date("[Y-m-d H:i:s]") . " GitHub Webhook triggered for repository: " . $repo_name . "\n";
@file_put_contents("/var/log/hestia/github-webhook.log", $log_msg, FILE_APPEND);

$cmd = "/usr/local/hestia/bin/v-sync-github-repos " . escapeshellarg($repo_name) . " >> /var/log/hestia/github-webhook.log 2>&1 &";
exec($cmd);

echo json_encode([
	"status" => "success",
	"message" => "Webhook received. Deployment triggered for repository: " . $repo_name,
	"timestamp" => date("c")
]);
