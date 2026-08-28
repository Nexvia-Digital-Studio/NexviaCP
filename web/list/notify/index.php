<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "NOTIFY";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user (admin only)
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Validate a channel target against the same rules the CLI enforces
function is_valid_notify_target(string $type, string $target): bool {
	if ($type === "telegram") {
		// bot_token@chat_id (numeric id, negative group id, or @channelname)
		return (bool)preg_match('/^[0-9A-Za-z:_-]+@(-?[0-9]+|@[A-Za-z0-9_]+)$/', $target);
	}
	if (strlen($target) > 2048 || strpos($target, "..") !== false) {
		return false;
	}
	return (bool)preg_match('#^https://[a-zA-Z0-9:/%._~?&=+,#-]+$#', $target);
}

// Action: add / update a channel
if (!empty($_POST["action"]) && $_POST["action"] === "add_channel") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	$v_type = trim($_POST["v_type"] ?? "");
	$v_target = trim($_POST["v_target"] ?? "");
	$v_events = trim($_POST["v_events"] ?? "");
	if ($v_events === "") {
		$v_events = "all";
	}

	if (!preg_match('/^[a-zA-Z0-9_-]+$/', $v_name) || str_contains($v_name, "..")) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz kanal adı. Sadece harf, rakam, '-' ve '_' kullanın."
			: "Invalid channel name. Use letters, digits, '-' and '_' only.";
	} elseif (!in_array($v_type, ["telegram", "discord", "slack", "webhook"], true)) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz kanal tipi." : "Invalid channel type.";
	} elseif (!is_valid_notify_target($v_type, $v_target)) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz hedef. Telegram için bot_token@chat_id, diğerleri için https:// webhook adresi gerekli."
			: "Invalid target. Telegram expects bot_token@chat_id; others expect an https:// webhook URL.";
	} elseif (!preg_match('/^[a-zA-Z0-9,_-]+$/', $v_events) || str_contains($v_events, "..")) {
		$_SESSION["error_msg"] = $is_tr
			? "Geçersiz olay listesi. Virgülle ayrılmış etiketler kullanın."
			: "Invalid events list. Use comma-separated tags.";
	} else {
		exec(
			HESTIA_CMD . "v-set-sys-notify-channel " .
				quoteshellarg($v_name) . " " .
				quoteshellarg($v_type) . " " .
				quoteshellarg($v_target) . " " .
				quoteshellarg($v_events),
			$output,
			$return_var
		);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Bildirim kanalı kaydedildi: " . implode(" ", $output)
				: "Notification channel saved: " . implode(" ", $output);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Kanal kaydedilirken hata oluştu: " : "Error saving channel: ") . implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/notify/");
	exit();
}

// Action: delete a channel
if (!empty($_POST["action"]) && $_POST["action"] === "delete_channel") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	if (!preg_match('/^[a-zA-Z0-9_-]+$/', $v_name) || str_contains($v_name, "..")) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz kanal adı." : "Invalid channel name.";
	} else {
		exec(HESTIA_CMD . "v-delete-sys-notify-channel " . quoteshellarg($v_name), $output, $return_var);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Bildirim kanalı silindi: " . $v_name
				: "Notification channel deleted: " . $v_name;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Kanal silinirken hata oluştu: " : "Error deleting channel: ") . implode(" ", $output);
		}
		unset($output);
	}
	header("Location: /list/notify/");
	exit();
}

// Action: send a test message to one channel
if (!empty($_POST["action"]) && $_POST["action"] === "test_channel") {
	verify_csrf($_POST);

	$v_name = trim($_POST["v_name"] ?? "");
	if (!preg_match('/^[a-zA-Z0-9_-]+$/', $v_name) || str_contains($v_name, "..")) {
		$_SESSION["error_msg"] = $is_tr ? "Geçersiz kanal adı." : "Invalid channel name.";
	} else {
		exec(
			HESTIA_CMD . "v-notify-sys-channel " .
				quoteshellarg("NexviaCP test notification") . " " .
				quoteshellarg($v_name) . " " .
				quoteshellarg("NexviaCP Test"),
			$output,
			$return_var
		);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = $is_tr
				? "Test bildirimi gönderildi: " . $v_name
				: "Test notification sent to: " . $v_name;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Test bildirimi gönderilemedi: " : "Error sending test notification to: ") . $v_name;
		}
		unset($output);
	}
	header("Location: /list/notify/");
	exit();
}

// Fetch channel list (targets are already masked by the CLI)
exec(HESTIA_CMD . "v-list-sys-notify-channels json", $output, $return_var);
$notify_channels = json_decode(implode("", $output), true) ?: [];
unset($output);

// Render page
render_page($user, $TAB, "list_notify");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
