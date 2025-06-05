<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');
$name = trim($_POST['name'] ?? '');
$user_id = $_SESSION['user_id'] ?? null;


// 檢查使用者是否登入
if(!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}
// 檢查書櫃名稱是否為空
if (empty($name)) {

    echo json_encode(['success' => false, 'message' => '書櫃名稱為空']);
    exit;
}

// 插入新書櫃
$stmt = $pdo->prepare("INSERT INTO book_shelf (name, user_id) VALUES (?, ?)");
$stmt->execute([$name, $user_id]);

echo json_encode(['success' => true, 'message' => '書櫃建立成功']);
