<?php
/**
 * Delete Notifications API
 * Delete single notification or all notifications for current user
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    respond(['success' => false, 'message' => 'غير مصرح - يجب تسجيل الدخول'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'DELETE') {
        
        // حذف إشعار واحد
        if (isset($_GET['notification_id'])) {
            $notification_id = (int)$_GET['notification_id'];
            
            // التحقق من أن الإشعار يخص المستخدم الحالي
            $check_stmt = $conn->prepare(
                "SELECT notification_id FROM notifications WHERE notification_id = ? AND user_id = ?"
            );
            $check_stmt->bind_param('ii', $notification_id, $user_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $check_stmt->close();
                respond(['success' => false, 'message' => 'الإشعار غير موجود أو لا تملك صلاحية حذفه'], 404);
            }
            $check_stmt->close();
            
            // حذف الإشعار
            $delete_stmt = $conn->prepare(
                "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?"
            );
            $delete_stmt->bind_param('ii', $notification_id, $user_id);
            
            if ($delete_stmt->execute()) {
                $delete_stmt->close();
                respond([
                    'success' => true,
                    'message' => 'تم حذف الإشعار بنجاح'
                ]);
            } else {
                $delete_stmt->close();
                respond(['success' => false, 'message' => 'فشل حذف الإشعار'], 500);
            }
        }
        
        // حذف جميع الإشعارات
        elseif (isset($_GET['all']) && $_GET['all'] === 'true') {
            
            // التحقق من وجود إشعارات
            $count_stmt = $conn->prepare(
                "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?"
            );
            $count_stmt->bind_param('i', $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $total = $count_row['total'];
            $count_stmt->close();
            
            if ($total === 0) {
                respond([
                    'success' => true,
                    'message' => 'لا توجد إشعارات للحذف',
                    'deleted_count' => 0
                ]);
            }
            
            // حذف جميع الإشعارات
            $delete_all_stmt = $conn->prepare(
                "DELETE FROM notifications WHERE user_id = ?"
            );
            $delete_all_stmt->bind_param('i', $user_id);
            
            if ($delete_all_stmt->execute()) {
                $deleted_count = $delete_all_stmt->affected_rows;
                $delete_all_stmt->close();
                
                respond([
                    'success' => true,
                    'message' => "تم حذف {$deleted_count} إشعار بنجاح",
                    'deleted_count' => $deleted_count
                ]);
            } else {
                $delete_all_stmt->close();
                respond(['success' => false, 'message' => 'فشل حذف الإشعارات'], 500);
            }
        }
        
        else {
            respond(['success' => false, 'message' => 'يجب تحديد notification_id أو all=true'], 400);
        }
    }
    
    else {
        respond(['success' => false, 'message' => 'طريقة غير مدعومة. استخدم DELETE'], 405);
    }
    
} catch (Exception $e) {
    respond([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], 500);
}
?>
