<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$user_id || !$book_id || !$rating) {
  echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
  exit;
}

$stmt = $pdo->prepare("UPDATE review SET rating = ?, comment = ?, create_time = CURRENT_TIMESTAMP WHERE user_id = ? AND book_id = ?");
$stmt->execute([$rating, $comment, $user_id, $book_id]);
echo json_encode(['success' => true]);
