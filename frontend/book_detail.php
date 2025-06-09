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
      width: 100%;
      max-width: 300px;
      height: auto;
      border: 1px solid #ccc;
      object-fit: cover;
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
      <hr>
      <p><strong>內容簡介：</strong></p>
      <p><?= nl2br(htmlspecialchars($book['description'] ?? '尚無簡介')) ?></p>
      
      <?php if ($user_id): ?>
        <button class="btn btn-outline-dark mt-3" onclick="openSilentShareModal()">📩 靜音分享</button>
      <?php endif; ?>

    </div>
  </div>



  <!-- 評論區 -->
  <hr>
  <h5 class="mt-4">📢 書籍評論</h5>
  <div id="myReviewSection" class="mb-4"></div>
  <div id="otherReviewsSection"></div>
</div>

<!-- Silent Share Modal -->
<div class="modal fade" id="silentShareModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">📩 靜音分享書籍</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <label>收件者（使用者名稱或 Email）：</label>
        <input type="text" id="recipientInput" class="form-control mb-2" required>
        <label>訊息內容：</label>
        <textarea id="messageInput" class="form-control mb-2" rows="3"></textarea>
        <label>解鎖時間：</label>
        <input type="datetime-local" id="unlockTimeInput" class="form-control">
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal">取消</button>
        <button class="btn btn-primary" onclick="submitSilentShare()">送出</button>
      </div>
    </div>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const bookId = <?= json_encode($book_id) ?>;

function loadReviews() {
  fetch(`/book-sharing-system/backend/get_review.php?book_id=${bookId}`)
    .then(res => res.json())
    .then(data => {
      const myReviewSection = document.getElementById('myReviewSection');
      const otherReviewsSection = document.getElementById('otherReviewsSection');
      myReviewSection.innerHTML = '';
      otherReviewsSection.innerHTML = '';

      if (!data.success || !data.reviews) return;

      const userId = <?= json_encode($user_id) ?>;
      let myReview = data.reviews.find(r => r.user_id == userId);

      if (myReview) {
        myReviewSection.innerHTML = `
          <div class="border rounded p-3 bg-light">
            <div class="d-flex justify-content-between align-items-center">
              <h6>我的評論</h6>
              <div>
                <button class="btn btn-sm btn-outline-primary me-2" onclick="editReview()">✏️ 編輯</button>
                <button class="btn btn-sm btn-outline-danger" onclick="deleteReview()">🗑️ 刪除</button>
              </div>
            </div>
            <div class="text-warning my-2">${renderStars(myReview.rating)}</div>
            <p class="mb-0">${myReview.comment || '（無內容）'}</p>
          </div>
        `;
      } else {
        myReviewSection.innerHTML = `
          <div class="border p-3 rounded">
            <h6 class="mb-2">新增評論</h6>
            <div id="starForm" class="mb-2 text-warning">${renderStarInput(0)}</div>
            <textarea id="commentInput" class="form-control mb-2" rows="3" placeholder="輸入評論內容（可留空）"></textarea>
            <button class="btn btn-primary btn-sm" onclick="submitReview()">送出</button>
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

function submitReview() {
  const rating = parseInt(document.getElementById('starForm').dataset.score || 0);
  const comment = document.getElementById('commentInput').value.trim();
  if (!rating) return alert('請點選星星評分');

  fetch('/book-sharing-system/backend/add_review.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ book_id: bookId, rating, comment })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✅ 評論成功');
      loadReviews();
    } else {
      alert('❌ ' + data.message);
    }
  });
}

function deleteReview() {
  if (!confirm('確定刪除評論？')) return;

  fetch('/book-sharing-system/backend/delete_review.php', {
    method: 'POST',
    credentials: 'include',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ book_id: bookId })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('已刪除');
      loadReviews();
    } else {
      alert('❌ 無法刪除');
    }
  });
}

function editReview() {
  // 同 submitReview，可擴充為可編輯介面（略）
  alert('請先刪除原評論再新增（可改為完整的編輯 UI）');
}

window.onload = loadReviews;

function openSilentShareModal() {
  const modal = new bootstrap.Modal(document.getElementById('silentShareModal'));
  modal.show();
}

function submitSilentShare() {
  const recipient = document.getElementById("recipientInput").value.trim();
  const message = document.getElementById("messageInput").value.trim();
  const unlockTime = document.getElementById("unlockTimeInput").value;

  if (!recipient || !unlockTime) {
    alert("請填寫收件者與解鎖時間");
    return;
  }

  fetch("/book-sharing-system/backend/silent_share_create.php", {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/x-www-form-urlencoded" },
    body: new URLSearchParams({
      book_id: <?= json_encode($book_id) ?>,
      recipient: recipient,
      message: message,
      unlock_time: unlockTime
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert("🎉 靜音分享成功！");
      bootstrap.Modal.getInstance(document.getElementById('silentShareModal')).hide();
    } else {
      alert("❌ 發送失敗：" + data.message);
    }
  });
}

</script>

</body>
</html>
