<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

try {
	require_once "../inc/vendor/autoload.php";
} catch (Throwable $ex) {
	$errstr =
		"Unable to load required libraries. Please run v-add-sys-dependencies in command line. Error: " .
		$ex->getMessage();
	trigger_error($errstr);
	echo $errstr;
	exit(1);
}

//die("Error: Disabled");
define("HESTIA_DIR_BIN", "/usr/local/hestia/bin/");
define("HESTIA_CMD", "/usr/bin/sudo /usr/local/hestia/bin/");

include $_SERVER["DOCUMENT_ROOT"] . "/inc/helpers.php";

/**
 * Displays the error message, checks the proper code and saves a log if needed.
 *
 * @param int $exit_code
 * @param string $message
 * @param bool $add_log
 * @param string $user
 * @return void
 */
function api_error($exit_code, $message, $hst_return, bool $add_log = false, $user = "system") {
	$message = trim(is_array($message) ? implode("\n", $message) : $message);

	// Add log
	if ($add_log) {
		$v_real_user_ip = get_real_user_ip();
		hst_add_history_log("[$v_real_user_ip] $message", "API", "Error", $user);
	}

	// Print the message with http_code and exit_code
	$http_code = $exit_code >= 100 ? $exit_code : exit_code_to_http_code($exit_code);
	header("Hestia-Exit-Code: $exit_code");
	http_response_code($http_code);
	if ($hst_return == "code") {
		echo $exit_code;
	} else {
		echo !preg_match("/^Error:/", $message) ? "Error: $message" : $message;
	}
	exit();
}

/**
 * Per-key API rate limiter using a fixed 60 second window.
 *
 * Counter files are kept in a dedicated 0700 subdirectory of
 * sys_get_temp_dir() (writable by hestiaweb, unlike $HESTIA/data/keys)
 * and are named after a SHA-256 hash of the identity, so the key itself
 * never appears in a file name. The subdirectory isolation plus a
 * symlink guard keep other same-user file writes (e.g. the
 * v-make-tmp-file API command) from pre-creating or hijacking a counter. Every counter
 * update is atomic (flock). The limiter fails open: when the temp
 * directory or the counter file is unavailable the request is allowed
 * through and the reason is recorded via error_log(), so a broken limiter
 * can never take the API down. The limit is read from
 * /usr/local/hestia/data/api-rate-limit (mode 644, set with
 * v-set-sys-api-rate-limit); when unreadable the default of 120 is used.
 *
 * @param string $identity Stable per-key identity (access key id, key file
 *                         name or legacy username); never written to disk
 * @param string $hst_return Return format ("code" or "data")
 * @return void
 */
function api_rate_limit(string $identity, string $hst_return) {
	$limit_file = "/usr/local/hestia/data/api-rate-limit";
	$window = 60;
	$limit = 120; // default when the limit file is missing or unreadable
	if (is_readable($limit_file)) {
		$raw_limit = trim(@file_get_contents($limit_file));
		if (ctype_digit($raw_limit) && (int) $raw_limit >= 1) {
			$limit = (int) $raw_limit;
		}
	}

	$tmp_dir = sys_get_temp_dir();
	if (!is_dir($tmp_dir) || !is_writable($tmp_dir)) {
		error_log("hestia api rate limit: temp directory not writable, failing open");
		return;
	}

	// Counters live in a dedicated 0700 subdirectory so files sprayed into
	// the temp directory root by other means (e.g. the v-make-tmp-file API
	// command) can never collide with, or pre-create, a counter.
	$counter_dir = $tmp_dir . "/hestia-api-rl";
	if (!is_dir($counter_dir)) {
		@mkdir($counter_dir, 0700);
		if (is_dir($counter_dir)) {
			@chmod($counter_dir, 0700);
		}
	}
	if (!is_dir($counter_dir) || !is_writable($counter_dir)) {
		error_log("hestia api rate limit: counter directory unavailable, failing open");
		return;
	}

	$counter_file = $counter_dir . "/" . hash("sha256", $identity) . ".cnt";
	$now = time();

	// Never follow a symlinked counter file (same-user tampering):
	// recreate it as a fresh regular file instead.
	if (@is_link($counter_file)) {
		error_log("hestia api rate limit: counter file is a symlink, recreating");
		@unlink($counter_file);
	}

	$fp = @fopen($counter_file, "c+");
	if (!$fp) {
		error_log("hestia api rate limit: unable to open counter file, failing open");
		return;
	}

	if (!flock($fp, LOCK_EX)) {
		fclose($fp);
		error_log("hestia api rate limit: unable to lock counter file, failing open");
		return;
	}

	// Read the "window_start count" pair written by previous requests
	$contents = trim((string) stream_get_contents($fp));
	$parts = explode(" ", $contents);
	$window_start =
		isset($parts[0]) && ctype_digit($parts[0]) && (int) $parts[0] > 0 ? (int) $parts[0] : 0;
	$count = isset($parts[1]) && ctype_digit($parts[1]) ? (int) $parts[1] : 0;

	// A window start in the future means a tampered counter: clamp it to
	// now so a forged file can never lock an identity for longer than one
	// regular window
	if ($window_start > $now) {
		$window_start = $now;
	}

	// Start a new window when the previous one has expired
	if ($window_start === 0 || $now - $window_start >= $window) {
		$window_start = $now;
		$count = 0;
	}
	$count++;

	// Persist the counter before deciding, so rejected requests keep
	// counting towards the current window
	ftruncate($fp, 0);
	rewind($fp);
	fwrite($fp, $window_start . " " . $count . "\n");
	fflush($fp);
	flock($fp, LOCK_UN);
	fclose($fp);

	if ($count > $limit) {
		$retry_after = max(1, (int) ceil($window_start + $window - $now));
		header("Retry-After: $retry_after");
		api_error(429, "rate limit exceeded", $hst_return);
	}
}

