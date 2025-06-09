<?php
session_start();
require_once 'db.php';

$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
  header("Location: ../frontend/login.html");
  exit;
}

$name = trim($_POST['name'] ?? '');
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['password'] ?? '');

if ($name === '') {
  header("Location: ../frontend/edit_profile.php?error=暱稱不能為空");
  exit;
}

// 先取出目前使用者資料，包含密碼和avatar
$stmt = $pdo->prepare("SELECT password, avatar FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
  header("Location: ../frontend/edit_profile.php?error=找不到使用者資料");
  exit;
}

// 若要改密碼，必須驗證原密碼
if ($new_password !== '') {
  if (!password_verify($current_password, $user['password'])) {
    header("Location: ../frontend/edit_profile.php?error=若要修改密碼，請輸入正確的目前密碼");
    exit;
  }
  $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
} else {
  $hashed_password = $user['password'];
}

// ---- 新增：頭像圖片上傳處理 ----
$avatar_filename = $user['avatar'] ?? 'default.png';  // 使用原本頭像或預設頭像

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
  if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
    header("Location: ../frontend/edit_profile.php?error=頭像格式只允許 JPG、PNG、GIF");
    exit;
  }

  // 重新命名圖片檔，避免檔名衝突
  $ext = pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION);
  $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;

  $upload_dir = __DIR__ . '/../uploads/avatars/';
  if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
  }
  $destination = $upload_dir . $avatar_filename;

  if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
    header("Location: ../frontend/edit_profile.php?error=頭像上傳失敗");
    exit;
  }

  // 刪除舊頭像（非預設）
  if ($user['avatar'] && $user['avatar'] !== 'default.png' && file_exists($upload_dir . $user['avatar'])) {
    unlink($upload_dir . $user['avatar']);
  }
}
// ---- 頭像上傳結束 ----

// 更新使用者資料 (暱稱、密碼、頭像)
$stmt = $pdo->prepare("UPDATE user SET name = ?, password = ?, avatar = ? WHERE user_id = ?");
$stmt->execute([$name, $hashed_password, $avatar_filename, $user_id]);

// 更新 session 暱稱和頭像
$_SESSION['user_name'] = $name;
$_SESSION['user_avatar'] = $avatar_filename;

header("Location: ../frontend/edit_profile.php?success=資料已更新");
exit;
