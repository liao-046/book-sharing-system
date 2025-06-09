<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// 標記點開的通知為已讀（如果有帶notification_id）
if (isset($_GET['mark_read'])) {
    $notif_id = (int)$_GET['mark_read'];
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->execute([$notif_id, $user_id]);
    header('Location: notifications.php');
    exit;
}

// 取得該使用者所有通知（最新在前）
$stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
<meta charset="UTF-8" />
<title>通知列表</title>
<style>
    body { font-family: Arial, sans-serif; padding: 20px; }
    .notif { border-bottom: 1px solid #ddd; padding: 10px 0; }
    .notif.unread { background-color: #f0f8ff; font-weight: bold; }
    .notif a { text-decoration: none; color: #333; }
    .notif .time { font-size: 0.8em; color: #666; }
</style>
</head>
<body>
<h1>通知列表</h1>

<?php if (empty($notifications)): ?>
    <p>目前沒有通知。</p>
<?php else: ?>
    <?php foreach ($notifications as $n): ?>
        <div class="notif <?= $n['is_read'] ? '' : 'unread' ?>">
            <a href="notifications.php?mark_read=<?= $n['notification_id'] ?>">
                <?= htmlspecialchars($n['title']) ?>
            </a>
            <p><?= nl2br(htmlspecialchars($n['content'])) ?></p>
            <div class="time"><?= $n['created_at'] ?></div>
        </div>
    <?php endforeach; ?>
<?php endif; ?>

</body>
</html>
