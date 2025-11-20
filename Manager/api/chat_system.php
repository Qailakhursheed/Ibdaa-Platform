<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * نظام الدردشة الكامل - Complete Chat System
 * يدعم: إرسال الرسائل، جلب المحادثات، البحث، الإشعارات الفورية
 */

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    respond(['success' => false, 'message' => 'غير مصرح - يجب تسجيل الدخول'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // ==============================================
    // GET: جلب المحادثات والرسائل
    // ==============================================
    if ($method === 'GET') {
        
        // جلب قائمة المحادثات (Conversations List)
        if ($action === 'conversations') {
            $sql = "
                SELECT 
                    m.message_id,
                    m.sender_id,
                    m.recipient_id,
                    m.body as message,
                    m.is_read,
                    m.created_at,
                    CASE 
                        WHEN m.sender_id = ? THEN u_receiver.full_name
                        ELSE u_sender.full_name
                    END AS contact_name,
                    CASE 
                        WHEN m.sender_id = ? THEN m.recipient_id
                        ELSE m.sender_id
                    END AS contact_id,
                    CASE 
                        WHEN m.sender_id = ? THEN u_receiver.photo_path
                        ELSE u_sender.photo_path
                    END AS contact_picture,
                    CASE 
                        WHEN m.sender_id = ? THEN u_receiver.role
                        ELSE u_sender.role
                    END AS contact_role
                FROM messages m
                INNER JOIN users u_sender ON m.sender_id = u_sender.id
                INNER JOIN users u_receiver ON m.recipient_id = u_receiver.id
                WHERE m.sender_id = ? OR m.recipient_id = ?
                ORDER BY m.created_at DESC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiiiii', $user_id, $user_id, $user_id, $user_id, $user_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $conversations = [];
            $seen_contacts = [];
            
            while ($row = $result->fetch_assoc()) {
                $contact_id = $row['contact_id'];
                
                // إظهار آخر رسالة فقط لكل محادثة
                if (!isset($seen_contacts[$contact_id])) {
                    $unread_count = 0;
                    
                    // حساب الرسائل غير المقروءة
                    $unread_stmt = $conn->prepare(
                        "SELECT COUNT(*) as unread FROM messages 
                         WHERE sender_id = ? AND recipient_id = ? AND is_read = 0"
                    );
                    $unread_stmt->bind_param('ii', $contact_id, $user_id);
                    $unread_stmt->execute();
                    $unread_result = $unread_stmt->get_result();
                    $unread_row = $unread_result->fetch_assoc();
                    $unread_count = $unread_row['unread'];
                    $unread_stmt->close();
                    
                    $conversations[] = [
                        'contact_id' => (int)$contact_id,
                        'contact_name' => $row['contact_name'],
                        'contact_picture' => $row['contact_picture'],
                        'contact_role' => $row['contact_role'],
                        'last_message' => $row['message'],
                        'last_message_time' => $row['created_at'],
                        'is_read' => (bool)$row['is_read'],
                        'unread_count' => (int)$unread_count,
                        'is_sender' => ($row['sender_id'] == $user_id)
                    ];
                    
                    $seen_contacts[$contact_id] = true;
                }
            }
            
            $stmt->close();
            respond(['success' => true, 'conversations' => $conversations, 'total' => count($conversations)]);
        }
        
        // جلب رسائل محادثة معينة (Get Messages with Contact)
        if ($action === 'messages') {
            $contact_id = (int)($_GET['contact_id'] ?? 0);
            
            if ($contact_id <= 0) {
                respond(['success' => false, 'message' => 'معرّف المستخدم مطلوب'], 400);
            }
            
            $sql = "
                SELECT 
                    m.message_id,
                    m.sender_id,
                    m.recipient_id,
                    m.body as message,
                    m.is_read,
                    m.created_at,
                    u_sender.full_name AS sender_name,
                    u_sender.photo_path AS sender_picture
                FROM messages m
                INNER JOIN users u_sender ON m.sender_id = u_sender.id
                WHERE (m.sender_id = ? AND m.recipient_id = ?)
                   OR (m.sender_id = ? AND m.recipient_id = ?)
                ORDER BY m.created_at ASC
            ";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('iiii', $user_id, $contact_id, $contact_id, $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            while ($row = $result->fetch_assoc()) {
                $messages[] = [
                    'message_id' => (int)$row['message_id'],
                    'sender_id' => (int)$row['sender_id'],
                    'recipient_id' => (int)$row['recipient_id'],
                    'message' => $row['message'],
                    'is_read' => (bool)$row['is_read'],
                    'created_at' => $row['created_at'],
                    'sender_name' => $row['sender_name'],
                    'sender_picture' => $row['sender_picture'],
                    'is_own_message' => ($row['sender_id'] == $user_id)
                ];
            }
            
            $stmt->close();
            
            // تحديث حالة القراءة للرسائل المستلمة
            $update_stmt = $conn->prepare(
                "UPDATE messages SET is_read = 1 
                 WHERE sender_id = ? AND recipient_id = ? AND is_read = 0"
            );
            $update_stmt->bind_param('ii', $contact_id, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // جلب بيانات المستخدم الآخر
            $contact_stmt = $conn->prepare(
                "SELECT id, full_name, email, phone, role, photo_path as profile_picture 
                 FROM users WHERE id = ?"
            );
            $contact_stmt->bind_param('i', $contact_id);
            $contact_stmt->execute();
            $contact_result = $contact_stmt->get_result();
            $contact_info = $contact_result->fetch_assoc();
            $contact_stmt->close();
            
            respond([
                'success' => true, 
                'messages' => $messages, 
                'total' => count($messages),
                'contact_info' => $contact_info
            ]);
        }
        
        // جلب عدد الرسائل غير المقروءة
        if ($action === 'unread_count') {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) as unread FROM messages WHERE recipient_id = ? AND is_read = 0"
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            respond(['success' => true, 'unread_count' => (int)$row['unread']]);
        }
        
        // البحث عن مستخدمين للدردشة معهم
        if ($action === 'search_users') {
            $search = $_GET['search'] ?? '';
            $search = '%' . $search . '%';
            
            $stmt = $conn->prepare(
                "SELECT id, full_name, email, role, profile_picture 
                 FROM users 
                 WHERE id != ? AND (full_name LIKE ? OR email LIKE ?)
                 LIMIT 20"
            );
            $stmt->bind_param('iss', $user_id, $search, $search);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $users = [];
            while ($row = $result->fetch_assoc()) {
                $users[] = [
                    'id' => (int)$row['id'],
                    'full_name' => $row['full_name'],
                    'email' => $row['email'],
                    'role' => $row['role'],
                    'profile_picture' => $row['profile_picture']
                ];
            }
            
            $stmt->close();
            respond(['success' => true, 'users' => $users, 'total' => count($users)]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // POST: إرسال رسالة جديدة
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        if ($action === 'send') {
            $receiver_id = (int)($data['receiver_id'] ?? 0);
            $message = trim($data['message'] ?? '');
            
            if ($receiver_id <= 0 || $message === '') {
                respond(['success' => false, 'message' => 'المستلم والرسالة مطلوبان'], 400);
            }
            
            // التحقق من وجود المستلم
            $check = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
            $check->bind_param('i', $receiver_id);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows === 0) {
                $check->close();
                respond(['success' => false, 'message' => 'المستخدم المستلم غير موجود'], 404);
            }
            $check->close();
            
            // إدراج الرسالة
            $stmt = $conn->prepare(
                "INSERT INTO messages (sender_id, recipient_id, body, is_read, created_at) 
                 VALUES (?, ?, ?, 0, NOW())"
            );
            $stmt->bind_param('iis', $user_id, $receiver_id, $message);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إرسال الرسالة'], 500);
            }
            
            $message_id = $stmt->insert_id;
            $stmt->close();
            
            // إنشاء إشعار للمستلم
            $notif_stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
                 VALUES (?, 'رسالة جديدة', ?, 'info', '/messages', 0)"
            );
            $sender_name = $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? 'مستخدم';
            $notif_message = "رسالة جديدة من {$sender_name}";
            $notif_stmt->bind_param('is', $receiver_id, $notif_message);
            $notif_stmt->execute();
            $notif_stmt->close();
            
            respond([
                'success' => true, 
                'message' => 'تم إرسال الرسالة بنجاح',
                'message_id' => $message_id,
                'created_at' => date('Y-m-d H:i:s')
            ], 201);
        }
        
        // تحديث حالة القراءة
        if ($action === 'mark_read') {
            $message_ids = $data['message_ids'] ?? $data['chat_ids'] ?? [];
            
            if (!is_array($message_ids) || empty($message_ids)) {
                respond(['success' => false, 'message' => 'معرفات الرسائل مطلوبة'], 400);
            }
            
            $placeholders = implode(',', array_fill(0, count($message_ids), '?'));
            $sql = "UPDATE messages SET is_read = 1 WHERE recipient_id = ? AND message_id IN ($placeholders)";
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('i', count($message_ids) + 1);
            $params = array_merge([$user_id], $message_ids);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            respond(['success' => true, 'message' => 'تم تحديث حالة القراءة', 'updated' => $affected]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // DELETE: حذف رسالة
    // ==============================================
    if ($method === 'DELETE') {
        $message_id = (int)($_GET['message_id'] ?? $_GET['chat_id'] ?? 0);
        
        if ($message_id <= 0) {
            respond(['success' => false, 'message' => 'معرّف الرسالة مطلوب'], 400);
        }
        
        // التحقق من أن المستخدم هو المرسل
        $stmt = $conn->prepare(
            "DELETE FROM messages WHERE message_id = ? AND sender_id = ?"
        );
        $stmt->bind_param('ii', $message_id, $user_id);
        $stmt->execute();
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected === 0) {
            respond(['success' => false, 'message' => 'الرسالة غير موجودة أو ليس لديك صلاحية'], 404);
        }
        
        respond(['success' => true, 'message' => 'تم حذف الرسالة']);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
