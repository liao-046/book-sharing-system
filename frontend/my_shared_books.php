<?php
session_start();
require_once(__DIR__ . '/../backend/db.php');


$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  header("Location: login.php");
  exit;
}

$stmt = $pdo->prepare("
  SELECT b.title, b.book_id, ss.message, u.name AS sender_name
  FROM receives r
  JOIN silent_share ss ON r.silent_share_id = ss.silent_share_id
  JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
  JOIN book b ON sb.book_id = b.book_id
  JOIN user u ON ss.sender_id = u.user_id
  WHERE r.user_id = ? AND r.unlocked = 1
  ORDER BY ss.open_time DESC
");
$stmt->execute([$user_id]);
$shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>我的書櫃 - 靜音分享</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h2>📂 我的書櫃 - 已解鎖靜音分享</h2>
  <hr>
  <?php if (empty($shares)): ?>
    <p class="text-muted">目前沒有已解鎖的靜音分享書籍。</p>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($shares as $s): ?>
        <li class="list-group-item">
          <strong>書名：</strong>
          <a href="book_detail.php?book_id=<?= $s['book_id'] ?>">
            <?= htmlspecialchars($s['title']) ?>
          </a><br>
          <strong>來自：</strong> <?= htmlspecialchars($s['sender_name']) ?><br>
          <strong>留言：</strong> <?= nl2br(htmlspecialchars($s['message'])) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
