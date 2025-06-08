<?php
require_once 'db.php';
session_start();

// ✅ 登入與權限檢查
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

// ✅ 取得所有書籍資料
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
    body { background: #f4f4f4; }
    .offcanvas-header, .offcanvas-body { background: #fff; }
    .cover-img { width: 100%; height: auto; max-height: 250px; object-fit: cover; border-bottom: 1px solid #ccc; }
    .badge-tag { margin-right: 6px; }
  </style>
</head>
<body>
<div class="container mt-4">
  <div class="d-flex justify-content-between mb-3">
    <h2>
      <img src="/book-sharing-system/assets/img/12260_color.png" alt="icon" style="width: 50px; height: 50px; vertical-align: middle; margin-right: 8px;">
      書籍表格管理
    </h2>
    <div>
      歡迎，<?= $user_name ?>　
      <a href="/book-sharing-system/backend/logout.php" class="btn btn-outline-secondary btn-sm">登出</a>
    </div>
  </div>

  <form id="deleteForm">
    <div class="d-flex justify-content-between align-items-center mb-2">
      <a href="/book-sharing-system/frontend/create_book_form.html" class="btn btn-success btn-sm">➕ 新增書籍</a>
    </div>
    <div class="mb-2">
      <button type="submit" class="btn btn-danger btn-sm">🗑️ 刪除選取</button>
    </div>
    <table class="table table-bordered bg-white">
      <thead class="table-light">
        <tr>
          <th><input type="checkbox" id="selectAll" onclick="toggleAll(this)"></th>
          <th>#</th>
          <th>書名</th>
          <th>作者</th>
          <th>操作</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($books as $i => $book): ?>
          <tr data-id="<?= $book['book_id'] ?>">
            <td><input type="checkbox" name="delete_ids[]" value="<?= $book['book_id'] ?>"></td>
            <td><?= $i + 1 ?></td>
            <td ondblclick="makeEditable(this, 'title', <?= $book['book_id'] ?>)"><?= htmlspecialchars($book['title']) ?: '（點此編輯）' ?></td>
            <td ondblclick="makeEditable(this, 'authors', <?= $book['book_id'] ?>)"><?= htmlspecialchars($book['authors']) ?: '（點此編輯）' ?></td>
            <td><button type="button" class="btn btn-sm btn-info" onclick="showDetail(<?= $book['book_id'] ?>)">查看</button></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </form>
</div>

<!-- 詳情側欄 -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="detailCanvas">
  <div class="offcanvas-header">
    <h5 id="canvasTitle" ondblclick="makeEditableSpan(this)" data-field="title">（點此編輯）</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">
    <img id="canvasCover" src="" class="cover-img mb-3">
    <p><strong>COVER URL：</strong><span id="canvasCoverUrl" ondblclick="makeEditableSpan(this)" data-field="cover_url">（點此編輯）</span></p>
    <p><strong>作者：</strong><span id="canvasAuthors" ondblclick="makeEditableSpan(this)" data-field="authors">（點此編輯）</span></p>
    <p><strong>出版社：</strong> <span class="badge bg-secondary" id="canvasPublisher" ondblclick="makeEditableSpan(this)" data-field="publisher">（點此編輯）</span></p>
    <p><strong>分類：</strong> <span class="badge bg-info text-dark" id="canvasCategory" ondblclick="makeEditableSpan(this)" data-field="category">（點此編輯）</span></p>
    <p><strong>簡介：</strong><br><span id="canvasDescription" ondblclick="makeEditableSpan(this)" data-field="description">（點此編輯）</span></p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
const books = <?= json_encode($books, JSON_UNESCAPED_UNICODE) ?>;
let currentBookId = null;
const placeholder = '（點此編輯）';

function toggleAll(source) {
  const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]');
  checkboxes.forEach(cb => cb.checked = source.checked);
}

function showDetail(bookId) {
  const book = books.find(b => b.book_id == bookId);
  if (!book) return;
  currentBookId = bookId;

  document.getElementById("canvasTitle").textContent = book.title || placeholder;
  document.getElementById("canvasCover").src = book.cover_url || '';
  document.getElementById("canvasCoverUrl").textContent = book.cover_url || placeholder;
  document.getElementById("canvasAuthors").textContent = book.authors || placeholder;
  document.getElementById("canvasPublisher").textContent = book.publisher || placeholder;
  document.getElementById("canvasCategory").textContent = book.category || placeholder;
  document.getElementById("canvasDescription").textContent = book.description || placeholder;

  new bootstrap.Offcanvas('#detailCanvas').show();
}

function makeEditable(cell, field, bookId) {
  const oldValue = (cell.textContent.trim() === placeholder) ? '' : cell.textContent.trim();
  const input = document.createElement('input');
  input.type = 'text';
  input.value = oldValue;
  input.className = 'form-control form-control-sm';
  cell.textContent = '';
  cell.appendChild(input);
  input.focus();

  input.addEventListener('blur', function () {
    const newValue = this.value.trim();
    if (newValue === oldValue) {
      cell.textContent = oldValue || placeholder;
      return;
    }

    fetch('update_book.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        book_id: bookId,
        field: field,
        value: newValue
      })
    }).then(res => res.text()).then(result => {
      cell.textContent = result === 'OK' ? (newValue || placeholder) : oldValue || placeholder;
      if (result !== 'OK') alert('更新失敗：' + result);
    }).catch(() => {
      alert('請求失敗');
      cell.textContent = oldValue || placeholder;
    });
  });
}

function makeEditableSpan(span) {
  const field = span.dataset.field;
  if (!field || !currentBookId) return;
  const oldValue = (span.textContent.trim() === placeholder) ? '' : span.textContent.trim();

  const input = document.createElement('input');
  input.type = 'text';
  input.className = 'form-control form-control-sm';
  input.value = oldValue;
  span.replaceWith(input);
  input.focus();

  input.addEventListener('blur', function () {
    const newValue = this.value.trim();
    if (newValue === oldValue) {
      this.replaceWith(span);
      return;
    }

    fetch('update_book.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: new URLSearchParams({
        book_id: currentBookId,
        field: field,
        value: newValue
      })
    }).then(r => r.text()).then(res => {
      if (res === 'OK') {
        span.textContent = newValue || placeholder;
        if (field === 'cover_url') {
          document.getElementById("canvasCover").src = newValue;
        }
      } else {
        alert('更新失敗：' + res);
      }
      this.replaceWith(span);
    }).catch(() => {
      alert('請求錯誤');
      this.replaceWith(span);
    });
  });
}

// ✅ AJAX 批次刪除
document.getElementById('deleteForm').addEventListener('submit', function (e) {
  e.preventDefault();
  const checkboxes = document.querySelectorAll('input[name="delete_ids[]"]:checked');
  const ids = Array.from(checkboxes).map(cb => parseInt(cb.value));

  if (ids.length === 0) {
    alert("請先選取要刪除的書籍");
    return;
  }

  if (!confirm(`確定要刪除 ${ids.length} 本書嗎？`)) return;

  fetch('delete_books.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ book_ids: ids })
  })
  .then(r => r.json())
  .then(res => {
    if (res.success) {
      ids.forEach(id => {
        const row = document.querySelector(`tr[data-id="${id}"]`);
        if (row) row.remove();
      });
      alert("刪除成功");
    } else {
      alert("刪除失敗：" + res.message);
    }
  })
  .catch(() => {
    alert("伺服器錯誤");
  });
});
</script>
</body>
</html>
