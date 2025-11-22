<?php
/**
 * mark_messages_read - Protected with Central Security System
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
    // POST: تحديد رسائل كمقروءة
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        $message_ids = $data['message_ids'] ?? [];
        $contact_id = (int)($data['contact_id'] ?? 0);
        
        // ======================================
        // تحديد رسائل محددة
        // ======================================
        if (!empty($message_ids) && is_array($message_ids)) {
            
            // تحويل إلى integers
            $message_ids = array_map('intval', $message_ids);
            $message_ids = array_filter($message_ids, function($id) {
                return $id > 0;
            });
            
            if (empty($message_ids)) {
                respond(['success' => false, 'message' => 'لا توجد معرفات رسائل صالحة'], 400);
            }
            
            // تحديث الرسائل (فقط الرسائل الموجهة للمستخدم الحالي)
            $placeholders = implode(',', array_fill(0, count($message_ids), '?'));
            $sql = "UPDATE messages 
                    SET status = 'seen' 
                    WHERE id IN ($placeholders) 
                      AND receiver_id = ? 
                      AND status = 'sent'";
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('i', count($message_ids)) . 'i';
            $params = array_merge($message_ids, [$user_id]);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم تحديث حالة القراءة',
                'updated' => $affected
            ]);
        }
        
        // ======================================
        // تحديد جميع رسائل محادثة محددة
        // ======================================
        if ($contact_id > 0) {
            
            $stmt = $conn->prepare(
                "UPDATE messages 
                 SET status = 'seen' 
                 WHERE sender_id = ? 
                   AND receiver_id = ? 
                   AND status = 'sent'"
            );
            $stmt->bind_param('ii', $contact_id, $user_id);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم تحديد جميع رسائل المحادثة كمقروءة',
                'updated' => $affected
            ]);
        }
        
        respond(['success' => false, 'message' => 'يجب تحديد message_ids أو contact_id'], 400);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    error_log("Mark Messages Read Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
