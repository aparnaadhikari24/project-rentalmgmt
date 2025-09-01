<?php
require_once __DIR__ . '/db.php';
ensure_session();
session_destroy();
// Redirect to login relative to backend/ -> project root
header('Location: ../login.php');
exit;
