<?php
require_once __DIR__ . '/db.php';
ensure_session();

$uid = current_user_id();
if (!$uid) { json_out(['ok'=>false,'message'=>'Login required']); }

$raw = file_get_contents('php://input');
$data = json_decode($raw, true) ?: [];
$pid = (int)($data['property_id'] ?? 0);
$action = $data['action'] ?? '';
if (!$pid || !in_array($action, ['add','remove'])) { json_out(['ok'=>false,'message'=>'Bad request']); }

if ($action === 'add') {
    try {
        $stmt = pdo()->prepare('INSERT IGNORE INTO favorites (user_id, property_id) VALUES (?, ?)');
        $stmt->execute([$uid, $pid]);
    } catch (PDOException $e) { json_out(['ok'=>false,'message'=>'DB error']); }
} else {
    $stmt = pdo()->prepare('DELETE FROM favorites WHERE user_id = ? AND property_id = ?');
    $stmt->execute([$uid, $pid]);
}

json_out(['ok'=>true]);
