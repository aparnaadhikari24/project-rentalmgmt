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
				$success = 'User created successfully';
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
				$success = 'User updated successfully';
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
			$success = 'User deleted successfully';
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

// Get stats for dashboard overview
$totalUsers = pdo()->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalProps = pdo()->query("SELECT COUNT(*) FROM properties")->fetchColumn();
$rentedProps = pdo()->query("SELECT COUNT(*) FROM properties WHERE status='rented'")->fetchColumn();
$availableProps = $totalProps - $rentedProps;
$totalRevenue = pdo()->query("SELECT COALESCE(SUM(price), 0) FROM properties WHERE status='rented'")->fetchColumn();

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
				if (!$success) { $success = 'Property updated successfully'; }
			}
		} elseif ($action === 'delete_property') {
			$pid = (int)($_POST['id'] ?? 0);
			if ($pid) {
				pdo()->prepare('DELETE FROM favorites WHERE property_id=?')->execute([$pid]);
				pdo()->prepare('DELETE FROM inquiries WHERE property_id=?')->execute([$pid]);
				pdo()->prepare('DELETE FROM properties WHERE id=?')->execute([$pid]);
				if (!$success) { $success = 'Property deleted successfully'; }
			}
		}
	}

	// Properties list with search
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
	<title>Admin Dashboard — StayNest</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
	<?php
		$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
		$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
		if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }
	?>
	<link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
	<style>
		.dashboard-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:28px; }
		.dashboard-header h1 { font-size:1.6rem; margin:0; }
		.welcome-text { color:var(--muted); font-size:.95rem; margin-top:4px; }
		.stats-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:18px; margin-bottom:32px; }
		.stat-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); padding:20px; box-shadow:var(--shadow); position:relative; overflow:hidden; }
		.stat-card::before { content:''; position:absolute; top:0; left:0; width:4px; height:100%; }
		.stat-card.blue::before { background:var(--primary); }
		.stat-card.teal::before { background:var(--accent); }
		.stat-card.green::before { background:#22c55e; }
		.stat-card.orange::before { background:#f97316; }
		.stat-icon { width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:1.3rem; margin-bottom:14px; }
		.stat-card.blue .stat-icon { background:#e0f2fe; color:var(--primary); }
		.stat-card.teal .stat-icon { background:#ccfbf1; color:var(--accent); }
		.stat-card.green .stat-icon { background:#dcfce7; color:#22c55e; }
		.stat-card.orange .stat-icon { background:#ffedd5; color:#f97316; }
		.stat-value { font-size:1.8rem; font-weight:800; color:var(--fg); }
		.stat-label { color:var(--muted); font-size:.85rem; margin-top:4px; }
		.section-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:20px; padding-bottom:14px; border-bottom:2px solid var(--border); }
		.section-header h2 { margin:0; font-size:1.25rem; display:flex; align-items:center; gap:10px; }
		.section-header h2 i { color:var(--primary); }
		.section-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; margin-bottom:28px; }
		.form-card { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:24px; }
		.form-card h3 { margin:0 0 18px; font-size:1.05rem; display:flex; align-items:center; gap:8px; }
		.form-card h3 i { color:var(--primary); }
		.form-row { display:grid; grid-template-columns:repeat(4,1fr); gap:14px; }
		.form-row-2 { display:grid; grid-template-columns:repeat(2,1fr); gap:14px; }
		.table-wrapper { overflow-x:auto; }
		.table { width:100%; border-collapse:collapse; }
		.table th { background:#f8fafc; color:var(--muted); font-weight:700; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px; padding:14px 16px; border-bottom:2px solid var(--border); text-align:left; }
		.table td { padding:14px 16px; border-bottom:1px solid var(--border); vertical-align:middle; }
		.table tr:hover { background:#fafbfd; }
		.table tr:last-child td { border-bottom:none; }
		.role-badge { display:inline-flex; align-items:center; padding:5px 12px; border-radius:999px; font-weight:700; font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; }
		.role-badge.admin { background:linear-gradient(135deg, #e0f2fe, #bae6fd); color:#075985; }
		.role-badge.user { background:#f1f5f9; color:#475569; }
		.role-badge.rented { background:linear-gradient(135deg, #fee2e2, #fecaca); color:#b91c1c; }
		.role-badge.available { background:linear-gradient(135deg, #dcfce7, #bbf7d0); color:#166534; }
		.action-group { display:flex; gap:8px; align-items:center; }
		.btn-sm { padding:7px 12px; font-size:.8rem; }
		.btn-danger { background:#fff; color:#dc2626; border:1px solid #fecaca; }
		.btn-danger:hover { background:#fef2f2; border-color:#fca5a5; }
		.edit-form { background:#f8fafc; padding:16px; border-radius:10px; margin-top:10px; border:1px solid var(--border); }
		.search-row { display:flex; gap:10px; margin-bottom:16px; }
		.search-row input { flex:1; }
		.alert { padding:12px 16px; border-radius:10px; margin-bottom:16px; display:flex; align-items:center; gap:10px; font-weight:500; }
		.alert-success { background:#dcfce7; color:#166534; border:1px solid #bbf7d0; }
		.alert-error { background:#fee2e2; color:#b91c1c; border:1px solid #fecaca; }
		.empty-state { text-align:center; padding:40px; color:var(--muted); }
		.empty-state i { font-size:2.5rem; margin-bottom:12px; opacity:.5; }
		.count-badge { background:var(--bg); padding:4px 10px; border-radius:999px; font-size:.8rem; font-weight:700; color:var(--muted); }
		@media(max-width:900px) {
			.stats-grid { grid-template-columns:repeat(2,1fr); }
			.form-row { grid-template-columns:1fr; }
			.form-row-2 { grid-template-columns:1fr; }
		}
		@media(max-width:600px) {
			.stats-grid { grid-template-columns:1fr; }
		}
	</style>
</head>
<body>
<header class="header">
	<div class="container nav">
		<a class="brand" href="index.php"><span class="logo"></span><span>StayNest</span></a>
		<nav>
			<a href="properties.php"><i class="fas fa-external-link-alt"></i> View Site</a>
			<a class="btn secondary" href="<?= $__root ?>controllers/logout.php"><i class="fas fa-sign-out-alt"></i> Logout</a>
		</nav>
	</div>
</header>

<main class="container" style="padding-top:28px; padding-bottom:48px;">
	
	<div class="dashboard-header">
		<div>
			<h1><i class="fas fa-gauge-high" style="color:var(--primary);"></i> Admin Dashboard</h1>
			<p class="welcome-text">Welcome back, <?= htmlspecialchars($_SESSION['name'] ?? 'Admin') ?>! Here's what's happening.</p>
		</div>
	</div>

	<div class="stats-grid">
		<div class="stat-card blue">
			<div class="stat-icon"><i class="fas fa-users"></i></div>
			<div class="stat-value"><?= number_format($totalUsers) ?></div>
			<div class="stat-label">Total Users</div>
		</div>
		<div class="stat-card teal">
			<div class="stat-icon"><i class="fas fa-building"></i></div>
			<div class="stat-value"><?= number_format($totalProps) ?></div>
			<div class="stat-label">Total Properties</div>
		</div>
		<div class="stat-card green">
			<div class="stat-icon"><i class="fas fa-check-circle"></i></div>
			<div class="stat-value"><?= number_format($availableProps) ?></div>
			<div class="stat-label">Available</div>
		</div>
		<div class="stat-card orange">
			<div class="stat-icon"><i class="fas fa-dollar-sign"></i></div>
			<div class="stat-value">$<?= number_format($totalRevenue, 0) ?></div>
			<div class="stat-label">Total Revenue</div>
		</div>
	</div>

	<?php if ($err): ?>
		<div class="alert alert-error"><i class="fas fa-circle-exclamation"></i> <?= htmlspecialchars($err) ?></div>
	<?php endif; ?>
	<?php if ($success): ?>
		<div class="alert alert-success"><i class="fas fa-check-circle"></i> <?= htmlspecialchars($success) ?></div>
	<?php endif; ?>

	<section>
		<div class="section-card">
			<div style="padding:20px 24px; border-bottom:1px solid var(--border);">
				<h3 style="margin:0; font-size:1.05rem; display:flex; align-items:center; gap:8px;"><i class="fas fa-user-plus" style="color:var(--primary);"></i> Add New User</h3>
			</div>
			<div style="padding:24px;">
				<form method="post">
					<input type="hidden" name="action" value="create_user">
					<div class="form-row">
						<div class="input">
							<label><i class="fas fa-user" style="color:var(--muted); margin-right:4px;"></i> Name</label>
							<input name="name" required placeholder="Enter full name">
						</div>
						<div class="input">
							<label><i class="fas fa-envelope" style="color:var(--muted); margin-right:4px;"></i> Email</label>
							<input type="email" name="email" required placeholder="user@example.com">
						</div>
						<div class="input">
							<label><i class="fas fa-lock" style="color:var(--muted); margin-right:4px;"></i> Password</label>
							<input type="password" name="password" required placeholder="Min 6 characters">
						</div>
						<div class="input">
							<label><i class="fas fa-shield-halved" style="color:var(--muted); margin-right:4px;"></i> Role</label>
							<select name="role">
								<option value="user">User</option>
								<option value="admin">Admin</option>
							</select>
						</div>
					</div>
					<div style="margin-top:16px;">
						<button class="btn" type="submit"><i class="fas fa-plus"></i> Create User</button>
					</div>
				</form>
			</div>
		</div>
	</section>

	<section>
		<div class="section-header">
			<h2><i class="fas fa-users-gear"></i> User Management <span class="count-badge"><?= count($rows) ?> users</span></h2>
		</div>
		<div class="section-card">
			<div style="padding:16px 20px; border-bottom:1px solid var(--border);">
				<form class="search-row" method="get">
					<input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by name or email...">
					<button class="btn" type="submit"><i class="fas fa-search"></i> Search</button>
				</form>
			</div>
			<div class="table-wrapper">
				<?php if (empty($rows)): ?>
					<div class="empty-state">
						<i class="fas fa-users-slash"></i>
						<p>No users found. <?= $q ? 'Try a different search.' : 'Create your first user above.' ?></p>
					</div>
				<?php else: ?>
				<table class="table">
					<thead>
						<tr>
							<th><i class="fas fa-hashtag"></i> ID</th>
							<th><i class="fas fa-user"></i> Name</th>
							<th><i class="fas fa-envelope"></i> Email</th>
							<th><i class="fas fa-shield-halved"></i> Role</th>
							<th><i class="fas fa-gear"></i> Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($rows as $u): ?>
							<tr>
								<td><strong>#<?= (int)$u['id'] ?></strong></td>
								<td><?= htmlspecialchars($u['name']) ?></td>
								<td><?= htmlspecialchars($u['email']) ?></td>
								<td><span class="role-badge <?= $u['role'] ?>"><?= htmlspecialchars($u['role']) ?></span></td>
								<td>
									<div class="action-group">
										<details style="display:inline;">
											<summary class="btn secondary btn-sm" style="cursor:pointer;"><i class="fas fa-pen"></i> Edit</summary>
											<div class="edit-form">
												<form method="post">
													<input type="hidden" name="action" value="update_user">
													<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
													<div class="form-row-2">
														<div class="input">
															<label>Name</label>
															<input name="name" value="<?= htmlspecialchars($u['name']) ?>" required>
														</div>
														<div class="input">
															<label>Email</label>
															<input type="email" name="email" value="<?= htmlspecialchars($u['email']) ?>" required>
														</div>
														<div class="input">
															<label>Role</label>
															<select name="role">
																<option value="user" <?= $u['role']==='user'?'selected':'' ?>>User</option>
																<option value="admin" <?= $u['role']==='admin'?'selected':'' ?>>Admin</option>
															</select>
														</div>
														<div class="input">
															<label>New Password</label>
															<input type="password" name="new_password" placeholder="Leave blank">
														</div>
													</div>
													<div style="margin-top:12px;">
														<button class="btn btn-sm" type="submit"><i class="fas fa-save"></i> Save Changes</button>
													</div>
												</form>
											</div>
										</details>
										<?php if ((int)$u['id'] !== (int)current_user_id()): ?>
										<form method="post" onsubmit="return confirm('Delete this user?\nThis action cannot be undone.')">
											<input type="hidden" name="action" value="delete_user">
											<input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
											<button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
										</form>
										<?php endif; ?>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div>
	</section>

	<section>
		<div class="section-header">
			<h2><i class="fas fa-building-user"></i> Property Management <span class="count-badge"><?= count($props) ?> properties</span></h2>
		</div>
		<div class="section-card">
			<div style="padding:16px 20px; border-bottom:1px solid var(--border);">
				<form class="search-row" method="get">
					<input name="pq" value="<?= htmlspecialchars($pq) ?>" placeholder="Search by title, location, or owner...">
					<button class="btn" type="submit"><i class="fas fa-search"></i> Search</button>
				</form>
			</div>
			<div class="table-wrapper">
				<?php if (empty($props)): ?>
					<div class="empty-state">
						<i class="fas fa-building"></i>
						<p>No properties found. <?= $pq ? 'Try a different search.' : 'Properties will appear here once added.' ?></p>
					</div>
				<?php else: ?>
				<table class="table">
					<thead>
						<tr>
							<th><i class="fas fa-hashtag"></i> ID</th>
							<th><i class="fas fa-building"></i> Title</th>
							<th><i class="fas fa-user"></i> Owner</th>
							<th><i class="fas fa-house"></i> Type</th>
							<th><i class="fas fa-circle-check"></i> Status</th>
							<th><i class="fas fa-dollar-sign"></i> Price</th>
							<th><i class="fas fa-calendar"></i> Created</th>
							<th><i class="fas fa-gear"></i> Actions</th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ($props as $p): ?>
							<tr>
								<td><strong>#<?= (int)$p['id'] ?></strong></td>
								<td><?= htmlspecialchars($p['title']) ?></td>
								<td><?= htmlspecialchars($p['owner_email'] ?? ('#'.$p['owner_id'])) ?></td>
								<td style="text-transform:capitalize;"><?= htmlspecialchars($p['type'] ?? '') ?></td>
								<td><span class="role-badge <?= ($p['status']==='rented')?'rented':'available' ?>"><?= htmlspecialchars($p['status']) ?></span></td>
								<td><strong>$<?= number_format((float)$p['price'], 2) ?></strong></td>
								<td><?= date('M d, Y', strtotime($p['created_at'])) ?></td>
								<td>
									<div class="action-group">
										<details style="display:inline;">
											<summary class="btn secondary btn-sm" style="cursor:pointer;"><i class="fas fa-pen"></i> Edit</summary>
											<div class="edit-form">
												<form method="post">
													<input type="hidden" name="action" value="update_property">
													<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
													<div class="form-row-2">
														<div class="input">
															<label>Title</label>
															<input name="title" value="<?= htmlspecialchars($p['title']) ?>" required>
														</div>
														<div class="input">
															<label>Price ($)</label>
															<input type="number" step="0.01" name="price" value="<?= htmlspecialchars($p['price']) ?>" required>
														</div>
														<div class="input">
															<label>Location</label>
															<input name="location" value="<?= htmlspecialchars($p['location']) ?>" required>
														</div>
														<div class="input">
															<label>Type</label>
															<select name="type">
																<?php foreach (['apartment','house','studio'] as $t): ?>
																	<option value="<?= $t ?>" <?= ($p['type']??'')===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
																<?php endforeach; ?>
															</select>
														</div>
														<div class="input" style="grid-column:span 2;">
															<label>Description</label>
															<textarea name="description" rows="2"><?= htmlspecialchars($p['description'] ?? '') ?></textarea>
														</div>
														<div class="input">
															<label>Status</label>
															<select name="status">
																<option value="available" <?= $p['status']==='available'?'selected':'' ?>>Available</option>
																<option value="rented" <?= $p['status']==='rented'?'selected':'' ?>>Rented</option>
															</select>
														</div>
													</div>
													<div style="margin-top:12px;">
														<button class="btn btn-sm" type="submit"><i class="fas fa-save"></i> Save Changes</button>
													</div>
												</form>
											</div>
										</details>
										<form method="post" onsubmit="return confirm('Delete this property?\nAll related data will be removed.')">
											<input type="hidden" name="action" value="delete_property">
											<input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
											<button class="btn btn-danger btn-sm" type="submit"><i class="fas fa-trash"></i></button>
										</form>
									</div>
								</td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<?php endif; ?>
			</div>
		</div>
	</section>

</main>

<footer class="footer">
	<div class="container" style="display:flex; justify-content:space-between; align-items:center;">
		<span>&copy; <?= date('Y') ?> StayNest. Admin Panel.</span>
		<span style="color:var(--muted); font-size:.85rem;">Property Rental Management System</span>
	</div>
</footer>
</body>
</html>
