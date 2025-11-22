<?php
/**
 * سكريبت التحقق من الأمان
 * استخدمه قبل النشر ثم احذفه!
 */

$checks = [];

// التحقق من .env
$checks['env_file'] = file_exists(__DIR__ . '/.env') ? '✅' : '❌';

// التحقق من config.php
$config = include __DIR__ . '/includes/config.php';
$checks['smtp_password'] = 
    ($config['smtp']['password'] !== 'YOUR_APP_PASSWORD') ? '✅' : '❌';

// التحقق من Error Reporting
// Note: This depends on APP_ENV. If local, it might be E_ALL (warning).
$checks['error_reporting'] = 
    (error_reporting() === 0) ? '✅' : '⚠️ (Check APP_ENV)';

// التحقق من Session Security
// Note: cookie_secure depends on HTTPS.
$checks['session_secure'] = 
    (ini_get('session.cookie_secure') == 1) ? '✅' : '⚠️ (Requires HTTPS)';

// التحقق من .htaccess
$checks['htaccess_uploads'] = 
    file_exists(__DIR__ . '/uploads/.htaccess') ? '✅' : '❌';

// عرض النتائج
echo "<!DOCTYPE html><html><head><meta charset='utf-8'><title>Security Check</title></head><body>";
echo "<h1>🔒 تقرير الأمان</h1>";
echo "<ul>";
foreach ($checks as $check => $status) {
    echo "<li><strong>$check:</strong> $status</li>";
}
echo "</ul>";

$passed = count(array_filter($checks, fn($s) => $s === '✅'));
$total = count($checks);
$percentage = round(($passed / $total) * 100);

echo "<h2>النتيجة: $passed/$total ($percentage%)</h2>";

if ($percentage < 100) {
    echo "<p style='color:red'>⚠️ يجب حل المشاكل قبل النشر!</p>";
} else {
    echo "<p style='color:green'>✅ جاهز للنشر!</p>";
}

echo "</body></html>";
?>
