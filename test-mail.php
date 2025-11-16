<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار PHPMailer - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="bg-gray-50 p-10">
    <div class="max-w-2xl mx-auto bg-white rounded-2xl shadow-xl p-8">
        <h1 class="text-3xl font-bold text-sky-700 mb-6">🧪 اختبار نظام البريد الإلكتروني</h1>
        
        <?php
        require_once __DIR__ . '/Mailer/sendMail.php';
        
        echo "<div class='space-y-4'>";
        
        // 1. التحقق من PHPMailer
        echo "<div class='bg-blue-50 border border-blue-200 p-4 rounded-lg'>";
        echo "<h3 class='font-bold text-blue-800 mb-2'>1️⃣ التحقق من PHPMailer</h3>";
        
        $phpmailerPath = __DIR__ . '/Mailer/PHPMailer/src/PHPMailer.php';
        if (file_exists($phpmailerPath)) {
            echo "<p class='text-green-700'>✅ PHPMailer موجود في المسار الصحيح</p>";
            echo "<p class='text-xs text-gray-600 mt-1'>المسار: " . $phpmailerPath . "</p>";
        } else {
            echo "<p class='text-red-700'>❌ PHPMailer غير موجود!</p>";
        }
        echo "</div>";
        
        // 2. التحقق من الملفات المطلوبة
        echo "<div class='bg-blue-50 border border-blue-200 p-4 rounded-lg'>";
        echo "<h3 class='font-bold text-blue-800 mb-2'>2️⃣ التحقق من الملفات</h3>";
        
        $requiredFiles = [
            'PHPMailer.php' => __DIR__ . '/Mailer/PHPMailer/src/PHPMailer.php',
            'SMTP.php' => __DIR__ . '/Mailer/PHPMailer/src/SMTP.php',
            'Exception.php' => __DIR__ . '/Mailer/PHPMailer/src/Exception.php'
        ];
        
        foreach ($requiredFiles as $name => $path) {
            if (file_exists($path)) {
                echo "<p class='text-green-700'>✅ $name</p>";
            } else {
                echo "<p class='text-red-700'>❌ $name غير موجود</p>";
            }
        }
        echo "</div>";
        
        // 3. اختبار تحميل الكلاسات
        echo "<div class='bg-blue-50 border border-blue-200 p-4 rounded-lg'>";
        echo "<h3 class='font-bold text-blue-800 mb-2'>3️⃣ اختبار تحميل الكلاسات</h3>";
        
        try {
            $testMail = new PHPMailer\PHPMailer\PHPMailer();
            echo "<p class='text-green-700'>✅ تم إنشاء كائن PHPMailer بنجاح</p>";
            echo "<p class='text-xs text-gray-600 mt-1'>الإصدار: " . $testMail::VERSION . "</p>";
        } catch (Exception $e) {
            echo "<p class='text-red-700'>❌ فشل إنشاء كائن PHPMailer: " . $e->getMessage() . "</p>";
        }
        echo "</div>";
        
        // 4. اختبار دالة sendStatusMail
        echo "<div class='bg-blue-50 border border-blue-200 p-4 rounded-lg'>";
        echo "<h3 class='font-bold text-blue-800 mb-2'>4️⃣ اختبار دالة الإرسال</h3>";
        
        if (function_exists('sendStatusMail')) {
            echo "<p class='text-green-700'>✅ دالة sendStatusMail موجودة</p>";
            echo "<p class='text-yellow-700 mt-2'>⚠️ لم يتم إرسال بريد تجريبي (يتطلب App Password من Gmail)</p>";
        } else {
            echo "<p class='text-red-700'>❌ دالة sendStatusMail غير موجودة</p>";
        }
        echo "</div>";
        
        // 5. التعليمات
        echo "<div class='bg-green-50 border border-green-200 p-4 rounded-lg'>";
        echo "<h3 class='font-bold text-green-800 mb-2'>📝 الخطوات التالية</h3>";
        echo "<ol class='list-decimal mr-6 space-y-2 text-sm'>";
        echo "<li>افتح <code class='bg-gray-100 px-2 py-1 rounded'>Mailer/sendMail.php</code></li>";
        echo "<li>عدّل السطر 20: <code class='bg-gray-100 px-2 py-1 rounded'>\$mail->Password = 'YOUR_APP_PASSWORD';</code></li>";
        echo "<li>ضع App Password من Gmail (16 حرف)</li>";
        echo "<li>جرّب إرسال بريد من لوحة المتابع الفني</li>";
        echo "</ol>";
        echo "<div class='mt-3 p-3 bg-white rounded border'>";
        echo "<p class='text-xs font-bold mb-1'>🔗 للحصول على App Password:</p>";
        echo "<p class='text-xs'>1. <a href='https://myaccount.google.com/security' class='text-blue-600 underline' target='_blank'>فعّل التحقق بخطوتين</a></p>";
        echo "<p class='text-xs'>2. <a href='https://myaccount.google.com/apppasswords' class='text-blue-600 underline' target='_blank'>أنشئ App Password</a></p>";
        echo "</div>";
        echo "</div>";
        
        echo "</div>";
        ?>
        
        <div class="mt-8 flex gap-4 justify-center">
            <a href="Technical/Portal.php" class="bg-teal-600 hover:bg-teal-700 text-white px-6 py-3 rounded-lg font-bold transition">
                🔧 لوحة الفني
            </a>
            <a href="Manager/requests_new.php" class="bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg font-bold transition">
                👔 لوحة المدير
            </a>
        </div>
    </div>
</body>
</html>
