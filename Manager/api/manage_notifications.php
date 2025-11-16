<?php
// 🔔 Advanced Notifications Management System
// Real-time notifications with Email/SMS integration, History, Preferences & Permissions

session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';

// Import PHPMailer for email sending
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../../Mailer/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../../Mailer/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../../Mailer/PHPMailer/src/SMTP.php';

header('Content-Type: application/json; charset=utf-8');

// ===== HELPER FUNCTIONS =====
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function checkAuth() {
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
    
    if (!$userId) {
        respond(['success' => false, 'message' => 'غير مصرح - يرجى تسجيل الدخول'], 401);
    }
    
    return ['user_id' => $userId, 'role' => $userRole];
}

function checkPermission($userRole, $requiredRoles) {
    if (!in_array($userRole, $requiredRoles, true)) {
        respond(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء'], 403);
    }
}

// ===== MAIN ROUTER =====
$auth = checkAuth();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'list':
        listNotifications($conn, $auth);
        break;
    
    case 'get':
        getNotification($conn, $auth);
        break;
    
    case 'create':
        checkPermission($auth['role'], ['manager', 'technical']);
        createNotification($conn, $auth);
        break;
    
    case 'update':
        checkPermission($auth['role'], ['manager', 'technical']);
        updateNotification($conn, $auth);
        break;
    
    case 'delete':
        checkPermission($auth['role'], ['manager', 'technical']);
        deleteNotification($conn, $auth);
        break;
    
    case 'mark_read':
        markAsRead($conn, $auth);
        break;
    
    case 'mark_all_read':
        markAllAsRead($conn, $auth);
        break;
    
    case 'get_unread_count':
        getUnreadCount($conn, $auth);
        break;
    
    case 'get_preferences':
        getPreferences($conn, $auth);
        break;
    
    case 'update_preferences':
        updatePreferences($conn, $auth);
        break;
    
    case 'send_bulk':
        checkPermission($auth['role'], ['manager', 'technical']);
        sendBulkNotifications($conn, $auth);
        break;
    
    case 'get_history':
        getNotificationHistory($conn, $auth);
        break;
    
    case 'get_stats':
        checkPermission($auth['role'], ['manager', 'technical']);
        getNotificationStats($conn, $auth);
        break;
    
    default:
        respond(['success' => false, 'message' => 'إجراء غير صالح'], 400);
}

// ===== ACTION HANDLERS =====

/**
 * List notifications for current user with filters
 */
