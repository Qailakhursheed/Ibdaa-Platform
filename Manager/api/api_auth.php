<?php
/**
 * API Authentication & Authorization Helper
 * ملف حماية مركزي لجميع ملفات API
 * 
 * الاستخدام:
 * require_once __DIR__ . '/api_auth.php';
 * APIAuth::requireAuth(); // للتحقق من تسجيل الدخول فقط
 * APIAuth::requireAuth(['manager', 'technical']); // للتحقق من صلاحيات محددة
 */

class APIAuth {
    /**
     * التحقق من تسجيل الدخول والصلاحيات
     * @param array|null $allowedRoles الأدوار المسموح بها (اختياري)
     * @return array معلومات المستخدم
     */
    public static function requireAuth($allowedRoles = null) {
        // التحقق من وجود جلسة نشطة
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // التحقق من تسجيل الدخول
        $userId = $_SESSION['user_id'] ?? null;
        $userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
        
        if (!$userId) {
            self::sendError('يجب تسجيل الدخول أولاً', 401);
        }
        
        // التحقق من الصلاحيات إذا تم تحديدها
        if ($allowedRoles !== null && !empty($allowedRoles)) {
            if (!in_array($userRole, $allowedRoles, true)) {
                self::sendError('ليس لديك صلاحية الوصول إلى هذه الوظيفة', 403);
            }
        }
        
        return [
            'user_id' => $userId,
            'role' => $userRole,
            'name' => $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'مستخدم',
            'email' => $_SESSION['user_email'] ?? ''
        ];
    }
    
    /**
     * التحقق من صلاحية محددة
     * @param array $allowedRoles الأدوار المسموح بها
     * @return bool
     */
    public static function hasRole($allowedRoles) {
        $userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
        return in_array($userRole, $allowedRoles, true);
    }
    
    /**
     * التحقق من CSRF Token
     * @param string $token
     * @return bool
     */
    public static function validateCSRF($token) {
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        return $sessionToken && hash_equals($sessionToken, $token);
    }
    
    /**
     * إرسال رسالة خطأ JSON والإيقاف
     * @param string $message
     * @param int $code
     */
    public static function sendError($message, $code = 400) {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => false,
            'error' => $message,
            'code' => $code
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * إرسال استجابة JSON ناجحة
     * @param mixed $data
     */
    public static function sendSuccess($data) {
        http_response_code(200);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'data' => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    /**
     * التحقق من طريقة الطلب
     * @param string $method الطريقة المطلوبة (GET, POST, PUT, DELETE)
     */
    public static function requireMethod($method) {
        if ($_SERVER['REQUEST_METHOD'] !== strtoupper($method)) {
            self::sendError('طريقة الطلب غير صحيحة. مطلوب: ' . $method, 405);
        }
    }
    
    /**
     * حماية ضد هجمات XSS
     * @param string $data
     * @return string
     */
    public static function sanitize($data) {
        return htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * التحقق من معدل الطلبات (Rate Limiting)
     * @param int $maxAttempts الحد الأقصى للمحاولات
     * @param int $timeWindow نافذة الوقت بالثواني
     */
    public static function rateLimit($maxAttempts = 60, $timeWindow = 60) {
        $userId = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . ($userId ?? $ip);
        
        if (!isset($_SESSION[$key])) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return;
        }
        
        $elapsed = time() - $_SESSION[$key]['start_time'];
        
        // إعادة تعيين العداد إذا انتهت نافذة الوقت
        if ($elapsed > $timeWindow) {
            $_SESSION[$key] = [
                'count' => 1,
                'start_time' => time()
            ];
            return;
        }
        
        // زيادة العداد
        $_SESSION[$key]['count']++;
        
        // التحقق من تجاوز الحد
        if ($_SESSION[$key]['count'] > $maxAttempts) {
            self::sendError('تم تجاوز الحد الأقصى للطلبات. يرجى المحاولة لاحقاً', 429);
        }
    }
    
    /**
     * تسجيل نشاط API
     * @param string $action
     * @param array $details
     */
    public static function logActivity($action, $details = []) {
        $userId = $_SESSION['user_id'] ?? null;
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'user_id' => $userId,
            'action' => $action,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        // يمكن تخزين السجلات في ملف أو قاعدة بيانات
        error_log(json_encode($logEntry, JSON_UNESCAPED_UNICODE));
    }
}
