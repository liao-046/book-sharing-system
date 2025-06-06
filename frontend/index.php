<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : null;
?>

<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8">
  <title>首頁 - 書櫃分享系統</title>
  <style>
    body { font-family: Arial, sans-serif; background: #f9f9f9; margin: 0; padding: 0; }
    header { background: #333; color: white; padding: 10px 20px; }
    header h1 { display: inline; font-size: 1.8em; }
    header .actions {
      float: right;
      font-size: 1.2em;
      font-weight: 600;
      display: flex;
      align-items: center;
      gap: 15px;
    }
    header .actions a,
    header .actions button {
      color: white;
      font-size: 1.1em;
      font-weight: 600;
      padding: 8px 14px;
      border-radius: 6px;
      border: none;
      cursor: pointer;
      text-decoration: none;
      background-color: transparent;
      transition: background-color 0.3s ease;
    }
    header .actions a:hover,
    header .actions button:hover {
      background-color: #555;
    }
    header .actions button.login-btn {
      background-color: #2196F3;
    }
    header .actions button.logout-btn {
      background-color: #f44336;
    }
    header .actions button.bookshelf-btn {
      background-color: #4CAF50;
    }
    .search-sort {
      display: flex;
      justify-content: space-between;
      margin-top: 20px;
      margin-bottom: 20px;
    }
    .search-sort input[type="text"],
    .search-sort select {
      font-size: 1.1em;
      padding: 8px 12px;
    }
    .book-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    /* 新增書櫃選擇視窗樣式 */
    #shelfSelectorModal {
      display: none;
      position: fixed;
      top: 30%;
      left: 50%;
      transform: translateX(-50%);
      background: #fff;
      padding: 20px;
      box-shadow: 0 2px 10px rgba(0,0,0,0.3);
      border-radius: 8px;
      z-index: 999;
    }
    #modalBackdrop {
      display: none;
      position: fixed;
      top: 0; left: 0;
      width: 100%; height: 100%;
      background: rgba(0,0,0,0.4);
      z-index: 998;
    }
  </style>
</head>
<body>
<header>
  <h1>
    <?php if ($is_logged_in): ?>
      歡迎，<?= $user_name ?>！
    <?php else: ?>
      書櫃分享系統
    <?php endif; ?>
  </h1>
  <div class="actions">
    <button class="login-btn" onclick="location.href='/book-sharing-system/frontend/login.html'">登入</button>
    <?php if ($is_logged_in): ?>
      <button class="logout-btn" onclick="location.href='/book-sharing-system/backend/logout.php'">登出</button>
      <button class="bookshelf-btn" onclick="location.href='/book-sharing-system/frontend/bookshelf_list.html'">我的書櫃</button>
    <?php endif; ?>
  </div>
</header>

<div class="container">
  <div class="search-sort">
    <div style="display: flex; gap: 10px; width: 65%;">
      <input type="text" id="searchInput" placeholder="搜尋書名或作者" style="flex: 1;">
      <button onclick="renderBooks(allBooks)">搜尋</button>
    </div>
    <select id="sortSelect">
      <option value="new">最新上架</option>
      <option value="rating">評分高到低</option>
    </select>
  </div>

  <div id="bookContainer"></div>
</div>

<!-- 書櫃選擇彈窗 -->
<div id="shelfSelectorModal">
  <h3>選擇書櫃</h3>
  <select id="shelfSelect" style="width:100%; padding:8px;"></select>
  <br><br>
  <button onclick="confirmAddToShelf()">確定</button>
  <button onclick="closeShelfSelector()">取消</button>
</div>
<div id="modalBackdrop" onclick="closeShelfSelector()"></div>

<script>
let allBooks = {};
const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;
let selectedBookId = null;

function renderBooks(data) {
  const container = document.getElementById("bookContainer");
  container.innerHTML = "";
  const searchKeyword = document.getElementById("searchInput").value.toLowerCase();
  const sortBy = document.getElementById("sortSelect").value;

  Object.entries(data).forEach(([category, books]) => {
    let filtered = books.filter(b => 
      b.title.toLowerCase().includes(searchKeyword) || 
      b.authors.toLowerCase().includes(searchKeyword)
    );
    if (sortBy === "rating") {
      filtered.sort((a, b) => b.avg_rating - a.avg_rating);
    } else {
      filtered.sort((a, b) => b.book_id - a.book_id);
    }
    if (filtered.length === 0) return;

    const block = document.createElement("div");
    block.className = "category-block";
    block.innerHTML = `<div class="category-title">${category}</div><div class="book-grid"></div>`;
    const grid = block.querySelector(".book-grid");

    filtered.forEach(book => {
      const card = document.createElement("div");
      card.className = "book-card";
      card.innerHTML = `
        <img src="${book.cover_url}" alt="cover">
        <h3>${book.title}</h3>
        <p>作者：${book.authors}</p>
        <p>評分：${parseFloat(book.avg_rating).toFixed(1)}</p>
        <button onclick="openShelfSelector(${book.book_id})">加入書架</button>
      `;
      grid.appendChild(card);
    });

    container.appendChild(block);
  });
}

function openShelfSelector(bookId) {
  if (!isLoggedIn) {
    alert("請先登入才能加入書架！");
    window.location.href = "/book-sharing-system/frontend/login.html";
    return;
  }
  selectedBookId = bookId;

  fetch("/book-sharing-system/backend/get_shelves.php")
    .then(res => res.json())
    .then(data => {
      if (data.success && data.shelves.length > 0) {
        const select = document.getElementById("shelfSelect");
        select.innerHTML = "";
        data.shelves.forEach(shelf => {
          const option = document.createElement("option");
          option.value = shelf.shelf_id;
          option.textContent = shelf.name;
          select.appendChild(option);
        });
        document.getElementById("shelfSelectorModal").style.display = "block";
        document.getElementById("modalBackdrop").style.display = "block";
      } else {
        alert("尚未建立任何書櫃，請先前往建立書櫃！");
      }
    })
    .catch(() => alert("無法取得書櫃列表"));
}

function closeShelfSelector() {
  document.getElementById("shelfSelectorModal").style.display = "none";
  document.getElementById("modalBackdrop").style.display = "none";
  selectedBookId = null;
}

function confirmAddToShelf() {
  const shelfId = document.getElementById("shelfSelect").value;
  if (!shelfId || !selectedBookId) return;

  fetch("/book-sharing-system/backend/add_book_to_shelf.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: `book_id=${selectedBookId}&shelf_id=${shelfId}`
  })
    .then(res => res.json())
    .then(data => {
      alert(data.message || "已加入書櫃！");
      closeShelfSelector();
    })
    .catch(() => {
      alert("加入書櫃失敗！");
      closeShelfSelector();
    });
}

document.getElementById("searchInput").addEventListener("input", () => renderBooks(allBooks));
document.getElementById("sortSelect").addEventListener("change", () => renderBooks(allBooks));

fetch("/book-sharing-system/backend/get_books_group.php")
  .then(res => res.json())
  .then(data => {
    if (data.success) {
      allBooks = data.books_by_category;
      renderBooks(allBooks);
    } else {
      alert("載入書籍失敗！");
    }
  });
</script>
</body>
</html>
