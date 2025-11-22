-- قاعدة بيانات منصة إبداع للتدريب والتأهيل
-- تاريخ الإنشاء: أكتوبر 2025

-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS ibdaa_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ibdaa_platform;

-- جدول المستخدمين
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('student', 'trainer', 'technical', 'manager') DEFAULT 'student',
    governorate VARCHAR(100),
    district VARCHAR(100),
    birth_date DATE,
    photo_path VARCHAR(255),
    verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(255) UNIQUE NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول محاولات تسجيل الدخول (Rate Limiter)
CREATE TABLE login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    success TINYINT(1) NOT NULL DEFAULT 0,
    INDEX idx_email (email),
    INDEX idx_ip_address (ip_address),
    INDEX idx_attempted_at (attempted_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- جدول طلبات التسجيل في الدورات
CREATE TABLE course_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(150) NOT NULL,
    governorate VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    id_card VARCHAR(255),
    status ENUM('قيد المراجعة', 'مقبول', 'مرفوض', 'تم الدفع') DEFAULT 'قيد المراجعة',
    fees DECIMAL(10,2) DEFAULT 0,
    note TEXT,
    assigned_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_course (course),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- إدراج بيانات تجريبية (اختياري)
-- INSERT INTO users (full_name, email, password_hash, role, verified) 
-- VALUES ('المدير العام', 'admin@ibdaa.com', '$2y$10$example_hash', 'manager', 1);
