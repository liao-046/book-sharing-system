CREATE DATABASE IF NOT EXISTS book_system;
USE book_system;

-- 接下來加上建立資料表的語法
CREATE TABLE user(
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255)
);

CREATE TABLE book(
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL, 
    author VARCHAR(100),
    publisher VARCHAR(100),
    category VARCHAR(50),
    cover_url VARCHAR(255),
    description TEXT
);