<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// 驗證登入
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '請先登入']);
  exit;
}

// 接收輸入
$book_id = $_POST['book_id'] ?? null;
$rating = $_POST['rating'] ?? null;
$comment = trim($_POST['comment'] ?? '');

if (!$book_id || !$rating || $rating < 1 || $rating > 5) {
  echo json_encode(['success' => false, 'message' => '請提供有效的評分 (1~5 分) 與書籍 ID']);
  exit;
}

// 插入資料
$stmt = $pdo->prepare("INSERT INTO review (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$user_id, $book_id, $rating, $comment]);

if ($success) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => '評論新增失敗']);
}
