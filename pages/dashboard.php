<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();
if (!is_admin()) { header('Location: login.php'); exit; }

$err = '';
$success = '';

// Create/Update/Delete Users
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	$action = $_POST['action'] ?? '';
	if ($action === 'create_user') {
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$password = $_POST['password'] ?? '';
		$role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
		if (!$name || !$email || !$password) { $err = 'Name, email, and password are required'; }
		if (!$err) {
			try {
				$hash = password_hash($password, PASSWORD_DEFAULT);
				$st = pdo()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
				$st->execute([$name, $email, $hash, $role]);
				$success = 'User created';
			} catch (PDOException $e) {
				if ($e->getCode() == 23000) { $err = 'Email already exists'; }
				else { $err = 'Failed to create user'; }
			}
		}
	} elseif ($action === 'update_user') {
		$id = (int)($_POST['id'] ?? 0);
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
		$newPass = $_POST['new_password'] ?? '';
		if (!$id || !$name || !$email) { $err = 'ID, name, and email are required'; }
		if (!$err) {
			try {
				if ($newPass !== '') {
					$hash = password_hash($newPass, PASSWORD_DEFAULT);
					$st = pdo()->prepare('UPDATE users SET name=?, email=?, role=?, password=? WHERE id=?');
					$st->execute([$name, $email, $role, $hash, $id]);
				} else {
					$st = pdo()->prepare('UPDATE users SET name=?, email=?, role=? WHERE id=?');
					$st->execute([$name, $email, $role, $id]);
				}
				$success = 'User updated';
				if ($id === (int)current_user_id()) { $_SESSION['name'] = $name; $_SESSION['role'] = $role; }
			} catch (PDOException $e) {
				if ($e->getCode() == 23000) { $err = 'Email already exists'; }
				else { $err = 'Failed to update user'; }
			}
		}
	} elseif ($action === 'delete_user') {
		$id = (int)($_POST['id'] ?? 0);
		if ($id === (int)current_user_id()) {
			$err = "You can't delete your own account";
		} else {
			$st = pdo()->prepare('DELETE FROM users WHERE id=?');
			$st->execute([$id]);
			$success = 'User deleted';
		}
	}
}

