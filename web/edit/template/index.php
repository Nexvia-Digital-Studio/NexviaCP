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

$category = $_GET["category"] ?? "web/nginx";
$name = $_GET["name"] ?? "default";
$is_new = !empty($_GET["new"]);

// Handle POST Save
if (!empty($_POST["save"])) {
	verify_csrf($_POST);
	$category = $_POST["category"] ?? $category;
	$name = $_POST["name"] ?? $name;

	// Save .tpl
	if (isset($_POST["content_tpl"])) {
		$tmp_tpl = tempnam("/tmp", "tpl_");
		file_put_contents($tmp_tpl, $_POST["content_tpl"]);
		exec(HESTIA_CMD . "v-change-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " tpl " . quoteshellarg($tmp_tpl), $output, $ret);
		@unlink($tmp_tpl);
	}

	// Save .stpl
	if (isset($_POST["content_stpl"])) {
		$tmp_stpl = tempnam("/tmp", "stpl_");
		file_put_contents($tmp_stpl, $_POST["content_stpl"]);
		exec(HESTIA_CMD . "v-change-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " stpl " . quoteshellarg($tmp_stpl), $output, $ret);
		@unlink($tmp_stpl);
	}

	// Save .sh hook if provided
	if (!empty($_POST["content_sh"])) {
		$tmp_sh = tempnam("/tmp", "sh_");
		file_put_contents($tmp_sh, $_POST["content_sh"]);
		exec(HESTIA_CMD . "v-change-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " sh " . quoteshellarg($tmp_sh), $output, $ret);
		@unlink($tmp_sh);
	}

	$_SESSION["ok_msg"] = sprintf(_("Template %s/%s has been saved successfully."), htmlentities($category), htmlentities($name));
	header("Location: /edit/template/?category=" . urlencode($category) . "&name=" . urlencode($name));
	exit();
}

// Fetch template content
$content_tpl = "";
$content_stpl = "";
$content_sh = "";

if (!$is_new) {
	exec(HESTIA_CMD . "v-get-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " tpl json", $out_tpl);
	$res_tpl = json_decode(implode("", $out_tpl), true);
	if (!empty($res_tpl["success"])) {
		$content_tpl = $res_tpl["content"] ?? "";
	}

	exec(HESTIA_CMD . "v-get-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " stpl json", $out_stpl);
	$res_stpl = json_decode(implode("", $out_stpl), true);
	if (!empty($res_stpl["success"])) {
		$content_stpl = $res_stpl["content"] ?? "";
	}

	exec(HESTIA_CMD . "v-get-sys-template " . quoteshellarg($category) . " " . quoteshellarg($name) . " sh json", $out_sh);
	$res_sh = json_decode(implode("", $out_sh), true);
	if (!empty($res_sh["success"])) {
		$content_sh = $res_sh["content"] ?? "";
	}
}

// Render page
render_page($user, $TAB, "edit_template");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
