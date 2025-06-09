<?php
session_start();
require_once '../backend/db.php';

$book_id = $_GET['book_id'] ?? null;
if (!$book_id) {
  echo "錯誤：未提供書籍 ID";
  exit;
}

// 撈書籍資料
$stmt = $pdo->prepare("
  SELECT b.book_id, b.title, b.publisher, b.category, b.cover_url, b.description,
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

// 撈取評論
$stmt = $pdo->prepare("
  SELECT r.rating, r.comment, u.name, r.create_time
  FROM review r
  JOIN user u ON r.user_id = u.user_id
  WHERE r.book_id = ?
  ORDER BY r.create_time DESC
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

$user_id = $_SESSION['user_id'] ?? null;
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
      <a href="index.php" class="btn btn-secondary btn-sm">← 返回瀏覽</a>
      <?php if ($user_name): ?>
        <span class="ms-2">👋 <?= htmlspecialchars($user_name) ?></span>
      <?php endif; ?>
    </div>
  </div>

  <div class="row mb-5">
    <div class="col-md-4 text-center">
      <img src="<?= htmlspecialchars($book['cover_url']) ?>" alt="封面" class="book-cover"
           onerror="this.src='/book-sharing-system/assets/img/default_cover.png'">
    </div>
    <div class="col-md-8">
      <h3><?= htmlspecialchars($book['title']) ?></h3>
      <p><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
      <p><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
      <p><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
      <hr>
      <p><strong>內容簡介：</strong></p>
      <p><?= nl2br(htmlspecialchars($book['description'] ?? '尚無簡介')) ?></p>
    </div>
  </div>

  <!-- 留言區 -->
  <h4 class="mb-3">⭐ 評論與評分</h4>

  <?php if ($user_id): ?>
    <form class="mb-4" id="reviewForm">
      <div class="mb-2">
        <label for="rating" class="form-label">評分（1～5）</label>
        <select class="form-select w-auto" name="rating" id="rating" required>
          <option value="">請選擇</option>
          <?php for ($i = 5; $i >= 1; $i--): ?>
            <option value="<?= $i ?>"><?= $i ?> 分</option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="mb-2">
        <label for="comment" class="form-label">留言</label>
        <textarea class="form-control" id="comment" name="comment" rows="3" placeholder="想說點什麼嗎？"></textarea>
      </div>
      <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
      <button type="submit" class="btn btn-primary">送出評論</button>
    </form>
  <?php else: ?>
    <div class="alert alert-warning">請先 <a href="login.html">登入</a> 才能發表評論。</div>
  <?php endif; ?>

  <div id="reviewList">
    <?php if (count($reviews) === 0): ?>
      <div class="text-muted">尚無任何評論。</div>
    <?php else: ?>
      <?php foreach ($reviews as $r): ?>
        <div class="border rounded p-3 mb-3 bg-white">
          <div class="d-flex justify-content-between">
            <strong><?= htmlspecialchars($r['name']) ?></strong>
            <span class="text-muted small"><?= $r['create_time'] ?></span>
          </div>
          <div>⭐ <?= str_repeat('⭐', $r['rating']) ?> (<?= $r['rating'] ?> 分)</div>
          <?php if ($r['comment']): ?>
            <div class="mt-2"><?= nl2br(htmlspecialchars($r['comment'])) ?></div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
</div>

<script>
document.getElementById('reviewForm')?.addEventListener('submit', async (e) => {
  e.preventDefault();
  const formData = new FormData(e.target);

  const res = await fetch('/book-sharing-system/backend/add_review.php', {
    method: 'POST',
    body: new URLSearchParams(formData),
    credentials: 'include'
  });

  const data = await res.json();
  if (data.success) {
    alert("✅ 評論已新增！");
    location.reload();
  } else {
    alert("❌ " + (data.message || "新增失敗"));
  }
});
</script>
</body>
</html>
