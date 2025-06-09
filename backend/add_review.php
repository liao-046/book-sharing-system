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

// 檢查是否已經對該書評論過
$stmt = $pdo->prepare("SELECT review_id FROM review WHERE user_id = ? AND book_id = ?");
$stmt->execute([$user_id, $book_id]);
if ($stmt->fetch()) {
  echo json_encode(['success' => false, 'message' => '你已經評論過這本書']);
  exit;
}

// 新增評論
$stmt = $pdo->prepare("INSERT INTO review (user_id, book_id, rating, comment) VALUES (?, ?, ?, ?)");
$success = $stmt->execute([$user_id, $book_id, $rating, $comment]);

echo json_encode(['success' => $success]);
