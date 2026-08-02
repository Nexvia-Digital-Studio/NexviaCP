<?php
/* NexviaCP SSO bridge for phpPgAdmin. */
/* Install with: v-add-sys-pga-sso */

/* Placeholders replaced by v-add-sys-pga-sso */
define("PGA_SSO_KEY", "%PGA_SSO_KEY%");
define("API_HOST_NAME", "%API_HOST_NAME%");
define("API_HESTIA_PORT", "%API_HESTIA_PORT%");
define("API_KEY", "%API_KEY%");

class Hestia_PGA_API {
	public $hostname;
	public $key;
	public $pga_key;
	private $api_url;
	public function __construct() {
		$this->hostname = "https://" . API_HOST_NAME . ":" . API_HESTIA_PORT . "/api/";
		$this->key = API_KEY;
		$this->pga_key = PGA_SSO_KEY;
	}

	public function request($postvars) {
		$postdata = http_build_query($postvars);
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, $this->hostname);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $postdata);
		$answer = curl_exec($curl);
		return $answer;
	}

	public function create_temp_user($database, $user, $host) {
		$post_request = [
			"hash" => $this->key,
			"returncode" => "no",
			"cmd" => "v-add-database-temp-user",
			"arg1" => $user,
			"arg2" => $database,
			"arg3" => "pgsql",
			"arg4" => $host,
		];
		$request = $this->request($post_request);
		$json = json_decode($request);
		if (json_last_error() == JSON_ERROR_NONE) {
			return $json;
		} else {
			trigger_error("Unable to connect over API please check api connection", E_USER_WARNING);
			return false;
		}
	}

	public function delete_temp_user($database, $user, $dbuser, $host) {
		$post_request = [
			"hash" => $this->key,
			"returncode" => "yes",
			"cmd" => "v-delete-database-temp-user",
			"arg1" => $user,
			"arg2" => $database,
			"arg3" => $dbuser,
			"arg4" => "pgsql",
			"arg5" => $host,
		];
		$request = $this->request($post_request);
		return (is_numeric($request) && $request == 0);
	}
}

/* Token verification — same HMAC scheme as the phpMyAdmin bridge. */
function verify_token($database, $user, $ip, $time, $token) {
	if (!password_verify($database . $user . $ip . $time . PGA_SSO_KEY, $token)) {
		if (
			!password_verify(
				$database . $user . $_SERVER["SERVER_ADDR"] . "|" . $ip . $time . PGA_SSO_KEY,
				$token,
			)
		) {
			return false;
		}
	}
	if ($time < time() - 60) {
		return false;
	}
	if ($time > time() + 60) {
		return false;
	}
	return true;
}

session_start();

if (isset($_GET["logout"])) {
	session_destroy();
	header("Location: /");
	exit;
}

/* Collect request parameters. */
$database = $_GET["database"] ?? "";
$user = $_GET["user"] ?? "";
$time = $_GET["exp"] ?? 0;
$token = $_GET["token"] ?? "";
$ip = $_SERVER["REMOTE_ADDR"] ?? "";

if (empty($database) || empty($user) || empty($token)) {
	http_response_code(400);
	echo "Missing parameters.";
	exit;
}

if (!verify_token($database, $user, $ip, $time, $token)) {
	http_response_code(403);
	echo "Invalid or expired token.";
	exit;
}

/* Create the temp PG role via the Hestia API. */
$api = new Hestia_PGA_API();
$host = "localhost";
$data = $api->create_temp_user($database, $user, $host);
if (!$data || !isset($data->login)) {
	http_response_code(500);
	echo "Unable to create temporary database user.";
	exit;
}

$dbuser = $data->login->user;
$dbpass = $data->login->password;

/* Store credentials in session for the auto-login bridge page. */
$_SESSION["pga_sso_user"] = $dbuser;
$_SESSION["pga_sso_pass"] = $dbpass;
$_SESSION["pga_sso_db"] = $database;
$_SESSION["pga_sso_owner"] = $user;

/*
 * phpPgAdmin does not support a native "signon" auth mode like phpMyAdmin.
 * We render a self-submitting form that POSTs the temp credentials straight
 * to phpPgAdmin's login handler (redirect.php), giving the user a one-click
 * passwordless experience while keeping credentials short-lived (60 min TTL).
 */
$loginUrl = "/phppgadmin/redirect.php";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<title>NexviaCP phpPgAdmin SSO</title>
<style>
body{font-family:system-ui,sans-serif;background:#f5f7fb;color:#2d3748;display:flex;align-items:center;justify-content:center;height:100vh;margin:0}
.card{background:#fff;padding:2.5rem 3rem;border-radius:12px;box-shadow:0 10px 40px rgba(0,0,0,.08);text-align:center;max-width:420px}
.spinner{width:34px;height:34px;border:3px solid #e2e8f0;border-top-color:#3b82f6;border-radius:50%;animation:spin 1s linear infinite;margin:0 auto 1rem}
@keyframes spin{to{transform:rotate(360deg)}}
</style>
</head>
<body>
<div class="card">
<div class="spinner"></div>
<h3>phpPgAdmin açılıyor…</h3>
<p style="color:#718096;font-size:.9rem;margin-top:.5rem">Geçici veritabanı kullanıcısı oluşturuldu. Yönlendiriliyorsunuz.</p>
</div>
<form id="pga-sso-form" method="post" action="<?= htmlspecialchars($loginUrl) ?>">
<input type="hidden" name="loginServer" value="0">
<input type="hidden" name="loginUsername" value="<?= htmlspecialchars($dbuser) ?>">
<input type="hidden" name="loginPassword" value="<?= htmlspecialchars($dbpass) ?>">
<input type="hidden" name="loginSubmit" value="Login">
</form>
<script>document.getElementById('pga-sso-form').submit();</script>
</body>
</html>
