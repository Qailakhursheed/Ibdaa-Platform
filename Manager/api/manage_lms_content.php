<?php
/**
 * manage_lms_content - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


header('Content-Type: application/json; charset=utf-8');

// Error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../platform/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Authentication check
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// ============================================
// ROLE-BASED PERMISSIONS
// ============================================
$permissions = [
    'manager' => ['create', 'edit', 'delete', 'view_all'],
    'technical' => ['create', 'edit', 'view_all'],
    'trainer' => ['create', 'edit', 'delete', 'view_assigned'],
    'student' => ['view_assigned']
];

function hasPermission($role, $permission) {
    global $permissions;
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

// ============================================
// 1. CREATE CONTENT
// ============================================
if ($action === 'create' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإنشاء محتوى'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $course_id = (int)($data['course_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = $data['description'] ?? '';
    $type = $data['type'] ?? 'document'; // document, video, link, quiz
    $content_url = $data['content_url'] ?? '';
    $is_active = (bool)($data['is_active'] ?? true);

    if ($course_id <= 0 || empty($title)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO course_materials (
                course_id, title, description, type, file_path, created_at
            ) VALUES (?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('issss', $course_id, $title, $description, $type, $content_url);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم إضافة المحتوى بنجاح',
                'content_id' => $conn->insert_id
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 2. GET CONTENT LIST
// ============================================
if ($action === 'list' && $method === 'GET') {
    $course_id = $_GET['course_id'] ?? null;

    if (!$course_id) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT * FROM course_materials 
            WHERE course_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $materials = [];
        while ($row = $result->fetch_assoc()) {
            $materials[] = $row;
        }

        echo json_encode([
            'success' => true,
            'count' => count($materials),
            'materials' => $materials
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 3. DELETE CONTENT
// ============================================
if ($action === 'delete' && $method === 'POST') {
    if (!hasPermission($user_role, 'delete')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $content_id = (int)($data['content_id'] ?? 0);

    if ($content_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف المحتوى مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("DELETE FROM course_materials WHERE id = ?");
        $stmt->bind_param('i', $content_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم حذف المحتوى بنجاح'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// Default response
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'إجراء غير معروف'
], JSON_UNESCAPED_UNICODE);
