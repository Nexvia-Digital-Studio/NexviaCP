<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "SERVERS";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user (admin only)
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// -------------------------------------------------------------
// Validation helpers (mirror the rules the CLI scripts enforce)
// -------------------------------------------------------------

function remote_server_valid_name(string $name): bool {
	if ($name === "" || strlen($name) > 64 || str_contains($name, "..")) {
		return false;
	}
	return (bool)preg_match('/^[a-zA-Z0-9_-]+$/', $name);
}

function remote_server_valid_host(string $host): bool {
	if ($host === "" || strlen($host) > 253) {
		return false;
	}
	// No path characters, whitespace, quotes or dollar signs
	if (strpbrk($host, "/ \t\r\n\"'\$") !== false || str_contains($host, "..")) {
		return false;
	}
	// IPv4 with strict octet range
	if (preg_match('/^[0-9]{1,3}(\.[0-9]{1,3}){3}$/', $host)) {
		foreach (explode(".", $host) as $octet) {
			if ((int)$octet > 255) {
				return false;
			}
		}
		return true;
	}
	// IPv6 (reasonable check): hex + colons, at least two colons,
	// '::' at most once, every explicit group at most 4 hex digits
	if (preg_match('/^[0-9A-Fa-f:]+$/', $host) && substr_count($host, ":") >= 2) {
		if (substr_count($host, "::") > 1) {
			return false;
		}
		foreach (preg_split('/:+/', $host, -1, PREG_SPLIT_NO_EMPTY) as $group) {
			if (strlen($group) > 4) {
				return false;
			}
		}
		return true;
	}
	// Hostname: dot-separated labels without leading/trailing hyphens
	return (bool)preg_match(
		'/^[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(\.[a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)*$/',
		$host,
	);
}

function remote_server_valid_port(string $port): bool {
	if (!preg_match('/^[0-9]{1,5}$/', $port)) {
		return false;
	}
	$p = (int)$port;
	return $p >= 1 && $p <= 65535;
}

function remote_server_valid_user(string $user): bool {
	if ($user === "" || strlen($user) > 64 || str_starts_with($user, "-")) {
		return false;
	}
	return (bool)preg_match('/^[a-zA-Z0-9._-]+$/', $user);
}

function remote_server_valid_key(string $key): bool {
	if ($key === "" || strlen($key) > 512 || str_contains($key, "..")) {
		return false;
	}
	if (strpbrk($key, " \t\r\n\"'\$") !== false) {
		return false;
	}
	// /root/.ssh/<something>
	if (str_starts_with($key, "/root/.ssh/")) {
		return strlen($key) > strlen("/root/.ssh/");
	}
	// /home/<user>/<something> (covers /home/<user>/.ssh/...)
	if (str_starts_with($key, "/home/")) {
		$rest = substr($key, strlen("/home/"));
		$first = explode("/", $rest)[0];
		if ($first === "" || $rest === $first || !preg_match('/^[a-zA-Z0-9._-]+$/', $first)) {
			return false;
		}
		return true;
	}
	return false;
}

function remote_server_valid_command(string $cmd): bool {
	if ($cmd === "") {
		return false;
	}
	// Newlines are never allowed (single command line only)
	if (str_contains($cmd, "\n") || str_contains($cmd, "\r")) {
		return false;
	}
	return strlen($cmd) <= 2000;
}

// -------------------------------------------------------------
// Action: add / update a server
// -------------------------------------------------------------
if (!empty($_POST["action"]) && $_POST["action"] === "add_server") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	$v_host = trim($_POST["v_host"] ?? "");
	$v_port = trim($_POST["v_port"] ?? "") ?: "22";
	$v_user = trim($_POST["v_user"] ?? "") ?: "root";
	$v_key = trim($_POST["v_key"] ?? "") ?: "/root/.ssh/id_ed25519";
	$v_note = trim($_POST["v_note"] ?? "");

	if (!remote_server_valid_name($v_name)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz sunucu adı. Sadece harf, rakam, '-' ve '_' kullanın (en fazla 64 karakter)."
			: "Invalid server name. Use letters, digits, '-' and '_' only (max 64 characters).";
	} elseif (!remote_server_valid_host($v_host)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz host. Hostname veya IPv4/IPv6 adresi girin (path karakterleri yasak)."
			: "Invalid host. Enter a hostname or IPv4/IPv6 address (path characters are not allowed).";
	} elseif (!remote_server_valid_port($v_port)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz port. 1-65535 arası bir tam sayı girin."
			: "Invalid port. Enter an integer between 1 and 65535.";
	} elseif (!remote_server_valid_user($v_user)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz kullanıcı adı. Harf, rakam, '.', '_', '-' kullanın ('-' ile başlayamaz)."
			: "Invalid user. Use letters, digits, '.', '_', '-' (may not start with '-').";
	} elseif (!remote_server_valid_key($v_key)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz anahtar yolu. Sadece /root/.ssh/ veya /home/<kullanıcı>/ altındaki yollar kabul edilir."
			: "Invalid key path. Only paths under /root/.ssh/ or /home/<user>/ are accepted.";
	} else {
		exec(
			HESTIA_CMD . "v-add-remote-server " .
				quoteshellarg($v_name) . " " .
				quoteshellarg($v_host) . " " .
				quoteshellarg($v_port) . " " .
				quoteshellarg($v_user) . " " .
				quoteshellarg($v_key) . " " .
				quoteshellarg($v_note),
			$output,
			$return_var,
		);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Sunucu kaydedildi: " . implode(" ", $output)
				: "Remote server saved: " . implode(" ", $output);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Sunucu kaydedilirken hata oluştu: " : "Error saving server: ") . implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/servers/");
	exit();
}

