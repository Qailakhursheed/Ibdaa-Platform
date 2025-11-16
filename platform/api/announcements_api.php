<?php
/**
 * Announcements API for Public Platform
 * Fetches announcements from technical supervisor and admin
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            getAnnouncements($conn);
            break;
        
        case 'latest':
            getLatestAnnouncements($conn);
            break;
        
        case 'details':
            getAnnouncementDetails($conn);
            break;
        
        case 'categories':
            getAnnouncementCategories($conn);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function getAnnouncements($conn) {
    $category = $_GET['category'] ?? null;
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    
    // Build query - get public announcements from technical supervisor
    $where = ["n.type IN ('announcement', 'news', 'scholarship', 'event')"];
    $where[] = "u.role IN ('technical_supervisor', 'admin')";
    $params = [];
    $types = "";
    
    if ($category) {
        $where[] = "n.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total 
                 FROM notifications n
                 JOIN users u ON n.created_by = u.user_id
                 WHERE $whereClause";
    
    if ($types) {
        $stmtCount = $conn->prepare($countSql);
        $stmtCount->bind_param($types, ...$params);
        $stmtCount->execute();
        $total = $stmtCount->get_result()->fetch_assoc()['total'];
    } else {
        $total = $conn->query($countSql)->fetch_assoc()['total'];
    }
    
    // Get announcements
    $sql = "SELECT 
                n.notification_id,
                n.title,
                n.message,
                n.type,
                n.category,
                n.priority,
                n.created_at,
                n.expires_at,
                u.full_name as author_name,
                u.role as author_role
            FROM notifications n
            JOIN users u ON n.created_by = u.user_id
            WHERE $whereClause
            ORDER BY n.created_at DESC
            LIMIT ? OFFSET ?";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $announcements = [];
    while ($row = $result->fetch_assoc()) {
        // Check if announcement is still valid
        $row['is_active'] = !$row['expires_at'] || strtotime($row['expires_at']) > time();
        $announcements[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $announcements,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => ceil($total / $limit)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getLatestAnnouncements($conn) {
    $limit = (int)($_GET['limit'] ?? 5);
    
    $sql = "SELECT 
                n.notification_id,
                n.title,
                n.message,
                n.type,
                n.category,
                n.priority,
                n.created_at,
                u.full_name as author_name
            FROM notifications n
            JOIN users u ON n.created_by = u.user_id
            WHERE n.type IN ('announcement', 'news', 'scholarship', 'event')
                AND u.role IN ('technical_supervisor', 'admin')
                AND (n.expires_at IS NULL OR n.expires_at > NOW())
            ORDER BY n.priority DESC, n.created_at DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $announcements = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $announcements
    ], JSON_UNESCAPED_UNICODE);
}

function getAnnouncementDetails($conn) {
    $id = $_GET['id'] ?? null;
    
    if (!$id) {
        throw new Exception('Announcement ID is required');
    }
    
    $sql = "SELECT 
                n.*,
                u.full_name as author_name,
                u.role as author_role,
                u.email as author_email
            FROM notifications n
            JOIN users u ON n.created_by = u.user_id
            WHERE n.notification_id = ?
                AND n.type IN ('announcement', 'news', 'scholarship', 'event')";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $announcement = $stmt->get_result()->fetch_assoc();
    
    if (!$announcement) {
        throw new Exception('Announcement not found');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $announcement
    ], JSON_UNESCAPED_UNICODE);
}

function getAnnouncementCategories($conn) {
    $sql = "SELECT 
                n.category,
                COUNT(*) as count
            FROM notifications n
            JOIN users u ON n.created_by = u.user_id
            WHERE n.type IN ('announcement', 'news', 'scholarship', 'event')
                AND u.role IN ('technical_supervisor', 'admin')
                AND n.category IS NOT NULL
            GROUP BY n.category
            ORDER BY count DESC";
    
    $result = $conn->query($sql);
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);
}
?>
