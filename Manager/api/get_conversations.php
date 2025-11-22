<?php
/**
 * get_conversations - Protected with Central Security System
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

/**
 * حساب الوقت النسبي (منذ X دقيقة/ساعة/يوم)
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
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return "منذ {$days} يوم";
    } elseif ($diff < 2592000) {
        $weeks = floor($diff / 604800);
        return "منذ {$weeks} أسبوع";
    } else {
        return date('Y-m-d', $timestamp);
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
    // GET: جلب قائمة المحادثات
    // ==============================================
    if ($method === 'GET') {
        $search = $_GET['search'] ?? '';
        $limit = (int)($_GET['limit'] ?? 20);
        $offset = (int)($_GET['offset'] ?? 0);
        
        // تحديد الحد الأقصى للقيم
        $limit = min(max($limit, 1), 100);
        $offset = max($offset, 0);
        
        // ======================================
        // جلب المحادثات الفردية (1-to-1)
        // ======================================
        $conversations = [];
        
        // SQL query لجلب آخر رسالة مع كل مستخدم
        $sql = "
            SELECT 
                CASE 
                    WHEN m.sender_id = ? THEN m.receiver_id 
                    ELSE m.sender_id 
                END AS contact_id,
                u.full_name AS contact_name,
                u.role AS contact_role,
                (SELECT message_text 
                 FROM messages 
                 WHERE (sender_id = ? AND receiver_id = contact_id) 
                    OR (sender_id = contact_id AND receiver_id = ?)
                 ORDER BY created_at DESC 
                 LIMIT 1) AS last_message,
                MAX(m.created_at) AS last_message_time,
                (SELECT COUNT(*) 
                 FROM messages 
                 WHERE sender_id = contact_id 
                   AND receiver_id = ? 
                   AND status = 'sent') AS unread_count,
                (SELECT created_at 
                 FROM messages 
                 WHERE (sender_id = ? AND receiver_id = contact_id) 
                    OR (sender_id = contact_id AND receiver_id = ?)
                 ORDER BY created_at DESC 
                 LIMIT 1) AS actual_last_time
            FROM messages m
            JOIN users u ON (u.id = CASE 
                WHEN m.sender_id = ? THEN m.receiver_id 
                ELSE m.sender_id 
            END)
            WHERE m.sender_id = ? OR m.receiver_id = ?
        ";
        
        $params = [$user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id, $user_id];
        
        // إضافة البحث إذا وجد
        if ($search !== '') {
            $sql .= " AND u.full_name LIKE ?";
            $search_param = "%{$search}%";
            $params[] = $search_param;
        }
        
        $sql .= "
            GROUP BY contact_id, u.full_name, u.role
            ORDER BY actual_last_time DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $conn->prepare($sql);
        
        // Bind parameters dynamically
        $types = str_repeat('i', 9);
        if ($search !== '') {
            $types .= 's';
        }
        $types .= 'ii';
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        while ($row = $result->fetch_assoc()) {
            $conversations[] = [
                'type' => 'individual',
                'conversation_id' => 'user_' . $row['contact_id'],
                'contact_id' => (int)$row['contact_id'],
                'contact_name' => $row['contact_name'],
                'contact_role' => $row['contact_role'],
                'last_message' => $row['last_message'] ?? '',
                'last_message_time' => $row['actual_last_time'] ?? '',
                'unread_count' => (int)($row['unread_count'] ?? 0),
                'time_ago' => getTimeAgo($row['actual_last_time'])
            ];
        }
        
        $stmt->close();
        
        // ======================================
        // جلب المحادثات الجماعية (Group Chats)
        // ======================================
        $group_sql = "
            SELECT 
                gc.id AS group_id,
                gc.name AS group_name,
                gc.description AS group_description,
                (SELECT message_text 
                 FROM group_messages 
                 WHERE group_id = gc.id 
                 ORDER BY created_at DESC 
                 LIMIT 1) AS last_message,
                (SELECT created_at 
                 FROM group_messages 
                 WHERE group_id = gc.id 
                 ORDER BY created_at DESC 
                 LIMIT 1) AS last_message_time,
                (SELECT COUNT(DISTINCT gm2.id)
                 FROM group_messages gm2
                 LEFT JOIN group_message_reads gmr ON gm2.id = gmr.message_id AND gmr.user_id = ?
                 WHERE gm2.group_id = gc.id 
                   AND gm2.sender_id != ?
                   AND gmr.id IS NULL) AS unread_count
            FROM group_chats gc
            INNER JOIN group_chat_members gcm ON gc.id = gcm.group_id
            WHERE gcm.user_id = ?
        ";
        
        if ($search !== '') {
            $group_sql .= " AND gc.name LIKE ?";
        }
        
        $group_sql .= "
            ORDER BY last_message_time DESC
            LIMIT ? OFFSET ?
        ";
        
        $group_stmt = $conn->prepare($group_sql);
        
        if ($search !== '') {
            $group_stmt->bind_param('iiisii', $user_id, $user_id, $user_id, $search_param, $limit, $offset);
        } else {
            $group_stmt->bind_param('iiiii', $user_id, $user_id, $user_id, $limit, $offset);
        }
        
        $group_stmt->execute();
        $group_result = $group_stmt->get_result();
        
        while ($row = $group_result->fetch_assoc()) {
            $conversations[] = [
                'type' => 'group',
                'conversation_id' => 'group_' . $row['group_id'],
                'group_id' => (int)$row['group_id'],
                'group_name' => $row['group_name'],
                'group_description' => $row['group_description'],
                'last_message' => $row['last_message'] ?? '',
                'last_message_time' => $row['last_message_time'] ?? '',
                'unread_count' => (int)($row['unread_count'] ?? 0),
                'time_ago' => getTimeAgo($row['last_message_time'])
            ];
        }
        
        $group_stmt->close();
        
        // ترتيب المحادثات حسب آخر رسالة
        usort($conversations, function($a, $b) {
            return strtotime($b['last_message_time']) - strtotime($a['last_message_time']);
        });
        
        // حساب إجمالي الرسائل غير المقروءة
        $total_unread = array_sum(array_column($conversations, 'unread_count'));
        
        respond([
            'success' => true,
            'conversations' => $conversations,
            'total' => count($conversations),
            'total_unread' => $total_unread,
            'limit' => $limit,
            'offset' => $offset
        ]);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    error_log("Get Conversations Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
