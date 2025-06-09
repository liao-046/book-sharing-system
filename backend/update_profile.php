<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['user_id'])) {
  header("Location: ../frontend/login.html");
  exit;
}

$user_id = $_SESSION['user_id'];
$name = trim($_POST['name'] ?? '');
$current_password = trim($_POST['current_password'] ?? '');
$new_password = trim($_POST['password'] ?? '');

// 驗證暱稱
if ($name === '') {
  header("Location: ../frontend/edit_profile.php?error=暱稱不能為空");
  exit;
}

// 查詢使用者資料
$stmt = $pdo->prepare("SELECT password, avatar FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$user) {
  header("Location: ../frontend/edit_profile.php?error=找不到使用者資料");
  exit;
}

// 密碼變更
$hashed_password = $user['password'];
if ($new_password !== '') {
  if (!password_verify($current_password, $user['password'])) {
    header("Location: ../frontend/edit_profile.php?error=若要修改密碼，請輸入正確的目前密碼");
    exit;
  }
  $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
}

// 處理頭像
$avatar_filename = $user['avatar'] ?? 'default.png';
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
  $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
  if (!in_array($_FILES['avatar']['type'], $allowed_types)) {
    header("Location: ../frontend/edit_profile.php?error=頭像格式只允許 JPG、PNG、GIF");
    exit;
  }

  $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
  $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;

  $upload_dir = realpath(__DIR__ . '/../assets/img/') . '/';
  $destination = $upload_dir . $avatar_filename;

  if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
    header("Location: ../frontend/edit_profile.php?error=頭像上傳失敗");
    exit;
  }

  // 刪除舊頭像（非預設）
  $old_avatar = $user['avatar'];
  if ($old_avatar && $old_avatar !== 'default.png' && file_exists($upload_dir . $old_avatar)) {
    unlink($upload_dir . $old_avatar);
  }
}

// 更新資料
$stmt = $pdo->prepare("UPDATE user SET name = ?, password = ?, avatar = ? WHERE user_id = ?");
$stmt->execute([$name, $hashed_password, $avatar_filename, $user_id]);

// 更新 SESSION
$_SESSION['user_name'] = $name;
$_SESSION['user_avatar'] = $avatar_filename;

header("Location: ../frontend/edit_profile.php?success=資料已更新");
exit;
