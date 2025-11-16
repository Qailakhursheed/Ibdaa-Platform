<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * نظام الإشعارات المتقدم - Advanced Notifications System
 * يدعم: إشعارات فورية، تصنيف، روابط، إشعارات النظام
 */

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
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // ==============================================
    // GET: جلب الإشعارات
    // ==============================================
    if ($method === 'GET') {
        
        // جلب جميع الإشعارات
        if ($action === 'all' || $action === '') {
            $limit = (int)($_GET['limit'] ?? 50);
            $offset = (int)($_GET['offset'] ?? 0);
            $type_filter = $_GET['type'] ?? '';
            
            $sql = "
                SELECT 
                    notification_id,
                    user_id,
                    title,
                    message,
                    type,
                    link,
                    is_read,
                    created_at
                FROM notifications
                WHERE user_id = ?
            ";
            
            $params = [$user_id];
            $types = 'i';
            
            if ($type_filter !== '') {
                $sql .= " AND type = ?";
                $params[] = $type_filter;
                $types .= 's';
            }
            
            $sql .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            $types .= 'ii';
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $notifications = [];
            while ($row = $result->fetch_assoc()) {
                $notifications[] = [
                    'notification_id' => (int)$row['notification_id'],
                    'title' => $row['title'],
                    'message' => $row['message'],
                    'type' => $row['type'],
                    'link' => $row['link'],
                    'is_read' => (bool)$row['is_read'],
                    'created_at' => $row['created_at'],
                    'time_ago' => getTimeAgo($row['created_at'])
                ];
            }
            
            $stmt->close();
            
            // حساب الإجمالي
            $count_stmt = $conn->prepare(
                "SELECT COUNT(*) as total FROM notifications WHERE user_id = ?"
            );
            $count_stmt->bind_param('i', $user_id);
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $count_row = $count_result->fetch_assoc();
            $total = $count_row['total'];
            $count_stmt->close();
            
            respond([
                'success' => true, 
                'notifications' => $notifications,
                'total' => (int)$total,
                'limit' => $limit,
                'offset' => $offset
            ]);
        }
        
        // جلب عدد الإشعارات غير المقروءة
        if ($action === 'unread_count') {
            $stmt = $conn->prepare(
                "SELECT COUNT(*) as unread FROM notifications WHERE user_id = ? AND is_read = 0"
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            $stmt->close();
            
            respond(['success' => true, 'unread_count' => (int)$row['unread']]);
        }
        
        // جلب الإشعارات حسب النوع
        if ($action === 'by_type') {
            $stmt = $conn->prepare(
                "SELECT type, COUNT(*) as count, SUM(is_read = 0) as unread 
                 FROM notifications 
                 WHERE user_id = ? 
                 GROUP BY type"
            );
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $stats = [];
            while ($row = $result->fetch_assoc()) {
                $stats[] = [
                    'type' => $row['type'],
                    'count' => (int)$row['count'],
                    'unread' => (int)$row['unread']
                ];
            }
            
            $stmt->close();
            respond(['success' => true, 'statistics' => $stats]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // POST: إنشاء أو تحديث إشعارات
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        // إنشاء إشعار جديد (للمديرين فقط)
        if ($action === 'create') {
            $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
            
            if (!in_array($user_role, ['manager', 'technical'], true)) {
                respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
            }
            
            $target_user_id = (int)($data['user_id'] ?? 0);
            $title = trim($data['title'] ?? '');
            $message = trim($data['message'] ?? '');
            $type = $data['type'] ?? 'info';
            $link = $data['link'] ?? null;
            
            if ($target_user_id <= 0 || $title === '' || $message === '') {
                respond(['success' => false, 'message' => 'البيانات غير كاملة'], 400);
            }
            
            $allowed_types = ['info', 'success', 'warning', 'error', 'payment', 'enrollment', 'card', 'announcement'];
            if (!in_array($type, $allowed_types, true)) {
                $type = 'info';
            }
            
            $stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
                 VALUES (?, ?, ?, ?, ?, 0)"
            );
            $stmt->bind_param('issss', $target_user_id, $title, $message, $type, $link);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إنشاء الإشعار'], 500);
            }
            
            $notification_id = $stmt->insert_id;
            $stmt->close();
            
            respond([
                'success' => true, 
                'message' => 'تم إنشاء الإشعار بنجاح',
                'notification_id' => $notification_id
            ], 201);
        }
        
        // إنشاء إشعار جماعي (Broadcast)
        if ($action === 'broadcast') {
            $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
            
            if ($user_role !== 'manager') {
                respond(['success' => false, 'message' => 'غير مصرح لك - المدير فقط'], 403);
            }
            
            $title = trim($data['title'] ?? '');
            $message = trim($data['message'] ?? '');
            $type = $data['type'] ?? 'announcement';
            $link = $data['link'] ?? null;
            $target_role = $data['target_role'] ?? 'all'; // all, student, trainer, technical
            
            if ($title === '' || $message === '') {
                respond(['success' => false, 'message' => 'العنوان والرسالة مطلوبان'], 400);
            }
            
            // جلب المستخدمين المستهدفين
            if ($target_role === 'all') {
                $users_stmt = $conn->prepare("SELECT id FROM users WHERE id != ?");
                $users_stmt->bind_param('i', $user_id);
            } else {
                $users_stmt = $conn->prepare("SELECT id FROM users WHERE role = ? AND id != ?");
                $users_stmt->bind_param('si', $target_role, $user_id);
            }
            
            $users_stmt->execute();
            $users_result = $users_stmt->get_result();
            
            $created_count = 0;
            $insert_stmt = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link, is_read) 
                 VALUES (?, ?, ?, ?, ?, 0)"
            );
            
            while ($user_row = $users_result->fetch_assoc()) {
                $target_id = (int)$user_row['id'];
                $insert_stmt->bind_param('issss', $target_id, $title, $message, $type, $link);
                if ($insert_stmt->execute()) {
                    $created_count++;
                }
            }
            
            $users_stmt->close();
            $insert_stmt->close();
            
            respond([
                'success' => true, 
                'message' => "تم إرسال الإشعار إلى {$created_count} مستخدم",
                'created_count' => $created_count
            ]);
        }
        
        // تحديث حالة القراءة
        if ($action === 'mark_read') {
            $notification_ids = $data['notification_ids'] ?? [];
            
            if (!is_array($notification_ids) || empty($notification_ids)) {
                respond(['success' => false, 'message' => 'معرفات الإشعارات مطلوبة'], 400);
            }
            
            $placeholders = implode(',', array_fill(0, count($notification_ids), '?'));
            $sql = "UPDATE notifications SET is_read = 1 
                    WHERE user_id = ? AND notification_id IN ($placeholders)";
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('i', count($notification_ids) + 1);
            $params = array_merge([$user_id], $notification_ids);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            respond(['success' => true, 'message' => 'تم تحديث حالة القراءة', 'updated' => $affected]);
        }
        
        // تحديد جميع الإشعارات كمقروءة
        if ($action === 'mark_all_read') {
            $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?");
            $stmt->bind_param('i', $user_id);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            respond(['success' => true, 'message' => 'تم تحديد جميع الإشعارات كمقروءة', 'updated' => $affected]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // DELETE: حذف إشعارات
    // ==============================================
    if ($method === 'DELETE') {
        $notification_id = (int)($_GET['notification_id'] ?? 0);
        
        if ($notification_id <= 0) {
            respond(['success' => false, 'message' => 'معرّف الإشعار مطلوب'], 400);
        }
        
        $stmt = $conn->prepare(
            "DELETE FROM notifications WHERE notification_id = ? AND user_id = ?"
        );
        $stmt->bind_param('ii', $notification_id, $user_id);
        $stmt->execute();
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected === 0) {
            respond(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
        }
        
        respond(['success' => true, 'message' => 'تم حذف الإشعار']);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}

/**
 * حساب الوقت النسبي
 */
function getTimeAgo($datetime) {
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
    } else {
        return date('Y-m-d', $timestamp);
    }
}
