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

// Action: AJAX — analyze a repository for the smart deploy wizard
if (($_GET["ajax"] ?? "") === "analyze_repo" && ($_SESSION["userContext"] ?? "") === "admin") {
	header("Content-Type: application/json; charset=utf-8");
	if (verify_csrf($_GET)) {
		$repo = trim($_GET["repo"] ?? "");
		$branch = trim($_GET["branch"] ?? "");
		if ($branch === "") {
			$branch = "main";
		}
		$is_url = (bool)preg_match("#^https?://#i", $repo);
		$valid = $repo !== "" && (!$is_url || preg_match("#^https?://(?:www\.)?github\.com/[A-Za-z0-9_.-]+/[A-Za-z0-9_.-]+#i", $repo));
		if ($valid) {
			exec(HESTIA_CMD . "v-analyze-repo " . quoteshellarg($repo) . " " . quoteshellarg($branch), $ajax_out, $ajax_rc);
			$payload = implode("", $ajax_out);
			$json = json_decode($payload, true);
			if ($ajax_rc == 0 && is_array($json)) {
				echo json_encode($json, JSON_UNESCAPED_UNICODE);
			} else {
				echo json_encode(["ok" => false, "error" => "analyze-failed"], JSON_UNESCAPED_UNICODE);
			}
		} else {
			echo json_encode(["ok" => false, "error" => "invalid-repo"], JSON_UNESCAPED_UNICODE);
		}
		exit();
	}
	echo json_encode(["ok" => false, "error" => "csrf"], JSON_UNESCAPED_UNICODE);
	exit();
}

// Action: 1-Click Deploy Web Site from GitHub (smart wizard)
if (!empty($_POST["deploy_repo"]) && ($_SESSION["userContext"] ?? "") === "admin") {
	if (verify_csrf($_POST)) {
		$v_target_user = trim($_POST["deploy_user"] ?? $user_plain, "'\" ");
		$v_domain_name = $_POST["deploy_domain"] ?? "";
		$v_repo_input = $_POST["deploy_repo_name"] ?? "";
		$v_branch = $_POST["deploy_branch"] ?? "main";
		$v_mode = $_POST["deploy_mode"] ?? "auto";
		$v_channel = $_POST["deploy_channel"] ?? "git";
		$v_compose = trim($_POST["deploy_compose"] ?? "docker-compose.yml");
		$v_app_name = strtolower(preg_replace("/[^a-Za-z0-9-]+/", "-", $_POST["deploy_app_name"] ?? ""));
		$v_app_name = trim($v_app_name, "-");
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
				if ($v_app_name === "") {
					$v_app_name = strtolower("{$m[1]}-{$m[2]}");
				}
			} else {
				$deploy_error = $is_tr
					? "Geçersiz GitHub linki. Örnek: https://github.com/kullanici/proje"
					: _("Invalid GitHub URL. Example: https://github.com/user/project");
			}
		} else {
			$v_repo = $v_repo_input;
			if ($v_app_name === "") {
				$v_app_name = strtolower(preg_replace("/[^a-zA-Z0-9-]+/", "-", $v_repo_input));
			}
		}
		$v_app_name = substr($v_app_name, 0, 32);

		// Wizard env values -> temp KEY=VALUE file consumed (and deleted) by the deployer
		$env_tmp = "";
		$env_lines = [];
		foreach (preg_split("/\r\n|\n/", $_POST["deploy_env"] ?? "") as $line) {
			if (preg_match('/^([A-Za-z_][A-Za-z0-9_]*)=(.*)$/', trim($line), $em)) {
				$env_lines[] = $em[1] . "=" . $em[2];
			}
		}
		if ($env_lines) {
			$env_tmp = tempnam(sys_get_temp_dir(), "nxv_env_");
			file_put_contents($env_tmp, implode("\n", array_slice($env_lines, 0, 200)) . "\n");
			chmod($env_tmp, 0600);
		}

		if ($deploy_error !== "") {
			$_SESSION["error_msg"] = $deploy_error;
		} elseif ($v_channel === "docker") {
			// Docker Compose stack: v-add-docker-app (+ managed .env from wizard)
			if ($v_app_name === "" || $v_repo === "") {
				$_SESSION["error_msg"] = $is_tr ? "Uygulama adı ve repo gerekli." : _("App name and repository required.");
			} else {
				exec(
					HESTIA_CMD . "v-add-docker-app " .
						quoteshellarg($v_app_name) . " " .
						quoteshellarg($v_repo) . " " .
						quoteshellarg($v_branch) . " " .
						quoteshellarg($v_compose),
					$output,
					$return_var
				);
				if ($return_var == 0 && $env_lines) {
					exec(
						"printf %s " . quoteshellarg(implode("\n", $env_lines)) . " | " . HESTIA_CMD . "v-save-docker-app-env " . quoteshellarg($v_app_name),
						$output2,
						$return_var2
					);
				}
				if ($return_var == 0) {
					$_SESSION["ok_msg"] = $is_tr
						? "Docker uygulaması kurulmaya başladı: " . htmlspecialchars($v_app_name) . " — durumunu uygulama detayından izleyin."
						: _("Docker app deployment started: ") . htmlspecialchars($v_app_name);
					header("Location: /list/docker-app/?app=" . urlencode($v_app_name));
					exit();
				}
				$_SESSION["error_msg"] = ($is_tr ? "Docker kurulum hatası: " : _("Docker deploy error: ")) . implode(" ", array_slice($output, -3));
			}
		} elseif (!empty($v_domain_name) && !empty($v_repo)) {
			exec(
				HESTIA_CMD . "v-deploy-github-repo " .
					quoteshellarg($v_target_user) . " " .
					quoteshellarg($v_domain_name) . " " .
					quoteshellarg($v_repo) . " " .
					quoteshellarg($v_branch) . " " .
					quoteshellarg($v_mode) . " " .
					quoteshellarg($env_tmp),
				$output,
				$return_var
			);
			if ($return_var == 0) {
				$_SESSION["ok_msg"] = $is_tr ? "Web sitesi başarıyla kuruldu ve yayınlandı: " : _("Web site deployed successfully: ") . $_POST["deploy_domain"];
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Dağıtım hatası: " : _("Deployment error: ")) . implode(" ", array_slice($output, -3));
			}
		}
		if ($env_tmp !== "") {
			unlink($env_tmp);
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
