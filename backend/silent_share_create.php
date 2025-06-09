<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '未登入']);
  exit;
}

$book_id = $_POST['book_id'] ?? null;
$recipient_input = trim($_POST['recipient'] ?? '');
$message = trim($_POST['message'] ?? '');
$unlock_time = $_POST['unlock_time'] ?? null;

if (!$book_id || !$recipient_input || !$unlock_time) {
  echo json_encode(['success' => false, 'message' => '資料不完整']);
  exit;
}

// 檢查收件者是否存在（以 email 或帳號搜尋）
$stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? OR name = ?");
$stmt->execute([$recipient_input, $recipient_input]);
$recipient = $stmt->fetch();

if (!$recipient) {
  echo json_encode(['success' => false, 'message' => '找不到該使用者']);
  exit;
}

$receiver_id = $recipient['user_id'];

// 插入 silent_share 資料，新增 sender_id 欄位
$stmt = $pdo->prepare("
  INSERT INTO silent_share (message, create_time, unlock_condition, is_open, open_time, sender_id)
  VALUES (?, NOW(), '', 1, ?, ?)
");
$stmt->execute([$message, $unlock_time, $user_id]);
$silent_share_id = $pdo->lastInsertId();

// 插入 share_book 資料
$stmt = $pdo->prepare("
  INSERT INTO share_book (silent_share_id, book_id)
  VALUES (?, ?)
");
$stmt->execute([$silent_share_id, $book_id]);

// 插入 receives 資料
$stmt = $pdo->prepare("
  INSERT INTO receives (user_id, silent_share_id)
  VALUES (?, ?)
");
$stmt->execute([$receiver_id, $silent_share_id]);

echo json_encode(['success' => true, 'message' => '🎉 靜音分享成功！']);
