<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * نظام إدارة طلبات التسجيل المتقدم
 * Advanced Registration Requests Management System
 * 
 * الميزات:
 * - استقبال طلبات التسجيل
 * - قبول/رفض الطلبات
 * - تحويل تلقائي للطلاب المقبولين
 * - تفعيل الحساب بعد اكتمال الدفع
 * - إرسال إشعارات تلقائية
 */

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // ==============================================
    // GET: جلب الطلبات
    // ==============================================
    if ($method === 'GET') {
        // التحقق من الصلاحيات للمديرين
        $user_id = $_SESSION['user_id'] ?? null;
        $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
        
        if (!in_array($user_role, ['manager', 'technical'], true)) {
            respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
        }
        
        $status_filter = $_GET['status'] ?? 'pending';
        $limit = (int)($_GET['limit'] ?? 50);
        $offset = (int)($_GET['offset'] ?? 0);
        
        $sql = "
            SELECT 
                rr.request_id,
                rr.full_name,
                rr.email,
                rr.phone,
                rr.dob,
                rr.gender,
                rr.governorate,
                rr.district,
                rr.course_id,
                rr.status,
                rr.reviewed_by,
                rr.reviewed_at,
                rr.rejection_reason,
                rr.created_at,
                c.title AS course_title,
                c.fees AS course_fees,
                c.region AS course_region,
                reviewer.full_name AS reviewer_name
            FROM registration_requests rr
            LEFT JOIN courses c ON rr.course_id = c.course_id
            LEFT JOIN users reviewer ON rr.reviewed_by = reviewer.id
        ";
        
        $params = [];
        $types = '';
        
        if ($status_filter !== 'all') {
            $sql .= " WHERE rr.status = ?";
            $params[] = $status_filter;
            $types .= 's';
        }
        
        $sql .= " ORDER BY rr.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        $types .= 'ii';
        
        $stmt = $conn->prepare($sql);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $requests = [];
        while ($row = $result->fetch_assoc()) {
            $requests[] = [
                'request_id' => (int)$row['request_id'],
                'full_name' => $row['full_name'],
                'email' => $row['email'],
                'phone' => $row['phone'],
                'dob' => $row['dob'],
                'gender' => $row['gender'],
                'governorate' => $row['governorate'],
                'district' => $row['district'],
                'course_id' => $row['course_id'] ? (int)$row['course_id'] : null,
                'course_title' => $row['course_title'],
                'course_fees' => $row['course_fees'] ? (float)$row['course_fees'] : null,
                'course_region' => $row['course_region'],
                'status' => $row['status'],
                'reviewed_by' => $row['reviewed_by'] ? (int)$row['reviewed_by'] : null,
                'reviewer_name' => $row['reviewer_name'],
                'reviewed_at' => $row['reviewed_at'],
                'rejection_reason' => $row['rejection_reason'],
                'created_at' => $row['created_at']
            ];
        }
        
        $stmt->close();
        
        // إحصائيات الطلبات
        $stats_stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM registration_requests
        ");
        $stats_stmt->execute();
        $stats_result = $stats_stmt->get_result();
        $stats = $stats_result->fetch_assoc();
        $stats_stmt->close();
        
        respond([
            'success' => true,
            'requests' => $requests,
            'total' => count($requests),
            'statistics' => [
                'total' => (int)$stats['total'],
                'pending' => (int)$stats['pending'],
                'approved' => (int)$stats['approved'],
                'rejected' => (int)$stats['rejected']
            ]
        ]);
    }
    
    // ==============================================
    // POST: إنشاء طلب جديد أو معالجة الطلبات
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        // إنشاء طلب تسجيل جديد (من صفحة التقديم العامة)
        if ($action === 'submit') {
            $full_name = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $phone = trim($data['phone'] ?? '');
            $dob = $data['dob'] ?? null;
            $gender = $data['gender'] ?? 'male';
            $governorate = trim($data['governorate'] ?? '');
            $district = trim($data['district'] ?? '');
            $course_id = (int)($data['course_id'] ?? 0);
            
            if ($full_name === '' || $email === '' || $phone === '') {
                respond(['success' => false, 'message' => 'الاسم والبريد والهاتف مطلوبة'], 400);
            }
            
            // التحقق من وجود البريد مسبقاً
            $check = $conn->prepare("SELECT request_id FROM registration_requests WHERE email = ?");
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $check->close();
                respond(['success' => false, 'message' => 'هذا البريد مسجل مسبقاً في قائمة الانتظار'], 409);
            }
            $check->close();
            
            // التحقق من وجود البريد في جدول المستخدمين
            $user_check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $user_check->bind_param('s', $email);
            $user_check->execute();
            $user_check->store_result();
            
            if ($user_check->num_rows > 0) {
                $user_check->close();
                respond(['success' => false, 'message' => 'هذا البريد مسجل مسبقاً في النظام'], 409);
            }
            $user_check->close();
            
            // إدراج الطلب
            $stmt = $conn->prepare("
                INSERT INTO registration_requests 
                (full_name, email, phone, dob, gender, governorate, district, course_id, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
            ");
            $course_id_val = $course_id > 0 ? $course_id : null;
            $stmt->bind_param('sssssssi', $full_name, $email, $phone, $dob, $gender, $governorate, $district, $course_id_val);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إرسال الطلب'], 500);
            }
            
            $request_id = $stmt->insert_id;
            $stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم إرسال طلبك بنجاح! سيتم مراجعته قريباً.',
                'request_id' => $request_id
            ], 201);
        }
        
        // قبول الطلب وتحويله إلى طالب
        if ($action === 'approve') {
            $user_id = $_SESSION['user_id'] ?? null;
            $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
            
            if (!in_array($user_role, ['manager', 'technical'], true)) {
                respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
            }
            
            $request_id = (int)($data['request_id'] ?? 0);
            
            if ($request_id <= 0) {
                respond(['success' => false, 'message' => 'معرّف الطلب مطلوب'], 400);
            }
            
            // جلب بيانات الطلب
            $stmt = $conn->prepare("
                SELECT * FROM registration_requests 
                WHERE request_id = ? AND status = 'pending'
            ");
            $stmt->bind_param('i', $request_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $request = $result->fetch_assoc();
            $stmt->close();
            
            if (!$request) {
                respond(['success' => false, 'message' => 'الطلب غير موجود أو تمت معالجته'], 404);
            }
            
            // بدء المعاملة (Transaction)
            $conn->begin_transaction();
            
            try {
                // إنشاء حساب طالب جديد
                $default_password = 'Ibdaa@' . substr($request['phone'], -4); // كلمة مرور مؤقتة
                $password_hash = password_hash($default_password, PASSWORD_DEFAULT);
                
                $user_stmt = $conn->prepare("
                    INSERT INTO users 
                    (full_name, email, phone, password_hash, role, dob, gender, 
                     governorate, district, account_status, payment_complete, verified) 
                    VALUES (?, ?, ?, ?, 'student', ?, ?, ?, ?, 'pending', 0, 1)
                ");
                $user_stmt->bind_param(
                    'ssssssss',
                    $request['full_name'],
                    $request['email'],
                    $request['phone'],
                    $password_hash,
                    $request['dob'],
                    $request['gender'],
                    $request['governorate'],
                    $request['district']
                );
                $user_stmt->execute();
                $new_user_id = $user_stmt->insert_id;
                $user_stmt->close();
                
                // تسجيل الطالب في الدورة (إذا كانت محددة)
                if ($request['course_id']) {
                    $enroll_stmt = $conn->prepare("
                        INSERT INTO enrollments 
                        (user_id, course_id, status, payment_status) 
                        VALUES (?, ?, 'pending', 'pending')
                    ");
                    $enroll_stmt->bind_param('ii', $new_user_id, $request['course_id']);
                    $enroll_stmt->execute();
                    $enroll_stmt->close();
                }
                
                // تحديث حالة الطلب
                $update_stmt = $conn->prepare("
                    UPDATE registration_requests 
                    SET status = 'approved', reviewed_by = ?, reviewed_at = NOW() 
                    WHERE request_id = ?
                ");
                $update_stmt->bind_param('ii', $user_id, $request_id);
                $update_stmt->execute();
                $update_stmt->close();
                
                // إنشاء إشعار للطالب الجديد
                $notif_stmt = $conn->prepare("
                    INSERT INTO notifications 
                    (user_id, title, message, type, link) 
                    VALUES (?, 'تم قبول طلبك', 'مرحباً بك في منصة إبداع! تم قبول طلب التسجيل الخاص بك.', 'success', '/dashboard')
                ");
                $notif_stmt->bind_param('i', $new_user_id);
                $notif_stmt->execute();
                $notif_stmt->close();
                
                $conn->commit();
                
                respond([
                    'success' => true,
                    'message' => 'تم قبول الطلب وإنشاء حساب الطالب بنجاح',
                    'user_id' => $new_user_id,
                    'default_password' => $default_password
                ]);
                
            } catch (Exception $e) {
                $conn->rollback();
                respond(['success' => false, 'message' => 'فشل قبول الطلب: ' . $e->getMessage()], 500);
            }
        }
        
        // رفض الطلب
        if ($action === 'reject') {
            $user_id = $_SESSION['user_id'] ?? null;
            $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
            
            if (!in_array($user_role, ['manager', 'technical'], true)) {
                respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
            }
            
            $request_id = (int)($data['request_id'] ?? 0);
            $rejection_reason = trim($data['rejection_reason'] ?? 'لم يتم تحديد السبب');
            
            if ($request_id <= 0) {
                respond(['success' => false, 'message' => 'معرّف الطلب مطلوب'], 400);
            }
            
            $stmt = $conn->prepare("
                UPDATE registration_requests 
                SET status = 'rejected', reviewed_by = ?, reviewed_at = NOW(), rejection_reason = ? 
                WHERE request_id = ? AND status = 'pending'
            ");
            $stmt->bind_param('isi', $user_id, $rejection_reason, $request_id);
            $stmt->execute();
            
            $affected = $stmt->affected_rows;
            $stmt->close();
            
            if ($affected === 0) {
                respond(['success' => false, 'message' => 'الطلب غير موجود أو تمت معالجته'], 404);
            }
            
            respond(['success' => true, 'message' => 'تم رفض الطلب']);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // DELETE: حذف طلب
    // ==============================================
    if ($method === 'DELETE') {
        $user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
        
        if ($user_role !== 'manager') {
            respond(['success' => false, 'message' => 'غير مصرح لك - المدير فقط'], 403);
        }
        
        $request_id = (int)($_GET['request_id'] ?? 0);
        
        if ($request_id <= 0) {
            respond(['success' => false, 'message' => 'معرّف الطلب مطلوب'], 400);
        }
        
        $stmt = $conn->prepare("DELETE FROM registration_requests WHERE request_id = ?");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        
        $affected = $stmt->affected_rows;
        $stmt->close();
        
        if ($affected === 0) {
            respond(['success' => false, 'message' => 'الطلب غير موجود'], 404);
        }
        
        respond(['success' => true, 'message' => 'تم حذف الطلب']);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
