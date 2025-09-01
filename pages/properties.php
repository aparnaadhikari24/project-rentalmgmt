<?php
require dirname(__DIR__) . '/backend/db.php';
ensure_session();

$q = trim($_GET['q'] ?? '');
$min = $_GET['min_price'] ?? '';
$max = $_GET['max_price'] ?? '';
$type = trim($_GET['type'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$per = 9;
$off = ($page-1)*$per;

$where = [];
$args = [];
if ($q !== '') { $where[] = '(title LIKE ? OR location LIKE ?)'; $args[] = "%$q%"; $args[] = "%$q%"; }
if ($min !== '') { $where[] = 'price >= ?'; $args[] = (float)$min; }
if ($max !== '') { $where[] = 'price <= ?'; $args[] = (float)$max; }
if ($type !== '') { $where[] = 'type = ?'; $args[] = $type; }
$wsql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$total = pdo()->prepare("SELECT COUNT(*) c FROM properties $wsql");
$total->execute($args);
$count = (int)$total->fetch()['c'];

$sql = pdo()->prepare("SELECT * FROM properties $wsql ORDER BY created_at DESC LIMIT $per OFFSET $off");
$sql->execute($args);
$rows = $sql->fetchAll();

$pages = max(1, (int)ceil($count / $per));
$favIds = [];
if (current_user_id()) {
  $fs = pdo()->prepare('SELECT property_id FROM favorites WHERE user_id = ?');
  $fs->execute([current_user_id()]);
  $favIds = array_map(fn($r)=> (int)$r['property_id'], $fs->fetchAll());
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Browse Properties — StayNest</title>
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
    <form id="searchForm" class="search-bar" method="get">
      <input name="q" value="<?= htmlspecialchars($q) ?>" placeholder="Search by city or title">
      <input name="min_price" value="<?= htmlspecialchars($min) ?>" type="number" placeholder="Min price">
      <input name="max_price" value="<?= htmlspecialchars($max) ?>" type="number" placeholder="Max price">
      <select name="type">
        <option value="">Any type</option>
        <?php foreach (['apartment','house','studio'] as $t): ?>
          <option value="<?= $t ?>" <?= $type===$t?'selected':'' ?>><?= ucfirst($t) ?></option>
        <?php endforeach; ?>
      </select>
      <button class="btn" type="submit">Filter</button>
    </form>
  </section>

  <section class="section">
    <h2>Results (<?= $count ?>)</h2>
    <div class="grid">
      <?php foreach ($rows as $r): ?>
        <article class="card">
          <a class="thumb" href="property.php?id=<?= $r['id'] ?>">
            <?php if (($r['status'] ?? 'available') === 'rented'): ?>
              <span class="badge rented">Rented</span>
            <?php endif; ?>
            <?php if (!empty($r['image_url'])):
              $img = (string)$r['image_url'];
              $isAbs = preg_match('~^https?://|^data:~i', $img);
              if (!$isAbs) {
                $img = ltrim(preg_replace('~^\./|^\.\./~', '', $img), '/');
                $img = $__root . $img; // project-root relative
              }
            ?>
              <img alt="" src="<?= htmlspecialchars($img) ?>">
            <?php endif; ?>
          </a>
          <div class="body">
            <div class="title"><a href="property.php?id=<?= $r['id'] ?>" style="text-decoration:none;color:inherit;"><?= htmlspecialchars($r['title']) ?></a></div>
            <div class="meta"><?= htmlspecialchars($r['location']) ?> • <?= htmlspecialchars($r['type'] ?? '') ?></div>
            <div class="actions">
              <span class="price">$<?= number_format($r['price'], 2) ?>/mo</span>
              <?php $saved = in_array((int)$r['id'], $favIds, true); ?>
              <button class="btn secondary fav-btn <?= $saved?'on':'' ?>" data-pid="<?= $r['id'] ?>" aria-pressed="<?= $saved?'true':'false' ?>">❤ <?= $saved?'Saved':'Save' ?></button>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>

    <div style="display:flex; gap:8px; justify-content:center; margin-top:16px;">
      <?php for ($i=1; $i<=$pages; $i++): $qs = $_GET; $qs['page']=$i; $href='?'.http_build_query($qs); ?>
        <a href="<?= $href ?>" class="btn <?= $i===$page?'':'secondary' ?>"><?= $i ?></a>
      <?php endfor; ?>
    </div>
  </section>
</main>

<script src="<?= $__root ?>assets/js/script.js"></script>
<?php require dirname(__DIR__) . '/includes/profile_drawer.php'; ?>
</body>
</html>
