<?php
/**
 * ====================================================================
 * Enhanced Announcements API with Media Support
 * API الإعلانات المطور مع دعم الوسائط المتعددة
 * ====================================================================
 * Features:
 * - Upload images and videos
 * - Display on website control
 * - Priority and pinning
 * - Expiration dates
 * - Full CRUD operations
 * ====================================================================
 */

require_once '../../database/db.php';
require_once '../../includes/session_security.php';

SessionSecurity::startSecureSession();
header('Content-Type: application/json; charset=utf-8');

// Check permissions - Manager and Technical only
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'غير مصرح - يتطلب صلاحيات مدير أو مشرف فني']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';
$userId = $_SESSION['user_id'];

try {
    switch ($action) {
        case 'list':
            listAnnouncements($conn);
            break;
        case 'get':
            getAnnouncement($conn);
            break;
        case 'create':
            createAnnouncement($conn, $userId);
            break;
        case 'update':
            updateAnnouncement($conn, $userId);
            break;
        case 'delete':
            deleteAnnouncement($conn);
            break;
        case 'upload_media':
            uploadMedia();
            break;
        case 'toggle_pin':
            togglePin($conn);
            break;
        case 'toggle_website':
            toggleWebsiteDisplay($conn);
            break;
        default:
            throw new Exception('إجراء غير صالح');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

/**
 * List all announcements with media info
 */
function listAnnouncements($conn) {
    $stmt = $conn->query("
        SELECT 
            a.*,
            u.full_name as creator_name,
            u.role as creator_role,
            CASE 
                WHEN a.expires_at IS NULL THEN 'active'
                WHEN a.expires_at > NOW() THEN 'active'
                ELSE 'expired'
            END as status
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        ORDER BY a.is_pinned DESC, a.priority DESC, a.created_at DESC
        LIMIT 200
    ");
    
    $announcements = [];
    while ($row = $stmt->fetch_assoc()) {
        $announcements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $announcements,
        'count' => count($announcements)
    ]);
}

/**
 * Get single announcement
 */
function getAnnouncement($conn) {
    $id = isset($_GET['id']) ? intval($_GET['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception('معرف الإعلان غير صالح');
    }
    
    $stmt = $conn->prepare("
        SELECT a.*, u.full_name as creator_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        WHERE a.id = ?
    ");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        echo json_encode(['success' => true, 'data' => $row]);
    } else {
        throw new Exception('الإعلان غير موجود');
    }
}

/**
 * Create new announcement
 */
function createAnnouncement($conn, $userId) {
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $displayOnWebsite = isset($_POST['display_on_website']) ? intval($_POST['display_on_website']) : 1;
    $isPinned = isset($_POST['is_pinned']) ? intval($_POST['is_pinned']) : 0;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $mediaType = $_POST['media_type'] ?? 'none';
    $imagePath = $_POST['image_path'] ?? null;
    $videoPath = $_POST['video_path'] ?? null;
    
    if (empty($title) || empty($content)) {
        throw new Exception('العنوان والمحتوى مطلوبان');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO announcements (
            title, content, created_by, priority, display_on_website,
            is_pinned, expires_at, media_type, image_path, video_path,
            created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param(
        'ssississss',
        $title, $content, $userId, $priority, $displayOnWebsite,
        $isPinned, $expiresAt, $mediaType, $imagePath, $videoPath
    );
    
    if ($stmt->execute()) {
        $newId = $stmt->insert_id;
        
        // Send notifications to users if display_on_website = 1
        if ($displayOnWebsite == 1) {
            sendAnnouncementNotifications($conn, $newId, $title, $content);
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الإعلان بنجاح',
            'id' => $newId
        ]);
    } else {
        throw new Exception('فشل إنشاء الإعلان: ' . $stmt->error);
    }
}

/**
 * Update announcement
 */
function updateAnnouncement($conn, $userId) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $priority = $_POST['priority'] ?? 'medium';
    $displayOnWebsite = isset($_POST['display_on_website']) ? intval($_POST['display_on_website']) : 1;
    $isPinned = isset($_POST['is_pinned']) ? intval($_POST['is_pinned']) : 0;
    $expiresAt = !empty($_POST['expires_at']) ? $_POST['expires_at'] : null;
    $mediaType = $_POST['media_type'] ?? 'none';
    $imagePath = $_POST['image_path'] ?? null;
    $videoPath = $_POST['video_path'] ?? null;
    
    if ($id <= 0) {
        throw new Exception('معرف الإعلان غير صالح');
    }
    
    if (empty($title) || empty($content)) {
        throw new Exception('العنوان والمحتوى مطلوبان');
    }
    
    $stmt = $conn->prepare("
        UPDATE announcements 
        SET title = ?, content = ?, priority = ?, display_on_website = ?,
            is_pinned = ?, expires_at = ?, media_type = ?, 
            image_path = ?, video_path = ?, updated_at = NOW()
        WHERE id = ?
    ");
    
    $stmt->bind_param(
        'ssississsi',
        $title, $content, $priority, $displayOnWebsite,
        $isPinned, $expiresAt, $mediaType, $imagePath, $videoPath, $id
    );
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث الإعلان بنجاح']);
    } else {
        throw new Exception('فشل التحديث: ' . $stmt->error);
    }
}

/**
 * Delete announcement
 */
function deleteAnnouncement($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception('معرف الإعلان غير صالح');
    }
    
    // Get media paths before deleting
    $stmt = $conn->prepare("SELECT image_path, video_path FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Delete media files
        if (!empty($row['image_path']) && file_exists('../../' . $row['image_path'])) {
            unlink('../../' . $row['image_path']);
        }
        if (!empty($row['video_path']) && file_exists('../../' . $row['video_path'])) {
            unlink('../../' . $row['video_path']);
        }
    }
    
    // Delete announcement
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم حذف الإعلان بنجاح']);
    } else {
        throw new Exception('فشل الحذف: ' . $stmt->error);
    }
}

/**
 * Upload media (image or video)
 */
function uploadMedia() {
    if (!isset($_FILES['media'])) {
        throw new Exception('لم يتم رفع أي ملف');
    }
    
    $file = $_FILES['media'];
    $mediaType = $_POST['media_type'] ?? 'image';
    
    // Validate file
    $allowedImages = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif', 'image/webp'];
    $allowedVideos = ['video/mp4', 'video/mpeg', 'video/quicktime', 'video/x-msvideo'];
    
    $maxImageSize = 5 * 1024 * 1024; // 5MB
    $maxVideoSize = 50 * 1024 * 1024; // 50MB
    
    if ($mediaType === 'image') {
        if (!in_array($file['type'], $allowedImages)) {
            throw new Exception('نوع الصورة غير مدعوم. المسموح: JPG, PNG, GIF, WEBP');
        }
        if ($file['size'] > $maxImageSize) {
            throw new Exception('حجم الصورة كبير جداً (الحد الأقصى 5MB)');
        }
    } elseif ($mediaType === 'video') {
        if (!in_array($file['type'], $allowedVideos)) {
            throw new Exception('نوع الفيديو غير مدعوم. المسموح: MP4, MOV, AVI');
        }
        if ($file['size'] > $maxVideoSize) {
            throw new Exception('حجم الفيديو كبير جداً (الحد الأقصى 50MB)');
        }
    } else {
        throw new Exception('نوع الوسائط غير صالح');
    }
    
    // Create upload directory if not exists
    $uploadDir = '../../uploads/announcements/' . date('Y/m/');
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    // Generate unique filename
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = uniqid('announcement_' . time() . '_') . '.' . $extension;
    $filepath = $uploadDir . $filename;
    
    // Move uploaded file
    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        $relativePath = 'uploads/announcements/' . date('Y/m/') . $filename;
        
        echo json_encode([
            'success' => true,
            'message' => 'تم رفع الملف بنجاح',
            'path' => $relativePath,
            'url' => '../../' . $relativePath
        ]);
    } else {
        throw new Exception('فشل رفع الملف');
    }
}