/**
 * Legacy connection format using hash or user and password.
 *
 * @param array{user: string?, pass: string?, hash?: string, cmd: string, arg1?: string, arg2?: string, arg3?: string, arg4?: string, arg5?: string, arg6?: string, arg7?: string, arg8?: string, arg9?: string, arg10?: string, arg11?: string, arg12?: string, arg13?: string, returncode?: string} $request_data
 * @return void
 * @return void
 */
function api_legacy(array $request_data) {
	$hst_return = ($request_data["returncode"] ?? "no") === "yes" ? "code" : "data";
	exec(HESTIA_CMD . "v-list-sys-config json", $output, $return_var);
	$settings = json_decode(implode("", $output), true);
	unset($output);

	if ($settings["config"]["API"] != "yes") {
		echo "Error: API has been disabled";
		api_error(E_DISABLED, "Error: API Disabled", $hst_return);
	}

	if ($settings["config"]["API_ALLOWED_IP"] != "allow-all") {
		$ip_list = explode(",", $settings["config"]["API_ALLOWED_IP"]);
		$ip_list[] = "";
		if (!in_array(get_real_user_ip(), $ip_list)) {
			api_error(E_FORBIDDEN, "Error: IP is not allowed to connect with API", $hst_return);
		}
	}

	//This exists, so native JSON can be used without the repeating the code twice, so future code changes are easier and don't need to be replicated twice
	// Authentication
	if (empty($request_data["hash"])) {
		exec(HESTIA_CMD . "v-list-sys-config json", $output, $return_var);
		$data = json_decode(implode("", $output), true);
		$root_user = $data["config"]["ROOT_USER"];

		if ($request_data["user"] != "$root_user") {
			api_error(E_FORBIDDEN, "Error: authentication failed", $hst_return);
		}
		$password = $request_data["password"];
		if (!isset($password)) {
			api_error(E_PASSWORD, "Error: authentication failed", $hst_return);
		}
		$v_ip = quoteshellarg(get_real_user_ip());
		$user = quoteshellarg($root_user);
		unset($output);
		exec(HESTIA_CMD . "v-get-user-salt " . $user . " " . $v_ip . " json", $output, $return_var);
		$pam = json_decode(implode("", $output), true);
		$salt = $pam[$root_user]["SALT"];
		$method = $pam[$root_user]["METHOD"];

		if ($method == "md5") {
			$hash = crypt($password, '$1$' . $salt . '$');
		}
		if ($method == "sha-512") {
			$hash = crypt($password, '$6$rounds=5000$' . $salt . '$');
			$hash = str_replace('$rounds=5000', "", $hash);
		}
		if ($method == "yescrypt") {
			$fp = tmpfile();
			$v_password = stream_get_meta_data($fp)["uri"];
			fwrite($fp, $password . "\n");
			unset($output);
			exec(
				HESTIA_CMD .
					"v-check-user-password " .
					quoteshellarg($root_user) .
					" " .
					quoteshellarg($v_password) .
					" " .
					$v_ip .
					" yes",
				$output,
				$return_var,
			);
			$hash = $output[0];
			fclose($fp);
			unset($output, $fp, $v_password);
		}
		if ($method == "des") {
			$hash = crypt($password, $salt);
		}

		// Send hash via tmp file
		$v_hash = exec("mktemp -p /tmp");
		$fp = fopen($v_hash, "w");
		fwrite($fp, $hash . "\n");
		fclose($fp);

		// Check user hash
		exec(
			HESTIA_CMD . "v-check-user-hash " . $user . " " . $v_hash . " " . $v_ip,
			$output,
			$return_var,
		);
		unset($output);

		// Remove tmp file
		unlink($v_hash);

		// Check API answer
		if ($return_var > 0) {
			api_error(E_PASSWORD, "Error: authentication failed", $hst_return);
		}

		// Rate limit the authenticated legacy user before any command is prepared
		api_rate_limit("user:" . $root_user, $hst_return);
	} else {
		$key = "/usr/local/hestia/data/keys/" . basename($request_data["hash"]);
		$v_ip = quoteshellarg(get_real_user_ip());
		exec(
			HESTIA_CMD . "v-check-api-key " . quoteshellarg($key) . " " . $v_ip,
			$output,
			$return_var,
		);
		unset($output);
		// Check API answer
		if ($return_var > 0) {
			api_error(E_PASSWORD, "Error: authentication failed", $hst_return);
		}

		// Rate limit the authenticated legacy key before any command is prepared
		api_rate_limit("key:" . basename($request_data["hash"]), $hst_return);
	}

	$hst_cmd = trim($request_data["cmd"] ?? "");
	$hst_cmd_args = [];
	for ($i = 1; $i <= 13; $i++) {
		if (isset($request_data["arg{$i}"])) {
			$hst_cmd_args["arg{$i}"] = trim($request_data["arg{$i}"]);
		}
	}

	if (empty($hst_cmd)) {
		api_error(E_INVALID, "Command not provided", $hst_return);
	} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $hst_cmd)) {
		api_error(E_INVALID, "$hst_cmd command invalid", $hst_return);
	}

	// Check command
	if ($hst_cmd == "v-make-tmp-file") {
		// Used in DNS Cluster
		$fp = fopen("/tmp/" . basename(escapeshellcmd($hst_cmd_args["arg2"])), "w");
		fwrite($fp, $hst_cmd_args["arg1"] . "\n");
		fclose($fp);
		$return_var = 0;
	} else {
		// Prepare command
		$cmdquery = HESTIA_CMD . escapeshellcmd($hst_cmd);

		// Prepare arguments
		foreach ($hst_cmd_args as $cmd_arg) {
			$cmdquery .= " " . quoteshellarg($cmd_arg);
		}

		// Run cmd query
		exec($cmdquery, $output, $cmd_exit_code);
	}

	if (!empty($hst_return) && $hst_return == "code") {
		echo $cmd_exit_code;
	} else {
		if ($return_var == 0 && empty($output)) {
			echo "OK";
		} else {
			echo implode("\n", $output) . "\n";
		}
	}

	exit();
}

