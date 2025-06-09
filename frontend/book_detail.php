<?php
session_start();
require_once '../backend/db.php';

$book_id = $_GET['book_id'] ?? null;
if (!$book_id) {
  echo "錯誤：未提供書籍 ID";
  exit;
}

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
      width: 100%; max-width: 300px; height: auto;
      border: 1px solid #ccc; object-fit: cover;
    }
    .star { font-size: 1.5rem; color: gold; cursor: pointer; }
    .star:hover, .star:hover ~ .star { color: orange; }
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
      <p id="ratingSummary"><em>正在載入評分...</em></p>
      <hr>
      <p><strong>內容簡介：</strong></p>
      <p><?= nl2br(htmlspecialchars($book['description'] ?? '尚無簡介')) ?></p>
    </div>
  </div>

  <hr>
  <h5 class="mt-4">📢 書籍評論</h5>
  <div id="myReviewSection" class="mb-4"></div>
  <div id="otherReviewsSection"></div>
</div>

<script>
const bookId = <?= json_encode($book_id) ?>;
const userId = <?= json_encode($user_id) ?>;

function loadRatingSummary() {
  fetch(`/book-sharing-system/backend/get_rating_summary.php?book_id=${bookId}`)
    .then(res => res.json())
    .then(data => {
      const ratingDiv = document.getElementById('ratingSummary');
      if (!data.success) {
        ratingDiv.textContent = '評分載入失敗';
        return;
      }
      if (data.total_reviews === 0) {
        ratingDiv.innerHTML = `<strong>綜合評分：</strong>尚無評論`;
      } else {
        const stars = '★'.repeat(Math.round(data.avg_rating)) + '☆'.repeat(5 - Math.round(data.avg_rating));
        ratingDiv.innerHTML = `<strong>綜合評分：</strong>
          <span class="text-warning">${stars}</span>
          (${data.avg_rating}/5，${data.total_reviews} 則評論)`;
      }
    });
}

function loadReviews() {
  fetch(`/book-sharing-system/backend/get_review.php?book_id=${bookId}`)
    .then(res => res.json())
    .then(data => {
      const myReviewSection = document.getElementById('myReviewSection');
      const otherReviewsSection = document.getElementById('otherReviewsSection');
      myReviewSection.innerHTML = '';
      otherReviewsSection.innerHTML = '';

      if (!data.success || !data.reviews) return;

      let myReview = data.reviews.find(r => r.user_id == userId);

      if (myReview) {
        myReviewSection.innerHTML = `
          <div class="border rounded p-3 bg-light">
            <h6 class="mb-2">✏️ 編輯我的評論</h6>
            <div id="starForm" class="mb-2 text-warning">${renderStarInput(myReview.rating)}</div>
            <textarea id="commentInput" class="form-control mb-2" rows="3">${myReview.comment || ''}</textarea>
            <button class="btn btn-primary btn-sm me-2" onclick="submitReview(true)">💾 儲存</button>
            <button class="btn btn-outline-danger btn-sm" onclick="deleteReview()">🗑️ 刪除</button>
          </div>
        `;
        addStarInputEvents();
      } else {
        myReviewSection.innerHTML = `
          <div class="border p-3 rounded">
            <h6 class="mb-2">新增評論</h6>
            <div id="starForm" class="mb-2 text-warning">${renderStarInput(0)}</div>
            <textarea id="commentInput" class="form-control mb-2" rows="3" placeholder="輸入評論內容（可留空）"></textarea>
            <button class="btn btn-primary btn-sm" onclick="submitReview(false)">送出</button>
          </div>
        `;
        addStarInputEvents();
      }

      data.reviews
        .filter(r => r.user_id != userId)
        .forEach(r => {
          const div = document.createElement('div');
          div.className = 'border-bottom py-2';
          div.innerHTML = `
            <strong>${r.user_name}</strong>：
            <span class="text-warning">${renderStars(r.rating)}</span><br>
            <small class="text-muted">${r.create_time}</small><br>
            ${r.comment ? `<p class="mb-1">${r.comment}</p>` : ''}
          `;
          otherReviewsSection.appendChild(div);
        });
    });
}

function renderStars(score) {
  return '★'.repeat(score) + '☆'.repeat(5 - score);
}
function renderStarInput(score) {
  return Array.from({ length: 5 }, (_, i) =>
    `<span class="star" data-score="${i + 1}">${i < score ? '★' : '☆'}</span>`
  ).join('');
}
function addStarInputEvents() {
  document.querySelectorAll('#starForm .star').forEach(star => {
    star.onclick = () => {
      const score = parseInt(star.dataset.score);
      document.getElementById('starForm').innerHTML = renderStarInput(score);
      document.getElementById('starForm').dataset.score = score;
      addStarInputEvents();
    };
  });
}

function submitReview(isEdit) {
  const rating = parseInt(document.getElementById('starForm').dataset.score || 0);
  const comment = document.getElementById('commentInput').value.trim();
  if (!rating) return alert('請選擇星星評分');

  const url = isEdit
    ? '/book-sharing-system/backend/update_review.php'
    : '/book-sharing-system/backend/add_review.php';

  fetch(url, {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ book_id: bookId, rating, comment })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✅ 已儲存評論');
      loadReviews();
      loadRatingSummary();
    } else {
      alert('❌ ' + data.message);
    }
  });
}

function deleteReview() {
  if (!confirm('確定要刪除這則評論？')) return;
  fetch('/book-sharing-system/backend/delete_review.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ book_id: bookId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✅ 已刪除評論');
      loadReviews();
      loadRatingSummary();
    } else {
      alert('❌ 刪除失敗');
    }
  });
}

window.onload = () => {
  loadReviews();
  loadRatingSummary();
};
</script>
</body>
</html>
