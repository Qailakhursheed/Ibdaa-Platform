<?php
/**
 * Anti-Detection Security Layer
 * تقنيات متقدمة لإخفاء الآليات الأمنية عن المهاجمين
 */

class AntiDetection {
    
    /**
     * توليد رسائل خطأ موحدة (Generic Error Messages)
     * لمنع تسريب معلومات عن النظام
     */
    public static function getGenericError($context = 'login') {
        $messages = [
            'login' => [
                'بيانات الدخول غير صحيحة',
                'فشل في تسجيل الدخول',
                'لا يمكن المتابعة. حاول مرة أخرى',
                'معلومات غير صالحة'
            ],
            'form' => [
                'خطأ في معالجة الطلب',
                'لا يمكن إتمام العملية',
                'حدث خطأ. حاول لاحقاً',
                'طلب غير صالح'
            ],
            'access' => [
                'غير مصرح بالوصول',
                'لا يمكن عرض هذه الصفحة',
                'طلب مرفوض',
                'وصول غير مسموح'
            ]
        ];
        
        $contextMessages = $messages[$context] ?? $messages['form'];
        return $contextMessages[array_rand($contextMessages)];
    }
    
    /**
     * إضافة تأخير عشوائي (Random Delay)
     * لمنع تحليل التوقيت (Timing Analysis)
     */
    public static function addRandomDelay($minMs = 100, $maxMs = 500) {
        $delay = mt_rand($minMs, $maxMs) * 1000; // تحويل إلى ميكروثانية
        usleep($delay);
    }
    
    /**
     * إضافة تأخير متدرج للمحاولات الفاشلة
     * يزيد التأخير مع كل محاولة فاشلة
     */
    public static function addProgressiveDelay($attempts) {
        if ($attempts <= 0) return;
        
        // التأخير يزيد بشكل أسي: 1s, 2s, 4s, 8s, 16s
        $baseDelay = min(pow(2, $attempts - 1), 16); // حد أقصى 16 ثانية
        $randomFactor = mt_rand(80, 120) / 100; // عشوائية ±20%
        $delay = $baseDelay * $randomFactor;
        
        sleep((int)$delay);
    }
    
    /**
     * إخفاء معلومات Server Headers
     */
    public static function hideServerHeaders() {
        // إزالة/إخفاء headers تكشف عن تقنيات السيرفر
        @header_remove('X-Powered-By');
        @header_remove('Server');
        
        // إضافة headers مزيفة (optional)
        // header('X-Powered-By: ASP.NET'); // يوهم المهاجم بأنه IIS/Windows
    }
    
    /**
     * توليد استجابة موحدة للطلبات المرفوضة
     * نفس الاستجابة لـ CSRF، Rate Limit، Invalid Credentials، إلخ
     */
    public static function getUnifiedResponse($type = 'error') {
        self::addRandomDelay(200, 600);
        
        $responses = [
            'error' => [
                'success' => false,
                'message' => self::getGenericError('form')
            ],
            'login_error' => [
                'success' => false,
                'message' => self::getGenericError('login')
            ],
            'access_denied' => [
                'success' => false,
                'message' => self::getGenericError('access')
            ]
        ];
        
        return $responses[$type] ?? $responses['error'];
    }
    
    /**
     * إضافة Honeypot Field (حقل فخ)
     * حقل مخفي يجب أن يبقى فارغاً (البشر لا يملأونه، البوتات تملأه)
     */
    public static function getHoneypotField($fieldName = 'website') {
        $token = bin2hex(random_bytes(8));
        return sprintf(
            '<input type="text" name="%s" id="%s" value="" style="position:absolute;left:-9999px;width:1px;height:1px;" tabindex="-1" autocomplete="off">',
            htmlspecialchars($fieldName),
            htmlspecialchars($token)
        );
    }
    
    /**
     * التحقق من Honeypot
     */
    public static function checkHoneypot($fieldName = 'website') {
        // إذا كان الحقل ممتلئاً، هذا بوت
        if (!empty($_POST[$fieldName])) {
            self::addRandomDelay(500, 1500);
            return false; // فشل (بوت)
        }
        return true; // نجح (بشر)
    }
    
