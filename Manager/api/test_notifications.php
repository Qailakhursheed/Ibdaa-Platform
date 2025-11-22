<?php
/**
 * Test Notifications API
 * اختبار API الإشعارات
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../database/db.php';

SessionSecurity::startSecureSession();

// محاكاة تسجيل دخول المدير
$_SESSION['user_id'] = 1;
$_SESSION['user_role'] = 'manager';

$response = [
    'test_info' => [
        'user_id' => $_SESSION['user_id'] ?? null,
        'user_role' => $_SESSION['user_role'] ?? null,
        'session_active' => session_status() === PHP_SESSION_ACTIVE
    ],
    'database' => [
        'connected' => false,
        'tables_exist' => []
    ],
    'notifications' => []
];

try {
    // 1. التحقق من الاتصال بقاعدة البيانات
    if ($conn->ping()) {
        $response['database']['connected'] = true;
        
        // 2. التحقق من وجود جدول notifications
        $result = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($result->num_rows > 0) {
            $response['database']['tables_exist'][] = 'notifications';
            
            // 3. جلب الإشعارات
            $stmt = $conn->prepare("
                SELECT 
                    notification_id,
                    user_id,
                    title,
                    message,
                    type,
                    link,
                    is_read,
                    created_at
                FROM notifications
                WHERE user_id = ?
                ORDER BY created_at DESC
                LIMIT 10
            ");
            
            $user_id = 1;
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $response['notifications'][] = $row;
            }
            
            $stmt->close();
            
            // 4. حساب الإشعارات غير المقروءة
            $count_stmt = $conn->prepare("SELECT COUNT(*) as total FROM notifications WHERE user_id = ? AND is_read = 0");
            $count_stmt->bind_param('i', $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $response['unread_count'] = $count_result->fetch_assoc()['total'];
            $count_stmt->close();
            
        } else {
            $response['database']['error'] = 'جدول notifications غير موجود. قم بتشغيل fix_notifications.sql';
        }
    } else {
        $response['database']['error'] = 'فشل الاتصال بقاعدة البيانات';
    }
    
    $response['success'] = true;
    $response['message'] = 'تم الاختبار بنجاح';
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['error'] = $e->getMessage();
    $response['trace'] = $e->getTraceAsString();
}

echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
