<?php
require_once 'db.php';

date_default_timezone_set('Asia/Taipei');

// 1. 查找符合條件的靜音分享（尚未 unlocked 且 open_time 已到）
$stmt = $pdo->prepare("
  SELECT ss.silent_share_id, sb.book_id, ss.sender_id, r.user_id AS receiver_id, b.title
  FROM silent_share ss
  JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
  JOIN book b ON sb.book_id = b.book_id
  JOIN receives r ON r.silent_share_id = ss.silent_share_id
  WHERE ss.open_time <= NOW() AND r.unlocked = 0
");
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($rows as $row) {
  $sid = $row['silent_share_id'];
  $sender = $row['sender_id'];
  $receiver = $row['receiver_id'];
  $book_id = $row['book_id'];
  $title = $row['title'];

  // 1. 標記為已解鎖
  $pdo->prepare("UPDATE receives SET unlocked = 1 WHERE user_id = ? AND silent_share_id = ?")
      ->execute([$receiver, $sid]);

  // 2. 建立通知給分享者
  $msg = "📖 書籍《{$title}》已被接收人解鎖！";
  $pdo->prepare("INSERT INTO notifications (user_id, message, create_time) VALUES (?, ?, NOW())")
      ->execute([$sender, $msg]);

  // 3. 若接收者沒有 Shared Book 書櫃，則建立
  $shelf_id = $pdo->prepare("SELECT shelf_id FROM book_shelf WHERE user_id = ? AND name = 'Shared Book'");
  $shelf_id->execute([$receiver]);
  $sid_result = $shelf_id->fetchColumn();

  if (!$sid_result) {
    $pdo->prepare("INSERT INTO book_shelf (user_id, name) VALUES (?, 'Shared Book')")->execute([$receiver]);
    $sid_result = $pdo->lastInsertId();
  }

  // 4. 書加入書櫃（忽略重複）
  $pdo->prepare("
    INSERT IGNORE INTO bookshelf_record (shelf_id, book_id)
    VALUES (?, ?)
  ")->execute([$sid_result, $book_id]);
}

echo "[OK] cron unlock completed at " . date('Y-m-d H:i:s');
