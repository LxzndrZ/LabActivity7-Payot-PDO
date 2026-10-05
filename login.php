<?php
require __DIR__ . '/auth.php';
require_guest();

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';

  if ($email === '' || $password === '') {
    $errors[] = 'Email and password are required.';
  } else {
    $stmt = $pdo->prepare('SELECT * FROM users WHERE email = :email');
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password'])) {
      session_regenerate_id(true);
      $_SESSION['user_id'] = $user['id'];
      $_SESSION['name'] = $user['name'];
      header('Location: index.php');
      exit;
    }
    $errors[] = 'Invalid email or password.';
  }
}

$pageTitle = 'Login';
require __DIR__ . '/header.php';
?>
<h2>Login</h2>
<?php if (!empty($_GET['registered'])): ?><div class="ok">Account created. Please log in.</div><?php endif; ?>
<?php foreach ($errors as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post">
  <label>Email</label>
  <input type="email" name="email" required value="<?= e($email) ?>">
  <label>Password</label>
  <input type="password" name="password" required>
  <button type="submit">Login</button>
</form>
<p>No account? <a href="register.php">Register</a></p>
<?php require __DIR__ . '/footer.php'; ?>