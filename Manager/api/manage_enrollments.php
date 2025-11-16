<?php
/**
 * ================================================
 * API: إدارة طلبات التسجيل (إعادة بناء كاملة)
 * ================================================
 * الهدف: ربط الموافقة بإرسال إيميل + إنشاء سجل enrollments بحالة pending
 * الصلاحية: technical فقط
 * ================================================
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// التحقق من الصلاحية (المشرف الفني فقط)
$user_role = $_SESSION['user_role'] ?? $_SESSION['role'] ?? null;
if (!isset($_SESSION['user_id']) || $user_role !== 'technical') {
    echo json_encode(['success' => false, 'error' => 'غير مصرح. هذا القسم للمشرف الفني فقط.'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'POST مطلوب']);
    exit;
}

$request_id = intval($_POST['request_id'] ?? 0);
$action = $_POST['action'] ?? 'approve';
$rejection_reason = $_POST['rejection_reason'] ?? '';

if (!$request_id) {
    echo json_encode(['success' => false, 'error' => 'request_id مطلوب']);
    exit;
}

try {
    $conn->begin_transaction();
    
    // جلب بيانات الطلب
    $stmt = $conn->prepare("SELECT * FROM requests WHERE request_id = ? FOR UPDATE");
    $stmt->bind_param('i', $request_id);
    $stmt->execute();
    $res = $stmt->get_result();
    
    if (!$res || $res->num_rows === 0) {
        throw new Exception('الطلب غير موجود');
    }
    
    $req = $res->fetch_assoc();
    
    if ($req['status'] !== 'pending') {
        throw new Exception('هذا الطلب ليس في حالة انتظار');
    }
    
    // ================================================
    // حالة الرفض
    // ================================================
    if ($action === 'reject') {
        if (empty($rejection_reason)) {
            throw new Exception('يجب إدخال سبب الرفض');
        }
        
        // تحديث حالة الطلب
        $stmt = $conn->prepare("UPDATE requests SET status = 'rejected' WHERE request_id = ?");
        $stmt->bind_param('i', $request_id);
        $stmt->execute();
        
        // إرسال إيميل الرفض
        $commData = [
            'email' => $req['email'],
            'message_type' => 'rejection',
            'student_name' => $req['full_name'],
            'course_name' => $req['course_name'],
            'rejection_reason' => $rejection_reason
        ];
        
        $commResponse = @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/Manager/api/send_communication.php', false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: application/json',
                'content' => json_encode($commData)
            ]
        ]));
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => '✅ تم رفض الطلب وإرسال إيميل للطالب.'
        ]);
        exit;
    }
    
    // ================================================
    // حالة الموافقة (لا ننشئ حساباً بعد - فقط نرسل إيميل!)
    // ================================================
    
    // البحث عن الدورة
    $course_id = null;
    
    // محاولة 1: بحث رقمي
    if (is_numeric($req['course_name'])) {
        $cid = intval($req['course_name']);
        $cstmt = $conn->prepare("SELECT id, title, price FROM courses WHERE id = ? LIMIT 1");
        $cstmt->bind_param('i', $cid);
        $cstmt->execute();
        $cres = $cstmt->get_result();
        if ($cres && $cres->num_rows) {
            $course = $cres->fetch_assoc();
            $course_id = $course['id'];
            $course_title = $course['title'];
            $course_price = $course['price'] ?? 0;
        }
    }
    
    // محاولة 2: بحث نصي
    if (!$course_id) {
        $cstmt2 = $conn->prepare("SELECT id, title, price FROM courses WHERE title = ? LIMIT 1");
        $cstmt2->bind_param('s', $req['course_name']);
        $cstmt2->execute();
        $cres2 = $cstmt2->get_result();
        if ($cres2 && $cres2->num_rows) {
            $course = $cres2->fetch_assoc();
            $course_id = $course['id'];
            $course_title = $course['title'];
            $course_price = $course['price'] ?? 0;
        }
    }
    
    if (!$course_id) {
        throw new Exception('لم يتم العثور على الدورة: ' . $req['course_name']);
    }
    
    // التحقق من وجود المستخدم
    $uStmt = $conn->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
    $uStmt->bind_param('s', $req['email']);
    $uStmt->execute();
    $uRes = $uStmt->get_result();
    
    $user_id_for_enrollment = null;
    
    if ($uRes && $uRes->num_rows) {
        // المستخدم موجود بالفعل
        $user = $uRes->fetch_assoc();
        $user_id_for_enrollment = $user['id'];
    } else {
        // إنشاء مستخدم مؤقت (بدون كلمة مرور - سنضيفها عند التفعيل)
        // نستخدم NULL أو قيمة عشوائية طويلة غير قابلة للتخمين
        $temp_password_hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
        
        $hasPasswordHash = ($conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'") && $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->num_rows > 0);
        
        if ($hasPasswordHash) {
            $stmtIns = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, role, verified, created_at) VALUES (?, ?, ?, ?, 'student', 0, NOW())");
            $stmtIns->bind_param('ssss', $req['full_name'], $req['email'], $req['phone'], $temp_password_hash);
        } else {
            $stmtIns = $conn->prepare("INSERT INTO users (full_name, email, phone, password, role, verified, created_at) VALUES (?, ?, ?, ?, 'student', 0, NOW())");
            $stmtIns->bind_param('ssss', $req['full_name'], $req['email'], $req['phone'], $temp_password_hash);
        }
        
        if (!$stmtIns->execute()) {
            throw new Exception('فشل إنشاء المستخدم المؤقت: ' . $stmtIns->error);
        }
        
        $user_id_for_enrollment = $conn->insert_id;
    }
    
    // إنشاء سجل في enrollments بحالة payment_status = 'pending'
    $checkEnroll = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1");
    $checkEnroll->bind_param('ii', $user_id_for_enrollment, $course_id);
    $checkEnroll->execute();
    $enrollRes = $checkEnroll->get_result();
    
    if ($enrollRes && $enrollRes->num_rows) {
        // السجل موجود - تحديثه
        $estmt = $conn->prepare("UPDATE enrollments SET payment_status = 'pending', status = 'pending' WHERE user_id = ? AND course_id = ?");
        $estmt->bind_param('ii', $user_id_for_enrollment, $course_id);
        $estmt->execute();
    } else {
        // إنشاء سجل جديد
        $estmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status, payment_status, enrollment_date) VALUES (?, ?, 'pending', 'pending', CURDATE())");
        $estmt->bind_param('ii', $user_id_for_enrollment, $course_id);
        if (!$estmt->execute()) {
            throw new Exception('فشل إنشاء سجل التسجيل: ' . $estmt->error);
        }
    }
    
    // تحديث حالة الطلب
    $u2 = $conn->prepare("UPDATE requests SET status = 'approved' WHERE request_id = ?");
    $u2->bind_param('i', $request_id);
    if (!$u2->execute()) {
        throw new Exception('فشل تحديث حالة الطلب: ' . $u2->error);
    }
    
    // إرسال إيميل الموافقة المبدئية
    $commData = [
        'email' => $req['email'],
        'message_type' => 'approval',
        'student_name' => $req['full_name'],
        'course_name' => $course_title ?? $req['course_name']
    ];
    
    $commResponse = @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/Manager/api/send_communication.php', false, stream_context_create([
        'http' => [
            'method' => 'POST',
            'header' => 'Content-Type: application/json',
            'content' => json_encode($commData)
        ]
    ]));
    
    // إنشاء إشعار للمدير العام
    $pdo_stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'manager' LIMIT 1");
    $pdo_stmt->execute();
    $manager = $pdo_stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($manager) {
        $notification_message = "تمت الموافقة مبدئياً على الطالب {$req['full_name']} للدورة {$course_title}. بانتظار تأكيد الدفع.";
        $pdo_notif = $pdo->prepare("INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)");
        $pdo_notif->execute([$manager['id'], $notification_message, '#finance']);
    }
    
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ تمت الموافقة وإرسال إيميل للطالب. الحساب سيُفعّل بعد تأكيد الدفع.',
        'user_id' => $user_id_for_enrollment
    ]);
    
} catch (Exception $e) {
    try { $conn->rollback(); } catch (Throwable $t) {}
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
