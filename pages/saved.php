<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();
if (!current_user_id()) { header('Location: login.php'); exit; }

// Compute app root for assets and image normalization
$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }

// Fetch saved properties for the user
$uid = current_user_id();
$st = pdo()->prepare('SELECT p.* FROM favorites f JOIN properties p ON p.id=f.property_id WHERE f.user_id=? ORDER BY f.id DESC');
$st->execute([$uid]);
$rows = $st->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Saved Properties — StayNest</title>
  <link rel="stylesheet" href="<?= $__root ?>assets/css/style.css">
</head>
<body>
<?php require dirname(__DIR__) . '/includes/header.php'; ?>

<main class="container">
  <section class="section">
    <h2>Saved properties</h2>
    <?php if (!$rows): ?>
      <p class="muted">You haven’t saved any properties yet. Browse <a href="properties.php">all properties</a> and tap ❤ Save.</p>
    <?php else: ?>
    <div class="grid">
      <?php foreach ($rows as $r): ?>
        <article class="card">
          <a class="thumb" href="property.php?id=<?= (int)$r['id'] ?>">
            <?php if (($r['status'] ?? 'available') === 'rented'): ?>
              <span class="badge rented">Rented</span>
            <?php endif; ?>
            <?php if (!empty($r['image_url'])):
              $img = (string)$r['image_url'];
              $isAbs = preg_match('~^https?://|^data:~i', $img);
              if (!$isAbs) {
                $img = ltrim(preg_replace('~^\./|^\.\./~', '', $img), '/');
                $img = $__root . $img;
              }
            ?>
              <img alt="" src="<?= htmlspecialchars($img) ?>">
            <?php endif; ?>
          </a>
          <div class="body">
            <div class="title"><a href="property.php?id=<?= (int)$r['id'] ?>" style="text-decoration:none;color:inherit;"><?= htmlspecialchars($r['title']) ?></a></div>
            <div class="meta"><?= htmlspecialchars($r['location']) ?> • <?= htmlspecialchars($r['type'] ?? '') ?></div>
            <div class="actions">
              <span class="price">$<?= number_format((float)$r['price'], 2) ?>/mo</span>
              <button class="btn secondary fav-btn on" data-pid="<?= (int)$r['id'] ?>" aria-pressed="true">❤ Saved</button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </section>
</main>

<script src="<?= $__root ?>assets/js/script.js"></script>
<?php require dirname(__DIR__) . '/includes/profile_drawer.php'; ?>
</body>
</html>
