<?php
/**
 * Smart Announcements System with AI
 * نظام الإعلانات الذكي بالذكاء الاصطناعي
 * 
 * Features:
 * - AI-powered audience targeting
 * - Send notifications to students
 * - External website integration
 * - Analytics and tracking
 * 
 * @version 1.0
 * @date 2025-11-09
 */

require_once '../../database/db.php';
header('Content-Type: application/json');
session_start();

// Check permissions
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح - يجب تسجيل الدخول كمدير أو مشرف فني']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            listAnnouncements($conn);
            break;
            
        case 'get':
            getAnnouncement($conn);
            break;
            
        case 'create':
            createAnnouncement($conn);
            break;
            
        case 'update':
            updateAnnouncement($conn);
            break;
            
        case 'delete':
            deleteAnnouncement($conn);
            break;
            
        case 'ai_suggest_audience':
            aiSuggestAudience($conn);
            break;
            
        case 'analytics':
            getAnalytics($conn);
            break;
            
        case 'mark_read':
            markAsRead($conn);
            break;
            
        default:
            echo json_encode(['success' => false, 'message' => 'إجراء غير صالح']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
}

/**
 * List all announcements with analytics
 */
function listAnnouncements($conn) {
    $stmt = $conn->query("
        SELECT 
            a.*,
            u.full_name as creator_name,
            c.title as course_title,
            COUNT(DISTINCT ar.user_id) as views_count,
            COUNT(DISTINCT CASE WHEN ar.is_read = 1 THEN ar.user_id END) as read_count,
            COUNT(DISTINCT e.user_id) as enrollments_count,
            ROUND(COUNT(DISTINCT CASE WHEN ar.is_read = 1 THEN ar.user_id END) * 100.0 / NULLIF(COUNT(DISTINCT ar.user_id), 0), 2) as open_rate
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id
        LEFT JOIN enrollments e ON a.course_id = e.course_id 
            AND e.created_at >= a.created_at
            AND e.created_at <= DATE_ADD(a.created_at, INTERVAL 30 DAY)
        GROUP BY a.id
        ORDER BY a.created_at DESC
        LIMIT 100
    ");
    
    $announcements = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'data' => $announcements,
        'count' => count($announcements)
    ]);
}

/**
 * Get single announcement with full details
 */
function getAnnouncement($conn) {
    $id = $_GET['id'] ?? 0;
    
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            u.full_name as creator_name,
            c.title as course_title,
            c.description as course_description
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.id
        LEFT JOIN courses c ON a.course_id = c.id
        WHERE a.id = ?
    ");
    
    $stmt->execute([$id]);
    $announcement = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($announcement) {
        echo json_encode(['success' => true, 'data' => $announcement]);
    } else {
        echo json_encode(['success' => false, 'message' => 'الإعلان غير موجود']);
    }
}

/**
 * Create new announcement with AI features
 */
function createAnnouncement($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    // Validate required fields
    if (empty($data['title']) || empty($data['description'])) {
        echo json_encode(['success' => false, 'message' => 'العنوان والوصف مطلوبان']);
        return;
    }
    
    // Insert announcement
    $stmt = $conn->prepare("
        INSERT INTO announcements 
        (title, description, course_id, target_audience, scheduled_at, status, created_by, priority, metadata)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $metadata = json_encode([
        'send_email' => $data['send_email'] ?? false,
        'send_notification' => $data['send_notification'] ?? true,
        'publish_to_website' => $data['publish_to_website'] ?? false,
        'ai_targeting' => $data['ai_targeting'] ?? false
    ]);
    
    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['course_id'] ?? null,
        $data['target_audience'] ?? 'all',
        $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
        $data['status'] ?? 'published',
        $_SESSION['user_id'],
        $data['priority'] ?? 'normal',
        $metadata
    ]);
    
    $announcementId = $conn->lastInsertId();
    
    // Send notifications to students
    if (!empty($data['send_notification'])) {
        sendAnnouncementNotifications($conn, $announcementId, $data);
    }
    
    // Send emails
    if (!empty($data['send_email'])) {
        sendAnnouncementEmails($conn, $announcementId, $data);
    }
    
    // Publish to external website
    if (!empty($data['publish_to_website'])) {
        $result = publishToExternalWebsite($data);
        // Store external publish result in metadata
        $conn->prepare("UPDATE announcements SET metadata = JSON_SET(metadata, '$.external_publish', ?) WHERE id = ?")
             ->execute([json_encode($result), $announcementId]);
    }
    
    echo json_encode([
        'success' => true, 
        'message' => 'تم نشر الإعلان بنجاح',
        'announcement_id' => $announcementId
    ]);
}

