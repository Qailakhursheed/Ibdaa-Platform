<?php
/**
 * student_id_card - Protected with Central Security System
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
        $action = $_GET['action'] ?? 'info';
        
        if ($action === 'info') {
            // Get ID card information
            $query = "SELECT 
                        u.id,
                        u.first_name,
                        u.last_name,
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.email,
                        u.phone,
                        u.photo,
                        u.created_at as enrollment_date,
                        idc.card_number,
                        idc.issue_date,
                        idc.expiry_date,
                        idc.qr_code,
                        idc.status,
                        idc.barcode,
                        CASE 
                            WHEN COUNT(DISTINCT e.course_id) <= 3 THEN 'المستوى الأول'
                            WHEN COUNT(DISTINCT e.course_id) <= 6 THEN 'المستوى الثاني'
                            WHEN COUNT(DISTINCT e.course_id) <= 9 THEN 'المستوى الثالث'
                            ELSE 'المستوى الرابع'
                        END as level,
                        'علوم الحاسوب' as major
                      FROM users u
                      LEFT JOIN student_id_cards idc ON u.id = idc.student_id
                      LEFT JOIN enrollments e ON u.id = e.student_id
                      WHERE u.id = :student_id
                      GROUP BY u.id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $card_info = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card_info) {
                echo json_encode([
                    'success' => false,
                    'message' => 'معلومات البطاقة غير موجودة'
                ]);
                exit;
            }
            
            // Generate card number if not exists
            if (!$card_info['card_number']) {
                $card_number = 'STD' . date('Y') . str_pad($student_id, 6, '0', STR_PAD_LEFT);
                $card_info['card_number'] = $card_number;
                
                // Generate QR code data (JSON encoded)
                $qr_data = json_encode([
                    'student_id' => $student_id,
                    'card_number' => $card_number,
                    'name' => $card_info['full_name'],
                    'issue_date' => date('Y-m-d')
                ]);
                
                // Insert card info
                $insertQuery = "INSERT INTO student_id_cards 
                               (student_id, card_number, issue_date, expiry_date, qr_code, status) 
                               VALUES (:student_id, :card_number, NOW(), DATE_ADD(NOW(), INTERVAL 2 YEAR), :qr_code, 'active')
                               ON DUPLICATE KEY UPDATE 
                               card_number = VALUES(card_number),
                               qr_code = VALUES(qr_code)";
                $stmt = $db->prepare($insertQuery);
                $stmt->bindParam(':student_id', $student_id);
                $stmt->bindParam(':card_number', $card_number);
                $stmt->bindParam(':qr_code', $qr_data);
                $stmt->execute();
                
                $card_info['issue_date'] = date('Y-m-d');
                $card_info['expiry_date'] = date('Y-m-d', strtotime('+2 years'));
                $card_info['qr_code'] = $qr_data;
                $card_info['status'] = 'active';
            }
            
            // Format student number
            $card_info['student_number'] = $card_info['card_number'];
            
            echo json_encode([
                'success' => true,
                'data' => $card_info
            ]);
            
        } elseif ($action === 'download') {
            // Download ID card as PDF or PNG
            $format = $_GET['format'] ?? 'pdf';
            
            // Get card info
            $query = "SELECT 
                        u.*,
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        idc.card_number,
                        idc.issue_date,
                        idc.expiry_date,
                        idc.qr_code
                      FROM users u
                      LEFT JOIN student_id_cards idc ON u.id = idc.student_id
                      WHERE u.id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$card) {
                echo json_encode([
                    'success' => false,
                    'message' => 'البطاقة غير موجودة'
                ]);
                exit;
            }
            
            // Log download
            $logQuery = "INSERT INTO id_card_downloads 
                        (student_id, download_date, format) 
                        VALUES (:student_id, NOW(), :format)";
            $stmt = $db->prepare($logQuery);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':format', $format);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'جاري تحميل البطاقة',
                'data' => [
                    'format' => $format,
                    'filename' => 'id_card_' . $card['card_number'] . '.' . $format
                ]
            ]);
            
        } elseif ($action === 'verify' && isset($_GET['card_number'])) {
            // Verify ID card
            $card_number = $_GET['card_number'];
            
            $query = "SELECT 
                        u.id,
                        CONCAT(u.first_name, ' ', u.last_name) as full_name,
                        u.photo,
                        idc.card_number,
                        idc.issue_date,
                        idc.expiry_date,
                        idc.status,
                        CASE 
                            WHEN idc.expiry_date < NOW() THEN 'منتهية'
                            WHEN idc.status = 'active' THEN 'صالحة'
                            ELSE 'غير صالحة'
                        END as validity
                      FROM student_id_cards idc
                      JOIN users u ON idc.student_id = u.id
                      WHERE idc.card_number = :card_number";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':card_number', $card_number);
            $stmt->execute();
            $card = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($card) {
                echo json_encode([
                    'success' => true,
                    'valid' => $card['validity'] === 'صالحة',
                    'data' => $card
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'valid' => false,
                    'message' => 'البطاقة غير موجودة'
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
