<?php
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

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// تحسين التحقق من الجلسة - للمشرف الفني فقط
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || $user_role !== 'technical') {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك - هذا القسم للمشرف الفني فقط'], JSON_UNESCAPED_UNICODE);
    exit;
}

$status = $_GET['status'] ?? 'pending';
$allowed = ['pending','approved','rejected','all'];
if (!in_array($status, $allowed)) $status = 'pending';

try {
    // استعلام أفضل مع معالجة الأخطاء
    if ($status === 'all') {
        $stmt = $conn->prepare("SELECT * FROM requests ORDER BY request_date DESC LIMIT 500");
        $stmt->execute();
        $res = $stmt->get_result();
    } else {
        $stmt = $conn->prepare("SELECT * FROM requests WHERE status = ? ORDER BY request_date DESC LIMIT 500");
        $stmt->bind_param('s', $status);
        $stmt->execute();
        
        // التحقق من أخطاء SQL
        if ($stmt->error) {
            echo json_encode(['success'=>false,'message'=>'خطأ SQL: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $res = $stmt->get_result();
    }

    $out = [];
    if ($res) {
        while ($r = $res->fetch_assoc()) {
            $out[] = $r;
        }
    }
    
    // التحقق من وجود نتائج
    if (empty($out)) {
        echo json_encode([
            'success'=>true,
            'data'=>[],
            'message'=>'لا توجد طلبات معلقة (تحقق 2)',
            'count'=>0
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success'=>true,
            'data'=>$out,
            'count'=>count($out)
        ], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success'=>false,
        'message'=>'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>