<?php
/**
 * Session Security Helper
 * يوفر حماية متقدمة للجلسات
 */

class SessionSecurity {
    /**
     * بدء جلسة آمنة مع إعدادات محسّنة
     */
    public static function startSecureSession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return; // الجلسة مفعلة بالفعل
        }
        
        // إعدادات أمان الجلسة
        ini_set('session.cookie_httponly', 1); // منع الوصول عبر JavaScript
        ini_set('session.use_only_cookies', 1); // استخدام cookies فقط
        ini_set('session.cookie_secure', 0); // تعطيل HTTPS للتطوير المحلي (فعّل في الإنتاج)
        ini_set('session.cookie_samesite', 'Lax'); // حماية CSRF إضافية
        
        session_start();
        
        // التحقق من Session Hijacking
        if (!self::validateSession()) {
            self::destroySession();
            return false;
        }
        
        // تحديث نشاط الجلسة
        self::updateActivity();
        
        return true;
    }
    
    /**
     * التحقق من صحة الجلسة (منع Session Hijacking)
     */
    private static function validateSession() {
        // التحقق الأول: User Agent
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
                return false; // User Agent تغير (احتمال اختراق)
            }
        } else {
            // تخزين User Agent في أول مرة
            $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        }
        
        // التحقق الثاني: IP Address (اختياري - قد يسبب مشاكل مع dynamic IPs)
        // Uncomment for stricter security
        /*
        if (isset($_SESSION['user_ip'])) {
            if ($_SESSION['user_ip'] !== $_SERVER['REMOTE_ADDR']) {
                return false;
            }
        } else {
            $_SESSION['user_ip'] = $_SERVER['REMOTE_ADDR'];
        }
        */
        
        return true;
    }
    
    /**
     * تحديث وقت آخر نشاط
     */
    private static function updateActivity() {
        $_SESSION['last_activity'] = time();
    }
    
    /**
     * التحقق من timeout الجلسة
     * @param int $timeout مدة timeout بالثواني (افتراضي: 30 دقيقة)
     */
    public static function checkTimeout($timeout = 1800) {
        if (isset($_SESSION['last_activity'])) {
            $elapsed = time() - $_SESSION['last_activity'];
            
            if ($elapsed > $timeout) {
                self::destroySession();
                return false; // انتهت صلاحية الجلسة
            }
        }
        
        self::updateActivity();
        return true; // الجلسة نشطة
    }
    
    /**
     * تدمير الجلسة بشكل آمن
     */
    public static function destroySession() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION = array();
            
            // حذف session cookie
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
        }
    }
    
    /**
     * تجديد Session ID (منع Session Fixation)
     */
    public static function regenerateId() {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_regenerate_id(true);
        }
    }
    
    /**
     * التحقق من تسجيل الدخول
     * @param string $redirectUrl صفحة إعادة التوجيه عند عدم التسجيل
     */
    public static function requireLogin($redirectUrl = 'login.php') {
        if (!isset($_SESSION['user_id'])) {
            header("Location: $redirectUrl");
            exit;
        }
        
        // التحقق من timeout
        if (!self::checkTimeout()) {
            header("Location: $redirectUrl?error=session_expired");
            exit;
        }
    }
    
    /**
     * التحقق من الصلاحيات
     * @param array $allowedRoles الأدوار المسموحة
     * @param string $redirectUrl صفحة إعادة التوجيه عند عدم وجود صلاحية
     */
    public static function requireRole($allowedRoles, $redirectUrl = 'login.php') {
        self::requireLogin($redirectUrl);
        
        $userRole = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
        
        if (!in_array($userRole, $allowedRoles)) {
            header("Location: $redirectUrl?error=unauthorized");
            exit;
        }
    }
    
    /**
     * تسجيل الدخول الآمن
     * @param array $userData بيانات المستخدم
     */
    public static function login($userData) {
        // تجديد Session ID لمنع Session Fixation
        self::regenerateId();
        
        // تخزين بيانات المستخدم
        $_SESSION['user_id'] = $userData['id'];
        $_SESSION['user_name'] = $userData['full_name'] ?? $userData['name'];
        $_SESSION['user_email'] = $userData['email'];
        $_SESSION['user_role'] = $userData['role'];
        $_SESSION['role'] = $userData['role']; // Fallback
        
        if (isset($userData['photo'])) {
            $_SESSION['user_photo'] = $userData['photo'];
        }
        
        // تخزين معلومات الجلسة الأمنية
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
        $_SESSION['login_time'] = time();
        
        self::updateActivity();
    }
    
    /**
     * تسجيل الخروج الآمن
     */
    public static function logout() {
        self::destroySession();
    }
}
