<?php
require_once __DIR__ . '/../includes/bootstrap.php';
Auth::requireLogin();

$id = (int) ($_GET['id'] ?? 0);
$user = Auth::user();

$stmt = Database::get()->prepare('SELECT * FROM extractions WHERE id = ? AND user_id = ?');
$stmt->execute([$id, $user['id']]);
$job = $stmt->fetch();

if (!$job || $job['status'] !== 'done' || !$job['result_file']) {
    http_response_code(404);
    die('Export not found.');
}

$path = EXPORTS_DIR . '/' . basename($job['result_file']); // basename() blocks path traversal
if (!file_exists($path)) {
    http_response_code(404);
    die('Export file is missing.');
}

$mime = $job['format'] === 'json' ? 'application/json' : 'text/csv';
$niceName = 'scrapefeed-export-' . $id . '.' . $job['format'];

header('Content-Type: ' . $mime);
header('Content-Disposition: attachment; filename="' . $niceName . '"');
header('Content-Length: ' . filesize($path));
readfile($path);
