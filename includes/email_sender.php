<?php
/**
 * نظام إرسال البريد الإلكتروني
 * Email Sending System
 * 
 * يعالج:
 * 1. إرسال الإشعارات البريدية من notification_log
 * 2. دعم SMTP (Gmail, SendGrid, AWS SES)
 * 3. Queue System للإرسال الجماعي
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/../vendor/autoload.php';

class EmailSender {
    private $mailer;
    private $conn;
    
    // إعدادات SMTP (يمكن نقلها إلى config.php)
    private $smtp_host = 'smtp.gmail.com';
    private $smtp_port = 587;
    private $smtp_username = 'your-email@gmail.com'; // غيّر هذا
    private $smtp_password = 'your-app-password'; // غيّر هذا
    private $smtp_from_email = 'noreply@ibdaa-taiz.com';
    private $smtp_from_name = 'منصة إبداع - تعز';
    
    public function __construct($db_connection) {
        $this->conn = $db_connection;
        $this->mailer = new PHPMailer(true);
        $this->configureSMTP();
    }
    
    /**
     * إعداد SMTP
     */
    private function configureSMTP() {
        try {
            // إعدادات السيرفر
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->smtp_host;
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->smtp_username;
            $this->mailer->Password = $this->smtp_password;
            $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mailer->Port = $this->smtp_port;
            
            // إعدادات عامة
            $this->mailer->CharSet = 'UTF-8';
            $this->mailer->setFrom($this->smtp_from_email, $this->smtp_from_name);
            
            // تعطيل التحقق من SSL في البيئة المحلية
            $this->mailer->SMTPOptions = array(
                'ssl' => array(
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                )
            );
            
        } catch (Exception $e) {
            error_log("SMTP Configuration Error: " . $e->getMessage());
        }
    }
    
    /**
     * إرسال بريد واحد
     */
    public function sendEmail($to, $subject, $body, $is_html = true) {
        try {
            // إعادة تعيين المستلمين
            $this->mailer->clearAddresses();
            $this->mailer->clearAttachments();
            
            // إضافة المستلم
            $this->mailer->addAddress($to);
            
            // المحتوى
            $this->mailer->isHTML($is_html);
            $this->mailer->Subject = $subject;
            
            if ($is_html) {
                $this->mailer->Body = $this->getEmailTemplate($subject, $body);
                $this->mailer->AltBody = strip_tags($body);
            } else {
                $this->mailer->Body = $body;
            }
            
            // إرسال
            $this->mailer->send();
            return ['success' => true, 'message' => 'Email sent successfully'];
            
        } catch (Exception $e) {
            error_log("Email Send Error: " . $this->mailer->ErrorInfo);
            return [
                'success' => false, 
                'message' => $this->mailer->ErrorInfo
            ];
        }
    }
    
    /**
     * معالجة الإشعارات المعلقة
     * يتم استدعاؤها عبر Cron Job كل 5 دقائق
     */
    public function processPendingEmails($limit = 10) {
        $stmt = $this->conn->prepare("
            SELECT id, recipient_email, subject, message 
            FROM notification_log 
            WHERE status = 'pending' 
            ORDER BY created_at ASC 
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $sent_count = 0;
        $failed_count = 0;
        
        while ($row = $result->fetch_assoc()) {
            $email_result = $this->sendEmail(
                $row['recipient_email'],
                $row['subject'],
                $row['message']
            );
            
            if ($email_result['success']) {
                $this->updateEmailStatus($row['id'], 'sent', null);
                $sent_count++;
            } else {
                $this->updateEmailStatus($row['id'], 'failed', $email_result['message']);
                $failed_count++;
            }
            
            // تأخير قصير لتجنب تجاوز حدود SMTP
            usleep(500000); // 0.5 ثانية
        }
        
        $stmt->close();
        
        return [
            'sent' => $sent_count,
            'failed' => $failed_count,
            'total' => $sent_count + $failed_count
        ];
    }
    
    /**
     * تحديث حالة الإشعار
     */
    private function updateEmailStatus($notification_id, $status, $error_message = null) {
        $stmt = $this->conn->prepare("
            UPDATE notification_log 
            SET 
                status = ?,
                sent_at = NOW(),
                error_message = ?
            WHERE id = ?
        ");
        $stmt->bind_param('ssi', $status, $error_message, $notification_id);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * قالب HTML للبريد
     */
    private function getEmailTemplate($subject, $body) {
        return '
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            background-color: #f4f4f4;
            margin: 0;
            padding: 0;
        }
        .container {
            max-width: 600px;
            margin: 20px auto;
            background: #ffffff;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        .content {
            padding: 30px;
        }
        .content p {
            margin: 15px 0;
            white-space: pre-line;
        }
        .footer {
            background: #f8f9fa;
            padding: 20px;
            text-align: center;
            border-top: 1px solid #e0e0e0;
            font-size: 12px;
            color: #666;
        }
        .button {
            display: inline-block;
            padding: 12px 30px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 20px 0;
        }
        .logo {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: white;
            display: inline-block;
            margin-bottom: 10px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="logo">
                <img src="https://your-domain.com/photos/Sh.jpg" alt="منصة إبداع" style="width: 60px; height: 60px; border-radius: 50%;">
            </div>
            <h1>' . htmlspecialchars($subject) . '</h1>
        </div>
        <div class="content">
            ' . nl2br(htmlspecialchars($body)) . '
        </div>
        <div class="footer">
            <p><strong>منصة إبداع للتدريب والتطوير - تعز</strong></p>
            <p>📧 info@ibdaa-taiz.com | 📱 00967-XXX-XXX-XXX</p>
            <p>© 2025 جميع الحقوق محفوظة</p>
        </div>
    </div>
</body>
</html>
        ';
    }
    
    /**
     * إرسال بريد تأكيد استلام الطلب
     */
    public function sendApplicationReceivedEmail($application_id, $full_name, $email, $course_name) {
        $subject = 'استلام طلب الانضمام - منصة إبداع';
        $message = "عزيزي/عزيزتي {$full_name},\n\n";
        $message .= "تم استلام طلب انضمامك إلى دورة: {$course_name}\n";
        $message .= "رقم الطلب: #{$application_id}\n\n";
        $message .= "سيتم مراجعة طلبك خلال 24-48 ساعة وإرسال إشعار بالقرار على بريدك الإلكتروني.\n\n";
        $message .= "في حالة القبول، سيتم إرسال تفاصيل الدفع ومعلومات الدخول.\n\n";
        $message .= "شكراً لاختياركم منصة إبداع";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * إرسال بريد قبول الطلب
     */
    public function sendApplicationApprovedEmail($application_id, $full_name, $email, $course_name, $course_price) {
        $subject = 'مبروك! تم قبول طلبك - منصة إبداع';
        $message = "عزيزي/عزيزتي {$full_name},\n\n";
        $message .= "مبروك! تم قبول طلب انضمامك إلى دورة: {$course_name}\n";
        $message .= "رقم الطلب: #{$application_id}\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📌 الخطوة التالية: الدفع\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "💰 الرسوم المطلوبة: " . number_format($course_price, 0) . " ريال\n\n";
        $message .= "📍 طرق الدفع:\n";
        $message .= "1. نقداً: في مقر المنصة\n";
        $message .= "2. تحويل بنكي: [تفاصيل الحساب]\n";
        $message .= "3. محفظة إلكترونية: [رقم المحفظة]\n\n";
        $message .= "بعد الدفع، سيتم تفعيل حسابك وإرسال بيانات الدخول.\n\n";
        $message .= "للاستفسار: اتصل بنا على 00967-XXX-XXX-XXX\n\n";
        $message .= "منصة إبداع - تعز";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * إرسال بريد رفض الطلب
     */
    public function sendApplicationRejectedEmail($application_id, $full_name, $email, $course_name, $rejection_reason) {
        $subject = 'تحديث بخصوص طلبك - منصة إبداع';
        $message = "عزيزي/عزيزتي {$full_name},\n\n";
        $message .= "نأسف لإبلاغك أنه لم يتم قبول طلب انضمامك إلى دورة: {$course_name}\n";
        $message .= "رقم الطلب: #{$application_id}\n\n";
        
        if (!empty($rejection_reason)) {
            $message .= "السبب: {$rejection_reason}\n\n";
        }
        
        $message .= "يمكنك:\n";
        $message .= "• التقديم مرة أخرى بعد تحسين المستندات\n";
        $message .= "• اختيار دورة أخرى تناسبك\n";
        $message .= "• التواصل معنا للاستفسار: 00967-XXX-XXX-XXX\n\n";
        $message .= "نتمنى لك التوفيق\n";
        $message .= "منصة إبداع - تعز";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * إرسال بريد تفعيل الحساب
     */
    public function sendAccountActivatedEmail($username, $full_name, $email, $temporary_password, $course_name) {
        $subject = '🎉 مبروك! تم تفعيل حسابك - منصة إبداع';
        $message = "عزيزي/عزيزتي {$full_name},\n\n";
        $message .= "مبروك! تم تفعيل حسابك في منصة إبداع.\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "🔐 معلومات الدخول:\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "🌐 رابط تسجيل الدخول:\n";
        $message .= "https://ibdaa-taiz.com/platform/login.php\n\n";
        $message .= "👤 اسم المستخدم: {$username}\n";
        $message .= "🔑 كلمة المرور المؤقتة: {$temporary_password}\n\n";
        $message .= "⚠️ مهم: يرجى تغيير كلمة المرور بعد أول تسجيل دخول\n\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
        $message .= "📚 دورتك المسجلة:\n";
        $message .= "━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
        $message .= "{$course_name}\n\n";
        $message .= "يمكنك الآن:\n";
        $message .= "✅ الوصول إلى لوحة التحكم الخاصة بك\n";
        $message .= "✅ مشاهدة محتوى الدورة\n";
        $message .= "✅ حضور المحاضرات المباشرة\n";
        $message .= "✅ تقديم الامتحانات\n";
        $message .= "✅ الحصول على الشهادة بعد الانتهاء\n\n";
        $message .= "نتمنى لك تجربة تعليمية ممتعة ومفيدة! 🎓\n\n";
        $message .= "منصة إبداع - تعز";
        
        return $this->sendEmail($email, $subject, $message);
    }
    
    /**
     * اختبار الاتصال بـ SMTP
     */
    public function testConnection() {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

/**
 * استخدام Cron Job:
 * 
 * قم بإنشاء ملف: cron_send_emails.php
 * 
 * <?php
 * require_once 'includes/email_sender.php';
 * require_once 'platform/db.php';
 * 
 * $emailSender = new EmailSender($conn);
 * $result = $emailSender->processPendingEmails(20);
 * 
 * echo "Sent: {$result['sent']}, Failed: {$result['failed']}\n";
 * ?>
 * 
 * ثم أضف إلى Crontab:
 * */5 * * * * php /path/to/cron_send_emails.php
 * 
 * (كل 5 دقائق)
 */
