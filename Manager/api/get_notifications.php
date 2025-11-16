<?php
/**
 * ================================================
 * API: الحصول على الإشعارات
 * ================================================
 * الهدف: جلب الإشعارات غير المقروءة للمستخدم الحالي
 * الاستخدام: يتم استدعاؤه كل 30 ثانية من JavaScript
 * ================================================
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح. الرجاء تسجيل الدخول.']);
    exit;
}

require_once __DIR__ . '/../../database/db.php';

try {
    $user_id = intval($_SESSION['user_id']);
    
    // جلب الإشعارات غير المقروءة فقط (استخدام mysqli بدلاً من PDO)
    $stmt = $conn->prepare("
        SELECT id, message, link, created_at 
        FROM notifications 
        WHERE user_id = ? AND is_read = 0 
        ORDER BY created_at DESC
    ");
    
    // التحقق من نجاح تحضير الاستعلام
    if ($stmt === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'خطأ SQL في get_notifications (prepare): ' . $conn->error
        ]);
        exit;
    }
    
    // ربط المعاملات وتنفيذ الاستعلام
    $stmt->bind_param('i', $user_id);
    
    if ($stmt->execute() === false) {
        echo json_encode([
            'success' => false, 
            'message' => 'خطأ SQL في get_notifications (execute): ' . $stmt->error
        ]);
        exit;
    }
    
    // جلب النتائج
    $result = $stmt->get_result();
    $notifications = [];
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
    }
    
    $stmt->close();
    
    echo json_encode([
        'success' => true,
        'count' => count($notifications),
        'notifications' => $notifications
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في جلب الإشعارات: ' . $e->getMessage()
    ]);
}
