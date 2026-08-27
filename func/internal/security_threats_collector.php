<?php
/**
 * NexviaCP Enterprise Threat Shield - System Security Threats Aggregator
 * Gathers WAF block events, malware scan reports, GeoIP configurations, and banned IPs.
 */

declare(strict_types=1);

$target_user = $argv[1] ?? 'all';
$hestia_path = getenv('HESTIA') ?: '/usr/local/hestia';
$users_dir = "{$hestia_path}/data/users";

$users = [];
if ($target_user === 'all' || empty($target_user)) {
	if (is_dir($users_dir)) {
		$users = array_diff(scandir($users_dir) ?: [], ['.', '..']);
	}
} else {
	if (is_dir("{$users_dir}/{$target_user}")) {
		$users = [$target_user];
	}
}

$domains_list = [];
$malware_scans = [];
$total_threats_detected = 0;
$total_quarantined_files = 0;
$active_waf_domains = 0;
$active_geoip_domains = 0;

foreach ($users as $user) {
	$web_conf = "{$users_dir}/{$user}/web.conf";
	if (!file_exists($web_conf)) {
		continue;
	}

	$lines = file($web_conf, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
	foreach ($lines as $line) {
		$line = trim($line);
		if (empty($line) || str_starts_with($line, '#')) {
			continue;
		}

		// Parse key='value' pairs
		preg_match_all("/([A-Z0-9_]+)='([^']*)'/", $line, $matches, PREG_SET_ORDER);
		$row = [];
		foreach ($matches as $m) {
			$row[$m[1]] = $m[2];
		}

		$domain = $row['DOMAIN'] ?? '';
		if (empty($domain)) {
			continue;
		}

		$waf_mode = $row['WAF'] ?? 'off';
		$waf_updated = $row['WAF_UPDATED'] ?? '';
		$geoip_mode = $row['GEOIP_MODE'] ?? 'off';
		$geoip_countries = $row['GEOIP_COUNTRIES'] ?? '';
		$geoip_ips = $row['GEOIP_IPS'] ?? '';
		$last_scan = $row['MALWARE_LAST_SCAN'] ?? '';
		$threats_count = (int)($row['MALWARE_THREATS_COUNT'] ?? 0);

		// Check actual conf files presence
		$waf_file = "/home/{$user}/conf/web/{$domain}/nginx.conf_waf";
		$is_waf_active = file_exists($waf_file) && $waf_mode !== 'off';

		$geoip_file = "/home/{$user}/conf/web/{$domain}/nginx.conf_geoip";
		$is_geoip_active = file_exists($geoip_file) && $geoip_mode !== 'off';

		if ($is_waf_active) {
			$active_waf_domains++;
		}
		if ($is_geoip_active) {
			$active_geoip_domains++;
		}

		$total_threats_detected += $threats_count;

		$domains_list[$domain] = [
			"domain" => $domain,
			"user" => $user,
			"waf_mode" => $is_waf_active ? $waf_mode : ($waf_mode !== 'off' ? $waf_mode : 'off'),
			"waf_active" => $is_waf_active,
			"waf_updated" => $waf_updated,
			"geoip_mode" => $is_geoip_active ? $geoip_mode : ($geoip_mode !== 'off' ? $geoip_mode : 'off'),
			"geoip_active" => $is_geoip_active,
			"geoip_countries" => $geoip_countries,
			"geoip_ips" => $geoip_ips,
			"last_malware_scan" => $last_scan,
			"threats_found" => $threats_count,
			"ssl" => $row['SSL'] ?? 'no',
			"suspended" => $row['SUSPENDED'] ?? 'no'
		];

		// Check malware scan report
		$report_file = "{$users_dir}/{$user}/malware/{$domain}.json";
		if (file_exists($report_file)) {
			$report_content = @file_get_contents($report_file);
			$report_json = json_decode($report_content ?: '', true);
			if (is_array($report_json)) {
				$malware_scans[$domain] = $report_json;
				$total_quarantined_files += (int)($report_json['quarantined'] ?? 0);
			}
		}
	}
}

// Parse recent threat events from Nginx domain logs
$recent_threat_events = [];
$nginx_domains_log_dir = '/var/log/nginx/domains';
$log_files = [];

if (is_dir($nginx_domains_log_dir)) {
	$log_files = glob("{$nginx_domains_log_dir}/*.error.log") ?: [];
	$access_logs = glob("{$nginx_domains_log_dir}/*.log") ?: [];
	$log_files = array_merge($log_files, $access_logs);
}

// Fallback to main nginx logs if domains log dir is empty or doesn't exist
if (empty($log_files) && file_exists('/var/log/nginx/error.log')) {
	$log_files[] = '/var/log/nginx/error.log';
}

$event_count = 0;
$total_blocked_requests = 0;

foreach ($log_files as $lfile) {
	if ($event_count >= 50) {
		break;
	}
	if (!is_readable($lfile) || filesize($lfile) === 0) {
		continue;
	}

	$lbasename = basename($lfile);
	$domain_from_log = explode('.', $lbasename)[0] ?? 'system';

	// Read last 150 lines
	$lines = [];
	$fp = @popen("tail -n 150 " . escapeshellarg($lfile) . " 2>/dev/null", 'r');
	if ($fp) {
		while (($line = fgets($fp)) !== false) {
			$lines[] = trim($line);
		}
		pclose($fp);
	}

	foreach (array_reverse($lines) as $log_line) {
		if ($event_count >= 50) break;

		// Detect 403 Forbidden or WAF match
		if (
			str_contains($log_line, ' 403 ') ||
			str_contains($log_line, 'access forbidden') ||
			str_contains($log_line, 'WAF') ||
			str_contains($log_line, 'SQL_INJECTION') ||
			str_contains($log_line, 'CROSS_SITE_SCRIPTING') ||
			str_contains($log_line, 'PATH_TRAVERSAL') ||
			str_contains($log_line, 'BAD_BOT')
		) {
			$total_blocked_requests++;

			// Parse IP and timestamp
			$ip = '-';
			if (preg_match('/(?:client:\s*|^\s*)([0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3})/', $log_line, $ip_m)) {
				$ip = $ip_m[1];
			}

			$time = date('Y-m-d H:i:s');
			if (preg_match('/\[(.*?)\]/', $log_line, $t_m)) {
				$time = $t_m[1];
			} elseif (preg_match('/^(\d{4}\/\d{2}\/\d{2} \d{2}:\d{2}:\d{2})/', $log_line, $t_m)) {
				$time = $t_m[1];
			}

			// Identify threat type
			$threat_type = 'Access Blocked (403)';
			if (stripos($log_line, 'select') !== false || stripos($log_line, 'union') !== false || stripos($log_line, 'SQL_INJECTION') !== false) {
				$threat_type = 'SQL Injection Attempt';
			} elseif (stripos($log_line, '<script') !== false || stripos($log_line, 'CROSS_SITE_SCRIPTING') !== false) {
				$threat_type = 'XSS Script Injection';
			} elseif (stripos($log_line, 'etc/passwd') !== false || stripos($log_line, '..') !== false || stripos($log_line, 'TRAVERSAL') !== false) {
				$threat_type = 'Path Traversal / LFI';
			} elseif (stripos($log_line, '.env') !== false || stripos($log_line, '.git') !== false) {
				$threat_type = 'Sensitive Environment Probe';
			} elseif (stripos($log_line, 'sqlmap') !== false || stripos($log_line, 'nikto') !== false || stripos($log_line, 'scanner') !== false) {
				$threat_type = 'Automated Vulnerability Scanner';
			}

			$recent_threat_events[] = [
				"timestamp" => $time,
				"domain" => $domain_from_log,
				"client_ip" => $ip,
				"threat_type" => $threat_type,
				"action" => "BLOCKED (403 Forbidden)",
				"raw" => substr($log_line, 0, 160)
			];
			$event_count++;
		}
	}
}

// Banned IPs retrieval
$banned_ips = [];
$ban_cmd = "{$hestia_path}/bin/v-list-firewall-ban json 2>/dev/null";
$ban_out = [];
@exec($ban_cmd, $ban_out, $ban_ret);
if ($ban_ret === 0 && !empty($ban_out)) {
	$banned_json = json_decode(implode('', $ban_out), true);
	if (is_array($banned_json)) {
		$banned_ips = $banned_json;
	}
}

$summary = [
	"total_monitored_domains" => count($domains_list),
	"active_waf_domains" => $active_waf_domains,
	"active_geoip_domains" => $active_geoip_domains,
	"total_threats_detected" => $total_threats_detected,
	"total_quarantined_files" => $total_quarantined_files,
	"total_banned_ips" => count($banned_ips),
	"total_waf_blocks" => $total_blocked_requests,
	"shield_health_score" => count($domains_list) > 0 ? round(($active_waf_domains / count($domains_list)) * 100) : 100
];

$output = [
	"status" => "success",
	"generated_at" => date('Y-m-d H:i:s'),
	"summary" => $summary,
	"domains" => $domains_list,
	"malware_scans" => $malware_scans,
	"recent_threat_events" => $recent_threat_events,
	"banned_ips" => $banned_ips
];

echo json_encode($output, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
exit(0);
