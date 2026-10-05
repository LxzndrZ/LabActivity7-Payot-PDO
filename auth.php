<?php
require __DIR__ . '/db.php';
session_start();

function require_login() {
  if (empty($_SESSION['user_id'])) { header('Location: login.php'); exit; }
}
function require_guest() {
  if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }
}
function e($s) {
  return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

// Runs $work inside a transaction: commit if it works, rollBack if it fails
function transaction(PDO $pdo, callable $work) {
  $pdo->beginTransaction();
  try {
    $result = $work();
    $pdo->commit();
    return $result;
  } catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    throw $e;
  }
}