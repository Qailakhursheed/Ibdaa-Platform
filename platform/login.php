<?php
require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once __DIR__ . '/../includes/anti_detection.php';
require_once 'db.php';

// إخفاء معلومات السيرفر
AntiDetection::hideServerHeaders();

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

// فحص سمعة IP
if (!AntiDetection::checkIPReputation($_SERVER['REMOTE_ADDR'])) {
    AntiDetection::sendDecoyResponse();
}

// كشف البوتات والطلبات المشبوهة
if (AntiDetection::detectBot() || AntiDetection::detectFingerprinting()) {
    AntiDetection::logSuspiciousActivity('suspicious_access', [
        'page' => 'login',
        'is_bot' => AntiDetection::detectBot()
    ]);
    AntiDetection::addRandomDelay(2000, 5000);
}

// التحقق من تسجيل الدخول مسبقاً
if (isset($_SESSION['user_id'])) {
    $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? 'student';
    switch ($userRole) {
        case 'manager':
        case 'technical':
        case 'trainer':
            header("Location: ../Manager/dashboard_router.php");
            break;
        default:
            header("Location: student-dashboard.php");
    }
    exit;
}

// إنشاء Rate Limiter
$rateLimiter = new RateLimiter($conn, 5, 15, 30);

// معالجة تسجيل الدخول
$error = '';
$success = '';
$warningMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // فحص الحماية الشاملة (Honeypot, Timestamp, JS)
    $protectionCheck = AntiDetection::validateFullProtection();
    
    if (!$protectionCheck['valid']) {
        // رسالة موحدة لجميع أنواع الفشل
        $error = AntiDetection::getGenericError('login');
        AntiDetection::logSuspiciousActivity('protection_failed', $protectionCheck['errors']);
    }
    // التحقق من CSRF Token
    elseif (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
        $error = AntiDetection::getGenericError('form');
        AntiDetection::addRandomDelay(300, 800);
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        
        if (empty($email) || empty($password)) {
            $error = AntiDetection::getGenericError('login');
            AntiDetection::addRandomDelay(200, 600);
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
                
                $stmt = $conn->prepare("SELECT id, full_name, email, password_hash, role, verified, photo_path, account_status, payment_complete FROM users WHERE email = ?");
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows === 0) {
                    // رسالة موحدة عامة
                    $error = AntiDetection::getGenericError('login');
                    $rateLimiter->recordAttempt($email, false);
                    // تأخير متدرج
                    AntiDetection::addProgressiveDelay($rateStatus['attempts'] + 1);
                } else {
                    $user = $result->fetch_assoc();
                    
                    // التحقق من كلمة المرور
                    if (!password_verify($password, $user['password_hash'])) {
                        // نفس الرسالة الموحدة
                        $error = AntiDetection::getGenericError('login');
                        $rateLimiter->recordAttempt($email, false);
                        // تأخير متدرج
                        AntiDetection::addProgressiveDelay($rateStatus['attempts'] + 1);
                    } 
                    // التحقق من تفعيل الحساب
                    elseif ($user['verified'] == 0) {
                        $error = "حسابك غير مفعل. يرجى التحقق من بريدك الإلكتروني لتفعيل الحساب.";
                        
                        // عرض رابط التفعيل للاختبار المحلي
                        if (isset($_SESSION['verification_link']) && $_SESSION['pending_email'] === $email) {
                            $error .= '<br><small>للاختبار: <a href="' . $_SESSION['verification_link'] . '" class="underline text-yellow-300">اضغط هنا للتفعيل</a></small>';
                        }
                    }
                    // التحقق من حالة الحساب والدفع
                    elseif ($user['account_status'] === 'pending' || $user['payment_complete'] == 0) {
                        $error = "حسابك قيد المراجعة أو لم يتم تأكيد الدفع بعد. يرجى التواصل مع الإدارة لتفعيل الحساب.";
                    }
                    else {
                        // تسجيل دخول ناجح
                        $rateLimiter->recordAttempt($email, true);
                        $rateLimiter->clearAttempts($email);
                        
                        $userRole = $user['role'] ?? 'student';
                        
                        // استخدام SessionSecurity للتسجيل الآمن
                        SessionSecurity::login([
                            'id' => $user['id'],
                            'full_name' => $user['full_name'],
                            'email' => $user['email'],
                            'role' => $userRole,
                            'photo' => $user['photo_path']
                        ]);
                        
                        // تجديد CSRF Token
                        CSRF::refreshToken();
                        
                        // التوجيه حسب الدور
                        switch ($userRole) {
                            case 'manager':
                            case 'technical':
                                header("Location: ../Manager/dashboard_router.php");
                                break;
                            case 'trainer':
                                header("Location: trainer-dashboard.php");
                                break;
                            default:
                                header("Location: student-dashboard.php");
                        }
                        exit;
                    }
                }
                $stmt->close();
            }
        }
    }
}

// رسائل من صفحات أخرى
if (isset($_GET['success'])) {
    $success = htmlspecialchars($_GET['success']);
}
if (isset($_GET['error'])) {
    $error = htmlspecialchars($_GET['error']);
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>تسجيل الدخول - منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body {
      font-family: 'Cairo', sans-serif;
      background: url('photos/bg.png') center center/cover no-repeat fixed;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(3px);
      z-index: 0;
    }
  </style>
</head>

<body class="relative text-white">
  <div class="overlay"></div>

  <div class="relative z-10 bg-white/10 p-10 rounded-2xl shadow-2xl w-[90%] max-w-md backdrop-blur-md border border-white/20">
    <div class="text-center mb-6">
      <img src="photos/Sh.jpg" class="mx-auto w-16 h-16 rounded-full border-2 border-indigo-400 shadow-md mb-3">
      <h1 class="text-3xl font-bold text-white">تسجيل الدخول</h1>
      <p class="text-gray-200 text-sm mt-2">مرحباً بك في منصة إبداع 👋</p>
    </div>

    <?php if(!empty($success)): ?>
      <div class="bg-green-500/20 border border-green-500 text-white px-4 py-3 rounded-lg mb-4">
        <?php echo $success; ?>
      </div>
    <?php endif; ?>

    <?php if(!empty($warningMessage)): ?>
      <div class="bg-yellow-500/20 border border-yellow-500 text-white px-4 py-3 rounded-lg mb-4">
        <?php echo $warningMessage; ?>
      </div>
    <?php endif; ?>

    <?php if(!empty($error)): ?>
      <div class="bg-red-500/20 border border-red-500 text-white px-4 py-3 rounded-lg mb-4">
        <?php echo $error; ?>
      </div>
    <?php endif; ?>

    <form class="space-y-5" method="POST" action="">
      <?php echo CSRF::getTokenField(); ?>
      <?php echo AntiDetection::getProtectedFormFields(); ?>
      <div>
        <label class="block text-gray-200 mb-1">البريد الإلكتروني</label>
        <input type="email" name="email" required placeholder="example@email.com" 
               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
               class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
      </div>

      <div>
        <label class="block text-gray-200 mb-1">كلمة المرور</label>
        <input type="password" name="password" required placeholder="••••••••" 
               class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 py-3 rounded-lg shadow-lg hover:opacity-90 transition font-semibold">
        دخول
      </button>
    </form>

    <p class="text-center text-gray-300 mt-6">ليس لديك حساب؟  
      <a href="signup.php" class="text-indigo-300 hover:text-indigo-200 font-semibold">إنشاء حساب جديد</a>
    </p>
  </div>
  <script src="js/watermark.js"></script>
</body>
</html>
