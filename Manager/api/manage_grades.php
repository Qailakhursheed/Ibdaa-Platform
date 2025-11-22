<?php
/**
 * Grades Management API
 * إدارة الدرجات - محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من الصلاحيات (مدراء ومدربين فقط)
$user = APIAuth::requireAuth(['manager', 'technical', 'trainer']);
APIAuth::rateLimit(120, 60);

header('Content-Type: application/json; charset=utf-8');

// التحقق من الصلاحيات - للمدراء والمدربين
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical', 'trainer'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    // GET: جلب الدرجات
    if ($method === 'GET') {
        $course_id = $_GET['course_id'] ?? null;
        $user_id_filter = $_GET['user_id'] ?? null;
        
        // بناء الاستعلام
        $query = "SELECT g.*, u.full_name, u.email, c.title as course_title 
                  FROM grades g
                  JOIN users u ON g.user_id = u.id
                  JOIN courses c ON g.course_id = c.course_id
                  WHERE 1=1";
        
        $params = [];
        $types = '';
        
        if ($course_id) {
            $query .= " AND g.course_id = ?";
            $params[] = intval($course_id);
            $types .= 'i';
        }
        
        if ($user_id_filter) {
            $query .= " AND g.user_id = ?";
            $params[] = intval($user_id_filter);
            $types .= 'i';
        }
        
        $query .= " ORDER BY g.created_at DESC";
        
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            $grades[] = $row;
        }
        
        echo json_encode(['success'=>true, 'data'=>$grades], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // POST: إضافة أو تعديل أو حذف درجة
    if ($method === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';
        
        // CREATE: إضافة درجة
        if ($action === 'create') {
            $student_id = intval($input['user_id'] ?? 0);
            $course_id = intval($input['course_id'] ?? 0);
            $assignment_name = $input['assignment_name'] ?? '';
            $grade_value = floatval($input['grade_value'] ?? 0);
            $max_grade = floatval($input['max_grade'] ?? 100);
            $notes = $input['notes'] ?? '';
            
            if ($student_id <= 0 || $course_id <= 0) {
                echo json_encode(['success'=>false,'message'=>'بيانات غير كاملة'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $conn->prepare("INSERT INTO grades (user_id, course_id, assignment_name, grade_value, max_grade, notes) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->bind_param('iisdds', $student_id, $course_id, $assignment_name, $grade_value, $max_grade, $notes);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true, 'message'=>'تم إضافة الدرجة بنجاح', 'grade_id'=>$conn->insert_id], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false, 'message'=>'فشل الإضافة: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // UPDATE: تعديل درجة
        if ($action === 'update') {
            $grade_id = intval($input['grade_id'] ?? 0);
            $grade_value = floatval($input['grade_value'] ?? 0);
            $max_grade = floatval($input['max_grade'] ?? 100);
            $assignment_name = $input['assignment_name'] ?? '';
            $notes = $input['notes'] ?? '';
            
            if ($grade_id <= 0) {
                echo json_encode(['success'=>false,'message'=>'معرف الدرجة مطلوب'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $conn->prepare("UPDATE grades SET assignment_name=?, grade_value=?, max_grade=?, notes=? WHERE grade_id=?");
            $stmt->bind_param('sddsi', $assignment_name, $grade_value, $max_grade, $notes, $grade_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true, 'message'=>'تم تحديث الدرجة بنجاح'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false, 'message'=>'فشل التحديث: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // DELETE: حذف درجة
        if ($action === 'delete') {
            $grade_id = intval($input['grade_id'] ?? 0);
            
            if ($grade_id <= 0) {
                echo json_encode(['success'=>false,'message'=>'معرف الدرجة مطلوب'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $stmt = $conn->prepare("DELETE FROM grades WHERE grade_id = ?");
            $stmt->bind_param('i', $grade_id);
            
            if ($stmt->execute()) {
                echo json_encode(['success'=>true, 'message'=>'تم حذف الدرجة بنجاح'], JSON_UNESCAPED_UNICODE);
            } else {
                echo json_encode(['success'=>false, 'message'=>'فشل الحذف: ' . $stmt->error], JSON_UNESCAPED_UNICODE);
            }
            exit;
        }
        
        // BATCH_CREATE: إضافة درجات متعددة دفعة واحدة
        if ($action === 'batch_create') {
            $grades_data = $input['grades'] ?? [];
            
            if (empty($grades_data)) {
                echo json_encode(['success'=>false,'message'=>'لا توجد بيانات للحفظ'], JSON_UNESCAPED_UNICODE);
                exit;
            }
            
            $success_count = 0;
            $failed_count = 0;
            
            foreach ($grades_data as $grade) {
                $student_id = intval($grade['user_id'] ?? 0);
                $course_id = intval($grade['course_id'] ?? 0);
                $assignment_name = $grade['assignment_name'] ?? '';
                $grade_value = floatval($grade['grade_value'] ?? 0);
                $max_grade = floatval($grade['max_grade'] ?? 100);
                
                if ($student_id > 0 && $course_id > 0) {
                    $stmt = $conn->prepare("INSERT INTO grades (user_id, course_id, assignment_name, grade_value, max_grade) VALUES (?, ?, ?, ?, ?)");
                    $stmt->bind_param('iisdd', $student_id, $course_id, $assignment_name, $grade_value, $max_grade);
                    
                    if ($stmt->execute()) {
                        $success_count++;
                    } else {
                        $failed_count++;
                    }
                }
            }
            
            echo json_encode([
                'success'=>true,
                'message'=>"تم حفظ {$success_count} درجة. فشل {$failed_count}",
                'saved'=>$success_count,
                'failed'=>$failed_count
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode(['success'=>false,'message'=>'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    echo json_encode(['success'=>false,'message'=>'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success'=>false, 'message'=>'خطأ في الخادم: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
