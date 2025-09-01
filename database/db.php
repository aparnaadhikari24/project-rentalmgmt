<?php
// Database connection helper using PDO
// Update credentials as needed for your MySQL setup

function envDefault($key, $default) {
    if (!isset($_ENV[$key]) && getenv($key) === false) {
        $_ENV[$key] = $default;
    }
}

envDefault('DB_HOST', '127.0.0.1');
envDefault('DB_NAME', 'rental_app');
envDefault('DB_USER', 'root');
envDefault('DB_PASS', '');

function pdo(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $host = $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1';
    $db   = $_ENV['DB_NAME'] ?? getenv('DB_NAME') ?: 'rental_app';
    $user = $_ENV['DB_USER'] ?? getenv('DB_USER') ?: 'root';
    $pass = $_ENV['DB_PASS'] ?? getenv('DB_PASS') ?: '';

    $dsn = "mysql:host=$host;dbname=$db;charset=utf8mb4";
    $opt = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];
    try {
        $pdo = new PDO($dsn, $user, $pass, $opt);
    } catch (PDOException $e) {
        http_response_code(500);
        echo 'DB connection failed: ' . htmlspecialchars($e->getMessage());
        exit;
    }
    return $pdo;
}

function ensure_session() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function current_user_id() {
    ensure_session();
    return $_SESSION['user_id'] ?? null;
}

function is_admin() {
    ensure_session();
    return ($_SESSION['role'] ?? '') === 'admin';
}

function require_admin() {
    if (!is_admin()) { http_response_code(403); echo 'Forbidden'; exit; }
}

function json_out($payload) {
    header('Content-Type: application/json');
    echo json_encode($payload);
    exit;
}

?>