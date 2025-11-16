<?php
/**
 * ================================================
 * API: جلب الطلاب بانتظار تأكيد الدفع
 * ================================================
 * الهدف: جلب قائمة بالطلاب الذين تمت الموافقة عليهم
 * ولم يتم تفعيل حساباتهم بعد (payment_status='pending')
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

// التحقق من الصلاحية
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (!isset($_SESSION['user_id']) || $user_role !== 'technical') {
    echo json_encode(['success' => false, 'error' => 'غير مصرح. هذا القسم للمشرف الفني فقط.']);
    exit;
}

require_once __DIR__ . '/../../database/db.php';

try {
    // جلب الطلاب من enrollments بحالة pending
    $query = "
        SELECT 
            e.id AS enrollment_id,
            e.user_id,
            e.course_id,
            e.enrollment_date,
            u.full_name AS student_name,
            u.email AS student_email,
            u.phone AS student_phone,
            c.title AS course_title,
            c.price AS course_price
        FROM enrollments e
        INNER JOIN users u ON e.user_id = u.id
        INNER JOIN courses c ON e.course_id = c.id
        WHERE e.payment_status = 'pending'
        AND e.status = 'pending'
        ORDER BY e.enrollment_date DESC
    ";
    
    $result = $conn->query($query);
    
    if (!$result) {
        throw new Exception('فشل تنفيذ الاستعلام: ' . $conn->error);
    }
    
    $pendingPayments = [];
    while ($row = $result->fetch_assoc()) {
        $pendingPayments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $pendingPayments,
        'count' => count($pendingPayments)
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'خطأ: ' . $e->getMessage()
    ]);
}
