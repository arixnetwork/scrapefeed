<?php
declare(strict_types=1);
session_start();

$configPath = dirname(__DIR__, 2) . '/includes/config.php';
$alreadyInstalled = file_exists($configPath);

$step = $_POST['step'] ?? ($alreadyInstalled ? 'done' : 'db');
$error = '';

function render($step, $error, $data = [])
{
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Set up scrapefeed</title>
    <link rel="stylesheet" href="/assets/style.css">
    </head>
    <body>
    <div class="auth-shell" style="max-width:480px;">
      <h1>Set up scrapefeed</h1>
      <p class="sub">Step <?= $step === 'db' ? '1 of 2 — database' : ($step === 'admin' ? '2 of 2 — admin account' : 'done') ?></p>
      <?php if ($error): ?><div class="alert alert-error"><?= htmlspecialchars($error) ?></div><?php endif; ?>

      <?php if ($step === 'db'): ?>
        <form method="post">
          <input type="hidden" name="step" value="db">
          <label>Database host</label>
          <input type="text" name="db_host" value="localhost" required>
          <label>Database name</label>
          <input type="text" name="db_name" required placeholder="cpaneluser_scrapefeed">
          <label>Database user</label>
          <input type="text" name="db_user" required placeholder="cpaneluser_scrapefeed">
          <label>Database password</label>
          <input type="password" name="db_pass">
          <button type="submit" class="btn btn-primary" style="width:100%;">Test connection &amp; continue</button>
        </form>
        <p class="auth-foot">Create the database and a MySQL user for it first in cPanel → MySQL Databases, then enter those details here.</p>

      <?php elseif ($step === 'admin'): ?>
        <form method="post">
          <input type="hidden" name="step" value="admin">
          <?php foreach (['db_host','db_name','db_user','db_pass'] as $f): ?>
            <input type="hidden" name="<?= $f ?>" value="<?= htmlspecialchars($data[$f] ?? '') ?>">
          <?php endforeach; ?>
          <label>Site name</label>
          <input type="text" name="site_name" value="scrapefeed" required>
          <label>Admin email</label>
          <input type="email" name="admin_email" required>
          <label>Admin password</label>
          <input type="password" name="admin_password" minlength="8" required>
          <button type="submit" class="btn btn-primary" style="width:100%;">Finish setup</button>
        </form>

      <?php else: ?>
        <p class="sub">scrapefeed is already installed.</p>
        <a href="/admin/login.php" class="btn btn-primary">Go to admin login</a>
      <?php endif; ?>
    </div>
    </body>
    </html>
    <?php
}

if ($alreadyInstalled && $step !== 'done') {
    render('done', '');
    exit;
}

if ($step === 'db' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $_SESSION['install_db'] = compact('host', 'name', 'user', 'pass');
        render('admin', '', ['db_host' => $host, 'db_name' => $name, 'db_user' => $user, 'db_pass' => $pass]);
    } catch (Throwable $e) {
        render('db', 'Could not connect: ' . $e->getMessage());
    }
    exit;
}

if ($step === 'admin' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $host = trim($_POST['db_host'] ?? '');
    $name = trim($_POST['db_name'] ?? '');
    $user = trim($_POST['db_user'] ?? '');
    $pass = $_POST['db_pass'] ?? '';
    $siteName = trim($_POST['site_name'] ?? 'scrapefeed');
    $adminEmail = trim($_POST['admin_email'] ?? '');
    $adminPassword = $_POST['admin_password'] ?? '';

    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL) || strlen($adminPassword) < 8) {
        render('admin', 'Enter a valid email and an 8+ character password.', compact('host', 'name', 'user', 'pass') + ['db_host' => $host, 'db_name' => $name, 'db_user' => $user, 'db_pass' => $pass]);
        exit;
    }

    try {
        $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // run schema.sql
        $sql = file_get_contents(dirname(__DIR__, 2) . '/includes/schema.sql');
        foreach (array_filter(array_map('trim', explode(';', $sql))) as $statement) {
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $pdo->prepare('UPDATE settings SET setting_value = ? WHERE setting_key = "site_name"')->execute([$siteName]);

        // create admin user
        $hash = password_hash($adminPassword, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare(
            'INSERT INTO users (email, password_hash, name, role, plan, credits_remaining)
             VALUES (?, ?, "Admin", "admin", "unlimited", 999999)
             ON DUPLICATE KEY UPDATE password_hash = VALUES(password_hash), role = "admin"'
        );
        $stmt->execute([strtolower($adminEmail), $hash]);

        // write config.php — account root/exports, deliberately outside public_html
        $exportsDir = dirname(__DIR__, 2) . '/exports';
        $configContents = "<?php\n"
            . "define('DB_HOST', " . var_export($host, true) . ");\n"
            . "define('DB_NAME', " . var_export($name, true) . ");\n"
            . "define('DB_USER', " . var_export($user, true) . ");\n"
            . "define('DB_PASS', " . var_export($pass, true) . ");\n"
            . "define('EXPORTS_DIR', " . var_export($exportsDir, true) . ");\n"
            . "define('APP_INSTALLED', true);\n";

        file_put_contents($configPath, $configContents);
        @mkdir($exportsDir, 0750, true);

        render('done', '');
    } catch (Throwable $e) {
        render('admin', 'Setup failed: ' . $e->getMessage(), compact('host', 'name', 'user', 'pass') + ['db_host' => $host, 'db_name' => $name, 'db_user' => $user, 'db_pass' => $pass]);
    }
    exit;
}

render($step, $error);
