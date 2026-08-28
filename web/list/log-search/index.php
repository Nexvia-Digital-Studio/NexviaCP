<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "LOG_SEARCH";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// This page is open to non-admin users: the scope below guarantees they
// can only ever search their own domains (enforced again inside
// v-search-sys-logs via the USER argument).
$is_admin = ($_SESSION["userContext"] === "admin");
// When an admin impersonates a user ($_SESSION["look"]), the page acts
// as that user, exactly like the web-log page does.
$all_users_scope = $is_admin && empty($_SESSION["look"]);
$eff_user = empty($_SESSION["look"]) ? ($_SESSION["user"] ?? "") : $_SESSION["look"];

/**
 * Fetch the web domains owned by a user (v-list-web-domains only ever
 * returns the domains of the requested user, so this doubles as the
 * ownership check for the domain selector).
 */
function log_search_fetch_domains(string $owner): array {
	$domains = [];
	exec(
		HESTIA_CMD . "v-list-web-domains " . quoteshellarg($owner) . " json",
		$out,
		$return_var,
	);
	if ($return_var === 0) {
		$data = json_decode(implode("", $out), true);
		if (is_array($data)) {
			foreach (array_keys($data) as $domain) {
				$domain = preg_replace('/[^a-zA-Z0-9._-]/', "", (string)$domain);
				if ($domain !== "" && !str_contains($domain, "..") && !in_array($domain, $domains, true)) {
					$domains[] = $domain;
				}
			}
		}
	}
	unset($out, $return_var);
	return $domains;
}

// Build the domain list for the selector:
// - admins (not impersonating): every user's domains, grouped by user
// - everyone else: only the acting user's own domains
$log_search_domains = [];
if ($all_users_scope) {
	exec(HESTIA_CMD . "v-list-users json", $ls_users_out, $ls_users_rc);
	$ls_users = json_decode(implode("", $ls_users_out), true);
	unset($ls_users_out, $ls_users_rc);
	if (is_array($ls_users)) {
		foreach (array_keys($ls_users) as $ls_user) {
			$ls_user = preg_replace('/[^a-zA-Z0-9._-]/', "", (string)$ls_user);
			if ($ls_user === "" || str_contains($ls_user, "..")) {
				continue;
			}
			$ls_domains = log_search_fetch_domains($ls_user);
			if (!empty($ls_domains)) {
				$log_search_domains[$ls_user] = $ls_domains;
			}
		}
	}
} else {
	$ls_domains = log_search_fetch_domains($eff_user);
	if (!empty($ls_domains)) {
		$log_search_domains[$eff_user] = $ls_domains;
	}
}
$log_search_all_users_scope = $all_users_scope;

// Search form input (GET based: no state is changed, no CSRF needed)
$log_search_q = trim((string)($_GET["q"] ?? ""));
$log_search_type = (string)($_GET["type"] ?? "all");
if (!in_array($log_search_type, ["access", "error", "all"], true)) {
	$log_search_type = "all";
}
$ls_limit_raw = (string)($_GET["limit"] ?? "100");
$log_search_limit = ctype_digit($ls_limit_raw) ? (int)$ls_limit_raw : 100;
if ($log_search_limit < 1 || $log_search_limit > 1000) {
	$log_search_limit = 100;
}
$log_search_domain = preg_replace('/[^a-zA-Z0-9._-]/', "", (string)($_GET["domain"] ?? "all"));
if ($log_search_domain === "" || str_contains($log_search_domain, "..")) {
	$log_search_domain = "all";
}

// Defense in depth: only allow domains that appeared in the selector
// list built above (own domains for users, known domains for admins)
$ls_known_domains = [];
foreach ($log_search_domains as $ls_group) {
	foreach ($ls_group as $ls_domain) {
		$ls_known_domains[] = $ls_domain;
	}
}
if ($log_search_domain !== "all" && !in_array($log_search_domain, $ls_known_domains, true)) {
	$log_search_domain = "all";
}

// Run the search
$log_search_error = "";
$log_search_done = false;
$log_search_results = [];
$log_search_total = 0;
$log_search_truncated = false;

if ($log_search_q !== "" && strlen($log_search_q) > 256) {
	$log_search_error = __tr(
		"Search pattern is too long (max 256 characters).",
		"Arama terimi çok uzun (en fazla 256 karakter).",
	);
	$log_search_q = "";
} elseif ($log_search_q !== "") {
	// Non-admins (and impersonating admins) are pinned to their own
	// username; only plain admins may search every user's domains.
	$ls_scope_user = $all_users_scope ? "all" : $eff_user;
	exec(
		HESTIA_CMD . "v-search-sys-logs " .
			quoteshellarg($log_search_q) . " " .
			quoteshellarg($ls_scope_user) . " " .
			quoteshellarg($log_search_domain) . " " .
			quoteshellarg($log_search_type) . " " .
			quoteshellarg((string)$log_search_limit) . " json",
		$ls_out,
		$ls_rc,
	);
	if ($ls_rc === 0) {
		$ls_data = json_decode(implode("", $ls_out), true);
		if (is_array($ls_data)) {
			$log_search_results = isset($ls_data["results"]) && is_array($ls_data["results"])
				? $ls_data["results"]
				: [];
			$log_search_total = isset($ls_data["total"]) ? (int)$ls_data["total"] : count($log_search_results);
			$log_search_truncated = !empty($ls_data["truncated"]);
		} else {
			$log_search_error = __tr(
				"Log search failed: unexpected command output.",
				"Log araması başarısız oldu: beklenmeyen komut çıktısı.",
			);
		}
	} else {
		$ls_error = trim(implode(" ", $ls_out));
		$log_search_error = $ls_error !== ""
			? $ls_error
			: __tr("Log search failed.", "Log araması başarısız oldu.");
		unset($ls_error);
	}
	unset($ls_out);
	$log_search_done = true;
}

// Render page
render_page($user, $TAB, "list_log_search");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
