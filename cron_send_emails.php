<?php
/**
 * Cron Job - معالج إرسال البريد الإلكتروني
 * يعمل كل 5 دقائق لإرسال الإشعارات المعلقة
 */

require_once __DIR__ . '/includes/email_sender.php';
require_once __DIR__ . '/platform/db.php';

// تسجيل وقت التنفيذ
$log_file = __DIR__ . '/logs/email_cron.log';
$log_dir = dirname($log_file);

if (!is_dir($log_dir)) {
    mkdir($log_dir, 0755, true);
}

$start_time = microtime(true);
$timestamp = date('Y-m-d H:i:s');

file_put_contents($log_file, "\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n", FILE_APPEND);
file_put_contents($log_file, "[$timestamp] Cron Job Started\n", FILE_APPEND);

try {
    // إنشاء كائن EmailSender
    $emailSender = new EmailSender($conn);
    
    // معالجة 20 بريد في كل تشغيل
    $result = $emailSender->processPendingEmails(20);
    
    // تسجيل النتائج
    $duration = round(microtime(true) - $start_time, 2);
    $log_message = "[$timestamp] Results: ";
    $log_message .= "Sent: {$result['sent']}, ";
    $log_message .= "Failed: {$result['failed']}, ";
    $log_message .= "Total: {$result['total']}, ";
    $log_message .= "Duration: {$duration}s\n";
    
    file_put_contents($log_file, $log_message, FILE_APPEND);
    
    // عرض النتائج (للـ CLI)
    echo $log_message;
    
} catch (Exception $e) {
    $error_message = "[$timestamp] ERROR: " . $e->getMessage() . "\n";
    file_put_contents($log_file, $error_message, FILE_APPEND);
    echo $error_message;
}

file_put_contents($log_file, "[$timestamp] Cron Job Completed\n", FILE_APPEND);

/**
 * لتفعيل هذا Cron Job:
 * 
 * Windows (Task Scheduler):
 * 1. افتح Task Scheduler
 * 2. Create Basic Task
 * 3. Trigger: Daily, Repeat every 5 minutes
 * 4. Action: Start a program
 *    Program: C:\xampp\php\php.exe
 *    Arguments: C:\xampp\htdocs\Ibdaa-Taiz\cron_send_emails.php
 * 
 * Linux (Crontab):
 * 1. افتح crontab: crontab -e
 * 2. أضف السطر التالي:
 *    (star-slash-5) (star) (star) (star) (star) php /path/to/cron_send_emails.php
 *    (استبدل النجوم بالرموز الفعلية)
 * 
 * للاختبار اليدوي:
 * php cron_send_emails.php
 */
