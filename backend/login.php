<?php
session_start();
require_once 'db.php';

// 設定回傳為 JSON
header('Content-Type: application/json');

// 擷取 POST 資料
$email = trim($_POST['email'] ?? '');
$password = trim($_POST['password'] ?? '');

// 檢查空值
if ($email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Email 或密碼不得為空']);
    exit;
}

try {
    // 查詢帳號是否存在
    $stmt = $pdo->prepare("SELECT * FROM user WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // 檢查密碼是否正確
    if ($user && password_verify($password, $user['password'])) {
        // 記錄登入資訊
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['name'];
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => '帳號或密碼錯誤']);
    }
} catch (PDOException $e) {
    // 資料庫錯誤處理
    echo json_encode(['success' => false, 'message' => '伺服器錯誤，請稍後再試']);
}
?>
