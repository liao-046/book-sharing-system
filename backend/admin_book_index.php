<?php
require_once 'db.php';
session_start();

// 管理者驗證
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT is_admin FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) {
    echo "無權限存取";
    exit;
}

// 取得書籍與作者資料
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.publisher, b.category,
           GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
    FROM book b
    LEFT JOIN book_author ba ON b.book_id = ba.book_id
    LEFT JOIN author a ON ba.author_id = a.author_id
    GROUP BY b.book_id
    ORDER BY b.book_id DESC
");
$books = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="zh-Hant">

<head>
    <meta charset="UTF-8">
    <title>書籍管理後台</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">
    <?php $total_books = count($books); ?>
    <div class="container mt-3 d-flex justify-content-between">
        <h2>📚 書籍管理後台</h2>
        <div>
            <span>歡迎，<?= htmlspecialchars($_SESSION['user_name']) ?>　</span>
            <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-danger btn-sm">登出</a>
        </div>
    </div>
    <hr>
    <p>目前共有 <strong><?= $total_books ?></strong> 本書籍。</p>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>id</th>
                <th>書名</th>
                <th>出版社</th>
                <th>分類</th>
                <th>作者</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php $index = 1; ?>
            <?php foreach ($books as $book): ?>
                <tr id="book-row-<?= $book['book_id'] ?>">
                    <td><?= $index++ ?></td>
                    <td><?= htmlspecialchars($book['book_id']) ?></td>
                    <td class="title"><?= htmlspecialchars($book['title']) ?></td>
                    <td class="publisher"><?= htmlspecialchars($book['publisher']) ?></td>
                    <td class="category"><?= htmlspecialchars($book['category']) ?></td>
                    <td class="authors"><?= htmlspecialchars($book['authors']) ?></td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editRow(<?= $book['book_id'] ?>)">編輯</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBook(<?= $book['book_id'] ?>)">刪除</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    </div>


    <script>
        function editRow(book_id) {
            const row = document.getElementById(`book-row-${book_id}`);
            const title = row.querySelector('.title');
            const publisher = row.querySelector('.publisher');
            const category = row.querySelector('.category');
            const authors = row.querySelector('.authors');

            title.innerHTML = `<input class="form-control form-control-sm" id="title-${book_id}" value="${title.innerText}">`;
            publisher.innerHTML = `<input class="form-control form-control-sm" id="publisher-${book_id}" value="${publisher.innerText}">`;
            category.innerHTML = `<input class="form-control form-control-sm" id="category-${book_id}" value="${category.innerText}">`;
            authors.innerHTML = `<input class="form-control form-control-sm" id="authors-${book_id}" value="${authors.innerText}">`;

            // 換成儲存按鈕
            row.querySelector('button.btn-primary').outerHTML = `
        <button class="btn btn-sm btn-success" onclick="saveRow(${book_id})">儲存</button>
    `;
        }

        function saveRow(book_id) {
            const title = document.getElementById(`title-${book_id}`).value;
            const publisher = document.getElementById(`publisher-${book_id}`).value;
            const category = document.getElementById(`category-${book_id}`).value;
            const authorsRaw = document.getElementById(`authors-${book_id}`).value;

            const authors = authorsRaw.split(',').map(s => s.trim()).filter(Boolean);

            fetch('edit_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id,
                        title,
                        publisher,
                        category,
                        cover_url: '', // 若未使用可留空
                        description: '', // 若未使用可留空
                        authors
                    })
                })
                .then(res => res.json())
                .then(data => {
                    alert(data.message);
                    if (data.success) location.reload();
                });
        }

        function deleteBook(book_id) {
            if (!confirm("確定要刪除這本書嗎？")) return;

            fetch('delete_book.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        book_id
                    })
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        document.getElementById('book-row-' + book_id).remove();
                    } else {
                        alert(data.message || '刪除失敗');
                    }
                })
                .catch(err => alert('錯誤：' + err));
        }
    </script>
</body>

</html>