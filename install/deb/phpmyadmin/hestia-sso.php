<?php

/* Hestia way to enable support for SSO to PHPmyAdmin */
/* To install please run v-add-sys-pma-sso */

/* Following keys will get replaced when calling v-add-sys-pma-sso */
define("PHPMYADMIN_KEY", "%PHPMYADMIN_KEY%");
define("API_HOST_NAME", "%API_HOST_NAME%");
define("API_HESTIA_PORT", "%API_HESTIA_PORT%");
define("API_KEY", "%API_KEY%");

class Hestia_API {
	/** @var string */
	public $hostname;
	/** @var string */
	public $key;
	/** @var string */
	public $pma_key;
	/** @var string */
	private $api_url;
	public function __construct() {
		$this->hostname = "https://" . API_HOST_NAME . ":" . API_HESTIA_PORT . "/api/";
		$this->key = API_KEY;
		$this->pma_key = PHPMYADMIN_KEY;
	}

	/* Creates curl request */
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

	/* Creates an new temp user in mysql */
	public function create_temp_user($database, $user, $host) {
		$post_request = [
			"hash" => $this->key,
			"returncode" => "no",
			"cmd" => "v-add-database-temp-user",
			"arg1" => $user,
			"arg2" => $database,
			"arg3" => "mysql",
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

	/* Delete an new temp user in mysql */
	public function delete_temp_user($database, $user, $dbuser, $host) {
		$post_request = [
			"hash" => $this->key,
			"returncode" => "yes",
			"cmd" => "v-delete-database-temp-user",
			"arg1" => $user,
			"arg2" => $database,
			"arg3" => $dbuser,
			"arg4" => "mysql",
			"arg5" => $host,
		];
		$request = $this->request($post_request);
		if (is_numeric($request) && $request == 0) {
			return true;
		} else {
			return false;
		}
	}

	public function get_user_ip() {
		// Must return the same single real client IP as the panel's
		// get_real_user_ip() (web/inc/helpers.php): list_db.php signs the
		// SSO token with that value, so a combined "proxy|client" string
		// never verifies behind Cloudflare.
		$ip = "";
		if (!empty($_SERVER["REMOTE_ADDR"]) && filter_var($_SERVER["REMOTE_ADDR"], FILTER_VALIDATE_IP)) {
			$ip = $_SERVER["REMOTE_ADDR"];
		}
		// Behind Cloudflare CF-Connecting-IP carries the real client IP.
		// Verify-only: a spoofed header can only lock the caller out, the
		// token is always signed with the panel-side IP.
		if (
			!empty($_SERVER["HTTP_CF_CONNECTING_IP"]) &&
			filter_var($_SERVER["HTTP_CF_CONNECTING_IP"], FILTER_VALIDATE_IP) &&
			$ip !== ""
		) {
			$ip = $_SERVER["HTTP_CF_CONNECTING_IP"];
		}
		// Handling IPv4-mapped IPv6 address
		if (strpos($ip, ":") === 0 && strpos($ip, ".") > 0) {
			$ip = substr($ip, strrpos($ip, ":") + 1);
		}
		return $ip;
	}
}

function verify_token($database, $user, $ip, $time, $token) {
	if (!password_verify($database . $user . $ip . $time . PHPMYADMIN_KEY, $token)) {
		if (
			!password_verify(
				$database . $user . $_SERVER["SERVER_ADDR"] . "|" . $ip . $time . PHPMYADMIN_KEY,
				$token,
			)
		) {
			trigger_error(
				"Access denied: There is a security token mismatch " . $time,
				E_USER_WARNING,
			);
			session_invalid();
		}
	}
	return;
}
/* Need to have cookie visible from parent directory */
session_set_cookie_params(0, "/", "", true, true);
/* Create signon session */
$session_name = "SignonSession";
session_name($session_name);
@session_start();

function session_invalid() {
	global $session_name;
	//delete all current sessions
	session_destroy();
	setcookie($session_name, null, -1, "/");
	header("Location: " . dirname($_SERVER["PHP_SELF"]) . "/index.php");
	die();
}

$api = new Hestia_API();
if (!empty($_GET)) {
	if (isset($_GET["logout"])) {
		$api->delete_temp_user(
			$_SESSION["HESTIA_sso_database"],
			$_SESSION["HESTIA_sso_user"],
			$_SESSION["PMA_single_signon_user"],
			$_SESSION["HESTIA_sso_host"],
		);
		//remove session
		session_invalid();
	} else {
		if (isset($_GET["user"]) && isset($_GET["hestia_token"])) {
			$database = $_GET["database"];
			$user = $_GET["user"];
			$host = "localhost";
			$token = $_GET["hestia_token"];
			if (is_numeric($_GET["exp"])) {
				$time = $_GET["exp"];
			} else {
				$time = 0;
			}

			if ($time + 60 > time()) {
				//note: Possible issues with cloudflare due to ip obfuscation
				$ip = $api->get_user_ip();
				verify_token($database, $user, $ip, $time, $token);
				$id = session_id();
				//create a new temp user
				$data = $api->create_temp_user($database, $user, $host);
				if ($data) {
					$_SESSION["PMA_single_signon_user"] = $data->login->user;
					$_SESSION["PMA_single_signon_password"] = $data->login->password;
					$_SESSION["PMA_single_signon_host"] = $host;
					//save database / username to be used for sending logout notification.
					$_SESSION["HESTIA_sso_user"] = $user;
					$_SESSION["HESTIA_sso_database"] = $database;
					$_SESSION["HESTIA_sso_host"] = $host;

					@session_write_close();
					setcookie($session_name, $id, 0, "/");
					header("Location: " . dirname($_SERVER["PHP_SELF"]) . "/index.php");
					die();
				} else {
					session_invalid();
				}
			} else {
				trigger_error(
					"Link has been expired: System time: " .
						time() .
						" / Time provided in link: " .
						$time,
					E_USER_WARNING,
				);
				session_invalid();
			}
		}
	}
} else {
	session_invalid();
}
