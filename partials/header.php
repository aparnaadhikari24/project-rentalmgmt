<?php
// Shared header/nav. Requires backend/db.php loaded already.
ensure_session();
?>
<header class="header">
  <div class="container nav">
    <div class="nav-left">
      <a class="brand" href="index.php"><span class="logo"></span><span>StayNest</span></a>
      <?php if (current_user_id()): ?>
        <span class="muted">Hi, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
      <?php endif; ?>
    </div>
    <div class="nav-right">
      <a href="index.php">Home</a>
      <a href="properties.php">Properties</a>
      <?php if (current_user_id()): ?><a href="tenant.php">Your listings</a><?php endif; ?>
      <?php if (current_user_id()): ?>
        <button class="three-dot" id="btnProfile" aria-label="Profile menu">
          <span class="dot"></span><span class="dot"></span><span class="dot"></span>
        </button>
      <?php else: ?>
        <a class="btn secondary" href="login.php">Login</a>
        <a class="btn" href="signup.php">Sign up</a>
      <?php endif; ?>
    </div>
  </div>
</header>