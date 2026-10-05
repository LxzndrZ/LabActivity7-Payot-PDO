<?php
require __DIR__ . '/auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM posts WHERE id = :id AND user_id = :uid');
$stmt->execute(['id' => $id, 'uid' => $uid]);
$post = $stmt->fetch();

if (!$post) {
  header('Location: index.php');
  exit;
}

$errors = [];
$content = $post['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $content = trim($_POST['content'] ?? '');

  if ($content === '') $errors[] = 'Post cannot be empty.';
  elseif (mb_strlen($content) > 2000) $errors[] = 'Post is too long (max 2000 characters).';

  if (!$errors) {
    if ($content !== $post['content']) {
      transaction($pdo, function () use ($pdo, $content, $id, $uid) {
        $stmt = $pdo->prepare('UPDATE posts SET content = :content, edited_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute(['content' => $content, 'id' => $id, 'uid' => $uid]);
      });
    }
    header('Location: index.php');
    exit;
  }
}

$pageTitle = 'Edit post';
require __DIR__ . '/header.php';
?>
<h2>Edit post</h2>
<?php foreach ($errors as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post">
  <textarea name="content" rows="4" maxlength="2000" required><?= e($content) ?></textarea>
  <button type="submit">Save changes</button>
  <a href="index.php">Cancel</a>
</form>
<?php require __DIR__ . '/footer.php'; ?>