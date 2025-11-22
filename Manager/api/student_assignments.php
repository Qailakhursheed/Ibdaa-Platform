<?php
/**
 * student_assignments - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// Check authentication
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'student') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$student_id = $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'];

try {
    $database = new Database();
    $db = $database->getConnection();
    
    if ($method === 'GET') {
        $action = $_GET['action'] ?? 'list';
        
        if ($action === 'list') {
            // Get all assignments
            $course_id = $_GET['course_id'] ?? null;
            
            $query = "SELECT 
                        a.*,
                        c.course_name,
                        s.id as submission_id,
                        s.submission_date,
                        s.file_path as submission_file,
                        s.grade as submission_grade,
                        s.feedback,
                        CASE WHEN s.id IS NOT NULL THEN 1 ELSE 0 END as submitted
                      FROM assignments a
                      JOIN courses c ON a.course_id = c.id
                      LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = :student_id
                      WHERE c.id IN (
                          SELECT course_id FROM enrollments WHERE student_id = :student_id2
                      )";
            
            if ($course_id) {
                $query .= " AND a.course_id = :course_id";
            }
            
            $query .= " ORDER BY a.due_date ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':student_id2', $student_id);
            if ($course_id) $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $assignments
            ]);
            
        } elseif ($action === 'details' && isset($_GET['assignment_id'])) {
            // Get assignment details
            $assignment_id = $_GET['assignment_id'];
            
            $query = "SELECT 
                        a.*,
                        c.course_name,
                        s.id as submission_id,
                        s.submission_date,
                        s.file_path as submission_file,
                        s.grade as submission_grade,
                        s.feedback,
                        s.graded_by,
                        s.graded_at,
                        CONCAT(u.first_name, ' ', u.last_name) as grader_name
                      FROM assignments a
                      JOIN courses c ON a.course_id = c.id
                      LEFT JOIN assignment_submissions s ON a.id = s.assignment_id AND s.student_id = :student_id
                      LEFT JOIN users u ON s.graded_by = u.id
                      WHERE a.id = :assignment_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':assignment_id', $assignment_id);
            $stmt->execute();
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $assignment
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Submit assignment
        $assignment_id = $_POST['assignment_id'] ?? null;
        
        if (!$assignment_id) {
            echo json_encode([
                'success' => false,
                'message' => 'معرف الواجب مطلوب'
            ]);
            exit;
        }
        
        // Check if assignment exists and student is enrolled
        $checkQuery = "SELECT a.* FROM assignments a
                       JOIN enrollments e ON a.course_id = e.course_id
                       WHERE a.id = :assignment_id AND e.student_id = :student_id";
        $stmt = $db->prepare($checkQuery);
        $stmt->bindParam(':assignment_id', $assignment_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            echo json_encode([
                'success' => false,
                'message' => 'الواجب غير موجود أو غير مسموح'
            ]);
            exit;
        }
        
        // Handle file upload
        $file_path = null;
        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = '../uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            $file_extension = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $file_name = 'assignment_' . $assignment_id . '_student_' . $student_id . '_' . time() . '.' . $file_extension;
            $file_path = $upload_dir . $file_name;
            
            if (!move_uploaded_file($_FILES['file']['tmp_name'], $file_path)) {
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل رفع الملف'
                ]);
                exit;
            }
            
            $file_path = 'uploads/assignments/' . $file_name;
        }
        
        // Check if submission already exists
        $checkSubmission = "SELECT id FROM assignment_submissions 
                           WHERE assignment_id = :assignment_id AND student_id = :student_id";
        $stmt = $db->prepare($checkSubmission);
        $stmt->bindParam(':assignment_id', $assignment_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->execute();
        
        if ($stmt->rowCount() > 0) {
            // Update existing submission
            $query = "UPDATE assignment_submissions 
                      SET file_path = :file_path,
                          submission_date = NOW(),
                          updated_at = NOW()
                      WHERE assignment_id = :assignment_id AND student_id = :student_id";
        } else {
            // Insert new submission
            $query = "INSERT INTO assignment_submissions 
                      (assignment_id, student_id, file_path, submission_date) 
                      VALUES (:assignment_id, :student_id, :file_path, NOW())";
        }
        
        $stmt = $db->prepare($query);
        $stmt->bindParam(':assignment_id', $assignment_id);
        $stmt->bindParam(':student_id', $student_id);
        $stmt->bindParam(':file_path', $file_path);
        
        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم تسليم الواجب بنجاح',
                'file_path' => $file_path
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'فشل تسليم الواجب'
            ]);
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم: ' . $e->getMessage()
    ]);
}
