<?php
session_start();
require_once "../../inc/main.php";

// SECURITY: Portainer is admin-only. A logged-in customer must never reach it.
// The Portainer ports themselves are bound to 127.0.0.1 (defense layer 1), and
// the 'docker-ui' nginx vhost enforces an auth_basic gate (defense layer 2), so
// even following the link would not let a non-admin in. This guard is defense
// layer 0: hide the entry point entirely from non-admin sessions.
if (($_SESSION["userContext"] ?? "") !== "admin") {
    header("Location: /list/web/");
    exit();
}

// Do NOT redirect to the raw Portainer port (https://host:9443). The ports are
// bound to 127.0.0.1, so a direct port redirect would fail from the browser.
// Instead, send the admin to their own 'docker-ui' vhost domain if one is
// configured; otherwise explain how to set it up.
//
// We look up the admin's web domain that uses the 'docker-ui' proxy template.
$dockerDomain = null;
if (!empty($_SESSION["ROOT_USER"])) {
    $rootUser = $_SESSION["ROOT_USER"];
    $cmd = escapeshellcmd("/usr/local/hestia/bin/v-list-web-domains " . escapeshellarg($rootUser) . " json");
    $json = @shell_exec($cmd . " 2>/dev/null");
    if ($json) {
        $domains = json_decode($json, true);
        if (is_array($domains)) {
            foreach ($domains as $dName => $dData) {
                $tpl = $dData["PROXY"] ?? ($dData["WEB_TEMPLATE"] ?? "");
                // PROXY is stored like "docker-ui" or may include an extension.
                if (strpos($tpl, "docker-ui") !== false) {
                    $dockerDomain = $dName;
                    break;
                }
            }
        }
    }
}

if ($dockerDomain) {
    // Force HTTPS on the docker-ui vhost (it has auth_basic + Let's Encrypt SSL).
    $scheme = "https";
    header("Location: " . $scheme . "://" . $dockerDomain . "/");
    exit();
}

// No docker-ui vhost yet — show a setup notice instead of a broken redirect.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title><?= _("Docker Manager (Portainer)") ?></title>
</head>
<body style="font-family:sans-serif;max-width:640px;margin:60px auto;line-height:1.6">
    <h2>🐳 <?= _("Portainer not published yet") ?></h2>
    <p><?= _("Portainer is installed and locked to 127.0.0.1 (not reachable directly).") ?></p>
    <p><?= _("To access it, create a web domain with the <b>docker-ui</b> proxy template, enable SSL, then open that domain.") ?></p>
    <p><a href="/add/web/"><?= _("Add web domain") ?> &rarr;</a></p>
</body>
</html>
