<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();

if (current_user_id()) { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$name = trim($_POST['name'] ?? '');
		$email = trim($_POST['email'] ?? '');
		$pass = $_POST['password'] ?? '';
		$role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
		if ($name && $email && $pass) {
				try {
						$hash = password_hash($pass, PASSWORD_DEFAULT);
						$stmt = pdo()->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
						$stmt->execute([$name, $email, $hash, $role]);
						$_SESSION['user_id'] = pdo()->lastInsertId();
						$_SESSION['role'] = $role;
						$_SESSION['name'] = $name;
						if ($role === 'admin') { header('Location: dashboard.php'); exit; }
						header('Location: index.php');
						exit;
				} catch (PDOException $e) {
						if ($e->getCode() == 23000) { $err = 'Email already registered'; }
						else { $err = 'Error creating account'; }
				}
		} else {
				$err = 'All fields required';
		}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Sign up — StayNest</title>
	<?php
		$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
		$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
		if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }
	?>
	<link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
</head>
<body>
	<header class="header">
		<div class="container nav">
			<a class="brand" href="index.php"><span class="logo"></span><span>StayNest</span></a>
			<nav>
				<a href="index.php">Home</a>
				<a href="properties.php">Properties</a>
				<a class="btn" href="login.php">Login</a>
			</nav>
		</div>
	</header>

	<main class="container">
		<div class="auth-card">
			<h1>Create your account</h1>
			<?php if ($err): ?><p class="muted" style="color:#b91c1c;"><?= htmlspecialchars($err) ?></p><?php endif; ?>
			<form method="post" class="form-grid">
				<div class="input">
					<label>Name</label>
					<input name="name" required>
				</div>
				<div class="input">
					<label>Email</label>
					<input type="email" name="email" required>
				</div>
				<div class="input">
					<label>Password</label>
					<input type="password" name="password" required>
				</div>
				<div class="input">
					<label>Account type</label>
					<?php $pref = ($_GET['role'] ?? '') === 'admin' ? 'admin' : 'user'; ?>
					<select name="role">
						<option value="user" <?= $pref==='user'?'selected':'' ?>>User</option>
						<option value="admin" <?= $pref==='admin'?'selected':'' ?>>Admin</option>
					</select>
				</div>
				<button class="btn" type="submit">Sign up</button>
				<p class="muted">Already have an account? <a href="login.php">Login</a></p>
			</form>
		</div>
	</main>
</body>
</html>
