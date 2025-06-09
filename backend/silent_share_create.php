<?php
session_start();
require_once 'db.php';

header('Content-Type: text/plain'); // ✅ Return plain text instead of JSON

// 1. Check login
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo "error: 未登入";
  exit;
}

// 2. Get POST data
$book_id = $_POST['book_id'] ?? null;
$recipient_input = trim($_POST['recipient'] ?? '');
$message = trim($_POST['message'] ?? '');
$unlock_time = $_POST['unlock_time'] ?? null;

// 3. Validate required fields
if (!$book_id || !$recipient_input || !$unlock_time) {
  echo "error: 資料不完整";
  exit;
}

// 4. Check recipient exists (username or email)
$stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? OR name = ?");
$stmt->execute([$recipient_input, $recipient_input]);
$recipient = $stmt->fetch();

if (!$recipient) {
  echo "error: 找不到該使用者";
  exit;
}
$receiver_id = $recipient['user_id'];

// 5. Insert into silent_share table
$stmt = $pdo->prepare("
  INSERT INTO silent_share (message, create_time, unlock_condition, is_open, open_time, sender_id)
  VALUES (?, NOW(), '', 1, ?, ?)
");
$stmt->execute([$message, $unlock_time, $user_id]);
$silent_share_id = $pdo->lastInsertId();

// 6. Insert book to share_book
$stmt = $pdo->prepare("
  INSERT INTO share_book (silent_share_id, book_id)
  VALUES (?, ?)
");
$stmt->execute([$silent_share_id, $book_id]);

// 7. Link receiver to receives table
$stmt = $pdo->prepare("
  INSERT INTO receives (user_id, silent_share_id)
  VALUES (?, ?)
");
$stmt->execute([$receiver_id, $silent_share_id]);

// 8. Success response
echo "success";
exit;
