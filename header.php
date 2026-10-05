<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($pageTitle ?? 'Blog') ?></title>
  <style>
    body{font-family:Arial,sans-serif;max-width:700px;margin:20px auto;padding:0 12px}
    nav{display:flex;justify-content:space-between;border-bottom:1px solid #ccc;padding-bottom:8px;margin-bottom:16px}
    .card{border:1px solid #ddd;border-radius:6px;padding:12px;margin-bottom:12px}
    .comment{margin-left:20px;border-left:3px solid #eee;padding-left:10px;margin-top:8px}
    .error{color:#b00020;margin-bottom:8px}
    .ok{color:#1b7f3b;margin-bottom:8px}
    .muted{color:#666;font-size:.85em}
    textarea,input[type=text],input[type=email],input[type=password]{width:100%;padding:6px;box-sizing:border-box;margin:4px 0 10px}
    form.inline{display:inline}
  </style>
</head>
<body>
<nav>
  <strong>Blog Site</strong>
  <div>
    <?php if (!empty($_SESSION['user_id'])): ?>
      Hi, <?= e($_SESSION['name']) ?> | <a href="logout.php">Logout</a>
    <?php else: ?>
      <a href="login.php">Login</a> | <a href="register.php">Register</a>
    <?php endif; ?>
  </div>
</nav>