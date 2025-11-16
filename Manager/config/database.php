<?php
/**
 * PDO Database Connection Class
 * For Manager/api/student_*.php files
 * 
 * تم إنشاؤه: 2025-11-12
 * الهدف: توفير اتصال PDO للملفات التي تحتاجه
 */

class Database {
    private static $pdo = null;
    
    /**
     * إرجاع اتصال PDO
     * @return PDO
     */
    public function getConnection() {
        if (self::$pdo === null) {
            // إعدادات قاعدة البيانات
            $host = 'localhost';
            $db   = 'ibdaa_taiz'; // تم التوحيد
            $user = 'root';
            $pass = '';
            $charset = 'utf8mb4';

            $dsn = "mysql:host=$host;dbname=$db;charset=$charset";
            
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ];
            
            try {
                self::$pdo = new PDO($dsn, $user, $pass, $options);
            } catch (PDOException $e) {
                // في حالة الخطأ
                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل الاتصال بقاعدة البيانات',
                    'error' => $e->getMessage()
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        }
        
        return self::$pdo;
    }
    
    /**
     * إغلاق الاتصال (اختياري)
     */
    public static function closeConnection() {
        self::$pdo = null;
    }
}
