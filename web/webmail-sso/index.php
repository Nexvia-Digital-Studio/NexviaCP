<?php
// Panel-side webmail SSO bridge: only for authenticated panel sessions.
// Generates a passwordless HMAC token for a mail domain the session user
// owns (admins may open any domain — owner is resolved server-side).
require_once dirname(__DIR__) . '/inc/main.php';

$domain = isset($_GET['domain']) ? strtolower(trim($_GET['domain'])) : '';
$account = isset($_GET['account']) ? trim($_GET['account']) : '';

$webmail_alias = 'webmail';
if (!empty($_SESSION['WEBMAIL_ALIAS'])) {
	$webmail_alias = $_SESSION['WEBMAIL_ALIAS'];
}

if (!preg_match('/^([a-z0-9-]+\.)+[a-z]{2,}$/', $domain)) {
	header('Location: /list/mail/');
	exit;
}

$fallback = 'https://' . $webmail_alias . '.' . $domain . '/';

// loginas sessions quote $user in main.php — strip for identity use
$user_plain = (string) $user;
if (preg_match("/^'(.*)'\$/s", $user_plain, $m)) {
	$user_plain = $m[1];
}
if ($user_plain === '') {
	header('Location: ' . $fallback);
	exit;
}

$cmd = HESTIA_CMD . 'v-gen-webmail-sso-token ' . escapeshellarg($user_plain) . ' ' . escapeshellarg($domain);
if ($account !== '' && preg_match('/^[a-z0-9_.-]+$/i', $account)) {
	$cmd .= ' ' . escapeshellarg($account);
}

exec($cmd, $out, $return_var);

if ($return_var == 0 && !empty($out[0]) && preg_match('/^[A-Za-z0-9_.-]+$/', $out[0])) {
	header('Location: https://' . $webmail_alias . '.' . $domain . '/?_nxvsso=' . urlencode($out[0]));
} else {
	// multiple accounts without an explicit pick, or any error: normal webmail
	header('Location: ' . $fallback);
}
exit;
