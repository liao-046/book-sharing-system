<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$shelf_id = $_POST['shelf_id'] ?? null;
$book_id = $_POST['book_id'] ?? null;

if (!$user_id || !$shelf_id || !$book_id) {
    echo json_encode(['success' => false, 'message' => '缺少必要參數']);
    exit;
}

// 確認這個書櫃是屬於這位使用者
$stmt = $pdo->prepare("SELECT * FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);
if (!$stmt->fetch()) {
    echo json_encode(['success' => false, 'message' => '書櫃不存在或無權限']);
    exit;
}

// 刪除 bookshelf_record 中的紀錄
$stmt = $pdo->prepare("DELETE FROM bookshelf_record WHERE shelf_id = ? AND book_id = ?");
$success = $stmt->execute([$shelf_id, $book_id]);

if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => '刪除失敗']);
}
?>
