<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "AI_HEALING";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Security Guard: Admin only
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Run AI Diagnostics & Healing Scan Now
if (!empty($_POST["run_scan"])) {
	verify_csrf($_POST);
	exec(HESTIA_CMD . "v-monitor-sys-healing force", $output, $return_var);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = $is_tr
			? "AI Teşhis ve Kendi Kendini Onarma taraması başarıyla tamamlandı: " . implode(" ", $output)
			: _("AI Diagnostics and Self-Healing scan completed successfully: ") . implode(" ", $output);
	} else {
		$_SESSION["error_msg"] = $is_tr
			? "Tarama sırasında bir hata oluştu: " . implode(" ", $output)
			: _("Error executing self-healing scan: ") . implode(" ", $output);
	}
	header("Location: /list/ai-healing/");
	exit();
}

// Action 2: Save Notification & Healing Settings
if (!empty($_POST["save_settings"])) {
	verify_csrf($_POST);

	$email = trim($_POST["notify_email"] ?? "");
	$level = strtoupper(trim($_POST["notify_level"] ?? "INFO"));
	$sender_name = trim($_POST["notify_sender_name"] ?? "NexviaCP AI Healing Engine");
	$sender_email = trim($_POST["notify_sender_email"] ?? "");
	$notify_enabled = (($_POST["notify_enabled"] ?? "") === "yes") ? "yes" : "no";
	$healing_enabled = (($_POST["healing_enabled"] ?? "") === "yes") ? "yes" : "no";

	if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz e-posta adresi formatı." : _("Invalid email address format.");
		header("Location: /list/ai-healing/");
		exit();
	}

	$v_email = quoteshellarg($email);
	$v_level = quoteshellarg($level);
	$v_sname = quoteshellarg($sender_name);
	$v_semail = quoteshellarg($sender_email);
	$v_enabled = quoteshellarg($notify_enabled);

	exec(HESTIA_CMD . "v-set-sys-notification-email {$v_email} {$v_level} {$v_sname} {$v_semail} {$v_enabled}", $output, $return_var);

	if ($return_var === 0) {
		$v_heal_flag = quoteshellarg($healing_enabled);
		exec(HESTIA_CMD . "v-change-sys-config-value SYS_HEALING_ENABLED {$v_heal_flag}");

		$_SESSION["ok_msg"] = $is_tr
			? "AI Bildirim ve Otomatik Onarım ayarları başarıyla kaydedildi."
			: _("AI Notification and Self-Healing settings saved successfully.");
	} else {
		$_SESSION["error_msg"] = ($is_tr ? "Ayarlar kaydedilirken hata oluştu: " : _("Error saving settings: ")) . implode(" ", $output);
	}

	header("Location: /list/ai-healing/");
	exit();
}

// Action 3: Send Test HTML Email Alert
if (!empty($_POST["send_test_alert"])) {
	verify_csrf($_POST);

	$test_level = strtoupper(trim($_POST["test_level"] ?? "INFO"));
	$test_email = trim($_POST["test_email"] ?? "");

	if (!empty($test_email) && !filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz test e-posta adresi." : _("Invalid test email address.");
		header("Location: /list/ai-healing/");
		exit();
	}

	$v_subj = quoteshellarg("NexviaCP AI Ops Notification Hub — Test Alert (" . $test_level . ")");
	$v_msg = quoteshellarg("This is a verified test notification sent from NexviaCP AI Ops & Self-Healing Hub to confirm responsive HTML email rendering and delivery routing.");
	$v_lvl = quoteshellarg($test_level);
	$v_to = quoteshellarg($test_email);
	$v_extra = quoteshellarg(json_encode([
		"service" => "Test / AI Ops Hub",
		"root_cause" => "Manual operator diagnostic test dispatch",
		"action_taken" => "Verified HTML email template formatting and SMTP/sendmail transport",
		"status" => "VERIFIED (HEALTHY)",
		"log_snippet" => "[test-dispatch] " . date("Y-m-d H:i:s") . " UTC: Test packet dispatched to " . ($test_email ?: "default admin")
	]));

	exec(HESTIA_CMD . "v-send-sys-notification {$v_subj} {$v_msg} {$v_lvl} {$v_to} 'test' {$v_extra}", $output, $return_var);

	if ($return_var === 0) {
		$_SESSION["ok_msg"] = $is_tr
			? "Test HTML e-posta bildirimi başarıyla gönderildi: " . implode(" ", $output)
			: _("Test HTML email notification dispatched successfully: ") . implode(" ", $output);
	} else {
		$_SESSION["error_msg"] = ($is_tr ? "Test e-postası gönderilirken hata oluştu: " : _("Error sending test email: ")) . implode(" ", $output);
	}

	header("Location: /list/ai-healing/");
	exit();
}

// Action 4: Clear Past Healing Events History
if (!empty($_POST["clear_events"])) {
	verify_csrf($_POST);

	$heal_dir = "/var/lib/hestia/healing";
	if (file_exists("$heal_dir/events.json")) {
		@file_put_contents("$heal_dir/events.json", "[]");
	}
	$_SESSION["ok_msg"] = $is_tr ? "Geçmiş onarım günlüğü temizlendi." : _("Healing event timeline history cleared.");
	header("Location: /list/ai-healing/");
	exit();
}

// Fetch Healing Events, Live Services, & Settings Data
exec(HESTIA_CMD . "v-list-sys-healing-events json", $heal_output, $return_var);
$heal_data = json_decode(implode("", $heal_output), true) ?: [
	"summary" => [
		"ENGINE_STATUS" => "ACTIVE",
		"LAST_SCAN_TIME" => date("c"),
		"MONITORED_SERVICES_COUNT" => 0,
		"ACTIVE_SERVICES_COUNT" => 0,
		"TOTAL_HEALING_EVENTS" => 0,
		"HEALS_LAST_24H" => 0,
		"SYSTEM_HEALTH" => "HEALTHY"
	],
	"settings" => [
		"SYS_NOTIFY_EMAIL" => "",
		"SYS_NOTIFY_LEVEL" => "INFO",
		"SYS_NOTIFY_SENDER_NAME" => "NexviaCP AI Healing Engine",
		"SYS_NOTIFY_SENDER_EMAIL" => "",
		"SYS_NOTIFY_ENABLED" => "yes",
		"SYS_HEALING_ENABLED" => "yes"
	],
	"services" => [],
	"events" => [],
	"notifications" => []
];

$summary = $heal_data["summary"] ?? [];
$settings = $heal_data["settings"] ?? [];
$services = $heal_data["services"] ?? [];
$events = $heal_data["events"] ?? [];
$notifications = $heal_data["notifications"] ?? [];

// Render Template
render_page($user, $TAB, "list_ai_healing");
