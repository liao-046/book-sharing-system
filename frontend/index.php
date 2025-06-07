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
    header h1 { display: inline; }
    header .actions { float: right; }
    .container { padding: 20px; }
    .category-block { margin-bottom: 40px; }
    .category-title { font-size: 1.5em; border-bottom: 2px solid #ccc; margin-bottom: 10px; }
    .book-grid { display: flex; flex-wrap: wrap; gap: 15px; }
    .book-card { background: white; border: 1px solid #ddd; border-radius: 8px; width: 180px; padding: 10px; box-shadow: 1px 1px 5px rgba(0,0,0,0.1); }
    .book-card img { width: 100%; height: 240px; object-fit: cover; }
    .book-card h3 { font-size: 1em; margin: 5px 0; }
    .book-card p { font-size: 0.9em; margin: 2px 0; }
    .book-card button { margin-top: 5px; width: 100%; }
    .search-sort { display: flex; justify-content: space-between; margin-bottom: 20px; }
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
    <?php if ($is_logged_in): ?>
      <a href="/book-sharing-system/backend/logout.php" style="color:white; margin-right: 20px;">登出</a>
      <button onclick="location.href='/book-sharing-system/frontend/bookshelf_list.html'" style="
        background-color: #4CAF50;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
      ">我的書櫃</button>
    <?php else: ?>
      <button onclick="location.href='/book-sharing-system/frontend/login.html'" style="
        background-color: #2196F3;
        color: white;
        border: none;
        padding: 5px 10px;
        border-radius: 5px;
        cursor: pointer;
      ">登入</button>
    <?php endif; ?>
  </div>
</header>

<div class="container">
  <div class="search-sort">
    <input type="text" id="searchInput" placeholder="搜尋書名或作者" style="width: 60%; padding: 5px;">
    <select id="sortSelect" style="padding: 5px;">
      <option value="new">最新上架</option>
      <option value="rating">評分高到低</option>
    </select>
  </div>

  <div id="bookContainer"></div>
</div>

<script>
let allBooks = {};
const isLoggedIn = <?= $is_logged_in ? 'true' : 'false' ?>;

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
        <button onclick="addToShelf(${book.book_id})">加入書架</button>
      `;
      grid.appendChild(card);
    });

    container.appendChild(block);
  });
}

function addToShelf(bookId) {
  if (!isLoggedIn) {
    alert("請先登入才能加入書架！");
    window.location.href = "/book-sharing-system/frontend/login.html";
    return;
  }

  fetch("/book-sharing-system/backend/add_book_to_shelf.php", {
    method: "POST",
    headers: {'Content-Type': 'application/x-www-form-urlencoded'},
    body: `book_id=${bookId}`
  })
  .then(res => res.json())
  .then(data => alert(data.message || '已加入書架'))
  .catch(err => alert('加入失敗'));
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
