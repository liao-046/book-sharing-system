<?php
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// 驗證登入
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    echo json_encode(['success' => false, 'message' => '未登入']);
    exit;
}

// 驗證管理員身份
$stmt = $pdo->prepare("SELECT is_admin FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) {
    echo json_encode(['success' => false, 'message' => '非管理者無權限']);
    exit;
}

// 讀取輸入資料
$input = json_decode(file_get_contents('php://input'), true);

$book_id = intval($input['book_id'] ?? 0);
$title = trim($input['title'] ?? '');
$publisher = trim($input['publisher'] ?? '');
$category = trim($input['category'] ?? '');
$cover_url = trim($input['cover_url'] ?? '');
$description = trim($input['description'] ?? '');
$authors = $input['authors'] ?? [];

if ($book_id <= 0 || $title === '' || !is_array($authors) || count($authors) === 0) {
    echo json_encode(['success' => false, 'message' => '書籍 ID、書名與作者皆為必填']);
    exit;
}

// 更新書籍基本資訊
$stmt = $pdo->prepare("UPDATE book SET title = ?, publisher = ?, category = ?, cover_url = ?, description = ? WHERE book_id = ?");
$stmt->execute([$title, $publisher, $category, $cover_url, $description, $book_id]);

// 清除原有的作者關聯
$pdo->prepare("DELETE FROM book_author WHERE book_id = ?")->execute([$book_id]);

// 重新插入作者與關聯
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

    // 建立書籍與作者關聯
    $stmt = $pdo->prepare("INSERT INTO book_author (book_id, author_id) VALUES (?, ?)");
    $stmt->execute([$book_id, $author_id]);
}

echo json_encode(['success' => true, 'message' => '書籍已成功更新']);
