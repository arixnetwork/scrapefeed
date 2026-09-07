<?php
// Copy to config.php and fill in, OR let /install/index.php generate it for you.

define('DB_HOST', 'localhost');
define('DB_NAME', 'cpaneluser_scrapefeed');
define('DB_USER', 'cpaneluser_scrapefeed');
define('DB_PASS', 'change-me');

// Absolute filesystem path to the folder where exported CSV/JSON files
// are written. Must be OUTSIDE public_html so files aren't guessable —
// download.php streams them with an ownership check instead.
define('EXPORTS_DIR', __DIR__ . '/../exports');

define('APP_INSTALLED', true);
