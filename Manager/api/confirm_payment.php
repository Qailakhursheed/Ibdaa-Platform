<?php
/**
 * API - تأكيد الدفع وتفعيل الحساب
 * هذا API يقوم بـ:
 * 1. تحديث حالة الدفع
 * 2. إنشاء حساب جديد للطالب (إن لم يكن موجوداً)
 * 3. تفعيل الحساب
 * 4. إنشاء سجل انضمام
 * 5. إرسال بيانات الدخول بالبريد
 */

require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../platform/db.php';

SessionSecurity::requireLogin();
SessionSecurity::requireRole(['manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة إرسال غير صحيحة']);
    exit;
}

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
    exit;
}

$application_id = (int)($_POST['application_id'] ?? 0);
$receipt_number = trim($_POST['receipt_number'] ?? '');
$payment_method = trim($_POST['payment_method'] ?? 'cash');
$payment_date = trim($_POST['payment_date'] ?? date('Y-m-d'));
$notes = trim($_POST['notes'] ?? '');
$verified_by = $_SESSION['user_id'];

if ($application_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'رقم الطلب غير صحيح']);
    exit;
}

if (empty($receipt_number)) {
    echo json_encode(['success' => false, 'message' => 'رقم الإيصال مطلوب']);
    exit;
}

// جلب بيانات الطلب
$stmt = $conn->prepare("
    SELECT 
        a.*,
        c.name as course_name,
        c.price as course_price,
        c.id as course_id
    FROM applications a
    JOIN courses c ON c.id = a.course_id
    WHERE a.id = ?
");
$stmt->bind_param('i', $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
    exit;
}

$application = $result->fetch_assoc();
$stmt->close();

// التحقق من أن الطلب مقبول
if ($application['status'] !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'الطلب غير مقبول بعد']);
    exit;
}

// التحقق من عدم تأكيد الدفع مسبقاً
if ($application['payment_status'] === 'completed') {
    echo json_encode(['success' => false, 'message' => 'الدفع تم تأكيده مسبقاً']);
    exit;
}

// بدء Transaction
$conn->begin_transaction();

