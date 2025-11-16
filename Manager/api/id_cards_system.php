<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;

header('Content-Type: application/json; charset=utf-8');

/**
 * نظام إصدار البطاقات المتقدم - Advanced ID Card System
 * الميزات:
 * - إصدار بطاقات بتصميم احترافي
 * - QR Code و Barcode
 * - تنزيل البطاقات بصيغة PDF/PNG
 * - تتبع حالة الدفع
 * - إرسال البطاقة للطالب تلقائياً
 */

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الصلاحيات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    respond(['success' => false, 'message' => 'غير مصرح - يجب تسجيل الدخول'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // ==============================================
    // POST: إصدار بطاقة جديدة
    // ==============================================
    if ($method === 'POST' && $action === 'generate') {
        
        if (!in_array($user_role, ['manager', 'technical'], true)) {
            respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
        }
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $student_id = (int)($data['user_id'] ?? 0);
        $course_id = (int)($data['course_id'] ?? 0);
        
        if ($student_id <= 0) {
            respond(['success' => false, 'message' => 'معرّف الطالب مطلوب'], 400);
        }
        
        // جلب بيانات الطالب الكاملة
        $user_stmt = $conn->prepare("
            SELECT 
                u.*,
                c.title AS course_title,
                c.region AS course_region,
                c.city AS course_city,
                c.start_date,
                c.end_date
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.id = ? AND u.role = 'student'
            LIMIT 1
        ");
        $user_stmt->bind_param('i', $student_id);
        $user_stmt->execute();
        $user_result = $user_stmt->get_result();
        
        if ($user_result->num_rows === 0) {
            $user_stmt->close();
            respond(['success' => false, 'message' => 'الطالب غير موجود'], 404);
        }
        
        $student = $user_result->fetch_assoc();
        $user_stmt->close();
        
        // التحقق من حالة الدفع
        $payment_stmt = $conn->prepare("
            SELECT 
                COALESCE(SUM(amount), 0) AS total_paid,
                c.fees AS total_fees
            FROM payments p
            LEFT JOIN enrollments e ON p.user_id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE p.user_id = ? AND p.status = 'completed'
            GROUP BY c.fees
        ");
        $payment_stmt->bind_param('i', $student_id);
        $payment_stmt->execute();
        $payment_result = $payment_stmt->get_result();
        $payment_info = $payment_result->fetch_assoc();
        $payment_stmt->close();
        
        $total_paid = (float)($payment_info['total_paid'] ?? 0);
        $total_fees = (float)($payment_info['total_fees'] ?? 0);
        $payment_complete = ($total_paid >= $total_fees) && ($total_fees > 0);
        
        // إنشاء رقم بطاقة فريد
        $card_number = 'IBD-' . date('Y') . '-' . str_pad($student_id, 5, '0', STR_PAD_LEFT);
        
        // التحقق من وجود بطاقة سابقة
        $check_stmt = $conn->prepare("SELECT card_id FROM id_cards WHERE user_id = ?");
        $check_stmt->bind_param('i', $student_id);
        $check_stmt->execute();
        $check_stmt->store_result();
        
        if ($check_stmt->num_rows > 0) {
            $check_stmt->close();
            respond(['success' => false, 'message' => 'البطاقة موجودة مسبقاً لهذا الطالب'], 409);
        }
        $check_stmt->close();
        
        // إنشاء QR Code
        $qr_data = json_encode([
            'card_number' => $card_number,
            'student_id' => $student_id,
            'name' => $student['full_name'],
            'course' => $student['course_title'] ?? 'غير محدد',
            'issued' => date('Y-m-d')
        ], JSON_UNESCAPED_UNICODE);
        
        $qr_code = new QrCode($qr_data);
        $qr_code->setSize(300);
        $qr_code->setMargin(10);
        
        $writer = new PngWriter();
        $result = $writer->write($qr_code);
        
        // حفظ QR Code
        $qr_dir = __DIR__ . '/../../uploads/qrcodes/';
        if (!is_dir($qr_dir)) {
            mkdir($qr_dir, 0755, true);
        }
        
        $qr_filename = 'qr_' . $card_number . '.png';
        $qr_path = $qr_dir . $qr_filename;
        $result->saveToFile($qr_path);
        $qr_url = '/uploads/qrcodes/' . $qr_filename;
        
        // إنشاء Barcode (رقم بسيط)
        $barcode = strtoupper(substr(md5($card_number), 0, 12));
        
        // تواريخ الصلاحية
        $issue_date = date('Y-m-d');
        $expiry_date = date('Y-m-d', strtotime('+1 year'));
        
        // إدراج البطاقة في قاعدة البيانات
        $insert_stmt = $conn->prepare("
            INSERT INTO id_cards 
            (user_id, card_number, course_id, issue_date, expiry_date, card_type, qr_code, barcode, photo_url, status) 
            VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?, 'active')
        ");
        $photo_url = $student['profile_picture'];
        $insert_stmt->bind_param('isisssss', $student_id, $card_number, $course_id, $issue_date, $expiry_date, $qr_data, $barcode, $photo_url);
        
        if (!$insert_stmt->execute()) {
            $insert_stmt->close();
            respond(['success' => false, 'message' => 'فشل إصدار البطاقة'], 500);
        }
        
        $card_id = $insert_stmt->insert_id;
        $insert_stmt->close();
        
        // تحديث حالة المستخدم
        $update_user = $conn->prepare("
            UPDATE users 
            SET card_issued = 1, card_number = ?, account_status = ?
            WHERE id = ?
        ");
        $account_status = $payment_complete ? 'active' : 'pending';
        $update_user->bind_param('ssi', $card_number, $account_status, $student_id);
        $update_user->execute();
        $update_user->close();
        
        // إرسال إشعار للطالب
        $notif_stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, title, message, type, link) 
            VALUES (?, 'تم إصدار بطاقتك', 'تم إصدار بطاقتك الشخصية بنجاح! يمكنك تنزيلها من لوحة التحكم.', 'card', '/my-card')
        ");
        $notif_stmt->bind_param('i', $student_id);
        $notif_stmt->execute();
        $notif_stmt->close();
        
        respond([
            'success' => true,
            'message' => 'تم إصدار البطاقة بنجاح',
            'card_id' => $card_id,
            'card_number' => $card_number,
            'qr_url' => $qr_url,
            'barcode' => $barcode,
            'payment_complete' => $payment_complete
        ], 201);
    }
    
    // ==============================================
    // GET: جلب البطاقات
    // ==============================================
    if ($method === 'GET') {
        
        // جلب بطاقة معينة
        if ($action === 'get_card') {
            $card_id = (int)($_GET['card_id'] ?? 0);
            $student_id = (int)($_GET['user_id'] ?? 0);
            
            $sql = "
                SELECT 
                    ic.*,
                    u.full_name,
                    u.full_name_en,
                    u.email,
                    u.phone,
                    u.dob,
                    u.gender,
                    u.profile_picture,
                    u.account_status,
                    u.payment_complete,
                    c.title AS course_title,
                    c.region AS course_region,
                    c.city AS course_city
                FROM id_cards ic
                INNER JOIN users u ON ic.user_id = u.id
                LEFT JOIN courses c ON ic.course_id = c.course_id
                WHERE 1=1
            ";
            
            if ($card_id > 0) {
                $sql .= " AND ic.card_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $card_id);
            } elseif ($student_id > 0) {
                $sql .= " AND ic.user_id = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param('i', $student_id);
            } else {
                respond(['success' => false, 'message' => 'معرّف البطاقة أو الطالب مطلوب'], 400);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                respond(['success' => false, 'message' => 'البطاقة غير موجودة'], 404);
            }
            
            $card = $result->fetch_assoc();
            $stmt->close();
            
            // فك تشفير QR data
            $card['qr_code_data'] = json_decode($card['qr_code'], true);
            
            respond(['success' => true, 'card' => $card]);
        }
        
        // جلب جميع البطاقات
        if ($action === 'list' || $action === '') {
            
            if (!in_array($user_role, ['manager', 'technical'], true)) {
                respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
            }
            
            $status_filter = $_GET['status'] ?? 'all';
            $limit = (int)($_GET['limit'] ?? 50);
            
            $sql = "
                SELECT 
                    ic.card_id,
                    ic.card_number,
                    ic.issue_date,
                    ic.expiry_date,
                    ic.status,
                    u.id AS user_id,
                    u.full_name,
                    u.email,
                    u.phone,
                    u.payment_complete,
                    c.title AS course_title
                FROM id_cards ic
                INNER JOIN users u ON ic.user_id = u.id
                LEFT JOIN courses c ON ic.course_id = c.course_id
            ";
            
            if ($status_filter !== 'all') {
                $sql .= " WHERE ic.status = ?";
                $stmt = $conn->prepare($sql . " ORDER BY ic.created_at DESC LIMIT ?");
                $stmt->bind_param('si', $status_filter, $limit);
            } else {
                $stmt = $conn->prepare($sql . " ORDER BY ic.created_at DESC LIMIT ?");
                $stmt->bind_param('i', $limit);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $cards = [];
            while ($row = $result->fetch_assoc()) {
                $cards[] = [
                    'card_id' => (int)$row['card_id'],
                    'card_number' => $row['card_number'],
                    'user_id' => (int)$row['user_id'],
                    'full_name' => $row['full_name'],
                    'email' => $row['email'],
                    'phone' => $row['phone'],
                    'course_title' => $row['course_title'],
                    'issue_date' => $row['issue_date'],
                    'expiry_date' => $row['expiry_date'],
                    'status' => $row['status'],
                    'payment_complete' => (bool)$row['payment_complete']
                ];
            }
            
            $stmt->close();
            respond(['success' => true, 'cards' => $cards, 'total' => count($cards)]);
        }
        
        // مسح QR Code للتحقق
        if ($action === 'scan_verify') {
            $card_number = $_GET['card_number'] ?? '';
            
            if ($card_number === '') {
                respond(['success' => false, 'message' => 'رقم البطاقة مطلوب'], 400);
            }
            
            $stmt = $conn->prepare("
                SELECT 
                    ic.*,
                    u.full_name,
                    u.email,
                    u.account_status,
                    c.title AS course_title
                FROM id_cards ic
                INNER JOIN users u ON ic.user_id = u.id
                LEFT JOIN courses c ON ic.course_id = c.course_id
                WHERE ic.card_number = ? AND ic.status = 'active'
            ");
            $stmt->bind_param('s', $card_number);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                $stmt->close();
                respond(['success' => false, 'message' => 'البطاقة غير صالحة أو منتهية'], 404);
            }
            
            $card = $result->fetch_assoc();
            $stmt->close();
            
            // التحقق من تاريخ الانتهاء
            $expired = (strtotime($card['expiry_date']) < time());
            
            respond([
                'success' => true,
                'valid' => !$expired,
                'card_number' => $card['card_number'],
                'student_name' => $card['full_name'],
                'course' => $card['course_title'],
                'issue_date' => $card['issue_date'],
                'expiry_date' => $card['expiry_date'],
                'status' => $expired ? 'منتهية' : 'صالحة'
            ]);
        }
    }
    
    // ==============================================
    // PUT/PATCH: تحديث حالة البطاقة
    // ==============================================
    if (in_array($method, ['PUT', 'PATCH'], true)) {
        
        if (!in_array($user_role, ['manager', 'technical'], true)) {
            respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
        }
        
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        $card_id = (int)($data['card_id'] ?? 0);
        $new_status = $data['status'] ?? '';
        
        if ($card_id <= 0 || !in_array($new_status, ['active', 'expired', 'cancelled'], true)) {
            respond(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
        }
        
        $stmt = $conn->prepare("UPDATE id_cards SET status = ?, updated_at = NOW() WHERE card_id = ?");
        $stmt->bind_param('si', $new_status, $card_id);
        $stmt->execute();
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected === 0) {
            respond(['success' => false, 'message' => 'البطاقة غير موجودة'], 404);
        }
        
        respond(['success' => true, 'message' => 'تم تحديث حالة البطاقة']);
    }
    
    respond(['success' => false, 'message' => 'طريقة أو إجراء غير مدعوم'], 400);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
