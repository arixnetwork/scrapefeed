<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/JobRunner.php';
Auth::requireLogin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: /dashboard.php');
    exit;
}
csrf_check();

$user = Auth::user();

if ((int) $user['credits_remaining'] <= 0) {
    header('Location: /dashboard.php?error=out_of_credits');
    exit;
}

$storeUrl = trim($_POST['store_url'] ?? '');
$platform = in_array($_POST['platform'] ?? '', ['auto', 'shopify', 'woocommerce'], true) ? $_POST['platform'] : 'auto';
$format = ($_POST['format'] ?? 'csv') === 'json' ? 'json' : 'csv';
$limit = isset($_POST['limit']) && $_POST['limit'] !== '' ? max(1, min(5000, (int) $_POST['limit'])) : null;

if ($storeUrl === '') {
    header('Location: /dashboard.php?error=missing_url');
    exit;
}

$db = Database::get();
$stmt = $db->prepare(
    'INSERT INTO extractions (user_id, store_url, platform, format, product_limit, status)
     VALUES (?, ?, ?, ?, ?, "pending")'
);
$stmt->execute([$user['id'], $storeUrl, $platform, $format, $limit]);
$extractionId = (int) $db->lastInsertId();

// Small jobs run inline for instant feedback. Larger/unbounded ones are
// left pending for cron_process_queue.php so a page request never has
// to hold a long-running crawl open on shared hosting.
$INLINE_THRESHOLD = 300;
if ($limit !== null && $limit <= $INLINE_THRESHOLD) {
    set_time_limit(55);
    JobRunner::run($extractionId);
}

header('Location: /dashboard.php');
