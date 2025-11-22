<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/anti_detection.php';
require_once __DIR__ . '/../database/db.php';

/** @var mysqli $conn */

// إخفاء معلومات السيرفر
AntiDetection::hideServerHeaders();

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

// حماية إضافية لصفحة المدير
if (AntiDetection::detectBot() || AntiDetection::detectFingerprinting()) {
    AntiDetection::logSuspiciousActivity('admin_access_attempt');
    AntiDetection::sendDecoyResponse();
}

// إنشاء Rate Limiter
$rateLimiter = new RateLimiter($conn, 5, 15, 30);

$error = '';
$success = false;
$warningMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // فحص الحماية الشاملة
    $protectionCheck = AntiDetection::validateFullProtection();
    
    if (!$protectionCheck['valid']) {
        $error = AntiDetection::getGenericError('login');
        AntiDetection::logSuspiciousActivity('admin_protection_failed', $protectionCheck['errors']);
    }
    // التحقق من CSRF Token
    elseif (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = AntiDetection::getGenericError('access');
        AntiDetection::addRandomDelay(500, 1000);
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (empty($email) || empty($password)) {
            $error = 'الرجاء إدخال البريد الإلكتروني وكلمة المرور.';
        } else {
            // التحقق من Rate Limiting
            $rateStatus = $rateLimiter->checkAttempts($email);
            
            if (!$rateStatus['allowed']) {
                $error = $rateLimiter->getErrorMessage($rateStatus);
            } else {
                // عرض رسالة تحذير إذا اقترب من الحد
                if ($rateStatus['remaining'] <= 2) {
                    $warningMessage = $rateLimiter->getErrorMessage($rateStatus);
                }
                
                $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role, photo FROM users WHERE email = ? LIMIT 1");
                if ($stmt) {
                    $stmt->bind_param('s', $email);
                    $stmt->execute();
                    $res = $stmt->get_result();
                    
                    if ($res && $res->num_rows === 1) {
                        $user = $res->fetch_assoc();
                        
                        if (password_verify($password, $user['password_hash'])) {
                            // 1. تجديد الجلسة (أمان)
                            session_regenerate_id(true);
                            
                            // 2. حفظ بيانات المستخدم
                            $_SESSION['user_id'] = $user['id'];
                            $_SESSION['user_role'] = $user['role'];
                            $_SESSION['email'] = $user['email'];

                            // 3. التوجيه إلى الراوتر (وليس للصفحة النهائية مباشرة)
                            header("Location: dashboard_router.php");
                            exit();
                        } else {
                            // رسالة موحدة
                            $error = AntiDetection::getGenericError('login');
                            $rateLimiter->logAttempt($email, false);
                            AntiDetection::addProgressiveDelay($rateStatus['attempts'] + 1);
                        }
                    } else {
                        // نفس الرسالة الموحدة
                        $error = AntiDetection::getGenericError('login');
                        $rateLimiter->logAttempt($email, false);
                        AntiDetection::addProgressiveDelay($rateStatus['attempts'] + 1);
                    }
                    $stmt->close();
                } else {
                    $error = 'خطأ داخلي في السيرفر.';
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل دخول - بوابة الإدارة</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
    <link rel="stylesheet" href="../platform/css/chatbot.css">
</head>
<body class="min-h-screen bg-sky-50 flex items-center justify-center p-6">
    <div class="max-w-md w-full bg-white rounded-2xl shadow-xl p-8">
        <!-- Logo -->
        <div class="text-center mb-6">
            <img src="../platform/photos/Sh.jpg" alt="شعار منصة إبداع" class="mx-auto mb-3 w-20 h-20 rounded-full border-4 border-sky-500 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-800">تسجيل دخول المنصة</h1>
            <p class="text-sm text-slate-500 mt-1">قم بتسجيل الدخول للوصول إلى لوحة التحكم المناسبة لدورك</p>
        </div>

        <!-- Warning Message -->
        <?php if ($warningMessage): ?>
        <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 text-yellow-800 rounded-lg text-sm">
            <?php echo htmlspecialchars($warningMessage); ?>
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error): ?>
        <div class="mb-4 p-3 bg-red-50 border border-red-200 text-red-700 rounded-lg text-sm">
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <!-- Login Form -->
        <form method="POST" action="" class="space-y-4">
            <?php echo CSRF::getTokenField(); ?>
            <?php echo AntiDetection::getProtectedFormFields(); ?>
            <div>
                <label for="email" class="block text-sm font-medium text-slate-700 mb-1">
                    البريد الإلكتروني
                </label>
                <input 
                    type="text" 
                    id="email" 
                    name="email" 
                    required
                    value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                    placeholder="أدخل بريدك الإلكتروني"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition"
                >
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700 mb-1">
                    كلمة المرور
                </label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    required
                    placeholder="••••••••"
                    class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-400 focus:border-sky-400 transition"
                >
            </div>

            <button 
                type="submit"
                class="w-full bg-sky-500 hover:bg-sky-600 text-white font-semibold py-2.5 rounded-lg transition shadow-sm hover:shadow"
            >
                تسجيل الدخول
            </button>
        </form>

        <!-- Student Signup Link -->
        <div class="mt-4 text-center text-sm text-slate-600">
            <p>
                للطلاب: يمكنكم التسجيل عبر 
                <a href="../platform/unified_registration.php" class="text-sky-600 hover:text-sky-700 font-medium underline">
                    بوابة التسجيل الموحد
                </a>
            </p>
        </div>
    </div>
    <script src="../platform/js/chatbot.js"></script>
</body>
</html>
