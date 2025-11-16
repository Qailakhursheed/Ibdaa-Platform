<?php
/**
 * ================================================
 * API: إرسال بريد تجريبي (Test Email)
 * ================================================
 * الهدف: اختبار إعدادات SMTP عن طريق إرسال بريد تجريبي
 * الصلاحية: manager فقط
 * ================================================
 */

session_start();

// إظهار جميع الأخطاء لتسهيل التشخيص
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
        ]);
    }
});

header('Content-Type: application/json; charset=utf-8');

// التحقق من الصلاحيات - للمدير فقط
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
$user_email = $_SESSION['user_email'] ?? ($_SESSION['email'] ?? null);

if (!$user_id || $user_role !== 'manager') {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك. هذه الصفحة للمدير فقط.'], JSON_UNESCAPED_UNICODE);
    exit;
}

// لف كل الكود داخل try-catch شامل لمنع الانهيار
try {
    // تحميل PHPMailer و db
    require_once __DIR__ . '/../../vendor/autoload.php';
    require_once __DIR__ . '/../../platform/db.php';

    // استيراد PHPMailer classes
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    
    // 1. جلب إعدادات SMTP من قاعدة البيانات
    $settings = [];
    
    // التحقق من وجود الاتصال بقاعدة البيانات
    if (!isset($conn) || $conn === null) {
        throw new \Exception('فشل الاتصال بقاعدة البيانات. تأكد من تشغيل MySQL.');
    }
    
    $res = $conn->query("SELECT setting_key, setting_value FROM settings");
    
    // التحقق من نجاح الاستعلام
    if ($res === false) {
        throw new \Exception('خطأ في جلب الإعدادات من قاعدة البيانات: ' . $conn->error);
    }
    
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }
    }

    // التحقق من وجود الإعدادات الأساسية
    if (empty($settings['smtp_host']) || empty($settings['smtp_user']) || empty($settings['smtp_pass'])) {
        echo json_encode(['success'=>false,'message'=>'إعدادات SMTP غير مكتملة. الرجاء حفظ الإعدادات أولاً.'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // 2. إعداد PHPMailer (الكائن تم إنشاؤه أعلاه)
    
    // إعداد SMTP
    $mail->isSMTP();
    $mail->Host = $settings['smtp_host'];
    $mail->SMTPAuth = true;
    $mail->Username = $settings['smtp_user'];
    $mail->Password = $settings['smtp_pass'];
    $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = intval($settings['smtp_port'] ?? 587);
    $mail->CharSet = 'UTF-8';

    // 3. إعداد الرسالة
    $mail->setFrom(
        $settings['smtp_user'], 
        $settings['smtp_from_name'] ?? 'منصة إبداع'
    );
    
    // إرسال إلى المدير نفسه
    if (empty($user_email)) {
        echo json_encode(['success'=>false,'message'=>'لا يوجد بريد إلكتروني مسجل لحسابك'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $mail->addAddress($user_email);
    
    // محتوى الرسالة
    $mail->isHTML(true);
    $mail->Subject = 'Test Email from Ibdaa Platform - اختبار البريد من منصة إبداع';
    $mail->Body = '
        <div style="font-family: Arial, sans-serif; direction: rtl; text-align: right; padding: 20px;">
            <h2 style="color: #3b82f6;">مرحباً من منصة إبداع للتدريب!</h2>
            <p style="font-size: 16px; color: #333;">
                إذا وصلتك هذه الرسالة، فهذا يعني أن <strong>إعدادات SMTP</strong> في منصة إبداع تعمل بنجاح ✅
            </p>
            <hr style="border: 1px solid #eee; margin: 20px 0;">
            <p style="font-size: 14px; color: #666;">
                <strong>معلومات الإرسال:</strong><br>
                - SMTP Host: ' . htmlspecialchars($settings['smtp_host']) . '<br>
                - SMTP Port: ' . htmlspecialchars($settings['smtp_port'] ?? '587') . '<br>
                - From: ' . htmlspecialchars($settings['smtp_from_name'] ?? 'منصة إبداع') . '<br>
                - To: ' . htmlspecialchars($user_email) . '
            </p>
            <p style="font-size: 12px; color: #999; margin-top: 20px;">
                هذا بريد تجريبي تلقائي من نظام منصة إبداع للتدريب والتأهيل.
            </p>
        </div>
    ';
    
    // 4. محاولة الإرسال
    if ($mail->send()) {
        echo json_encode([
            'success'=>true,
            'message'=>"✅ تم إرسال بريد تجريبي بنجاح إلى: {$user_email}\n\nالرجاء التحقق من صندوق الوارد (أو البريد المزعج/Spam)"
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'success'=>false,
            'message'=>'❌ فشل الإرسال: ' . $mail->ErrorInfo
        ], JSON_UNESCAPED_UNICODE);
    }

} catch (\Exception $e) {
    // التقاط أي خطأ أو انهيار وإرسال رسالة JSON واضحة
    echo json_encode([
        'success'=>false,
        'message'=>'❌ انهيار في الخادم: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
} catch (\Error $e) {
    // التقاط أخطاء PHP الحرجة (Fatal Errors)
    echo json_encode([
        'success'=>false,
        'message'=>'❌ خطأ حرج (Fatal Error): ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
