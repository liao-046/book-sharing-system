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

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// 查詢使用者是否已評論
$userReview = null;
if ($user_id) {
  $stmt = $pdo->prepare("SELECT * FROM review WHERE user_id = ? AND book_id = ?");
  $stmt->execute([$user_id, $book_id]);
  $userReview = $stmt->fetch(PDO::FETCH_ASSOC);
}

// 撈取所有評論
$stmt = $pdo->prepare("
  SELECT r.*, u.username FROM review r
  JOIN user u ON r.user_id = u.user_id
  WHERE r.book_id = ?
  ORDER BY r.create_time DESC
");
$stmt->execute([$book_id]);
$reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($book['title']) ?> - 書籍詳情</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .book-cover { max-width: 280px; border: 1px solid #ccc; }
    .star { font-size: 1.5rem; color: gold; cursor: pointer; }
    .review-box { background: #fff; padding: 1rem; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 1rem; }
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

  <div class="row mb-4">
    <div class="col-md-4 text-center">
      <img src="<?= htmlspecialchars($book['cover_url']) ?>" class="book-cover"
           onerror="this.src='/book-sharing-system/assets/img/default_cover.png'">
    </div>
    <div class="col-md-8">
      <h3><?= htmlspecialchars($book['title']) ?></h3>
      <p><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
      <p><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
      <p><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
      <hr>
      <p><strong>內容簡介：</strong><br><?= nl2br(htmlspecialchars($book['description'] ?? '尚無簡介')) ?></p>
    </div>
  </div>

  <?php if ($user_id): ?>
    <div class="mb-4">
      <h5>📝 我的評論</h5>

      <?php if ($userReview): ?>
        <div class="review-box position-relative">
          <div class="position-absolute top-0 end-0 p-2">
            <button class="btn btn-sm btn-outline-primary" onclick="toggleEditForm(true)">✏️ 編輯</button>
          </div>
          <div id="myReviewDisplay">
            <div><?= str_repeat('★', $userReview['rating']) . str_repeat('☆', 5 - $userReview['rating']) ?></div>
            <p class="mb-0"><?= nl2br(htmlspecialchars($userReview['comment'])) ?></p>
          </div>

          <form id="editForm" style="display:none" method="post" action="../backend/update_review.php">
            <input type="hidden" name="review_id" value="<?= $userReview['review_id'] ?>">
            <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
            <div class="mb-2">
              <div id="editStars"></div>
              <input type="hidden" name="rating" id="editRating" value="<?= $userReview['rating'] ?>">
            </div>
            <textarea name="comment" class="form-control mb-2" rows="3"><?= htmlspecialchars($userReview['comment']) ?></textarea>
            <button type="submit" class="btn btn-success btn-sm">更新評論</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="toggleEditForm(false)">取消</button>
          </form>
        </div>
      <?php else: ?>
        <form class="review-box" method="post" action="../backend/add_review.php">
          <input type="hidden" name="book_id" value="<?= $book['book_id'] ?>">
          <div class="mb-2">
            <div id="newStars"></div>
            <input type="hidden" name="rating" id="newRating" required>
          </div>
          <textarea name="comment" class="form-control mb-2" rows="3" placeholder="分享你的看法..."></textarea>
          <button type="submit" class="btn btn-primary btn-sm">送出評論</button>
        </form>
      <?php endif; ?>
    </div>
  <?php endif; ?>

  <h5>🗣️ 所有評論</h5>
  <?php foreach ($reviews as $r): ?>
    <div class="review-box">
      <strong><?= htmlspecialchars($r['username']) ?></strong> - <?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?>
      <p class="mb-0"><?= nl2br(htmlspecialchars($r['comment'])) ?></p>
    </div>
  <?php endforeach; ?>
</div>

<script>
function renderStars(containerId, inputId, current) {
  const container = document.getElementById(containerId);
  container.innerHTML = '';
  for (let i = 1; i <= 5; i++) {
    const span = document.createElement('span');
    span.textContent = i <= current ? '★' : '☆';
    span.className = 'star';
    span.onclick = () => {
      document.getElementById(inputId).value = i;
      renderStars(containerId, inputId, i);
    };
    container.appendChild(span);
  }
}
function toggleEditForm(show) {
  document.getElementById('myReviewDisplay').style.display = show ? 'none' : 'block';
  document.getElementById('editForm').style.display = show ? 'block' : 'none';
}
renderStars('newStars', 'newRating', 0);
renderStars('editStars', 'editRating', <?= $userReview['rating'] ?? 0 ?>);
</script>
</body>
</html>