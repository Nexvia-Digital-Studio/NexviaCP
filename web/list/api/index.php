<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "API_SERVICES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Security Guard: Admin only!
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action: Restart API / Backend App
if (!empty($_GET["restart"]) && !empty($_GET["domain"]) && !empty($_GET["user"])) {
	if (verify_csrf($_GET)) {
		$v_domain = quoteshellarg($_GET["domain"]);
		$v_user = quoteshellarg($_GET["user"]);
		exec(HESTIA_CMD . "v-restart-web-domain-app " . $v_user . " " . $v_domain, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = $is_tr ? "Servis başarıyla yeniden başlatıldı." : _("Service restarted successfully");
		} else {
			$_SESSION["error_msg"] = $is_tr ? "Servis başlatılırken hata oluştu." : _("Error restarting service");
		}
		header("Location: /list/api/");
		exit();
	}
}

// Action: 1-Click Deploy from GitHub
if (!empty($_POST["deploy_repo"])) {
	if (verify_csrf($_POST)) {
		$v_user = quoteshellarg($_POST["deploy_user"] ?? "admin");
		$v_domain = quoteshellarg($_POST["deploy_domain"] ?? "");
		$v_repo = quoteshellarg($_POST["deploy_repo_name"] ?? "");
		$v_branch = quoteshellarg($_POST["deploy_branch"] ?? "main");
		$v_mode = quoteshellarg($_POST["deploy_mode"] ?? "api");

		if (!empty($_POST["deploy_domain"]) && !empty($_POST["deploy_repo_name"])) {
			exec(HESTIA_CMD . "v-deploy-github-repo " . $v_user . " " . $v_domain . " " . $v_repo . " " . $v_branch . " " . $v_mode, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["ok_msg"] = ($is_tr ? "API servisi başarıyla kuruldu ve canlıya alındı: " : _("API service deployed successfully: ")) . "https://" . $_POST["deploy_domain"];
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Dağıtım hatası: " : _("Deployment error: ")) . implode(" ", array_slice($output, -3));
			}
		}
		header("Location: /list/api/");
		exit();
	}
}

// Fetch Running Backend Apps / Standalone APIs
exec(HESTIA_CMD . "v-list-web-domain-apps json", $app_output, $return_var);
$api_services = json_decode(implode("", $app_output), true) ?: [];

// Fetch GitHub Repositories
exec(HESTIA_CMD . "v-list-github-repos json", $gh_output, $return_var);
$github_repos = json_decode(implode("", $gh_output), true) ?: [];

// Fetch Users List
exec(HESTIA_CMD . "v-list-users json", $u_output, $return_var);
$users_list = json_decode(implode("", $u_output), true) ?: [];

// Render Page
render_page($user, $TAB, "list_api");
