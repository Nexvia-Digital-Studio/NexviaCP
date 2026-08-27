<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "WEB";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action: Update Git Repository for Web Domain
if (!empty($_GET["git_update"]) && !empty($_GET["domain"])) {
	if (verify_csrf($_GET)) {
		$v_domain = quoteshellarg($_GET["domain"]);
		exec(HESTIA_CMD . "v-update-web-domain-git " . $user . " " . $v_domain, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = $is_tr ? "Web sitesi GitHub'dan en güncel sürüme yükseltildi." : _("Web site updated successfully from GitHub.");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Güncelleme hatası: " : _("Update error: ")) . implode(" ", array_slice($output, -3));
		}
		header("Location: /list/web/");
		exit();
	}
}

// Action: 1-Click Deploy Web Site from GitHub
if (!empty($_POST["deploy_repo"]) && ($_SESSION["userContext"] ?? "") === "admin") {
	if (verify_csrf($_POST)) {
		$v_target_user = quoteshellarg(trim($_POST["deploy_user"] ?? $user_plain, "'\" "));
		$v_domain_name = quoteshellarg($_POST["deploy_domain"] ?? "");
		$v_repo = quoteshellarg($_POST["deploy_repo_name"] ?? "");
		$v_branch = quoteshellarg($_POST["deploy_branch"] ?? "main");
		$v_mode = quoteshellarg($_POST["deploy_mode"] ?? "auto");

		if (!empty($_POST["deploy_domain"]) && !empty($_POST["deploy_repo_name"])) {
			exec(HESTIA_CMD . "v-deploy-github-repo " . $v_target_user . " " . $v_domain_name . " " . $v_repo . " " . $v_branch . " " . $v_mode, $output, $return_var);
			if ($return_var == 0) {
				$_SESSION["ok_msg"] = ($is_tr ? "Web sitesi başarıyla kuruldu ve yayınlandı: " : _("Web site deployed successfully: ")) . $_POST["deploy_domain"];
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Dağıtım hatası: " : _("Deployment error: ")) . implode(" ", array_slice($output, -3));
			}
		}
		header("Location: /list/web/");
		exit();
	}
}

// Data
exec(HESTIA_CMD . "v-list-web-domains " . $user . " 'json'", $output, $return_var);
$data = json_decode(implode("", $output), true) ?: [];
if ($_SESSION["userSortOrder"] == "name") {
	ksort($data);
} else {
	$data = array_reverse($data, true);
}
$ips = json_decode(shell_exec(HESTIA_CMD . "v-list-sys-ips json"), true);

// Fetch GitHub Repositories for Admin
$github_repos = [];
if (($_SESSION["userContext"] ?? "") === "admin") {
	exec(HESTIA_CMD . "v-list-github-repos json", $gh_output, $return_var);
	$github_repos = json_decode(implode("", $gh_output), true) ?: [];
}

// Render page
render_page($user, $TAB, "list_web");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
