<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();

if (current_user_id()) { header('Location: index.php'); exit; }

$err = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$email = trim($_POST['email'] ?? '');
		$pass = $_POST['password'] ?? '';
		if ($email && $pass) {
				$stmt = pdo()->prepare('SELECT id, password, role, name FROM users WHERE email = ?');
				$stmt->execute([$email]);
				$u = $stmt->fetch();
				if ($u && password_verify($pass, $u['password'])) {
						$_SESSION['user_id'] = $u['id'];
						$_SESSION['role'] = $u['role'];
						$_SESSION['name'] = $u['name'];
						if ($u['role'] === 'admin') { header('Location: dashboard.php'); exit; }
						header('Location: index.php');
						exit;
				}
				$err = 'Invalid credentials';
		} else {
				$err = 'Email and password required';
		}
}
?>
<!doctype html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>Login — StayNest</title>
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
				<a class="btn" href="signup.php">Sign up</a>
				<a class="btn secondary" href="signup.php?role=admin">Sign up as Admin</a>
			</nav>
		</div>
	</header>

	<main class="container">
		<div class="auth-card">
			<h1>Welcome back</h1>
			<?php if ($err): ?><p class="muted" style="color:#b91c1c;"><?= htmlspecialchars($err) ?></p><?php endif; ?>
			<form method="post" class="form-grid">
				<div class="input">
					<label>Email</label>
					<input type="email" name="email" required>
				</div>
				<div class="input">
					<label>Password</label>
					<input type="password" name="password" required>
				</div>
				<button class="btn" type="submit">Login</button>
				<p class="muted">No account? <a href="signup.php">Create one</a> or <a href="signup.php?role=admin">sign up as Admin</a></p>
			</form>
		</div>
	</main>
</body>
</html>
