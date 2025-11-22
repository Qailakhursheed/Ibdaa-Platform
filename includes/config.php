<?php
/**
 * Configuration File
 * ملف الإعدادات
 * 
 * يستخدم متغيرات البيئة من ملف .env
 * Uses environment variables from .env file
 * 
 * ⚠️ لا ترفع هذا الملف إلى Git!
 * ⚠️ Do not commit this file to Git!
 */

// تحميل متغيرات البيئة
require_once __DIR__ . '/env_loader.php';

// Gemini API Key
define('GEMINI_API_KEY', EnvLoader::get('GEMINI_API_KEY', ''));

// إعدادات SMTP
return [
    'smtp' => [
        'host' => EnvLoader::get('SMTP_HOST', 'smtp.gmail.com'),
        'port' => (int)EnvLoader::get('SMTP_PORT', 587),
        'username' => EnvLoader::get('SMTP_USER', ''),
        'password' => EnvLoader::get('SMTP_PASS', ''),
        'from_email' => EnvLoader::get('SMTP_FROM_EMAIL', ''),
        'from_name' => EnvLoader::get('SMTP_FROM_NAME', 'منصة إبداع للتدريب والتأهيل')
    ],
    
    'database' => [
        'host' => EnvLoader::get('DB_HOST', 'localhost'),
        'name' => EnvLoader::get('DB_NAME', 'ibdaa_platform'),
        'user' => EnvLoader::get('DB_USER', 'root'),
        'pass' => EnvLoader::get('DB_PASS', '')
    ],
    
    'app' => [
        'env' => EnvLoader::get('APP_ENV', 'development'),
        'debug' => EnvLoader::get('APP_DEBUG', true),
        'url' => EnvLoader::get('APP_URL', 'http://localhost/Ibdaa-Taiz')
    ],
    
    'security' => [
        'session_lifetime' => (int)EnvLoader::get('SESSION_LIFETIME', 7200),
        'csrf_token_expire' => (int)EnvLoader::get('CSRF_TOKEN_EXPIRE', 3600),
        'max_login_attempts' => (int)EnvLoader::get('MAX_LOGIN_ATTEMPTS', 5),
        'login_lockout_time' => (int)EnvLoader::get('LOGIN_LOCKOUT_TIME', 900)
    ]
];