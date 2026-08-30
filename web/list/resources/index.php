<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "RESOURCES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Security Guard: Admin only
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');

// Action 1: Change Priority Tier (0 to 5)
if (!empty($_POST["change_priority"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["prio_user"] ?? $user_plain);
	$v_domain = quoteshellarg($_POST["prio_domain"] ?? "");
	$v_prio = quoteshellarg($_POST["prio_level"] ?? "0");

	if (!empty($_POST["prio_domain"])) {
		exec(HESTIA_CMD . "v-set-web-domain-priority " . $v_user . " " . $v_domain . " " . $v_prio, $output, $return_var);
		if ($return_var == 0) {
			$tier_names = [
				0 => $is_tr ? "0 (⚡ Akıllı Otomatik)" : "0 (⚡ Smart Auto)",
				1 => $is_tr ? "1 (🟢 Düşük / Eco-Kısılmış)" : "1 (🟢 Low / Eco-Throttled)",
				2 => $is_tr ? "2 (🔵 Standart)" : "2 (🔵 Standard)",
				3 => $is_tr ? "3 (🟣 Yüksek Öncelik)" : "3 (🟣 High Priority)",
				4 => $is_tr ? "4 (🟠 Kritik)" : "4 (🟠 Critical)",
				5 => $is_tr ? "5 (👑 Maksimum VIP)" : "5 (👑 Maximum VIP)"
			];
			$p_val = (int)($_POST["prio_level"] ?? 0);
			$p_label = $tier_names[$p_val] ?? $p_val;
			$_SESSION["ok_msg"] = ($is_tr ? "Öncelik güncellendi: " : _("Priority updated: ")) . htmlspecialchars($_POST["prio_domain"]) . " -> " . $p_label;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Öncelik güncellenirken hata oluştu: " : _("Error updating priority: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/resources/");
	exit();
}

// Action 2: Trigger Global Auto-Tune Engine Now
if (!empty($_POST["tune_all"])) {
	verify_csrf($_POST);
	exec(HESTIA_CMD . "v-tune-sys-resources", $output, $return_var);
	if ($return_var == 0) {
		$_SESSION["ok_msg"] = $is_tr ? "Tüm sitelerin trafik ve kaynak profilleri analiz edildi; boşta olanlar kısıldı, aktif siteler ölçeklendirildi." : _("All sites analyzed; idle sites throttled and active sites scaled successfully.");
	} else {
		$_SESSION["error_msg"] = ($is_tr ? "Kaynak optimizasyonu sırasında hata oluştu: " : _("Error during resource auto-tuning: ")) . implode(" ", $output);
	}
	header("Location: /list/resources/");
	exit();
}

// Action 2b: Trigger Single Domain Auto-Tune Now
if (!empty($_POST["tune_single"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["tune_user"] ?? $user_plain);
	$v_domain = quoteshellarg($_POST["tune_domain"] ?? "");
	if (!empty($_POST["tune_domain"])) {
		exec(HESTIA_CMD . "v-tune-sys-resources " . $v_user . " " . $v_domain, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "Analiz ve optimizasyon tamamlandı: " : _("Analysis and optimization completed: ")) . htmlspecialchars($_POST["tune_domain"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Optimizasyon sırasında hata: " : _("Error during optimization: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/resources/");
	exit();
}

// Action 3: Fine-grain Custom Limits Save
if (!empty($_POST["save_custom_cgroup"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["custom_user"] ?? $user_plain);
	$v_domain = quoteshellarg($_POST["custom_domain"] ?? "");
	$v_high = quoteshellarg($_POST["custom_high"] ?? "256M");
	$v_max = quoteshellarg($_POST["custom_max"] ?? "1G");
	$v_cpu = quoteshellarg($_POST["custom_cpu"] ?? "100%");

	if (!empty($_POST["custom_domain"])) {
		exec(HESTIA_CMD . "v-change-web-domain-cgroup " . $v_user . " " . $v_domain . " " . $v_high . " " . $v_max . " " . $v_cpu, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "Özel kaynak limitleri uygulandı: " : _("Custom resource limits applied: ")) . htmlspecialchars($_POST["custom_domain"]);
		} else {
			$_SESSION["error_msg"] = implode(" ", $output);
		}
	}
	header("Location: /list/resources/");
	exit();
}

// Fetch Governance & Comparison Data
exec(HESTIA_CMD . "v-list-resource-governance json", $gov_output, $return_var);
$gov_data = json_decode(implode("", $gov_output), true) ?: [
	"summary" => [
		"TOTAL_DOMAINS" => 0,
		"IDLE_COUNT" => 0,
		"ACTIVE_COUNT" => 0,
		"BOOSTED_COUNT" => 0,
		"THROTTLED_COUNT" => 0,
		"VIP_COUNT" => 0,
		"AUTO_MANAGED_COUNT" => 0,
		"TOTAL_RAM_MANAGED_MB" => 0,
		"ESTIMATED_SAVED_RAM_MB" => 0
	],
	"domains" => []
];

$summary = $gov_data["summary"] ?? [];
$domains = $gov_data["domains"] ?? [];

// Render Template
render_page($user, $TAB, "list_resources");
