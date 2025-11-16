<?php
/**
 * Dashboard Router - موجه لوحات التحكم
 * يوجه كل دور إلى لوحة التحكم المناسبة
 * 
 * الأدوار المدعومة:
 * - manager: المدير العام
 * - technical: المشرف الفني
 * - trainer: المدرب
 * - student: الطالب
 */

session_start();

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// الحصول على دور المستخدم
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

// التحقق من وجود الدور
if (!$userRole) {
    session_destroy();
    header('Location: login.php?error=invalid_role');
    exit;
}

// التوجيه حسب الدور
switch ($userRole) {
    case 'manager':
        require_once __DIR__ . '/dashboards/manager-dashboard.php';
        break;
    
    case 'technical':
        require_once __DIR__ . '/dashboards/technical-dashboard.php';
        break;
    
    case 'trainer':
        require_once __DIR__ . '/dashboards/trainer-dashboard.php';
        break;
    
    case 'student':
        require_once __DIR__ . '/dashboards/student-dashboard.php';
        break;
    
    default:
        // دور غير معروف
        session_destroy();
        header('Location: login.php?error=unknown_role');
        exit;
}
?>
