<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار النظام - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .status-ok { color: #10b981; }
        .status-error { color: #ef4444; }
        .status-warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-8 max-w-4xl">
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h1 class="text-3xl font-bold text-center mb-8 text-sky-600">🔍 اختبار حالة النظام</h1>
            
            <div class="space-y-4">
                <!-- PHP Version -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold">إصدار PHP</span>
                    <span class="status-ok font-bold">
                        <?php echo phpversion(); ?>
                    </span>
                </div>

                <!-- Database Connection -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold">الاتصال بقاعدة البيانات</span>
                    <?php
                    $dbFile = __DIR__ . '/platform/db.php';
                    if (file_exists($dbFile)) {
                        require_once $dbFile;
                        if ($conn && $conn->connect_error === null) {
                            echo '<span class="status-ok font-bold">✅ متصل</span>';
                        } else {
                            echo '<span class="status-error font-bold">❌ فشل الاتصال</span>';
                        }
                    } else {
                        echo '<span class="status-warning font-bold">⚠️ الملف غير موجود</span>';
                    }
                    ?>
                </div>

                <!-- Requests JSON File -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold">ملف الطلبات (requests.json)</span>
                    <?php
                    $jsonFile = __DIR__ . '/database/requests.json';
                    if (file_exists($jsonFile)) {
                        $content = file_get_contents($jsonFile);
                        $requests = json_decode($content, true);
                        $count = is_array($requests) ? count($requests) : 0;
                        echo '<span class="status-ok font-bold">✅ موجود (' . $count . ' طلبات)</span>';
                    } else {
                        echo '<span class="status-warning font-bold">⚠️ غير موجود (سيتم إنشاؤه تلقائياً)</span>';
                    }
                    ?>
                </div>

                <!-- Uploads Directory -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold">مجلد الرفعات (uploads/ids)</span>
                    <?php
                    $uploadsDir = __DIR__ . '/platform/uploads/ids';
                    if (is_dir($uploadsDir)) {
                        if (is_writable($uploadsDir)) {
                            echo '<span class="status-ok font-bold">✅ موجود وقابل للكتابة</span>';
                        } else {
                            echo '<span class="status-error font-bold">❌ غير قابل للكتابة</span>';
                        }
                    } else {
                        echo '<span class="status-warning font-bold">⚠️ غير موجود</span>';
                        @mkdir($uploadsDir, 0755, true);
                        echo ' <small class="text-gray-500">(تم إنشاؤه الآن)</small>';
                    }
                    ?>
                </div>

                <!-- PHPMailer -->
                <div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">
                    <span class="font-semibold">PHPMailer</span>
                    <?php
                    $vendorPath = __DIR__ . '/platform/vendor/autoload.php';
                    $altVendorPath = __DIR__ . '/vendor/autoload.php';
                    
                    if (file_exists($vendorPath) || file_exists($altVendorPath)) {
                        echo '<span class="status-ok font-bold">✅ مثبت</span>';
                    } else {
                        echo '<span class="status-warning font-bold">⚠️ غير مثبت</span>';
                        echo '<small class="text-xs text-gray-500 mr-2">(استخدم: composer require phpmailer/phpmailer)</small>';
                    }
                    ?>
                </div>

                <!-- Core Files -->
                <?php
                $coreFiles = [
                    'platform/index.html' => 'الصفحة الرئيسية',
                    'platform/signup.php' => 'صفحة التسجيل',
                    'platform/application.php' => 'استمارة التقديم',
                    'platform/apply.php' => 'معالج الطلبات',
                    'Manager/requests.php' => 'بوابة المدير',
                    'Manager/updateRequest.php' => 'تحديث الطلبات',
                    'Mailer/sendMail.php' => 'نظام البريد'
                ];

                $missingFiles = [];
                foreach ($coreFiles as $file => $name) {
                    if (!file_exists(__DIR__ . '/' . $file)) {
                        $missingFiles[] = $name . " ($file)";
                    }
                }

                if (empty($missingFiles)) {
                    echo '<div class="flex items-center justify-between p-4 bg-gray-50 rounded-lg">';
                    echo '<span class="font-semibold">الملفات الأساسية</span>';
                    echo '<span class="status-ok font-bold">✅ جميع الملفات موجودة</span>';
                    echo '</div>';
                } else {
                    echo '<div class="p-4 bg-red-50 rounded-lg border border-red-200">';
                    echo '<span class="font-semibold text-red-700">❌ ملفات مفقودة:</span>';
                    echo '<ul class="mt-2 mr-4 text-sm text-red-600">';
                    foreach ($missingFiles as $missing) {
                        echo "<li>• $missing</li>";
                    }
                    echo '</ul></div>';
                }
                ?>
            </div>

            <!-- Quick Links -->
            <div class="mt-8 grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="platform/index.html" class="block bg-gradient-to-r from-blue-500 to-blue-600 text-white p-4 rounded-lg text-center hover:shadow-lg transition">
                    🏠 الصفحة الرئيسية
                </a>
                <a href="platform/courses.html" class="block bg-gradient-to-r from-cyan-500 to-cyan-600 text-white p-4 rounded-lg text-center hover:shadow-lg transition">
                    📚 الدورات التدريبية
                </a>
                <a href="Manager/" class="block bg-gradient-to-r from-sky-500 to-sky-600 text-white p-4 rounded-lg text-center hover:shadow-lg transition">
                    👔 بوابة المدير
                </a>
                <a href="Technical/Portal.html" class="block bg-gradient-to-r from-teal-500 to-teal-600 text-white p-4 rounded-lg text-center hover:shadow-lg transition">
                    🔧 بوابة الفني
                </a>
            </div>

            <!-- Documentation Links -->
            <div class="mt-8 text-center space-y-2">
                <p class="text-gray-600">📖 للمزيد من المعلومات:</p>
                <div class="flex justify-center gap-4 text-sm">
                    <a href="README.md" class="text-blue-600 hover:underline">📄 README</a>
                    <a href="SETUP.md" class="text-blue-600 hover:underline">⚙️ دليل الإعداد</a>
                </div>
            </div>

            <div class="mt-8 text-center text-gray-500 text-sm">
                <p>© 2025 منصة إبداع للتدريب والتأهيل</p>
                <p class="mt-1">آخر تحديث: <?php echo date('Y-m-d H:i:s'); ?></p>
            </div>
        </div>
    </div>
</body>
</html>
