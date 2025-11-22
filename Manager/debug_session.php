<?php
/**
 * صفحة تصحيح الأخطاء - للتحقق من الجلسات وقاعدة البيانات
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../database/db.php';

SessionSecurity::startSecureSession();

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>صفحة التصحيح</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-slate-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-4xl mx-auto">
            <h1 class="text-3xl font-bold text-slate-800 mb-6">🔍 صفحة التصحيح - Debug Page</h1>
            
            <!-- معلومات الجلسة -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-sky-600 mb-4">📊 معلومات الجلسة (Session Info)</h2>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="bg-green-50 border border-green-200 rounded p-4 mb-4">
                        <p class="font-bold text-green-800">✅ مسجل دخول (Logged In)</p>
                    </div>
                    <table class="w-full text-right">
                        <tr class="border-b">
                            <td class="py-2 font-bold text-slate-700">User ID:</td>
                            <td class="py-2"><?php echo htmlspecialchars($_SESSION['user_id'] ?? 'غير محدد'); ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-bold text-slate-700">User Role:</td>
                            <td class="py-2"><?php echo htmlspecialchars($_SESSION['user_role'] ?? $_SESSION['role'] ?? 'غير محدد'); ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-bold text-slate-700">User Name:</td>
                            <td class="py-2"><?php echo htmlspecialchars($_SESSION['user_name'] ?? $_SESSION['full_name'] ?? 'غير محدد'); ?></td>
                        </tr>
                        <tr class="border-b">
                            <td class="py-2 font-bold text-slate-700">User Email:</td>
                            <td class="py-2"><?php echo htmlspecialchars($_SESSION['user_email'] ?? 'غير محدد'); ?></td>
                        </tr>
                    </table>
                <?php else: ?>
                    <div class="bg-red-50 border border-red-200 rounded p-4">
                        <p class="font-bold text-red-800">❌ غير مسجل دخول (Not Logged In)</p>
                    </div>
                <?php endif; ?>
            </div>

            <!-- كل محتويات الجلسة -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-sky-600 mb-4">🔐 محتويات الجلسة الكاملة</h2>
                <pre class="bg-slate-50 p-4 rounded overflow-x-auto text-sm"><?php print_r($_SESSION); ?></pre>
            </div>

            <!-- معلومات قاعدة البيانات -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-sky-600 mb-4">🗄️ معلومات قاعدة البيانات</h2>
                <?php
                try {
                    // التحقق من الاتصال
                    if ($conn->ping()) {
                        echo '<div class="bg-green-50 border border-green-200 rounded p-4 mb-4">';
                        echo '<p class="font-bold text-green-800">✅ الاتصال بقاعدة البيانات نشط</p>';
                        echo '</div>';
                        
                        // عرض حسابات المدراء
                        echo '<h3 class="font-bold text-lg mb-2">حسابات المدراء:</h3>';
                        $result = $conn->query("SELECT id, full_name, email, role FROM users WHERE role = 'manager' LIMIT 5");
                        if ($result && $result->num_rows > 0) {
                            echo '<table class="w-full text-right border">';
                            echo '<tr class="bg-slate-100"><th class="p-2 border">ID</th><th class="p-2 border">الاسم</th><th class="p-2 border">البريد</th><th class="p-2 border">الدور</th></tr>';
                            while ($row = $result->fetch_assoc()) {
                                echo '<tr>';
                                echo '<td class="p-2 border">' . htmlspecialchars($row['id']) . '</td>';
                                echo '<td class="p-2 border">' . htmlspecialchars($row['full_name']) . '</td>';
                                echo '<td class="p-2 border">' . htmlspecialchars($row['email']) . '</td>';
                                echo '<td class="p-2 border"><span class="bg-sky-100 px-2 py-1 rounded">' . htmlspecialchars($row['role']) . '</span></td>';
                                echo '</tr>';
                            }
                            echo '</table>';
                        } else {
                            echo '<p class="text-red-600">❌ لا يوجد حسابات مدراء في قاعدة البيانات</p>';
                        }
                        
                        // عرض إحصائيات
                        echo '<h3 class="font-bold text-lg mt-4 mb-2">إحصائيات سريعة:</h3>';
                        $stats = [];
                        $result = $conn->query("SELECT COUNT(*) as count FROM users");
                        if ($result) $stats['total_users'] = $result->fetch_assoc()['count'];
                        
                        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
                        if ($result) $stats['students'] = $result->fetch_assoc()['count'];
                        
                        $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
                        if ($result) $stats['trainers'] = $result->fetch_assoc()['count'];
                        
                        $result = $conn->query("SELECT COUNT(*) as count FROM courses");
                        if ($result) $stats['courses'] = $result->fetch_assoc()['count'];
                        
                        echo '<div class="grid grid-cols-2 gap-4">';
                        echo '<div class="bg-sky-50 p-4 rounded"><p class="text-sm text-slate-600">إجمالي المستخدمين</p><p class="text-2xl font-bold">' . ($stats['total_users'] ?? 0) . '</p></div>';
                        echo '<div class="bg-emerald-50 p-4 rounded"><p class="text-sm text-slate-600">الطلاب</p><p class="text-2xl font-bold">' . ($stats['students'] ?? 0) . '</p></div>';
                        echo '<div class="bg-amber-50 p-4 rounded"><p class="text-sm text-slate-600">المدربون</p><p class="text-2xl font-bold">' . ($stats['trainers'] ?? 0) . '</p></div>';
                        echo '<div class="bg-violet-50 p-4 rounded"><p class="text-sm text-slate-600">الدورات</p><p class="text-2xl font-bold">' . ($stats['courses'] ?? 0) . '</p></div>';
                        echo '</div>';
                        
                    } else {
                        echo '<div class="bg-red-50 border border-red-200 rounded p-4">';
                        echo '<p class="font-bold text-red-800">❌ فشل الاتصال بقاعدة البيانات</p>';
                        echo '</div>';
                    }
                } catch (Exception $e) {
                    echo '<div class="bg-red-50 border border-red-200 rounded p-4">';
                    echo '<p class="font-bold text-red-800">❌ خطأ: ' . htmlspecialchars($e->getMessage()) . '</p>';
                    echo '</div>';
                }
                ?>
            </div>

            <!-- معلومات السيرفر -->
            <div class="bg-white rounded-lg shadow-md p-6 mb-6">
                <h2 class="text-2xl font-bold text-sky-600 mb-4">⚙️ معلومات السيرفر</h2>
                <table class="w-full text-right">
                    <tr class="border-b">
                        <td class="py-2 font-bold text-slate-700">PHP Version:</td>
                        <td class="py-2"><?php echo PHP_VERSION; ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-bold text-slate-700">Server Software:</td>
                        <td class="py-2"><?php echo $_SERVER['SERVER_SOFTWARE'] ?? 'غير محدد'; ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-bold text-slate-700">Document Root:</td>
                        <td class="py-2 text-sm"><?php echo $_SERVER['DOCUMENT_ROOT'] ?? 'غير محدد'; ?></td>
                    </tr>
                    <tr class="border-b">
                        <td class="py-2 font-bold text-slate-700">Request URI:</td>
                        <td class="py-2 text-sm"><?php echo $_SERVER['REQUEST_URI'] ?? 'غير محدد'; ?></td>
                    </tr>
                </table>
            </div>

            <!-- أزرار الإجراءات -->
            <div class="bg-white rounded-lg shadow-md p-6">
                <h2 class="text-2xl font-bold text-sky-600 mb-4">🔧 إجراءات</h2>
                <div class="flex gap-4 flex-wrap">
                    <a href="login.php" class="px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700">
                        تسجيل الدخول
                    </a>
                    <a href="dashboard_router.php" class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">
                        لوحة التحكم
                    </a>
                    <a href="logout.php" class="px-6 py-3 bg-red-600 text-white rounded-lg hover:bg-red-700">
                        تسجيل الخروج
                    </a>
                    <button onclick="location.reload()" class="px-6 py-3 bg-slate-600 text-white rounded-lg hover:bg-slate-700">
                        تحديث
                    </button>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
