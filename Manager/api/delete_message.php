<?php
/**
 * delete_message - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * إرسال استجابة JSON
 */
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من تسجيل الدخول
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    respond(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==============================================
    // DELETE: حذف رسالة أو محادثة
    // ==============================================
    if ($method === 'DELETE') {
        $message_id = (int)($_GET['message_id'] ?? 0);
        $contact_id = (int)($_GET['contact_id'] ?? 0);
        $group_message_id = (int)($_GET['group_message_id'] ?? 0);
        
        // ======================================
        // حذف رسالة فردية محددة
        // ======================================
        if ($message_id > 0) {
            
            // التحقق من أن المستخدم هو المرسل
            $check_stmt = $conn->prepare(
                "SELECT id, sender_id FROM messages WHERE id = ?"
            );
            $check_stmt->bind_param('i', $message_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $check_stmt->close();
                respond(['success' => false, 'message' => 'الرسالة غير موجودة'], 404);
            }
            
            $message = $check_result->fetch_assoc();
            $check_stmt->close();
            
            // يمكن للمرسل فقط حذف رسالته
            if ((int)$message['sender_id'] !== $user_id) {
                respond(['success' => false, 'message' => 'لا يمكنك حذف رسائل الآخرين'], 403);
            }
            
            // حذف الرسالة
            $delete_stmt = $conn->prepare("DELETE FROM messages WHERE id = ?");
            $delete_stmt->bind_param('i', $message_id);
            $delete_stmt->execute();
            
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            
            if ($affected === 0) {
                respond(['success' => false, 'message' => 'فشل حذف الرسالة'], 500);
            }
            
            respond([
                'success' => true,
                'message' => 'تم حذف الرسالة بنجاح'
            ]);
        }
        
        // ======================================
        // حذف محادثة كاملة مع مستخدم
        // ======================================
        if ($contact_id > 0) {
            
            // حذف جميع الرسائل المتبادلة (المرسلة والمستقبلة)
            $delete_stmt = $conn->prepare(
                "DELETE FROM messages 
                 WHERE (sender_id = ? AND receiver_id = ?) 
                    OR (sender_id = ? AND receiver_id = ?)"
            );
            $delete_stmt->bind_param('iiii', $user_id, $contact_id, $contact_id, $user_id);
            $delete_stmt->execute();
            
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم حذف المحادثة بنجاح',
                'deleted_messages' => $affected
            ]);
        }
        
        // ======================================
        // حذف رسالة جماعية
        // ======================================
        if ($group_message_id > 0) {
            
            // التحقق من أن المستخدم هو المرسل
            $check_stmt = $conn->prepare(
                "SELECT id, sender_id FROM group_messages WHERE id = ?"
            );
            $check_stmt->bind_param('i', $group_message_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $check_stmt->close();
                respond(['success' => false, 'message' => 'الرسالة غير موجودة'], 404);
            }
            
            $message = $check_result->fetch_assoc();
            $check_stmt->close();
            
            if ((int)$message['sender_id'] !== $user_id) {
                respond(['success' => false, 'message' => 'لا يمكنك حذف رسائل الآخرين'], 403);
            }
            
            // حذف سجلات القراءة أولاً
            $delete_reads = $conn->prepare("DELETE FROM group_message_reads WHERE message_id = ?");
            $delete_reads->bind_param('i', $group_message_id);
            $delete_reads->execute();
            $delete_reads->close();
            
            // ثم حذف الرسالة
            $delete_stmt = $conn->prepare("DELETE FROM group_messages WHERE id = ?");
            $delete_stmt->bind_param('i', $group_message_id);
            $delete_stmt->execute();
            
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            
            if ($affected === 0) {
                respond(['success' => false, 'message' => 'فشل حذف الرسالة'], 500);
            }
            
            respond([
                'success' => true,
                'message' => 'تم حذف الرسالة الجماعية بنجاح'
            ]);
        }
        
        respond(['success' => false, 'message' => 'يجب تحديد message_id أو contact_id أو group_message_id'], 400);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    error_log("Delete Message Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
