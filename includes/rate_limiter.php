<?php
/**
 * Rate Limiting Helper
 * يوفر حماية ضد هجمات Brute Force
 */

class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $timeWindow; // بالدقائق
    private $lockoutTime; // بالدقائق
    
    /**
     * @param mysqli $connection اتصال قاعدة البيانات
     * @param int $maxAttempts الحد الأقصى للمحاولات
     * @param int $timeWindow نافذة الوقت بالدقائق
     * @param int $lockoutTime مدة الحظر بالدقائق
     */
    public function __construct($connection, $maxAttempts = 5, $timeWindow = 15, $lockoutTime = 30) {
        $this->conn = $connection;
        $this->maxAttempts = $maxAttempts;
        $this->timeWindow = $timeWindow;
        $this->lockoutTime = $lockoutTime;
        
        // إنشاء جدول المحاولات إذا لم يكن موجوداً
        $this->createTableIfNotExists();
    }
    
    /**
     * إنشاء جدول login_attempts
     */
    private function createTableIfNotExists() {
        $sql = "CREATE TABLE IF NOT EXISTS login_attempts (
            id INT AUTO_INCREMENT PRIMARY KEY,
            ip_address VARCHAR(45) NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            attempted_at DATETIME NOT NULL,
            success TINYINT(1) DEFAULT 0,
            INDEX idx_ip (ip_address),
            INDEX idx_email (email),
            INDEX idx_attempted (attempted_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
        
        $this->conn->query($sql);
    }
    
    /**
     * الحصول على عنوان IP الحالي
     */
    private function getClientIP() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    /**
     * التحقق من عدد المحاولات الفاشلة
     * @param string $email البريد الإلكتروني (اختياري)
     * @return array ['allowed' => bool, 'remaining' => int, 'wait_time' => int]
     */
    public function checkAttempts($email = null) {
        $ip = $this->getClientIP();
        $timeLimit = date('Y-m-d H:i:s', strtotime("-{$this->timeWindow} minutes"));
        
        // عد المحاولات الفاشلة
        $query = "SELECT COUNT(*) as attempts, MAX(attempted_at) as last_attempt 
                  FROM login_attempts 
                  WHERE ip_address = ? 
                  AND success = 0 
                  AND attempted_at > ?";
        
        $params = [$ip, $timeLimit];
        $types = "ss";
        
        // إضافة البريد الإلكتروني إلى الفلتر إذا كان متاحاً
        if ($email) {
            $query .= " AND email = ?";
            $params[] = $email;
            $types .= "s";
        }
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        $stmt->close();
        
        $attempts = (int)$result['attempts'];
        $lastAttempt = $result['last_attempt'];
        
        // حساب الوقت المتبقي للحظر
        $waitTime = 0;
        if ($attempts >= $this->maxAttempts && $lastAttempt) {
            $lockoutEnd = strtotime($lastAttempt) + ($this->lockoutTime * 60);
            $waitTime = max(0, $lockoutEnd - time());
        }
        
        return [
            'allowed' => $attempts < $this->maxAttempts || $waitTime == 0,
            'remaining' => max(0, $this->maxAttempts - $attempts),
            'wait_time' => $waitTime, // بالثواني
            'attempts' => $attempts
        ];
    }
    
    /**
     * تسجيل محاولة تسجيل دخول
     * @param string $email البريد الإلكتروني
     * @param bool $success نجاح أو فشل المحاولة
     */
    public function recordAttempt($email, $success = false) {
        $ip = $this->getClientIP();
        $stmt = $this->conn->prepare(
            "INSERT INTO login_attempts (ip_address, email, attempted_at, success) 
             VALUES (?, ?, NOW(), ?)"
        );
        $successInt = $success ? 1 : 0;
        $stmt->bind_param("ssi", $ip, $email, $successInt);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * مسح المحاولات الناجحة لبريد إلكتروني محدد
     * @param string $email البريد الإلكتروني
     */
    public function clearAttempts($email) {
        $ip = $this->getClientIP();
        $stmt = $this->conn->prepare(
            "DELETE FROM login_attempts 
             WHERE (ip_address = ? OR email = ?) 
             AND success = 0"
        );
        $stmt->bind_param("ss", $ip, $email);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * تنظيف السجلات القديمة (يُفضل تشغيلها دورياً)
     * @param int $days عدد الأيام للاحتفاظ بالسجلات
     */
    public function cleanOldRecords($days = 30) {
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE attempted_at < ?");
        $stmt->bind_param("s", $date);
        $stmt->execute();
        $affectedRows = $stmt->affected_rows;
        $stmt->close();
        
        return $affectedRows;
    }
    
    /**
     * الحصول على رسالة خطأ مناسبة
     * @param array $status نتيجة checkAttempts
     */
    public function getErrorMessage($status) {
        if (!$status['allowed']) {
            $minutes = ceil($status['wait_time'] / 60);
            return "تم تجاوز عدد محاولات تسجيل الدخول المسموح بها. يرجى المحاولة بعد {$minutes} دقيقة.";
        }
        
        if ($status['remaining'] <= 2 && $status['remaining'] > 0) {
            return "تحذير: لديك {$status['remaining']} محاولة متبقية قبل حظر الحساب مؤقتاً.";
        }
        
        return null;
    }
}
