<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();

// Compute app root early for image URL normalization
$__dir = rtrim(str_replace('\\','/', dirname($_SERVER['SCRIPT_NAME'] ?? '/')), '/');
$__root = ($__dir && basename($__dir) === 'pages') ? rtrim(dirname($__dir), '/') : $__dir;
if ($__root === '') { $__root = '/'; } else { $__root .= '/'; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { http_response_code(404); echo 'Not found'; exit; }

$stmt = pdo()->prepare('SELECT * FROM properties WHERE id = ?');
$stmt->execute([$id]);
$p = $stmt->fetch();
if (!$p) { http_response_code(404); echo 'Not found'; exit; }
$saved = false;
if (current_user_id()) {
  $fs = pdo()->prepare('SELECT 1 FROM favorites WHERE user_id=? AND property_id=?');
  $fs->execute([current_user_id(), $id]);
  $saved = (bool)$fs->fetchColumn();
}
$imgs = [];
if (!empty($p['image_url'])) {
  $parts = array_filter(array_map('trim', explode(',', (string)$p['image_url'])));
  $imgs = $parts ?: [$p['image_url']];
  // Normalize to absolute project paths if not external URLs
  foreach ($imgs as &$u) {
    if (!preg_match('~^https?://|^data:~i', $u)) {
      $u = ltrim(preg_replace('~^\./|^\.\./~', '', $u), '/');
  $u = ($__root ?: '/') . $u;
    }
  }
  unset($u);
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($p['title']) ?> — StayNest</title>
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
    <div class="card">
  <?php if ($imgs): ?>
      <div class="thumb" style="padding-top:46%;">
        <div class="carousel" id="carousel" data-idx="0">
          <?php foreach ($imgs as $i => $u): ?>
            <img class="slide" alt="" src="<?= htmlspecialchars($u) ?>" style="display: <?= $i===0 ? 'block' : 'none' ?>;">
          <?php endforeach; ?>
          <?php if (count($imgs) > 1): ?>
            <button type="button" class="btn secondary prev" style="position:absolute; left:10px; bottom:10px;">◀</button>
            <button type="button" class="btn secondary next" style="position:absolute; right:10px; bottom:10px;">▶</button>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>
      <div class="body">
        <h1 style="margin:0;"><?= htmlspecialchars($p['title']) ?></h1>
        <?php if (($p['status'] ?? 'available') === 'rented'): ?>
          <div class="muted" style="margin:6px 0 0; font-weight:800; color:#b91c1c;">Rented</div>
        <?php endif; ?>
        <div class="meta"><?= htmlspecialchars($p['location']) ?> • <?= htmlspecialchars($p['type'] ?? '') ?></div>
        <div class="price" style="font-size:1.3rem;">Rs<?= number_format($p['price'], 2) ?>/mo</div>
        <p><?= nl2br(htmlspecialchars($p['description'])) ?></p>
        <div class="actions">
          <?php $isRented = (($p['status'] ?? 'available') === 'rented'); ?>
          <button class="btn secondary fav-btn <?= $saved?'on':'' ?>" data-pid="<?= $p['id'] ?>" aria-pressed="<?= $saved?'true':'false' ?>" <?= $isRented?'disabled title="Disabled for rented property"':'' ?>>❤ <?= $saved?'Saved':'Save' ?></button>
        </div>
      </div>
    </div>
  </section>

  <section class="section">
    <h2>Contact landlord</h2>
    <?php if (($p['status'] ?? 'available') === 'rented'): ?>
      <p class="muted">This property is currently rented. Inquiries are disabled.</p>
    <?php else: ?>
      <form id="contactForm" class="form-grid">
        <input type="hidden" name="property_id" value="<?= $p['id'] ?>">
        <div class="input">
          <label>Contact number (optional)</label>
          <input type="tel" name="phone" placeholder="e.g., +1 555 123 4567">
        </div>
        <div class="input">
          <label>Your message</label>
          <textarea name="message" rows="4" required placeholder="Hello, I'm interested in this property..."></textarea>
        </div>
        <button class="btn" type="submit">Send inquiry</button>
      </form>
    <?php endif; ?>
  </section>
</main>

<script src="<?= $__root ?>assets/js/script.js"></script>
<script>
  // basic carousel logic
  (function(){
    const root = document.getElementById('carousel');
    if (!root) return;
    const slides = Array.from(root.querySelectorAll('.slide'));
    if (slides.length < 2) return;
    let idx = 0;
    const show = (i) => {
      idx = (i + slides.length) % slides.length;
      slides.forEach((el, j)=> el.style.display = j===idx ? 'block' : 'none');
    };
    root.querySelector('.prev').addEventListener('click', ()=> show(idx-1));
    root.querySelector('.next').addEventListener('click', ()=> show(idx+1));
  })();
</script>
<?php require dirname(__DIR__) . '/includes/profile_drawer.php'; ?>
</body>
</html>
