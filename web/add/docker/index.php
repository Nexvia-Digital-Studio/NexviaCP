<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

ob_start();
$TAB = "DOCKER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Docker app management is admin-only.
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

// Check POST request
if (!empty($_POST["ok"])) {
	// Check token
	verify_csrf($_POST);

	// Check empty fields
	if (empty($_POST["v_app"])) {
		$errors[] = _("Uygulama adı");
	}
	if (empty($_POST["v_repo"])) {
		$errors[] = _("Repository");
	}
	if (!empty($errors[0])) {
		$_SESSION["error_msg"] = sprintf(_('Field "%s" can not be blank.'), implode(", ", $errors));
	}

	$v_app = $_POST["v_app"];
	$v_repo = $_POST["v_repo"];
	$v_branch = $_POST["v_branch"] ?? "main";
	$v_compose = $_POST["v_compose"] ?? "";
	$v_force = !empty($_POST["v_force"]) ? "yes" : "no";
	$v_deploy_cmd = trim($_POST["v_deploy_cmd"] ?? "");

	if (empty($_SESSION["error_msg"])) {
		if (!preg_match("/^[a-z0-9][a-z0-9_-]{0,39}$/", $v_app)) {
			$_SESSION["error_msg"] = _("Uygulama adı küçük harf, rakam, - ve _ içerebilir (en fazla 40 karakter).");
		}
	}

	// Add docker app (clone is synchronous; build runs in the background).
	if (empty($_SESSION["error_msg"])) {
		exec(
			HESTIA_CMD .
				"v-add-docker-app " .
				quoteshellarg($v_app) . " " .
				quoteshellarg($v_repo) . " " .
				quoteshellarg($v_branch) . " " .
				quoteshellarg($v_compose) . " " .
				quoteshellarg($v_force) . " " .
				quoteshellarg($v_deploy_cmd),
			$output,
			$return_var,
		);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = sprintf(
				_("'%s' uygulaması eklendi — kurulum arka planda sürüyor. Durumu Docker listesinde izleyin."),
				htmlentities($v_app)
			);
			unset($v_app, $v_repo, $v_branch, $v_compose, $v_deploy_cmd);
		}
		check_return_code($return_var, $output);
		unset($output, $return_var);
	}
}

if (empty($v_app)) {
	$v_app = "";
}
if (empty($v_repo)) {
	$v_repo = "";
}
if (empty($v_branch)) {
	$v_branch = "main";
}
if (empty($v_compose)) {
	$v_compose = "";
}
if (empty($v_deploy_cmd)) {
	$v_deploy_cmd = "";
}

render_page($user, $TAB, "add_docker");

// Flush session messages
unset($_SESSION["error_msg"]);
unset($_SESSION["ok_msg"]);
