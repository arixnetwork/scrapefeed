<?php
require_once __DIR__ . '/../../includes/bootstrap.php';

if (Auth::isAdmin()) {
    header('Location: /admin/index.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($email, $password) && Auth::isAdmin()) {
        header('Location: /admin/index.php');
        exit;
    }
    Auth::logout();
    $error = 'Incorrect credentials, or this account is not an admin.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin login — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<div class="auth-shell">
  <h1>Admin login</h1>
  <p class="sub">Restricted to site administrators.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required>
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit" class="btn btn-primary" style="width:100%;">Log in</button>
  </form>
</div>
</body>
</html>
