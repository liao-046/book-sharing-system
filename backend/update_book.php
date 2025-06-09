<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$user_id || !$book_id || !$rating) {
  echo json_encode(['success' => false, 'message' => '缺少必要資訊']);
  exit;
}

// 確認這筆評論屬於該使用者
$stmt = $pdo->prepare("SELECT review_id FROM review WHERE user_id = ? AND book_id = ?");
$stmt->execute([$user_id, $book_id]);
$review = $stmt->fetch();

if (!$review) {
  echo json_encode(['success' => false, 'message' => '找不到可修改的評論']);
  exit;
}

// 執行更新
$stmt = $pdo->prepare("UPDATE review SET rating = ?, comment = ? WHERE user_id = ? AND book_id = ?");
$success = $stmt->execute([$rating, $comment, $user_id, $book_id]);

echo json_encode(['success' => $success]);
