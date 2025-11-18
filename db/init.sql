-- 建立資料庫（docker-compose 裡已經用 MYSQL_DATABASE 建立 test，可再保險一次）
CREATE DATABASE IF NOT EXISTS test;
USE test;

-- 建 adduser table
CREATE TABLE IF NOT EXISTS adduser (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL,
    password VARCHAR(255) NOT NULL
);
