<?php
session_start();
require_once 'db.php';
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo json_encode(['success' => false]);
  exit;
}

$stmt = $pdo->prepare("
  SELECT ss.silent_share_id, ss.message, ss.open_time, b.title, u.name AS sender_name
  FROM silent_share ss
  JOIN receives r ON ss.silent_share_id = r.silent_share_id
  JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
  JOIN book b ON sb.book_id = b.book_id
  JOIN user u ON ss.sender_id = u.user_id
  WHERE r.user_id = ? AND ss.open_time <= NOW() AND r.unlocked = 0
");
$stmt->execute([$user_id]);
$shares = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 更新 unlocked 狀態 + 發送通知給 sender
foreach ($shares as $share) {
  $sid = $share['silent_share_id'];

  // 標記為已解鎖
  $pdo->prepare("UPDATE receives SET unlocked = 1 WHERE user_id = ? AND silent_share_id = ?")
      ->execute([$user_id, $sid]);

  // 建立通知訊息給 sender
  $sender_stmt = $pdo->prepare("
    INSERT INTO notifications (user_id, message, create_time)
    SELECT sender_id, CONCAT('📖 書籍「', b.title, '」已被接收人開啟'), NOW()
    FROM silent_share ss
    JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
    JOIN book b ON sb.book_id = b.book_id
    WHERE ss.silent_share_id = ?
  ");
  $sender_stmt->execute([$sid]);

  // 加入到接收者的書櫃（Shared Book 書櫃）
  $bookshelf_id_stmt = $pdo->prepare("
    SELECT shelf_id FROM book_shelf WHERE user_id = ? AND name = 'Shared Book'
  ");
  $bookshelf_id_stmt->execute([$user_id]);
  $shelf_id = $bookshelf_id_stmt->fetchColumn();

  if (!$shelf_id) {
    $pdo->prepare("INSERT INTO book_shelf (user_id, name) VALUES (?, 'Shared Book')")->execute([$user_id]);
    $shelf_id = $pdo->lastInsertId();
  }

  $pdo->prepare("
    INSERT IGNORE INTO bookshelf_record (shelf_id, book_id)
    SELECT ?, sb.book_id FROM share_book sb WHERE sb.silent_share_id = ?
  ")->execute([$shelf_id, $sid]);
}

echo json_encode(['success' => true, 'shares' => $shares]);
