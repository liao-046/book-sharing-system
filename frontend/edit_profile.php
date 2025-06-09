<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: login.html");
  exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? '';
$user_avatar = $_SESSION['user_avatar'] ?? 'default.png';

// 頭像完整路徑
$avatar_path = __DIR__ . '/../assets/img/' . $user_avatar;

// 如果頭像檔案不存在，就使用預設頭像
if (!file_exists($avatar_path) || empty($user_avatar)) {
  $user_avatar = 'default.png';
}

$error = $_GET['error'] ?? null;
$success = $_GET['success'] ?? null;
?>
<!DOCTYPE html>
<html lang="zh-Hant">
<head>
  <meta charset="UTF-8" />
  <title>編輯個人資料</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <style>
    body { background-color: #f8f9fa; padding-top: 60px; }
    .container { max-width: 500px; }
    .avatar-img {
      width: 100px;
      height: 100px;
      object-fit: cover;
      border-radius: 50%;
      border: 2px solid #ccc;
      cursor: pointer;
      transition: box-shadow 0.3s ease;
    }
    .avatar-img:hover {
      box-shadow: 0 0 8px 2px #888;
    }
  </style>
</head>
<body>
<div class="container">
  <h2 class="mb-4 d-flex align-items-center">✏️ 編輯個人資料</h2>

  <?php if ($error): ?>
    <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
  <?php elseif ($success): ?>
    <div class="alert alert-success">✅ <?= htmlspecialchars($success) ?></div>
  <?php endif; ?>

  <form method="POST" action="../backend/update_profile.php" enctype="multipart/form-data">
    <div class="mb-3 d-flex flex-column align-items-center">
      <label for="avatar" class="form-label">更新頭像（點擊圖片可選擇）</label>
      <img
        src="../assets/img/<?= htmlspecialchars($user_avatar) ?>?t=<?= time() ?>"
        alt="頭像"
        class="avatar-img"
        id="avatarPreview"
        onclick="document.getElementById('avatar').click()"
      />
      <input type="file" class="form-control d-none" id="avatar" name="avatar" accept="image/*" />
    </div>

    <div class="mb-3">
      <label for="name" class="form-label">暱稱</label>
      <input type="text" class="form-control" id="name" name="name" value="<?= htmlspecialchars($user_name) ?>" required />
    </div>

    <div class="mb-3">
      <label for="current_password" class="form-label">目前密碼（如要更改密碼才需填寫）</label>
      <input type="password" class="form-control" id="current_password" name="current_password" />
    </div>

    <div class="mb-3">
      <label for="password" class="form-label">🆕 新密碼（可留空不變更）</label>
      <input type="password" class="form-control" id="password" name="password" />
    </div>

    <div class="d-flex justify-content-between">
      <a href="index.php" class="btn btn-secondary">← 返回首頁</a>
      <button type="submit" class="btn btn-primary">💾 儲存變更</button>
    </div>
  </form>
</div>

<script>
  // 預覽選擇的圖片
  document.getElementById('avatar').addEventListener('change', function(event) {
    const file = event.target.files[0];
    if (file) {
      const reader = new FileReader();
      reader.onload = function(e) {
        document.getElementById('avatarPreview').src = e.target.result;
      };
      reader.readAsDataURL(file);
    }
  });
</script>
</body>
</html>
