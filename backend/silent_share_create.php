<?php
session_start();
require_once 'db.php';
header('Content-Type: text/plain');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo "error: 請先登入再分享書籍";
  exit;
}

$book_id = $_POST['book_id'] ?? null;
$recipient_input = trim($_POST['recipient'] ?? '');
$message = trim($_POST['message'] ?? '');
$unlock_time = $_POST['unlock_time'] ?? null;

if (!$book_id || !$recipient_input || !$unlock_time) {
  echo "error: 所有欄位皆為必填";
  exit;
}

// 查找接收者（by email 或 name）
$stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? OR name = ?");
$stmt->execute([$recipient_input, $recipient_input]);
$recipient = $stmt->fetch();

if (!$recipient) {
  echo "error: 找不到此使用者";
  exit;
}
$receiver_id = $recipient['user_id'];

// 插入 silent_share
$stmt = $pdo->prepare("
  INSERT INTO silent_share (sender_id, message, unlock_condition, is_open, open_time)
  VALUES (?, ?, '', 1, ?)
");
$stmt->execute([$user_id, $message, $unlock_time]);
$sid = $pdo->lastInsertId();

// 插入 share_book
$stmt = $pdo->prepare("INSERT INTO share_book (silent_share_id, book_id) VALUES (?, ?)");
$stmt->execute([$sid, $book_id]);

// 插入 receives
$stmt = $pdo->prepare("INSERT INTO receives (user_id, silent_share_id) VALUES (?, ?)");
$stmt->execute([$receiver_id, $sid]);

echo "success";
