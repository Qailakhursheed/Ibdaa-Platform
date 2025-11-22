<?php
/**
 * mark_notification_read - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


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

function respond(bool $success, string $message = '', array $extra = [], int $status = 200): void {
    if ($status !== 200) {
        http_response_code($status);
    }
    $payload = array_merge(['success' => $success], $extra);
    if ($message !== '') {
        $payload[$success ? 'message' : 'error'] = $message;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
    respond(false, 'غير مصرح. الرجاء تسجيل الدخول.', [], 401);
}

require_once __DIR__ . '/../../database/db.php';

$data = json_decode(file_get_contents('php://input'), true);
if (!is_array($data)) {
    respond(false, 'تنسيق بيانات غير صالح.');
}

$notificationId = isset($data['notification_id']) ? (int) $data['notification_id'] : 0;
if ($notificationId <= 0) {
    respond(false, 'معرف الإشعار مطلوب.');
}

$userId = (int) $_SESSION['user_id'];

/** تحديث الإشعار المحدد للمستخدم الحالي */
$stmt = $conn->prepare('UPDATE notifications SET is_read = 1 WHERE id = ? AND user_id = ?');
if (!$stmt) {
    respond(false, 'تعذر تجهيز الاستعلام: ' . $conn->error);
}

$stmt->bind_param('ii', $notificationId, $userId);

if (!$stmt->execute()) {
    $stmt->close();
    respond(false, 'تعذر تحديث الإشعار: ' . $stmt->error);
}

if ($stmt->affected_rows > 0) {
    $stmt->close();
    respond(true, 'تم تمييز الإشعار كمقروء.');
}

$stmt->close();
respond(false, 'الإشعار غير موجود أو لا تملك صلاحية.');
