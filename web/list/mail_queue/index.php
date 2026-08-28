<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "MAIL_QUEUE";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user (admin only: queue contains data of all users)
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user/");
	exit();
}

// Fetch queue data
exec(HESTIA_CMD . "v-list-mail-queue json", $output, $return_var);
$queue_data = json_decode(implode("", $output), true);
unset($output);

if (empty($queue_data) || !is_array($queue_data)) {
	$queue_data = [
		"summary" => [
			"total" => 0,
			"frozen" => 0,
			"oldest_age_seconds" => 0,
			"count_listed" => 0,
			"truncated" => false,
		],
		"messages" => [],
	];
	$return_var = 0;
}

// Handle POST actions: per-message control and queue flush
if (!empty($_POST["action"]) && $_POST["action"] === "ctrl") {
	verify_csrf($_POST);

	$op = $_POST["op"] ?? "";
	$id = $_POST["id"] ?? "";

	// Strict server-side validation (mirror of CLI validation)
	if (!in_array($op, ["retry", "freeze", "unfreeze", "remove", "flush"], true)) {
		$_SESSION["error_msg"] = _("Invalid mail queue action.");
		header("Location: /list/mail_queue/");
		exit();
	}
	if ($op === "flush") {
		if ($id !== "") {
			$_SESSION["error_msg"] = _("Invalid mail queue action.");
			header("Location: /list/mail_queue/");
			exit();
		}
		$cmd = "v-ctrl-mail-queue flush";
	} elseif ($id === "all") {
		if ($op !== "remove") {
			$_SESSION["error_msg"] = _("Invalid mail queue action.");
			header("Location: /list/mail_queue/");
			exit();
		}
		$cmd = "v-ctrl-mail-queue remove all";
	} else {
		if (!preg_match('/^[a-zA-Z0-9]{6}-[a-zA-Z0-9]{6}-[a-zA-Z0-9]{2}$/', $id)) {
			$_SESSION["error_msg"] = _("Invalid mail queue message id.");
			header("Location: /list/mail_queue/");
			exit();
		}
		$cmd = "v-ctrl-mail-queue " . quoteshellarg($op) . " " . quoteshellarg($id);
	}

	exec(HESTIA_CMD . $cmd, $ctrl_output, $ctrl_return_var);
	unset($ctrl_output);

	if ($ctrl_return_var != 0) {
		$_SESSION["error_msg"] = sprintf(_("Error code: %s"), $ctrl_return_var);
	}
	header("Location: /list/mail_queue/");
	exit();
}

// Render page
render_page($user, $TAB, "list_mail_queue");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
