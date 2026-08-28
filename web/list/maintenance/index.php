<?php
use function Hestiacp\quoteshellarg\quoteshellarg;

$TAB = "MAINTENANCE";

// Main include
include $_SERVER["DOCUMENT_ROOT"] . "/inc/main.php";

// Check user
if ($_SESSION["userContext"] != "admin") {
	header("Location: /list/user");
	exit();
}

// Handle run request (regular POST; the run itself may take several minutes)
$run_result = null;
$run_error = null;
if (!empty($_POST["action"]) && $_POST["action"] === "run") {
	verify_csrf($_POST);

	// Fixed strings only: the checkbox maps to a hard-coded flag, nothing
	// user-supplied is interpolated into the command line.
	$run_security_updates =
		!empty($_POST["security_updates"]) && $_POST["security_updates"] === "yes";
	$run_cmd =
		HESTIA_CMD . "v-run-sys-maintenance" .
		($run_security_updates ? " --security-updates" : "") .
		" json";
	exec($run_cmd, $run_output, $run_return_var);
	$run_result = json_decode(implode("", $run_output), true);
	unset($run_output);
	if ($run_return_var !== 0 || empty($run_result)) {
		$run_error = $run_return_var;
	}
}

// Fetch last report + recent history (reflects the run above, if any)
exec(HESTIA_CMD . "v-list-sys-maintenance json", $output, $return_var);
$maintenance_data = json_decode(implode("", $output), true);
unset($output);

if (empty($maintenance_data)) {
	$maintenance_data = ["report" => null, "history" => []];
}

// Render page
render_page($user, $TAB, "list_maintenance");

// Back uri
$_SESSION["back"] = $_SERVER["REQUEST_URI"];
