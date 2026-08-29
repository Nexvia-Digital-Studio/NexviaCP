<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "TEMPLATES";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Admin only
if (($_SESSION["userContext"] ?? "") !== "admin") {
	header("Location: /list/web/");
	exit();
}

// Fetch templates
exec(HESTIA_CMD . "v-list-sys-templates all json", $output, $return_var);
$templates_data = json_decode(implode("", $output), true);
unset($output);

if (!is_array($templates_data)) {
	$templates_data = [];
}

// Render page
render_page($user, $TAB, "list_templates");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
