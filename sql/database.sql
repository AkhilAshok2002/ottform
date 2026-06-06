-- Create database
CREATE DATABASE IF NOT EXISTS ott_platform;
USE ott_platform;

-- Users table
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) NULL,
    role ENUM('user', 'admin') DEFAULT 'user',
    subscription_status ENUM('free', 'basic', 'premium') DEFAULT 'free',
    subscription_expiry DATE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Categories table
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    type ENUM('movie', 'series') DEFAULT 'movie',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Videos table
CREATE TABLE videos (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    description TEXT,
    category_id INT,
    thumbnail_path VARCHAR(255),
    video_path VARCHAR(255),
    duration VARCHAR(20),
    release_year YEAR,
    rating DECIMAL(2,1) DEFAULT 0,
    views INT DEFAULT 0,
    type ENUM('movie', 'series') DEFAULT 'movie',
    featured BOOLEAN DEFAULT FALSE,
    access_level ENUM('free', 'premium') DEFAULT 'free',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
);

-- Watch history table
CREATE TABLE watch_history (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    watch_time INT DEFAULT 0,
    last_watched TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_video (user_id, video_id)
);

-- User favorites/watchlist
CREATE TABLE user_favorites (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    video_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (video_id) REFERENCES videos(id) ON DELETE CASCADE,
    UNIQUE KEY unique_favorite (user_id, video_id)
);

-- Subscription plans
CREATE TABLE subscription_plans (
    id INT PRIMARY KEY AUTO_INCREMENT,
    plan_name VARCHAR(50) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    duration_days INT NOT NULL,
    max_quality VARCHAR(20),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Payments table
CREATE TABLE payments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    plan_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expiry_date DATE NOT NULL,
    status ENUM('pending', 'completed', 'failed') DEFAULT 'pending',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES subscription_plans(id)
);

-- Admin logs
CREATE TABLE admin_logs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    admin_id INT NOT NULL,
    action VARCHAR(255) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Insert default categories
INSERT INTO categories (name, type) VALUES
('Action', 'movie'),
('Comedy', 'movie'),
('Drama', 'movie'),
('Sci-Fi', 'movie'),
('Horror', 'movie'),
('Romance', 'movie'),
('Documentary', 'movie'),
('Action Series', 'series'),
('Comedy Series', 'series'),
('Drama Series', 'series');

-- Insert subscription plans
INSERT INTO subscription_plans (plan_name, price, duration_days, max_quality) VALUES
('Free', 0.00, 30, '480p'),
('Basic', 9.99, 30, '720p'),
('Premium', 14.99, 30, '1080p');

-- Create admin user (password: admin123)
INSERT INTO users (name, email, password, role, subscription_status) VALUES
('Admin', 'admin@ott.com', '$2y$10$jYDiutpajwvRPjavffXbVugffjwINhEua/lGu//OE7.iwBtW7Qwli', 'admin', 'premium');