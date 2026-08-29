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

// Validate the requested app.
$v_app_name = $_GET["app"] ?? $_POST["app"] ?? "";
if (!preg_match("/^[a-z0-9][a-z0-9_-]{0,39}$/", $v_app_name)) {
	header("Location: /list/docker/");
	exit();
}
$v_app = quoteshellarg($v_app_name);

// Check POST requests
if (!empty($_POST["action"])) {
	verify_csrf($_POST);

	switch ($_POST["action"]) {
		case "update":
			exec(HESTIA_CMD . "v-update-docker-app " . $v_app, $output, $return_var);
			break;

		case "restart-service":
			$v_service = quoteshellarg($_POST["service"] ?? "");
			exec(HESTIA_CMD . "v-restart-docker-app " . $v_app . " " . $v_service, $output, $return_var);
			break;

		case "add-domain":
			$v_domain = quoteshellarg($_POST["v_domain"] ?? "");
			$v_service = quoteshellarg($_POST["v_service"] ?? "");
			$v_domain_user = quoteshellarg($_POST["v_domain_user"] ?? "admin");
			exec(
				HESTIA_CMD . "v-add-docker-app-domain " . $v_app . " " . $v_domain . " " .
					$v_service . " " . $v_domain_user,
				$output,
				$return_var,
			);
			// Optional Let's Encrypt certificate after the domain exists.
			if ($return_var === 0 && !empty($_POST["v_ssl"])) {
				exec(
					HESTIA_CMD . "v-add-letsencrypt-domain " . $v_domain_user . " " . $v_domain,
					$output2,
					$return_var2,
				);
				if ($return_var2 !== 0) {
					$_SESSION["error_msg"] = implode("\n", $output2 ?: []);
				}
				unset($output2, $return_var2);
			}
			break;

		case "delete-domain":
			$v_domain = quoteshellarg($_POST["v_domain"] ?? "");
			exec(HESTIA_CMD . "v-delete-docker-app-domain " . $v_app . " " . $v_domain, $output, $return_var);
			break;

		case "save-env":
			// Pipe the textarea through a temp file (same pattern as
			// v-add-database password passing).
			$v_env_tmp = tempnam("/tmp", "cpe");
			$fp = fopen($v_env_tmp, "w");
			fwrite($fp, $_POST["v_env"] ?? "");
			fclose($fp);
			exec(
				HESTIA_CMD . "v-save-docker-app-env " . $v_app . " < " . quoteshellarg($v_env_tmp),
				$output,
				$return_var,
			);
			unlink($v_env_tmp);
			break;

		case "delete-volumes":
			exec(
				HESTIA_CMD . "v-delete-docker-app " . $v_app . " volumes",
				$output,
				$return_var,
			);
			if ($return_var === 0) {
				header("Location: /list/docker/");
				exit();
			}
			break;
	}
	check_return_code($return_var, $output);
	unset($output, $return_var);
	// POST-redirect-GET so refresh does not re-submit.
	header("Location: /list/docker-app/?app=" . urlencode($v_app_name));
	exit();
}

// Data: app details with live service state.
exec(HESTIA_CMD . "v-list-docker-app " . $v_app . " json", $output, $return_var);
$data = json_decode(implode("", $output), true);
unset($output, $return_var);
if (!is_array($data)) {
	header("Location: /list/docker/");
	exit();
}

// Optional log view: ?logs=<service> or ?logs=_deploy (deploy.log).
$v_logs = $_GET["logs"] ?? "";
$v_log_output = "";
if ($v_logs !== "") {
	if ($v_logs === "_deploy") {
		$v_log_service = "";
	} elseif (preg_match("/^[a-zA-Z0-9_.-]+$/", $v_logs)) {
		$v_log_service = $v_logs;
	} else {
		$v_logs = "";
	}
	if ($v_logs !== "") {
		exec(
			HESTIA_CMD . "v-list-docker-app-logs " . $v_app . " " .
				quoteshellarg($v_log_service) . " 200",
			$output,
			$return_var,
		);
		$v_log_output = implode("\n", $output ?: []);
		unset($output, $return_var);
	}
}

// Current managed .env content.
exec(HESTIA_CMD . "v-list-docker-app-env " . $v_app, $output, $return_var);
$v_env = implode("\n", $output ?: []);
unset($output, $return_var);

// Panel users (domain owner choices for an admin).
exec(HESTIA_CMD . "v-list-users json", $output, $return_var);
$users = json_decode(implode("", $output), true);
unset($output, $return_var);
if (!is_array($users)) {
	$users = [];
}

render_page($user, $TAB, "list_docker_app");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
