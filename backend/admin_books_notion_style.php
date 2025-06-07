<?php
require_once 'db.php';
session_start();

// 管理者驗證
$user_id = $_SESSION['user_id'] ?? null;
if (!$user_id) {
    header("Location: login.html");
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

// 取得書籍資料（含作者）
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url, b.description,
           GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
    FROM book b
    LEFT JOIN book_author ba ON b.book_id = ba.book_id
    LEFT JOIN author a ON ba.author_id = a.author_id
    GROUP BY b.book_id
    ORDER BY b.book_id DESC
");
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>書籍管理表格模式</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background: #f4f4f4; } /* #f4f4f4 */
    .offcanvas-header, .offcanvas-body { background: #fff; }
    .cover-img { width: 100%; height: auto; max-height: 250px; object-fit: cover; border-bottom: 1px solid #ccc; }
    .badge-tag { margin-right: 6px; }
  </style>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>📘 書籍表格管理</h2>
    <div>
      歡迎，<?= $user_name ?>　
      <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-secondary btn-sm">登出</a>
    </div>
  </div>

  <table class="table table-bordered bg-white">
    <thead class="table-light">
      <tr>
        <th>#</th>
        <th>書名</th>
        <th>作者</th>
        <th>詳細資訊</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($books as $i => $book): ?>
        <tr>
          <td><?= $i + 1 ?></td>
          <td><?= htmlspecialchars($book['title']) ?></td>
          <td><?= htmlspecialchars($book['authors']) ?></td>
          <td><button class="btn btn-sm btn-info" onclick="showDetail(<?= $book['book_id'] ?>)">查看</button></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
</div>

<!-- 詳情側欄 -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="detailCanvas">
  <div class="offcanvas-header">
    <h5 id="canvasTitle"></h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <img id="canvasCover" src="" class="cover-img mb-3">
    <p><strong>作者：</strong><span id="canvasAuthors"></span></p>
    <p><strong>出版社：</strong> <span class="badge bg-secondary" id="canvasPublisher"></span></p>
    <p><strong>分類：</strong> <span class="badge bg-info text-dark" id="canvasCategory"></span></p>
    <p><strong>簡介：</strong><br><span id="canvasDescription"></span></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const books = <?= json_encode($books, JSON_UNESCAPED_UNICODE) ?>;

function showDetail(bookId) {
  const book = books.find(b => b.book_id == bookId);
  if (!book) return;

  document.getElementById("canvasTitle").textContent = book.title;
  document.getElementById("canvasCover").src = book.cover_url || '';
  document.getElementById("canvasAuthors").textContent = book.authors;
  document.getElementById("canvasPublisher").textContent = book.publisher;
  document.getElementById("canvasCategory").textContent = book.category;
  document.getElementById("canvasDescription").textContent = book.description;

  const offcanvas = new bootstrap.Offcanvas('#detailCanvas');
  offcanvas.show();
}
</script>
</body>
</html>
