<?php
session_start();
require_once "../../inc/main.php";

$TAB = "DOCKER";

// Render page with Docker Manager UI
render_page($user, $TAB, "list_docker");
