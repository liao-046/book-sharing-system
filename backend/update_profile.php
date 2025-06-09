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

// 若要改密碼，必須驗證原密碼
if ($new_password !== '') {
  // 撈出密碼 hash
  $stmt = $pdo->prepare("SELECT password FROM user WHERE user_id = ?");
  $stmt->execute([$user_id]);
  $user = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$user || !password_verify($current_password, $user['password'])) {
    header("Location: ../frontend/edit_profile.php?error=若要修改密碼，請輸入正確的目前密碼");
    exit;
  }

  // 密碼驗證通過 → 更新暱稱和密碼
  $hashed = password_hash($new_password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE user SET name = ?, password = ? WHERE user_id = ?");
  $stmt->execute([$name, $hashed, $user_id]);
} else {
  // 僅更新暱稱
  $stmt = $pdo->prepare("UPDATE user SET name = ? WHERE user_id = ?");
  $stmt->execute([$name, $user_id]);
}

// 更新 session 暱稱
$_SESSION['user_name'] = $name;

header("Location: ../frontend/edit_profile.php?success=資料已更新");
exit;
