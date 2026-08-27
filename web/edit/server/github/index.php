<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "SERVER";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Save GitHub Account
if (!empty($_POST["save"])) {
	verify_csrf($_POST);
	
	$v_org_val = trim($_POST["v_github_org"] ?? "");
	$v_token_val = trim($_POST["v_github_token"] ?? "");

	$v_org = quoteshellarg($v_org_val);
	$v_token = quoteshellarg($v_token_val);

	exec(HESTIA_CMD . "v-set-sys-github-token " . $v_org . " " . $v_token, $output, $return_var);
	if ($return_var == 0) {
		$_SESSION["GITHUB_ORG"] = $v_org_val;
		$_SESSION["GITHUB_TOKEN"] = $v_token_val;
		$_SESSION["error_msg"] = $is_tr ? "GitHub entegrasyon ayarları başarıyla kaydedildi." : _("GitHub integration settings saved successfully.");
	} else {
		$_SESSION["error_msg"] = $is_tr ? "GitHub ayarları kaydedilirken hata oluştu." : _("Error saving GitHub settings.");
	}
	header("Location: /edit/server/github/");
	exit();
}

// Action 2: Add or Update Global Vault Secret
if (!empty($_POST["save_vault"])) {
	verify_csrf($_POST);
	$v_key = quoteshellarg(trim($_POST["vault_key"] ?? ""));
	$v_val = quoteshellarg(trim($_POST["vault_value"] ?? ""));

	if (!empty($_POST["vault_key"]) && !empty($_POST["vault_value"])) {
		exec(HESTIA_CMD . "v-set-sys-global-vault " . $v_key . " " . $v_val, $output, $return_var);
		$_SESSION["error_msg"] = ($return_var == 0) ? ($is_tr ? "Global Secret (Anahtar) güvenle kaydedildi." : _("Global secret saved securely.")) : ($is_tr ? "Secret kaydedilirken hata oluştu." : _("Error saving secret."));
	}
	header("Location: /edit/server/github/");
	exit();
}

// Action 3: Delete Global Vault Secret
if (!empty($_GET["delete_vault"]) && !empty($_GET["key"])) {
	if (verify_csrf($_GET)) {
		$v_key = quoteshellarg(trim($_GET["key"]));
		exec(HESTIA_CMD . "v-delete-sys-global-vault " . $v_key, $output, $return_var);
		$_SESSION["error_msg"] = $is_tr ? "Global Secret başarıyla silindi." : _("Global secret deleted.");
		header("Location: /edit/server/github/");
		exit();
	}
}

// Read from system config
exec(HESTIA_CMD . "v-list-sys-config json", $sys_output, $sys_return_var);
$sys_config = json_decode(implode("", $sys_output), true)["config"] ?? [];

$v_github_org = $sys_config["GITHUB_ORG"] ?? $_SESSION["GITHUB_ORG"] ?? "";
$v_github_token = $sys_config["GITHUB_TOKEN"] ?? $_SESSION["GITHUB_TOKEN"] ?? "";

$gh_status = "unconfigured";
$gh_repos = [];
$gh_error_detail = "";

if (!empty($v_github_token)) {
	exec(HESTIA_CMD . "v-list-github-repos json", $gh_output, $return_var);
	$gh_raw = implode("", $gh_output);
	$gh_data = json_decode($gh_raw, true);
	if ($return_var == 0 && is_array($gh_data) && !isset($gh_data["error"])) {
		$gh_status = "connected";
		$gh_repos = $gh_data;
	} else {
		$gh_status = "error";
		$gh_error_detail = $gh_data["message"] ?? $gh_raw;
	}
}

// Fetch Global Vault Secrets (Masked)
$global_vault = json_decode(shell_exec(HESTIA_CMD . "v-list-sys-global-vault json"), true) ?: [];

// Render page
render_page($user, $TAB, "edit_server_github");
