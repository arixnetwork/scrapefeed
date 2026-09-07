<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    [$ok, $result] = Auth::register($email, $password, $name);
    if ($ok) {
        Auth::attempt($email, $password);
        header('Location: /dashboard.php');
        exit;
    }
    $error = $result;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Create your account — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav>
  <div class="logo"><span class="dot"></span><a href="/">scrapefeed</a></div>
  <div class="links"><a href="/login.php">Log in</a></div>
</nav>
<div class="auth-shell">
  <h1>Create your account</h1>
  <p class="sub">Get <?= e(Settings::get('starter_credits', '200')) ?> free product extractions to start.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label for="name">Name</label>
    <input type="text" id="name" name="name" value="<?= e($_POST['name'] ?? '') ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required minlength="8">
    <button type="submit" class="btn btn-primary" style="width:100%;">Create account</button>
  </form>
  <div class="auth-foot">Already have an account? <a href="/login.php" style="color:var(--green);">Log in</a></div>
</div>
</body>
</html>
