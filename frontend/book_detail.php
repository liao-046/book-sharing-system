<?php
session_start();
require_once '../backend/db.php';

$book_id = $_GET['book_id'] ?? null;

if (!$book_id) {
  echo "錯誤：未提供書籍 ID";
  exit;
}

// 撈取書籍詳細資訊
$stmt = $pdo->prepare("
  SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url,
         GROUP_CONCAT(a.name SEPARATOR ', ') AS authors
  FROM book b
  LEFT JOIN book_author ba ON b.book_id = ba.book_id
  LEFT JOIN author a ON ba.author_id = a.author_id
  WHERE b.book_id = ?
  GROUP BY b.book_id
");
$stmt->execute([$book_id]);
$book = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$book) {
  echo "找不到這本書";
  exit;
}

$user_name = $_SESSION['user_name'] ?? null;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($book['title']) ?> - 書籍詳情</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .book-cover {
      width: 100%;
      max-width: 300px;
      height: auto;
      border: 1px solid #ccc;
      object-fit: cover;
    }
  </style>
</head>
<body>
<div class="container py-4">
  <div class="mb-4 d-flex justify-content-between align-items-center">
    <h2>📖 書籍詳情</h2>
    <div>
      <a href="browse_books.php" class="btn btn-secondary btn-sm">← 返回瀏覽</a>
      <?php if ($user_name): ?>
        <span class="ms-2">👋 <?= htmlspecialchars($user_name) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="row">
    <div class="col-md-4 text-center">
      <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="封面" class="book-cover"
           onerror="this.src='/book-sharing-system/assets/img/default_cover.png'">
    </div>
    <div class="col-md-8">
      <h3><?= htmlspecialchars($book['title']) ?></h3>
      <p><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
      <p><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
      <p><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
      <!-- 你可以擴充更多資訊欄位，例如簡介、出版年等 -->
    </div>
  </div>
</div>
</body>
</html>
