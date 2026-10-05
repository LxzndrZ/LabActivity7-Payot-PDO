<?php
require __DIR__ . '/auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM comments WHERE id = :id AND user_id = :uid');
$stmt->execute(['id' => $id, 'uid' => $uid]);
$comment = $stmt->fetch();

if (!$comment) {
  header('Location: index.php');
  exit;
}

$errors = [];
$content = $comment['content'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $content = trim($_POST['content'] ?? '');

  if ($content === '') $errors[] = 'Comment cannot be empty.';
  elseif (mb_strlen($content) > 1000) $errors[] = 'Comment is too long (max 1000 characters).';

  if (!$errors) {
    if ($content !== $comment['content']) {
      transaction($pdo, function () use ($pdo, $content, $id, $uid) {
        $stmt = $pdo->prepare('UPDATE comments SET content = :content, edited_at = NOW() WHERE id = :id AND user_id = :uid');
        $stmt->execute(['content' => $content, 'id' => $id, 'uid' => $uid]);
      });
    }
    header('Location: index.php');
    exit;
  }
}

$pageTitle = 'Edit comment';
require __DIR__ . '/header.php';
?>
<h2>Edit comment</h2>
<?php foreach ($errors as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>

<form method="post">
  <input type="text" name="content" maxlength="1000" required value="<?= e($content) ?>">
  <button type="submit">Save changes</button>
  <a href="index.php">Cancel</a>
</form>
<?php require __DIR__ . '/footer.php'; ?>