<?php
require __DIR__ . '/auth.php';
require_guest();

$errors = [];
$name = $email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name = trim($_POST['name'] ?? '');
  $email = trim($_POST['email'] ?? '');
  $password = $_POST['password'] ?? '';
  $confirm = $_POST['confirm'] ?? '';

  if ($name === '' || mb_strlen($name) > 100) $errors[] = 'Name is required (max 100 characters).';
  if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Enter a valid email.';
  if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
  if ($password !== $confirm) $errors[] = 'Passwords do not match.';

  if (!$errors) {
    $created = false;
    try {
      $created = transaction($pdo, function () use ($pdo, $name, $email, $password) {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = :email');
        $stmt->execute(['email' => $email]);
        if ($stmt->fetch()) return false;

        $stmt = $pdo->prepare('INSERT INTO users (name, email, password) VALUES (:name, :email, :password)');
        $stmt->execute([
          'name' => $name,
          'email' => $email,
          'password' => password_hash($password, PASSWORD_DEFAULT),
        ]);
        return true;
      });
      if (!$created) $errors[] = 'That email is already registered.';
    } catch (PDOException $e) {
      $errors[] = 'Could not create the account. Please try again.';
    }

    if ($created) {
      header('Location: login.php?registered=1');
      exit;
    }
  }
}

$pageTitle = 'Register';
require __DIR__ . '/header.php';
?>
<h2>Register</h2>
<?php foreach ($errors as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post" onsubmit="return checkMatch()">
  <label>Name</label>
  <input type="text" name="name" maxlength="100" required value="<?= e($name) ?>">
  <label>Email</label>
  <input type="email" name="email" required value="<?= e($email) ?>">
  <label>Password (min 8 characters)</label>
  <input type="password" name="password" id="password" minlength="8" required>
  <label>Confirm password</label>
  <input type="password" name="confirm" id="confirm" required>
  <button type="submit">Register</button>
</form>
<p>Already have an account? <a href="login.php">Login</a></p>

<script>
function checkMatch() {
  if (document.getElementById('password').value !== document.getElementById('confirm').value) {
    alert('Passwords do not match.');
    return false;
  }
  return true;
}
</script>
<?php require __DIR__ . '/footer.php'; ?>