    /**
     * إضافة Timestamp Field للحماية من Replay Attacks
     */
    public static function getTimestampField() {
        $timestamp = time();
        $signature = hash_hmac('sha256', $timestamp, session_id());
        
        return sprintf(
            '<input type="hidden" name="form_ts" value="%s">
            <input type="hidden" name="form_sig" value="%s">',
            $timestamp,
            $signature
        );
    }
    
    /**
     * التحقق من Timestamp (منع الطلبات القديمة والسريعة جداً)
     */
    public static function checkTimestamp($minSeconds = 3, $maxSeconds = 600) {
        $timestamp = (int)($_POST['form_ts'] ?? 0);
        $signature = $_POST['form_sig'] ?? '';
        
        if (empty($timestamp) || empty($signature)) {
            return false;
        }
        
        // التحقق من صحة التوقيع
        $expectedSig = hash_hmac('sha256', $timestamp, session_id());
        if (!hash_equals($expectedSig, $signature)) {
            return false;
        }
        
        $elapsed = time() - $timestamp;
        
        // الطلب قديم جداً (أكثر من 10 دقائق)
        if ($elapsed > $maxSeconds) {
            return false;
        }
        
        // الطلب سريع جداً (أقل من 3 ثواني - بوت)
        if ($elapsed < $minSeconds) {
            self::addRandomDelay(1000, 2000);
            return false;
        }
        
        return true;
    }
    
