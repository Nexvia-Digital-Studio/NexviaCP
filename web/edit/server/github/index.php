<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "GITHUB";

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
		$_SESSION["ok_msg"] = $is_tr ? "GitHub entegrasyon ayarları başarıyla kaydedildi." : _("GitHub integration settings saved successfully.");
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
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = $is_tr ? "Global Secret (Anahtar) güvenle kaydedildi." : _("Global secret saved securely.");
		} else {
			$_SESSION["error_msg"] = $is_tr ? "Secret kaydedilirken hata oluştu." : _("Error saving secret.");
		}
	}
	header("Location: /edit/server/github/");
	exit();
}

// Action 3: Delete Global Vault Secret
if (!empty($_POST["delete_vault"]) && !empty($_POST["key"])) {
	verify_csrf($_POST);
	$v_key = quoteshellarg(trim($_POST["key"]));
	exec(HESTIA_CMD . "v-delete-sys-global-vault " . $v_key, $output, $return_var);
	$_SESSION["ok_msg"] = $is_tr ? "Global Secret başarıyla silindi." : _("Global secret deleted.");
	header("Location: /edit/server/github/");
	exit();
}

// Action 4: Generate or Save GitHub Webhook Secret
if (!empty($_POST["save_webhook_secret"])) {
	verify_csrf($_POST);
	$v_secret_val = trim($_POST["webhook_secret_val"] ?? "");
	if (empty($v_secret_val) || !empty($_POST["generate_random"])) {
		$v_secret_val = bin2hex(random_bytes(24));
	}
	$v_key = quoteshellarg("GITHUB_WEBHOOK_SECRET");
	$v_val = quoteshellarg($v_secret_val);
	exec(HESTIA_CMD . "v-set-sys-global-vault " . $v_key . " " . $v_val, $output, $return_var);
	if ($return_var == 0) {
		$_SESSION["ok_msg"] = $is_tr ? "GitHub Webhook Secret başarıyla kaydedildi." : _("GitHub Webhook Secret saved successfully.");
	} else {
		$_SESSION["error_msg"] = $is_tr ? "Webhook Secret kaydedilirken hata oluştu." : _("Error saving Webhook Secret.");
	}
	header("Location: /edit/server/github/");
	exit();
}

// Read from system config
exec(HESTIA_CMD . "v-list-sys-config json", $sys_output, $sys_return_var);
$sys_config = json_decode(implode("", $sys_output), true)["config"] ?? [];

$v_github_org = $sys_config["GITHUB_ORG"] ?? $_SESSION["GITHUB_ORG"] ?? "";
$v_github_token = $sys_config["GITHUB_TOKEN"] ?? "";

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

$is_connected = ($gh_status === "connected");

// Fetch Global Vault Secrets (Masked)
$global_vault = json_decode(shell_exec(HESTIA_CMD . "v-list-sys-global-vault json"), true) ?: [];

// Fetch Webhook Secret
exec(HESTIA_CMD . "v-list-webhook-secret 2>/dev/null", $wh_secret_out, $wh_secret_rc);
$webhook_secret = ($wh_secret_rc === 0 && !empty($wh_secret_out[0])) ? trim($wh_secret_out[0]) : "";

// Render page
render_page($user, $TAB, "edit_server_github");
