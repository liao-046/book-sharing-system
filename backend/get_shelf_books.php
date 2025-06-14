<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

$user_id = $_SESSION['user_id'] ?? null;
$shelf_id = $_GET['shelf_id'] ?? null;

error_log("🧾 get_shelf_books called by user_id = $user_id, shelf_id = $shelf_id");

if (!$user_id || !$shelf_id) {
    echo json_encode(['success' => false, 'message' => '未登入或缺少參數']);
    exit;
}

// ✅ 讀取書櫃資料，包括 background_url
$stmt = $pdo->prepare("SELECT name, icon, background_url FROM book_shelf WHERE shelf_id = ? AND user_id = ?");
$stmt->execute([$shelf_id, $user_id]);
$shelf = $stmt->fetch();

if (!$shelf) {
    echo json_encode(['success' => false, 'message' => '書櫃不存在或無權限']);
    exit;
}

// ✅ 查詢書櫃中的書籍清單
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

// ✅ 回傳包含背景網址
echo json_encode([
    'success' => true,
    'shelf_name' => $shelf['name'],
    'icon' => $shelf['icon'] ?? '📁',
    'background_url' => $shelf['background_url'] ?? null,
    'books' => $books
]);
