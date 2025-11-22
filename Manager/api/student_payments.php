<?php
/**
 * student_payments - Protected with Central Security System
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
            // Get all payments
            $query = "SELECT 
                        p.*,
                        CASE 
                            WHEN p.status = 'paid' THEN 'مدفوعة'
                            WHEN p.status = 'pending' THEN 'معلقة'
                            WHEN p.status = 'overdue' THEN 'متأخرة'
                            WHEN p.status = 'cancelled' THEN 'ملغاة'
                            ELSE p.status
                        END as status_ar,
                        CASE p.payment_type
                            WHEN 'tuition' THEN 'رسوم دراسية'
                            WHEN 'registration' THEN 'رسوم تسجيل'
                            WHEN 'exam' THEN 'رسوم امتحان'
                            WHEN 'materials' THEN 'رسوم مواد'
                            WHEN 'lab' THEN 'رسوم معامل'
                            ELSE p.payment_type
                        END as type_ar
                      FROM student_payments p
                      WHERE p.student_id = :student_id
                      ORDER BY p.payment_date DESC, p.due_date DESC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $payments = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $payments
            ]);
            
        } elseif ($action === 'balance') {
            // Get balance summary
            $query = "SELECT 
                        SUM(amount) as total_amount,
                        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as paid_amount,
                        SUM(CASE WHEN status != 'paid' THEN amount ELSE 0 END) as outstanding_amount,
                        COUNT(*) as total_payments,
                        COUNT(CASE WHEN status = 'paid' THEN 1 END) as paid_count,
                        COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                        COUNT(CASE WHEN status = 'overdue' THEN 1 END) as overdue_count
                      FROM student_payments
                      WHERE student_id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $balance = $stmt->fetch(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $balance
            ]);
            
        } elseif ($action === 'outstanding') {
            // Get outstanding invoices
            $query = "SELECT 
                        p.*,
                        DATEDIFF(NOW(), p.due_date) as days_overdue,
                        CASE 
                            WHEN p.status = 'pending' THEN 'معلقة'
                            WHEN p.status = 'overdue' THEN 'متأخرة'
                            ELSE p.status
                        END as status_ar
                      FROM student_payments p
                      WHERE p.student_id = :student_id
                      AND p.status IN ('pending', 'overdue')
                      ORDER BY p.due_date ASC";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $outstanding = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            echo json_encode([
                'success' => true,
                'data' => $outstanding
            ]);
            
        } elseif ($action === 'receipt' && isset($_GET['payment_id'])) {
            // Get payment receipt
            $payment_id = $_GET['payment_id'];
            
            $query = "SELECT 
                        p.*,
                        CONCAT(u.first_name, ' ', u.last_name) as student_name,
                        u.email as student_email,
                        CASE p.payment_type
                            WHEN 'tuition' THEN 'رسوم دراسية'
                            WHEN 'registration' THEN 'رسوم تسجيل'
                            WHEN 'exam' THEN 'رسوم امتحان'
                            WHEN 'materials' THEN 'رسوم مواد'
                            WHEN 'lab' THEN 'رسوم معامل'
                            ELSE p.payment_type
                        END as type_ar
                      FROM student_payments p
                      JOIN users u ON p.student_id = u.id
                      WHERE p.id = :payment_id AND p.student_id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->execute();
            $receipt = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($receipt) {
                echo json_encode([
                    'success' => true,
                    'data' => $receipt
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'الإيصال غير موجود'
                ]);
            }
        }
        
    } elseif ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $action = $data['action'] ?? 'pay';
        
        if ($action === 'request_plan') {
            // Request payment plan
            $payment_id = $data['payment_id'] ?? null;
            $installments = $data['installments'] ?? 3;
            $reason = $data['reason'] ?? '';
            
            if (!$payment_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'معرف الدفعة مطلوب'
                ]);
                exit;
            }
            
            // Create payment plan request
            $query = "INSERT INTO payment_plan_requests 
                     (student_id, payment_id, requested_installments, reason, request_date, status) 
                     VALUES (:student_id, :payment_id, :installments, :reason, NOW(), 'pending')";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->bindParam(':installments', $installments);
            $stmt->bindParam(':reason', $reason);
            
            if ($stmt->execute()) {
                echo json_encode([
                    'success' => true,
                    'message' => 'تم تقديم طلب خطة الدفع بنجاح'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل تقديم الطلب'
                ]);
            }
            
        } elseif ($action === 'pay') {
            // Process payment (placeholder - integrate with payment gateway)
            $payment_id = $data['payment_id'] ?? null;
            $payment_method = $data['payment_method'] ?? 'cash';
            
            if (!$payment_id) {
                echo json_encode([
                    'success' => false,
                    'message' => 'معرف الدفعة مطلوب'
                ]);
                exit;
            }
            
            // Update payment status
            $query = "UPDATE student_payments 
                      SET status = 'paid',
                          payment_date = NOW(),
                          payment_method = :payment_method,
                          updated_at = NOW()
                      WHERE id = :payment_id AND student_id = :student_id";
            
            $stmt = $db->prepare($query);
            $stmt->bindParam(':payment_id', $payment_id);
            $stmt->bindParam(':student_id', $student_id);
            $stmt->bindParam(':payment_method', $payment_method);
            
            if ($stmt->execute()) {
                // Log transaction
                $logQuery = "INSERT INTO payment_transactions 
                            (payment_id, student_id, amount, transaction_date, payment_method) 
                            SELECT amount, student_id, amount, NOW(), :payment_method
                            FROM student_payments 
                            WHERE id = :payment_id";
                $stmt = $db->prepare($logQuery);
                $stmt->bindParam(':payment_id', $payment_id);
                $stmt->bindParam(':payment_method', $payment_method);
                $stmt->execute();
                
                echo json_encode([
                    'success' => true,
                    'message' => 'تم الدفع بنجاح'
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل معالجة الدفع'
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
