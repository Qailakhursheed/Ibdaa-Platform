<?php
/**
 * Unified Login Portal - بوابة الدخول الموحدة
 * نقطة دخول واحدة لجميع المستخدمين (طلاب، مدربين، فنيين، مدير)
 * 
 * Security Features:
 * - Rate Limiting (5 محاولات / 15 دقيقة)
 * - CSRF Protection
 * - Session Security
 * - Anti-Bot Detection
 * - IP Tracking & Blocking
 * - Audit Logging
 * 
 * @version 2.0 - Unified Portal Edition
 * @security HIGH
 */

// Load security modules
require_once __DIR__ . '/includes/session_security.php';
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/rate_limiter.php';
require_once __DIR__ . '/includes/anti_detection.php';
require_once __DIR__ . '/includes/db.php';

// Start secure session
SessionSecurity::startSecureSession();

// Check if already logged in - redirect to appropriate dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['user_role'])) {
    redirectToDashboard($_SESSION['user_role']);
    exit;
}

// Generate CSRF token for form
$csrfToken = CSRFProtection::generateToken();

// Initialize error message
$error = '';
$success = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // CSRF validation
        if (!CSRFProtection::validateToken($_POST['csrf_token'] ?? '')) {
            throw new Exception('Invalid security token. Please refresh the page.');
        }
        
        // Anti-bot detection
        AntiDetection::detectBot();
        
        // Rate limiting
        $rateLimiter = new RateLimiter($conn, 5, 15, 30); // 5 attempts per 15 minutes, 30 min block
        $identifier = $_POST['username'] ?? $_SERVER['REMOTE_ADDR'];
        
        if (!$rateLimiter->checkLimit($identifier)) {
            throw new Exception('تم تجاوز عدد المحاولات المسموحة. الرجاء المحاولة بعد 30 دقيقة.');
        }
        
        // Get and sanitize input
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            throw new Exception('الرجاء إدخال اسم المستخدم وكلمة المرور');
        }
        
        // Check user credentials
        $stmt = $conn->prepare("
            SELECT user_id, username, password, full_name, email, role, status 
            FROM users 
            WHERE (username = ? OR email = ?) 
            LIMIT 1
        ");
        $stmt->bind_param("ss", $username, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            // Log failed attempt
            logLoginAttempt($conn, $username, 'failed', 'User not found');
            throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
        }
        
        $user = $result->fetch_assoc();
        
        // Check account status
        if ($user['status'] !== 'active') {
            logLoginAttempt($conn, $username, 'blocked', 'Account inactive');
            throw new Exception('الحساب غير نشط. الرجاء التواصل مع الإدارة.');
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            logLoginAttempt($conn, $username, 'failed', 'Wrong password');
            throw new Exception('اسم المستخدم أو كلمة المرور غير صحيحة');
        }
        
        // SUCCESS - Login successful
        
        // Regenerate session ID for security
        session_regenerate_id(true);
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        
        // Update last login
        $updateStmt = $conn->prepare("
            UPDATE users 
            SET last_login = NOW(), 
                login_count = login_count + 1 
            WHERE user_id = ?
        ");
        $updateStmt->bind_param("i", $user['user_id']);
        $updateStmt->execute();
        
        // Log successful login
        logLoginAttempt($conn, $username, 'success', 'Login successful', $user['user_id']);
        
        // Create activity log
        logActivity($conn, $user['user_id'], 'login', 'تسجيل دخول ناجح', [
            'ip' => $_SERVER['REMOTE_ADDR'],
            'browser' => $_SERVER['HTTP_USER_AGENT'],
            'role' => $user['role']
        ]);
        
        // Redirect to appropriate dashboard
        redirectToDashboard($user['role']);
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        error_log("Login Error: " . $e->getMessage());
    }
}

/**
 * Redirect user to appropriate dashboard based on role
 */
