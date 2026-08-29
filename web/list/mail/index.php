<?php
use function Hestiacp\quoteshellarg\quoteshellarg;
$TAB = "MAIL";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Admin overview: target user comes from the link (?user=...) so an
// administrator can open records of any account directly.
if (($_SESSION["userContext"] ?? "") === "admin" && !empty($_GET["user"])) {
	$user = quoteshellarg($_GET["user"]);
}


// Data & Render page
if (empty($_GET["domain"])) {
	if (is_admin_overview()) {
		$data = list_records_for_all_users("v-list-mail-domains");
	} else {
		exec(HESTIA_CMD . "v-list-mail-domains $user json", $output, $return_var);
		$data = json_decode(implode("", $output), true);
	}
	if ($_SESSION["userSortOrder"] == "name") {
		ksort($data);
	} else {
		$data = array_reverse($data, true);
	}
	unset($output);

	render_page($user, $TAB, "list_mail");
} elseif (!empty($_GET["dns"])) {
	exec(
		HESTIA_CMD . "v-list-mail-domain " . $user . " " . quoteshellarg($_GET["domain"]) . " json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	$data = array_reverse($data, true);
	unset($output);
	exec(HESTIA_CMD . "v-list-user-ips " . $user . " json", $output, $return_var);
	$ips = json_decode(implode("", $output), true);
	$ips = array_reverse($ips, true);
	unset($output);
	exec(
		HESTIA_CMD .
			"v-list-mail-domain-dkim-dns " .
			$user .
			" " .
			quoteshellarg($_GET["domain"]) .
			" json",
		$output,
		$return_var,
	);
	$dkim = json_decode(implode("", $output), true);
	$dkim = array_reverse($dkim, true);
	unset($output);

	render_page($user, $TAB, "list_mail_dns");
} else {
	exec(
		HESTIA_CMD .
			"v-list-mail-accounts " .
			$user .
			" " .
			quoteshellarg($_GET["domain"]) .
			" json",
		$output,
		$return_var,
	);
	$data = json_decode(implode("", $output), true);
	if ($_SESSION["userSortOrder"] == "name") {
		ksort($data);
	} else {
		$data = array_reverse($data, true);
	}
	unset($output);

	render_page($user, $TAB, "list_mail_acc");
}

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
