<?php
/**
 * Student Materials API
 * Handles course materials access and downloads
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
            // Get course materials
            $course_id = $_GET['course_id'] ?? null;
            
            if (!$course_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'معرف الدورة مطلوب'
                ]);
                exit;
            }
            
            // Check if student is enrolled
            $enrollCheck = "SELECT id FROM enrollments 
                           WHERE student_id = :student_id AND course_id = :course_id";
            $stmt = $db->prepare($enrollCheck);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'غير مسجل في هذه الدورة'
                ]);
                exit;
            }
            
            // Get materials
            $query = "SELECT 
                        m.*,
                        c.course_name,
                        CONCAT(u.first_name, ' ', u.last_name) as uploaded_by_name,
                        d.download_date,
                        d.download_count
                      FROM course_materials m
                      JOIN courses c ON m.course_id = c.id
                      LEFT JOIN users u ON m.uploaded_by = u.id
                      LEFT JOIN (
                          SELECT material_id, 
                                 MAX(download_date) as download_date,
                                 COUNT(*) as download_count
                          FROM material_downloads
                          WHERE student_id = :student_id
                          GROUP BY material_id
                      ) d ON m.id = d.material_id
                      WHERE m.course_id = :course_id
                      ORDER BY m.upload_date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $course_id);
            $stmt->execute();
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $materials
            ]);
            
        } elseif ($action === 'download' && isset($_GET['material_id'])) {
            // Track material download
            $material_id = $_GET['material_id'];
            
            // Get material info
            $query = "SELECT m.*, c.course_name 
                      FROM course_materials m
                      JOIN courses c ON m.course_id = c.id
                      WHERE m.id = :material_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':material_id', $material_id);
            $stmt->execute();
            $material = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$material) {
                echo json_encode([
                    'success' => false,
                    'message' => 'المادة غير موجودة'
                ]);
                exit;
            }
            
            // Check enrollment
            $enrollCheck = "SELECT id FROM enrollments 
                           WHERE student_id = :student_id AND course_id = :course_id";
            $stmt = $db->prepare($enrollCheck);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':course_id', $material['course_id']);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                echo json_encode([
                    'success' => false,
                    'message' => 'غير مسموح بتحميل هذه المادة'
                ]);
                exit;
            }
            
            // Log download
            $logQuery = "INSERT INTO material_downloads 
                        (material_id, student_id, download_date) 
                        VALUES (:material_id, :student_id, NOW())";
            $stmt = $db->prepare($logQuery);
            $stmt->bindParam(':material_id', $material_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'تم تسجيل التحميل',
                'data' => [
                    'file_url' => $material['file_path'],
                    'file_name' => $material['title']
                ]
            ]);
            
        } elseif ($action === 'statistics') {
            // Get materials statistics
            $query = "SELECT 
                        COUNT(DISTINCT cm.id) as total_materials,
                        SUM(CASE WHEN cm.type = 'pdf' THEN 1 ELSE 0 END) as pdf_count,
                        SUM(CASE WHEN cm.type = 'video' THEN 1 ELSE 0 END) as video_count,
                        SUM(CASE WHEN cm.type = 'ppt' THEN 1 ELSE 0 END) as ppt_count,
                        COUNT(DISTINCT md.material_id) as downloaded_count
                      FROM enrollments e
                      JOIN course_materials cm ON e.course_id = cm.course_id
                      LEFT JOIN material_downloads md ON cm.id = md.material_id AND md.student_id = :student_id
                      WHERE e.student_id = :student_id2";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':student_id2', $student_id);
            $stmt->execute();
            $stats = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $stats
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
