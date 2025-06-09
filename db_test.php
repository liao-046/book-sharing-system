<?php
$host = '127.0.0.1';
$dbname = 'book_system';
$user = 'admin';
$pass = 'Admin1234!';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass);
    echo "✅ 成功連接資料庫";
} catch (PDOException $e) {
    echo "❌ 錯誤：" . $e->getMessage();
}