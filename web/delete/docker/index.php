<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Docker app management is admin-only.
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

// Check token
verify_csrf($_GET);

if (!empty($_GET["app"])) {
	$v_app = quoteshellarg($_GET["app"]);
	// Defaults: volumes preserved, linked domains removed with the app.
	// Deleting volumes is available from the app detail page (POST).
	exec(HESTIA_CMD . "v-delete-docker-app " . $v_app, $output, $return_var);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = sprintf(_("Docker app '%s' deleted."), $_GET["app"]);
	}
}
check_return_code($return_var, $output);
unset($output);

$back = $_SESSION["back"] ?? "";
if (!empty($back)) {
	header("Location: " . $back);
	exit();
}

header("Location: /list/docker/");
exit();
