<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "CACHE";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

$is_tr = (($_SESSION['language'] ?? '') === 'tr' || ($_SESSION['LANGUAGE'] ?? '') === 'tr');
$is_admin = (($_SESSION["userContext"] ?? "") === "admin");

/**
 * Ownership guard for the domain-scoped cache actions (1, 2 and 5).
 *
 * Non-admin sessions are always pinned to their own account, admins may act
 * on any user's domain. In both cases the requested domain must really exist
 * in the resolved owner's web domain list before any v-* command runs.
 *
 * @return array [bool $allowed, string $owner, string $domain]
 */
function cache_validate_domain_target($posted_user, $posted_domain, $is_admin) {
	$owner = (string)($posted_user ?? "");
	if ($owner === "" || (!$is_admin && $owner !== $_SESSION["user"])) {
		$owner = empty($_SESSION["look"]) ? $_SESSION["user"] : $_SESSION["look"];
	}

	$domain = trim((string)($posted_domain ?? ""));
	if ($domain === "") {
		return [false, $owner, ""];
	}

	exec(HESTIA_CMD . "v-list-web-domains " . quoteshellarg($owner) . " json", $dom_out, $dom_rv);
	$dom_list = json_decode(implode("", $dom_out), true);
	if (!is_array($dom_list) || !array_key_exists($domain, $dom_list)) {
		return [false, $owner, $domain];
	}

	return [true, $owner, $domain];
}

// Action 1: Purge Specific Domain Cache (Redis + FastCGI + Proxy)
if (!empty($_POST["purge_domain_cache"])) {
	verify_csrf($_POST);
	[$domain_allowed, $owner, $domain_plain] = cache_validate_domain_target($_POST["domain_user"] ?? null, $_POST["domain_name"] ?? "", $is_admin);

	if ($domain_allowed) {
		$target_user = quoteshellarg($owner);
		$target_domain = quoteshellarg($domain_plain);
		$cache_type = quoteshellarg($_POST["cache_type"] ?? "all");

		exec(HESTIA_CMD . "v-purge-web-domain-cache " . $target_user . " " . $target_domain . " " . $cache_type, $output, $return_var);
		if ($return_var == 0) {
			$_SESSION["ok_msg"] = ($is_tr ? "Önbellek başarıyla temizlendi: " : _("Cache purged successfully: ")) . htmlspecialchars($domain_plain);
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Önbellek temizlenirken hata oluştu: " : _("Error purging cache: ")) . implode(" ", $output);
		}
	} elseif ($domain_plain !== "") {
		$_SESSION["error_msg"] = ($is_tr ? "Erişim reddedildi: " : _("Access denied: ")) . htmlspecialchars($domain_plain) . ($is_tr ? " bu hesaba ait bir web alan adı değil." : _(" is not a web domain of this account."));
	}
	header("Location: /list/cache/");
	exit();
}

