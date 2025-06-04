<?php
require_once 'db.php';

header('Content-Type: application/json');

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 檢查輸入
if (empty($name) || empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '欄位不得為空'], JSON_UNESCAPED_UNICODE);
    exit;
}

// ✅ Email 格式檢查
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email 格式不正確'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 檢查 email 是否已存在
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode(['success' => false, 'message' => '此 Email 已被註冊'],JSON_UNESCAPED_UNICODE);
    exit;
}

// 密碼加密（bcrypt）
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

// 寫入資料庫
$stmt = $pdo->prepare("INSERT INTO user (name, email, password) VALUES (?, ?, ?)");
$stmt->execute([$name, $email, $hashedPassword]);

echo json_encode(['success' => true, 'message' => '註冊成功'], JSON_UNESCAPED_UNICODE);
?>
