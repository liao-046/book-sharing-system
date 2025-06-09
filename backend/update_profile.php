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

// 處理密碼更新
$hashed_password = $user['password'];
if ($new_password !== '') {
    if (!password_verify($current_password, $user['password'])) {
        header("Location: ../frontend/edit_profile.php?error=若要修改密碼，請輸入正確的目前密碼");
        exit;
    }
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
}

// 頭像檔名（預設）
$avatar_filename = $user['avatar'] ?? 'default.png';

// 設定圖片上傳目錄，統一使用 assets/img/
$upload_dir = realpath(__DIR__ . '/../assets/img/');
if (!$upload_dir || !is_dir($upload_dir)) {
    die("錯誤：上傳資料夾不存在，請建立資料夾 assets/img 並設定權限。");
}
if (!is_writable($upload_dir)) {
    die("錯誤：上傳資料夾無寫入權限，請設定資料夾權限為可寫。");
}

// 處理頭像上傳
if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
    $file_type = $_FILES['avatar']['type'];
    if (!in_array($file_type, $allowed_types)) {
        header("Location: ../frontend/edit_profile.php?error=頭像格式只允許 JPG、PNG、GIF");
        exit;
    }

    // 取得副檔名（小寫）
    $ext = strtolower(pathinfo($_FILES['avatar']['name'], PATHINFO_EXTENSION));
    $avatar_filename = 'avatar_' . $user_id . '_' . time() . '.' . $ext;

    $destination = $upload_dir . DIRECTORY_SEPARATOR . $avatar_filename;

    // 移動上傳檔案
    if (!move_uploaded_file($_FILES['avatar']['tmp_name'], $destination)) {
        header("Location: ../frontend/edit_profile.php?error=頭像上傳失敗");
        exit;
    }

    // 刪除舊頭像（非預設）
    $old_avatar = $user['avatar'];
    if ($old_avatar && $old_avatar !== 'default.png') {
        $old_avatar_path = $upload_dir . DIRECTORY_SEPARATOR . $old_avatar;
        if (file_exists($old_avatar_path)) {
            unlink($old_avatar_path);
        }
    }
}

// 更新資料庫
$stmt = $pdo->prepare("UPDATE user SET name = ?, password = ?, avatar = ? WHERE user_id = ?");
if (!$stmt->execute([$name, $hashed_password, $avatar_filename, $user_id])) {
    die("資料庫更新失敗：" . implode(", ", $stmt->errorInfo()));
}

// 更新 session
$_SESSION['user_name'] = $name;
$_SESSION['user_avatar'] = $avatar_filename;

// 成功導回
header("Location: ../frontend/edit_profile.php?success=資料已更新");
exit;
?>