function listNotifications($conn, $auth) {
    $userId = $auth['user_id'];
    $userRole = $auth['role'];
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $filter = $_GET['filter'] ?? 'all'; // all, unread, read
    $type = $_GET['type'] ?? null; // info, success, warning, error, announcement
    
    // Build WHERE clause based on role
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if (in_array($userRole, ['manager', 'technical'])) {
        // Managers/Technical can see all notifications
        $whereConditions[] = "(n.target_user_id = ? OR n.target_role IS NULL OR n.target_role = ?)";
        $params[] = $userId;
        $params[] = $userRole;
        $types .= 'is';
    } else {
        // Regular users see only their notifications
        $whereConditions[] = "n.target_user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    // Filter by read status
    if ($filter === 'unread') {
        $whereConditions[] = "n.is_read = 0";
    } elseif ($filter === 'read') {
        $whereConditions[] = "n.is_read = 1";
    }
    
    // Filter by type
    if ($type) {
        $whereConditions[] = "n.type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Get total count
    $countQuery = "SELECT COUNT(*) as total FROM notifications n $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Get notifications with pagination
    $query = "
        SELECT 
            n.id, n.title, n.message, n.type, n.priority,
            n.target_user_id, n.target_role, n.metadata,
            n.is_read, n.read_at, n.created_at, n.created_by,
            u.full_name as creator_name,
            t.full_name as target_user_name
        FROM notifications n
        LEFT JOIN users u ON n.created_by = u.id
        LEFT JOIN users t ON n.target_user_id = t.id
        $whereClause
        ORDER BY n.priority DESC, n.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $notifications = [];
    while ($row = $result->fetch_assoc()) {
        $row['metadata'] = $row['metadata'] ? json_decode($row['metadata'], true) : null;
        $notifications[] = $row;
    }
    
    respond([
        'success' => true,
        'data' => $notifications,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Get single notification details
 */
function getNotification($conn, $auth) {
    $id = intval($_GET['id'] ?? 0);
    if (!$id) {
        respond(['success' => false, 'message' => 'رقم الإشعار مطلوب'], 400);
    }
    
    $userId = $auth['user_id'];
    $userRole = $auth['role'];
    
    $query = "
        SELECT 
            n.*, 
            u.full_name as creator_name,
            t.full_name as target_user_name
        FROM notifications n
        LEFT JOIN users u ON n.created_by = u.id
        LEFT JOIN users t ON n.target_user_id = t.id
        WHERE n.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'الإشعار غير موجود'], 404);
    }
    
    $notification = $result->fetch_assoc();
    
    // Check permissions
    if (!in_array($userRole, ['manager', 'technical'])) {
        if ($notification['target_user_id'] != $userId) {
            respond(['success' => false, 'message' => 'ليس لديك صلاحية لعرض هذا الإشعار'], 403);
        }
    }
    
    $notification['metadata'] = $notification['metadata'] ? json_decode($notification['metadata'], true) : null;
    
    respond(['success' => true, 'data' => $notification]);
}

/**
 * Create new notification
 */
function createNotification($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    $type = $input['type'] ?? 'info'; // info, success, warning, error, announcement
    $priority = intval($input['priority'] ?? 1); // 1=low, 2=normal, 3=high, 4=urgent
    $targetUserId = $input['target_user_id'] ?? null;
    $targetRole = $input['target_role'] ?? null; // student, trainer, manager, technical
    $metadata = $input['metadata'] ?? [];
    $sendEmail = $input['send_email'] ?? false;
    $sendSms = $input['send_sms'] ?? false;
    
    // Validation
    if (empty($title) || empty($message)) {
        respond(['success' => false, 'message' => 'العنوان والرسالة مطلوبان'], 400);
    }
    
    if (!$targetUserId && !$targetRole) {
        respond(['success' => false, 'message' => 'يجب تحديد مستخدم أو دور مستهدف'], 400);
    }
    
    // Insert notification
    $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
    $createdBy = $auth['user_id'];
    
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (title, message, type, priority, target_user_id, target_role, metadata, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('sssiissi', $title, $message, $type, $priority, $targetUserId, $targetRole, $metadataJson, $createdBy);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل إنشاء الإشعار: ' . $stmt->error], 500);
    }
    
    $notificationId = $conn->insert_id;
    
    // Send email if requested
    if ($sendEmail) {
        sendNotificationEmail($conn, $notificationId, $targetUserId, $targetRole, $title, $message);
    }
    
    // Send SMS if requested
    if ($sendSms) {
        sendNotificationSms($conn, $notificationId, $targetUserId, $targetRole, $title, $message);
    }
    
    respond([
        'success' => true,
        'message' => 'تم إنشاء الإشعار بنجاح',
        'notification_id' => $notificationId,
        'email_sent' => $sendEmail,
        'sms_sent' => $sendSms
    ]);
}

/**
 * Update notification
 */
function updateNotification($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'رقم الإشعار مطلوب'], 400);
    }
    
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    $type = $input['type'] ?? 'info';
    $priority = intval($input['priority'] ?? 1);
    
    if (empty($title) || empty($message)) {
        respond(['success' => false, 'message' => 'العنوان والرسالة مطلوبان'], 400);
    }
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET title = ?, message = ?, type = ?, priority = ?
        WHERE id = ?
    ");
    
    $stmt->bind_param('sssii', $title, $message, $type, $priority, $id);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل تحديث الإشعار'], 500);
    }
    
    respond(['success' => true, 'message' => 'تم تحديث الإشعار بنجاح']);
}

/**
 * Delete notification
 */
function deleteNotification($conn, $auth) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'رقم الإشعار مطلوب'], 400);
    }
    
    $stmt = $conn->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل حذف الإشعار'], 500);
    }
    
    respond(['success' => true, 'message' => 'تم حذف الإشعار بنجاح']);
}