/**
 * Connection using access key.
 *
 * @param array{access_key: string, secret_key: string, cmd: string, arg1?: string, arg2?: string, arg3?: string, arg4?: string, arg5?: string, arg6?: string, arg7?: string, arg8?: string, arg9?: string, arg10?: string, arg11?: string, arg12?: string, arg13?: string, returncode?: string} $request_data
 * @return void
 */
function api_connection(array $request_data) {
	$hst_return = ($request_data["returncode"] ?? "no") === "yes" ? "code" : "data";
	$v_real_user_ip = get_real_user_ip();

	exec(HESTIA_CMD . "v-list-sys-config json", $output, $return_var);
	$settings = json_decode(implode("", $output), true);
	unset($output, $return_var);
	$root_user = $settings["config"]["ROOT_USER"];
	// Get the status of api
	$api_status =
		!empty($settings["config"]["API_SYSTEM"]) && is_numeric($settings["config"]["API_SYSTEM"])
			? $settings["config"]["API_SYSTEM"]
			: 0;
	if ($api_status == 0) {
		// Check if API is disabled for all users
		api_error(E_DISABLED, "API has been disabled", $hst_return);
	}

	// Check if API access is enabled for the user
	if ($settings["config"]["API_ALLOWED_IP"] != "allow-all") {
		$ip_list = explode(",", $settings["config"]["API_ALLOWED_IP"]);
		$ip_list[] = "";
		if (!in_array($v_real_user_ip, $ip_list) && !in_array("0.0.0.0", $ip_list)) {
			api_error(E_FORBIDDEN, "IP is not allowed to connect with API", $hst_return);
		}
	}

	// Get POST Params
	$hst_access_key_id = trim($request_data["access_key"] ?? "");
	$hst_secret_access_key = trim($request_data["secret_key"] ?? "");
	$hst_cmd = trim($request_data["cmd"] ?? "");
	$hst_cmd_args = [];
	for ($i = 1; $i <= 13; $i++) {
		if (isset($request_data["arg{$i}"])) {
			$hst_cmd_args["arg{$i}"] = trim($request_data["arg{$i}"]);
		}
	}

	if (empty($hst_cmd)) {
		api_error(E_INVALID, "Command not provided", $hst_return);
	} elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $hst_cmd)) {
		api_error(E_INVALID, "$hst_cmd command invalid", $hst_return);
	}

	if (empty($hst_access_key_id) || empty($hst_secret_access_key)) {
		api_error(E_PASSWORD, "Authentication failed", $hst_return);
	}

	// Authenticates the key and checks permission to run the script
	exec(
		HESTIA_CMD .
			"v-check-access-key " .
			quoteshellarg($hst_access_key_id) .
			" " .
			quoteshellarg($hst_secret_access_key) .
			" " .
			quoteshellarg($hst_cmd) .
			" " .
			quoteshellarg($v_real_user_ip) .
			" json",
		$output,
		$return_var,
	);
	if ($return_var > 0) {
		//api_error($return_var, "Key $hst_access_key_id - authentication failed", $hst_return);
		api_error($return_var, $output, $hst_return);
	}
	$key_data = json_decode(implode("", $output), true) ?? [];
	unset($output, $return_var);

	// Rate limit the authenticated key before any command is prepared
	api_rate_limit("key:" . $hst_access_key_id, $hst_return);

	$key_user = $key_data["USER"];
	$user_arg_position =
		isset($key_data["USER_ARG_POSITION"]) && is_numeric($key_data["USER_ARG_POSITION"])
			? $key_data["USER_ARG_POSITION"]
			: -1;

	# Check if API access is enabled for nonadmin users
	if ($key_user != $root_user && $api_status < 2) {
		api_error(E_API_DISABLED, "API has been disabled", $hst_return);
	}

	// Checks if the value entered in the "user" argument matches the user of the key
	if (
		$key_user != $root_user &&
		$user_arg_position > 0 &&
		$hst_cmd_args["arg{$user_arg_position}"] != $key_user
	) {
		api_error(
			E_FORBIDDEN,
			"Key $hst_access_key_id - the \"user\" argument doesn\'t match the key\'s user",
			$hst_return,
		);
	}

	// Prepare command
	$cmdquery = HESTIA_CMD . escapeshellcmd($hst_cmd);

	// Prepare arguments
	foreach ($hst_cmd_args as $cmd_arg) {
		$cmdquery .= " " . quoteshellarg($cmd_arg);
	}

	# v-make-temp files is manodory other wise some functions will break
	if ($hst_cmd == "v-make-tmp-file") {
		$fp = fopen("/tmp/" . basename($hst_cmd_args["arg2"]), "w");
		fwrite($fp, $hst_cmd_args["arg1"] . "\n");
		fclose($fp);
		$cmd_exit_code = 0;
	} else {
		// Run cmd query
		exec($cmdquery, $output, $cmd_exit_code);
		$cmd_output = trim(implode("\n", $output));
		unset($output);
	}

	header("Hestia-Exit-Code: $cmd_exit_code");

	if ($hst_return == "code") {
		echo $cmd_exit_code;
	} else {
		if ($cmd_exit_code > 0) {
			http_response_code(exit_code_to_http_code($cmd_exit_code));
		} else {
			http_response_code(!empty($cmd_output) ? 200 : 204);

			if (!empty($cmd_output) && json_decode($cmd_output, true)) {
				header("Content-Type: application/json; charset=utf-8");
			}
		}

		echo $cmd_output;
	}

	exit();
}

