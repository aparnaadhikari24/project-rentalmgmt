<?php
require_once __DIR__ . '/../backend/logout.php';
// Compute base path and redirect to login
$base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($base === '') { $base = '/'; }
else { $base .= '/'; }
header('Location: ' . $base . 'login.php');
exit;
