<?php
require_once __DIR__ . '/db.php';
ensure_session();
$uid = current_user_id();
if (!$uid) { json_out(['ok'=>false,'message'=>'Login required']); }

// Fetch inquiries for properties owned by current user
$sql = 'SELECT i.*, p.title, u.email as user_email
        FROM inquiries i
        JOIN properties p ON p.id = i.property_id
        LEFT JOIN users u ON u.id = i.user_id
        WHERE p.owner_id = ?
        ORDER BY i.created_at DESC';
$st = pdo()->prepare($sql);
$st->execute([$uid]);
json_out(['ok'=>true,'data'=>$st->fetchAll()]);
