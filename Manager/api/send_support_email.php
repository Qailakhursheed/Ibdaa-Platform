<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/../../platform/db.php';
header('Content-Type: application/json; charset=utf-8');

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$user_email = $_SESSION['email'] ?? $_SESSION['user_email'] ?? 'unknown';
$user_name = $_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'مستخدم';

if (!$user_id) {
    echo json_encode(['success'=>false,'message'=>'يجب تسجيل الدخول أولاً'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $data = json_decode(file_get_contents('php://input'), true);
    $subject = $data['subject'] ?? '';
    $message = $data['message'] ?? '';
    
    if (empty($subject) || empty($message)) {
        echo json_encode(['success'=>false,'message'=>'الرجاء ملء جميع الحقول'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    // إعداد البريد الإلكتروني
    $to = 'almktoop77370@gmail.com';
    $email_subject = "دعم فني من منصة إبداع: " . $subject;
    $email_body = "
    ===================================
    رسالة دعم فني جديدة من منصة إبداع
    ===================================
    
    المرسل: $user_name
    البريد الإلكتروني: $user_email
    معرف المستخدم: $user_id
    
    الموضوع: $subject
    
    الرسالة:
    $message
    
    ===================================
    تاريخ الإرسال: " . date('Y-m-d H:i:s') . "
    ===================================
    ";
    
    $headers = "From: no-reply@ibdaa-taiz.com\r\n";
    $headers .= "Reply-To: $user_email\r\n";
    $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
    
    // محاولة إرسال البريد
    // ملاحظة: mail() قد لا تعمل على localhost بدون إعداد SMTP
    $mail_sent = @mail($to, $email_subject, $email_body, $headers);
    
    if ($mail_sent) {
        // حفظ الرسالة في قاعدة البيانات كنسخة احتياطية
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, user_email, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'open', NOW())");
        if ($stmt) {
            $stmt->bind_param('isss', $user_id, $user_email, $subject, $message);
            $stmt->execute();
        }
        
        echo json_encode([
            'success'=>true,
            'message'=>'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        // حفظ الرسالة في قاعدة البيانات حتى لو فشل الإرسال
        $stmt = $conn->prepare("INSERT INTO support_tickets (user_id, user_email, subject, message, status, created_at) VALUES (?, ?, ?, ?, 'pending', NOW())");
        if ($stmt) {
            $stmt->bind_param('isss', $user_id, $user_email, $subject, $message);
            $stmt->execute();
            
            echo json_encode([
                'success'=>true,
                'message'=>'تم حفظ رسالتك بنجاح. سنتواصل معك قريباً.',
                'note'=>'ملاحظة: خدمة البريد الإلكتروني غير مفعلة على الخادم المحلي'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode([
                'success'=>false,
                'message'=>'فشل إرسال الرسالة. الرجاء المحاولة لاحقاً أو التواصل مباشرة عبر: almktoop77370@gmail.com'
            ], JSON_UNESCAPED_UNICODE);
        }
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success'=>false,
        'message'=>'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
