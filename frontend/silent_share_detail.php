<?php
session_start();
require_once '../backend/db.php';  // 請確保路徑與你的 DB 連線檔一致

// 驗證用戶登入
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header('Location: login.php');
    exit;
}

// 取得分享 ID
$share_id = $_GET['share_id'] ?? 1;
if (!$share_id) {
    echo "參數錯誤：缺少分享ID";
    exit;
}

// 取出分享資料並確認分享接收者為當前用戶
$stmt = $pdo->prepare("
    SELECT ss.*, b.title, b.cover_url, b.description,
           u.user_name AS sender_name
    FROM silent_share ss
    JOIN book b ON ss.book_id = b.book_id
    JOIN user u ON ss.sender_id = u.user_id
    WHERE ss.share_id = ? AND ss.recipient_id = ?
");
$stmt->execute([$share_id, $user_id]);
$share = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$share) {
    echo "找不到分享內容或無權限檢視";
    exit;
}

// 判斷是否解鎖
$now = new DateTime();
$unlock = $share['unlock_time'] ? new DateTime($share['unlock_time']) : null;
$isUnlocked = !$unlock || $now >= $unlock;

?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8">
<title>靜音分享內容 - <?= htmlspecialchars($share['title']) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container py-4">
  <a href="silent_share_list.php" class="btn btn-secondary mb-3">← 返回分享列表</a>
  <h2><?= htmlspecialchars($share['title']) ?></h2>
  <p><strong>分享者：</strong> <?= htmlspecialchars($share['sender_name']) ?></p>
  <?php if ($share['cover_url']): ?>
    <img src="<?= htmlspecialchars($share['cover_url']) ?>" alt="封面" style="max-width:200px;">
  <?php endif; ?>
  <hr>
  <?php if ($isUnlocked): ?>
    <p><strong>分享訊息：</strong></p>
    <p><?= nl2br(htmlspecialchars($share['message'])) ?></p>
    <hr>
    <p><strong>書籍簡介：</strong></p>
    <p><?= nl2br(htmlspecialchars($share['description'] ?: '無簡介')) ?></p>
  <?php else: ?>
    <div class="alert alert-warning">
      這本書尚未解鎖，解鎖時間：<?= htmlspecialchars($share['unlock_time']) ?>
    </div>
  <?php endif; ?>
</div>
</body>
</html>
