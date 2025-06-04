<?php
require_once 'db.php';
session_start();

header('Content-Type: application/json');

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

// 檢查輸入
if (empty($email) || empty($password)) {
    echo json_encode(['success' => false, 'message' => '欄位不得為空']);
    exit;
}

// 查詢使用者
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) {
    // 登入成功，建立 session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    echo json_encode(['success' => true, 'message' => '登入成功']);
} else {
    echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤']);
}
?>
