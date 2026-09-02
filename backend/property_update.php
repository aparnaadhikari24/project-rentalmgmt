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

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$price = (float)($_POST['price'] ?? 0);
$location = trim($_POST['location'] ?? '');
$type = trim($_POST['type'] ?? 'apartment');
$image_url = $property['image_url'];

if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
    $tmp = $_FILES['image_file']['tmp_name'];
    $name = basename($_FILES['image_file']['name']);
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'webp'];
    $maxBytes = 5 * 1024 * 1024;

    if (!in_array($ext, $allowed, true)) {
        http_response_code(400);
        echo 'Invalid image type (jpg, jpeg, png, webp only)';
        exit;
    }
    if (filesize($tmp) > $maxBytes) {
        http_response_code(400);
        echo 'Image is too large (max 5MB)';
        exit;
    }

    $uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
    if (!is_dir($uploadDir)) {
        @mkdir($uploadDir, 0777, true);
    }
    $safe = preg_replace('/[^a-zA-Z0-9_.-]/', '_', $name);
    $fileName = time() . '_' . $safe;
    $destRel = 'uploads/' . $fileName;
    $destAbs = $uploadDir . DIRECTORY_SEPARATOR . $fileName;

    if (move_uploaded_file($tmp, $destAbs)) {
        if ($property['image_url'] && file_exists(dirname(__DIR__) . DIRECTORY_SEPARATOR . $property['image_url'])) {
            @unlink(dirname(__DIR__) . DIRECTORY_SEPARATOR . $property['image_url']);
        }
        $image_url = $destRel;
    }
}

if (!$title || !$price || !$location) {
    http_response_code(400);
    echo 'Title, price, and location are required';
    exit;
}

$stmt = pdo()->prepare('UPDATE properties SET title=?, description=?, price=?, location=?, type=?, image_url=? WHERE id=? AND owner_id=?');
$stmt->execute([$title, $description, $price, $location, $type, $image_url, $id, $uid]);

header('Location: ../tenant.php');
exit;
