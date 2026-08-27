<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "WAF";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$current_user = $_SESSION['user'] ?? 'admin';
$is_admin = (($_SESSION['userContext'] ?? '') === 'admin');

// Security Guard: the WAF shield exposes firewall-wide controls, so only
// administrators may reach this page at all.
if (!$is_admin) {
	header("Location: /list/user/");
	exit();
}

// Defense in depth: even though the gate above makes this page admin-only,
// never let a non-admin context target another account through a forged user
// field.
if (!$is_admin) {
	foreach (["waf_user", "geoip_user", "scan_user"] as $user_field) {
		if (isset($_POST[$user_field]) && $_POST[$user_field] !== $current_user) {
			$_POST[$user_field] = $current_user;
		}
	}
}

// -------------------------------------------------------------
// POST Action 1: Change Domain WAF Mode
// -------------------------------------------------------------
if (!empty($_POST["action_waf_mode"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["waf_user"] ?? $current_user);
	$v_domain = quoteshellarg($_POST["waf_domain"] ?? "");
	$v_mode = quoteshellarg(strtolower($_POST["waf_mode"] ?? "block"));

	if (!empty($_POST["waf_domain"])) {
		exec(HESTIA_CMD . "v-add-web-domain-waf " . $v_user . " " . $v_domain . " " . $v_mode . " 'yes'", $output, $return_var);
		if ($return_var === 0) {
			$mode_label = strtoupper($_POST["waf_mode"] ?? "BLOCK");
			$_SESSION["ok_msg"] = ($is_tr ? "WAF kalkan modu güncellendi: " : _("WAF shield mode updated: ")) . htmlspecialchars($_POST["waf_domain"]) . " -> " . $mode_label;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "WAF modu değiştirilirken hata oluştu: " : _("Error updating WAF mode: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// POST Action 2: Configure GeoIP & IP Access Rules
// -------------------------------------------------------------
if (!empty($_POST["action_geoip"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["geoip_user"] ?? $current_user);
	$v_domain = quoteshellarg($_POST["geoip_domain"] ?? "");
	$v_action = quoteshellarg($_POST["geoip_action"] ?? "off");
	$v_countries = quoteshellarg($_POST["geoip_countries"] ?? "");
	$v_ips = quoteshellarg($_POST["geoip_ips"] ?? "");

	if (!empty($_POST["geoip_domain"])) {
		exec(HESTIA_CMD . "v-set-web-domain-geoip " . $v_user . " " . $v_domain . " " . $v_action . " " . $v_countries . " " . $v_ips . " 'yes'", $output, $return_var);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "GeoIP ve IP erişim kuralları uygulandı: " : _("GeoIP and IP access rules applied: ")) . htmlspecialchars($_POST["geoip_domain"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "GeoIP kuralları kaydedilirken hata: " : _("Error saving GeoIP rules: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// POST Action 3: Launch Malware Scan (Single Domain)
// -------------------------------------------------------------
if (!empty($_POST["action_scan_domain"])) {
	verify_csrf($_POST);
	$v_user = quoteshellarg($_POST["scan_user"] ?? $current_user);
	$v_domain = quoteshellarg($_POST["scan_domain"] ?? "");
	$v_quarantine = quoteshellarg(!empty($_POST["quarantine"]) ? "yes" : "no");

	if (!empty($_POST["scan_domain"])) {
		exec(HESTIA_CMD . "v-scan-web-domain-malware " . $v_user . " " . $v_domain . " " . $v_quarantine . " 'json'", $output, $return_var);
		$res_str = implode("", $output);
		$res_json = json_decode($res_str, true);

		if (is_array($res_json) && isset($res_json['threats_found'])) {
			$t_count = (int)$res_json['threats_found'];
			$q_count = (int)($res_json['quarantined'] ?? 0);
			if ($t_count > 0) {
				$_SESSION["error_msg"] = ($is_tr ? "⚠️ Tarama Tamamlandı: " : _("⚠️ Scan Complete: ")) . htmlspecialchars($_POST["scan_domain"]) . " (" . $t_count . ($is_tr ? " tehdit bulundu" : " threats found") . ($q_count > 0 ? ", " . $q_count . ($is_tr ? " dosya karantinaya alındı" : " quarantined") : "") . ")";
			} else {
				$_SESSION["ok_msg"] = ($is_tr ? "✅ Güvenlik Taraması Temiz: " : _("✅ Security Scan Clean: ")) . htmlspecialchars($_POST["scan_domain"]) . " (" . ($res_json['scanned_files'] ?? 0) . ($is_tr ? " dosya tarandı" : " files scanned") . ")";
			}
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Tarama sırasında hata oluştu: " : _("Error during malware scan: ")) . $res_str;
		}
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// POST Action 4: Scan All Domains
// -------------------------------------------------------------
if (!empty($_POST["action_scan_all"])) {
	verify_csrf($_POST);
	$v_quarantine = quoteshellarg(!empty($_POST["quarantine_all"]) ? "yes" : "no");
	
	// Fetch all domains
	exec(HESTIA_CMD . "v-list-sys-security-threats json", $threat_out, $threat_ret);
	$all_data = json_decode(implode("", $threat_out), true);
	$domains_to_scan = $all_data['domains'] ?? [];

	$scanned_domains = 0;
	$total_found = 0;
	foreach ($domains_to_scan as $dname => $dinfo) {
		$u_name = quoteshellarg($dinfo['user'] ?? $current_user);
		$d_name = quoteshellarg($dname);
		$s_out = [];
		exec(HESTIA_CMD . "v-scan-web-domain-malware " . $u_name . " " . $d_name . " " . $v_quarantine . " 'json'", $s_out, $s_ret);
		$sj = json_decode(implode("", $s_out), true);
		if (is_array($sj)) {
			$scanned_domains++;
			$total_found += (int)($sj['threats_found'] ?? 0);
		}
	}

	if ($total_found > 0) {
		$_SESSION["error_msg"] = ($is_tr ? "Tüm siteler tarandı. Toplam $total_found tehdit tespit edildi!" : _("All sites scanned. Total $total_found threats detected!"));
	} else {
		$_SESSION["ok_msg"] = ($is_tr ? "Tüm web siteleri ($scanned_domains alan adı) başarıyla tarandı. Tehdit bulunamadı." : _("All web domains ($scanned_domains sites) scanned successfully. No threats found."));
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// POST Action 5: Unban IP
// -------------------------------------------------------------
if (!empty($_POST["action_unban_ip"])) {
	verify_csrf($_POST);
	$v_ip = quoteshellarg($_POST["banned_ip"] ?? "");
	$v_chain = quoteshellarg($_POST["ban_chain"] ?? "HESTIA");

	if (!empty($_POST["banned_ip"])) {
		exec(HESTIA_CMD . "v-delete-firewall-ban " . $v_ip . " " . $v_chain, $output, $return_var);
		if ($return_var === 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "IP engel kaldırıldı: " : _("IP unbanned: ")) . htmlspecialchars($_POST["banned_ip"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Engel kaldırılamadı: " : _("Failed to unban IP: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// POST Action 6: Manage Global Security & PR Whitelist
// -------------------------------------------------------------
if (!empty($_POST["action_global_whitelist"])) {
	verify_csrf($_POST);
	$v_act = quoteshellarg($_POST["whitelist_act"] ?? "set");
	$v_ips = quoteshellarg($_POST["whitelist_ips"] ?? "");

	exec(HESTIA_CMD . "v-set-sys-global-whitelist " . $v_act . " " . $v_ips, $output, $return_var);
	if ($return_var === 0) {
		$_SESSION["ok_msg"] = ($is_tr ? "Genel Güvenlik ve PR Whitelist IP listesi güncellendi." : _("Global Security and PR Whitelist updated."));
	} else {
		$_SESSION["error_msg"] = implode(" ", $output);
	}
	header("Location: /list/waf/");
	exit();
}

// -------------------------------------------------------------
// Fetch Aggregated Security Threat Data
// -------------------------------------------------------------
// Client IP for the whitelist helper: use the validated helper (only trusts
// Cloudflare headers when the peer really is a Cloudflare IP), never raw
// spoofable X-Forwarded-For / CF-Connecting-IP values.
$detected_client_ip = function_exists("get_real_user_ip") ? get_real_user_ip() : ($_SERVER["REMOTE_ADDR"] ?? "");
if ($detected_client_ip === "") {
	$detected_client_ip = $_SERVER["REMOTE_ADDR"] ?? "127.0.0.1";
}

exec(HESTIA_CMD . "v-set-sys-global-whitelist list", $gw_output, $return_var);
$global_whitelist_ips = array_filter(array_map('trim', $gw_output));

$target_user_param = $is_admin ? "all" : quoteshellarg($current_user);
exec(HESTIA_CMD . "v-list-sys-security-threats " . $target_user_param . " json", $sec_output, $return_var);
$security_data = json_decode(implode("", $sec_output), true) ?: [
	"summary" => [
		"total_monitored_domains" => 0,
		"active_waf_domains" => 0,
		"active_geoip_domains" => 0,
		"total_threats_detected" => 0,
		"total_quarantined_files" => 0,
		"total_banned_ips" => 0,
		"total_waf_blocks" => 0,
		"shield_health_score" => 100
	],
	"domains" => [],
	"malware_scans" => [],
	"recent_threat_events" => [],
	"banned_ips" => []
];

$summary = $security_data["summary"] ?? [];
$domains = $security_data["domains"] ?? [];
$malware_scans = $security_data["malware_scans"] ?? [];
$recent_threat_events = $security_data["recent_threat_events"] ?? [];
$banned_ips = $security_data["banned_ips"] ?? [];

// Render Template
render_page($user, $TAB, "list_waf");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