/**
 * Toggle pin status
 */
function togglePin($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception('معرف الإعلان غير صالح');
    }
    
    $stmt = $conn->prepare("UPDATE announcements SET is_pinned = NOT is_pinned WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة التثبيت']);
    } else {
        throw new Exception('فشل التحديث');
    }
}

/**
 * Toggle website display
 */
function toggleWebsiteDisplay($conn) {
    $id = isset($_POST['id']) ? intval($_POST['id']) : 0;
    
    if ($id <= 0) {
        throw new Exception('معرف الإعلان غير صالح');
    }
    
    $stmt = $conn->prepare("UPDATE announcements SET display_on_website = NOT display_on_website WHERE id = ?");
    $stmt->bind_param('i', $id);
    
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'تم تحديث حالة العرض']);
    } else {
        throw new Exception('فشل التحديث');
    }
}

/**
 * Send notifications to all active users when announcement is created
 */
function sendAnnouncementNotifications($conn, $announcementId, $title, $content) {
    try {
        // Get all active users (students enrolled in courses)
        $stmt = $conn->query("
            SELECT DISTINCT u.id 
            FROM users u 
            WHERE u.role = 'student' AND u.id > 0
            LIMIT 1000
        ");
        
        $preview = mb_substr(strip_tags($content), 0, 100) . '...';
        
        while ($row = $stmt->fetch_assoc()) {
            $userId = $row['id'];
            
            // Insert notification
            $notifStmt = $conn->prepare("
                INSERT INTO notifications (
                    user_id, message, notification_type, priority,
                    action_url, icon, color, created_at
                ) VALUES (?, ?, 'announcement', 'normal', ?, 'megaphone', 'blue', NOW())
            ");
            
            $message = "📢 إعلان جديد: {$title}\n{$preview}";
            $actionUrl = "announcements.php?id={$announcementId}";
            
            $notifStmt->bind_param('iss', $userId, $message, $actionUrl);
            $notifStmt->execute();
        }
        
    } catch (Exception $e) {
        error_log("Failed to send announcement notifications: " . $e->getMessage());
    }
}
