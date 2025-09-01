<?php
require_once __DIR__ . '/db.php';
ensure_session();

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if ($path && str_ends_with($path, '/logout.php')) {
  session_destroy();
  header('Location: /login.php');
  exit;
}

json_out(['ok' => false, 'message' => 'No route']);
