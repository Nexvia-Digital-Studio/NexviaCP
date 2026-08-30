<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "DOMAIN_EXPIRY";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Security Guard: Admin only
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Manual Trigger Scan & Auto-Suspend
if (!empty($_POST["scan_expirations"])) {
	verify_csrf($_POST);
	exec(HESTIA_CMD . "v-check-domain-expirations check", $scan_out, $scan_rc);
	if ($scan_rc == 0) {
		$_SESSION["ok_msg"] = $is_tr ? "Tüm domain süreleri başarıyla tarandı; süresi dolanlar askıya alındı, eski domainler 1 yıllık varsayılana eşitlendi." : _("Domain expirations checked successfully; expired domains suspended.");
	} else {
		$_SESSION["error_msg"] = ($is_tr ? "Tarama sırasında hata: " : _("Error during scan: ")) . implode(" ", $scan_out);
	}
	header("Location: /list/domain-expiry/");
	exit();
}

// Action 2: Single Domain Duration Update / Renewal
if (!empty($_POST["update_single_expiry"])) {
	verify_csrf($_POST);
	$target_user = quoteshellarg($_POST["exp_user"] ?? $user_plain);
	$target_domain = quoteshellarg($_POST["exp_domain"] ?? "");
	$exp_value = trim($_POST["exp_value"] ?? "1y");

	if ($exp_value === "custom" && !empty($_POST["custom_date"])) {
		$exp_value = trim($_POST["custom_date"]);
	}

	if (!empty($_POST["exp_domain"]) && !empty($exp_value)) {
		exec(HESTIA_CMD . "v-set-web-domain-expiry " . $target_user . " " . $target_domain . " " . quoteshellarg($exp_value) . " 'yes'", $out, $rc);
		if ($rc == 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "Domain süresi başarıyla güncellendi / uzatıldı: " : _("Domain expiry updated: ")) . htmlspecialchars($_POST["exp_domain"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Süre güncelleme hatası: " : _("Error updating expiry: ")) . implode(" ", $out);
		}
	}
	header("Location: /list/domain-expiry/");
	exit();
}

// Action 3: Bulk Duration Update
if (!empty($_POST["bulk_expiry_action"]) && !empty($_POST["domains"])) {
	verify_csrf($_POST);
	$bulk_action = $_POST["bulk_action_type"] ?? "1y";
	if ($bulk_action === "custom" && !empty($_POST["bulk_custom_date"])) {
		$bulk_action = trim($_POST["bulk_custom_date"]);
	}

	$success_count = 0;
	$selected_domains = (array)$_POST["domains"];
	foreach ($selected_domains as $item) {
		// item format: user:domain or domain
		$parts = explode(":", $item);
		if (count($parts) === 2) {
			$u = $parts[0];
			$d = $parts[1];
		} else {
			$u = $user_plain;
			$d = $item;
		}

		if (!empty($d)) {
			exec(HESTIA_CMD . "v-set-web-domain-expiry " . quoteshellarg($u) . " " . quoteshellarg($d) . " " . quoteshellarg($bulk_action) . " 'yes'", $out, $rc);
			if ($rc == 0) {
				$success_count++;
			}
			unset($out);
		}
	}

	$_SESSION["ok_msg"] = sprintf($is_tr ? "%d domainin süresi başarıyla güncellendi." : _("%d domains updated successfully."), $success_count);
	header("Location: /list/domain-expiry/");
	exit();
}

// Fetch Expiry Data
$target_arg = (($_SESSION["userContext"] ?? "") === "admin") ? "" : $user_plain;
exec(HESTIA_CMD . "v-check-domain-expirations json " . quoteshellarg($target_arg), $json_out, $json_rc);
$data = json_decode(implode("", $json_out), true) ?: [
	"summary" => [
		"total_domains" => 0,
		"active_domains" => 0,
		"expiring_soon" => 0,
		"expired" => 0,
		"suspended" => 0,
		"unlimited" => 0,
		"migrated" => 0
	],
	"domains" => []
];

$summary = $data["summary"] ?? [];
$domains = $data["domains"] ?? [];

// Render Template
render_page($user, $TAB, "list_domain_expiry");
