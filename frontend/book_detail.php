<?php
session_start();
require_once '../backend/db.php';

$book_id = $_GET['book_id'] ?? null;

if (!$book_id) {
  echo "錯誤：未提供書籍 ID";
  exit;
}

$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// 書籍資料
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

// 平均評分
$stmt = $pdo->prepare("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM review WHERE book_id = ?");
$stmt->execute([$book_id]);
$summary = $stmt->fetch();

// 所有評論（含自己）
$stmt = $pdo->prepare("
  SELECT r.user_id, u.name AS user_name, r.rating, r.comment, r.create_time
  FROM review r
  JOIN user u ON r.user_id = u.user_id
  WHERE r.book_id = ?
  ORDER BY r.create_time DESC
");
$stmt->execute([$book_id]);
$all_reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 自己的評論（如果有）
$my_review = null;
foreach ($all_reviews as $r) {
  if ($r['user_id'] == $user_id) {
    $my_review = $r;
    break;
  }
}
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
      width: 100%; max-width: 300px; object-fit: cover; border: 1px solid #ccc;
    }
    .star {
      font-size: 24px;
      cursor: pointer;
      color: #ccc;
    }
    .star.selected {
      color: #ffc107;
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

  <div class="row">
    <div class="col-md-4 text-center">
      <img src="<?= htmlspecialchars($book['cover_url']) ?>" class="book-cover"
           onerror="this.src='/book-sharing-system/assets/img/default_cover.png'">
    </div>
    <div class="col-md-8">
      <h3><?= htmlspecialchars($book['title']) ?></h3>
      <?php if ($summary && $summary['total'] > 0): ?>
        <p class="text-muted">🌟 平均評分：<?= round($summary['avg_rating'], 1) ?> / 5（共 <?= $summary['total'] ?> 筆）</p>
      <?php endif; ?>
      <p><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
      <p><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
      <p><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
      <hr>
      <p><strong>內容簡介：</strong></p>
      <p><?= nl2br(htmlspecialchars($book['description'] ?? '尚無簡介')) ?></p>
    </div>
  </div>

  <!-- 所有他人評論 -->
  <?php if (count($all_reviews) > 0): ?>
    <hr>
    <div class="my-4">
      <h5>💬 讀者評論</h5>
      <?php foreach ($all_reviews as $r): ?>
        <?php if ($r['user_id'] != ($user_id ?? -1)): ?>
          <div class="border rounded p-2 mb-2 bg-white shadow-sm">
            <div class="d-flex justify-content-between">
              <strong><?= htmlspecialchars($r['user_name']) ?></strong>
              <div class="text-warning"><?= str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']) ?></div>
            </div>
            <div class="text-muted small"><?= $r['create_time'] ?></div>
            <div><?= nl2br(htmlspecialchars($r['comment'])) ?></div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- 自己的評論 -->
  <?php if ($user_id): ?>
    <div class="border rounded p-3 mb-3 bg-light">
  <div class="d-flex justify-content-between align-items-center">
    <h6 class="mb-1">我的評論</h6>
    <div>
      <button class="btn btn-sm btn-outline-primary me-2" onclick="editReview()">✏️ 編輯</button>
      <button class="btn btn-sm btn-outline-danger" onclick="deleteReview()">🗑️ 刪除</button>
    </div>
  </div>
  <!-- 評分星星與評論內容顯示... -->
</div>
  <?php endif; ?>
</div>

<script>
  document.querySelectorAll('.star').forEach(star => {
    star.addEventListener('click', function () {
      const value = this.dataset.value;
      document.getElementById('ratingInput').value = value;

      document.querySelectorAll('.star').forEach(s => {
        s.classList.toggle('selected', s.dataset.value <= value);
      });
    });
  });

  document.getElementById('reviewForm')?.addEventListener('submit', async e => {
    e.preventDefault();
    const form = e.target;
    const data = new URLSearchParams(new FormData(form));

    const res = await fetch('/book-sharing-system/backend/<?= $my_review ? 'update_review.php' : 'add_review.php' ?>', {
      method: 'POST',
      credentials: 'include',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: data
    });
    const result = await res.json();
    if (result.success) {
      alert('✅ 評論已送出！');
      location.reload();
    } else {
      alert('❌ ' + (result.message || '評論失敗'));
    }
  });


function deleteReview() {
  if (!confirm("確定要刪除你的評論嗎？")) return;

  fetch('/book-sharing-system/backend/delete_review.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'include',
    body: new URLSearchParams({
      book_id: <?= json_encode($book_id) ?>
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("✅ 評論已刪除");
      location.reload();
    } else {
      alert("❌ 刪除失敗：" + data.message);
    }
  })
  .catch(() => alert("❌ 發生錯誤"));
}

</script>
</body>
</html>
  