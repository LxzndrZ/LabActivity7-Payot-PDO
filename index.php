<?php
require __DIR__ . '/auth.php';
require_login();

$uid = (int)$_SESSION['user_id'];
$errors = [];
$commentErrors = [];
$newPost = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';

  if ($action === 'add_post') {
    $newPost = trim($_POST['content'] ?? '');
    if ($newPost === '') $errors[] = 'Post cannot be empty.';
    elseif (mb_strlen($newPost) > 2000) $errors[] = 'Post is too long (max 2000 characters).';

    if (!$errors) {
      transaction($pdo, function () use ($pdo, $uid, $newPost) {
        $stmt = $pdo->prepare('INSERT INTO posts (user_id, content) VALUES (:uid, :content)');
        $stmt->execute(['uid' => $uid, 'content' => $newPost]);
      });
      header('Location: index.php');
      exit;
    }

  } elseif ($action === 'add_comment') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
      $commentErrors[$postId] = 'Comment cannot be empty.';
    } elseif (mb_strlen($content) > 1000) {
      $commentErrors[$postId] = 'Comment is too long (max 1000 characters).';
    } else {
      $added = transaction($pdo, function () use ($pdo, $postId, $uid, $content) {
        $stmt = $pdo->prepare('SELECT id FROM posts WHERE id = :id');
        $stmt->execute(['id' => $postId]);
        if (!$stmt->fetch()) return false;

        $stmt = $pdo->prepare('INSERT INTO comments (post_id, user_id, content) VALUES (:pid, :uid, :content)');
        $stmt->execute(['pid' => $postId, 'uid' => $uid, 'content' => $content]);
        return true;
      });

      if ($added) {
        header('Location: index.php');
        exit;
      }
      $commentErrors[$postId] = 'That post no longer exists.';
    }

  } elseif ($action === 'delete_post') {
    $id = (int)($_POST['id'] ?? 0);
    transaction($pdo, function () use ($pdo, $id, $uid) {
      $stmt = $pdo->prepare('DELETE FROM posts WHERE id = :id AND user_id = :uid');
      $stmt->execute(['id' => $id, 'uid' => $uid]);
    });
    header('Location: index.php');
    exit;

  } elseif ($action === 'delete_comment') {
    $id = (int)($_POST['id'] ?? 0);
    transaction($pdo, function () use ($pdo, $id, $uid) {
      $stmt = $pdo->prepare('DELETE FROM comments WHERE id = :id AND user_id = :uid');
      $stmt->execute(['id' => $id, 'uid' => $uid]);
    });
    header('Location: index.php');
    exit;
  }
}

$stmt = $pdo->prepare(
  'SELECT p.id, p.user_id, p.content, p.created_at, p.edited_at, u.name
   FROM posts p
   INNER JOIN users u ON u.id = p.user_id
   ORDER BY p.created_at DESC, p.id DESC'
);
$stmt->execute();
$posts = $stmt->fetchAll();

$comments = [];
$stmt = $pdo->prepare(
  'SELECT c.id, c.post_id, c.user_id, c.content, c.created_at, c.edited_at, u.name
   FROM comments c
   INNER JOIN users u ON u.id = c.user_id
   ORDER BY c.created_at ASC, c.id ASC'
);
$stmt->execute();
foreach ($stmt->fetchAll() as $c) {
  $comments[$c['post_id']][] = $c;
}

$pageTitle = 'Home';
require __DIR__ . '/header.php';
?>
<h2>News Feed</h2>

<form method="post" class="card">
  <input type="hidden" name="action" value="add_post">
  <label>What's on your mind?</label>
  <textarea name="content" rows="3" maxlength="2000" required><?= e($newPost) ?></textarea>
  <?php foreach ($errors as $err): ?><div class="error"><?= e($err) ?></div><?php endforeach; ?>
  <button type="submit">Post</button>
</form>

<?php if (!$posts): ?><p class="muted">No posts yet.</p><?php endif; ?>

<?php foreach ($posts as $p): ?>
  <div class="card">
    <strong><?= e($p['name']) ?></strong>
    <span class="muted">
      <?= e($p['created_at']) ?>
      <?php if ($p['edited_at']): ?> · (edited <?= e($p['edited_at']) ?>)<?php endif; ?>
    </span>
    <p><?= nl2br(e($p['content'])) ?></p>

    <?php if ((int)$p['user_id'] === $uid): ?>
      <a href="post-edit.php?id=<?= (int)$p['id'] ?>">Edit</a>
      <form method="post" class="inline" onsubmit="return confirm('Delete this post?')">
        <input type="hidden" name="action" value="delete_post">
        <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
        <button type="submit">Delete</button>
      </form>
    <?php endif; ?>

    <?php foreach ($comments[$p['id']] ?? [] as $c): ?>
      <div class="comment">
        <strong><?= e($c['name']) ?></strong>
        <span class="muted">
          <?= e($c['created_at']) ?>
          <?php if ($c['edited_at']): ?> · (edited <?= e($c['edited_at']) ?>)<?php endif; ?>
        </span>
        <div><?= nl2br(e($c['content'])) ?></div>
        <?php if ((int)$c['user_id'] === $uid): ?>
          <a href="comment-edit.php?id=<?= (int)$c['id'] ?>">Edit</a>
          <form method="post" class="inline" onsubmit="return confirm('Delete this comment?')">
            <input type="hidden" name="action" value="delete_comment">
            <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
            <button type="submit">Delete</button>
          </form>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <form method="post" style="margin-top:10px">
      <input type="hidden" name="action" value="add_comment">
      <input type="hidden" name="post_id" value="<?= (int)$p['id'] ?>">
      <input type="text" name="content" maxlength="1000" placeholder="Write a comment..." required>
      <?php if (!empty($commentErrors[$p['id']])): ?>
        <div class="error"><?= e($commentErrors[$p['id']]) ?></div>
      <?php endif; ?>
      <button type="submit">Comment</button>
    </form>
  </div>
<?php endforeach; ?>

<?php require __DIR__ . '/footer.php'; ?>