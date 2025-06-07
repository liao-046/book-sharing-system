<?php
require_once 'db.php';
session_start();

// 管理者驗證
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.php");
    exit;
}
$stmt = $pdo->prepare("SELECT is_admin, name FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();
if (!$user || !$user['is_admin']) {
    echo "無權限存取";
    exit;
}
$user_name = htmlspecialchars($user['name']);

// 取得書籍與作者資料
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url, b.description,
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
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h2>📚 書籍管理後台</h2>
        <div>
            <span>歡迎，<?= $user_name ?>　</span>
            <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-danger btn-sm">登出</a>
        </div>
    </div>

    <p>目前共有 <strong><?= count($books) ?></strong> 本書籍。</p>
    <table class="table table-bordered table-hover bg-white">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>ID</th>
                <th>書名</th>
                <th>出版社</th>
                <th>分類</th>
                <th>作者</th>
                <th colspan="2">詳情</th>
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
                    <td colspan="2">
                        <button class="btn btn-sm btn-info" data-bs-toggle="collapse" data-bs-target="#detail-<?= $book['book_id'] ?>">
                            展開詳情
                        </button>
                        <div class="collapse mt-2" id="detail-<?= $book['book_id'] ?>">
                            <div class="card card-body bg-light">
                                <p><strong>封面：</strong><br>
                                    <a href="<?= htmlspecialchars($book['cover_url']) ?>" target="_blank">
                                        <img src="<?= htmlspecialchars($book['cover_url']) ?>" style="max-height:200px;">
                                    </a>
                                </p>
                                <p><strong>簡介：</strong><br><?= nl2br(htmlspecialchars($book['description'])) ?></p>
                            </div>
                        </div>
                    </td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editRow(<?= $book['book_id'] ?>)">編輯</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteBook(<?= $book['book_id'] ?>)">刪除</button>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            book_id,
            title,
            publisher,
            category,
            cover_url: '',        // 若要編輯封面與簡介，這裡可擴充
            description: '',
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
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ book_id })
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
