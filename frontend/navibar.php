<header class="navbar">
  <style>
    .navbar {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 14px 28px;
      background: linear-gradient(90deg, #3b82f6, #6366f1);
      color: white;
      font-family: 'Segoe UI', sans-serif;
      box-shadow: 0 2px 8px rgba(0,0,0,0.1);
      position: sticky;
      top: 0;
      z-index: 1000;
    }

    .logo {
      font-size: 1.6em;
      font-weight: bold;
    }

    .nav-group {
      display: flex;
      align-items: center;
      gap: 20px;
    }

    .dropdown {
      position: relative;
    }

    .dropbtn {
      background: rgba(255,255,255,0.15);
      color: white;
      padding: 10px 16px;
      border: none;
      border-radius: 999px;
      font-size: 0.95em;
      cursor: pointer;
      transition: background 0.3s;
    }

    .dropbtn:hover {
      background: rgba(255,255,255,0.3);
    }

    .dropdown-content {
      display: none;
      position: absolute;
      right: 0;
      top: 110%;
      background: white;
      min-width: 180px;
      border-radius: 10px;
      box-shadow: 0 6px 16px rgba(0,0,0,0.1);
      overflow: hidden;
      animation: fadeIn 0.2s ease-in-out;
      z-index: 999;
    }

    .dropdown-content a {
      display: block;
      padding: 12px 18px;
      color: #1f2937;
      text-decoration: none;
      transition: background 0.2s;
    }

    .dropdown-content a:hover {
      background: #f3f4f6;
      color: #2563eb;
    }

    .dropdown.active .dropdown-content {
      display: block;
    }

    .login-btn, .signup-btn {
      padding: 8px 16px;
      border-radius: 999px;
      text-decoration: none;
      font-weight: 500;
      transition: background 0.3s;
    }

    .login-btn {
      background: white;
      color: #3b82f6;
    }

    .signup-btn {
      background: #10b981;
      color: white;
    }

    .login-btn:hover {
      background: #e5e7eb;
    }

    .signup-btn:hover {
      background: #059669;
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
      <a href="/book-sharing-system/frontend/signup.html" class="signup-btn">註冊</a>
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
