<?php
require_once 'db.php';

$book_id = $_POST['book_id'] ?? null;
$field = $_POST['field'] ?? '';
$value = trim($_POST['value'] ?? '');

$allowed = ['title', 'publisher', 'category', 'description', 'authors', 'cover_url'];
if (!in_array($field, $allowed) || !$book_id) {
    echo "非法操作"; exit;
}

try {
    if ($field === 'authors') {
        // 多對多關聯處理
        $authorNames = array_filter(array_map('trim', explode(',', $value)));
        $authorIds = [];

        foreach ($authorNames as $name) {
            // 檢查是否已有此作者
            $stmt = $pdo->prepare("SELECT author_id FROM author WHERE name = ?");
            $stmt->execute([$name]);
            $author = $stmt->fetch();

            if ($author) {
                $authorIds[] = $author['author_id'];
            } else {
                // 若無，嘗試插入（避免 UNIQUE constraint 錯誤）
                $stmt = $pdo->prepare("INSERT IGNORE INTO author (name) VALUES (?)");
                $stmt->execute([$name]);

                // 再查一次拿 ID（因為 INSERT IGNORE 不一定插入）
                $stmt = $pdo->prepare("SELECT author_id FROM author WHERE name = ?");
                $stmt->execute([$name]);
                $newAuthor = $stmt->fetch();
                if ($newAuthor) {
                    $authorIds[] = $newAuthor['author_id'];
                }
            }
        }

        // 清除原關聯
        $stmt = $pdo->prepare("DELETE FROM book_author WHERE book_id = ?");
        $stmt->execute([$book_id]);

        // 新增關聯
        foreach ($authorIds as $aid) {
            $stmt = $pdo->prepare("INSERT INTO book_author (book_id, author_id) VALUES (?, ?)");
            $stmt->execute([$book_id, $aid]);
        }

        echo "OK";

    } else {
        // 其他欄位更新
        $stmt = $pdo->prepare("UPDATE book SET `$field` = ? WHERE book_id = ?");
        $stmt->execute([$value, $book_id]);
        echo "OK";
    }
} catch (PDOException $e) {
    echo "錯誤：" . $e->getMessage();
}
