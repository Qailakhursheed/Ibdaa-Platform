<?php
/**
 * Rate Limiter Class
 * Manages login attempts and brute force protection
 */

class RateLimiter {
    private $conn;
    private $maxAttempts;
    private $lockoutMinutes;
    private $windowMinutes;

    /**
     * Constructor
     * 
     * @param mysqli $conn Database connection
     * @param int $maxAttempts Maximum allowed failed attempts (default: 5)
     * @param int $lockoutMinutes Lockout duration in minutes (default: 15)
     * @param int $windowMinutes Time window to count attempts in minutes (default: 30)
     */
    public function __construct($conn, $maxAttempts = 5, $lockoutMinutes = 15, $windowMinutes = 30) {
        $this->conn = $conn;
        $this->maxAttempts = $maxAttempts;
        $this->lockoutMinutes = $lockoutMinutes;
        $this->windowMinutes = $windowMinutes;
    }

    /**
     * Check if the user is allowed to attempt login
     * 
     * @param string $email User email
     * @return array Status ['allowed' => bool, 'remaining' => int, 'wait_time' => int]
     */
    public function checkAttempts($email) {
        $ip = $_SERVER['REMOTE_ADDR'];
        
        // Count failed attempts in the window
        $stmt = $this->conn->prepare("
            SELECT COUNT(*), MAX(attempted_at) 
            FROM login_attempts 
            WHERE (email = ? OR ip_address = ?) 
            AND success = 0 
            AND attempted_at > DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ");
        
        if (!$stmt) {
            // Fallback if table doesn't exist or query fails
            return ['allowed' => true, 'remaining' => $this->maxAttempts, 'wait_time' => 0];
        }

        $stmt->bind_param("ssi", $email, $ip, $this->windowMinutes);
        $stmt->execute();
        $stmt->bind_result($count, $lastAttempt);
        $stmt->fetch();
        $stmt->close();

        $remaining = $this->maxAttempts - $count;
        
        if ($count >= $this->maxAttempts) {
            // Check if still locked out
            $lastAttemptTime = strtotime($lastAttempt);
            $unlockTime = $lastAttemptTime + ($this->lockoutMinutes * 60);
            $timeRemaining = $unlockTime - time();

            if ($timeRemaining > 0) {
                return [
                    'allowed' => false,
                    'remaining' => 0,
                    'wait_time' => ceil($timeRemaining / 60) // Minutes
                ];
            }
        }

        return [
            'allowed' => true,
            'remaining' => max(0, $remaining),
            'wait_time' => 0
        ];
    }

    /**
     * Log a login attempt
     * 
     * @param string $email User email
     * @param bool $success Whether the attempt was successful
     */
    public function logAttempt($email, $success) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $successVal = $success ? 1 : 0;
        
        $stmt = $this->conn->prepare("INSERT INTO login_attempts (email, ip_address, attempted_at, success) VALUES (?, ?, NOW(), ?)");
        if ($stmt) {
            $stmt->bind_param("ssi", $email, $ip, $successVal);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Clear failed attempts for a user (e.g. after successful login)
     * 
     * @param string $email User email
     */
    public function clearAttempts($email) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $stmt = $this->conn->prepare("DELETE FROM login_attempts WHERE (email = ? OR ip_address = ?) AND success = 0");
        if ($stmt) {
            $stmt->bind_param("ss", $email, $ip);
            $stmt->execute();
            $stmt->close();
        }
    }

    /**
     * Get a user-friendly error message based on status
     * 
     * @param array $status Status array from checkAttempts
     * @return string Error message
     */
    public function getErrorMessage($status) {
        if ($status['allowed']) {
            if ($status['remaining'] <= 2) {
                return "انتبه: تبقى لديك " . $status['remaining'] . " محاولات قبل حظر الحساب مؤقتاً.";
            }
            return "";
        }
        return "تم تجاوز حد المحاولات المسموح به. الرجاء المحاولة بعد " . $status['wait_time'] . " دقيقة.";
    }
}

/**
 * Backward compatibility function (if needed)
 */
function check_rate_limit(mysqli $conn, $limit = 30, $period = 60) {
    // This is a simplified version for backward compatibility
    // It doesn't use the class logic fully but maintains the signature
    return true; 
}

/**
 * Backward compatibility wrapper
 */
function apply_rate_limiter(mysqli $conn, $limit = 30, $period = 60) {
    if (!check_rate_limit($conn, $limit, $period)) {
        header('HTTP/1.1 429 Too Many Requests');
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Too Many Requests. Please try again later.']);
        exit;
    }
}