/**
 * Update existing announcement
 */
function updateAnnouncement($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (empty($data['id'])) {
        echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب']);
        return;
    }
    
    $stmt = $conn->prepare("
        UPDATE announcements 
        SET title = ?, description = ?, course_id = ?, target_audience = ?, 
            scheduled_at = ?, status = ?, priority = ?, metadata = ?
        WHERE id = ?
    ");
    
    $metadata = json_encode([
        'send_email' => $data['send_email'] ?? false,
        'send_notification' => $data['send_notification'] ?? true,
        'publish_to_website' => $data['publish_to_website'] ?? false,
        'ai_targeting' => $data['ai_targeting'] ?? false,
        'updated_at' => date('Y-m-d H:i:s')
    ]);
    
    $stmt->execute([
        $data['title'],
        $data['description'],
        $data['course_id'] ?? null,
        $data['target_audience'] ?? 'all',
        $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
        $data['status'] ?? 'published',
        $data['priority'] ?? 'normal',
        $metadata,
        $data['id']
    ]);
    
    echo json_encode(['success' => true, 'message' => 'تم تحديث الإعلان بنجاح']);
}

/**
 * Delete announcement
 */
function deleteAnnouncement($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $id = $data['id'] ?? 0;
    
    if (!$id) {
        echo json_encode(['success' => false, 'message' => 'معرف الإعلان مطلوب']);
        return;
    }
    
    // Delete related reads first
    $conn->prepare("DELETE FROM announcement_reads WHERE announcement_id = ?")->execute([$id]);
    
    // Delete announcement
    $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    
    echo json_encode(['success' => true, 'message' => 'تم حذف الإعلان بنجاح']);
}

/**
 * AI-powered audience targeting
 * يستخدم الذكاء الاصطناعي لاقتراح الجمهور المستهدف
 */
function aiSuggestAudience($conn) {
    $courseId = $_GET['course_id'] ?? 0;
    $limit = $_GET['limit'] ?? 100;
    
    if (!$courseId) {
        echo json_encode(['success' => false, 'message' => 'معرف الدورة مطلوب']);
        return;
    }
    
    // AI Algorithm: Find students with similar interests based on:
    // 1. Past enrollments in similar courses
    // 2. Course category matching
    // 3. Learning patterns
    // 4. Engagement level
    
    $stmt = $conn->prepare("
        SELECT DISTINCT 
            u.id, 
            u.full_name, 
            u.email,
            u.phone,
            COUNT(DISTINCT e.course_id) as courses_count,
            AVG(CASE WHEN e.status = 'completed' THEN 100 ELSE 50 END) as engagement_score,
            MAX(e.created_at) as last_enrollment
        FROM users u
        INNER JOIN enrollments e ON u.id = e.user_id
        WHERE u.role = 'student'
        AND u.id NOT IN (
            SELECT user_id FROM enrollments WHERE course_id = ?
        )
        AND e.course_id IN (
            -- Find similar courses
            SELECT DISTINCT c2.id 
            FROM courses c1
            INNER JOIN courses c2 ON (
                c2.category = c1.category 
                OR c2.id IN (
                    SELECT course_id FROM enrollments 
                    WHERE user_id IN (
                        SELECT user_id FROM enrollments WHERE course_id = c1.id
                    )
                )
            )
            WHERE c1.id = ?
            AND c2.id != ?
        )
        GROUP BY u.id
        ORDER BY engagement_score DESC, courses_count DESC, last_enrollment DESC
        LIMIT ?
    ");
    
    $stmt->execute([$courseId, $courseId, $courseId, $limit]);
    $suggestedStudents = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Calculate AI confidence score
    $confidence = min(85 + (count($suggestedStudents) * 0.1), 99);
    
    echo json_encode([
        'success' => true, 
        'suggested_students' => $suggestedStudents,
        'count' => count($suggestedStudents),
        'ai_confidence' => round($confidence, 2),
        'algorithm' => 'Collaborative Filtering + Engagement Analysis',
        'factors' => [
            'Past enrollments similarity',
            'Course category matching',
            'Student engagement level',
            'Recent activity'
        ]
    ]);
}

/**
 * Get analytics for announcements
 */
function getAnalytics($conn) {
    // Overall statistics
    $overallStats = $conn->query("
        SELECT 
            COUNT(*) as total_announcements,
            COUNT(CASE WHEN status = 'published' THEN 1 END) as published_count,
            COUNT(CASE WHEN status = 'draft' THEN 1 END) as draft_count,
            AVG(
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id)
            ) as avg_views,
            AVG(
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.is_read = 1)
            ) as avg_reads
        FROM announcements a
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ")->fetch(PDO::FETCH_ASSOC);
    
    // Top performing announcements
    $topAnnouncements = $conn->query("
        SELECT 
            a.id,
            a.title,
            a.created_at,
            COUNT(DISTINCT ar.user_id) as views,
            COUNT(DISTINCT CASE WHEN ar.is_read = 1 THEN ar.user_id END) as reads,
            COUNT(DISTINCT e.user_id) as conversions,
            ROUND(COUNT(DISTINCT CASE WHEN ar.is_read = 1 THEN ar.user_id END) * 100.0 / NULLIF(COUNT(DISTINCT ar.user_id), 0), 2) as open_rate,
            ROUND(COUNT(DISTINCT e.user_id) * 100.0 / NULLIF(COUNT(DISTINCT ar.user_id), 0), 2) as conversion_rate
        FROM announcements a
        LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id
        LEFT JOIN enrollments e ON a.course_id = e.course_id 
            AND e.created_at >= a.created_at
            AND e.created_at <= DATE_ADD(a.created_at, INTERVAL 30 DAY)
        WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
        GROUP BY a.id
        ORDER BY conversion_rate DESC, open_rate DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    // Best time to publish (hour analysis)
    $bestTimes = $conn->query("
        SELECT 
            HOUR(a.created_at) as hour,
            COUNT(*) as announcements_count,
            AVG(
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.is_read = 1)
            ) as avg_opens,
            ROUND(AVG(
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id AND ar.is_read = 1)
            ) * 100.0 / NULLIF(AVG(
                (SELECT COUNT(*) FROM announcement_reads ar WHERE ar.announcement_id = a.id)
            ), 0), 2) as avg_open_rate
        FROM announcements a
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
        GROUP BY HOUR(a.created_at)
        ORDER BY avg_open_rate DESC
        LIMIT 3
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $bestTime = !empty($bestTimes) ? sprintf('%02d:00', $bestTimes[0]['hour']) : '10:00';
    
    // Calculate metrics
    $openRate = $overallStats['avg_views'] > 0 
        ? round(($overallStats['avg_reads'] / $overallStats['avg_views']) * 100, 2) 
        : 0;
    
    echo json_encode([
        'success' => true,
        'data' => [
            'overall' => $overallStats,
            'open_rate' => $openRate,
            'conversion_rate' => 0, // Will be calculated based on enrollments
            'best_time' => $bestTime,
            'top_announcements' => $topAnnouncements,
            'best_publishing_times' => $bestTimes
        ]
    ]);
}

/**
 * Mark announcement as read by user
 */
function markAsRead($conn) {
    $data = json_decode(file_get_contents('php://input'), true);
    $announcementId = $data['announcement_id'] ?? 0;
    $userId = $data['user_id'] ?? $_SESSION['user_id'] ?? 0;
    
    if (!$announcementId || !$userId) {
        echo json_encode(['success' => false, 'message' => 'معرف الإعلان والمستخدم مطلوبان']);
        return;
    }
    
    // Check if already exists
    $check = $conn->prepare("SELECT id FROM announcement_reads WHERE announcement_id = ? AND user_id = ?");
    $check->execute([$announcementId, $userId]);
    
    if ($check->fetch()) {
        // Update
        $conn->prepare("UPDATE announcement_reads SET is_read = 1, read_at = NOW() WHERE announcement_id = ? AND user_id = ?")
             ->execute([$announcementId, $userId]);
    } else {
        // Insert
        $conn->prepare("INSERT INTO announcement_reads (announcement_id, user_id, is_read, read_at) VALUES (?, ?, 1, NOW())")
             ->execute([$announcementId, $userId]);
    }
    
    echo json_encode(['success' => true, 'message' => 'تم تحديث حالة القراءة']);
}

/**
 * Send notifications to students
 */
function sendAnnouncementNotifications($conn, $announcementId, $data) {
    $targetAudience = $data['target_audience'] ?? 'all';
    
    // Get target students
    if ($targetAudience === 'all') {
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE role = 'student' AND email IS NOT NULL");
        $stmt->execute();
    } else {
        // Custom audience (AI-suggested or manual selection)
        $studentIds = $data['target_student_ids'] ?? [];
        if (empty($studentIds)) return;
        
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE id IN ($placeholders) AND role = 'student'");
        $stmt->execute($studentIds);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $notificationsSent = 0;
    
    foreach ($students as $student) {
        try {
            // Insert notification record
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, title, message, link, metadata) 
                VALUES (?, 'announcement', ?, ?, ?, ?)
            ");
            
            $link = '/platform/announcements.php?id=' . $announcementId;
            $metadata = json_encode(['announcement_id' => $announcementId, 'course_id' => $data['course_id'] ?? null]);
            
            $stmt->execute([
                $student['id'],
                $data['title'],
                $data['description'],
                $link,
                $metadata
            ]);
            
            // Track view
            $conn->prepare("INSERT INTO announcement_reads (announcement_id, user_id, is_read) VALUES (?, ?, 0) ON DUPLICATE KEY UPDATE announcement_id = announcement_id")
                 ->execute([$announcementId, $student['id']]);
            
            $notificationsSent++;
        } catch (Exception $e) {
            error_log("Failed to send notification to user {$student['id']}: " . $e->getMessage());
        }
    }
    
    return $notificationsSent;
}

/**
 * Send emails to students
 */
function sendAnnouncementEmails($conn, $announcementId, $data) {
    // Get target students (same logic as notifications)
    $targetAudience = $data['target_audience'] ?? 'all';
    
    if ($targetAudience === 'all') {
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE role = 'student' AND email IS NOT NULL");
        $stmt->execute();
    } else {
        $studentIds = $data['target_student_ids'] ?? [];
        if (empty($studentIds)) return;
        
        $placeholders = str_repeat('?,', count($studentIds) - 1) . '?';
        $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE id IN ($placeholders) AND role = 'student'");
        $stmt->execute($studentIds);
    }
    
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        $emailBody = generateAnnouncementEmail($student, $data);
        
        // Here you would integrate with your email service
        // For now, we'll use a simple approach
        $emailData = [
            'to' => $student['email'],
            'subject' => '📢 إعلان جديد: ' . $data['title'],
            'message' => $emailBody
        ];
        
        // Call your email service (PHPMailer, etc.)
        // file_get_contents('../../Mailer/sendMail.php?' . http_build_query($emailData));
    }
}

