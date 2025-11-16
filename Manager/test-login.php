<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <title>اختبار نظام تسجيل الدخول</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class="min-h-screen bg-slate-50 p-8">
    <div class="max-w-4xl mx-auto">
        <h1 class="text-3xl font-bold text-slate-800 mb-8">🧪 اختبار نظام تسجيل الدخول والترابط</h1>
        
        <!-- Session Status -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">حالة الجلسة</h2>
            <?php
            session_start();
            
            if (isset($_SESSION['user_id'])) {
                echo '<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 p-4 rounded-lg mb-4">';
                echo '<p class="font-bold">✅ تم تسجيل الدخول بنجاح!</p>';
                echo '<div class="mt-3 space-y-2 text-sm">';
                echo '<p><strong>المستخدم:</strong> ' . htmlspecialchars($_SESSION['user_name'] ?? 'غير محدد') . '</p>';
                echo '<p><strong>البريد:</strong> ' . htmlspecialchars($_SESSION['user_email'] ?? 'غير محدد') . '</p>';
                echo '<p><strong>الدور:</strong> ' . htmlspecialchars($_SESSION['user_role'] ?? 'غير محدد') . '</p>';
                echo '<p><strong>رقم المستخدم:</strong> ' . htmlspecialchars($_SESSION['user_id'] ?? 'غير محدد') . '</p>';
                echo '</div>';
                echo '</div>';
            } else {
                echo '<div class="bg-amber-50 border border-amber-200 text-amber-700 p-4 rounded-lg">';
                echo '<p class="font-bold">⚠️ لم يتم تسجيل الدخول</p>';
                echo '<p class="text-sm mt-2">يرجى تسجيل الدخول أولاً</p>';
                echo '</div>';
            }
            ?>
        </div>

        <!-- Login Options -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">خيارات الدخول</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <a href="login.php" class="block px-6 py-4 bg-sky-600 text-white text-center rounded-lg hover:bg-sky-700 transition font-semibold">
                    🔑 صفحة تسجيل الدخول
                </a>
                <a href="logout.php" class="block px-6 py-4 bg-red-600 text-white text-center rounded-lg hover:bg-red-700 transition font-semibold">
                    🚪 تسجيل الخروج
                </a>
            </div>
        </div>

        <!-- Dashboard Links -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">اللوحات المتاحة</h2>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php
                $role = $_SESSION['user_role'] ?? '';
                
                if ($role === 'manager') {
                    echo '<a href="dashboards/manager-dashboard.php" class="block px-6 py-4 bg-violet-600 text-white text-center rounded-lg hover:bg-violet-700 transition font-semibold">';
                    echo '👔 لوحة المدير';
                    echo '</a>';
                }
                
                if ($role === 'technical') {
                    echo '<a href="dashboards/technical-dashboard.php" class="block px-6 py-4 bg-sky-600 text-white text-center rounded-lg hover:bg-sky-700 transition font-semibold">';
                    echo '🔧 لوحة المشرف الفني';
                    echo '</a>';
                }
                
                if ($role === 'trainer') {
                    echo '<a href="dashboards/trainer-dashboard.php" class="block px-6 py-4 bg-emerald-600 text-white text-center rounded-lg hover:bg-emerald-700 transition font-semibold">';
                    echo '👨‍🏫 لوحة المدرب';
                    echo '</a>';
                }
                
                if ($role === 'student') {
                    echo '<a href="dashboards/student-dashboard.php" class="block px-6 py-4 bg-amber-600 text-white text-center rounded-lg hover:bg-amber-700 transition font-semibold">';
                    echo '🎓 لوحة الطالب';
                    echo '</a>';
                }
                
                // Router link for testing
                echo '<a href="dashboard_router.php" class="block px-6 py-4 bg-slate-600 text-white text-center rounded-lg hover:bg-slate-700 transition font-semibold">';
                echo '🔀 الموجه التلقائي';
                echo '</a>';
                ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- System Status -->
        <div class="bg-white rounded-xl shadow p-6 mb-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">حالة النظام</h2>
            <div class="space-y-2 text-sm">
                <?php
                // Check database connection
                require_once __DIR__ . '/../database/db.php';
                
                if ($conn && $conn->ping()) {
                    echo '<p class="text-emerald-600">✅ الاتصال بقاعدة البيانات: نشط</p>';
                } else {
                    echo '<p class="text-red-600">❌ الاتصال بقاعدة البيانات: فشل</p>';
                }
                
                // Check session
                echo '<p class="text-emerald-600">✅ نظام الجلسات: نشط</p>';
                echo '<p class="text-slate-600">📂 معرف الجلسة: ' . session_id() . '</p>';
                
                // Check files
                $files = [
                    'login.php' => 'صفحة تسجيل الدخول',
                    'logout.php' => 'صفحة الخروج',
                    'dashboard_router.php' => 'موجه اللوحات',
                    'dashboards/manager-dashboard.php' => 'لوحة المدير',
                    'dashboards/technical-dashboard.php' => 'لوحة المشرف الفني',
                    'dashboards/trainer-dashboard.php' => 'لوحة المدرب',
                    'dashboards/student-dashboard.php' => 'لوحة الطالب',
                    'dashboards/shared-header.php' => 'الهيدر المشترك',
                    'js/dashboard-integration.js' => 'نظام التكامل'
                ];
                
                foreach ($files as $file => $name) {
                    if (file_exists(__DIR__ . '/' . $file)) {
                        echo '<p class="text-emerald-600">✅ ' . $name . ': موجود</p>';
                    } else {
                        echo '<p class="text-red-600">❌ ' . $name . ': غير موجود</p>';
                    }
                }
                ?>
            </div>
        </div>

        <!-- Test Buttons -->
        <?php if (isset($_SESSION['user_id'])): ?>
        <div class="bg-white rounded-xl shadow p-6">
            <h2 class="text-xl font-bold text-slate-800 mb-4">اختبار JavaScript</h2>
            <div class="space-y-3">
                <button onclick="testToast()" class="w-full px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition font-semibold">
                    اختبار Toast Notification
                </button>
                <button onclick="testModal()" class="w-full px-6 py-3 bg-violet-600 text-white rounded-lg hover:bg-violet-700 transition font-semibold">
                    اختبار Modal
                </button>
                <button onclick="testAPI()" class="w-full px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition font-semibold">
                    اختبار API Integration
                </button>
            </div>
            <div id="testResult" class="mt-4 p-4 bg-slate-50 rounded-lg hidden">
                <pre id="resultContent" class="text-xs overflow-auto"></pre>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Load Integration System -->
    <script src="js/dashboard-integration.js"></script>
    
    <script>
        function testToast() {
            if (typeof DashboardIntegration !== 'undefined') {
                DashboardIntegration.ui.showToast('✅ النظام يعمل بشكل ممتاز!', 'success');
            } else {
                alert('DashboardIntegration غير محمل!');
            }
        }

        function testModal() {
            if (typeof DashboardIntegration !== 'undefined') {
                DashboardIntegration.ui.showModal(
                    'اختبار المودال',
                    '<p class="text-center py-8">✅ المودال يعمل بشكل ممتاز!</p>',
                    [
                        {
                            text: 'إغلاق',
                            class: 'bg-slate-600 text-white hover:bg-slate-700',
                            onclick: 'this.closest(".fixed").remove()'
                        }
                    ]
                );
            } else {
                alert('DashboardIntegration غير محمل!');
            }
        }

        async function testAPI() {
            const result = document.getElementById('testResult');
            const content = document.getElementById('resultContent');
            
            result.classList.remove('hidden');
            content.textContent = 'جاري الاختبار...';
            
            if (typeof DashboardIntegration !== 'undefined') {
                try {
                    // Test Analytics API
                    const stats = await DashboardIntegration.api.analytics.getDashboardStats();
                    content.textContent = JSON.stringify(stats, null, 2);
                    
                    if (stats.success) {
                        DashboardIntegration.ui.showToast('✅ API يعمل بشكل ممتاز!', 'success');
                    }
                } catch (error) {
                    content.textContent = 'خطأ: ' + error.message;
                    DashboardIntegration.ui.showToast('❌ فشل الاتصال بالـ API', 'error');
                }
            } else {
                content.textContent = 'DashboardIntegration غير محمل!';
            }
        }

        // Auto-check on load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🧪 Test Page Loaded');
            
            if (typeof DashboardIntegration !== 'undefined') {
                console.log('✅ DashboardIntegration: Loaded');
                console.log('Current User:', DashboardIntegration.currentUser);
            } else {
                console.warn('⚠️ DashboardIntegration: Not Loaded');
            }
        });
    </script>
</body>
</html>