function redirectToDashboard($role) {
    switch ($role) {
        case 'manager':
            header('Location: /Ibdaa-Taiz/Manager/dashboards/manager-dashboard.php');
            break;
            
        case 'technical':
            header('Location: /Ibdaa-Taiz/Manager/dashboards/technical-dashboard.php');
            break;
            
        case 'trainer':
            header('Location: /Ibdaa-Taiz/Manager/dashboards/trainer-dashboard.php');
            break;
            
        case 'student':
            header('Location: /Ibdaa-Taiz/platform/dashboard.php');
            break;
            
        default:
            header('Location: /Ibdaa-Taiz/');
            break;
    }
}

/**
 * Log login attempt
 */
function logLoginAttempt($conn, $username, $status, $notes = '', $userId = null) {
    $stmt = $conn->prepare("
        INSERT INTO login_attempts 
        (user_id, username, ip_address, user_agent, status, notes, attempt_time)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    $stmt->bind_param("isssss", $userId, $username, $ip, $userAgent, $status, $notes);
    $stmt->execute();
}

/**
 * Log user activity
 */
function logActivity($conn, $userId, $activityType, $description, $metadata = []) {
    $metadataJson = json_encode($metadata);
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, activity_type, description, metadata, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $userId, $activityType, $description, $metadataJson);
    $stmt->execute();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تسجيل الدخول - منصة إبداع للتدريب</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="icon" href="platform/photos/Sh.jpg" type="image/jpeg">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
        .input-field:focus {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-20px); }
        }
        .floating {
            animation: float 3s ease-in-out infinite;
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <!-- Background Decorations -->
    <div class="fixed inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-20 left-20 w-64 h-64 bg-white opacity-10 rounded-full blur-3xl"></div>
        <div class="absolute bottom-20 right-20 w-96 h-96 bg-white opacity-10 rounded-full blur-3xl"></div>
    </div>

    <!-- Login Container -->
    <div class="w-full max-w-md relative z-10">
        <!-- Logo & Title -->
        <div class="text-center mb-8 floating">
            <img src="platform/photos/Sh.jpg" alt="شعار منصة إبداع" 
                 class="w-32 h-32 mx-auto rounded-full shadow-2xl border-4 border-white mb-4 object-cover">
            <h1 class="text-4xl font-bold text-white mb-2">منصة إبداع للتدريب</h1>
            <p class="text-white text-opacity-90 text-lg">بوابة الدخول الموحدة</p>
        </div>

        <!-- Login Card -->
        <div class="login-card rounded-3xl shadow-2xl p-8">
            <div class="text-center mb-8">
                <h2 class="text-3xl font-bold text-slate-800 mb-2">تسجيل الدخول</h2>
                <p class="text-slate-600">للوصول إلى لوحة التحكم الخاصة بك</p>
            </div>

            <!-- Error Message -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 border-r-4 border-red-500 rounded-lg">
                <div class="flex items-center gap-3">
                    <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                    <p class="text-red-800 font-semibold"><?php echo htmlspecialchars($error); ?></p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Login Form -->
            <form method="POST" action="" class="space-y-6">
                <!-- CSRF Token -->
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrfToken); ?>">

                <!-- Username Field -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <i data-lucide="user" class="w-4 h-4 inline ml-1"></i>
                        اسم المستخدم أو البريد الإلكتروني
                    </label>
                    <input type="text" 
                           name="username" 
                           required 
                           autocomplete="username"
                           class="input-field w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:outline-none focus:border-purple-500 transition-all duration-300"
                           placeholder="أدخل اسم المستخدم أو البريد الإلكتروني">
                </div>

                <!-- Password Field -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        <i data-lucide="lock" class="w-4 h-4 inline ml-1"></i>
                        كلمة المرور
                    </label>
                    <div class="relative">
                        <input type="password" 
                               name="password" 
                               id="password"
                               required 
                               autocomplete="current-password"
                               class="input-field w-full px-4 py-3 border-2 border-slate-300 rounded-xl focus:outline-none focus:border-purple-500 transition-all duration-300 pl-12"
                               placeholder="••••••••">
                        <button type="button" 
                                onclick="togglePassword()"
                                class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 transition">
                            <i data-lucide="eye" id="eyeIcon" class="w-5 h-5"></i>
                        </button>
                    </div>
                </div>

                <!-- Remember Me & Forgot Password -->
                <div class="flex items-center justify-between">
                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" class="rounded border-slate-300 text-purple-600 focus:ring-purple-500">
                        <span class="text-sm text-slate-600">تذكرني</span>
                    </label>
                    <a href="forgot-password.php" class="text-sm text-purple-600 hover:text-purple-800 font-semibold transition">
                        نسيت كلمة المرور؟
                    </a>
                </div>

                <!-- Submit Button -->
                <button type="submit" 
                        class="w-full py-4 bg-gradient-to-r from-purple-600 to-blue-600 text-white rounded-xl font-bold text-lg shadow-lg hover:shadow-xl transform hover:scale-[1.02] transition-all duration-300">
                    <i data-lucide="log-in" class="w-5 h-5 inline ml-2"></i>
                    تسجيل الدخول
                </button>
            </form>

            <!-- Divider -->
            <div class="relative my-8">
                <div class="absolute inset-0 flex items-center">
                    <div class="w-full border-t border-slate-300"></div>
                </div>
                <div class="relative flex justify-center text-sm">
                    <span class="px-4 bg-white text-slate-500">أو</span>
                </div>
            </div>

            <!-- Register Link -->
            <div class="text-center">
                <p class="text-slate-600 mb-4">ليس لديك حساب؟</p>
                <a href="platform/register.php" 
                   class="inline-block px-8 py-3 bg-white border-2 border-purple-600 text-purple-600 rounded-xl font-bold hover:bg-purple-50 transition-all duration-300">
                    <i data-lucide="user-plus" class="w-5 h-5 inline ml-2"></i>
                    إنشاء حساب جديد
                </a>
            </div>

            <!-- Back to Home -->
            <div class="mt-6 text-center">
                <a href="index.html" class="inline-flex items-center gap-2 text-slate-600 hover:text-purple-600 transition">
                    <i data-lucide="arrow-right" class="w-4 h-4"></i>
                    <span>العودة للصفحة الرئيسية</span>
                </a>
            </div>
        </div>

        <!-- Security Notice -->
        <div class="mt-6 text-center text-white text-opacity-90 text-sm">
            <div class="flex items-center justify-center gap-2 mb-2">
                <i data-lucide="shield-check" class="w-5 h-5"></i>
                <span class="font-semibold">اتصال آمن محمي</span>
            </div>
            <p class="text-xs text-white text-opacity-75">
                جميع البيانات محمية بتشفير SSL وأنظمة أمان متقدمة
            </p>
        </div>
    </div>

    <script>
        // Toggle password visibility
        function togglePassword() {
            const passwordField = document.getElementById('password');
            const eyeIcon = document.getElementById('eyeIcon');
            
            if (passwordField.type === 'password') {
                passwordField.type = 'text';
                eyeIcon.setAttribute('data-lucide', 'eye-off');
            } else {
                passwordField.type = 'password';
                eyeIcon.setAttribute('data-lucide', 'eye');
            }
            
            lucide.createIcons();
        }

        // Auto-focus first input
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelector('input[name="username"]').focus();
            lucide.createIcons();
        });

        // Form validation enhancement
        const form = document.querySelector('form');
        form.addEventListener('submit', function(e) {
            const username = form.querySelector('input[name="username"]').value.trim();
            const password = form.querySelector('input[name="password"]').value;
            
            if (!username || !password) {
                e.preventDefault();
                alert('الرجاء إدخال جميع البيانات المطلوبة');
                return false;
            }
            
            // Show loading state
            const submitBtn = form.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i data-lucide="loader" class="w-5 h-5 inline ml-2 animate-spin"></i> جاري تسجيل الدخول...';
            submitBtn.disabled = true;
        });
    </script>
</body>
</html>