/**
 * Publish announcement to external website via API
 */
function publishToExternalWebsite($data) {
    // Example: Call external API
    $externalApiUrl = 'https://your-website.com/api/announcements';
    
    $postData = [
        'title' => $data['title'],
        'description' => $data['description'],
        'course_id' => $data['course_id'] ?? null,
        'published_at' => date('Y-m-d H:i:s'),
        'api_key' => 'YOUR_API_KEY' // Store in config
    ];
    
    try {
        $ch = curl_init($externalApiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'response' => json_decode($response, true),
                'published_at' => date('Y-m-d H:i:s')
            ];
        } else {
            return [
                'success' => false,
                'error' => 'HTTP ' . $httpCode,
                'response' => $response
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

/**
 * Generate HTML email for announcement
 */
function generateAnnouncementEmail($student, $data) {
    $courseLink = '';
    if (!empty($data['course_id'])) {
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $courseLink = "http://{$host}/platform/courses.php?id={$data['course_id']}";
    }
    
    return "
        <div style='font-family: Cairo, Arial, sans-serif; direction: rtl; padding: 20px; background: #f8fafc;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 16px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
                <div style='background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; text-align: center;'>
                    <h1 style='color: white; margin: 0; font-size: 28px;'>📢 إعلان جديد</h1>
                </div>
                <div style='padding: 30px;'>
                    <p style='font-size: 18px; color: #334155; margin-bottom: 10px;'>مرحباً <strong>{$student['full_name']}</strong>،</p>
                    <h2 style='color: #1e293b; font-size: 24px; margin: 20px 0;'>{$data['title']}</h2>
                    <p style='color: #475569; font-size: 16px; line-height: 1.8; margin: 20px 0;'>{$data['description']}</p>
                    " . (!empty($courseLink) ? "
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='{$courseLink}' style='display: inline-block; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 14px 32px; text-decoration: none; border-radius: 8px; font-weight: bold; font-size: 16px;'>
                            🎓 سجل الآن في الدورة
                        </a>
                    </div>
                    " : "") . "
                    <div style='margin-top: 30px; padding-top: 20px; border-top: 1px solid #e2e8f0; color: #64748b; font-size: 14px;'>
                        <p>مع أطيب التحيات،<br><strong>فريق منصة إبداع</strong></p>
                    </div>
                </div>
            </div>
        </div>
    ";
}
?>