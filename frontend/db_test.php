<?php
$host = '140.122.184.128';   // 遠端主機 IP
$port = '3306';              // MySQL 連接埠
$dbname = 'team19';       // 資料庫名稱（假設叫 bookshare）
$username = 'team19';        // 使用者帳號
$password = 'Wy$Kq83Nbm';         // 替換為實際密碼

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4",
        $username,
        $password,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    die("資料庫連線失敗：" . $e->getMessage());
}
?>
