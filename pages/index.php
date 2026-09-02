<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();
// Load recent properties for the Featured section
$featured = [];
try {
  $st = pdo()->query("SELECT id, title, location, type, price, image_url, status FROM properties ORDER BY created_at DESC LIMIT 6");
  $featured = $st->fetchAll();
} catch (Throwable $e) {
  $featured = [];
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>StayNest — Find your next rental</title>
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
    <section class="hero">
      <div class="wrap">
        <div>
          <h1>Find beautiful places to live, anywhere</h1>
          <p>Search thousands of verified rental listings with transparent prices and modern amenities.</p>
          <form id="searchForm" class="search-bar" action="properties.php" method="get">
            <input name="q" placeholder="City, neighborhood, or address" aria-label="Location">
            <input name="min_price" type="number" placeholder="Min price" aria-label="Minimum price">
            <input name="max_price" type="number" placeholder="Max price" aria-label="Maximum price">
            <select name="type" aria-label="Property type">
              <option value="">Any type</option>
              <option value="apartment">Apartment</option>
              <option value="house">House</option>
              <option value="studio">Studio</option>
            </select>
            <button class="btn" type="submit">Search</button>
          </form>
        </div>
        <div>
          <div class="card" style="padding:16px;">
            <div class="body">
              <div class="title">Why StayNest?</div>
              <div class="meta">• Clean, modern listings<br>• Direct landlord contact<br>• Safe and secure</div>
              <a class="btn" href="properties.php" style="margin-top:8px;">Browse Properties</a>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="section">
      <h2>Featured properties</h2>
      <?php if (!$featured): ?>
        <p class="muted">No properties yet. Try browsing <a href="properties.php">all properties</a>.</p>
      <?php else: ?>
      <div class="grid">
        <?php foreach ($featured as $r): ?>
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
                <span class="price">Rs<?= number_format((float)$r['price'], 2) ?>/mo</span>
                <a class="btn secondary" href="property.php?id=<?= (int)$r['id'] ?>">View</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
      <?php endif; ?>
    </section>
  </main>

  <?php require dirname(__DIR__) . '/includes/footer.php'; ?>
  <?php require dirname(__DIR__) . '/includes/profile_drawer.php'; ?>
  <script src="<?= $__root ?>assets/js/script.js"></script>
</body>
</html>
