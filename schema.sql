CREATE DATABASE IF NOT EXISTS book_system;
USE book_system;

-- 接下來加上建立資料表的語法
CREATE TABLE User (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
);


