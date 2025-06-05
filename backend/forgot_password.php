<?php
require_once 'db.php';
header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');

// 檢查 email 是否存在
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if (!$user) {
    echo json_encode(['success' => false, 'message' => '該 Email 不存在'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 產生唯一 token
$token = bin2hex(random_bytes(16));
$expires = date('Y-m-d H:i:s', time() + 3600); // 1 小時內有效

// 儲存 token
$stmt = $pdo->prepare("INSERT INTO password_reset_tokens (email, token, expires_at) VALUES (?, ?, ?)");
$stmt->execute([$email, $token, $expires]);

// 寄信（正式請改用 SMTP 寄出）
$resetLink = "http://localhost/book-sharing-system/frontend/reset_password.php?token=$token";
$subject = "重設密碼連結";
$message = "請點選以下連結重設密碼：\n$resetLink";
//暫時不用
//mail($email, $subject, $message);

echo json_encode([
    'success' => true,
    'message' => '重設密碼連結產生成功',
    'link' => $resetLink, // 這裡印出 token 連結
    'expires_at' => $expires // 新增 token 到期時間
], JSON_UNESCAPED_UNICODE);
