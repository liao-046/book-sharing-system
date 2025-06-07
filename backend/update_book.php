<?php
require_once 'db.php';

$book_id = $_POST['book_id'] ?? null;
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

$allowed = ['title', 'publisher', 'category', 'description', 'authors', 'cover_url'];
if (!in_array($field, $allowed) || !$book_id) {
    echo "非法操作"; exit;
}

if ($field === 'authors') {
    // 多對多處理
    $authorNames = array_filter(array_map('trim', explode(',', $value)));
    $authorIds = [];
    foreach ($authorNames as $name) {
        $stmt = $pdo->prepare("SELECT author_id FROM author WHERE name = ?");
        $stmt->execute([$name]);
        $author = $stmt->fetch();
        if ($author) {
            $authorIds[] = $author['author_id'];
        } else {
            $stmt = $pdo->prepare("INSERT INTO author (name) VALUES (?)");
            $stmt->execute([$name]);
            $authorIds[] = $pdo->lastInsertId();
        }
    }

    $stmt = $pdo->prepare("DELETE FROM book_author WHERE book_id = ?");
    $stmt->execute([$book_id]);

    foreach ($authorIds as $aid) {
        $stmt = $pdo->prepare("INSERT INTO book_author (book_id, author_id) VALUES (?, ?)");
        $stmt->execute([$book_id, $aid]);
    }

    echo "OK";
} else {
    $stmt = $pdo->prepare("UPDATE book SET $field = ? WHERE book_id = ?");
    echo $stmt->execute([$value, $book_id]) ? "OK" : "更新失敗";
}
