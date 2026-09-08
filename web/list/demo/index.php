<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

// Demo sites: publish git repositories (plain HTML / PHP, no build step)
// under secret panel-domain URLs like /<name>-<random16>/ so clients can
// browse a demo that outsiders cannot guess.
ob_start();
$TAB = "DEMO";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$v_is_admin = ($_SESSION["userContext"] ?? "") === "admin";
$v_host = $_SERVER["HTTP_HOST"] ?? "";

// Check POST requests
if (!empty($_POST["action"])) {
	verify_csrf($_POST);

	switch ($_POST["action"]) {
		case "add":
			$v_repo_input = $_POST["demo_repo_name"] ?? "";
			$v_repo_url = trim($_POST["demo_repo_url"] ?? "");
			$v_branch = trim($_POST["demo_branch"] ?? "main");
			$v_name = strtolower(trim($_POST["demo_name"] ?? ""));
			$v_subdir = trim($_POST["demo_subdir"] ?? "");
			$v_repo = "";

			if ($v_repo_input === "__custom__") {
				if (preg_match('#^(?:https?://)?(?:www\.)?github\.com/([A-Za-z0-9_.-]+)/([A-Za-z0-9_.-]+?)(?:\.git)?(?:/(?:tree|blob)/([A-Za-z0-9._-]+))?/?$#i', $v_repo_url, $m)) {
					if (!empty($m[3]) && ($v_branch === "" || $v_branch === "main")) {
						$v_branch = $m[3];
					}
					$v_repo = "https://github.com/{$m[1]}/{$m[2]}.git";
				} else {
					$_SESSION["error_msg"] = _("Geçersiz GitHub linki. Örnek: https://github.com/kullanici/proje");
					break;
				}
			} elseif ($v_repo_input !== "") {
				// Bare org repo name from the admin select.
				$v_repo = $v_repo_input;
			} else {
				$_SESSION["error_msg"] = _("Lütfen bir repo seçin ya da GitHub linkini girin.");
				break;
			}
			if ($v_branch !== "" && !preg_match("#^[A-Za-z0-9._/-]{1,100}$#", $v_branch)) {
				$_SESSION["error_msg"] = _("Geçersiz dal (branch) adı.");
				break;
			}
			if ($v_branch === "") {
				$v_branch = "main";
			}
			if ($v_name !== "" && !preg_match("/^[a-z0-9][a-z0-9-]{0,29}$/", $v_name)) {
				$_SESSION["error_msg"] = _("Demo adı: küçük harf, rakam ve tire (en fazla 30 karakter) olmalı.");
				break;
			}
			if ($v_subdir !== "" && (strpos($v_subdir, "..") !== false || !preg_match("#^[A-Za-z0-9._/-]{1,200}$#", $v_subdir))) {
				$_SESSION["error_msg"] = _("Geçersiz alt klasör (SUBDIR).");
				break;
			}

			exec(
				HESTIA_CMD . "v-add-demo-site " . $user . " " .
					quoteshellarg($v_repo) . " " .
					quoteshellarg($v_branch) . " " .
					quoteshellarg($v_name) . " " .
					quoteshellarg($v_subdir),
				$output,
				$return_var,
			);
			if ($return_var === 0) {
				$v_path = "";
				$v_hints = [];
				foreach ($output ?: [] as $line) {
					if (strpos($line, "URL_PATH:") === 0) {
						$v_path = substr($line, strlen("URL_PATH:"));
					} elseif (strpos($line, "[!") === 0) {
						$v_hints[] = $line;
					}
				}
				if ($v_path !== "" && $v_path[0] === "/") {
					$_SESSION["ok_msg"] = _("Demo sitesi yayınlandı 🎉") . "\n" .
						"https://" . $v_host . $v_path .
						(empty($v_hints) ? "" : "\n" . implode("\n", $v_hints));
				} else {
					$_SESSION["ok_msg"] = implode("\n", $output ?: []);
				}
			} else {
				$_SESSION["error_msg"] = implode("\n", $output ?: []) ?: _("Demo oluşturulamadı.");
			}
			unset($output, $return_var);
			break;

		case "update":
			$v_slug = $_POST["slug"] ?? "";
			if (!preg_match("/^[a-z0-9][a-z0-9-]{0,40}-[a-f0-9]{16}$/", $v_slug)) {
				$_SESSION["error_msg"] = _("Geçersiz demo kimliği.");
				break;
			}
			exec(HESTIA_CMD . "v-update-demo-site " . $user . " " . quoteshellarg($v_slug), $output, $return_var);
			if ($return_var === 0) {
				$_SESSION["ok_msg"] = implode("\n", array_filter($output ?: [], fn($l) => strpos($l, "OK:") === 0)) ?: _("Demo güncellendi.");
			} else {
				$_SESSION["error_msg"] = implode("\n", $output ?: []) ?: _("Demo güncellenemedi.");
			}
			unset($output, $return_var);
			break;

		case "delete":
			$v_slug = $_POST["slug"] ?? "";
			if (!preg_match("/^[a-z0-9][a-z0-9-]{0,40}-[a-f0-9]{16}$/", $v_slug)) {
				$_SESSION["error_msg"] = _("Geçersiz demo kimliği.");
				break;
			}
			exec(HESTIA_CMD . "v-delete-demo-site " . $user . " " . quoteshellarg($v_slug), $output, $return_var);
			if ($return_var === 0) {
				$_SESSION["ok_msg"] = _("Demo silindi: ") . $v_slug;
			} else {
				$_SESSION["error_msg"] = implode("\n", $output ?: []) ?: _("Demo silinemedi.");
			}
			unset($output, $return_var);
			break;
	}
	unset($_POST);
}

// List: admins see every user's demos, regular users see their own.
exec(HESTIA_CMD . "v-list-demo-sites " . ($v_is_admin ? "all" : $user) . " json", $output, $return_var);
$data = json_decode(implode("", $output ?: []), true) ?: [];
unset($output, $return_var);

// GitHub repos for the add modal (admins get the org repo dropdown).
$github_repos = [];
if ($v_is_admin) {
	exec(HESTIA_CMD . "v-list-github-repos json", $gh_output, $gh_return);
	$github_repos = json_decode(implode("", $gh_output ?: []), true) ?: [];
	unset($gh_output, $gh_return);
}

// Render page
render_page($user, $TAB, "list_demo");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
