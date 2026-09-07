<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireAdmin();

$db = Database::get();
$totalUsers = (int) $db->query('SELECT COUNT(*) c FROM users')->fetch()['c'];
$totalExtractions = (int) $db->query('SELECT COUNT(*) c FROM extractions')->fetch()['c'];
$totalProducts = (int) $db->query('SELECT COALESCE(SUM(product_count),0) c FROM extractions')->fetch()['c'];
$pending = (int) $db->query("SELECT COUNT(*) c FROM extractions WHERE status IN ('pending','running')")->fetch()['c'];

$recent = $db->query(
    'SELECT e.*, u.email FROM extractions e JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT 20'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="logo"><span class="dot"></span>scrapefeed</div>
    <a href="/admin/index.php" class="active">Overview</a>
    <a href="/admin/users.php">Users</a>
    <a href="/admin/extractions.php">Extractions</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Log out</a>
  </aside>
  <main class="main">
    <h1>Overview</h1>
    <div class="stat-row">
      <div class="stat"><div class="num"><?= $totalUsers ?></div><div class="label">total users</div></div>
      <div class="stat"><div class="num"><?= $totalExtractions ?></div><div class="label">extractions run</div></div>
      <div class="stat"><div class="num"><?= number_format($totalProducts) ?></div><div class="label">products exported</div></div>
      <div class="stat"><div class="num"><?= $pending ?></div><div class="label">in queue right now</div></div>
    </div>

    <h1>Recent activity</h1>
    <div class="card">
      <table>
        <tr><th>User</th><th>Store</th><th>Platform</th><th>Products</th><th>Status</th><th>Started</th></tr>
        <?php foreach ($recent as $ex): ?>
        <tr>
          <td><?= e($ex['email']) ?></td>
          <td><?= e($ex['store_url']) ?></td>
          <td><?= e($ex['detected_platform'] ?: $ex['platform']) ?></td>
          <td><?= (int) $ex['product_count'] ?></td>
          <td><span class="badge badge-<?= e($ex['status']) ?>"><?= e($ex['status']) ?></span></td>
          <td style="color:var(--muted);"><?= e($ex['created_at']) ?></td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </main>
</div>
</body>
</html>
