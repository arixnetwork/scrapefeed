<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireAdmin();

$db = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) ($_POST['id'] ?? 0);
    if (($_POST['action'] ?? '') === 'requeue') {
        $db->prepare("UPDATE extractions SET status = 'pending', error_message = NULL WHERE id = ?")->execute([$id]);
    }
    header('Location: /admin/extractions.php');
    exit;
}

$jobs = $db->query(
    'SELECT e.*, u.email FROM extractions e JOIN users u ON u.id = e.user_id ORDER BY e.id DESC LIMIT 100'
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Extractions — Admin — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="logo"><span class="dot"></span>scrapefeed</div>
    <a href="/admin/index.php">Overview</a>
    <a href="/admin/users.php">Users</a>
    <a href="/admin/extractions.php" class="active">Extractions</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Log out</a>
  </aside>
  <main class="main">
    <h1>Extractions</h1>
    <div class="card">
      <table>
        <tr><th>User</th><th>Store</th><th>Platform</th><th>Products</th><th>Status</th><th>Started</th><th></th></tr>
        <?php foreach ($jobs as $j): ?>
        <tr>
          <td><?= e($j['email']) ?></td>
          <td><?= e($j['store_url']) ?></td>
          <td><?= e($j['detected_platform'] ?: $j['platform']) ?></td>
          <td><?= (int) $j['product_count'] ?></td>
          <td>
            <span class="badge badge-<?= e($j['status']) ?>"><?= e($j['status']) ?></span>
            <?php if ($j['error_message']): ?><div style="color:var(--muted);font-size:11px;"><?= e($j['error_message']) ?></div><?php endif; ?>
          </td>
          <td style="color:var(--muted);"><?= e($j['created_at']) ?></td>
          <td>
            <?php if ($j['status'] === 'failed'): ?>
            <form method="post">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="id" value="<?= (int) $j['id'] ?>">
              <button type="submit" name="action" value="requeue" class="btn btn-secondary" style="padding:6px 10px;font-size:11.5px;">Requeue</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </main>
</div>
</body>
</html>