try {
    // 1. إنشاء حساب الطالب (إن لم يكن موجوداً)
    $user_id = null;
    
    // التحقق من وجود حساب بنفس البريد
    $check_user = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $check_user->bind_param('s', $application['email']);
    $check_user->execute();
    $user_result = $check_user->get_result();
    
    if ($user_result->num_rows > 0) {
        // المستخدم موجود
        $user_id = $user_result->fetch_assoc()['id'];
    } else {
        // إنشاء حساب جديد
        $username = strtolower(str_replace(' ', '_', $application['full_name'])) . '_' . rand(1000, 9999);
        $temporary_password = 'Ibdaa' . rand(100000, 999999) . '@';
        $password_hash = password_hash($temporary_password, PASSWORD_DEFAULT);
        
        $create_user = $conn->prepare("
            INSERT INTO users (
                username, full_name, email, password, role, phone,
                governorate, district, birth_date, application_id,
                verified, status, created_at
            ) VALUES (?, ?, ?, ?, 'student', ?, ?, ?, ?, ?, 1, 'active', NOW())
        ");
        
        $create_user->bind_param(
            'ssssssssi',
            $username,
            $application['full_name'],
            $application['email'],
            $password_hash,
            $application['phone'],
            $application['governorate'],
            $application['district'],
            $application['birth_date'],
            $application_id
        );
        
        if (!$create_user->execute()) {
            throw new Exception('فشل إنشاء حساب الطالب');
        }
        
        $user_id = $conn->insert_id;
        $create_user->close();
        
        // إضافة إشعار بريدي ببيانات الدخول
        $email_subject = '🎉 تم تفعيل حسابك - منصة إبداع';
        $email_message = "عزيزي/عزيزتي {$application['full_name']},\n\n";
        $email_message .= "مبروك! تم تفعيل حسابك في منصة إبداع.\n\n";
        $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $email_message .= "🔐 معلومات الدخول:\n";
        $email_message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $email_message .= "🌐 رابط تسجيل الدخول:\n";
        $email_message .= "http://localhost/Ibdaa-Taiz/platform/login.php\n\n";
        $email_message .= "👤 اسم المستخدم: {$username}\n";
        $email_message .= "🔑 كلمة المرور المؤقتة: {$temporary_password}\n\n";
        $email_message .= "⚠️ مهم: يرجى تغيير كلمة المرور بعد أول تسجيل دخول\n\n";
        $email_message .= "📚 دورتك المسجلة: {$application['course_name']}\n\n";
        $email_message .= "نتمنى لك تجربة تعليمية ممتعة! 🎓\n\n";
        $email_message .= "منصة إبداع - تعز";
        
        $add_email_notif = $conn->prepare("
            INSERT INTO notification_log (recipient_email, subject, message, status)
            VALUES (?, ?, ?, 'pending')
        ");
        $add_email_notif->bind_param('sss', $application['email'], $email_subject, $email_message);
        $add_email_notif->execute();
        $add_email_notif->close();
    }
    
    $check_user->close();
    
    // 2. تحديث طلب التسجيل
    $update_app = $conn->prepare("
        UPDATE applications 
        SET payment_status = 'completed'
        WHERE id = ?
    ");
    $update_app->bind_param('i', $application_id);
    if (!$update_app->execute()) {
        throw new Exception('فشل تحديث حالة الطلب');
    }
    $update_app->close();
    
    // 3. إنشاء سجل الدفع
    $insert_payment = $conn->prepare("
        INSERT INTO payments (
            application_id, student_id, course_id, amount,
            payment_method, payment_date, receipt_number,
            status, verified_by, verified_at, notes, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'verified', ?, NOW(), ?, NOW())
    ");
    
    $insert_payment->bind_param(
        'iiiisssiss',
        $application_id,
        $user_id,
        $application['course_id'],
        $application['course_price'],
        $payment_method,
        $payment_date,
        $receipt_number,
        $verified_by,
        $notes
    );
    
    if (!$insert_payment->execute()) {
        throw new Exception('فشل تسجيل الدفع');
    }
    $insert_payment->close();
    
    // 4. إنشاء سجل انضمام (Enrollment)
    $check_enrollment = $conn->prepare("
        SELECT id FROM enrollments WHERE student_id = ? AND course_id = ?
    ");
    $check_enrollment->bind_param('ii', $user_id, $application['course_id']);
    $check_enrollment->execute();
    $enrollment_result = $check_enrollment->get_result();
    
    if ($enrollment_result->num_rows === 0) {
        // إنشاء سجل انضمام جديد
        $create_enrollment = $conn->prepare("
            INSERT INTO enrollments (
                student_id, course_id, application_id, enrollment_date,
                payment_status, payment_amount, total_amount,
                approved_by, approved_at, status
            ) VALUES (?, ?, ?, NOW(), 'completed', ?, ?, ?, NOW(), 'active')
        ");
        
        $create_enrollment->bind_param(
            'iiiddi',
            $user_id,
            $application['course_id'],
            $application_id,
            $application['course_price'],
            $application['course_price'],
            $verified_by
        );
        
        if (!$create_enrollment->execute()) {
            throw new Exception('فشل إنشاء سجل الانضمام');
        }
        $create_enrollment->close();
    }
    
    $check_enrollment->close();
    
    // 5. إشعار داخلي للمدرب
    $add_trainer_notif = $conn->prepare("
        INSERT INTO notifications (user_id, message, type, created_at)
        SELECT 
            trainer_id,
            CONCAT('طالب جديد انضم إلى دورتك: ', ?, ' - الدورة: ', ?),
            'success',
            NOW()
        FROM courses
        WHERE id = ? AND trainer_id IS NOT NULL
    ");
    $add_trainer_notif->bind_param('ssi', $application['full_name'], $application['course_name'], $application['course_id']);
    $add_trainer_notif->execute();
    $add_trainer_notif->close();
    
    // Commit Transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تأكيد الدفع وتفعيل الحساب بنجاح',
        'user_id' => $user_id,
        'username' => $username ?? 'موجود مسبقاً'
    ]);
    
} catch (Exception $e) {
    // Rollback في حالة الخطأ
    $conn->rollback();
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
