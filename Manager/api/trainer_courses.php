<?php
/**
 * Trainer Courses API
 * API دورات المدرب
 */

session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الصلاحية
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    respond(['success' => false, 'message' => 'غير مصرح - يجب تسجيل الدخول'], 401);
}

// قبول trainer أو manager
if (!in_array($user_role, ['trainer', 'manager', 'مدرب'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? 'list';

try {
    // جلب دورات المدرب
    if ($method === 'GET' && ($action === 'list' || !isset($_GET['action']))) {
        $trainer_id = (int)($_GET['trainer_id'] ?? $user_id);
        
        // التحقق من صلاحية الوصول
        if ($trainer_id !== $user_id && $user_role !== 'manager') {
            respond(['success' => false, 'message' => 'غير مصرح بالوصول'], 403);
        }
        
        $stmt = $conn->prepare("
            SELECT 
                c.course_id as id,
                c.course_id,
                c.title as course_name,
                c.title,
                c.description,
                c.start_date,
                c.end_date,
                c.status,
                c.region,
                c.fees,
                c.district,
                COUNT(DISTINCT e.user_id) as student_count,
                COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.user_id END) as active_students
            FROM courses c
            LEFT JOIN enrollments e ON c.course_id = e.course_id
            WHERE c.trainer_id = ?
            GROUP BY c.course_id
            ORDER BY c.start_date DESC
        ");
        
        $stmt->bind_param('i', $trainer_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $courses = [];
        while ($row = $result->fetch_assoc()) {
            $courses[] = [
                'id' => (int)$row['id'],
                'course_id' => (int)$row['course_id'],
                'course_name' => $row['course_name'],
                'title' => $row['title'],
                'description' => $row['description'],
                'start_date' => $row['start_date'],
                'end_date' => $row['end_date'],
                'status' => $row['status'],
                'region' => $row['region'],
                'fees' => (float)$row['fees'],
                'district' => $row['district'],
                'student_count' => (int)$row['student_count'],
                'active_students' => (int)$row['active_students']
            ];
        }
        
        $stmt->close();
        
        respond([
            'success' => true,
            'data' => $courses,
            'total' => count($courses)
        ]);
    }
    
    // جلب تفاصيل دورة محددة
    if ($method === 'GET' && $action === 'details') {
        $course_id = (int)($_GET['course_id'] ?? 0);
        
        if ($course_id <= 0) {
            respond(['success' => false, 'message' => 'معرف الدورة مطلوب'], 400);
        }
        
        $stmt = $conn->prepare("
            SELECT 
                c.*,
                COUNT(DISTINCT e.user_id) as total_students,
                COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.user_id END) as active_students,
                COUNT(DISTINCT CASE WHEN e.status = 'completed' THEN e.user_id END) as completed_students
            FROM courses c
            LEFT JOIN enrollments e ON c.course_id = e.course_id
            WHERE c.course_id = ? AND c.trainer_id = ?
            GROUP BY c.course_id
        ");
        
        $stmt->bind_param('ii', $course_id, $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            respond(['success' => false, 'message' => 'الدورة غير موجودة أو غير مصرح بالوصول'], 404);
        }
        
        $course = $result->fetch_assoc();
        $stmt->close();
        
        respond([
            'success' => true,
            'data' => $course
        ]);
    }
    
    respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    
} catch (Exception $e) {
    error_log("Trainer Courses API Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي'], 500);
}
