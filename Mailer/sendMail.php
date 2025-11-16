<?php
// sendMail.php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// تحميل PHPMailer يدوياً
require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';

function sendStatusMail($to, $name, $course, $status) {
    try {
        $mail = new PHPMailer(true);
        
        // إعداد SMTP
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'ha717781053@gmail.com';  // غيّر هذا
        $mail->Password = 'YOUR_APP_PASSWORD';      // ضع كلمة مرور التطبيق من Google
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->CharSet = 'UTF-8';

        $mail->setFrom('ha717781053@gmail.com', 'منصة إبداع');
        $mail->addAddress($to, $name);

        $mail->isHTML(true);
        $mail->Subject = "تحديث حالة طلبك في منصة إبداع";
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; direction: rtl; text-align: right;'>
                <h2>مرحباً {$name}،</h2>
                <p>تم تحديث حالة طلبك في الدورة <strong>$course</strong> إلى: <strong style='color: #2563eb;'>$status</strong>.</p>
                <p>نشكرك على ثقتك في منصة إبداع ونتطلع لرؤيتك قريبًا.</p>
                <hr style='margin: 20px 0;'>
                <p style='font-size: 12px; color: #666;'>منصة إبداع للتدريب والتأهيل - تعز، اليمن</p>
            </div>
        ";
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log('Mail Error: ' . $mail->ErrorInfo);
        return false;
    }
}
?>
