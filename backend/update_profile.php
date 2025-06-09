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

if ($name === '' || $current_password === '') {
  header("Location: ../frontend/edit_profile.php?error=名稱與目前密碼皆為必填");
  exit;
}

// 撈出目前密碼 hash
$stmt = $pdo->prepare("SELECT password FROM user WHERE user_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($current_password, $user['password'])) {
  header("Location: ../frontend/edit_profile.php?error=目前密碼錯誤");
  exit;
}

// 執行更新
if ($new_password !== '') {
  $hashed = password_hash($new_password, PASSWORD_DEFAULT);
  $stmt = $pdo->prepare("UPDATE user SET name = ?, password = ? WHERE user_id = ?");
  $stmt->execute([$name, $hashed, $user_id]);
} else {
  $stmt = $pdo->prepare("UPDATE user SET name = ? WHERE user_id = ?");
  $stmt->execute([$name, $user_id]);
}

// 更新 session
$_SESSION['user_name'] = $name;

header("Location: ../frontend/edit_profile.php?success=個人資料已更新");
exit;
