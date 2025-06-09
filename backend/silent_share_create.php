<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

// 1. 檢查是否已登入
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false, 'message' => '未登入']);
  exit;
}

// 2. 從 POST 取得資料
$book_id = $_POST['book_id'] ?? null;
$recipient_input = trim($_POST['recipient'] ?? '');
$message = trim($_POST['message'] ?? '');
$unlock_time = $_POST['unlock_time'] ?? null;

// 3. 確保資料完整
if (!$book_id || !$recipient_input || !$unlock_time) {
  echo json_encode(['success' => false, 'message' => '資料不完整']);
  exit;
}

// 4. 查找收件者是否存在（可用 email 或 username）
$stmt = $pdo->prepare("SELECT user_id FROM user WHERE email = ? OR name = ?");
$stmt->execute([$recipient_input, $recipient_input]);
$recipient = $stmt->fetch();

if (!$recipient) {
  echo json_encode(['success' => false, 'message' => '找不到該使用者']);
  exit;
}
$receiver_id = $recipient['user_id'];

// 5. 將分享訊息寫入 silent_share 資料表（含解鎖時間與發送者 ID）
$stmt = $pdo->prepare("
  INSERT INTO silent_share (message, create_time, unlock_condition, is_open, open_time, sender_id)
  VALUES (?, NOW(), '', 1, ?, ?)
");
$stmt->execute([$message, $unlock_time, $user_id]);
$silent_share_id = $pdo->lastInsertId(); // 取得剛剛插入的分享記錄 ID

// 6. 把書與該分享關聯（插入 share_book 表）
$stmt = $pdo->prepare("
  INSERT INTO share_book (silent_share_id, book_id)
  VALUES (?, ?)
");
$stmt->execute([$silent_share_id, $book_id]);

// 7. 指定接收者與分享關係（插入 receives 表）
$stmt = $pdo->prepare("
  INSERT INTO receives (user_id, silent_share_id)
  VALUES (?, ?)
");
$stmt->execute([$receiver_id, $silent_share_id]);

// 8. 回傳成功訊息給前端
echo json_encode(['success' => true, 'message' => '🎉 靜音分享成功！']);
