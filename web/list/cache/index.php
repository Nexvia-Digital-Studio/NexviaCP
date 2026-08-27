<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "CACHE";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$is_admin = (($_SESSION["userContext"] ?? "") === "admin");

// Action 1: Purge Specific Domain Cache (Redis + FastCGI + Proxy)
if (!empty($_POST["purge_domain_cache"])) {
	verify_csrf($_POST);
	$target_user = quoteshellarg($_POST["domain_user"] ?? $user);
	$target_domain = quoteshellarg($_POST["domain_name"] ?? "");
	$cache_type = quoteshellarg($_POST["cache_type"] ?? "all");

	if (!empty($_POST["domain_name"])) {
		exec(HESTIA_CMD . "v-purge-web-domain-cache " . $target_user . " " . $target_domain . " " . $cache_type, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "Önbellek başarıyla temizlendi: " : _("Cache purged successfully: ")) . htmlspecialchars($_POST["domain_name"]);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Önbellek temizlenirken hata oluştu: " : _("Error purging cache: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/cache/");
	exit();
}

// Action 2: Assign / Reassign Dedicated Redis DB to Domain
if (!empty($_POST["assign_redis_db"])) {
	verify_csrf($_POST);
	$target_user = quoteshellarg($_POST["domain_user"] ?? $user);
	$target_domain = quoteshellarg($_POST["domain_name"] ?? "");
	$redis_db = quoteshellarg($_POST["redis_db"] ?? "auto");

	if (!empty($_POST["domain_name"])) {
		exec(HESTIA_CMD . "v-add-web-domain-redis " . $target_user . " " . $target_domain . " " . $redis_db . " no", $output, $return_var);
		if ($return_var == 0) {
			$assigned_msg = ($_POST["redis_db"] === "auto" || empty($_POST["redis_db"])) ? ($is_tr ? "Otomatik DB" : _("Auto DB")) : "DB " . htmlspecialchars($_POST["redis_db"]);
			$_SESSION["ok_msg"] = ($is_tr ? "Redis veritabanı ayrıldı ve .env dosyasına enjekte edildi: " : _("Redis DB assigned and injected into .env: ")) . htmlspecialchars($_POST["domain_name"]) . " -> " . $assigned_msg;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Redis veritabanı atanırken hata oluştu: " : _("Error assigning Redis DB: ")) . implode(" ", $output);
		}
	}
	header("Location: /list/cache/");
	exit();
}

// Action 3: Purge All System Caches (Redis FLUSHALL + Nginx microcache) [Admin only]
if (!empty($_POST["purge_all_caches"])) {
	verify_csrf($_POST);
	if ($is_admin) {
		exec("redis-cli flushall async 2>/dev/null || redis-cli flushall 2>/dev/null", $r_out, $r_code);
		exec("find /var/cache/nginx/micro -mindepth 2 -type f -delete 2>/dev/null; find /var/cache/nginx/proxy -mindepth 2 -type f -delete 2>/dev/null", $n_out, $n_code);
		$_SESSION["ok_msg"] = $is_tr ? "Tüm Redis veritabanları ve Nginx FastCGI microcache dosyaları başarıyla temizlendi." : _("All Redis databases and Nginx FastCGI microcache files flushed successfully.");
	}
	header("Location: /list/cache/");
	exit();
}

// Action 4: Flush Single Redis DB (0 - 15) [Admin only]
if (!empty($_POST["flush_single_db"])) {
	verify_csrf($_POST);
	$db_idx = (int)($_POST["db_index"] ?? 0);
	if ($db_idx >= 0 && $db_idx <= 15) {
		exec("redis-cli -n " . $db_idx . " flushdb async 2>/dev/null || redis-cli -n " . $db_idx . " flushdb 2>/dev/null", $f_out, $f_code);
		$_SESSION["ok_msg"] = ($is_tr ? "Redis DB " : _("Redis DB ")) . $db_idx . ($is_tr ? " verileri sıfırlandı." : _(" keys flushed."));
	}
	header("Location: /list/cache/");
	exit();
}

// Action 5: Toggle FastCGI Cache
if (!empty($_POST["toggle_fastcgi"])) {
	verify_csrf($_POST);
	$target_user = quoteshellarg($_POST["domain_user"] ?? $user);
	$target_domain = quoteshellarg($_POST["domain_name"] ?? "");
	$current_status = $_POST["current_status"] ?? "no";
	$duration = quoteshellarg($_POST["duration"] ?? "10m");

	if (!empty($_POST["domain_name"])) {
		if ($current_status === "yes") {
			exec(HESTIA_CMD . "v-delete-fastcgi-cache " . $target_user . " " . $target_domain . " yes", $t_out, $t_code);
			$_SESSION["ok_msg"] = ($is_tr ? "FastCGI önbelleği kapatıldı: " : _("FastCGI cache disabled: ")) . htmlspecialchars($_POST["domain_name"]);
		} else {
			exec(HESTIA_CMD . "v-add-fastcgi-cache " . $target_user . " " . $target_domain . " " . $duration . " yes", $t_out, $t_code);
			$_SESSION["ok_msg"] = ($is_tr ? "FastCGI önbelleği aktif edildi: " : _("FastCGI cache enabled: ")) . htmlspecialchars($_POST["domain_name"]);
		}
	}
	header("Location: /list/cache/");
	exit();
}

// Action 6: Enable Slow Query Log [Admin only]
if (!empty($_POST["enable_slow_log"])) {
	verify_csrf($_POST);
	if ($is_admin) {
		exec(HESTIA_CMD . "v-change-sys-mysql-slowlog on 1", $s_out, $s_code);
		if ($s_code == 0) {
			$_SESSION["ok_msg"] = $is_tr ? "MariaDB/MySQL yavaş sorgu günlüğü (Slow Query Log) başarıyla aktif edildi (>1.0s)." : _("MariaDB/MySQL slow query log activated successfully (>1.0s).");
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Slow query log aktifleştirilirken hata oluştu: " : _("Error enabling slow query log: ")) . implode(" ", $s_out);
		}
	}
	header("Location: /list/cache/");
	exit();
}

// Fetch Performance & Cache Governance Data
exec(HESTIA_CMD . "v-list-cache-governance json", $cache_output, $return_var);
$cache_data = json_decode(implode("", $cache_output), true) ?: [
	"summary" => [],
	"redis_databases" => [],
	"domains" => [],
	"slow_queries" => []
];

$summary = $cache_data["summary"] ?? [];
$redis_dbs = $cache_data["redis_databases"] ?? [];
$domains = $cache_data["domains"] ?? [];
$slow_queries = $cache_data["slow_queries"] ?? [];

// Filter domains if not admin
if (!$is_admin) {
	$filtered_domains = [];
	foreach ($domains as $dname => $dinfo) {
		if (($dinfo["USER"] ?? "") === $user) {
			$filtered_domains[$dname] = $dinfo;
		}
	}
	$domains = $filtered_domains;
}

// Render Page Template
render_page($user, $TAB, "list_cache");
