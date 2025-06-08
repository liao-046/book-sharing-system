<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$shelf_id = $_POST['shelf_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;

if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '請先登入']);
  exit;
}

if (!$shelf_id || !$book_id) {
  echo json_encode(['success' => false, 'message' => '缺少 shelf_id 或 book_id']);
  exit;
}

// 檢查該書櫃是否屬於使用者
$stmt = $pdo->prepare("SELECT 1 FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);
if (!$stmt->fetch()) {
  echo json_encode(['success' => false, 'message' => '書櫃不屬於你']);
  exit;
}

// 檢查是否已經加入
$stmt = $pdo->prepare("SELECT COUNT(*) FROM bookshelf_record WHERE shelf_id = ? AND book_id = ?");
$stmt->execute([$shelf_id, $book_id]);
if ($stmt->fetchColumn() > 0) {
  echo json_encode(['success' => false, 'message' => '書籍已在書櫃中']);
  exit;
}

// 加入書櫃
$stmt = $pdo->prepare("INSERT INTO bookshelf_record (shelf_id, book_id, add_time) VALUES (?, ?, NOW())");
$stmt->execute([$shelf_id, $book_id]);

echo json_encode(['success' => true]);
