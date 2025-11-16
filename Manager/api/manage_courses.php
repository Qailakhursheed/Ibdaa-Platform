<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

require_once __DIR__ . '/../../platform/db.php';
header('Content-Type: application/json; charset=utf-8');

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager','technical'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // GET: جلب قائمة الدورات
    if ($method === 'GET') {
        $status_filter = $_GET['status'] ?? 'all';
        
        // استعلام مع JOIN لجلب اسم المدرب
        if ($status_filter === 'all') {
            $query = "SELECT c.*, u.full_name as trainer_name, 
                      (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count
                      FROM courses c 
                      LEFT JOIN users u ON c.trainer_id = u.id 
                      ORDER BY c.course_id DESC";
            $stmt = $conn->prepare($query);
        } else {
            $query = "SELECT c.*, u.full_name as trainer_name,
                      (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count
                      FROM courses c 
                      LEFT JOIN users u ON c.trainer_id = u.id 
                      WHERE c.status = ?
                      ORDER BY c.course_id DESC";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $status_filter);
        }
        
        $stmt->execute();
        $res = $stmt->get_result();
        
        $courses = [];
        while ($r = $res->fetch_assoc()) {
            $courses[] = $r;
        }
        
        echo json_encode(['success'=>true, 'data'=>$courses, 'count'=>count($courses)], JSON_UNESCAPED_UNICODE);
    }
    
    // POST: إنشاء أو تعديل أو حذف
    elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? '';
        
        // CREATE: إضافة دورة جديدة
        if ($action === 'create') {
            $title = $data['title'] ?? '';
            $short_desc = $data['short_desc'] ?? '';
            $full_desc = $data['full_desc'] ?? '';
            $category = $data['category'] ?? '';
            $trainer_id = $data['trainer_id'] ?? null;
            $duration = $data['duration'] ?? '';
            $start_date = $data['start_date'] ?? null;
            $end_date = $data['end_date'] ?? null;
            $max_students = $data['max_students'] ?? 30;
            $fees = $data['fees'] ?? 0;
            $image_url = $data['image_url'] ?? '';
            $status = $data['status'] ?? 'active';
            
            // التحقق من البيانات المطلوبة
            if (empty($title)) {
                echo json_encode(['success'=>false,'message'=>'عنوان الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // إدراج الدورة الجديدة
            $stmt = $conn->prepare("INSERT INTO courses (title, short_desc, full_desc, category, trainer_id, duration, start_date, end_date, max_students, fees, image_url, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('ssssisssidss', $title, $short_desc, $full_desc, $category, $trainer_id, $duration, $start_date, $end_date, $max_students, $fees, $image_url, $status);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true,'message'=>'تم إضافة الدورة بنجاح', 'course_id'=>$conn->insert_id], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false,'message'=>'فشل إضافة الدورة: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // UPDATE: تعديل دورة
        elseif ($action === 'update') {
            $course_id = $data['course_id'] ?? 0;
            $title = $data['title'] ?? '';
            $short_desc = $data['short_desc'] ?? '';
            $full_desc = $data['full_desc'] ?? '';
            $category = $data['category'] ?? '';
            $trainer_id = $data['trainer_id'] ?? null;
            $duration = $data['duration'] ?? '';
            $start_date = $data['start_date'] ?? null;
            $end_date = $data['end_date'] ?? null;
            $max_students = $data['max_students'] ?? 30;
            $fees = $data['fees'] ?? 0;
            $image_url = $data['image_url'] ?? '';
            $status = $data['status'] ?? 'active';
            
            if (empty($course_id) || empty($title)) {
                echo json_encode(['success'=>false,'message'=>'بيانات غير كاملة'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // تحديث البيانات
            $stmt = $conn->prepare("UPDATE courses SET title=?, short_desc=?, full_desc=?, category=?, trainer_id=?, duration=?, start_date=?, end_date=?, max_students=?, fees=?, image_url=?, status=? WHERE course_id=?");
            // Corrected type string: fees is decimal (d), image_url is string (s)
            $stmt->bind_param('ssssisssidssi', $title, $short_desc, $full_desc, $category, $trainer_id, $duration, $start_date, $end_date, $max_students, $fees, $image_url, $status, $course_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true,'message'=>'تم تحديث الدورة بنجاح'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false,'message'=>'فشل التحديث: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
        }
        
        // DELETE: حذف دورة
        elseif ($action === 'delete') {
            $course_id = $data['course_id'] ?? 0;
            
            if (empty($course_id)) {
                echo json_encode(['success'=>false,'message'=>'معرف الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            // التحقق من عدم وجود تسجيلات نشطة
            $check = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ? AND status IN ('pending', 'active')");
            $check->bind_param('i', $course_id);
            $check->execute();
            $result = $check->get_result()->fetch_assoc();
            
            if ($result['count'] > 0) {
                echo json_encode(['success'=>false,'message'=>'لا يمكن حذف الدورة لوجود طلاب مسجلين فيها'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
            $stmt->bind_param('i', $course_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true,'message'=>'تم حذف الدورة بنجاح'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false,'message'=>'فشل الحذف: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
        }
        
        else {
            echo json_encode(['success'=>false,'message'=>'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
        }
    }
    
    else {
        echo json_encode(['success'=>false,'message'=>'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
    }
    
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
