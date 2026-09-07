<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$user = Auth::user();
$db = Database::get();

$stmt = $db->prepare('SELECT * FROM extractions WHERE user_id = ? ORDER BY id DESC LIMIT 50');
$stmt->execute([$user['id']]);
$extractions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Dashboard — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="shell">
  <aside class="sidebar">
    <div class="logo"><span class="dot"></span>scrapefeed</div>
    <a href="/dashboard.php" class="active">Dashboard</a>
    <a href="/logout.php">Log out</a>
  </aside>

  <main class="main">
    <h1>New extraction</h1>

    <?php if (($_GET['error'] ?? '') === 'out_of_credits'): ?>
      <div class="alert alert-error">You're out of credits. Contact support or wait for your plan to renew.</div>
    <?php elseif (($_GET['error'] ?? '') === 'missing_url'): ?>
      <div class="alert alert-error">Enter a store URL to extract from.</div>
    <?php endif; ?>

    <div class="stat-row">
      <div class="stat"><div class="num"><?= (int) $user['credits_remaining'] ?></div><div class="label">credits remaining</div></div>
      <div class="stat"><div class="num"><?= e(ucfirst($user['plan'])) ?></div><div class="label">plan</div></div>
      <div class="stat"><div class="num"><?= count($extractions) ?></div><div class="label">extractions run</div></div>
      <div class="stat"><div class="num"><?= (int) array_sum(array_column($extractions, 'product_count')) ?></div><div class="label">products exported</div></div>
    </div>

    <div class="card" style="margin-bottom:32px;">
      <form method="post" action="/new-extraction.php">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label for="store_url">Store URL</label>
        <input type="text" id="store_url" name="store_url" placeholder="shop.example.com" required>
        <div class="form-row">
          <div>
            <label for="platform">Platform</label>
            <select id="platform" name="platform">
              <option value="auto">Auto-detect</option>
              <option value="shopify">Shopify</option>
              <option value="woocommerce">WooCommerce</option>
            </select>
          </div>
          <div>
            <label for="format">Format</label>
            <select id="format" name="format">
              <option value="csv">CSV</option>
              <option value="json">JSON</option>
            </select>
          </div>
          <div>
            <label for="limit">Product limit</label>
            <input type="number" id="limit" name="limit" min="1" max="5000" placeholder="No limit">
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Run extraction</button>
      </form>
    </div>

    <h1>History</h1>
    <div class="card">
      <table>
        <tr><th>Store</th><th>Platform</th><th>Format</th><th>Products</th><th>Status</th><th>Started</th><th></th></tr>
        <?php if (!$extractions): ?>
          <tr><td colspan="7" style="color:var(--muted);">No extractions yet — run your first one above.</td></tr>
        <?php endif; ?>
        <?php foreach ($extractions as $ex): ?>
        <tr>
          <td><?= e($ex['store_url']) ?></td>
          <td><?= e($ex['detected_platform'] ?: $ex['platform']) ?></td>
          <td><?= e(strtoupper($ex['format'])) ?></td>
          <td><?= (int) $ex['product_count'] ?></td>
          <td><span class="badge badge-<?= e($ex['status']) ?>"><?= e($ex['status']) ?></span>
            <?php if ($ex['status'] === 'failed' && $ex['error_message']): ?>
              <div style="color:var(--muted);font-size:11.5px;margin-top:4px;"><?= e($ex['error_message']) ?></div>
            <?php endif; ?>
          </td>
          <td style="color:var(--muted);"><?= e($ex['created_at']) ?></td>
          <td>
            <?php if ($ex['status'] === 'done' && $ex['result_file']): ?>
              <a href="/download.php?id=<?= (int) $ex['id'] ?>" class="btn btn-secondary" style="padding:6px 12px;font-size:12px;">Download</a>
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
