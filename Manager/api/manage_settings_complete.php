<?php
/**
 * API لإدارة إعدادات المنصة - نسخة كاملة
 * الإصدار: 2.0
 * الصلاحية: المدير فقط (manager)
 */

session_start();
require_once '../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ]);
    exit;
}

// التحقق من صلاحية المدير فقط
if ($_SESSION['role'] !== 'manager') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'هذا القسم مخصص للمديرين فقط'
    ]);
    exit;
}

// استلام البيانات من JSON body
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// دمج البيانات من GET و POST و JSON
if (is_array($data)) {
    $_POST = array_merge($_POST, $data);
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$userId = $_SESSION['user_id'];

/**
 * إنشاء جدول الإعدادات إذا لم يكن موجوداً
 */
function ensureSettingsTable($conn) {
    $createTableQuery = "
    CREATE TABLE IF NOT EXISTS platform_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(255) UNIQUE NOT NULL,
        setting_value TEXT,
        setting_type ENUM('general', 'email', 'security', 'backup', 'users') DEFAULT 'general',
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        updated_by INT,
        FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL,
        INDEX idx_setting_key (setting_key),
        INDEX idx_setting_type (setting_type)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ";
    
    mysqli_query($conn, $createTableQuery);
}

/**
 * الحصول على إعداد واحد
 */
function getSetting($conn, $key, $default = null) {
    $stmt = mysqli_prepare($conn, "SELECT setting_value FROM platform_settings WHERE setting_key = ?");
    mysqli_stmt_bind_param($stmt, "s", $key);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if ($row = mysqli_fetch_assoc($result)) {
        return $row['setting_value'];
    }
    
    return $default;
}

/**
 * حفظ أو تحديث إعداد
 */
function setSetting($conn, $key, $value, $type = 'general', $userId = null) {
    $stmt = mysqli_prepare($conn, 
        "INSERT INTO platform_settings (setting_key, setting_value, setting_type, updated_by) 
         VALUES (?, ?, ?, ?) 
         ON DUPLICATE KEY UPDATE 
         setting_value = VALUES(setting_value), 
         setting_type = VALUES(setting_type),
         updated_by = VALUES(updated_by),
         updated_at = CURRENT_TIMESTAMP"
    );
    
    mysqli_stmt_bind_param($stmt, "sssi", $key, $value, $type, $userId);
    return mysqli_stmt_execute($stmt);
}

/**
 * الحصول على جميع الإعدادات حسب النوع
 */
function getSettingsByType($conn, $type = null) {
    if ($type) {
        $stmt = mysqli_prepare($conn, "SELECT setting_key, setting_value FROM platform_settings WHERE setting_type = ?");
        mysqli_stmt_bind_param($stmt, "s", $type);
    } else {
        $stmt = mysqli_prepare($conn, "SELECT setting_key, setting_value FROM platform_settings");
    }
    
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    $settings = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    return $settings;
}

// التأكد من وجود جدول الإعدادات
ensureSettingsTable($conn);

// معالجة الطلبات
try {
    switch ($action) {
        
        // ========================
        // الحصول على جميع الإعدادات
        // ========================
        case 'get':
        case 'getAll':
            $type = $_GET['type'] ?? null;
            $settings = getSettingsByType($conn, $type);
            
            // إضافة قيم افتراضية إذا كانت فارغة
            $defaults = [
                'platform_name' => 'منصة إبداع',
                'support_email' => 'info@ibdaa-taiz.org',
                'phone' => '+967 777 123 456',
                'address' => 'تعز، اليمن',
                'platform_description' => 'منصة تدريبية رائدة لتطوير المهارات',
                'timezone' => 'Asia/Aden',
                'language' => 'ar',
                'enable_2fa' => '0',
                'session_timeout' => '3600'
            ];
            
            $settings = array_merge($defaults, $settings);
            
            echo json_encode([
                'success' => true,
                'settings' => $settings
            ]);
            break;
        
        // ========================
        // تحديث الإعدادات
        // ========================
        case 'update':
            // الحقول المسموح بتحديثها
            $allowedFields = [
                'platform_name' => 'general',
                'support_email' => 'general',
                'phone' => 'general',
                'address' => 'general',
                'platform_description' => 'general',
                'timezone' => 'general',
                'language' => 'general',
                'smtp_host' => 'email',
                'smtp_port' => 'email',
                'smtp_username' => 'email',
                'smtp_password' => 'email',
                'smtp_encryption' => 'email',
                'enable_2fa' => 'security',
                'session_timeout' => 'security',
                'password_min_length' => 'security',
                'backup_frequency' => 'backup',
                'backup_retention_days' => 'backup'
            ];
            
            $updated = [];
            $errors = [];
            
            foreach ($allowedFields as $field => $type) {
                if (isset($_POST[$field])) {
                    $value = trim($_POST[$field]);
                    
                    // التحقق من صحة البريد الإلكتروني
                    if ($field === 'support_email' && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        $errors[] = 'البريد الإلكتروني غير صالح';
                        continue;
                    }
                    
                    // التحقق من الأرقام
                    if (in_array($field, ['smtp_port', 'session_timeout', 'password_min_length', 'backup_retention_days'])) {
                        if (!is_numeric($value)) {
                            $errors[] = "القيمة $field يجب أن تكون رقماً";
                            continue;
                        }
                    }
                    
                    if (setSetting($conn, $field, $value, $type, $userId)) {
                        $updated[] = $field;
                    } else {
                        $errors[] = "فشل تحديث $field";
                    }
                }
            }
            
            if (empty($errors)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حفظ الإعدادات بنجاح',
                    'updated_fields' => $updated,
                    'count' => count($updated)
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'حدثت بعض الأخطاء',
                    'errors' => $errors,
                    'updated_fields' => $updated
                ]);
            }
            break;
        
        // ========================
        // حذف إعداد معين
        // ========================
        case 'delete':
            $key = $_POST['key'] ?? '';
            
            if (empty($key)) {
                throw new Exception('يجب تحديد مفتاح الإعداد');
            }
            
            $stmt = mysqli_prepare($conn, "DELETE FROM platform_settings WHERE setting_key = ?");
            mysqli_stmt_bind_param($stmt, "s", $key);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم حذف الإعداد بنجاح'
                ]);
            } else {
                throw new Exception('فشل حذف الإعداد');
            }
            break;
        
        // ========================
        // إعادة تعيين الإعدادات للقيم الافتراضية
        // ========================
        case 'reset':
            $type = $_POST['type'] ?? 'general';
            
            $stmt = mysqli_prepare($conn, "DELETE FROM platform_settings WHERE setting_type = ?");
            mysqli_stmt_bind_param($stmt, "s", $type);
            
            if (mysqli_stmt_execute($stmt)) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم إعادة تعيين الإعدادات بنجاح'
                ]);
            } else {
                throw new Exception('فشل إعادة التعيين');
            }
            break;
        
        // ========================
        // تصدير الإعدادات
        // ========================
        case 'export':
            $settings = getSettingsByType($conn);
            
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="settings_backup_' . date('Y-m-d_H-i-s') . '.json"');
            
            echo json_encode([
                'exported_at' => date('Y-m-d H:i:s'),
                'platform' => 'منصة إبداع',
                'version' => '2.0',
                'settings' => $settings
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
            exit;
        
        // ========================
        // استيراد الإعدادات
        // ========================
        case 'import':
            if (!isset($_FILES['settings_file'])) {
                throw new Exception('يجب رفع ملف JSON');
            }
            
            $fileContent = file_get_contents($_FILES['settings_file']['tmp_name']);
            $importData = json_decode($fileContent, true);
            
            if (!$importData || !isset($importData['settings'])) {
                throw new Exception('ملف غير صالح');
            }
            
            $imported = 0;
            foreach ($importData['settings'] as $key => $value) {
                if (setSetting($conn, $key, $value, 'general', $userId)) {
                    $imported++;
                }
            }
            
            echo json_encode([
                'success' => true,
                'message' => "تم استيراد $imported إعداد بنجاح",
                'imported_count' => $imported
            ]);
            break;
        
        // ========================
        // طلب غير معروف
        // ========================
        default:
            throw new Exception('إجراء غير معروف');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
