<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;

if (!$user_id || !$book_id) {
  echo json_encode(['success' => false, 'message' => '缺少必要欄位']);
  exit;
}

// 刪除對應評論
$stmt = $pdo->prepare("DELETE FROM review WHERE user_id = ? AND book_id = ?");
$stmt->execute([$user_id, $book_id]);

echo json_encode(['success' => true]);
