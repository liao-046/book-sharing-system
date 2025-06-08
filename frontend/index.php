<?php
session_start();
require_once '../backend/db.php';

// 取得目前登入者資訊
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// 撈取書籍與作者
$stmt = $pdo->query("
    SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url,
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
  <title>書籍瀏覽</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body { background-color: #f8f9fa; }
    .book-card {
      width: 200px;
      margin-bottom: 20px;
      transition: transform 0.2s;
    }
    .book-card:hover {
      transform: scale(1.03);
    }
    .book-cover {
      width: 100%;
      height: 280px;
      object-fit: cover;
      border-bottom: 1px solid #ddd;
    }
  </style>
</head>
<body>
  <div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
      <h2>
        <img src="/book-sharing-system/assets/img/12260_color.png" alt="icon" style="height: 40px; vertical-align: middle;">
        書籍瀏覽
      </h2>
      <div>
        <?php if ($user_name): ?>
          👋 歡迎，<?= htmlspecialchars($user_name) ?>
          <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-secondary btn-sm ms-2">登出</a>
        <?php else: ?>
          <a href="/book-sharing-system/frontend/login.html" class="btn btn-primary">登入</a>
        <?php endif; ?>
      </div>
    </div>

    <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
      <?php foreach ($books as $book): ?>
        <div class="col">
          <div class="card book-card shadow-sm">
            <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="封面" class="book-cover">
            <div class="card-body">
              <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
              <p class="card-text mb-1"><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
              <p class="card-text mb-1"><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
              <p class="card-text mb-2"><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
              <div class="d-grid gap-1">
                <button class="btn btn-outline-primary btn-sm">➕ 加入書櫃</button>
                <a href="/book-sharing-system/frontend/book_detail.php?book_id=<?= $book['book_id'] ?>" class="btn btn-info btn-sm">🔍 查看詳情</a>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</body>
</html>
