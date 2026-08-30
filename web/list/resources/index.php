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

// Action 3: Single Domain Open / Close (Suspend / Unsuspend)
if (!empty($_POST["toggle_domain_status"])) {
	verify_csrf($_POST);
	$target_user = quoteshellarg($_POST["toggle_user"] ?? $user_plain);
	$target_domain = quoteshellarg($_POST["toggle_domain"] ?? "");
	$target_action = $_POST["toggle_action"] ?? "";

	if (!empty($_POST["toggle_domain"])) {
		if ($target_action === "suspend") {
			exec(HESTIA_CMD . "v-suspend-web-domain " . $target_user . " " . $target_domain . " 'yes'", $out, $rc);
			if ($rc == 0) {
				$_SESSION["ok_msg"] = ($is_tr ? "Site yayını ve yönlendirmesi kapatıldı / askıya alındı: " : _("Web domain suspended: ")) . htmlspecialchars($_POST["toggle_domain"]);
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Site kapatılırken hata: " : _("Error suspending domain: ")) . implode(" ", $out);
			}
		} elseif ($target_action === "unsuspend") {
			exec(HESTIA_CMD . "v-unsuspend-web-domain " . $target_user . " " . $target_domain . " 'yes'", $out, $rc);
			if ($rc == 0) {
				$_SESSION["ok_msg"] = ($is_tr ? "Site yayını ve yönlendirmesi açıldı / aktif edildi: " : _("Web domain unsuspended: ")) . htmlspecialchars($_POST["toggle_domain"]);
			} else {
				$_SESSION["error_msg"] = ($is_tr ? "Site açılırken hata: " : _("Error unsuspending domain: ")) . implode(" ", $out);
			}
		}
	}
	header("Location: /list/resources/");
	exit();
}

// Action 4: Flush / Restart PHP Workers for Domain
if (!empty($_POST["flush_php_workers"])) {
	verify_csrf($_POST);
	$v_domain = $_POST["flush_domain"] ?? "";
	if (!empty($v_domain)) {
		exec(HESTIA_CMD . "v-restart-service php-fpm no", $out, $rc);
		$_SESSION["ok_msg"] = ($is_tr ? "PHP iş parçacıkları ve bellek havuzu sıfırlandı: " : _("PHP worker memory flushed for: ")) . htmlspecialchars($v_domain);
	}
	header("Location: /list/resources/");
	exit();
}

// Action 5: System Service Action (Start, Stop, Restart)
if (!empty($_POST["service_action"])) {
	verify_csrf($_POST);
	$srv_name = trim($_POST["srv_name"] ?? "");
	$srv_act = trim($_POST["srv_action"] ?? "restart");
	if (!empty($srv_name)) {
		$v_srv = quoteshellarg($srv_name);
		if ($srv_act === "stop") {
			exec(HESTIA_CMD . "v-stop-service " . $v_srv, $out, $rc);
			$action_label = $is_tr ? "durduruldu" : "stopped";
		} elseif ($srv_act === "start") {
			exec(HESTIA_CMD . "v-start-service " . $v_srv, $out, $rc);
			$action_label = $is_tr ? "başlatıldı" : "started";
		} else {
			exec(HESTIA_CMD . "v-restart-service " . $v_srv . " yes", $out, $rc);
			$action_label = $is_tr ? "yeniden başlatıldı" : "restarted";
		}
		if ($rc == 0) {
			$_SESSION["ok_msg"] = sprintf($is_tr ? "%s servisi başarıyla %s." : _("Service %s %s successfully."), htmlspecialchars($srv_name), $action_label);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Servis işlemi sırasında hata: " : _("Error during service action: ")) . implode(" ", $out);
		}
	}
	header("Location: /list/resources/");
	exit();
}

// Action 6: Docker / Backend App Action
if (!empty($_POST["app_action"])) {
	verify_csrf($_POST);
	$app_name = trim($_POST["app_name"] ?? "");
	$app_act = trim($_POST["app_action_type"] ?? "restart");
	if (!empty($app_name)) {
		$v_app = quoteshellarg($app_name);
		if ($app_act === "stop") {
			exec(HESTIA_CMD . "v-stop-docker-app " . $v_app, $out, $rc);
			$action_label = $is_tr ? "durduruldu" : "stopped";
		} elseif ($app_act === "start") {
			exec(HESTIA_CMD . "v-start-docker-app " . $v_app, $out, $rc);
			$action_label = $is_tr ? "başlatıldı" : "started";
		} else {
			exec(HESTIA_CMD . "v-restart-docker-app " . $v_app, $out, $rc);
			$action_label = $is_tr ? "yeniden başlatıldı" : "restarted";
		}
		if ($rc == 0) {
			$_SESSION["ok_msg"] = sprintf($is_tr ? "%s uygulaması %s." : _("App %s %s successfully."), htmlspecialchars($app_name), $action_label);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Uygulama işlemi sırasında hata: " : _("Error during app action: ")) . implode(" ", $out);
		}
	}
	header("Location: /list/resources/");
	exit();
}

// 1. Fetch Governance & Per-Domain Resource Metrics
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

// Merge suspension status from web domains
exec(HESTIA_CMD . "v-list-web-domains-all json", $all_web_output, $w_rc);
$all_web_domains = json_decode(implode("", $all_web_output), true) ?: [];
foreach ($domains as $d_name => &$d_info) {
	if (isset($all_web_domains[$d_name])) {
		$d_info["SUSPENDED"] = $all_web_domains[$d_name]["SUSPENDED"] ?? "no";
		$d_info["SSL"] = $all_web_domains[$d_name]["SSL"] ?? "no";
		$d_info["BACKEND"] = $all_web_domains[$d_name]["BACKEND"] ?? "";
	} else {
		$d_info["SUSPENDED"] = $d_info["SUSPENDED"] ?? "no";
	}
}
unset($d_info);

// 2. Fetch System Services Metrics
exec(HESTIA_CMD . "v-list-sys-services json", $srv_output, $s_rc);
$services = json_decode(implode("", $srv_output), true) ?: [];

// 3. Fetch Docker / Background API Apps
exec(HESTIA_CMD . "v-list-docker-apps json", $docker_output, $d_rc);
$docker_apps = json_decode(implode("", $docker_output), true) ?: [];

// 4. Live System Memory & CPU Summary
$live_ram = [
	"total_mb" => 15974,
	"used_mb" => 3072,
	"free_mb" => 12902,
	"used_pct" => 19.2
];
if (file_exists('/proc/meminfo')) {
	$meminfo = file_get_contents('/proc/meminfo');
	preg_match('/MemTotal:\s+(\d+)/', $meminfo, $mt);
	preg_match('/MemAvailable:\s+(\d+)/', $meminfo, $ma);
	if (!empty($mt[1])) {
		$t_mb = round($mt[1] / 1024);
		$a_mb = !empty($ma[1]) ? round($ma[1] / 1024) : 0;
		$u_mb = max(0, $t_mb - $a_mb);
		$live_ram["total_mb"] = $t_mb;
		$live_ram["used_mb"] = $u_mb;
		$live_ram["free_mb"] = $a_mb;
		$live_ram["used_pct"] = $t_mb > 0 ? round(($u_mb / $t_mb) * 100, 1) : 0;
	}
}

// Render Template
render_page($user, $TAB, "list_resources");
