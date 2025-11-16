<?php
/**
 * Support Tickets API - يربط بين الموقع الخارجي ولوحة التحكم
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

// التحقق من الاتصال
if (!isset($conn)) {
    die(json_encode(['success' => false, 'error' => 'فشل الاتصال بقاعدة البيانات']));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$response = ['success' => false, 'data' => null, 'error' => null];

try {
    switch ($action) {
        // قائمة التذاكر حسب الحالة
        case 'list':
        case 'getAll':
            $status = $_GET['status'] ?? 'pending';
            $stmt = $conn->prepare("
                SELECT * FROM support_tickets 
                WHERE status = ? 
                ORDER BY 
                    CASE priority 
                        WHEN 'high' THEN 1 
                        WHEN 'medium' THEN 2 
                        WHEN 'low' THEN 3 
                    END,
                    created_at DESC
            ");
            $stmt->bind_param("s", $status);
            $stmt->execute();
            $result = $stmt->get_result();
            $tickets = [];
            while ($row = $result->fetch_assoc()) {
                $tickets[] = $row;
            }
            $response = ['success' => true, 'data' => $tickets];
            $stmt->close();
            break;

        // الحصول على تذكرة واحدة
        case 'get':
            $ticket_id = $_GET['id'] ?? $_POST['id'] ?? null;
            if (!$ticket_id) {
                throw new Exception('رقم التذكرة مطلوب');
            }
            
            $stmt = $conn->prepare("SELECT * FROM support_tickets WHERE ticket_id = ?");
            $stmt->bind_param("s", $ticket_id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('التذكرة غير موجودة');
            }
            
            $ticket = $result->fetch_assoc();
            
            // جلب الردود
            $stmt2 = $conn->prepare("
                SELECT * FROM support_responses 
                WHERE ticket_id = ? 
                ORDER BY created_at ASC
            ");
            $stmt2->bind_param("s", $ticket_id);
            $stmt2->execute();
            $responses_result = $stmt2->get_result();
            $responses = [];
            while ($row = $responses_result->fetch_assoc()) {
                $responses[] = $row;
            }
            
            $ticket['responses'] = $responses;
            $response = ['success' => true, 'data' => $ticket];
            
            $stmt->close();
            $stmt2->close();
            break;

        // إضافة رد على تذكرة
        case 'respond':
            $ticket_id = $_POST['ticket_id'] ?? null;
            $message = $_POST['message'] ?? null;
            $user_name = $_POST['user_name'] ?? 'فريق الدعم الفني';
            $user_type = $_POST['user_type'] ?? 'staff';
            
            if (!$ticket_id || !$message) {
                throw new Exception('رقم التذكرة والرسالة مطلوبان');
            }
            
            // إضافة الرد
            $stmt = $conn->prepare("
                INSERT INTO support_responses 
                (ticket_id, user_name, user_type, message, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssss", $ticket_id, $user_name, $user_type, $message);
            $stmt->execute();
            
            // تحديث حالة التذكرة إلى "قيد المعالجة"
            $stmt2 = $conn->prepare("
                UPDATE support_tickets 
                SET status = 'in-progress', updated_at = NOW() 
                WHERE ticket_id = ?
            ");
            $stmt2->bind_param("s", $ticket_id);
            $stmt2->execute();
            
            // إرسال بريد إلكتروني للمستخدم
            $stmt3 = $conn->prepare("SELECT name, email FROM support_tickets WHERE ticket_id = ?");
            $stmt3->bind_param("s", $ticket_id);
            $stmt3->execute();
            $ticket_data = $stmt3->get_result()->fetch_assoc();
            
            if ($ticket_data) {
                $to = $ticket_data['email'];
                $subject = "رد جديد على تذكرتك - $ticket_id";
                $email_message = "
                <html dir='rtl'>
                <head><meta charset='UTF-8'></head>
                <body style='font-family: Cairo, Arial, sans-serif;'>
                    <div style='background: #f3f4f6; padding: 20px;'>
                        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; padding: 30px;'>
                            <h2 style='color: #6366f1;'>رد جديد على تذكرتك 💬</h2>
                            <div style='background: #eff6ff; padding: 20px; border-radius: 8px; margin: 20px 0;'>
                                <p><strong>رقم التذكرة:</strong> $ticket_id</p>
                                <hr>
                                <p><strong>الرد:</strong></p>
                                <p style='background: white; padding: 15px; border-radius: 4px;'>$message</p>
                            </div>
                            <a href='http://localhost/Ibdaa-Taiz/platform/track_ticket.php?id=$ticket_id' 
                               style='display: inline-block; background: #6366f1; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px;'>
                                عرض التذكرة الكاملة
                            </a>
                        </div>
                    </div>
                </body>
                </html>
                ";
                
                $headers = "MIME-Version: 1.0\r\n";
                $headers .= "Content-type: text/html; charset=UTF-8\r\n";
                $headers .= "From: منصة إبداع <support@ibdaa-platform.com>\r\n";
                
                @mail($to, $subject, $email_message, $headers);
            }
            
            $response = ['success' => true, 'message' => 'تم إرسال الرد بنجاح'];
            
            $stmt->close();
            $stmt2->close();
            $stmt3->close();
            break;

        // تغيير حالة التذكرة
        case 'updateStatus':
            $ticket_id = $_POST['ticket_id'] ?? null;
            $status = $_POST['status'] ?? null;
            
            if (!$ticket_id || !$status) {
                throw new Exception('رقم التذكرة والحالة مطلوبان');
            }
            
            $valid_statuses = ['pending', 'in-progress', 'resolved', 'closed'];
            if (!in_array($status, $valid_statuses)) {
                throw new Exception('حالة غير صحيحة');
            }
            
            $resolved_at = ($status === 'resolved' || $status === 'closed') ? 'NOW()' : 'NULL';
            
            $stmt = $conn->prepare("
                UPDATE support_tickets 
                SET status = ?, 
                    resolved_at = IF(? IN ('resolved', 'closed'), NOW(), NULL),
                    updated_at = NOW() 
                WHERE ticket_id = ?
            ");
            $stmt->bind_param("sss", $status, $status, $ticket_id);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'تم تحديث حالة التذكرة'];
            $stmt->close();
            break;

        // إغلاق تذكرة
        case 'close':
            $ticket_id = $_POST['ticket_id'] ?? $_POST['id'] ?? null;
            
            if (!$ticket_id) {
                throw new Exception('رقم التذكرة مطلوب');
            }
            
            $stmt = $conn->prepare("
                UPDATE support_tickets 
                SET status = 'closed', resolved_at = NOW(), updated_at = NOW() 
                WHERE ticket_id = ?
            ");
            $stmt->bind_param("s", $ticket_id);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'تم إغلاق التذكرة'];
            $stmt->close();
            break;

        // إحصائيات الدعم
        case 'stats':
            $stmt = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                    SUM(CASE WHEN status = 'in-progress' THEN 1 ELSE 0 END) as in_progress,
                    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved,
                    SUM(CASE WHEN status = 'closed' THEN 1 ELSE 0 END) as closed,
                    SUM(CASE WHEN priority = 'high' THEN 1 ELSE 0 END) as high_priority,
                    AVG(TIMESTAMPDIFF(HOUR, created_at, resolved_at)) as avg_resolution_hours
                FROM support_tickets
            ");
            
            $stats = $stmt->fetch_assoc();
            $response = ['success' => true, 'data' => $stats];
            break;

        // البحث في التذاكر
        case 'search':
            $query = $_GET['query'] ?? '';
            
            if (empty($query)) {
                throw new Exception('نص البحث مطلوب');
            }
            
            $search_term = "%$query%";
            $stmt = $conn->prepare("
                SELECT * FROM support_tickets 
                WHERE ticket_id LIKE ? 
                   OR name LIKE ? 
                   OR email LIKE ? 
                   OR subject LIKE ? 
                   OR message LIKE ?
                ORDER BY created_at DESC
                LIMIT 50
            ");
            $stmt->bind_param("sssss", $search_term, $search_term, $search_term, $search_term, $search_term);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $tickets = [];
            while ($row = $result->fetch_assoc()) {
                $tickets[] = $row;
            }
            
            $response = ['success' => true, 'data' => $tickets];
            $stmt->close();
            break;

        // حذف تذكرة (للمدير فقط)
        case 'delete':
            $ticket_id = $_POST['ticket_id'] ?? null;
            
            if (!$ticket_id) {
                throw new Exception('رقم التذكرة مطلوب');
            }
            
            // حذف الردود أولاً
            $stmt1 = $conn->prepare("DELETE FROM support_responses WHERE ticket_id = ?");
            $stmt1->bind_param("s", $ticket_id);
            $stmt1->execute();
            $stmt1->close();
            
            // حذف التذكرة
            $stmt2 = $conn->prepare("DELETE FROM support_tickets WHERE ticket_id = ?");
            $stmt2->bind_param("s", $ticket_id);
            $stmt2->execute();
            $stmt2->close();
            
            $response = ['success' => true, 'message' => 'تم حذف التذكرة'];
            break;

        default:
            throw new Exception('إجراء غير معروف');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
