<?php
/**
 * send_message - Protected with Central Security System
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
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    respond(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==============================================
    // POST: إرسال رسالة جديدة
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        $receiver_id = (int)($data['receiver_id'] ?? 0);
        $group_id = (int)($data['group_id'] ?? 0);
        $message_text = trim($data['message_text'] ?? '');
        $attachment = $data['attachment'] ?? null;
        
        // التحقق من وجود نص الرسالة
        if ($message_text === '') {
            respond(['success' => false, 'message' => 'نص الرسالة فارغ'], 400);
        }
        
        // التحقق من طول الرسالة (حد أقصى 5000 حرف)
        if (mb_strlen($message_text) > 5000) {
            respond(['success' => false, 'message' => 'الرسالة طويلة جداً (الحد الأقصى 5000 حرف)'], 400);
        }
        
        // ======================================
        // رسالة فردية (1-to-1)
        // ======================================
        if ($receiver_id > 0 && $group_id === 0) {
            
            // التحقق من وجود المستلم
            $check_stmt = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
            $check_stmt->bind_param('i', $receiver_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $check_stmt->close();
                respond(['success' => false, 'message' => 'المستخدم المستلم غير موجود'], 404);
            }
            
            $receiver = $check_result->fetch_assoc();
            $check_stmt->close();
            
            // منع إرسال رسالة لنفسك
            if ($receiver_id === $user_id) {
                respond(['success' => false, 'message' => 'لا يمكنك إرسال رسالة لنفسك'], 400);
            }
            
            // إدراج الرسالة
            $stmt = $conn->prepare(
                "INSERT INTO messages (sender_id, receiver_id, message_text, status, created_at) 
                 VALUES (?, ?, ?, 'sent', NOW())"
            );
            $stmt->bind_param('iis', $user_id, $receiver_id, $message_text);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إرسال الرسالة'], 500);
            }
            
            $message_id = $stmt->insert_id;
            $stmt->close();
            
            // إنشاء إشعار للمستلم
            $notif_stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
                 VALUES (?, 'رسالة جديدة', ?, 'info', '/Manager/messages.php', 0)"
            );
            $sender_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'مستخدم';
            $notif_message = "رسالة جديدة من {$sender_name}";
            $notif_stmt->bind_param('is', $receiver_id, $notif_message);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم إرسال الرسالة بنجاح',
                'message_id' => $message_id,
                'receiver_name' => $receiver['full_name'],
                'created_at' => date('Y-m-d H:i:s')
            ], 201);
        }
        
        // ======================================
        // رسالة جماعية (Group Message)
        // ======================================
        if ($group_id > 0 && $receiver_id === 0) {
            
            // التحقق من وجود المجموعة
            $check_group = $conn->prepare("SELECT id, name FROM group_chats WHERE id = ?");
            $check_group->bind_param('i', $group_id);
            $check_group->execute();
            $group_result = $check_group->get_result();
            
            if ($group_result->num_rows === 0) {
                $check_group->close();
                respond(['success' => false, 'message' => 'المجموعة غير موجودة'], 404);
            }
            
            $group = $group_result->fetch_assoc();
            $check_group->close();
            
            // التحقق من عضوية المرسل في المجموعة
            $member_check = $conn->prepare(
                "SELECT id FROM group_chat_members WHERE group_id = ? AND user_id = ?"
            );
            $member_check->bind_param('ii', $group_id, $user_id);
            $member_check->execute();
            $member_result = $member_check->get_result();
            
            if ($member_result->num_rows === 0) {
                $member_check->close();
                respond(['success' => false, 'message' => 'أنت لست عضواً في هذه المجموعة'], 403);
            }
            $member_check->close();
            
            // إدراج الرسالة الجماعية
            $stmt = $conn->prepare(
                "INSERT INTO group_messages (group_id, sender_id, message_text, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->bind_param('iis', $group_id, $user_id, $message_text);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إرسال الرسالة'], 500);
            }
            
            $message_id = $stmt->insert_id;
            $stmt->close();
            
            // إنشاء إشعارات لجميع أعضاء المجموعة (ماعدا المرسل)
            $members_stmt = $conn->prepare(
                "SELECT user_id FROM group_chat_members WHERE group_id = ? AND user_id != ?"
            );
            $members_stmt->bind_param('ii', $group_id, $user_id);
            $members_stmt->execute();
            $members_result = $members_stmt->get_result();
            
            $notif_insert = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
                 VALUES (?, 'رسالة جماعية جديدة', ?, 'info', '/Manager/messages.php', 0)"
            );
            
            $sender_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'مستخدم';
            $notif_message = "رسالة جديدة في {$group['name']} من {$sender_name}";
            
            while ($member = $members_result->fetch_assoc()) {
                $member_user_id = (int)$member['user_id'];
                $notif_insert->bind_param('is', $member_user_id, $notif_message);
                $notif_insert->execute();
            }
            
            $members_stmt->close();
            $notif_insert->close();
            
            respond([
                'success' => true,
                'message' => 'تم إرسال الرسالة للمجموعة بنجاح',
                'message_id' => $message_id,
                'group_name' => $group['name'],
                'created_at' => date('Y-m-d H:i:s')
            ], 201);
        }
        
        // إذا لم يتم تحديد مستلم أو مجموعة
        respond(['success' => false, 'message' => 'يجب تحديد مستلم أو مجموعة'], 400);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    error_log("Send Message Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
