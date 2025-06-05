<?php
require_once 'db.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
header('Content-Type: application/json');

// ✅ 接收 JSON 格式資料
$input = json_decode(file_get_contents('php://input'), true);

// 取得書籍資訊
$title = trim($input['title'] ?? '');
$publisher = trim($input['publisher'] ?? '');
$category = trim($input['category'] ?? '');
$cover_url = trim($input['cover_url'] ?? '');
$description = trim($input['description'] ?? '');
$authors = $input['authors'] ?? [];

if (empty($title) || !is_array($authors) || count($authors) === 0) {
    echo json_encode(['success' => false, 'message' => '書名與作者皆為必填']);
    exit;
}

// 新增書籍
$stmt = $pdo->prepare("INSERT INTO book (title, publisher, category, cover_url, description) VALUES (?, ?, ?, ?, ?)");
$stmt->execute([$title, $publisher, $category, $cover_url, $description]);
$book_id = $pdo->lastInsertId();

// 處理作者資料與關聯
foreach ($authors as $authorName) {
    $authorName = trim($authorName);
    if ($authorName === '') continue;

    // 檢查作者是否已存在
    $stmt = $pdo->prepare("SELECT author_id FROM author WHERE name = ?");
    $stmt->execute([$authorName]);
    $author = $stmt->fetch();

    if ($author) {
        $author_id = $author['author_id'];
    } else {
        // 新增作者
        $stmt = $pdo->prepare("INSERT INTO author (name) VALUES (?)");
        $stmt->execute([$authorName]);
        $author_id = $pdo->lastInsertId();
    }

    // 建立書籍與作者的關聯
    $stmt = $pdo->prepare("INSERT INTO book_author (book_id, author_id) VALUES (?, ?)");
    $stmt->execute([$book_id, $author_id]);
}

echo json_encode(['success' => true, 'message' => '書籍與作者新增成功', 'book_id' => $book_id]);