// Search and list users
$q = trim($_GET['q'] ?? '');
$w = '';
$args = [];
if ($q !== '') { $w = 'WHERE (name LIKE ? OR email LIKE ?)'; $args = ["%$q%", "%$q%"]; }
$users = pdo()->prepare("SELECT id, name, email, role FROM users $w ORDER BY id DESC LIMIT 200");
$users->execute($args);
$rows = $users->fetchAll();

	// ---------- Properties management ----------
	// Handle property updates/deletes
	if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$action = $_POST['action'] ?? '';
		if ($action === 'update_property') {
			$pid = (int)($_POST['id'] ?? 0);
			$title = trim($_POST['title'] ?? '');
			$description = trim($_POST['description'] ?? '');
			$price = (float)($_POST['price'] ?? 0);
			$location = trim($_POST['location'] ?? '');
			$type = trim($_POST['type'] ?? 'apartment');
			$status = ($_POST['status'] ?? 'available') === 'rented' ? 'rented' : 'available';
			if (!$pid || !$title || !$price || !$location) {
				$err = $err ?: 'Property: Title, price, and location are required';
			} else {
				$st = pdo()->prepare('UPDATE properties SET title=?, description=?, price=?, location=?, type=?, status=? WHERE id=?');
				$st->execute([$title, $description, $price, $location, $type, $status, $pid]);
				if (!$success) { $success = 'Property updated'; }
			}
		} elseif ($action === 'delete_property') {
			$pid = (int)($_POST['id'] ?? 0);
			if ($pid) {
				// Cleanup dependent rows to avoid FK issues
				pdo()->prepare('DELETE FROM favorites WHERE property_id=?')->execute([$pid]);
				pdo()->prepare('DELETE FROM inquiries WHERE property_id=?')->execute([$pid]);
				pdo()->prepare('DELETE FROM properties WHERE id=?')->execute([$pid]);
				if (!$success) { $success = 'Property deleted'; }
			}
		}
	}

	// Properties list with search (separate query param pq)
	$pq = trim($_GET['pq'] ?? '');
	$pw = '';
	$pargs = [];
	if ($pq !== '') { $pw = 'WHERE (p.title LIKE ? OR p.location LIKE ? OR u.email LIKE ?)'; $pargs = ["%$pq%", "%$pq%", "%$pq%"]; }
	$plist = pdo()->prepare("SELECT p.id, p.title, p.price, p.status, p.type, p.location, p.created_at, p.owner_id, u.email AS owner_email FROM properties p LEFT JOIN users u ON u.id = p.owner_id $pw ORDER BY p.id DESC LIMIT 200");
	$plist->execute($pargs);
	$props = $plist->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Admin Dashboard — User Management</title>
	<?php
		$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
		$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
		if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }
	?>
	<link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
	<style>.role-badge{display:inline-block;padding:4px 8px;border-radius:999px;border:1px solid var(--border);font-weight:800;font-size:.8rem}.role-badge.admin{background:#e0f2fe;color:#075985;border-color:#bae6fd}</style>
	</head>
<body>
<header class="header">
	<div class="container nav">
		<a class="brand" href="index.php"><span class="logo"></span><span>StayNest</span></a>
		<nav>
			<a href="properties.php">View site</a>
			<a class="btn secondary" href="<?= $__root ?>controllers/logout.php">Logout</a>
		</nav>
	</div>
</header>

<main class="container">
	<section class="section">
		<h2>Add user</h2>
		<?php if ($err): ?><p style="color:#b91c1c;"><?= htmlspecialchars($err) ?></p><?php endif; ?>
		<?php if ($success): ?><p class="muted" style="color:#047857;"><?= htmlspecialchars($success) ?></p><?php endif; ?>
		<form class="form-grid" method="post">
			<input type="hidden" name="action" value="create_user">
			<div class="input"><label>Name</label><input name="name" required></div>
			<div class="input"><label>Email</label><input type="email" name="email" required></div>
			<div class="input"><label>Password</label><input type="password" name="password" required></div>
			<div class="input"><label>Role</label>
				<select name="role">
					<option value="user">User</option>
					<option value="admin">Admin</option>
				</select>
			</div>
			<button class="btn" type="submit">Create user</button>
		</form>
	</section>

	<section class="section">
		<h2>Users</h2>
		<form class="search-bar" method="get" style="margin-bottom:14px;">
			<input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name or email">
			<button class="btn" type="submit">Search</button>
		</form>
		<table class="table">
			<thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Actions</th></tr></thead>
			<tbody>
				<?php foreach ($rows as $u): ?>
					<tr>
						<td><?= (int)$u['id'] ?></td>
						<td><?= htmlspecialchars($u['name']) ?></td>
						<td><?= htmlspecialchars($u['email']) ?></td>
						<td><span class="role-badge <?= $u['role']==='admin'?'admin':'' ?>"><?= htmlspecialchars($u['role']) ?></span></td>
						<td style="display:flex; gap:8px;">
							<details>
								<summary class="btn secondary" style="cursor:pointer;">Edit</summary>
								<form class="form-grid" method="post" style="margin-top:8px;">
									<input type="hidden" name="action" value="update_user">
									<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
									<div class="input"><label>Name</label><input name="name" value="<?= htmlspecialchars($u['name']) ?>" required></div>
									<div class="input"><label>Email</label><input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required></div>
									<div class="input"><label>Role</label>
										<select name="role">
											<option value="user" <?= $u['role']==='user'?'selected':'' ?>>User</option>
											<option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
										</select>
									</div>
									<div class="input"><label>New password (optional)</label><input type="password" name="new_password" placeholder="Leave blank to keep"></div>
									<button class="btn" type="submit">Save</button>
								</form>
							</details>
							<?php if ((int)$u['id'] !== (int)current_user_id()): ?>
							<form method="post" onsubmit="return confirm('Delete this user?')">
								<input type="hidden" name="action" value="delete_user">
								<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
								<button class="btn secondary" type="submit">Delete</button>
							</form>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
	</section>

		<section class="section">
			<h2>Properties</h2>
			<form class="search-bar" method="get" style="margin-bottom:14px;">
				<input name="pq" value="<?= htmlspecialchars($pq) ?>" placeholder="Search by title, location, or owner email">
				<button class="btn" type="submit">Search</button>
			</form>
			<table class="table">
				<thead>
					<tr>
						<th>ID</th>
						<th>Title</th>
						<th>Owner</th>
						<th>Type</th>
						<th>Status</th>
						<th>Price</th>
						<th>Created</th>
						<th>Actions</th>
					</tr>
				</thead>
				<tbody>
					<?php foreach ($props as $p): ?>
						<tr>
							<td><?= (int)$p['id'] ?></td>
							<td><?= htmlspecialchars($p['title']) ?></td>
							<td><?= htmlspecialchars($p['owner_email'] ?? ('#'.$p['owner_id'])) ?></td>
							<td><?= htmlspecialchars($p['type'] ?? '') ?></td>
							<td>
								<span class="role-badge <?= ($p['status']==='rented')?'admin':'' ?>" style="text-transform:capitalize;"><?= htmlspecialchars($p['status']) ?></span>
							</td>
							<td>$<?= number_format((float)$p['price'], 2) ?></td>
							<td><?= htmlspecialchars($p['created_at']) ?></td>
							<td style="display:flex; gap:8px;">
								<details>
									<summary class="btn secondary" style="cursor:pointer;">Edit</summary>
									<form class="form-grid" method="post" style="margin-top:8px;">
										<input type="hidden" name="action" value="update_property">
										<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
										<div class="input"><label>Title</label><input name="title" value="<?= htmlspecialchars($p['title']) ?>" required></div>
										<div class="input"><label>Description</label><textarea name="description" rows="3"><?= htmlspecialchars($p['description'] ?? '') ?></textarea></div>
										<div class="input"><label>Price</label><input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p['price']) ?>" required></div>
										<div class="input"><label>Location</label><input name="location" value="<?= htmlspecialchars($p['location']) ?>" required></div>
										<div class="input"><label>Type</label>
											<select name="type">
												<?php foreach (['apartment','house','studio'] as $t): ?>
													<option value="<?= $t ?>" <?= ($p['type']??'')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
												<?php endforeach; ?>
											</select>
										</div>
										<div class="input"><label>Status</label>
											<select name="status">
												<option value="available" <?= $p['status']==='available'?'selected':'' ?>>Available</option>
												<option value="rented" <?= $p['status']==='rented'?'selected':'' ?>>Rented</option>
											</select>
										</div>
										<button class="btn" type="submit">Save</button>
									</form>
								</details>
								<form method="post" onsubmit="return confirm('Delete this property?')">
									<input type="hidden" name="action" value="delete_property">
									<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
									<button class="btn secondary" type="submit">Delete</button>
								</form>
							</td>
						</tr>
					<?php endforeach; ?>
				</tbody>
			</table>
		</section>
</main>
</body>
</html>
