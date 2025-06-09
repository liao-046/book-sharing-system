<?php
session_start();
require_once 'db.php';

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false]);
  exit;
}

// 取得所有已解鎖的靜音分享
$stmt = $pdo->prepare("
  SELECT ss.silent_share_id, ss.message, ss.open_time, b.title, u.name AS sender_name
  FROM receives r
  JOIN silent_share ss ON r.silent_share_id = ss.silent_share_id
  JOIN share_book sb ON sb.silent_share_id = ss.silent_share_id
  JOIN book b ON b.book_id = sb.book_id
  JOIN user u ON sb.sender_id = u.user_id
  WHERE r.user_id = ? 
    AND ss.open_time <= NOW()
    AND r.shown IS NULL
");
$stmt->execute([$user_id]);
$shares = $stmt->fetchAll();

foreach ($shares as $s) {
  // 加入書櫃（Shared Book）
  $pdo->prepare("
    INSERT IGNORE INTO bookshelf_record (user_id, book_id, shelf_name)
    SELECT ?, sb.book_id, 'Shared Book'
    FROM share_book sb
    WHERE sb.silent_share_id = ?
  ")->execute([$user_id, $s['silent_share_id']]);

  // 標記為已顯示（避免重複提醒）
  $pdo->prepare("UPDATE receives SET shown = 1 WHERE user_id = ? AND silent_share_id = ?")
      ->execute([$user_id, $s['silent_share_id']]);

  // 傳送通知給 sender
  $pdo->prepare("
    INSERT INTO notifications (sender_id, receiver_id, book_title, message, notify_time)
    VALUES (
      (SELECT sender_id FROM share_book WHERE silent_share_id = ?),
      ?, ?, ?, NOW()
    )
  ")->execute([$s['silent_share_id'], $user_id, $s['title'], $s['message']]);
}

echo json_encode(['success' => true, 'shares' => $shares]);
