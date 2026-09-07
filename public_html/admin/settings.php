<?php
require_once __DIR__ . '/../../includes/bootstrap.php';
Auth::requireAdmin();

$saved = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    Settings::set('site_name', trim($_POST['site_name'] ?? 'scrapefeed'));
    Settings::set('starter_credits', (string) max(0, (int) ($_POST['starter_credits'] ?? 200)));
    $saved = true;
}

$siteName = Settings::get('site_name', 'scrapefeed');
$starterCredits = Settings::get('starter_credits', '200');
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Settings — Admin — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="logo"><span class="dot"></span>scrapefeed</div>
    <a href="/admin/index.php">Overview</a>
    <a href="/admin/users.php">Users</a>
    <a href="/admin/extractions.php">Extractions</a>
    <a href="/admin/settings.php" class="active">Settings</a>
    <a href="/admin/logout.php">Log out</a>
  </aside>
  <main class="main">
    <h1>Site settings</h1>
    <?php if ($saved): ?><div class="alert alert-ok">Saved.</div><?php endif; ?>
    <div class="card" style="max-width:480px;">
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="site_name">Site name</label>
        <input type="text" id="site_name" name="site_name" value="<?= e($siteName) ?>">
        <label for="starter_credits">Free credits for new signups</label>
        <input type="number" id="starter_credits" name="starter_credits" value="<?= e($starterCredits) ?>">
        <button type="submit" class="btn btn-primary">Save settings</button>
      </form>
    </div>
  </main>
</div>
</body>
</html>
