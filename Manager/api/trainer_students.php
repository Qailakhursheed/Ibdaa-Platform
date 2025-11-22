<?php
/**
 * Trainer Students API
 * API طلاب المدرب
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
    // جلب طلاب المدرب
    if ($method === 'GET' && ($action === 'list' || !isset($_GET['action']))) {
        $trainer_id = (int)($_GET['trainer_id'] ?? $user_id);
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
        
        // التحقق من صلاحية الوصول
        if ($trainer_id !== $user_id && $user_role !== 'manager') {
            respond(['success' => false, 'message' => 'غير مصرح بالوصول'], 403);
        }
        
        $sql = "
            SELECT DISTINCT
                u.id,
                u.id as student_id,
                u.full_name,
                u.full_name as name,
                u.email,
                u.phone,
                u.dob,
                u.governorate,
                u.district,
                u.created_at as registration_date,
                c.course_id,
                c.title as course_name,
                e.status as enrollment_status,
                e.enrollment_date,
                e.payment_status,
                e.progress,
                e.grade
            FROM users u
            INNER JOIN enrollments e ON u.id = e.user_id
            INNER JOIN courses c ON e.course_id = c.course_id
            WHERE c.trainer_id = ?
              AND u.role = 'student'
        ";
        
        $params = [$trainer_id];
        $types = 'i';
        
        if ($course_id !== null) {
            $sql .= " AND c.course_id = ?";
            $params[] = $course_id;
            $types .= 'i';
        }
        
        $sql .= " ORDER BY u.full_name ASC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $students = [];
        while ($row = $result->fetch_assoc()) {
            $students[] = [
                'id' => (int)$row['id'],
                'student_id' => (int)$row['student_id'],
                'full_name' => $row['full_name'],
                'name' => $row['name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'dob' => $row['dob'],
                'governorate' => $row['governorate'],
                'district' => $row['district'],
                'registration_date' => $row['registration_date'],
                'course_id' => (int)$row['course_id'],
                'course_name' => $row['course_name'],
                'enrollment_status' => $row['enrollment_status'],
                'enrollment_date' => $row['enrollment_date'],
                'payment_status' => $row['payment_status'],
                'progress' => (float)($row['progress'] ?? 0),
                'grade' => $row['grade']
            ];
        }
        
        $stmt->close();
        
        respond([
            'success' => true,
            'data' => $students,
            'total' => count($students)
        ]);
    }
    
    // جلب ملف طالب محدد
    if ($method === 'GET' && $action === 'profile') {
        $student_id = (int)($_GET['student_id'] ?? 0);
        
        if ($student_id <= 0) {
            respond(['success' => false, 'message' => 'معرف الطالب مطلوب'], 400);
        }
        
        // التحقق من أن الطالب مسجل في دورة للمدرب
        $check_stmt = $conn->prepare("
            SELECT COUNT(*) as count
            FROM enrollments e
            INNER JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = ? AND c.trainer_id = ?
        ");
        $check_stmt->bind_param('ii', $student_id, $user_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_row = $check_result->fetch_assoc();
        
        if ($check_row['count'] == 0) {
            respond(['success' => false, 'message' => 'غير مصرح بالوصول'], 403);
        }
        
        // جلب معلومات الطالب
        $stmt = $conn->prepare("
            SELECT 
                u.*,
                GROUP_CONCAT(DISTINCT c.title SEPARATOR ', ') as enrolled_courses
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.id = ?
            GROUP BY u.id
        ");
        
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            respond(['success' => false, 'message' => 'الطالب غير موجود'], 404);
        }
        
        $student = $result->fetch_assoc();
        $stmt->close();
        
        respond([
            'success' => true,
            'data' => $student
        ]);
    }
    
    respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    
} catch (Exception $e) {
    error_log("Trainer Students API Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
