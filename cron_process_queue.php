<?php
/**
 * Run via cPanel → Cron Jobs, e.g. every 5 minutes:
 *   php /home/YOURUSER/cron_process_queue.php
 *
 * Processes queued extractions one at a time so a single slow store
 * can't block the others indefinitely; increase MAX_JOBS_PER_RUN if
 * your host allows longer script execution.
 */
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/Auth.php';
require_once __DIR__ . '/includes/JobRunner.php';

set_time_limit(0); // CLI cron context, not a web request — safe to let this run long

const MAX_JOBS_PER_RUN = 5;

$db = Database::get();
$stmt = $db->query("SELECT id FROM extractions WHERE status = 'pending' ORDER BY id ASC LIMIT " . MAX_JOBS_PER_RUN);
$jobIds = $stmt->fetchAll(PDO::FETCH_COLUMN);

foreach ($jobIds as $id) {
    echo "Processing extraction #$id...\n";
    JobRunner::run((int) $id);
}

echo "Done. Processed " . count($jobIds) . " job(s).\n";
