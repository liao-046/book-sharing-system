<!-- frontend/reset_password.php -->
<!DOCTYPE html>
<html lang="zh-TW">
<head>
    <meta charset="UTF-8">
    <title>重設密碼</title>
</head>
<body>
    <h2>請輸入新密碼</h2>

    <form action="../backend/do_reset_password.php" method="POST">
        <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token'] ?? ''); ?>">

        <label>新密碼：</label><br>
        <input type="password" name="new_password" required><br><br>

        <label>再次輸入密碼：</label><br>
        <input type="password" name="confirm_password" required><br><br>

        <button type="submit">提交</button>
    </form>
</body>
</html>
