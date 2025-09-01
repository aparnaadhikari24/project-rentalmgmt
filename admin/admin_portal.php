<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();
if (!is_admin()) { header('Location: login.php'); exit; }
// Admin portal now focuses on user management; redirect to dashboard
header('Location: dashboard.php');
exit;
