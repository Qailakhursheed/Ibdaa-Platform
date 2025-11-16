<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>النسخ الاحتياطي - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-50 p-8">
    <div class="max-w-2xl mx-auto bg-white rounded-lg shadow-lg p-8">
        <h1 class="text-3xl font-bold text-center mb-8 text-sky-600">💾 النسخ الاحتياطي للبيانات</h1>

        <?php
        if (isset($_GET['action']) && $_GET['action'] === 'backup') {
            $jsonFile = __DIR__ . '/database/requests.json';
            
            if (file_exists($jsonFile)) {
                $timestamp = date('Y-m-d_H-i-s');
                $backupFile = __DIR__ . "/database/requests_backup_{$timestamp}.json";
                
                if (copy($jsonFile, $backupFile)) {
                    echo '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">';
                    echo '✅ تم إنشاء النسخة الاحتياطية بنجاح!';
                    echo '<br><small class="text-xs">الملف: ' . basename($backupFile) . '</small>';
                    echo '</div>';
                } else {
                    echo '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">';
                    echo '❌ فشل إنشاء النسخة الاحتياطية';
                    echo '</div>';
                }
            } else {
                echo '<div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">';
                echo '⚠️ لا يوجد ملف طلبات للنسخ الاحتياطي';
                echo '</div>';
            }
        }

        // عرض الإحصائيات
        $jsonFile = __DIR__ . '/database/requests.json';
        if (file_exists($jsonFile)) {
            $content = file_get_contents($jsonFile);
            $requests = json_decode($content, true);
            
            if (is_array($requests)) {
                $total = count($requests);
                $pending = count(array_filter($requests, fn($r) => $r['status'] === 'قيد المراجعة'));
                $approved = count(array_filter($requests, fn($r) => $r['status'] === 'مقبول'));
                $paid = count(array_filter($requests, fn($r) => $r['status'] === 'تم الدفع'));
                $rejected = count(array_filter($requests, fn($r) => $r['status'] === 'مرفوض'));
                
                echo '<div class="mb-6">';
                echo '<h2 class="text-xl font-bold mb-4">📊 إحصائيات الطلبات</h2>';
                echo '<div class="grid grid-cols-2 gap-4">';
                echo "<div class='bg-blue-50 p-4 rounded'><span class='text-2xl font-bold text-blue-600'>$total</span><br><small class='text-gray-600'>إجمالي الطلبات</small></div>";
                echo "<div class='bg-yellow-50 p-4 rounded'><span class='text-2xl font-bold text-yellow-600'>$pending</span><br><small class='text-gray-600'>قيد المراجعة</small></div>";
                echo "<div class='bg-green-50 p-4 rounded'><span class='text-2xl font-bold text-green-600'>$approved</span><br><small class='text-gray-600'>مقبول</small></div>";
                echo "<div class='bg-purple-50 p-4 rounded'><span class='text-2xl font-bold text-purple-600'>$paid</span><br><small class='text-gray-600'>تم الدفع</small></div>";
                echo '</div>';
                echo '</div>';
            }
        }
        ?>

        <!-- Backup Button -->
        <div class="text-center mb-6">
            <a href="?action=backup" class="inline-block bg-gradient-to-r from-sky-500 to-sky-600 text-white px-8 py-3 rounded-lg font-bold hover:shadow-lg transition">
                💾 إنشاء نسخة احتياطية الآن
            </a>
        </div>

        <!-- Backup Files List -->
        <?php
        $backupFiles = glob(__DIR__ . '/database/requests_backup_*.json');
        if (!empty($backupFiles)) {
            rsort($backupFiles); // أحدث الملفات أولاً
            echo '<div class="mt-8">';
            echo '<h2 class="text-xl font-bold mb-4">📁 النسخ الاحتياطية السابقة</h2>';
            echo '<div class="space-y-2">';
            foreach (array_slice($backupFiles, 0, 10) as $backup) {
                $filename = basename($backup);
                $size = filesize($backup);
                $sizeKB = round($size / 1024, 2);
                $time = filemtime($backup);
                $timeStr = date('Y-m-d H:i:s', $time);
                
                echo '<div class="flex items-center justify-between p-3 bg-gray-50 rounded hover:bg-gray-100">';
                echo "<div><span class='font-semibold'>$filename</span><br><small class='text-gray-500'>$timeStr • {$sizeKB}KB</small></div>";
                echo "<a href='database/$filename' download class='text-blue-600 hover:underline text-sm'>⬇️ تحميل</a>";
                echo '</div>';
            }
            echo '</div>';
            echo '</div>';
        }
        ?>

        <!-- Export to Excel -->
        <div class="mt-8 p-4 bg-blue-50 rounded-lg border border-blue-200">
            <h3 class="font-bold text-blue-800 mb-2">💡 تصدير البيانات إلى Excel</h3>
            <p class="text-sm text-gray-700 mb-3">يمكنك فتح ملف requests.json في Excel مباشرة:</p>
            <ol class="text-sm text-gray-700 mr-4 space-y-1">
                <li>1. افتح Microsoft Excel</li>
                <li>2. اذهب إلى: Data → Get Data → From File → From JSON</li>
                <li>3. اختر ملف database/requests.json</li>
                <li>4. اضغط "Transform Data" ثم "To Table"</li>
            </ol>
        </div>

        <div class="mt-8 text-center">
            <a href="Manager/requests.php" class="text-blue-600 hover:underline">← العودة إلى إدارة الطلبات</a>
        </div>
    </div>
</body>
</html>
