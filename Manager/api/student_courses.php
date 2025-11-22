<?php
/**
 * student_courses - Protected with Central Security System
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
            // Get all enrolled courses
            $query = "SELECT 
                        c.id,
                        c.course_name,
                        c.course_description,
                        c.duration,
                        c.start_date,
                        c.end_date,
                        c.image,
                        CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
                        e.enrollment_date,
                        e.progress,
                        e.status,
                        (SELECT COUNT(*) FROM course_materials WHERE course_id = c.id) as materials_count,
                        (SELECT COUNT(*) FROM assignments WHERE course_id = c.id) as assignments_count
                      FROM enrollments e
                      JOIN courses c ON e.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE e.student_id = :student_id
                      ORDER BY e.enrollment_date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $courses = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $courses
            ]);
            
        } elseif ($action === 'details' && isset($_GET['course_id'])) {
            // Get course details
            $course_id = $_GET['course_id'];
            
            $query = "SELECT 
                        c.*,
                        CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
                        u.email as trainer_email,
                        u.phone as trainer_phone,
                        e.enrollment_date,
                        e.progress,
                        e.status,
                        e.completion_date
                      FROM enrollments e
                      JOIN courses c ON e.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE e.student_id = :student_id AND c.id = :course_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $course = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($course) {
                // Get course modules/sections
                $moduleQuery = "SELECT * FROM course_modules 
                               WHERE course_id = :course_id 
                               ORDER BY module_order";
                $stmt = $db->prepare($moduleQuery);
                $stmt->bindParam(':course_id', $course_id);
                $stmt->execute();
                $course['modules'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                echo json_encode([
                    'success' => true,
                    'data' => $course
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'الدورة غير موجودة'
                ]);
            }
            
        } elseif ($action === 'statistics') {
            // Get course statistics
            $query = "SELECT 
                        COUNT(*) as total_courses,
                        SUM(CASE WHEN e.status = 'active' THEN 1 ELSE 0 END) as active_courses,
                        SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_courses,
                        AVG(e.progress) as avg_progress
                      FROM enrollments e
                      WHERE e.student_id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
            ]);
        }
        
    } elseif ($method === 'POST') {
        // Update course progress
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (isset($data['course_id']) && isset($data['progress'])) {
            $query = "UPDATE enrollments 
                      SET progress = :progress,
                          status = CASE WHEN :progress >= 100 THEN 'completed' ELSE status END,
                          completion_date = CASE WHEN :progress >= 100 THEN NOW() ELSE NULL END
                      WHERE student_id = :student_id AND course_id = :course_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':progress', $data['progress']);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $data['course_id']);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تحديث التقدم بنجاح'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل تحديث التقدم'
                ]);
            }
        }
    }
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'خطأ في الخادم: ' . $e->getMessage()
    ]);
}
