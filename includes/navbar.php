<?php
// Simple navbar included by header
// Compute base path relative to the executing script so links work from root or /pages
$__base = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
if ($__base === '') { $__base = '/'; }
else { $__base .= '/'; }
?>
<div class="nav-left">
  <a class="brand" href="<?= $__base ?>index.php"><span class="logo"></span><span>StayNest</span></a>
  <?php if (current_user_id()): ?>
    <span class="muted">Hi, <?= htmlspecialchars($_SESSION['name'] ?? 'User') ?></span>
  <?php endif; ?>
  </div>
  <div class="nav-right">
    <a href="<?= $__base ?>index.php">Home</a>
    <a href="<?= $__base ?>properties.php">Properties</a>
    <?php if (is_admin()): ?><a href="<?= $__base ?>dashboard.php">Dashboard</a><?php endif; ?>
    <?php if (current_user_id()): ?>
      <a href="<?= $__base ?>tenant.php">Your listings</a>
      <a href="<?= $__base ?>saved.php">Saved</a>
    <?php endif; ?>
    <?php if (current_user_id()): ?>
      <button class="three-dot" id="btnProfile" aria-label="Profile menu">
        <span class="dot"></span><span class="dot"></span><span class="dot"></span>
      </button>
    <?php else: ?>
      <a class="btn secondary" href="<?= $__base ?>login.php">Login</a>
      <a class="btn" href="<?= $__base ?>signup.php">Sign up</a>
    <?php endif; ?>
  </div>
