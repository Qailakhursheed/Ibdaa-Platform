<?php
/**
 * نظام المراسلة - Messaging System
 * Get Messages API
 * 
 * جلب رسائل محادثة محددة (فردية أو جماعية)
 * مع تحديث حالة القراءة تلقائياً
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

/**
 * حساب الوقت النسبي
 */
function getTimeAgo($datetime) {
    if (!$datetime) return '';
    
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return 'الآن';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return "منذ {$mins} دقيقة";
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return "منذ {$hours} ساعة";
    } else {
        return date('H:i', $timestamp);
    }
}

// التحقق من تسجيل الدخول
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    respond(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // ==============================================
    // GET: جلب رسائل محادثة محددة
    // ==============================================
    if ($method === 'GET') {
        $contact_id = (int)($_GET['contact_id'] ?? 0);
        $group_id = (int)($_GET['group_id'] ?? 0);
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        // تحديد الحد الأقصى
        $limit = min(max($limit, 1), 200);
        $offset = max($offset, 0);
        
        // ======================================
        // محادثة فردية (1-to-1)
        // ======================================
        if ($contact_id > 0 && $group_id === 0) {
            
            // التحقق من وجود المستخدم
            $check_stmt = $conn->prepare(
                "SELECT id, full_name, role FROM users WHERE id = ?"
            );
            $check_stmt->bind_param('i', $contact_id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            if ($check_result->num_rows === 0) {
                $check_stmt->close();
                respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            
            $contact = $check_result->fetch_assoc();
            $check_stmt->close();
            
            // جلب الرسائل
            $stmt = $conn->prepare("
                SELECT 
                    m.id,
                    m.sender_id,
                    m.receiver_id,
                    m.message_text,
                    m.status,
                    m.created_at,
                    s.full_name AS sender_name,
                    r.full_name AS receiver_name
                FROM messages m
                INNER JOIN users s ON m.sender_id = s.id
                INNER JOIN users r ON m.receiver_id = r.id
                WHERE (m.sender_id = ? AND m.receiver_id = ?) 
                   OR (m.sender_id = ? AND m.receiver_id = ?)
                ORDER BY m.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->bind_param('iiiiii', $user_id, $contact_id, $contact_id, $user_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            $message_ids_to_mark = [];
            
            while ($row = $result->fetch_assoc()) {
                $is_mine = ($row['sender_id'] == $user_id);
                
                $messages[] = [
                    'id' => (int)$row['id'],
                    'sender_id' => (int)$row['sender_id'],
                    'receiver_id' => (int)$row['receiver_id'],
                    'sender_name' => $row['sender_name'],
                    'receiver_name' => $row['receiver_name'],
                    'message_text' => $row['message_text'],
                    'status' => $row['status'],
                    'created_at' => $row['created_at'],
                    'time' => date('H:i', strtotime($row['created_at'])),
                    'time_ago' => getTimeAgo($row['created_at']),
                    'is_mine' => $is_mine
                ];
                
                // تجميع الرسائل غير المقروءة من المستخدم الآخر
                if (!$is_mine && $row['status'] === 'sent') {
                    $message_ids_to_mark[] = (int)$row['id'];
                }
            }
            
            $stmt->close();
            
            // عكس الترتيب (الأقدم أولاً للعرض)
            $messages = array_reverse($messages);
            
            // تحديث حالة الرسائل إلى "seen" تلقائياً
            if (!empty($message_ids_to_mark)) {
                $placeholders = implode(',', array_fill(0, count($message_ids_to_mark), '?'));
                $update_sql = "UPDATE messages SET status = 'seen' WHERE id IN ($placeholders)";
                $update_stmt = $conn->prepare($update_sql);
                
                $types = str_repeat('i', count($message_ids_to_mark));
                $update_stmt->bind_param($types, ...$message_ids_to_mark);
                $update_stmt->execute();
                $update_stmt->close();
            }
            
            respond([
                'success' => true,
                'messages' => $messages,
                'total' => count($messages),
                'contact' => [
                    'id' => (int)$contact['id'],
                    'name' => $contact['full_name'],
                    'role' => $contact['role']
                ],
                'marked_as_read' => count($message_ids_to_mark)
            ]);
        }
        
        // ======================================
        // محادثة جماعية (Group Chat)
        // ======================================
        if ($group_id > 0 && $contact_id === 0) {
            
            // التحقق من وجود المجموعة والعضوية
            $check_group = $conn->prepare("
                SELECT gc.id, gc.name, gc.description
                FROM group_chats gc
                INNER JOIN group_chat_members gcm ON gc.id = gcm.group_id
                WHERE gc.id = ? AND gcm.user_id = ?
            ");
            $check_group->bind_param('ii', $group_id, $user_id);
            $check_group->execute();
            $group_result = $check_group->get_result();
            
            if ($group_result->num_rows === 0) {
                $check_group->close();
                respond(['success' => false, 'message' => 'المجموعة غير موجودة أو أنت لست عضواً'], 404);
            }
            
            $group = $group_result->fetch_assoc();
            $check_group->close();
            
            // جلب رسائل المجموعة
            $stmt = $conn->prepare("
                SELECT 
                    gm.id,
                    gm.sender_id,
                    gm.message_text,
                    gm.created_at,
                    u.full_name AS sender_name,
                    u.role AS sender_role,
                    EXISTS(
                        SELECT 1 FROM group_message_reads gmr 
                        WHERE gmr.message_id = gm.id AND gmr.user_id = ?
                    ) AS is_read_by_me
                FROM group_messages gm
                INNER JOIN users u ON gm.sender_id = u.id
                WHERE gm.group_id = ?
                ORDER BY gm.created_at DESC
                LIMIT ? OFFSET ?
            ");
            
            $stmt->bind_param('iiii', $user_id, $group_id, $limit, $offset);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $messages = [];
            $message_ids_to_mark = [];
            
            while ($row = $result->fetch_assoc()) {
                $is_mine = ($row['sender_id'] == $user_id);
                
                $messages[] = [
                    'id' => (int)$row['id'],
                    'sender_id' => (int)$row['sender_id'],
                    'sender_name' => $row['sender_name'],
                    'sender_role' => $row['sender_role'],
                    'message_text' => $row['message_text'],
                    'created_at' => $row['created_at'],
                    'time' => date('H:i', strtotime($row['created_at'])),
                    'time_ago' => getTimeAgo($row['created_at']),
                    'is_mine' => $is_mine,
                    'is_read' => (bool)$row['is_read_by_me']
                ];
                
                // تجميع الرسائل غير المقروءة
                if (!$is_mine && !$row['is_read_by_me']) {
                    $message_ids_to_mark[] = (int)$row['id'];
                }
            }
            
            $stmt->close();
            
            // عكس الترتيب
            $messages = array_reverse($messages);
            
            // تحديث حالة القراءة للرسائل الجماعية
            if (!empty($message_ids_to_mark)) {
                $insert_reads = $conn->prepare(
                    "INSERT IGNORE INTO group_message_reads (message_id, user_id, read_at) 
                     VALUES (?, ?, NOW())"
                );
                
                foreach ($message_ids_to_mark as $msg_id) {
                    $insert_reads->bind_param('ii', $msg_id, $user_id);
                    $insert_reads->execute();
                }
                
                $insert_reads->close();
            }
            
            // جلب أعضاء المجموعة
            $members_stmt = $conn->prepare("
                SELECT u.id, u.full_name, u.role
                FROM group_chat_members gcm
                INNER JOIN users u ON gcm.user_id = u.id
                WHERE gcm.group_id = ?
            ");
            $members_stmt->bind_param('i', $group_id);
            $members_stmt->execute();
            $members_result = $members_stmt->get_result();
            
            $members = [];
            while ($member = $members_result->fetch_assoc()) {
                $members[] = [
                    'id' => (int)$member['id'],
                    'name' => $member['full_name'],
                    'role' => $member['role']
                ];
            }
            $members_stmt->close();
            
            respond([
                'success' => true,
                'messages' => $messages,
                'total' => count($messages),
                'group' => [
                    'id' => (int)$group['id'],
                    'name' => $group['name'],
                    'description' => $group['description'],
                    'members' => $members,
                    'members_count' => count($members)
                ],
                'marked_as_read' => count($message_ids_to_mark)
            ]);
        }
        
        respond(['success' => false, 'message' => 'يجب تحديد contact_id أو group_id'], 400);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    error_log("Get Messages Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
