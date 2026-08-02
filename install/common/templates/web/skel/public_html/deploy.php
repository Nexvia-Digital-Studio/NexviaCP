<?php
/**
 * NexviaCP Git Auto-Deploy Webhook Receiver
 *
 * This file is automatically placed in every domain's public_html by
 * v-add-web-domain. GitHub/GitLab/Bitbucket sends a POST request here on
 * every push; we verify the HMAC-SHA256 signature with the per-domain
 * secret and then invoke the domain's deploy.sh (git pull + deps + reload).
 *
 * Configuration:
 *   - The secret below is replaced by v-add-web-domain with openssl rand.
 *   - The deploy target is the domain owner, looked up from the path.
 *
 * Security:
 *   - No signature -> 403.
 *   - Wrong signature -> 403.
 *   - deploy.sh runs as the domain owner (never root).
 */

// Per-domain secret (64 hex chars). Replaced at domain creation time.
$DEPLOY_SECRET = '%deploy_secret%';

// Failsafe: never execute if the secret was never personalized.
if ($DEPLOY_SECRET === '%deploy_secret%' || $DEPLOY_SECRET === '') {
    http_response_code(403);
    echo 'Deploy webhook not configured.';
    exit;
}

// Only accept POST.
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    echo 'Method not allowed.';
    exit;
}

// Read raw payload.
$payload = file_get_contents('php://input');
if ($payload === false || $payload === '') {
    http_response_code(400);
    echo 'Empty payload.';
    exit;
}

// Verify HMAC-SHA256 signature. Supports GitHub (X-Hub-Signature-256),
// and the generic sha256= prefix used by GitLab/Bitbucket.
$signatureHeader = '';
foreach (['HTTP_X_HUB_SIGNATURE_256', 'HTTP_X_GITLAB_TOKEN', 'HTTP_X_HUB_SIGNATURE'] as $header) {
    if (!empty($_SERVER[$header])) {
        $signatureHeader = $_SERVER[$header];
        break;
    }
}

// GitLab uses a plain shared secret token (X-Gitlab-Token) instead of HMAC.
if (isset($_SERVER['HTTP_X_GITLAB_TOKEN'])) {
    if (!hash_equals($DEPLOY_SECRET, $_SERVER['HTTP_X_GITLAB_TOKEN'])) {
        http_response_code(403);
        echo 'Invalid token.';
        exit;
    }
} else {
    // HMAC path (GitHub / Bitbucket / generic).
    if ($signatureHeader === '') {
        http_response_code(403);
        echo 'Missing signature header.';
        exit;
    }
    // Strip optional "sha256=" prefix.
    $received = $signatureHeader;
    if (strpos($received, 'sha256=') === 0) {
        $received = substr($received, 7);
    } elseif (strpos($received, 'sha1=') === 0) {
        // Legacy sha1 fallback (older GitHub setups).
        $expectedSha1 = hash_hmac('sha1', $payload, $DEPLOY_SECRET);
        if (!hash_equals($expectedSha1, substr($received, 5))) {
            http_response_code(403);
            echo 'Invalid signature.';
            exit;
        }
        $received = null; // mark as already verified
    }

    if ($received !== null) {
        $expected = hash_hmac('sha256', $payload, $DEPLOY_SECRET);
        if (!hash_equals($expected, $received)) {
            http_response_code(403);
            echo 'Invalid signature.';
            exit;
        }
    }
}

// Resolve the domain owner from the file path:
//   /home/<user>/web/<domain>/public_html/deploy.php
$docRoot = realpath(__DIR__);
$pathParts = explode('/', trim($docRoot, '/'));
// Expected: home/<user>/web/<domain>/public_html
if (count($pathParts) < 5 || $pathParts[0] !== 'home' || $pathParts[2] !== 'web') {
    http_response_code(500);
    echo 'Cannot resolve domain owner.';
    exit;
}
$domainUser = $pathParts[1];
$domainName = $pathParts[3];

// Path to the per-domain deploy script (sibling of public_html).
$deployScript = '/home/' . $domainUser . '/web/' . $domainName . '/deploy.sh';
if (!is_file($deployScript) || !is_executable($deployScript)) {
    http_response_code(500);
    echo 'deploy.sh missing or not executable.';
    exit;
}

// Run deploy.sh as the domain owner (never root, never web user).
// sudoers allows hestiaweb to run /usr/local/hestia/bin/* only, so we use
// the runuser binary which does not require sudo.
$cmd = sprintf(
    'runuser -u %s -- %s 2>&1',
    escapeshellarg($domainUser),
    escapeshellarg($deployScript)
);

// Optional: log the deployment to a per-domain deploy log.
$logFile = '/home/' . $domainUser . '/web/' . $domainName . '/logs/deploy.log';
$ts = date('Y-m-d H:i:s');
$cmd .= sprintf(' && echo "[%s] deploy OK" >> %s || echo "[%s] deploy FAILED ($?)" >> %s',
    $ts, escapeshellarg($logFile), $ts, escapeshellarg($logFile));

// Execute (non-blocking shell). We return 200 immediately; deploy runs in bg.
exec($cmd . ' > /dev/null 2>&1 &', $output, $returnVar);

http_response_code(202);
echo 'Deploy triggered.' . PHP_EOL;
