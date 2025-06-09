CREATE DATABASE IF NOT EXISTS book_system;
USE book_system;

-- 使用者表
CREATE TABLE user (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    email VARCHAR(100) UNIQUE,
    password VARCHAR(255),
    is_admin BOOLEAN NOT NULL DEFAULT 0,
    avatar VARCHAR(255) DEFAULT 'default.png'
);

-- 書籍表
CREATE TABLE book (
    book_id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    publisher VARCHAR(100),
    category VARCHAR(50),
    cover_url VARCHAR(255),
    description TEXT
);

-- 作者表
CREATE TABLE author (
    author_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL
);

-- 書籍與作者關聯表
CREATE TABLE book_author (
    book_id INT,
    author_id INT,
    PRIMARY KEY (book_id, author_id),
    FOREIGN KEY (book_id) REFERENCES book(book_id) ON DELETE CASCADE,
    FOREIGN KEY (author_id) REFERENCES author(author_id) ON DELETE CASCADE
);

-- 書櫃表
CREATE TABLE book_shelf (
    shelf_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50),
    user_id INT,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 書評表
CREATE TABLE review (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    book_id INT,
    rating INT CHECK(rating BETWEEN 1 AND 5),
    comment TEXT,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (book_id) REFERENCES book(book_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 靜默分享表
CREATE TABLE silent_share (
    silent_share_id INT AUTO_INCREMENT PRIMARY KEY,
    message TEXT,
    create_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    unlock_condition VARCHAR(255),
    is_open BOOLEAN DEFAULT FALSE,
    open_time DATETIME
);

-- 分享書籍關聯表
CREATE TABLE share_book (
    silent_share_id INT,
    book_id INT,
    PRIMARY KEY (silent_share_id, book_id),
    FOREIGN KEY (silent_share_id) REFERENCES silent_share(silent_share_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES book(book_id) ON DELETE CASCADE
);

-- 使用者收到分享關聯表
CREATE TABLE receives (
    user_id INT,
    silent_share_id INT,
    PRIMARY KEY (user_id, silent_share_id),
    FOREIGN KEY (silent_share_id) REFERENCES silent_share(silent_share_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES user(user_id) ON DELETE CASCADE
);

-- 書櫃紀錄表
CREATE TABLE bookshelf_record (
    shelf_id INT,
    book_id INT,
    add_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (shelf_id, book_id),
    FOREIGN KEY (shelf_id) REFERENCES book_shelf(shelf_id) ON DELETE CASCADE,
    FOREIGN KEY (book_id) REFERENCES book(book_id) ON DELETE CASCADE
);

-- 密碼重設用 token 表
CREATE TABLE password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100),
    token VARCHAR(100) UNIQUE,
    expires_at DATETIME,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);