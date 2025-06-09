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

// 檢查是否已評論過
$stmt = $pdo->prepare("SELECT COUNT(*) FROM review WHERE user_id = ? AND book_id = ?");
$stmt->execute([$user_id, $book_id]);
if ($stmt->fetchColumn() > 0) {
  echo json_encode(['success' => false, 'message' => '你已對此書籍評論過']);
  exit;
}

// 插入
$stmt = $pdo->prepare("INSERT INTO review (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
$stmt->execute([$user_id, $book_id, $rating, $comment]);
echo json_encode(['success' => true]);
