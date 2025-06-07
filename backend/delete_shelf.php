<?php
session_start();
require_once 'db.php';  // 你連資料庫的檔案，請確認路徑正確

header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$shelf_id = $_POST['shelf_id'] ?? null;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => '請先登入']);
    exit;
}

if (!$shelf_id) {
    echo json_encode(['success' => false, 'message' => '缺少 shelf_id']);
    exit;
}

try {
    // 確認書櫃屬於該使用者
    $stmt = $pdo->prepare("SELECT * FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
    $stmt->execute([$shelf_id, $user_id]);
    $shelf = $stmt->fetch();

    if (!$shelf) {
        echo json_encode(['success' => false, 'message' => '書櫃不存在或無權限']);
        exit;
    }

    // 刪除書櫃（連帶刪除 bookshelf_record 的紀錄）
    $stmt = $pdo->prepare("DELETE FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
    $stmt->execute([$shelf_id, $user_id]);

    echo json_encode(['success' => true, 'message' => '刪除成功']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => '刪除失敗：' . $e->getMessage()]);
}
