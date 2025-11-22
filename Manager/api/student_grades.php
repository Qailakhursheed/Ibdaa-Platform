<?php
/**
 * student_grades - Protected with Central Security System
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
            // Get all grades
            $course_id = $_GET['course_id'] ?? null;
            $semester = $_GET['semester'] ?? null;
            
            $query = "SELECT 
                        g.*,
                        c.course_name,
                        c.credits,
                        (g.assignments + g.quizzes + g.midterm + g.final) as total_grade,
                        CASE 
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 90 THEN 'A+'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 85 THEN 'A'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 80 THEN 'B+'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 75 THEN 'B'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 70 THEN 'C+'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 65 THEN 'C'
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 60 THEN 'D'
                            ELSE 'F'
                        END as letter_grade,
                        CASE 
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 90 THEN 4.0
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 85 THEN 3.7
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 80 THEN 3.3
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 75 THEN 3.0
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 70 THEN 2.7
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 65 THEN 2.3
                            WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 60 THEN 2.0
                            ELSE 0.0
                        END as grade_point
                      FROM grades g
                      JOIN courses c ON g.course_id = c.id
                      WHERE g.student_id = :student_id";
            
            if ($course_id) {
                $query .= " AND g.course_id = :course_id";
            }
            if ($semester) {
                $query .= " AND g.semester = :semester";
            }
            
            $query .= " ORDER BY g.created_at DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            if ($course_id) $stmt->bindParam(':course_id', $course_id);
            if ($semester) $stmt->bindParam(':semester', $semester);
            $stmt->execute();
            $grades = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $grades
            ]);
            
        } elseif ($action === 'gpa') {
            // Calculate GPA
            $semester = $_GET['semester'] ?? null;
            
            $query = "SELECT 
                        COUNT(*) as total_courses,
                        SUM(c.credits) as total_credits,
                        SUM(
                            CASE 
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 90 THEN 4.0 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 85 THEN 3.7 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 80 THEN 3.3 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 75 THEN 3.0 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 70 THEN 2.7 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 65 THEN 2.3 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 60 THEN 2.0 * c.credits
                                ELSE 0.0
                            END
                        ) as total_points
                      FROM grades g
                      JOIN courses c ON g.course_id = c.id
                      WHERE g.student_id = :student_id";
            
            if ($semester) {
                $query .= " AND g.semester = :semester";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            if ($semester) $stmt->bindParam(':semester', $semester);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            $gpa = $result['total_credits'] > 0 
                ? round($result['total_points'] / $result['total_credits'], 2)
                : 0.00;
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'gpa' => $gpa,
                    'total_courses' => $result['total_courses'],
                    'total_credits' => $result['total_credits'],
                    'semester' => $semester ?? 'cumulative'
                ]
            ]);
            
        } elseif ($action === 'trend') {
            // Get GPA trend over semesters
            $query = "SELECT 
                        g.semester,
                        COUNT(*) as courses,
                        SUM(c.credits) as credits,
                        ROUND(SUM(
                            CASE 
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 90 THEN 4.0 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 85 THEN 3.7 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 80 THEN 3.3 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 75 THEN 3.0 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 70 THEN 2.7 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 65 THEN 2.3 * c.credits
                                WHEN (g.assignments + g.quizzes + g.midterm + g.final) >= 60 THEN 2.0 * c.credits
                                ELSE 0.0
                            END
                        ) / SUM(c.credits), 2) as gpa
                      FROM grades g
                      JOIN courses c ON g.course_id = c.id
                      WHERE g.student_id = :student_id
                      GROUP BY g.semester
                      ORDER BY g.semester";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $trend
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
