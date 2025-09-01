<?php
require_once __DIR__ . '/db.php';
ensure_session();

$uid = current_user_id();
if (!$uid) { json_out(['ok'=>false,'message'=>'Login required']); }

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $st = pdo()->prepare('SELECT name, email FROM users WHERE id=?');
    $st->execute([$uid]);
    $u = $st->fetch();
    if (!$u) json_out(['ok'=>false,'message'=>'User not found']);
    json_out(['ok'=>true,'data'=>$u]);
}

if ($method === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newPass = $_POST['new_password'] ?? '';
    $current = $_POST['current_password'] ?? '';

    if (!$current) json_out(['ok'=>false,'message'=>'Current password required']);

    $st = pdo()->prepare('SELECT password FROM users WHERE id=?');
    $st->execute([$uid]);
    $row = $st->fetch();
    if (!$row || !password_verify($current, $row['password'])) {
        json_out(['ok'=>false,'message'=>'Current password incorrect']);
    }

    // Build update dynamically
    $fields = [];
    $args = [];
    if ($name !== '') { $fields[] = 'name = ?'; $args[] = $name; }
    if ($email !== '') {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            json_out(['ok'=>false,'message'=>'Invalid email']);
        }
        $fields[] = 'email = ?'; $args[] = $email;
    }
    if ($newPass !== '') {
        $fields[] = 'password = ?'; $args[] = password_hash($newPass, PASSWORD_DEFAULT);
    }
    if (!$fields) {
        json_out(['ok'=>true,'message'=>'No changes']);
    }
    $args[] = $uid;

    try {
        $sql = 'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?';
        $upd = pdo()->prepare($sql);
        $upd->execute($args);
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            json_out(['ok'=>false,'message'=>'Email already in use']);
        }
        json_out(['ok'=>false,'message'=>'Update failed']);
    }

    if ($name !== '') { $_SESSION['name'] = $name; }

    json_out(['ok'=>true,'message'=>'Profile updated']);
}

json_out(['ok'=>false,'message'=>'Unsupported method']);
