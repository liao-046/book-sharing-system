<?php
session_start();
require_once(__DIR__ . '/../backend/db.php');

$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
  header("Location: login.php");
  exit;
}

// 撈出「我收到的分享」
$stmt = $pdo->prepare("
  SELECT b.title, b.book_id, ss.message, ss.unlock_time, r.unlocked, u.name AS sender_name
  FROM silent_share ss
  JOIN share_book sb ON ss.silent_share_id = sb.silent_share_id
  JOIN book b ON sb.book_id = b.book_id
  JOIN receives r ON r.silent_share_id = ss.silent_share_id
  JOIN user u ON ss.sender_id = u.user_id
  WHERE r.user_id = ?
  ORDER BY ss.unlock_time DESC
");
$stmt->execute([$user_id]);
$shares = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>我收到的書籍</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <h2>
    <img src="/book-sharing-system/assets/img/22512_color.png" alt="我收到的書籍" style="height: 1.4em; vertical-align: middle; margin-right: 6px;">
    我收到的書籍
  </h2>
  <hr>
  <?php if (empty($shares)): ?>
    <p class="text-muted">目前尚未收到任何書籍分享。</p>
  <?php else: ?>
    <ul class="list-group">
      <?php foreach ($shares as $s): ?>
        <li class="list-group-item">
          <strong>書名：</strong>
          <a href="book_detail.php?book_id=<?= $s['book_id'] ?>">
            <?= htmlspecialchars($s['title']) ?>
          </a><br>
          <strong>分享者：</strong> <?= htmlspecialchars($s['sender_name']) ?><br>
          <strong>留言：</strong> <?= nl2br(htmlspecialchars($s['message'])) ?><br>
          <strong>狀態：</strong>
          <?php if ($s['unlocked']): ?>
            ✅ 已解鎖
          <?php else: ?>
            ⏳ 尚未解鎖
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php endif; ?>
</div>
</body>
</html>
