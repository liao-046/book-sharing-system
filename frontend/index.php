<?php
session_start();
require_once '../backend/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// 取得使用者頭像
if ($user_id) {
    $stmt_avatar = $pdo->prepare("SELECT avatar FROM user WHERE user_id = ?");
    $stmt_avatar->execute([$user_id]);
    $user_avatar = $stmt_avatar->fetchColumn();
    $avatar_url = $user_avatar
        ? '/book-sharing-system/assets/img/' . $user_avatar . '?t=' . time()
        : '/book-sharing-system/assets/img/default.png?t=' . time();
} else {
    $avatar_url = '/book-sharing-system/assets/img/default.png';
}

// 取得搜尋與排序參數
$search = $_GET['q'] ?? '';
$sort = $_GET['sort'] ?? 'new';

// WHERE 子句與參數
$where = '';
$params = [];
if ($search !== '') {
    $where = "WHERE b.title LIKE ? OR a.name LIKE ?";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

// 排序條件
switch ($sort) {
    case 'old':
        $orderBy = 'b.book_id ASC';
        break;
    case 'rating':
        $orderBy = 'avg_rating DESC';
        break;
    default:
        $orderBy = 'b.book_id DESC';
}

// 執行查詢
$sql = "
  SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url,
         GROUP_CONCAT(DISTINCT a.name SEPARATOR ', ') AS authors,
         ROUND(AVG(r.rating), 1) AS avg_rating,
         COUNT(r.review_id) AS review_count
  FROM book b
  LEFT JOIN book_author ba ON b.book_id = ba.book_id
  LEFT JOIN author a ON ba.author_id = a.author_id
  LEFT JOIN review r ON b.book_id = r.book_id
  $where
  GROUP BY b.book_id
  ORDER BY $orderBy
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$books = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 已加入書櫃的書籍 ID
$addedBookIds = [];
if ($user_id) {
    $stmt = $pdo->prepare("
        SELECT br.book_id
        FROM bookshelf_record br
        JOIN book_shelf bs ON br.shelf_id = bs.shelf_id
        WHERE bs.user_id = ?
    ");
    $stmt->execute([$user_id]);
    $addedBookIds = array_column($stmt->fetchAll(), 'book_id');
}
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
      height: 520px;
      margin-bottom: 20px;
      transition: transform 0.2s;
    }
    .book-card:hover { transform: scale(1.03); }
    .book-cover {
      width: 100%;
      height: 280px;
      object-fit: cover;
      border-bottom: 1px solid #ddd;
    }
    .avatar-small {
      width: 35px;
      height: 35px;
      border-radius: 50%;
      object-fit: cover;
      vertical-align: middle;
      margin-right: 8px;
      border: 1.5px solid #ddd;
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
        <img src="<?= htmlspecialchars($avatar_url) ?>" alt="頭像" class="avatar-small">
        👋 歡迎，<a href="/book-sharing-system/frontend/edit_profile.php" class="text-decoration-none"><?= htmlspecialchars($user_name) ?></a>
        <a href="/book-sharing-system/frontend/book_shelf_list.html" class="btn btn-outline-success btn-sm ms-2">📚 我的書櫃</a>
        <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-secondary btn-sm ms-2">登出</a>
      <?php else: ?>
        <a href="/book-sharing-system/frontend/login.html" class="btn btn-primary">登入</a>
      <?php endif; ?>
    </div>
  </div>

  <form method="get" class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
  <!-- 搜尋欄，盡量拉長 -->
  <input type="text" name="q" class="form-control flex-grow-1" style="min-width: 250px;"
         placeholder="🔍 搜尋書名或作者"
         value="<?= htmlspecialchars($search) ?>">

  <!-- 包裹一層容器，用 justify-content-end 靠右 -->
<div class="d-flex justify-content-end">
<div class="d-flex flex-nowrap gap-2 ms-auto">
    <select name="sort" class="form-select" style="width: 120px;">
      <option value="new" <?= $sort === 'new' ? 'selected' : '' ?>>📅 最新</option>
      <option value="old" <?= $sort === 'old' ? 'selected' : '' ?>>📁 最舊</option>
      <option value="rating" <?= $sort === 'rating' ? 'selected' : '' ?>>⭐ 評分高</option>
    </select>
    <button type="submit" class="btn btn-primary" style="width: 80px;">篩選</button>
  </div>
</div>

</form>




  <?php if (count($books) === 0): ?>
    <div class="alert alert-info text-center">🔍 找不到符合的書籍</div>
  <?php endif; ?>

  <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-4">
    <?php foreach ($books as $book): ?>
      <div class="col">
        <div class="card book-card shadow-sm">
          <img src="<?= htmlspecialchars($book['cover_url'] ?? '/book-sharing-system/assets/img/default.png') ?>" alt="封面" class="book-cover">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
            <p class="card-text mb-1">
              <strong>作者：</strong>
              <span title="<?= htmlspecialchars($book['authors'] ?? '未知') ?>">
                <?= htmlspecialchars(mb_strimwidth($book['authors'] ?? '未知', 0, 15, '...')) ?>
              </span>
            </p>
            <p class="card-text mb-1"><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
            <p class="card-text mb-2"><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
            <p class="card-text mb-1">
              <strong>評分：</strong>
              <?php if (is_null($book['avg_rating'])): ?>
                尚無評分
              <?php else: ?>
                <?= str_repeat('★', round($book['avg_rating'])) . str_repeat('☆', 5 - round($book['avg_rating'])) ?>
                (<?= $book['avg_rating'] ?>/5)
                <span class="text-muted">(<?= $book['review_count'] ?> 則評論)</span>
              <?php endif; ?>
            </p>
            <div class="d-grid gap-1">
              <?php if ($user_id): ?>
                <?php if (in_array($book['book_id'], $addedBookIds)): ?>
                  <button class="btn btn-success btn-sm" onclick="addToShelfModal(<?= $book['book_id'] ?>, this)">✔ 已加入書櫃</button>
                <?php else: ?>
                  <button class="btn btn-outline-primary btn-sm" onclick="addToShelfModal(<?= $book['book_id'] ?>, this)">➕ 加入書櫃</button>
                <?php endif; ?>
              <?php endif; ?>
              <a href="/book-sharing-system/frontend/book_detail.php?book_id=<?= $book['book_id'] ?>" class="btn btn-info btn-sm">🔍 查看詳情</a>
            </div>
          </div>
        </div>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<!-- 書櫃 Modal（略） -->
<!-- 加入書櫃的 JS 函式（略） -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
