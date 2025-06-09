<?php
session_start();
require_once '../backend/db.php';

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}

$stmt = $pdo->prepare("
    SELECT message FROM notifications
    WHERE user_id = ?
    ORDER BY notification_id DESC
");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<script src="/book-sharing-system/assets/js/silent_share_alert.js"></script>
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
        <li class="list-group-item d-flex justify-content-between">
          <span><?= htmlspecialchars($note['message']) ?></span>
          <small class="text-muted"><?= htmlspecialchars($note['create_time']) ?></small>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
