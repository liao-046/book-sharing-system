<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 驗證是否登入
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 驗證是否為管理者
$stmt = $pdo->prepare("SELECT is_admin FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) {
    echo json_encode(['success' => false, 'message' => '非管理者無權限']);
    exit;
}

// ✅ 接收 JSON 格式資料
$input = json_decode(file_get_contents('php://input'), true);
$book_id = intval($input['book_id'] ?? 0);

if ($book_id <= 0) {
    echo json_encode(['success' => false, 'message' => '缺少或錯誤的 book_id']);
    exit;
}

// 先刪除關聯的 book_author 資料
$stmt = $pdo->prepare("DELETE FROM book_author WHERE book_id = ?");
$stmt->execute([$book_id]);

// 再刪除書籍
$stmt = $pdo->prepare("DELETE FROM book WHERE book_id = ?");
$success = $stmt->execute([$book_id]);

if ($success) {
    echo json_encode(['success' => true, 'message' => '書籍刪除成功']);
} else {
    echo json_encode(['success' => false, 'message' => '書籍刪除失敗']);
}
