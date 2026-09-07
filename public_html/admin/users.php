<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireAdmin();

$db = Database::get();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $userId = (int) ($_POST['user_id'] ?? 0);
    $action = $_POST['action'] ?? '';

    if ($action === 'add_credits') {
        $amount = (int) ($_POST['amount'] ?? 0);
        $db->prepare('UPDATE users SET credits_remaining = GREATEST(0, credits_remaining + ?) WHERE id = ?')
           ->execute([$amount, $userId]);
    } elseif ($action === 'toggle_active') {
        $db->prepare('UPDATE users SET is_active = 1 - is_active WHERE id = ?')->execute([$userId]);
    } elseif ($action === 'toggle_admin') {
        $db->prepare("UPDATE users SET role = IF(role = 'admin', 'user', 'admin') WHERE id = ?")->execute([$userId]);
    }
    header('Location: /admin/users.php');
    exit;
}

$users = $db->query('SELECT * FROM users ORDER BY id DESC')->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Users — Admin — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="logo"><span class="dot"></span>scrapefeed</div>
    <a href="/admin/index.php">Overview</a>
    <a href="/admin/users.php" class="active">Users</a>
    <a href="/admin/extractions.php">Extractions</a>
    <a href="/admin/settings.php">Settings</a>
    <a href="/admin/logout.php">Log out</a>
  </aside>
  <main class="main">
    <h1>Users</h1>
    <div class="card">
      <table>
        <tr><th>Email</th><th>Role</th><th>Plan</th><th>Credits</th><th>Active</th><th>Joined</th><th>Actions</th></tr>
        <?php foreach ($users as $u): ?>
        <tr>
          <td><?= e($u['email']) ?><br><span style="color:var(--muted);font-size:11.5px;"><?= e($u['name']) ?></span></td>
          <td><?= e($u['role']) ?></td>
          <td><?= e($u['plan']) ?></td>
          <td><?= (int) $u['credits_remaining'] ?></td>
          <td><?= $u['is_active'] ? 'Yes' : 'No' ?></td>
          <td style="color:var(--muted);"><?= e($u['created_at']) ?></td>
          <td>
            <form method="post" style="display:flex;gap:6px;align-items:center;">
              <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
              <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
              <input type="number" name="amount" placeholder="+/-" style="width:70px;margin:0;padding:6px;">
              <button type="submit" name="action" value="add_credits" class="btn btn-secondary" style="padding:6px 10px;font-size:11.5px;">Adjust</button>
              <button type="submit" name="action" value="toggle_active" class="btn btn-secondary" style="padding:6px 10px;font-size:11.5px;"><?= $u['is_active'] ? 'Disable' : 'Enable' ?></button>
              <button type="submit" name="action" value="toggle_admin" class="btn btn-secondary" style="padding:6px 10px;font-size:11.5px;"><?= $u['role'] === 'admin' ? 'Revoke admin' : 'Make admin' ?></button>
            </form>
          </td>
        </tr>
        <?php endforeach; ?>
      </table>
    </div>
  </main>
</div>
</body>
</html>
