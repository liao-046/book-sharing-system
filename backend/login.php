<?php
require_once 'db.php'; // 注意路徑要正確
session_start();

header('Content-Type: application/json');

$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 檢查欄位是否為空
if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email 或密碼不得為空'], JSON_UNESCAPED_UNICODE);
    exit;
}

// 查詢該 Email 是否存在
$stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user && password_verify($password, $user['password'])) { // 注意：這裡應該使用密碼雜湊驗證) {
    // 登入成功，儲存 session
    $_SESSION['user_id'] = $user['user_id'];
    $_SESSION['user_name'] = $user['name'];
    echo json_encode(['success' => true, 'message' => '登入成功'], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤'], JSON_UNESCAPED_UNICODE);
}
?>
