<?php
require_once __DIR__ . '/db.php';
ensure_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed';
    exit;
}

$uid = current_user_id();
if (!$uid) {
    http_response_code(401);
    echo 'Unauthorized';
    exit;
}

$id = (int)($_POST['id'] ?? 0);
if (!$id) {
    http_response_code(400);
    echo 'Property ID required';
    exit;
}

$stmt = pdo()->prepare('SELECT * FROM properties WHERE id = ? AND owner_id = ?');
$stmt->execute([$id, $uid]);
$property = $stmt->fetch();

if (!$property) {
    http_response_code(403);
    echo 'Property not found or access denied';
    exit;
}

if ($property['image_url'] && file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . $property['image_url'])) {
    @unlink(dirname(__DIR__) . DIRECTORY_SEPARATOR . $property['image_url']);
}

$stmt = pdo()->prepare('DELETE FROM properties WHERE id = ? AND owner_id = ?');
$stmt->execute([$id, $uid]);

header('Location: ../pages/tenant.php');
exit;