    /**
     * كشف وحظر البوتات المشهورة
     */
    public static function detectBot() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $botSignatures = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'python-requests', 'scrapy', 'selenium', 'phantomjs',
            'headless', 'automated', 'sqlmap', 'nikto', 'nmap'
        ];
        
        foreach ($botSignatures as $signature) {
            if (stripos($userAgent, $signature) !== false) {
                return true; // بوت مكتشف
            }
        }
        
        // فحص إضافي: عدم وجود User Agent
        if (empty($userAgent)) {
            return true;
        }
        
        return false;
    }
    
    /**
     * فحص JavaScript (البوتات غالباً لا تشغل JS)
     */
    public static function getJSCheckField() {
        $token = bin2hex(random_bytes(16));
        $_SESSION['js_token'] = $token;
        
        return sprintf(
            '<input type="hidden" name="js_enabled" value="" id="js_check">
            <script>document.getElementById("js_check").value="%s";</script>',
            $token
        );
    }
    
    /**
     * التحقق من تفعيل JavaScript
     */
    public static function checkJSEnabled() {
        $jsValue = $_POST['js_enabled'] ?? '';
        $expectedToken = $_SESSION['js_token'] ?? '';
        
        if (empty($jsValue) || empty($expectedToken)) {
            return false; // JS غير مفعل (محتمل بوت)
        }
        
        return hash_equals($expectedToken, $jsValue);
    }
    
    /**
     * تسجيل المحاولات المشبوهة بدون كشف
     * (Silent Logging)
     */
    public static function logSuspiciousActivity($type, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'type' => $type,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'details' => $details
        ];
        
        $logFile = __DIR__ . '/../logs/security_silent.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        
        // تسجيل بدون إظهار أي رسالة للمستخدم
        @file_put_contents(
            $logFile,
            json_encode($logEntry) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
    
    /**
     * Fingerprinting Detection
     * كشف محاولات fingerprinting للنظام
     */
    public static function detectFingerprinting() {
        $suspiciousPatterns = [
            // SQL Injection probes
            'union', 'select', '1=1', 'or 1=1', 'drop table',
            // XSS probes
            '<script', 'javascript:', 'onerror=',
            // Path traversal
            '../', '..\\', '/etc/passwd',
            // Command injection
            '|', '&&', ';ls', ';cat',
        ];
        
        // فحص جميع المدخلات
        $allInput = array_merge($_GET, $_POST, $_COOKIE);
        
        foreach ($allInput as $key => $value) {
            if (is_array($value)) continue;
            
            $value = strtolower($value);
            foreach ($suspiciousPatterns as $pattern) {
                if (strpos($value, $pattern) !== false) {
                    self::logSuspiciousActivity('fingerprinting', [
                        'pattern' => $pattern,
                        'input' => substr($value, 0, 100)
                    ]);
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * إنشاء Fake Response لتضليل المهاجمين
     */
    public static function sendDecoyResponse() {
        self::addRandomDelay(1000, 3000);
        
        // استجابة مزيفة تبدو حقيقية لكنها عديمة الفائدة
        http_response_code(200);
        header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false,
            'message' => self::getGenericError('form'),
            'code' => 'ERR_' . mt_rand(1000, 9999)
        ]);
        
        exit;
    }
    
    /**
     * IP Reputation Check (فحص سمعة IP)
     * يمكن ربطه بقوائم سوداء خارجية
     */
    public static function checkIPReputation($ip) {
        // قائمة IPs المحظورة محلياً
        $blacklist = [
            // أمثلة - يمكن تحديثها ديناميكياً
            // '192.168.1.100',
            // '10.0.0.50'
        ];
        
        if (in_array($ip, $blacklist)) {
            return false; // IP محظور
        }
        
        // TODO: ربط مع APIs خارجية لفحص السمعة
        // مثل: AbuseIPDB, StopForumSpam, etc.
        
        return true; // IP نظيف
    }
    
    /**
     * Rate Limiting مخفي (لا يُظهر أي رسائل محددة)
     */
    public static function checkHiddenRateLimit($identifier, $maxAttempts = 10, $windowMinutes = 5) {
        if (!isset($_SESSION['hidden_rl'])) {
            $_SESSION['hidden_rl'] = [];
        }
        
        $now = time();
        $windowSeconds = $windowMinutes * 60;
        
        // تنظيف السجلات القديمة
        $_SESSION['hidden_rl'] = array_filter(
            $_SESSION['hidden_rl'],
            function($timestamp) use ($now, $windowSeconds) {
                return ($now - $timestamp) < $windowSeconds;
            }
        );
        
        // عد المحاولات
        $attempts = $_SESSION['hidden_rl'][$identifier] ?? [];
        
        if (count($attempts) >= $maxAttempts) {
            // تجاوز الحد - لكن نُظهر رسالة عامة
            self::addProgressiveDelay(count($attempts) - $maxAttempts + 1);
            return false;
        }
        
        // تسجيل المحاولة
        $_SESSION['hidden_rl'][$identifier][] = $now;
        
        return true;
    }
    
    /**
     * إنشاء نموذج كامل محمي بكل التقنيات
     */
    public static function getProtectedFormFields() {
        return 
            self::getHoneypotField() .
            self::getTimestampField() .
            self::getJSCheckField();
    }
    
    /**
     * التحقق الشامل من جميع الآليات
     */
    public static function validateFullProtection() {
        $errors = [];
        
        // 1. فحص Honeypot
        if (!self::checkHoneypot()) {
            self::logSuspiciousActivity('honeypot_filled');
            $errors[] = 'bot_detected';
        }
        
        // 2. فحص Timestamp
        if (!self::checkTimestamp()) {
            self::logSuspiciousActivity('timestamp_invalid');
            $errors[] = 'timing_issue';
        }
        
        // 3. فحص JavaScript
        if (!self::checkJSEnabled()) {
            self::logSuspiciousActivity('js_disabled');
            $errors[] = 'js_required';
        }
        
        // 4. فحص Bot User Agent
        if (self::detectBot()) {
            self::logSuspiciousActivity('bot_user_agent');
            $errors[] = 'bot_detected';
        }
        
        // 5. فحص Fingerprinting
        if (self::detectFingerprinting()) {
            $errors[] = 'suspicious_input';
        }
        
        // إذا كان هناك أخطاء، نرد برسالة موحدة عامة
        if (!empty($errors)) {
            return [
                'valid' => false,
                'errors' => $errors,
                'message' => self::getGenericError('form')
            ];
        }
        
        return [
            'valid' => true,
            'errors' => [],
            'message' => null
        ];
    }
}
