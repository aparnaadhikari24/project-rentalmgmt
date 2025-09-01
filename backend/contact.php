<?php
require_once __DIR__ . '/db.php';
ensure_session();

$uid = current_user_id();
$pid = (int)($_POST['property_id'] ?? 0);
$msg = trim($_POST['message'] ?? '');
// optional phone
$phone = trim($_POST['phone'] ?? '');
if (!$pid || !$msg) { json_out(['ok'=>false,'message'=>'Message required']); }
if (!$uid) { json_out(['ok'=>false,'message'=>'Please log in to send an inquiry']); }

// Block inquiries for rented properties
$ps = pdo()->prepare('SELECT status FROM properties WHERE id=?');
$ps->execute([$pid]);
$status = $ps->fetchColumn();
if ($status === 'rented') { json_out(['ok'=>false,'message'=>'This property is rented. Inquiries are disabled.']); }

$stmt = pdo()->prepare('INSERT INTO inquiries (user_id, property_id, message, phone) VALUES (?, ?, ?, ?)');
$stmt->execute([$uid, $pid, $msg, $phone !== '' ? $phone : null]);

json_out(['ok'=>true,'message'=>'Inquiry sent']);
