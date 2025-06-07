<?php
session_start();

// 清除所有 session 資料
$_SESSION = [];
session_unset();
session_destroy();

// 如果你希望用 JS AJAX 呼叫，可以回傳 JSON：
header('Content-Type: application/json');
echo json_encode(['success' => true, 'message' => '已成功登出']);

// 導回登入頁
header("Location: /book-sharing-system/frontend/login.html");
exit;
