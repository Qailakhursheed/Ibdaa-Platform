<?php
/**
 * student_schedule - Protected with Central Security System
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
        $action = $_GET['action'] ?? 'weekly';
        
        if ($action === 'weekly') {
            // Get weekly schedule
            $query = "SELECT 
                        s.*,
                        c.course_name,
                        CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
                        CASE s.day
                            WHEN 'Sunday' THEN 'الأحد'
                            WHEN 'Monday' THEN 'الإثنين'
                            WHEN 'Tuesday' THEN 'الثلاثاء'
                            WHEN 'Wednesday' THEN 'الأربعاء'
                            WHEN 'Thursday' THEN 'الخميس'
                            WHEN 'Friday' THEN 'الجمعة'
                            WHEN 'Saturday' THEN 'السبت'
                        END as day_ar
                      FROM class_schedule s
                      JOIN courses c ON s.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE s.course_id IN (
                          SELECT course_id FROM enrollments WHERE student_id = :student_id
                      )
                      ORDER BY 
                        FIELD(s.day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                        s.start_time";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $schedule
            ]);
            
        } elseif ($action === 'today') {
            // Get today's classes
            $today = date('l'); // Sunday, Monday, etc.
            
            $query = "SELECT 
                        s.*,
                        c.course_name,
                        c.course_description,
                        CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
                        u.photo as trainer_photo
                      FROM class_schedule s
                      JOIN courses c ON s.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE s.course_id IN (
                          SELECT course_id FROM enrollments WHERE student_id = :student_id
                      )
                      AND s.day = :today
                      ORDER BY s.start_time";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':today', $today);
            $stmt->execute();
            $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $classes
            ]);
            
        } elseif ($action === 'upcoming') {
            // Get upcoming classes (next 5)
            $query = "SELECT 
                        s.*,
                        c.course_name,
                        CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
                        CASE s.day
                            WHEN 'Sunday' THEN 'الأحد'
                            WHEN 'Monday' THEN 'الإثنين'
                            WHEN 'Tuesday' THEN 'الثلاثاء'
                            WHEN 'Wednesday' THEN 'الأربعاء'
                            WHEN 'Thursday' THEN 'الخميس'
                            WHEN 'Friday' THEN 'الجمعة'
                            WHEN 'Saturday' THEN 'السبت'
                        END as day_ar,
                        CASE 
                            WHEN s.day = DAYNAME(NOW()) AND s.start_time > TIME(NOW()) THEN 0
                            WHEN DAYOFWEEK(NOW()) <= DAYOFWEEK(STR_TO_DATE(s.day, '%W')) THEN 
                                DAYOFWEEK(STR_TO_DATE(s.day, '%W')) - DAYOFWEEK(NOW())
                            ELSE 
                                7 - DAYOFWEEK(NOW()) + DAYOFWEEK(STR_TO_DATE(s.day, '%W'))
                        END as days_until
                      FROM class_schedule s
                      JOIN courses c ON s.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE s.course_id IN (
                          SELECT course_id FROM enrollments WHERE student_id = :student_id
                      )
                      ORDER BY days_until, s.start_time
                      LIMIT 5";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $upcoming = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $upcoming
            ]);
            
        } elseif ($action === 'export') {
            // Get full schedule for export
            $query = "SELECT 
                        c.course_name as 'اسم الدورة',
                        CASE s.day
                            WHEN 'Sunday' THEN 'الأحد'
                            WHEN 'Monday' THEN 'الإثنين'
                            WHEN 'Tuesday' THEN 'الثلاثاء'
                            WHEN 'Wednesday' THEN 'الأربعاء'
                            WHEN 'Thursday' THEN 'الخميس'
                            WHEN 'Friday' THEN 'الجمعة'
                            WHEN 'Saturday' THEN 'السبت'
                        END as 'اليوم',
                        s.start_time as 'وقت البداية',
                        s.end_time as 'وقت النهاية',
                        s.room as 'القاعة',
                        CONCAT(u.first_name, ' ', u.last_name) as 'المدرب'
                      FROM class_schedule s
                      JOIN courses c ON s.course_id = c.id
                      LEFT JOIN users u ON c.trainer_id = u.id
                      WHERE s.course_id IN (
                          SELECT course_id FROM enrollments WHERE student_id = :student_id
                      )
                      ORDER BY 
                        FIELD(s.day, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                        s.start_time";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $schedule = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $schedule,
                'format' => 'csv'
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
