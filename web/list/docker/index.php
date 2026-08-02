<?php
session_start();
require_once "../../inc/main.php";

// Redirect directly to official Portainer CE Application
$host = $_SERVER['HTTP_HOST'];
// Remove port if present in HTTP_HOST
if (strpos($host, ':') !== false) {
    $host = explode(':', $host)[0];
}

// Redirect to official Portainer CE on port 9000
header("Location: http://" . $host . ":9000");
exit();
