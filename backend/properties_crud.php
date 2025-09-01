<?php
require_once __DIR__ . '/db.php';
ensure_session();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'GET') {
    // List for API usage (optional)
    $stmt = pdo()->query('SELECT * FROM properties ORDER BY created_at DESC LIMIT 50');
    json_out(['ok'=>true,'data'=>$stmt->fetchAll()]);
}

if (!is_admin()) { json_out(['ok'=>false,'message'=>'Forbidden']); }

if ($method === 'POST') {
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $price = (float)($_POST['price'] ?? 0);
    $location = trim($_POST['location'] ?? '');
    $type = trim($_POST['type'] ?? 'apartment');
    $image_url = trim($_POST['image_url'] ?? '');
    if (!$title || !$price || !$location) json_out(['ok'=>false,'message'=>'Missing fields']);
    $stmt = pdo()->prepare('INSERT INTO properties (title, description, price, location, type, image_url) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([$title, $description, $price, $location, $type, $image_url]);
    json_out(['ok'=>true]);
}

if ($method === 'PUT' || $method === 'PATCH') {
    parse_str(file_get_contents('php://input'), $data);
    $id = (int)($data['id'] ?? 0);
    if (!$id) json_out(['ok'=>false,'message'=>'ID required']);
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    $price = (float)($data['price'] ?? 0);
    $location = trim($data['location'] ?? '');
    $type = trim($data['type'] ?? 'apartment');
    $image_url = trim($data['image_url'] ?? '');
    $stmt = pdo()->prepare('UPDATE properties SET title=?, description=?, price=?, location=?, type=?, image_url=? WHERE id=?');
    $stmt->execute([$title, $description, $price, $location, $type, $image_url, $id]);
    json_out(['ok'=>true]);
}

if ($method === 'DELETE') {
    parse_str(file_get_contents('php://input'), $data);
    $id = (int)($data['id'] ?? 0);
    if (!$id) json_out(['ok'=>false,'message'=>'ID required']);
    $stmt = pdo()->prepare('DELETE FROM properties WHERE id=?');
    $stmt->execute([$id]);
    json_out(['ok'=>true]);
}

json_out(['ok'=>false,'message'=>'Unsupported']);
