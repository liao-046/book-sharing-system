<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// 取得登入者 ID 和 shelf_id
$user_id = $_SESSION['user_id'] ?? null;
$shelf_id = $_GET['shelf_id'] ?? null;

// 驗證輸入
if (!$user_id || !$shelf_id) {
    echo json_encode(['success' => false, 'message' => '未登入或缺少參數']);
    exit;
}

// 確認書櫃屬於使用者並取得名稱
$stmt = $pdo->prepare("SELECT name FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);
$shelf = $stmt->fetch();

if (!$shelf) {
    echo json_encode(['success' => false, 'message' => '書櫃不存在或無權限']);
    exit;
}

// 查詢書櫃中的書籍與作者
$stmt = $pdo->prepare("
    SELECT 
        b.book_id,
        b.title,
        GROUP_CONCAT(a.name SEPARATOR ', ') AS authors,
        b.publisher,
        b.cover_url
    FROM bookshelf_record br
    JOIN book b ON br.book_id = b.book_id
    LEFT JOIN book_author ba ON b.book_id = ba.book_id
    LEFT JOIN author a ON ba.author_id = a.author_id
    WHERE br.shelf_id = ?
    GROUP BY b.book_id, b.title, b.publisher, b.cover_url
");
$stmt->execute([$shelf_id]);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 回傳 JSON
echo json_encode([
    'success' => true,
    'shelf_name' => $shelf['name'],
    'books' => $books
]);
