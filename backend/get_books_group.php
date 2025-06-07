<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'db.php';
session_start();
header('Content-Type: application/json');

// 撈出書籍資料，包含作者、平均評分，並依書籍分類
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.cover_url, b.category,
           GROUP_CONCAT(a.name SEPARATOR ', ') AS authors,
           COALESCE(AVG(r.rating), 0) AS avg_rating
    FROM book b
    LEFT JOIN book_author ba ON b.book_id = ba.book_id
    LEFT JOIN author a ON ba.author_id = a.author_id
    LEFT JOIN review r ON b.book_id = r.book_id
    GROUP BY b.book_id
    ORDER BY b.book_id DESC
");

$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 將書籍依 category 分組
$grouped = [];
foreach ($books as $book) {
    $category = $book['category'] ?? '未分類';
    $grouped[$category][] = $book;
}

echo json_encode(['success' => true, 'books_by_category' => $grouped], JSON_UNESCAPED_UNICODE);
