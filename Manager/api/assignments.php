<?php
/**
 * Trainer Assignments API
 * API واجبات المدرب
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
    // جلب الواجبات
    if ($method === 'GET' && ($action === 'list' || !isset($_GET['action']))) {
        $course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : null;
        
        // التحقق من وجود جدول assignments
        $table_check = $conn->query("SHOW TABLES LIKE 'assignments'");
        if ($table_check->num_rows === 0) {
            // إرجاع بيانات فارغة إذا لم يكن الجدول موجود
            respond([
                'success' => true,
                'data' => [],
                'total' => 0,
                'message' => 'جدول الواجبات غير موجود بعد'
            ]);
        }
        
        $sql = "
            SELECT 
                a.id,
                a.id as assignment_id,
                a.title,
                a.description,
                a.course_id,
                a.due_date,
                a.max_score,
                a.created_at,
                c.title as course_name,
                COUNT(DISTINCT s.id) as submission_count,
                COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded_count
            FROM assignments a
            INNER JOIN courses c ON a.course_id = c.course_id
            LEFT JOIN assignment_submissions s ON a.id = s.assignment_id
            WHERE c.trainer_id = ?
        ";
        
        $params = [$user_id];
        $types = 'i';
        
        if ($course_id !== null) {
            $sql .= " AND a.course_id = ?";
            $params[] = $course_id;
            $types .= 'i';
        }
        
        $sql .= " GROUP BY a.id ORDER BY a.due_date DESC";
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            $assignments[] = [
                'id' => (int)$row['id'],
                'assignment_id' => (int)$row['assignment_id'],
                'title' => $row['title'],
                'description' => $row['description'],
                'course_id' => (int)$row['course_id'],
                'course_name' => $row['course_name'],
                'due_date' => $row['due_date'],
                'max_score' => (float)$row['max_score'],
                'created_at' => $row['created_at'],
                'submission_count' => (int)$row['submission_count'],
                'graded_count' => (int)$row['graded_count'],
                'pending_count' => (int)$row['submission_count'] - (int)$row['graded_count']
            ];
        }
        
        $stmt->close();
        
        respond([
            'success' => true,
            'data' => $assignments,
            'total' => count($assignments)
        ]);
    }
    
    // إنشاء واجب جديد
    if ($method === 'POST' && $action === 'create') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        $course_id = (int)($data['course_id'] ?? 0);
        $title = trim($data['title'] ?? '');
        $description = trim($data['description'] ?? '');
        $due_date = $data['due_date'] ?? null;
        $max_score = (float)($data['max_score'] ?? 100);
        
        if ($course_id <= 0 || $title === '') {
            respond(['success' => false, 'message' => 'الدورة والعنوان مطلوبان'], 400);
        }
        
        // التحقق من أن المدرب يملك الدورة
        $check = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
        $check->bind_param('ii', $course_id, $user_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            respond(['success' => false, 'message' => 'غير مصرح بإضافة واجب لهذه الدورة'], 403);
        }
        $check->close();
        
        // إدراج الواجب
        $stmt = $conn->prepare("
            INSERT INTO assignments 
            (course_id, title, description, due_date, max_score, created_by, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param('isssdi', $course_id, $title, $description, $due_date, $max_score, $user_id);
        
        if (!$stmt->execute()) {
            $stmt->close();
            respond(['success' => false, 'message' => 'فشل إنشاء الواجب'], 500);
        }
        
        $assignment_id = $stmt->insert_id;
        $stmt->close();
        
        respond([
            'success' => true,
            'message' => 'تم إنشاء الواجب بنجاح',
            'assignment_id' => $assignment_id
        ], 201);
    }
    
    // حذف واجب
    if ($method === 'DELETE' || ($method === 'POST' && $action === 'delete')) {
        $assignment_id = (int)($_GET['assignment_id'] ?? $_POST['assignment_id'] ?? 0);
        
        if ($assignment_id <= 0) {
            respond(['success' => false, 'message' => 'معرف الواجب مطلوب'], 400);
        }
        
        // التحقق من الملكية
        $check = $conn->prepare("
            SELECT a.id 
            FROM assignments a
            INNER JOIN courses c ON a.course_id = c.course_id
            WHERE a.id = ? AND c.trainer_id = ?
        ");
        $check->bind_param('ii', $assignment_id, $user_id);
        $check->execute();
        $check->store_result();
        
        if ($check->num_rows === 0) {
            respond(['success' => false, 'message' => 'غير مصرح بحذف هذا الواجب'], 403);
        }
        $check->close();
        
        // حذف الواجب
        $stmt = $conn->prepare("DELETE FROM assignments WHERE id = ?");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $stmt->close();
        
        respond([
            'success' => true,
            'message' => 'تم حذف الواجب بنجاح'
        ]);
    }
    
    respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    
} catch (Exception $e) {
    error_log("Trainer Assignments API Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
