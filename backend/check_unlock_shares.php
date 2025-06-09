<?php
session_start();
require_once 'db.php';
header('Content-Type: text/plain'); // 非 JSON

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  echo "error: 未登入";
  exit;
}

// 查找即將解鎖的分享書籍
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

if (!$shares) {
  echo "none";
  exit;
}

$outputs = []; // 組裝回傳訊息

foreach ($shares as $share) {
  $sid = $share['silent_share_id'];

  // 1. 標記為已解鎖
  $pdo->prepare("UPDATE receives SET unlocked = 1 WHERE user_id = ? AND silent_share_id = ?")
      ->execute([$user_id, $sid]);

  // 2. 建立通知給 sender
  $pdo->prepare("
    INSERT INTO notifications (user_id, message, create_time)
    SELECT ss.sender_id, CONCAT('📖 書籍「', b.title, '」已被接收人開啟'), NOW()
    FROM silent_share ss
    JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
    JOIN book b ON sb.book_id = b.book_id
    WHERE ss.silent_share_id = ?
  ")->execute([$sid]);

  // 3. 找接收者的 Shared Book 書櫃（若沒有就建立）
  $shelf_stmt = $pdo->prepare("
    SELECT shelf_id FROM book_shelf WHERE user_id = ? AND name = 'Shared Book'
  ");
  $shelf_stmt->execute([$user_id]);
  $shelf_id = $shelf_stmt->fetchColumn();

  if (!$shelf_id) {
    $pdo->prepare("INSERT INTO book_shelf (user_id, name) VALUES (?, 'Shared Book')")
        ->execute([$user_id]);
    $shelf_id = $pdo->lastInsertId();
  }

  // 4. 加入書籍到書櫃（忽略重複）
  $pdo->prepare("
    INSERT IGNORE INTO bookshelf_record (shelf_id, book_id)
    SELECT ?, sb.book_id FROM share_book sb WHERE sb.silent_share_id = ?
  ")->execute([$shelf_id, $sid]);

  // 5. 準備 alert 用訊息
  $outputs[] = "📩 來自「{$share['sender_name']}」分享的書籍《{$share['title']}》：{$share['message']}";
}

// 輸出所有 alert 文字，用分隔符號分開（可前端用 \n 分割）
echo implode("\n", $outputs);
