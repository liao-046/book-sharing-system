<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 取得登入者 ID
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 查詢使用者的書櫃
$stmt = $pdo->prepare("SELECT shelf_id, name FROM book_shelf WHERE user_id = ?");
$stmt->execute([$user_id]);
$shelves = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode(['success' => true, 'shelves' => $shelves]);
