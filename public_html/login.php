<?php
require_once __DIR__ . '/../includes/bootstrap.php';

if (Auth::check()) {
    header('Location: /dashboard.php');
    exit;
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (Auth::attempt($email, $password)) {
        header('Location: /dashboard.php');
        exit;
    }
    $error = 'Incorrect email or password.';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Log in — scrapefeed</title>
<link rel="stylesheet" href="/assets/style.css">
</head>
<body>
<nav>
  <div class="logo"><span class="dot"></span><a href="/">scrapefeed</a></div>
  <div class="links"><a href="/register.php">Sign up</a></div>
</nav>
<div class="auth-shell">
  <h1>Welcome back</h1>
  <p class="sub">Log in to run an extraction or download past exports.</p>
  <?php if ($error): ?><div class="alert alert-error"><?= e($error) ?></div><?php endif; ?>
  <form method="post">
    <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
    <label for="password">Password</label>
    <input type="password" id="password" name="password" required>
    <button type="submit" class="btn btn-primary" style="width:100%;">Log in</button>
  </form>
  <div class="auth-foot">No account yet? <a href="/register.php" style="color:var(--green);">Sign up free</a></div>
</div>
</body>
</html>
