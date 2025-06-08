<header class="navbar">
  <style>
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 28px;
      background:rgb(224, 224, 224);
      color: #111827;
      font-family: 'Segoe UI', sans-serif;
      border-bottom: 1px solid #e5e7eb;
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo {
      font-size: 1.5em;
      font-weight: 600;
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
      background:rgb(255, 255, 255);
      color: #111827;
      padding: 8px 14px;
      border: 1px solid #d1d5db;
      border-radius: 999px;
      font-size: 0.95em;
      cursor: pointer;
      transition: background 0.2s, border-color 0.2s;
    }

    .dropbtn:hover {
      background: #f3f4f6;
      border-color: #9ca3af;
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 110%;
      background: #ffffff;
      min-width: 180px;
      border-radius: 8px;
      box-shadow: 0 4px 12px rgba(0,0,0,0.08);
      overflow: hidden;
      animation: fadeIn 0.2s ease-in-out;
      z-index: 999;
      border: 1px solid #e5e7eb;
    }

    .dropdown-content a {
      display: block;
      padding: 10px 16px;
      color: #111827;
      text-decoration: none;
      font-size: 0.95em;
      transition: background 0.2s;
    }

    .dropdown-content a:hover {
      background: #f9fafb;
    }

    .dropdown.active .dropdown-content {
      display: block;
    }

    .login-btn, .signup-btn {
      padding: 8px 16px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 500;
      font-size: 0.95em;
      transition: background 0.3s, color 0.3s;
      border: 1px solid #d1d5db;
    }

    .login-btn {
      background:rgb(255, 255, 255);
      color: #111827;
    }

    .signup-btn {
      background:rgb(207, 180, 2);
      color: #ffffff;
    }

    .login-btn:hover {
      background: #f3f4f6;
    }

    .signup-btn:hover {
      background:rgb(241, 147, 147);
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>

  <div class="logo">書櫃分享系統</div>

  <div class="nav-group">
    <div class="dropdown">
      <button class="dropbtn">☰ 功能選單</button>
      <div class="dropdown-content">
        <a href="/book-sharing-system/frontend/index.php">🏠 首頁</a>
        <a href="/book-sharing-system/frontend/book_list.php">📘 書籍清單</a>
        <a href="/book-sharing-system/frontend/create_book_form.php">➕ 新增書籍</a>
      </div>
    </div>

    <?php if ($is_logged_in): ?>
      <div class="dropdown">
        <button class="dropbtn">👤 <?= $user_name ?> ▾</button>
        <div class="dropdown-content">
          <a href="/book-sharing-system/frontend/bookshelf_list.html">📚 我的書櫃</a>
          <a href="/book-sharing-system/backend/logout.php">🚪 登出</a>
        </div>
      </div>
    <?php else: ?>
      <a href="/book-sharing-system/frontend/login.html" class="login-btn">登入</a>
      <a href="/book-sharing-system/frontend/register.html" class="signup-btn">註冊</a>
    <?php endif; ?>
  </div>
</header>

<script>
  document.querySelectorAll('.dropdown .dropbtn').forEach(button => {
    button.addEventListener('click', function (e) {
      e.stopPropagation();
      const dropdown = this.closest('.dropdown');
      dropdown.classList.toggle('active');
      document.querySelectorAll('.dropdown').forEach(d => {
        if (d !== dropdown) d.classList.remove('active');
      });
    });
  });

  document.addEventListener('click', () => {
    document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('active'));
  });
</script>
