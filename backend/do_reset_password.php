<?php
require_once 'db.php';
header('Content-Type: application/json');

$token = $_POST['token'] ?? '';
$new_password = trim($_POST['new_password'] ?? '');

// 找 token
$stmt = $pdo->prepare("SELECT * FROM password_reset_tokens WHERE token = ?");
$stmt->execute([$token]);
$tokenData = $stmt->fetch();

if (!$tokenData || strtotime($tokenData['expires_at']) < time()) {
    echo json_encode(['success' => false, 'message' => 'Token 無效或已過期'], JSON_UNESCAPED_UNICODE);
    exit;
}

$email = $tokenData['email'];
$hashed = password_hash($new_password, PASSWORD_DEFAULT);

// 更新密碼
$stmt = $pdo->prepare("UPDATE user SET password = ? WHERE email = ?");
$stmt->execute([$hashed, $email]);

// 刪除 token
$stmt = $pdo->prepare("DELETE FROM password_reset_tokens WHERE token = ?");
$stmt->execute([$token]);

echo json_encode(['success' => true, 'message' => '密碼已成功更新'], JSON_UNESCAPED_UNICODE);
