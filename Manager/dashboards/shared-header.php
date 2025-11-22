<?php
/**
 * Shared Dashboard Resources
 * موارد مشتركة لجميع لوحات التحكم
 */

require_once __DIR__ . '/../../includes/session_security.php';
SessionSecurity::startSecureSession();

// حساب المسارات الأساسية للروابط (يدعم بيئات التطوير والإنتاج)
$docRootPath = isset($_SERVER['DOCUMENT_ROOT']) && $_SERVER['DOCUMENT_ROOT']
    ? realpath($_SERVER['DOCUMENT_ROOT'])
    : realpath(__DIR__ . '/../../..');
$docRootPath = $docRootPath ? rtrim(str_replace('\\', '/', $docRootPath), '/') : '';

$projectRootPath = rtrim(str_replace('\\', '/', realpath(__DIR__ . '/../../')), '/');
$projectBaseUrl = $docRootPath && strpos($projectRootPath, $docRootPath) === 0
    ? substr($projectRootPath, strlen($docRootPath))
    : '';
$projectBaseUrl = '/' . ltrim($projectBaseUrl, '/');
if ($projectBaseUrl === '/') {
    $projectBaseUrl = '';
}

$managerBaseUrl = '/' . ltrim(($projectBaseUrl ? $projectBaseUrl . '/Manager' : 'Manager'), '/');
$platformBaseUrl = '/' . ltrim(($projectBaseUrl ? $projectBaseUrl . '/platform' : 'platform'), '/');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    $redirectTarget = $_SERVER['REQUEST_URI'] ?? '';
    header('Location: ' . $managerBaseUrl . '/login.php?redirect=' . urlencode($redirectTarget));
    exit;
}

// الاتصال بقاعدة البيانات
require_once __DIR__ . '/../../database/db.php';

// بيانات المستخدم الأساسية
$userId = (int) ($_SESSION['user_id'] ?? 0);
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$userName = $_SESSION['user_name'] ?? ($_SESSION['full_name'] ?? 'مستخدم المنصة');
$userEmail = $_SESSION['user_email'] ?? '';
$userPhoto = $_SESSION['user_photo'] ?? null;

// أسماء الأدوار بالعربية
$roleNames = [
    'manager' => 'المدير العام',
    'technical' => 'المشرف الفني',
    'trainer' => 'المدرب',
    'student' => 'الطالب'
];
$currentRoleLabel = $roleNames[$userRole] ?? 'مستخدم';

// دالة مساعدة: التحقق من الصلاحية
function hasPermission($allowedRoles) {
    global $userRole;
    if (!$allowedRoles) return true;
    $roles = is_array($allowedRoles) ? $allowedRoles : explode(',', $allowedRoles);
    return in_array($userRole, array_map('trim', $roles));
}

// دالة مساعدة: تنسيق التاريخ
function formatDate($date) {
    if (!$date) return '-';
    return date('Y-m-d', strtotime($date));
}

// دالة مساعدة: تنسيق المبلغ
function formatMoney($amount) {
    return number_format($amount, 2) . ' ريال';
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
    <title><?php echo $currentRoleLabel; ?> - منصة إبداع</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.4/dist/chart.umd.min.js"></script>
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Custom CSS -->
    <!-- Chatbot widget styles (Manager area) -->
    <link rel="stylesheet" href="<?php echo $platformBaseUrl; ?>/css/chatbot.css">
    <script defer src="<?php echo $platformBaseUrl; ?>/js/chatbot.js"></script>
    <script>
        window.MANAGER_BASE_URL = '<?php echo $managerBaseUrl; ?>';
        window.PLATFORM_BASE_URL = '<?php echo $platformBaseUrl; ?>';
    </script>
    
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background-color: #f1f5f9;
        }
        .sidebar-link { 
            transition: background-color 0.2s ease, color 0.2s ease; 
        }
        .sidebar-link.active { 
            background-color: rgba(14,165,233,0.15); 
            color: #0284c7; 
            font-weight: 600; 
        }
    </style>
</head>
<body class="min-h-screen text-slate-800">