/**
 * Mark notification as read
 */
function markAsRead($conn, $auth) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'رقم الإشعار مطلوب'], 400);
    }
    
    $userId = $auth['user_id'];
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW()
        WHERE id = ? AND target_user_id = ?
    ");
    
    $stmt->bind_param('ii', $id, $userId);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل تحديث الإشعار'], 500);
    }
    
    respond(['success' => true, 'message' => 'تم تحديث حالة الإشعار']);
}

/**
 * Mark all notifications as read
 */
function markAllAsRead($conn, $auth) {
    $userId = $auth['user_id'];
    
    $stmt = $conn->prepare("
        UPDATE notifications 
        SET is_read = 1, read_at = NOW()
        WHERE target_user_id = ? AND is_read = 0
    ");
    
    $stmt->bind_param('i', $userId);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل تحديث الإشعارات'], 500);
    }
    
    $affected = $stmt->affected_rows;
    
    respond([
        'success' => true,
        'message' => "تم تحديث $affected إشعار كمقروء",
        'count' => $affected
    ]);
}

/**
 * Get unread count for current user
 */
function getUnreadCount($conn, $auth) {
    $userId = $auth['user_id'];
    
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM notifications 
        WHERE target_user_id = ? AND is_read = 0
    ");
    
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    respond([
        'success' => true,
        'unread_count' => $result['count']
    ]);
}

/**
 * Get user notification preferences
 */
function getPreferences($conn, $auth) {
    $userId = $auth['user_id'];
    
    $stmt = $conn->prepare("
        SELECT * FROM notification_preferences 
        WHERE user_id = ?
    ");
    
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        // Return default preferences
        $defaults = [
            'email_enabled' => true,
            'sms_enabled' => false,
            'push_enabled' => true,
            'announcements' => true,
            'course_updates' => true,
            'payment_reminders' => true,
            'system_alerts' => true,
            'marketing' => false
        ];
        respond(['success' => true, 'preferences' => $defaults]);
    }
    
    $preferences = $result->fetch_assoc();
    $preferences['preferences'] = json_decode($preferences['preferences'], true);
    
    respond(['success' => true, 'preferences' => $preferences]);
}

/**
 * Update user notification preferences
 */
