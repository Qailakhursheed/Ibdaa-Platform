<?php
/**
 * send_communication - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


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
        ]);
    }
});

header('Content-Type: application/json; charset=utf-8');

// التحقق من الصلاحية (technical أو manager)
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'error' => 'غير مصرح.']);
    exit;
}

require_once __DIR__ . '/../../platform/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$data = json_decode(file_get_contents('php://input'), true);

$email = $data['email'] ?? null;
$message_type = $data['message_type'] ?? null; // 'approval', 'rejection', 'activation'
$student_name = $data['student_name'] ?? 'الطالب';
$course_name = $data['course_name'] ?? '';
$rejection_reason = $data['rejection_reason'] ?? '';
$username = $data['username'] ?? '';
$password = $data['password'] ?? '';

if (!$email || !$message_type) {
    echo json_encode(['success' => false, 'error' => 'بيانات ناقصة.']);
    exit;
}

try {
    // جلب إعدادات SMTP من قاعدة البيانات
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'smtp_%' OR setting_key LIKE 'whatsapp_%'");
    $settings = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
    
    $smtp_host = $settings['smtp_host'] ?? 'smtp.gmail.com';
    $smtp_port = $settings['smtp_port'] ?? '587';
    $smtp_user = $settings['smtp_user'] ?? '';
    $smtp_pass = $settings['smtp_pass'] ?? '';
    $smtp_from_name = $settings['smtp_from_name'] ?? 'منصة إبداع';
    $whatsapp_number = $settings['whatsapp_number'] ?? '967700000000';
    
    // التحقق من إعدادات SMTP
    if (empty($smtp_user) || empty($smtp_pass)) {
        echo json_encode([
            'success' => false,
            'error' => '⚠️ إعدادات SMTP غير مكتملة. الرجاء إدخالها من الإعدادات.'
        ]);
        exit;
    }
    
    // إنشاء رسالة HTML حسب النوع
    $subject = '';
    $body = '';
    $whatsapp_link = '';
    
    if ($message_type === 'approval') {
        $subject = '✅ تمت الموافقة على طلب التسجيل - منصة إبداع';
        $body = "
        <div dir='rtl' style='font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px;'>
                <h2 style='color: #10b981; text-align: center;'>✅ تمت الموافقة على طلبك!</h2>
                <p style='font-size: 16px;'>عزيزنا <strong>$student_name</strong>،</p>
                <p>يسرنا إخبارك بأنه تمت الموافقة على طلب تسجيلك في دورة:</p>
                <div style='background: #ecfdf5; padding: 15px; border-right: 4px solid #10b981; margin: 20px 0;'>
                    <h3 style='color: #059669; margin: 0;'>$course_name</h3>
                </div>
                <h3 style='color: #1f2937;'>📋 الخطوات التالية:</h3>
                <ol style='font-size: 15px; line-height: 1.8;'>
                    <li>التوجه إلى مقر المعهد لتسديد الرسوم</li>
                    <li>إحضار المستندات المطلوبة (إن وجدت)</li>
                    <li>بعد التسديد، سيتم تفعيل حسابك وإرسال بيانات الدخول</li>
                </ol>
                <p style='margin-top: 20px;'>للتواصل معنا عبر واتساب:</p>
                <a href='https://wa.me/$whatsapp_number?text=مرحبا، أريد الاستفسار عن دورة $course_name' 
                   style='display: inline-block; background: #10b981; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    💬 تواصل عبر واتساب
                </a>
                <p style='margin-top: 30px; color: #6b7280; font-size: 14px; text-align: center;'>
                    منصة إبداع للتدريب والتطوير<br>
                    نسعد بخدمتكم
                </p>
            </div>
        </div>
        ";
        $whatsapp_link = "https://wa.me/$whatsapp_number?text=" . urlencode("مرحبا، أريد الاستفسار عن دورة $course_name");
    }
    
    elseif ($message_type === 'rejection') {
        $subject = '❌ إشعار بشأن طلب التسجيل - منصة إبداع';
        $body = "
        <div dir='rtl' style='font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px;'>
                <h2 style='color: #ef4444; text-align: center;'>إشعار بشأن طلب التسجيل</h2>
                <p style='font-size: 16px;'>عزيزنا <strong>$student_name</strong>،</p>
                <p>نأسف لإبلاغك بأنه لم تتم الموافقة على طلب التسجيل في دورة:</p>
                <div style='background: #fef2f2; padding: 15px; border-right: 4px solid #ef4444; margin: 20px 0;'>
                    <h3 style='color: #dc2626; margin: 0;'>$course_name</h3>
                </div>
                <p><strong>السبب:</strong></p>
                <p style='background: #f9fafb; padding: 15px; border-radius: 5px;'>$rejection_reason</p>
                <p style='margin-top: 20px;'>للاستفسار أو التواصل معنا:</p>
                <a href='https://wa.me/$whatsapp_number?text=مرحبا، أريد الاستفسار عن سبب رفض طلبي' 
                   style='display: inline-block; background: #ef4444; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold;'>
                    💬 تواصل معنا
                </a>
                <p style='margin-top: 30px; color: #6b7280; font-size: 14px; text-align: center;'>
                    منصة إبداع للتدريب والتطوير
                </p>
            </div>
        </div>
        ";
        $whatsapp_link = "https://wa.me/$whatsapp_number?text=" . urlencode("مرحبا، أريد الاستفسار عن سبب رفض طلبي");
    }
    
    elseif ($message_type === 'activation') {
        $subject = '🎉 تم تفعيل حسابك - منصة إبداع';
        $body = "
        <div dir='rtl' style='font-family: Arial, sans-serif; background: #f3f4f6; padding: 20px;'>
            <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px;'>
                <h2 style='color: #3b82f6; text-align: center;'>🎉 مبروك! تم تفعيل حسابك</h2>
                <p style='font-size: 16px;'>عزيزنا <strong>$student_name</strong>،</p>
                <p>تم تأكيد دفعتك بنجاح وتفعيل حسابك في دورة:</p>
                <div style='background: #eff6ff; padding: 15px; border-right: 4px solid #3b82f6; margin: 20px 0;'>
                    <h3 style='color: #2563eb; margin: 0;'>$course_name</h3>
                </div>
                <h3 style='color: #1f2937;'>🔐 بيانات الدخول:</h3>
                <div style='background: #f9fafb; padding: 20px; border-radius: 5px; margin: 20px 0;'>
                    <p style='margin: 5px 0;'><strong>اسم المستخدم:</strong> $username</p>
                    <p style='margin: 5px 0;'><strong>كلمة المرور:</strong> $password</p>
                </div>
                <p style='color: #dc2626; font-weight: bold;'>⚠️ يُرجى تغيير كلمة المرور بعد أول تسجيل دخول للحفاظ على أمان حسابك.</p>
                <a href='" . $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'] . "/Manager/login.php' 
                   style='display: inline-block; background: #3b82f6; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; font-weight: bold; margin-top: 20px;'>
                    🔓 تسجيل الدخول الآن
                </a>
                <p style='margin-top: 30px; color: #6b7280; font-size: 14px; text-align: center;'>
                    منصة إبداع للتدريب والتطوير<br>
                    نتمنى لك تجربة تعليمية ممتعة
                </p>
            </div>
        </div>
        ";
    }
    
    // إعداد PHPMailer
    $mail = new PHPMailer(true);
    
    // إعدادات SMTP
    $mail->isSMTP();
    $mail->Host = $smtp_host;
    $mail->SMTPAuth = true;
    $mail->Username = $smtp_user;
    $mail->Password = $smtp_pass;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = $smtp_port;
    $mail->CharSet = 'UTF-8';
    
    // المرسل والمستلم
    $mail->setFrom($smtp_user, $smtp_from_name);
    $mail->addAddress($email, $student_name);
    
    // المحتوى
    $mail->isHTML(true);
    $mail->Subject = $subject;
    $mail->Body = $body;
    $mail->AltBody = strip_tags($body);
    
    // إرسال الإيميل
    $mail->send();
    
    echo json_encode([
        'success' => true,
        'message' => '✅ تم إرسال الإيميل بنجاح.',
        'whatsapp_link' => $whatsapp_link
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => '❌ فشل إرسال الإيميل: ' . $mail->ErrorInfo ?? $e->getMessage()
    ]);
}
