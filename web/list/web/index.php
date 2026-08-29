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
		$v_target_user = trim($_POST["deploy_user"] ?? $user_plain, "'\" ");
		$v_domain_name = $_POST["deploy_domain"] ?? "";
		$v_repo_input = $_POST["deploy_repo_name"] ?? "";
		$v_branch = $_POST["deploy_branch"] ?? "main";
		$v_mode = $_POST["deploy_mode"] ?? "auto";
		$deploy_error = "";
		$v_repo = "";

		if ($v_repo_input === "__custom__") {
			// Any public GitHub repository entered by link
			$raw_url = trim($_POST["deploy_repo_url"] ?? "");
			if ($raw_url === "") {
				$deploy_error = $is_tr ? "Lütfen GitHub repo linkini giriniz." : _("Please enter the GitHub repository URL.");
			} elseif (preg_match('#^(?:https?://)?(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?(?:/(?:tree|blob)/([A-Za-z0-9._-]+(?:/[A-Za-z0-9._-]+)*))?/?$#i', $raw_url, $m)) {
				if (!empty($m[3])) {
					$v_branch = $m[3];
				}
				$v_repo = "https://github.com/{$m[1]}/{$m[2]}.git";
			} else {
				$deploy_error = $is_tr
					? "Geçersiz GitHub linki. Örnek: https://github.com/kullanici/proje"
					: _("Invalid GitHub URL. Example: https://github.com/user/project");
			}
		} else {
			$v_repo = $v_repo_input;
		}

		if ($deploy_error !== "") {
			$_SESSION["error_msg"] = $deploy_error;
		} elseif (!empty($v_domain_name) && !empty($v_repo)) {
			exec(
				HESTIA_CMD . "v-deploy-github-repo " .
					quoteshellarg($v_target_user) . " " .
					quoteshellarg($v_domain_name) . " " .
					quoteshellarg($v_repo) . " " .
					quoteshellarg($v_branch) . " " .
					quoteshellarg($v_mode),
				$output,
				$return_var
			);
			if ($return_var == 0) {
				$_SESSION["ok_msg"] = $is_tr ? "Web sitesi başarıyla kuruldu ve yayınlandı: " : _("Web site deployed successfully: ") . $_POST["deploy_domain"];
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Dağıtım hatası: " : _("Deployment error: ")) . implode(" ", array_slice($output, -3));
			}
		}
		header("Location: /list/web/");
		exit();
	}
}

// Data
if (is_admin_overview()) {
	$data = list_records_for_all_users("v-list-web-domains");
} else {
	exec(HESTIA_CMD . "v-list-web-domains " . $user . " 'json'", $output, $return_var);
	$data = json_decode(implode("", $output), true) ?: [];
}
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
