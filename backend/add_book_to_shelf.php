<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 取得登入者 ID
$user_id = $_SESSION['user_id'] ?? null;

// 接收輸入
$shelf_id = $_POST['shelf_id'] ?? '';
$book_id = $_POST['book_id'] ?? '';

// 檢查登入與資料
if (!$user_id || empty($shelf_id) || empty($book_id)) {
    echo json_encode(['success' => false, 'message' => '未登入或資料不完整']);
    exit;
}
//確認書是否存在

$stmt = $pdo->prepare("SELECT * FROM book WHERE book_id = ?");
$stmt->execute([$book_id]);
if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => '書籍不存在']);
    exit;
}

// 確認書櫃是這位使用者的
$stmt = $pdo->prepare("SELECT * FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => '書櫃不存在或不屬於此使用者']);
    exit;
}

// 嘗試插入（避免重複加入）
try {
    $stmt = $pdo->prepare("INSERT INTO bookshelf_record (shelf_id, book_id) VALUES (?, ?)");
    $stmt->execute([$shelf_id, $book_id]);

    echo json_encode(['success' => true, 'message' => '書籍成功加入書櫃']);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') { // 主鍵重複
        echo json_encode(['success' => false, 'message' => '書籍已經在書櫃中']);
    } else {
        echo json_encode(['success' => false, 'message' => '發生錯誤: ' . $e->getMessage()]);
    }
}
