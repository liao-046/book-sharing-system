<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;
$shelf_id = $_POST['shelf_id'] ?? null;

if (!$user_id || !$book_id || !$shelf_id) {
  echo json_encode(['success' => false, 'message' => '參數不完整或未登入']);
  exit;
}

// 檢查這個書櫃是否屬於該使用者
$stmt = $pdo->prepare("SELECT * FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);
if ($stmt->rowCount() === 0) {
  echo json_encode(['success' => false, 'message' => '無效的書櫃或權限不足']);
  exit;
}

// 檢查是否已經加入過該書櫃
$stmt = $pdo->prepare("SELECT 1 FROM bookshelf_record WHERE shelf_id = ? AND book_id = ?");
$stmt->execute([$shelf_id, $book_id]);
if ($stmt->fetch()) {
  echo json_encode(['success' => false, 'message' => '這本書已經在這個書櫃中了']);
  exit;
}

// 新增紀錄
$stmt = $pdo->prepare("INSERT INTO bookshelf_record (shelf_id, book_id) VALUES (?, ?)");
if ($stmt->execute([$shelf_id, $book_id])) {
  echo json_encode(['success' => true]);
} else {
  echo json_encode(['success' => false, 'message' => '加入失敗，請稍後再試']);
}
