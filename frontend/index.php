<?php
session_start();
require_once '../backend/db.php';

// 使用者資訊
$user_id = $_SESSION['user_id'] ?? null;
$user_name = $_SESSION['user_name'] ?? null;

// 撈取所有書籍與作者
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

// 撈取使用者已加入的書籍 ID（若已登入）
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
      height: 500px;
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
    button.btn-success[onclick]:hover::after {
      content: "（點擊可加入其他書櫃）";
      display: block;
      font-size: 0.75rem;
      color: rgb(205, 249, 205);
      margin-top: 4px;
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
        👋 歡迎，<a href="/book-sharing-system/frontend/edit_profile.php" class="text-decoration-none"><?= htmlspecialchars($user_name) ?></a>
        <a href="/book-sharing-system/frontend/book_shelf_list.html" class="btn btn-outline-success btn-sm ms-2">📚 我的書櫃</a>
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
          <?php
            $cover = !empty($book['cover_url']) ? $book['cover_url'] : '/book-sharing-system/assets/img/default_cover.png';
          ?>
          <img src="<?= htmlspecialchars($cover) ?>" alt="封面" class="book-cover">
          <div class="card-body">
            <h5 class="card-title"><?= htmlspecialchars($book['title']) ?></h5>
            <p class="card-text mb-1"><strong>作者：</strong><?= htmlspecialchars($book['authors']) ?: '未知' ?></p>
            <p class="card-text mb-1"><strong>出版社：</strong><?= htmlspecialchars($book['publisher']) ?: '未知' ?></p>
            <p class="card-text mb-2"><strong>分類：</strong><?= htmlspecialchars($book['category']) ?: '無' ?></p>
            <div class="d-grid gap-1">
              <?php if ($user_id): ?>
                <?php if (in_array($book['book_id'], $addedBookIds)): ?>
                  <button class="btn btn-success btn-sm" onclick="addToShelfModal(<?= $book['book_id'] ?>, this)">
                    ✔ 已加入書櫃
                  </button>
                <?php else: ?>
                  <button class="btn btn-outline-primary btn-sm" onclick="addToShelfModal(<?= $book['book_id'] ?>, this)">
                    ➕ 加入書櫃
                  </button>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
let currentBookId = null;
let currentButton = null;

function addToShelfModal(bookId, btn = null) {
  currentBookId = bookId;
  currentButton = btn;

  fetch(`/book-sharing-system/backend/get_shelves.php?book_id=${bookId}`, {
  credentials: 'include'
})

  .then(res => res.json())
  .then(data => {
    const shelfOptions = document.getElementById('shelfOptions');
    const noShelfMessage = document.getElementById('noShelfMessage');
    shelfOptions.innerHTML = '';

    if (!data.success || !data.shelves || data.shelves.length === 0) {
      noShelfMessage.style.display = 'block';
      return;
    }

    noShelfMessage.style.display = 'none';

    data.shelves.forEach(shelf => {
      const btn = document.createElement('button');
      btn.className = 'list-group-item list-group-item-action d-flex justify-content-between align-items-center';
      btn.textContent = shelf.name;

      if (shelf.already_added == 1) {
        btn.classList.add('disabled', 'text-secondary');
        const badge = document.createElement('span');
        badge.className = 'badge bg-success rounded-pill';
        badge.textContent = '✔ 已加入';
        btn.appendChild(badge);
      } else {
        btn.onclick = () => addBookToShelf(shelf.shelf_id);
      }

      shelfOptions.appendChild(btn);
    });
    const modal = new bootstrap.Modal(document.getElementById('addToShelfModal'));
    modal.show();
  })
  .catch(() => alert('無法載入書櫃列表，請稍後再試'));
}

function addBookToShelf(shelfId) {
  if (!currentBookId) return;

  fetch('/book-sharing-system/backend/add_to_shelf.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    credentials: 'include',
    body: new URLSearchParams({
      book_id: currentBookId,
      shelf_id: shelfId
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      alert('✅ 書籍已成功加入書櫃');
      const modal = bootstrap.Modal.getInstance(document.getElementById('addToShelfModal'));
      modal.hide();

      if (currentButton) {
        currentButton.className = 'btn btn-success btn-sm';
        currentButton.textContent = '✔ 已加入書櫃';
        currentButton.disabled = true;
      }
    } else {
      alert('❌ ' + data.message);
    }
  })
  .catch(() => alert('加入失敗，請稍後再試'));
}
</script>

<!-- 書櫃選擇 Modal -->
<div class="modal fade" id="addToShelfModal" tabindex="-1" aria-labelledby="addToShelfModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content shadow">
      <div class="modal-header">
        <h5 class="modal-title" id="addToShelfModalLabel">選擇書櫃</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="關閉"></button>
      </div>
      <div class="modal-body">
        <div id="shelfOptions" class="list-group">
          <!-- 書櫃選項會在這裡動態插入 -->
        </div>
        <div id="noShelfMessage" class="text-muted text-center mt-3" style="display: none;">
          😢 你還沒有書櫃，請先建立一個。
        </div>
      </div>
    </div>
  </div>
</div>

</body>
</html>
