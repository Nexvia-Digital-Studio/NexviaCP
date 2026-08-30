<?php
// GitHub Webhook Auto-Deploy Listener
// Responds to GitHub push / release events to automatically update connected domains.
//
// Security: HMAC-SHA256 verification is MANDATORY (fail-closed). The secret
// is fetched through the sudo-whitelisted v-list-webhook-secret helper
// because this endpoint runs as the unprivileged panel user. Replayed
// deliveries are rejected via the X-GitHub-Delivery id cache.

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

// 1. Signature Verification (HMAC-SHA256) — always enforced
$headers = function_exists('getallheaders') ? getallheaders() : [];
$signature = $headers['X-Hub-Signature-256'] ?? $headers['x-hub-signature-256'] ?? ($_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '');

$webhook_secret = "";
exec("/usr/bin/sudo /usr/local/hestia/bin/v-list-webhook-secret 2>/dev/null", $secret_out, $secret_rc);
if ($secret_rc === 0 && !empty($secret_out[0])) {
	$webhook_secret = trim($secret_out[0]);
}

if (empty($webhook_secret)) {
	// Fail closed: without a configured secret no request is trusted.
	http_response_code(403);
	echo json_encode([
		"status" => "error",
		"message" => "Webhook secret is not configured. Run: v-set-sys-global-vault GITHUB_WEBHOOK_SECRET <secret> (or set it via v-change-sys-config-value), then retry.",
	]);
	exit();
}

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
unset($webhook_secret, $secret_out);

// 2. Payload Parsing & Sanitization
$payload = json_decode($payload_raw, true);
$repo_name = $payload["repository"]["name"] ?? "";

if (empty($repo_name) || !preg_match('/^[a-zA-Z0-9_\-\.]+$/', $repo_name)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Invalid or missing repository name in payload"]);
	exit();
}

// 3. Replay protection: reject already-seen delivery ids (24h window)
$delivery_id = $_SERVER["HTTP_X_GITHUB_DELIVERY"] ?? "";
if (empty($delivery_id) || !preg_match('/^[a-zA-Z0-9\-]{8,64}$/', $delivery_id)) {
	http_response_code(400);
	echo json_encode(["status" => "error", "message" => "Missing or invalid X-GitHub-Delivery header"]);
	exit();
}

$cache_dir = is_writable("/var/log/hestia") ? "/var/log/hestia/webhook_deliveries" : (sys_get_temp_dir() . "/hestia_webhook_deliveries");
if (!is_dir($cache_dir)) {
	@mkdir($cache_dir, 0700, true);
}
$marker = $cache_dir . "/" . hash('sha256', $delivery_id);
if (file_exists($marker)) {
	http_response_code(429);
	echo json_encode(["status" => "error", "message" => "Duplicate delivery rejected"]);
	exit();
}
@touch($marker);
// Opportunistic prune of markers older than 24h
$now = time();
foreach (glob($cache_dir . "/*") ?: [] as $old) {
	if (is_file($old) && !is_link($old) && ($now - filemtime($old)) > 86400) {
		@unlink($old);
	}
}

// 4. Rate Limit / Cooldown per repository (prevents flood DoS attacks)
$lock_dir = is_writable("/var/log/hestia") ? "/var/log/hestia/webhook_locks" : (sys_get_temp_dir() . "/hestia_webhook_locks");
if (!is_dir($lock_dir)) {
	@mkdir($lock_dir, 0700, true);
}
$lock_file = $lock_dir . "/lock_" . hash('sha256', $repo_name);

if (file_exists($lock_file) && (time() - filemtime($lock_file) < 15)) {
	http_response_code(429);
	echo json_encode(["status" => "error", "message" => "Sync already in progress or cooldown active for this repository"]);
	exit();
}
@touch($lock_file);

// 5. Log and Execute Background Sync (sudo: bin scripts require root)
$log_msg = date("[Y-m-d H:i:s]") . " GitHub Webhook triggered for repository: " . $repo_name . "\n";
@file_put_contents("/var/log/hestia/github-webhook.log", $log_msg, FILE_APPEND);

$cmd = "/usr/bin/sudo /usr/local/hestia/bin/v-sync-github-repos " . escapeshellarg($repo_name) . " >> /var/log/hestia/github-webhook.log 2>&1 &";
exec($cmd);

echo json_encode([
	"status" => "success",
	"message" => "Webhook received. Deployment triggered for repository: " . $repo_name,
	"timestamp" => date("c")
]);
