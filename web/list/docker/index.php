<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "DOCKER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// SECURITY: Docker app management is admin-only. Hide the entry point
// from non-admin sessions entirely (defense layer 0 — the v-* scripts
// are additionally root-gated and only reachable through sudo).
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

// Row actions (POST + CSRF), then redirect back (POST-redirect-GET).
if (!empty($_POST["action"])) {
	verify_csrf($_POST);
	$v_app = quoteshellarg($_POST["app"] ?? "");
	switch ($_POST["action"]) {
		case "update":
			exec(HESTIA_CMD . "v-update-docker-app " . $v_app, $output, $return_var);
			if ($return_var === 0) {
				$_SESSION["ok_msg"] = sprintf(
					_("Update of docker app '%s' started — watch the state on this page."),
					$_POST["app"]
				);
			}
			break;
		case "restart":
			exec(HESTIA_CMD . "v-restart-docker-app " . $v_app, $output, $return_var);
			break;
		case "suspend":
			exec(HESTIA_CMD . "v-suspend-docker-app " . $v_app, $output, $return_var);
			break;
		case "unsuspend":
			exec(HESTIA_CMD . "v-unsuspend-docker-app " . $v_app, $output, $return_var);
			break;
	}
	check_return_code($return_var, $output);
	unset($output, $return_var);
	header("Location: /list/docker/");
	exit();
}

// Data: all docker apps with live state.
exec(HESTIA_CMD . "v-list-docker-apps json", $output, $return_var);
$data = json_decode(implode("", $output), true);
unset($output, $return_var);
if (!is_array($data)) {
	$data = [];
}

// Keep the legacy Portainer shortcut: find the admin's web domain that
// uses the 'docker-ui' proxy template, if any.
$portainer_domain = null;
if (!empty($_SESSION["ROOT_USER"])) {
	$rootUser = $_SESSION["ROOT_USER"];
	exec(
		HESTIA_CMD . "v-list-web-domains " . quoteshellarg($rootUser) . " json",
		$output,
		$return_var,
	);
	$json = implode("", $output);
	unset($output, $return_var);
	$domains = json_decode($json, true);
	if (is_array($domains)) {
		foreach ($domains as $dName => $dData) {
			$tpl = $dData["TPL"] ?? "";
			$proxy = $dData["PROXY"] ?? "";
			if (strpos($tpl, "docker-ui") !== false || strpos($proxy, "docker-ui") !== false) {
				$portainer_domain = $dName;
				break;
			}
		}
	}
}

render_page($user, $TAB, "list_docker");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
