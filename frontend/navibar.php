<?php
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$user_name = $is_logged_in ? htmlspecialchars($_SESSION['user_name']) : null;
?>
<header class="navbar">
  <style>
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 12px 24px;
      background-color: #1f2937;
      color: white;
      font-family: "Noto Sans TC", sans-serif;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo {
      font-size: 1.5em;
      font-weight: bold;
    }

    .nav-group {
      display: flex;
      align-items: center;
      gap: 16px;
    }

    .dropdown {
      position: relative;
    }

    .dropbtn {
      background-color: #374151;
      color: white;
      padding: 10px 14px;
      font-size: 0.95em;
      border: none;
      border-radius: 6px;
      cursor: pointer;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      top: 120%;
      right: 0;
      background-color: white;
      color: #1f2937;
      min-width: 160px;
      box-shadow: 0 8px 16px rgba(0,0,0,0.1);
      border-radius: 6px;
      z-index: 10;
    }

    .dropdown-content a {
      color: #1f2937;
      padding: 12px 16px;
      text-decoration: none;
      display: block;
    }

    .dropdown-content a:hover {
      background-color: #f3f4f6;
      color: #2563eb;
    }

    .dropdown.active .dropdown-content {
      display: block;
    }

    .login-btn, .signup-btn {
      background-color: #2563eb;
      color: white;
      padding: 8px 14px;
      text-decoration: none;
      border-radius: 6px;
    }

    .signup-btn {
      background-color: #10b981;
    }

    .login-btn:hover, .signup-btn:hover {
      opacity: 0.9;
    }
  </style>

  <div class="logo">書櫃分享系統</div>
  <div class="nav-group">
    <!-- ☰ 功能選單 -->
    <div class="dropdown">
      <button class="dropbtn">☰ 功能</button>
      <div class="dropdown-content">
        <a href="/book-sharing-system/frontend/index.php">首頁</a>
        <a href="/book-sharing-system/frontend/book_list.php">書籍清單</a>
        <a href="/book-sharing-system/frontend/create_book_form.php">新增書籍</a>
      </div>
    </div>

    <!-- 登入 / 註冊 OR 使用者選單 -->
    <?php if ($is_logged_in): ?>
      <div class="dropdown">
        <button class="dropbtn">👤 <?= $user_name ?> ▾</button>
        <div class="dropdown-content">
          <div style="padding: 10px 16px; font-weight: bold;"> <?= $user_name ?></div>
          <a href="/book-sharing-system/frontend/bookshelf_list.html">我的書櫃</a>
          <a href="/book-sharing-system/backend/logout.php">登出</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/book-sharing-system/frontend/login.html" class="login-btn">登入</a>
      <a href="/book-sharing-system/frontend/signup.html" class="signup-btn">註冊</a>
    <?php endif; ?>
  </div>
</header>

<!-- JS for click dropdown -->
<script>
  document.querySelectorAll('.dropdown .dropbtn').forEach(button => {
    button.addEventListener('click', function (e) {
      e.stopPropagation();
      const dropdown = this.closest('.dropdown');
      dropdown.classList.toggle('active');

      // 關閉其他選單
      document.querySelectorAll('.dropdown').forEach(d => {
        if (d !== dropdown) d.classList.remove('active');
      });
    });
  });

  // 點空白處關閉所有 dropdown
  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('active'));
  });
</script>
