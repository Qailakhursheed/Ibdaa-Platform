<?php
/**
 * 🧪 RBAC Testing Dashboard
 * صفحة اختبار نظام توزيع الأدوار والصلاحيات
 * 
 * Test Accounts:
 * - manager@test.local / Test@123
 * - technical@test.local / Test@123
 * - trainer@test.local / Test@123
 * - student@test.local / Test@123
 */

require_once __DIR__ . '/../database/db.php';

// Check if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_role'])) {
    $role = $_POST['test_role'];
    
    // Get user for this role
    $query = "SELECT id, full_name, email, role FROM users WHERE role = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if ($user) {
        // Auto-login as this user
        session_start();
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['role'] = $user['role']; // Fallback
        
        // Redirect to dashboard
        header('Location: dashboard.php');
        exit;
    }
}

// Get counts of users per role
$roleCounts = [];
foreach (['manager', 'technical', 'trainer', 'student'] as $role) {
    $query = "SELECT COUNT(*) as count FROM users WHERE role = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $roleCounts[$role] = $row['count'];
}

// Get sample users for each role
$sampleUsers = [];
foreach (['manager', 'technical', 'trainer', 'student'] as $role) {
    $query = "SELECT id, full_name, email, role FROM users WHERE role = ? LIMIT 1";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('s', $role);
    $stmt->execute();
    $result = $stmt->get_result();
    $sampleUsers[$role] = $result->fetch_assoc();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 RBAC Testing Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .test-card {
            transition: all 0.3s ease;
        }
        .test-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 40px rgba(0,0,0,0.2);
        }
        .role-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.75rem;
            border-radius: 9999px;
            display: inline-block;
        }
        .role-manager { background: #dbeafe; color: #1e40af; }
        .role-technical { background: #fef3c7; color: #92400e; }
        .role-trainer { background: #d1fae5; color: #065f46; }
        .role-student { background: #e0e7ff; color: #3730a3; }
    </style>
</head>
<body class="p-6">
    <div class="max-w-7xl mx-auto">
        <!-- Header -->
        <div class="bg-white rounded-2xl shadow-2xl p-8 mb-6">
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-4xl font-bold text-gray-800 mb-2">
                        🧪 RBAC Testing Dashboard
                    </h1>
                    <p class="text-gray-600 text-lg">
                        اختبار نظام توزيع الأدوار والصلاحيات
                    </p>
                </div>
                <div class="text-right">
                    <div class="text-sm text-gray-500">التاريخ</div>
                    <div class="text-2xl font-bold text-purple-600">
                        <?php echo date('Y-m-d H:i'); ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
            <?php
            $roleStats = [
                'manager' => ['icon' => 'shield-check', 'label' => 'مديرون', 'pages' => 16, 'color' => 'blue'],
                'technical' => ['icon' => 'wrench', 'label' => 'مشرفون فنيون', 'pages' => 12, 'color' => 'amber'],
                'trainer' => ['icon' => 'user-check', 'label' => 'مدربون', 'pages' => 7, 'color' => 'green'],
                'student' => ['icon' => 'graduation-cap', 'label' => 'طلاب', 'pages' => 1, 'color' => 'indigo']
            ];
            
            foreach ($roleStats as $role => $info):
                $count = $roleCounts[$role];
                $colorClass = [
                    'blue' => 'from-blue-500 to-blue-600',
                    'amber' => 'from-amber-500 to-amber-600',
                    'green' => 'from-green-500 to-green-600',
                    'indigo' => 'from-indigo-500 to-indigo-600'
                ][$info['color']];
            ?>
            <div class="bg-gradient-to-br <?php echo $colorClass; ?> rounded-xl shadow-lg p-6 text-white">
                <div class="flex items-center justify-between mb-4">
                    <i data-lucide="<?php echo $info['icon']; ?>" class="w-8 h-8"></i>
                    <div class="text-3xl font-bold"><?php echo $count; ?></div>
                </div>
                <div class="text-lg font-semibold mb-1"><?php echo $info['label']; ?></div>
                <div class="text-sm opacity-90"><?php echo $info['pages']; ?> صفحة متاحة</div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Test Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <?php
            $roleDetails = [
                'manager' => [
                    'title' => 'المدير',
                    'icon' => 'shield-check',
                    'color' => 'blue',
                    'pages' => 16,
                    'exclusive' => ['التقارير المتقدمة', 'تقارير الحضور', 'الخريجون', 'الإعدادات'],
                    'description' => 'كامل الصلاحيات - إدارة كاملة للمنصة'
                ],
                'technical' => [
                    'title' => 'المشرف الفني',
                    'icon' => 'wrench',
                    'color' => 'amber',
                    'pages' => 12,
                    'exclusive' => ['البطاقات الذكية 🎴'],
                    'description' => 'إدارة فنية + بطاقات ذكية'
                ],
                'trainer' => [
                    'title' => 'المدرب',
                    'icon' => 'user-check',
                    'color' => 'green',
                    'pages' => 7,
                    'exclusive' => ['إدارة دوراته', 'إدخال درجات دوراته'],
                    'description' => 'إدارة الدورات + الإعلانات + الدرجات'
                ],
                'student' => [
                    'title' => 'الطالب',
                    'icon' => 'graduation-cap',
                    'color' => 'indigo',
                    'pages' => 1,
                    'exclusive' => ['واجهة منفصلة تماماً'],
                    'description' => 'عرض الدورات + المحتوى التعليمي فقط'
                ]
            ];

            foreach ($roleDetails as $role => $details):
                $user = $sampleUsers[$role];
                $bgColor = [
                    'blue' => 'bg-blue-50 border-blue-200',
                    'amber' => 'bg-amber-50 border-amber-200',
                    'green' => 'bg-green-50 border-green-200',
                    'indigo' => 'bg-indigo-50 border-indigo-200'
                ][$details['color']];
                
                $btnColor = [
                    'blue' => 'bg-blue-600 hover:bg-blue-700',
                    'amber' => 'bg-amber-600 hover:bg-amber-700',
                    'green' => 'bg-green-600 hover:bg-green-700',
                    'indigo' => 'bg-indigo-600 hover:bg-indigo-700'
                ][$details['color']];
            ?>
            <div class="test-card bg-white rounded-2xl shadow-xl overflow-hidden border-2 <?php echo $bgColor; ?>">
                <!-- Card Header -->
                <div class="bg-gradient-to-r <?php 
                    echo [
                        'blue' => 'from-blue-500 to-blue-600',
                        'amber' => 'from-amber-500 to-amber-600',
                        'green' => 'from-green-500 to-green-600',
                        'indigo' => 'from-indigo-500 to-indigo-600'
                    ][$details['color']];
                ?> p-6 text-white">
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center gap-3">
                            <i data-lucide="<?php echo $details['icon']; ?>" class="w-10 h-10"></i>
                            <div>
                                <h2 class="text-2xl font-bold"><?php echo $details['title']; ?></h2>
                                <span class="role-badge role-<?php echo $role; ?> text-xs">
                                    <?php echo $role; ?>
                                </span>
                            </div>
                        </div>
                        <div class="text-right">
                            <div class="text-4xl font-bold"><?php echo $details['pages']; ?></div>
                            <div class="text-sm opacity-90">صفحة</div>
                        </div>
                    </div>
                    <p class="text-sm opacity-90"><?php echo $details['description']; ?></p>
                </div>

                <!-- Card Body -->
                <div class="p-6">
                    <!-- User Info -->
                    <?php if ($user): ?>
                    <div class="mb-4 p-4 bg-gray-50 rounded-lg">
                        <div class="flex items-center gap-3 mb-2">
                            <i data-lucide="user" class="w-5 h-5 text-gray-400"></i>
                            <div>
                                <div class="font-semibold text-gray-800"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="text-sm text-gray-500"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <div class="mb-4 p-4 bg-red-50 border border-red-200 rounded-lg">
                        <div class="flex items-center gap-2 text-red-600">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            <span class="text-sm font-semibold">لا يوجد مستخدم لهذا الدور</span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Exclusive Features -->
                    <div class="mb-4">
                        <div class="text-xs font-semibold text-gray-500 uppercase mb-2">
                            الصلاحيات الحصرية
                        </div>
                        <div class="space-y-1">
                            <?php foreach ($details['exclusive'] as $feature): ?>
                            <div class="flex items-center gap-2 text-sm text-gray-700">
                                <i data-lucide="check-circle" class="w-4 h-4 text-green-500"></i>
                                <span><?php echo $feature; ?></span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Test Button -->
                    <?php if ($user): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="test_role" value="<?php echo $role; ?>">
                        <button type="submit" 
                                class="w-full <?php echo $btnColor; ?> text-white font-bold py-4 px-6 rounded-xl 
                                       transition duration-300 transform hover:scale-105 shadow-lg
                                       flex items-center justify-center gap-3">
                            <i data-lucide="play-circle" class="w-6 h-6"></i>
                            <span class="text-lg">اختبار دور <?php echo $details['title']; ?></span>
                        </button>
                    </form>
                    <?php else: ?>
                    <button disabled 
                            class="w-full bg-gray-300 text-gray-500 font-bold py-4 px-6 rounded-xl 
                                   cursor-not-allowed flex items-center justify-center gap-3">
                        <i data-lucide="lock" class="w-6 h-6"></i>
                        <span class="text-lg">غير متوفر</span>
                    </button>
                    <?php endif; ?>

                    <!-- Expected Sidebar Count -->
                    <div class="mt-4 text-center text-sm text-gray-500">
                        <i data-lucide="layout-list" class="w-4 h-4 inline"></i>
                        يجب رؤية <strong class="text-gray-800"><?php echo $details['pages']; ?></strong> 
                        <?php echo $role === 'student' ? 'واجهة منفصلة' : 'رابط في Sidebar'; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Expected Results -->
        <div class="mt-6 bg-white rounded-2xl shadow-xl p-8">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                <i data-lucide="clipboard-check" class="w-8 h-8 text-purple-600"></i>
                النتائج المتوقعة
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <h4 class="font-bold text-lg text-gray-800 mb-3">✅ يجب أن يحدث:</h4>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span><strong>Manager:</strong> يرى 16 رابط في Sidebar (كل الصفحات)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span><strong>Technical:</strong> يرى 12 رابط (لا يرى: Analytics, Graduates, Settings)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span><strong>Trainer:</strong> يرى 7 روابط فقط (دورات، إعلانات، درجات)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span><strong>Student:</strong> واجهة منفصلة تماماً (لا Sidebar إداري)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span>الروابط المخفية <strong>display: none</strong> تماماً</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="check" class="w-5 h-5 text-green-500 mt-0.5"></i>
                            <span>عند محاولة الوصول لصفحة ممنوعة: رسالة "لا تملك صلاحية"</span>
                        </li>
                    </ul>
                </div>

                <div>
                    <h4 class="font-bold text-lg text-gray-800 mb-3">❌ يجب ألا يحدث:</h4>
                    <ul class="space-y-2 text-gray-700">
                        <li class="flex items-start gap-2">
                            <i data-lucide="x" class="w-5 h-5 text-red-500 mt-0.5"></i>
                            <span>ظهور أكواد PHP في الصفحة</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="x" class="w-5 h-5 text-red-500 mt-0.5"></i>
                            <span>رؤية روابط غير مصرح بها (حتى لو مخفية بـ opacity)</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="x" class="w-5 h-5 text-red-500 mt-0.5"></i>
                            <span>الوصول لصفحة ممنوعة بالضغط على رابط مخفي</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="x" class="w-5 h-5 text-red-500 mt-0.5"></i>
                            <span>أخطاء JavaScript في Console</span>
                        </li>
                        <li class="flex items-start gap-2">
                            <i data-lucide="x" class="w-5 h-5 text-red-500 mt-0.5"></i>
                            <span>تحميل كل الصفحات تلقائياً (يجب التحميل عند الضغط)</span>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Test Instructions -->
        <div class="mt-6 bg-gradient-to-r from-purple-500 to-pink-500 rounded-2xl shadow-xl p-8 text-white">
            <h3 class="text-2xl font-bold mb-4 flex items-center gap-3">
                <i data-lucide="list-checks" class="w-8 h-8"></i>
                خطوات الاختبار
            </h3>
            <ol class="space-y-3 text-lg">
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">1</span>
                    <span>اضغط على زر "اختبار دور" لأي دور من الأعلى</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">2</span>
                    <span>سيتم تسجيل الدخول تلقائياً والانتقال إلى Dashboard</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">3</span>
                    <span>عُد عدد الروابط الظاهرة في Sidebar (يجب أن يطابق العدد المتوقع)</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">4</span>
                    <span>افتح Console (F12) → تأكد من عدم وجود أخطاء JavaScript</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">5</span>
                    <span>جرب الضغط على الروابط المختلفة → تحقق من التحميل الصحيح</span>
                </li>
                <li class="flex items-start gap-3">
                    <span class="flex-shrink-0 w-8 h-8 bg-white text-purple-600 rounded-full flex items-center justify-center font-bold">6</span>
                    <span>ارجع لهذه الصفحة واختبر الدور التالي</span>
                </li>
            </ol>
        </div>

        <!-- Back to Dashboard -->
        <div class="mt-6 text-center">
            <a href="dashboard.php" 
               class="inline-flex items-center gap-2 bg-white text-purple-600 font-bold py-3 px-8 
                      rounded-xl shadow-lg hover:bg-purple-50 transition duration-300">
                <i data-lucide="arrow-right" class="w-5 h-5"></i>
                <span>العودة إلى Dashboard</span>
            </a>
        </div>
    </div>

    <script>
        lucide.createIcons();
        
        // Auto-scroll to top on page load
        window.scrollTo(0, 0);
    </script>
</body>
</html>