function updatePreferences($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $userId = $auth['user_id'];
    
    $emailEnabled = intval($input['email_enabled'] ?? 1);
    $smsEnabled = intval($input['sms_enabled'] ?? 0);
    $pushEnabled = intval($input['push_enabled'] ?? 1);
    $preferences = json_encode($input['preferences'] ?? [], JSON_UNESCAPED_UNICODE);
    
    // Check if preferences exist
    $checkStmt = $conn->prepare("SELECT id FROM notification_preferences WHERE user_id = ?");
    $checkStmt->bind_param('i', $userId);
    $checkStmt->execute();
    $exists = $checkStmt->get_result()->num_rows > 0;
    
    if ($exists) {
        // Update
        $stmt = $conn->prepare("
            UPDATE notification_preferences 
            SET email_enabled = ?, sms_enabled = ?, push_enabled = ?, preferences = ?, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->bind_param('iiisi', $emailEnabled, $smsEnabled, $pushEnabled, $preferences, $userId);
    } else {
        // Insert
        $stmt = $conn->prepare("
            INSERT INTO notification_preferences 
            (user_id, email_enabled, sms_enabled, push_enabled, preferences, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, NOW(), NOW())
        ");
        $stmt->bind_param('iiiis', $userId, $emailEnabled, $smsEnabled, $pushEnabled, $preferences);
    }
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل تحديث التفضيلات'], 500);
    }
    
    respond(['success' => true, 'message' => 'تم تحديث التفضيلات بنجاح']);
}

/**
 * Send bulk notifications
 */
function sendBulkNotifications($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $title = trim($input['title'] ?? '');
    $message = trim($input['message'] ?? '');
    $type = $input['type'] ?? 'info';
    $priority = intval($input['priority'] ?? 1);
    $targetRole = $input['target_role'] ?? null;
    $targetUserIds = $input['target_user_ids'] ?? [];
    $sendEmail = $input['send_email'] ?? false;
    $sendSms = $input['send_sms'] ?? false;
    
    if (empty($title) || empty($message)) {
        respond(['success' => false, 'message' => 'العنوان والرسالة مطلوبان'], 400);
    }
    
    $created = 0;
    $createdBy = $auth['user_id'];
    
    $conn->begin_transaction();
    
    try {
        // If target_role specified, get all users with that role
        if ($targetRole) {
            $roleStmt = $conn->prepare("SELECT id FROM users WHERE role = ?");
            $roleStmt->bind_param('s', $targetRole);
            $roleStmt->execute();
            $result = $roleStmt->get_result();
            
            while ($row = $result->fetch_assoc()) {
                $targetUserIds[] = $row['id'];
            }
        }
        
        // Remove duplicates
        $targetUserIds = array_unique($targetUserIds);
        
        // Insert notification for each user
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (title, message, type, priority, target_user_id, created_by, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        foreach ($targetUserIds as $targetUserId) {
            $stmt->bind_param('sssiii', $title, $message, $type, $priority, $targetUserId, $createdBy);
            if ($stmt->execute()) {
                $created++;
            }
        }
        
        $conn->commit();
        
        respond([
            'success' => true,
            'message' => "تم إرسال $created إشعار بنجاح",
            'count' => $created
        ]);
        
    } catch (Exception $e) {
        $conn->rollback();
        respond(['success' => false, 'message' => 'فشل إرسال الإشعارات: ' . $e->getMessage()], 500);
    }
}

/**
 * Get notification history with analytics
 */
function getNotificationHistory($conn, $auth) {
    $userId = $auth['user_id'];
    $userRole = $auth['role'];
    $days = intval($_GET['days'] ?? 30);
    
    $query = "
        SELECT 
            DATE(created_at) as date,
            COUNT(*) as total,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as unread_count
        FROM notifications
        WHERE target_user_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
        GROUP BY DATE(created_at)
        ORDER BY date DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param('ii', $userId, $days);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $history = [];
    while ($row = $result->fetch_assoc()) {
        $history[] = $row;
    }
    
    respond(['success' => true, 'history' => $history]);
}

/**
 * Get notification statistics (Manager/Technical only)
 */
function getNotificationStats($conn, $auth) {
    // Overall stats
    $overallStmt = $conn->query("
        SELECT 
            COUNT(*) as total_notifications,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as total_read,
            SUM(CASE WHEN is_read = 0 THEN 1 ELSE 0 END) as total_unread,
            AVG(CASE WHEN read_at IS NOT NULL 
                THEN TIMESTAMPDIFF(SECOND, created_at, read_at) 
                ELSE NULL END) as avg_read_time_seconds
        FROM notifications
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    
    $overall = $overallStmt->fetch_assoc();
    
    // By type
    $byTypeStmt = $conn->query("
        SELECT 
            type,
            COUNT(*) as count,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
        FROM notifications
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY type
    ");
    
    $byType = [];
    while ($row = $byTypeStmt->fetch_assoc()) {
        $byType[] = $row;
    }
    
    // By role
    $byRoleStmt = $conn->query("
        SELECT 
            target_role,
            COUNT(*) as count,
            SUM(CASE WHEN is_read = 1 THEN 1 ELSE 0 END) as read_count
        FROM notifications
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY) AND target_role IS NOT NULL
        GROUP BY target_role
    ");
    
    $byRole = [];
    while ($row = $byRoleStmt->fetch_assoc()) {
        $byRole[] = $row;
    }
    
    respond([
        'success' => true,
        'stats' => [
            'overall' => $overall,
            'by_type' => $byType,
            'by_role' => $byRole
        ]
    ]);
}

// ===== EMAIL & SMS HELPER FUNCTIONS =====

/**
 * Send notification via email
 */
function sendNotificationEmail($conn, $notificationId, $targetUserId, $targetRole, $title, $message) {
    // Get target users
    $users = [];
    
    if ($targetUserId) {
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE id = ?");
        $stmt->bind_param('i', $targetUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    } elseif ($targetRole) {
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE role = ?");
        $stmt->bind_param('s', $targetRole);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    // Send email to each user
    foreach ($users as $user) {
        // Check if user has email enabled in preferences
        $prefStmt = $conn->prepare("SELECT email_enabled FROM notification_preferences WHERE user_id = ?");
        $prefStmt->bind_param('i', $user['id']);
        $prefStmt->execute();
        $prefResult = $prefStmt->get_result();
        
        if ($prefResult->num_rows > 0) {
            $pref = $prefResult->fetch_assoc();
            if (!$pref['email_enabled']) {
                continue; // Skip if email disabled
            }
        }
        
        // Generate email HTML
        $emailHtml = generateNotificationEmailHtml($user['full_name'], $title, $message);
        
        // Send using PHPMailer
        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'ha717781053@gmail.com';
            $mail->Password = 'YOUR_APP_PASSWORD'; // Update with actual password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->CharSet = 'UTF-8';
            
            $mail->setFrom('ha717781053@gmail.com', 'منصة إبداع');
            $mail->addAddress($user['email'], $user['full_name']);
            $mail->isHTML(true);
            $mail->Subject = $title;
            $mail->Body = $emailHtml;
            
            $mail->send();
        } catch (Exception $e) {
            error_log("Failed to send notification email to {$user['email']}: " . $e->getMessage());
        }
    }
}

/**
 * Send notification via SMS (placeholder)
 */
function sendNotificationSms($conn, $notificationId, $targetUserId, $targetRole, $title, $message) {
    // TODO: Integrate with SMS provider (Twilio, Nexmo, etc.)
    // For now, this is a placeholder
    
    error_log("SMS notification would be sent: $title - $message");
    
    // Example SMS integration:
    /*
    $users = [];
    if ($targetUserId) {
        $stmt = $conn->prepare("SELECT phone FROM users WHERE id = ?");
        $stmt->bind_param('i', $targetUserId);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
    }
    
    foreach ($users as $user) {
        $smsMessage = "$title: $message";
        // Send SMS using provider API
    }
    */
}

/**
 * Generate HTML email for notification
 */
function generateNotificationEmailHtml($userName, $title, $message) {
    return "
    <!DOCTYPE html>
    <html dir='rtl' lang='ar'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>$title</title>
    </head>
    <body style='margin: 0; padding: 0; font-family: Arial, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);'>
        <div style='max-width: 600px; margin: 40px auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 20px 60px rgba(0,0,0,0.3);'>
            <!-- Header -->
            <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 40px 20px; text-align: center;'>
                <h1 style='color: white; margin: 0; font-size: 28px; font-weight: bold;'>🔔 إشعار جديد</h1>
                <p style='color: rgba(255,255,255,0.9); margin: 10px 0 0 0; font-size: 14px;'>منصة إبداع للتدريب والتأهيل</p>
            </div>
            
            <!-- Content -->
            <div style='padding: 40px 30px;'>
                <p style='color: #334155; font-size: 16px; margin: 0 0 20px 0;'>
                    مرحباً <strong>$userName</strong>،
                </p>
                
                <div style='background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border-right: 4px solid #0284c7; padding: 20px; border-radius: 8px; margin-bottom: 30px;'>
                    <h2 style='color: #0c4a6e; margin: 0 0 15px 0; font-size: 20px;'>$title</h2>
                    <p style='color: #475569; line-height: 1.8; margin: 0; font-size: 15px;'>$message</p>
                </div>
                
                <div style='text-align: center; margin: 30px 0;'>
                    <a href='http://localhost/Ibdaa-Taiz/platform/' 
                       style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 40px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px; box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);'>
                        عرض التفاصيل
                    </a>
                </div>
            </div>
            
            <!-- Footer -->
            <div style='background: #f8fafc; padding: 25px 30px; border-top: 1px solid #e2e8f0; text-align: center;'>
                <p style='color: #64748b; font-size: 13px; margin: 0 0 10px 0;'>
                    منصة إبداع للتدريب والتأهيل
                </p>
                <p style='color: #94a3b8; font-size: 12px; margin: 0;'>
                    هذا إشعار تلقائي، يرجى عدم الرد على هذا البريد الإلكتروني
                </p>
            </div>
        </div>
    </body>
    </html>
    ";
}