// -------------------------------------------------------------
// Action: delete a server
// -------------------------------------------------------------
if (!empty($_POST["action"]) && $_POST["action"] === "delete_server") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	if (!remote_server_valid_name($v_name)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz sunucu adı." : "Invalid server name.";
	} else {
		exec(HESTIA_CMD . "v-delete-remote-server " . quoteshellarg($v_name), $output, $return_var);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Sunucu silindi: " . $v_name
				: "Remote server deleted: " . $v_name;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Sunucu silinirken hata oluştu: " : "Error deleting server: ") . implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/servers/");
	exit();
}

// -------------------------------------------------------------
// Action: connection test on one server
// -------------------------------------------------------------
if (!empty($_POST["action"]) && $_POST["action"] === "check_server") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	if (!remote_server_valid_name($v_name)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz sunucu adı." : "Invalid server name.";
	} else {
		exec(HESTIA_CMD . "v-check-remote-server " . quoteshellarg($v_name), $output, $return_var);
		$_SESSION["remote_result"] = [
			"kind" => "check",
			"server" => $v_name,
			"command" => "echo REMOTE_OK",
			"output" => (string)implode("\n", $output),
			"exit_code" => (int)$return_var,
		];
		unset($output);
	}
	header("Location: /list/servers/");
	exit();
}

// -------------------------------------------------------------
// Action: run a command on one server
// -------------------------------------------------------------
if (!empty($_POST["action"]) && $_POST["action"] === "run_server") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	$v_command = (string)($_POST["v_command"] ?? "");

	if (!remote_server_valid_name($v_name)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz sunucu adı." : "Invalid server name.";
	} elseif (!remote_server_valid_command($v_command)) {
		$_SESSION["error_msg"] = $is_tr
			? "Komut reddedildi: satır sonu karakteri içeremez, 2000 karakterden uzun olamaz ve boş olamaz."
			: "Command rejected: it must not contain newlines, must not exceed 2000 characters and must not be empty.";
	} else {
		exec(
			HESTIA_CMD . "v-run-remote-server " . quoteshellarg($v_name) . " " . quoteshellarg($v_command),
			$output,
			$return_var,
		);
		$_SESSION["remote_result"] = [
			"kind" => "run",
			"server" => $v_name,
			"command" => $v_command,
			"output" => (string)implode("\n", $output),
			"exit_code" => (int)$return_var,
		];
		unset($output);
	}
	header("Location: /list/servers/");
	exit();
}

// -------------------------------------------------------------
// Fetch server list (KEY_EXISTS comes from the CLI '[ -r key ]' test)
// -------------------------------------------------------------
exec(HESTIA_CMD . "v-list-remote-servers json", $output, $return_var);
$remote_servers = json_decode(implode("", $output), true) ?: [];
unset($output);

// Flash result of the last check/run (post/redirect/get pattern); the
// template renders it fully escaped inside a <pre> block.
$remote_result = $_SESSION["remote_result"] ?? null;
unset($_SESSION["remote_result"]);

// Optional edit prefill via ?edit=NAME (display only, changes no state;
// the name is only accepted when it matches an existing server)
$edit_server = null;
if (!empty($_GET["edit"])) {
	$candidate = trim((string)$_GET["edit"]);
	if (remote_server_valid_name($candidate) && isset($remote_servers[$candidate])) {
		$edit_server = ["name" => $candidate] + $remote_servers[$candidate];
	}
}

// Render page
render_page($user, $TAB, "list_servers");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
