<?php
session_start();
require_once '../backend/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

// 標記所有通知為已讀
$pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0")
    ->execute([$user_id]);

// 取得所有通知（含時間）
$stmt = $pdo->prepare("
    SELECT message, create_time 
    FROM notifications 
    WHERE user_id = ? 
    ORDER BY notification_id DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>🔔 通知中心</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h3 class="mb-4">🔔 通知中心</h3>
  <a href="index.php" class="btn btn-secondary btn-sm mb-3">← 返回主頁</a>

  <?php if (empty($notifications)): ?>
    <div class="alert alert-info">目前沒有任何通知。</div>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($notifications as $note): ?>
        <li class="list-group-item d-flex justify-content-between align-items-center">
          <div><?= htmlspecialchars($note['message']) ?></div>
          <small class="text-muted"><?= htmlspecialchars($note['create_time']) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
