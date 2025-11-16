<?php
/**
 * Student Attendance API
 * Handles student attendance records
 */

session_start();
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
            // Get attendance records
            $course_id = $_GET['course_id'] ?? null;
            $period = $_GET['period'] ?? null;
            
            $query = "SELECT 
                        a.*,
                        c.course_name,
                        CASE 
                            WHEN a.status = 'present' THEN 'حضور'
                            WHEN a.status = 'absent' THEN 'غياب'
                            WHEN a.status = 'excused' THEN 'عذر'
                            ELSE a.status
                        END as status_ar
                      FROM attendance a
                      JOIN courses c ON a.course_id = c.id
                      WHERE a.student_id = :student_id";
            
            if ($course_id) {
                $query .= " AND a.course_id = :course_id";
            }
            
            if ($period === 'week') {
                $query .= " AND a.date >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
            } elseif ($period === 'month') {
                $query .= " AND a.date >= DATE_SUB(NOW(), INTERVAL 30 DAY)";
            }
            
            $query .= " ORDER BY a.date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            if ($course_id) $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $attendance = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $attendance
            ]);
            
        } elseif ($action === 'summary') {
            // Get attendance summary
            $course_id = $_GET['course_id'] ?? null;
            
            $query = "SELECT 
                        COUNT(*) as total_sessions,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent,
                        SUM(CASE WHEN status = 'excused' THEN 1 ELSE 0 END) as excused,
                        ROUND(SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 1) as attendance_rate
                      FROM attendance
                      WHERE student_id = :student_id";
            
            if ($course_id) {
                $query .= " AND course_id = :course_id";
            }
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            if ($course_id) $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $summary = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Calculate warnings (1 warning per 3 absences)
            $warnings = floor($summary['absent'] / 3);
            $summary['warnings'] = $warnings;
            
            echo json_encode([
                'success' => true,
                'data' => $summary
            ]);
            
        } elseif ($action === 'monthly_chart') {
            // Get monthly attendance for chart
            $course_id = $_GET['course_id'] ?? null;
            
            $query = "SELECT 
                        DATE_FORMAT(date, '%Y-%m') as month,
                        WEEK(date) as week,
                        SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present,
                        SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent
                      FROM attendance
                      WHERE student_id = :student_id
                      AND date >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
            
            if ($course_id) {
                $query .= " AND course_id = :course_id";
            }
            
            $query .= " GROUP BY YEARWEEK(date) ORDER BY date";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            if ($course_id) $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $chart_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $chart_data
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