// Get request data
if (isset($_POST["access_key"]) || isset($_POST["user"]) || isset($_POST["hash"])) {
	$request_data = $_POST;
} elseif (($json_data = json_decode(file_get_contents("php://input"), true)) != null) {
	$request_data = $json_data;
} else {
	api_error(
		405,
		"Error: data received is null or invalid, check https://hestiacp.com/docs/server-administration/rest-api.html",
		"",
	);
}

// Try to get access key in the hash
if (
	!isset($request_data["access_key"]) &&
	isset($request_data["hash"]) &&
	substr_count($request_data["hash"], ":") == 1
) {
	$hash_parts = explode(":", $request_data["hash"]);
	if (strlen($hash_parts[0]) == 20 && strlen($hash_parts[1]) == 40) {
		$request_data["access_key"] = $hash_parts[0];
		$request_data["secret_key"] = $hash_parts[1];
		unset($request_data["hash"]);
	}
}

// Check data format
if (isset($request_data["access_key"]) && isset($request_data["secret_key"])) {
	api_connection($request_data);
} elseif (isset($request_data["user"]) || isset($request_data["hash"])) {
	api_legacy($request_data);
} else {
	api_error(
		405,
		"Error: data received is null or invalid, check https://hestiacp.com/docs/server-administration/rest-api.html",
		"",
	);
}
