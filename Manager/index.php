<?php
/**
 * Manager Entry Point
 * نقطة الدخول لنظام الإدارة
 */

require_once __DIR__ . '/../includes/session_security.php';
SessionSecurity::startSecureSession();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    // غير مسجل دخول - التوجيه لصفحة تسجيل الدخول
    header('Location: login.php');
    exit;
}

// مسجل دخول بالفعل - التوجيه للوحة التحكم المناسبة
header('Location: dashboard_router.php');
exit;