// Action 2: Assign / Reassign Dedicated Redis DB to Domain
if (!empty($_POST["assign_redis_db"])) {
	verify_csrf($_POST);
	[$domain_allowed, $owner, $domain_plain] = cache_validate_domain_target($_POST["domain_user"] ?? null, $_POST["domain_name"] ?? "", $is_admin);

	if ($domain_allowed) {
		$target_user = quoteshellarg($owner);
		$target_domain = quoteshellarg($domain_plain);
		$redis_db = quoteshellarg($_POST["redis_db"] ?? "auto");

		exec(HESTIA_CMD . "v-add-web-domain-redis " . $target_user . " " . $target_domain . " " . $redis_db . " no", $output, $return_var);
		if ($return_var == 0) {
			$assigned_msg = ($_POST["redis_db"] === "auto" || empty($_POST["redis_db"])) ? ($is_tr ? "Otomatik DB" : _("Auto DB")) : "DB " . htmlspecialchars($_POST["redis_db"]);
			$_SESSION["ok_msg"] = ($is_tr ? "Redis veritabanı ayrıldı ve .env dosyasına enjekte edildi: " : _("Redis DB assigned and injected into .env: ")) . htmlspecialchars($domain_plain) . " -> " . $assigned_msg;
		} else {
			$_SESSION["error_msg"] = ($is_tr ? "Redis veritabanı atanırken hata oluştu: " : _("Error assigning Redis DB: ")) . implode(" ", $output);
		}
	} elseif ($domain_plain !== "") {
		$_SESSION["error_msg"] = ($is_tr ? "Erişim reddedildi: " : _("Access denied: ")) . htmlspecialchars($domain_plain) . ($is_tr ? " bu hesaba ait bir web alan adı değil." : _(" is not a web domain of this account."));
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
	if ($is_admin) {
		$db_idx = (int)($_POST["db_index"] ?? 0);
		if ($db_idx >= 0 && $db_idx <= 15) {
			exec("redis-cli -n " . $db_idx . " flushdb async 2>/dev/null || redis-cli -n " . $db_idx . " flushdb 2>/dev/null", $f_out, $f_code);
			$_SESSION["ok_msg"] = ($is_tr ? "Redis DB " : _("Redis DB ")) . $db_idx . ($is_tr ? " verileri sıfırlandı." : _(" keys flushed."));
		}
	}
	header("Location: /list/cache/");
	exit();
}

// Action 5: Toggle FastCGI Cache
if (!empty($_POST["toggle_fastcgi"])) {
	verify_csrf($_POST);
	[$domain_allowed, $owner, $domain_plain] = cache_validate_domain_target($_POST["domain_user"] ?? null, $_POST["domain_name"] ?? "", $is_admin);
	$current_status = $_POST["current_status"] ?? "no";

	if ($domain_allowed) {
		$target_user = quoteshellarg($owner);
		$target_domain = quoteshellarg($domain_plain);
		$duration = quoteshellarg($_POST["duration"] ?? "10m");

		if ($current_status === "yes") {
			exec(HESTIA_CMD . "v-delete-fastcgi-cache " . $target_user . " " . $target_domain . " yes", $t_out, $t_code);
			$_SESSION["ok_msg"] = ($is_tr ? "FastCGI önbelleği kapatıldı: " : _("FastCGI cache disabled: ")) . htmlspecialchars($domain_plain);
		} else {
			exec(HESTIA_CMD . "v-add-fastcgi-cache " . $target_user . " " . $target_domain . " " . $duration . " yes", $t_out, $t_code);
			$_SESSION["ok_msg"] = ($is_tr ? "FastCGI önbelleği aktif edildi: " : _("FastCGI cache enabled: ")) . htmlspecialchars($domain_plain);
		}
	} elseif ($domain_plain !== "") {
		$_SESSION["error_msg"] = ($is_tr ? "Erişim reddedildi: " : _("Access denied: ")) . htmlspecialchars($domain_plain) . ($is_tr ? " bu hesaba ait bir web alan adı değil." : _(" is not a web domain of this account."));
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

// Action 7: Safely Apply Suggested SQL Index to Database
if (!empty($_POST["apply_sql_index"])) {
	verify_csrf($_POST);
	$target_db = trim($_POST["target_db"] ?? "");
	$target_user = trim($_POST["target_user"] ?? $user);
	$index_sql = trim($_POST["index_sql"] ?? "");

	if ($is_admin || $target_user === $user) {
		if (!empty($target_db) && !empty($index_sql)) {
			$cmd = HESTIA_CMD . "v-apply-mysql-index " . quoteshellarg($target_user) . " " . quoteshellarg($target_db) . " " . quoteshellarg($index_sql);
			exec($cmd, $a_out, $a_code);
			if ($a_code == 0) {
				$_SESSION["ok_msg"] = $is_tr ? ("İndeks başarıyla uygulandı: " . implode(" ", $a_out)) : (_("Index created successfully: ") . implode(" ", $a_out));
			} else {
				$_SESSION["error_msg"] = $is_tr ? ("İndeks oluşturulurken hata: " . implode(" ", $a_out)) : (_("Error creating index: ") . implode(" ", $a_out));
			}
		} else {
			$_SESSION["error_msg"] = $is_tr ? "Veritabanı veya indeks komutu eksik." : _("Missing database or SQL statement.");
		}
	} else {
		$_SESSION["error_msg"] = $is_tr ? "Erişim reddedildi." : _("Access denied.");
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
