<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();
if (!current_user_id()) { header('Location: login.php'); exit; }
$uid = current_user_id();

$err = '';
$success = '';

// Handle create property by tenant (owner)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	if ($action === 'create') {
		$title = trim($_POST['title'] ?? '');
		$description = trim($_POST['description'] ?? '');
		$price = (float)($_POST['price'] ?? 0);
		$location = trim($_POST['location'] ?? '');
		$type = trim($_POST['type'] ?? 'apartment');
		$image_url = '';

		if (!empty($_FILES['image_file']['name']) && $_FILES['image_file']['error'] === UPLOAD_ERR_OK) {
			$tmp = $_FILES['image_file']['tmp_name'];
			$name = basename($_FILES['image_file']['name']);
			$ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
			$allowed = ['jpg','jpeg','png','webp'];
			$maxBytes = 5 * 1024 * 1024; // 5MB
			if (!in_array($ext, $allowed, true)) { $err = 'Invalid image type (jpg, jpeg, png, webp only)'; }
			if (filesize($tmp) > $maxBytes) { $err = 'Image is too large (max 5MB)'; }
			if (!$err) {
				$uploadDir = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
				if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
				$safe = preg_replace('/[^a-zA-Z0-9_.-]/','_', $name);
				$fileName = time() . '_' . $safe;
								$destRel = 'uploads/' . $fileName;
				$destAbs = $uploadDir . DIRECTORY_SEPARATOR . $fileName;
				if (!move_uploaded_file($tmp, $destAbs)) {
					$err = 'Failed to save uploaded file';
				} else {
									// Store project-root relative path in DB
									$image_url = $destRel;
				}
			}
		}
		if (!$title || !$price || !$location) { $err = 'Title, price, and location are required'; }
		if (!$err) {
			$stmt = pdo()->prepare('INSERT INTO properties (title, description, price, location, type, image_url, owner_id) VALUES (?, ?, ?, ?, ?, ?, ?)');
			$stmt->execute([$title, $description, $price, $location, $type, $image_url, $uid]);
			$success = 'Property created';
		}
	} elseif ($action === 'delete') {
		$id = (int)($_POST['id'] ?? 0);
		$stmt = pdo()->prepare('DELETE FROM properties WHERE id=? AND owner_id=?');
		$stmt->execute([$id, $uid]);
		$success = 'Property deleted';
	} elseif ($action === 'set_status') {
		$id = (int)($_POST['id'] ?? 0);
		$status = $_POST['status'] === 'rented' ? 'rented' : 'available';
		$stmt = pdo()->prepare('UPDATE properties SET status=? WHERE id=? AND owner_id=?');
		$stmt->execute([$status, $id, $uid]);
		$success = 'Status updated';
	}
}

$props = pdo()->prepare('SELECT p.*, (SELECT COUNT(*) FROM inquiries i WHERE i.property_id=p.id) AS inquiries_count FROM properties p WHERE p.owner_id = ? ORDER BY p.created_at DESC');
$props->execute([$uid]);
$rows = $props->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Your Properties — StayNest</title>
	<?php
		$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
		$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
		if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }
	?>
	<link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
	</head>
<body>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<main class="container">
	<section class="section">
		<h2>Add a property</h2>
		<?php if ($err): ?><p style="color:#b91c1c;"><?= htmlspecialchars($err) ?></p><?php endif; ?>
		<?php if ($success): ?><p style="color:#047857;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
		<form class="form-grid" method="post" enctype="multipart/form-data">
			<input type="hidden" name="action" value="create">
			<div class="input"><label>Title</label><input name="title" required></div>
			<div class="input"><label>Description</label><textarea name="description" rows="3"></textarea></div>
			<div class="input"><label>Price (monthly)</label><input type="number" step="0.01" name="price" required></div>
			<div class="input"><label>Location</label><input name="location" required></div>
			<div class="input"><label>Type</label>
				<select name="type">
					<option value="apartment">Apartment</option>
					<option value="house">House</option>
					<option value="studio">Studio</option>
				</select>
			</div>
			<div class="input"><label>Image file</label><input type="file" name="image_file" accept="image/*"></div>
			<button class="btn" type="submit">Create</button>
		</form>
	</section>

	<section class="section">
		<h2>Your listings</h2>
		<table class="table">
			<thead><tr><th>Title</th><th>Type</th><th>Price</th><th>Status</th><th>Inquiries</th><th>Created</th><th></th></tr></thead>
			<tbody>
			<?php foreach ($rows as $r): ?>
				<tr>
					<td><a href="property.php?id=<?= $r['id'] ?>"><?= htmlspecialchars($r['title']) ?></a></td>
					<td><?= htmlspecialchars($r['type'] ?? '') ?></td>
					<td>$<?= number_format($r['price'], 2) ?></td>
					<td>
						<form method="post" style="display:inline-block">
							<input type="hidden" name="action" value="set_status">
							<input type="hidden" name="id" value="<?= $r['id'] ?>">
							<select name="status" onchange="this.form.submit()">
								<option value="available" <?= $r['status']==='available'?'selected':'' ?>>Available</option>
								<option value="rented" <?= $r['status']==='rented'?'selected':'' ?>>Rented</option>
							</select>
						</form>
					</td>
					<td><?= (int)$r['inquiries_count'] ?></td>
					<td><?= htmlspecialchars($r['created_at']) ?></td>
					<td>
						<form method="post" onsubmit="return confirm('Delete this listing?')" style="display:inline-block;">
							<input type="hidden" name="action" value="delete">
							<input type="hidden" name="id" value="<?= $r['id'] ?>">
							<button class="btn secondary" type="submit">Delete</button>
						</form>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</section>

	<section class="section">
		<h2>Recent inquiries</h2>
		<div id="inqList" class="form-grid"></div>
	</section>
</main>
<script>
(async function(){
	try {
		const base = (window.APP_ROOT || '').replace(/\/+$/, '/') || '/';
		const res = await fetch(base + 'controllers/tenant_inquiries.php');
		const json = await res.json();
		const el = document.getElementById('inqList');
		if (!json.ok) { el.textContent = json.message || 'Failed to load'; return; }
		if (!json.data.length) { el.textContent = 'No inquiries yet.'; return; }
		el.innerHTML = '';
	  json.data.forEach(i => {
			const wrap = document.createElement('div');
			wrap.className = 'card';
			wrap.innerHTML = `
				<div class="body">
					<div class="title">${i.title || ('#'+i.property_id)}</div>
		  <div class="meta">${i.created_at} • From: ${i.user_email || 'Guest'}${i.phone ? ' • Phone: ' + i.phone : ''}</div>
					<p>${(i.message || '').replace(/</g,'&lt;')}</p>
				</div>`;
			el.appendChild(wrap);
		});
	} catch (e) {
		document.getElementById('inqList').textContent = 'Error loading inquiries.';
	}
})();
</script>
</body>
</html>
