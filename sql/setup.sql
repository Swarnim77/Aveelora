CREATE DATABASE IF NOT EXISTS raksi_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE raksi_db;
CREATE TABLE IF NOT EXISTS users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(100),
  email VARCHAR(150) UNIQUE,
  password VARCHAR(255),
  role VARCHAR(20) DEFAULT 'user'
);
CREATE TABLE IF NOT EXISTS products (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(200),
  category VARCHAR(100),
  price DECIMAL(10,2),
  description TEXT,
  image VARCHAR(200) DEFAULT 'placeholder_dark.png'
);
CREATE TABLE IF NOT EXISTS orders (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  items TEXT,
  total_amount DECIMAL(10,2),
  status VARCHAR(50),
  name VARCHAR(200),
  address VARCHAR(300),
  created_at DATETIME
);