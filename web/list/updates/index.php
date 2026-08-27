<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "UPDATES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action: Update NexviaCP Core from GitHub
if (!empty($_GET["update_nexvia"])) {
	if (verify_csrf($_GET)) {
		exec(HESTIA_CMD . "v-update-sys-nexvia main", $up_out, $return_var);
		if ($return_var == 0) {
			$_SESSION["error_msg"] = $is_tr ? "NexviaCP çekirdeği GitHub'dan en güncel sürüme yükseltildi." : _("NexviaCP core updated successfully from GitHub.");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Güncelleme hatası: " : _("Update error: ")) . implode(" ", array_slice($up_out, -3));
		}
		header("Location: /list/updates/");
		exit();
	}
}

// Data
exec(HESTIA_CMD . "v-list-sys-hestia-updates json", $output, $return_var);
$data = json_decode(implode("", $output), true) ?: [];
unset($output);

exec(HESTIA_CMD . "v-list-sys-hestia-autoupdate plain", $output, $return_var);
$autoupdate = $output["0"] ?? "Disabled";
unset($output);

// Fetch NexviaCP & Upstream HestiaCP Release Status
exec(HESTIA_CMD . "v-check-sys-nexvia-updates json", $nx_out, $return_var);
$nexvia_info = json_decode(implode("", $nx_out), true) ?: [];

// Render page
render_page($user, $TAB, "list_updates");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
