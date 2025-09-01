<?php
require_once __DIR__ . '/../database/db.php';
ensure_session();
?>
<header class="header">
  <div class="container nav">
    <?php
      // Compute app root (handles being served from / or /final project or /final project/pages)
      $__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
      $__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
      if ($__root === '') { $__root = '/'; }
      else { $__root .= '/'; }
    ?>
    <script>
      window.APP_ROOT = <?= json_encode($__root) ?>;
    </script>
    <?php require __DIR__ . '/navbar.php'; ?>
  </div>
</header>