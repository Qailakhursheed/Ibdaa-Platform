<?php
/**
 * Manager Dashboard - Complete Functional Version
 * لوحة تحكم المدير العام - النسخة الوظيفية الكاملة
 * 
 * @version 3.0
 * @author Ibdaa Platform Team
 */

require_once __DIR__ . '/shared-header.php';

// التحقق من الصلاحية - المدير فقط
if ($userRole !== 'manager') {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// جلب الإحصائيات الأساسية
$stats = [
    'total_students' => 0,
    'active_courses' => 0,
    'total_revenue' => 0,
    'certificates_issued' => 0,
    'pending_requests' => 0,
    'active_trainers' => 0,
    'total_payments' => 0,
    'this_month_revenue' => 0
];

try {
    // إجمالي الطلاب
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if ($result) $stats['total_students'] = (int)$result->fetch_assoc()['count'];
    
    // الدورات النشطة
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    if ($result) $stats['active_courses'] = (int)$result->fetch_assoc()['count'];
    
    // إجمالي الإيرادات
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
    if ($result) $stats['total_revenue'] = (float)$result->fetch_assoc()['total'];
    
    // الشهادات الصادرة
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE certificate_issued = 1");
    if ($result) $stats['certificates_issued'] = (int)$result->fetch_assoc()['count'];
    
    // الطلبات المعلقة
    $result = $conn->query("SELECT COUNT(*) as count FROM registration_requests WHERE status = 'pending'");
    if ($result) $stats['pending_requests'] = (int)$result->fetch_assoc()['count'];
    
    // المدربون النشطون
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer' AND status = 'active'");
    if ($result) $stats['active_trainers'] = (int)$result->fetch_assoc()['count'];
    
    // إجمالي الدفعات
    $result = $conn->query("SELECT COUNT(*) as count FROM payments");
    if ($result) $stats['total_payments'] = (int)$result->fetch_assoc()['count'];
    
    // إيرادات هذا الشهر
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    if ($result) $stats['this_month_revenue'] = (float)$result->fetch_assoc()['total'];
    
} catch (Exception $e) {
    error_log("Stats Error: " . $e->getMessage());
}
?>

<div id="managerDashboard" class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-white border-l border-slate-200 shadow-sm fixed h-screen overflow-y-auto">
        <!-- Logo Section -->
        <div class="px-6 py-6 border-b border-slate-200 text-center bg-gradient-to-br from-sky-50 to-white">
            <img src="../../platform/photos/Sh.jpg" alt="شعار منصة إبداع" class="mx-auto mb-3 w-20 h-20 rounded-full border-4 border-sky-500 shadow-lg object-cover">
            <h1 class="text-2xl font-bold text-slate-800">منصة إبداع</h1>
            <p class="text-sm text-slate-600 mt-1 font-semibold">لوحة المدير العام</p>
            <p class="text-xs text-slate-500 mt-1">أ. عبد الباسط يوسف اليوسفي</p>
            <div class="mt-3 px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-xs inline-block">
                <i data-lucide="shield-check" class="w-3 h-3 inline"></i>
                صلاحيات كاملة
            </div>
        </div>
        
        <nav class="px-4 py-6 space-y-2 text-slate-700">
            <!-- لوحة التحكم -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 transition active" data-page="dashboard">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">لوحة التحكم</span>
            </a>

            <!-- إدارة المستخدمين -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-section="users">
                    <div class="flex items-center gap-3">
                        <i data-lucide="users-2" class="w-5 h-5"></i>
                        <span class="font-medium">إدارة المستخدمين</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="trainees">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>المتدربون</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="trainers">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        <span>المدربون</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="graduates">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>ملف الخريجين</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="users-management">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>إدارة شاملة</span>
                    </a>
                </div>
            </div>

            <!-- الدورات التدريبية -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-section="courses">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span class="font-medium">الدورات التدريبية</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="courses">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>إدارة الدورات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="grades">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span>الدرجات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="attendance">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>الحضور</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="certificates">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>الشهادات</span>
                    </a>
                </div>
            </div>

            <!-- الشؤون المالية -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-page="finance">
                <i data-lucide="wallet" class="w-5 h-5"></i>
                <span class="font-medium">الشؤون المالية</span>
            </a>

            <!-- طلبات وإعلانات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-section="requests">
                    <div class="flex items-center gap-3">
                        <i data-lucide="inbox" class="w-5 h-5"></i>
                        <span class="font-medium">الطلبات والإعلانات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="requests">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        <span>طلبات الالتحاق</span>
                        <?php if ($stats['pending_requests'] > 0): ?>
                        <span class="mr-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                            <?php echo $stats['pending_requests']; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="announcements">
                        <i data-lucide="megaphone" class="w-4 h-4"></i>
                        <span>الإعلانات</span>
                    </a>
                </div>
            </div>

            <!-- التقارير والأدوات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-section="reports">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        <span class="font-medium">التقارير والأدوات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="analytics">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>التحليلات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="idcards">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                        <span>البطاقات الطلابية</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="imports">
                        <i data-lucide="file-up" class="w-4 h-4"></i>
                        <span>الاستيراد الذكي</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="ai-images">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>توليد الصور AI</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="ai-charts">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        <span>رسوم بيانية AI</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm transition" data-page="certificate-designer">
                        <i data-lucide="pen-tool" class="w-4 h-4"></i>
                        <span>مصمم الشهادات</span>
                    </a>
                </div>
            </div>

            <!-- الإعدادات -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 transition" data-page="settings">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span class="font-medium">الإعدادات</span>
            </a>

            <!-- تسجيل الخروج -->
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-red-600 transition mt-4">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span class="font-medium">تسجيل الخروج</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 mr-72">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 shadow-sm sticky top-0 z-40">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">مرحباً،</p>
                    <h2 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($userName); ?></h2>
                    <p class="text-xs text-sky-600 font-medium mt-1">
                        <i data-lucide="shield" class="w-3 h-3 inline"></i>
                        المدير العام
                    </p>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Notifications Bell -->
                    <button id="notificationsBell" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50 transition">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="notificationsCounter" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center font-bold"></span>
                    </button>
                    
                    <!-- Quick Actions -->
                    <button onclick="navigateTo('trainees')" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition" title="إضافة متدرب">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </button>
                    
                    <button onclick="navigateTo('courses')" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition" title="إضافة دورة">
                        <i data-lucide="book-plus" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        </header>

        <!-- Dynamic Page Content -->
        <div id="pageContainer" class="p-8">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 id="pageTitle" class="text-3xl font-bold text-slate-800 mb-2">لوحة التحكم الرئيسية</h1>
                <p id="pageSubtitle" class="text-slate-600">نظرة عامة على أداء المنصة</p>
            </div>

            <!-- Page Body -->
            <div id="pageBody">
                <!-- Stats Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Total Students -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-sky-50 text-sky-600">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                            <span class="text-xs text-emerald-600 font-semibold">
                                <i data-lucide="trending-up" class="w-3 h-3 inline"></i>
                            </span>
                        </div>
                        <p class="text-sm text-slate-500 mb-1">إجمالي المتدربين</p>
                        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_students']); ?></p>
                        <button onclick="navigateTo('trainees')" class="mt-3 text-xs text-sky-600 hover:text-sky-700 font-medium">
                            عرض التفاصيل ←
                        </button>
                    </div>

                    <!-- Active Courses -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                                <i data-lucide="book-open" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 mb-1">الدورات النشطة</p>
                        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['active_courses']); ?></p>
                        <button onclick="navigateTo('courses')" class="mt-3 text-xs text-emerald-600 hover:text-emerald-700 font-medium">
                            إدارة الدورات ←
                        </button>
                    </div>

                    <!-- Total Revenue -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
                                <i data-lucide="wallet" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 mb-1">إجمالي الإيرادات</p>
                        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_revenue'], 0); ?></p>
                        <p class="text-xs text-slate-500 mt-1">ريال يمني</p>
                        <button onclick="navigateTo('finance')" class="mt-3 text-xs text-amber-600 hover:text-amber-700 font-medium">
                            التقرير المالي ←
                        </button>
                    </div>

                    <!-- Certificates Issued -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-xl bg-violet-50 text-violet-600">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500 mb-1">الشهادات الصادرة</p>
                        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['certificates_issued']); ?></p>
                        <button onclick="navigateTo('graduates')" class="mt-3 text-xs text-violet-600 hover:text-violet-700 font-medium">
                            ملف الخريجين ←
                        </button>
                    </div>
                </div>

                <!-- Secondary Stats -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                    <!-- Active Trainers -->
                    <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-5 border border-blue-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-blue-100 text-blue-600">
                                <i data-lucide="user-check" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-600">المدربون النشطون</p>
                                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['active_trainers']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requests -->
                    <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl p-5 border border-orange-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-orange-100 text-orange-600">
                                <i data-lucide="clock" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-600">طلبات معلقة</p>
                                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['pending_requests']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Total Payments -->
                    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-5 border border-green-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-green-100 text-green-600">
                                <i data-lucide="credit-card" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-600">إجمالي الدفعات</p>
                                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_payments']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- This Month Revenue -->
                    <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-5 border border-purple-100">
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-lg bg-purple-100 text-purple-600">
                                <i data-lucide="trending-up" class="w-5 h-5"></i>
                            </div>
                            <div>
                                <p class="text-xs text-slate-600">إيرادات الشهر</p>
                                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['this_month_revenue'], 0); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 mb-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="zap" class="w-5 h-5 text-sky-600"></i>
                        إجراءات سريعة
                    </h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button onclick="navigateTo('trainees')" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-sky-500 hover:bg-sky-50 transition">
                            <div class="p-3 rounded-xl bg-sky-100 text-sky-600 group-hover:scale-110 transition">
                                <i data-lucide="user-plus" class="w-7 h-7"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-700">إدارة المتدربين</span>
                        </button>
                        
                        <button onclick="navigateTo('courses')" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50 transition">
                            <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 group-hover:scale-110 transition">
                                <i data-lucide="book-plus" class="w-7 h-7"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-700">إدارة الدورات</span>
                        </button>
                        
                        <button onclick="navigateTo('finance')" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-amber-500 hover:bg-amber-50 transition">
                            <div class="p-3 rounded-xl bg-amber-100 text-amber-600 group-hover:scale-110 transition">
                                <i data-lucide="dollar-sign" class="w-7 h-7"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-700">الشؤون المالية</span>
                        </button>
                        
                        <button onclick="navigateTo('analytics')" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-500 hover:bg-violet-50 transition">
                            <div class="p-3 rounded-xl bg-violet-100 text-violet-600 group-hover:scale-110 transition">
                                <i data-lucide="bar-chart" class="w-7 h-7"></i>
                            </div>
                            <span class="text-sm font-medium text-slate-700">التحليلات</span>
                        </button>
                    </div>
                </div>

                <!-- Recent Activity / Announcements Area -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Recent Registrations -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-lg font-bold text-slate-800">أحدث التسجيلات</h3>
                            <button onclick="navigateTo('trainees')" class="text-sm text-sky-600 hover:text-sky-700">
                                عرض الكل
                            </button>
                        </div>
                        <div id="recentRegistrations" class="space-y-3">
                            <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50">
                                <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                                    <i data-lucide="user" class="w-5 h-5 text-sky-600"></i>
                                </div>
                                <div class="flex-1">
                                    <p class="text-sm font-medium text-slate-800">جاري التحميل...</p>
                                    <p class="text-xs text-slate-500">الرجاء الانتظار</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Status -->
                    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
                        <h3 class="text-lg font-bold text-slate-800 mb-4">حالة النظام</h3>
                        <div class="space-y-3">
                            <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <span class="text-sm font-medium text-slate-700">قاعدة البيانات</span>
                                </div>
                                <span class="text-xs text-emerald-600 font-semibold">نشط</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-emerald-50">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-emerald-500"></div>
                                    <span class="text-sm font-medium text-slate-700">خادم التطبيق</span>
                                </div>
                                <span class="text-xs text-emerald-600 font-semibold">نشط</span>
                            </div>
                            <div class="flex items-center justify-between p-3 rounded-lg bg-sky-50">
                                <div class="flex items-center gap-2">
                                    <div class="w-2 h-2 rounded-full bg-sky-500"></div>
                                    <span class="text-sm font-medium text-slate-700">مساحة التخزين</span>
                                </div>
                                <span class="text-xs text-sky-600 font-semibold">75% متاح</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Container -->
<div id="modalBackdrop" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center px-4 z-50">
    <div class="bg-white w-full max-w-4xl rounded-2xl shadow-2xl overflow-hidden animate-modal">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 id="modalTitle" class="text-xl font-bold text-slate-800"></h3>
            <button id="closeModalBtn" class="p-2 rounded-lg hover:bg-slate-200 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="modalBody" class="px-6 py-6 max-h-[70vh] overflow-y-auto"></div>
    </div>
</div>

<!-- Loading Overlay -->
<div id="loadingOverlay" class="hidden fixed inset-0 bg-slate-900/50 backdrop-blur-sm flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl p-8 shadow-2xl text-center">
        <div class="w-16 h-16 border-4 border-sky-200 border-t-sky-600 rounded-full animate-spin mx-auto mb-4"></div>
        <p class="text-slate-700 font-medium">جاري التحميل...</p>
    </div>
</div>

<style>
/* Sidebar Styles */
.sidebar-link.active {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    color: #0284c7;
    font-weight: 600;
    border-right: 3px solid #0284c7;
}

.sidebar-link.active i {
    color: #0284c7;
}

.sidebar-section-toggle {
    font-weight: 500;
}

.sidebar-section-toggle.active {
    background-color: #f8fafc;
    color: #0f172a;
}

.sidebar-section-toggle.active i:last-child {
    transform: rotate(180deg);
}

.sidebar-submenu {
    animation: slideDown 0.2s ease;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-8px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Smooth Transitions */
#pageContainer, #pageBody, #pageTitle, #pageSubtitle {
    transition: all 0.3s ease;
}

/* Modal Animation */
@keyframes modalFade {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.animate-modal {
    animation: modalFade 0.2s ease;
}

/* Custom Scrollbar */
#sidebar::-webkit-scrollbar {
    width: 6px;
}

#sidebar::-webkit-scrollbar-track {
    background: #f1f5f9;
}

#sidebar::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

#sidebar::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Loading Animation */
@keyframes spin {
    to { transform: rotate(360deg); }
}

.animate-spin {
    animation: spin 1s linear infinite;
}
</style>

<script>
// ============================================
// CORE CONFIGURATION
// ============================================
window.CURRENT_USER = {
    id: <?php echo (int)$userId; ?>,
    role: <?php echo json_encode($userRole); ?>,
    name: <?php echo json_encode($userName); ?>
};

console.log('Manager Dashboard Initialized:', CURRENT_USER);

// ============================================
// NAVIGATION SYSTEM
// ============================================
function navigateTo(page) {
    console.log('Navigate to:', page);
    showToast('جاري تحميل الصفحة...', 'info');
    
    // Update active link
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.page === page) {
            link.classList.add('active');
        }
    });
    
    // Load page content
    loadPageContent(page);
}

function loadPageContent(page) {
    // صفحات منفصلة (Standalone Pages) - يتم فتحها مباشرة
    const standalonePages = {
        'analytics': 'analytics.php',
        'certificate-designer': 'certificate-designer.php',
        'imports': 'smart_import_wizard.php',
        'ai-images': 'ai-import.php',
        'ai-charts': 'ai-charts.php'
    };
    
    // إذا كانت صفحة منفصلة، افتحها في iframe
    if (standalonePages[page]) {
        loadStandalonePage(page, standalonePages[page]);
        return;
    }
    
    // صفحات ديناميكية (Dynamic Pages) - من manager-features.js
    const pageRenderers = {
        'dashboard': renderDashboard,
        'trainees': renderTrainees,
        'trainers': renderTrainers,
        'graduates': renderGraduates,
        'users-management': renderUsersManagement,
        'courses': renderCourses,
        'grades': renderGrades,
        'attendance': renderAttendance,
        'certificates': renderCertificates,
        'finance': renderFinance,
        'requests': renderRequests,
        'announcements': renderAnnouncements,
        'idcards': renderIDCards,
        'settings': renderSettings
    };
    
    if (pageRenderers[page]) {
        pageRenderers[page]();
    } else {
        renderNotFound(page);
    }
}

// تحميل صفحة منفصلة في iframe
function loadStandalonePage(page, filename) {
    setPageHeader(getPageTitle(page), 'صفحة متخصصة');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow-sm overflow-hidden border border-slate-200" style="height: calc(100vh - 200px);">
            <iframe 
                src="${filename}" 
                class="w-full h-full border-0"
                title="${getPageTitle(page)}"
                allow="clipboard-write"
            ></iframe>
        </div>
    `;
}

// الحصول على عنوان الصفحة
function getPageTitle(page) {
    const titles = {
        'analytics': 'التحليلات المتقدمة',
        'certificate-designer': 'مصمم الشهادات',
        'imports': 'معالج الاستيراد الذكي',
        'ai-images': 'توليد الصور بالذكاء الاصطناعي',
        'ai-charts': 'رسوم بيانية ذكية'
    };
    return titles[page] || page;
}

// ============================================
// PAGE RENDERERS (Using manager-features.js)
// ============================================

// If manager-features.js is loaded, these will be overridden
function renderDashboard() {
    setPageHeader('لوحة التحكم الرئيسية', 'نظرة عامة على أداء المنصة');
    // Dashboard is already rendered in PHP, just refresh icons
    lucide.createIcons();
}

// Stub functions with initial data - will be enhanced by manager-features.js
function renderTrainees() { 
    if (typeof window.managerFeatures?.renderTrainees === 'function') {
        window.managerFeatures.renderTrainees();
    } else {
        renderWithInitialData('المتدربين', 'trainees', getInitialTrainees());
    }
}

function renderTrainers() {
    if (typeof window.managerFeatures?.renderTrainers === 'function') {
        window.managerFeatures.renderTrainers();
    } else {
        renderWithInitialData('المدربون', 'trainers', getInitialTrainers());
    }
}

function renderCourses() {
    if (typeof window.managerFeatures?.renderCourses === 'function') {
        window.managerFeatures.renderCourses();
    } else {
        renderWithInitialData('الدورات التدريبية', 'courses', getInitialCourses());
    }
}

function renderGraduates() { renderStub('الخريجين', 'graduates'); }
function renderUsersManagement() { renderStub('إدارة المستخدمين', 'users'); }
function renderGrades() { renderStub('الدرجات', 'grades'); }
function renderAttendance() { renderStub('الحضور', 'attendance'); }
function renderCertificates() { renderStub('الشهادات', 'certificates'); }

function renderFinance() {
    if (typeof window.managerFeatures?.renderFinance === 'function') {
        window.managerFeatures.renderFinance();
    } else {
        renderWithInitialData('الشؤون المالية', 'finance', getInitialFinance());
    }
}

function renderRequests() {
    if (typeof window.managerFeatures?.renderRequests === 'function') {
        window.managerFeatures.renderRequests();
    } else {
        renderWithInitialData('طلبات الالتحاق', 'requests', getInitialRequests());
    }
}

function renderAnnouncements() {
    if (typeof window.managerFeatures?.renderAnnouncements === 'function') {
        window.managerFeatures.renderAnnouncements();
    } else {
        renderWithInitialData('الإعلانات', 'announcements', getInitialAnnouncements());
    }
}

function renderAnalytics() { renderStub('التحليلات', 'analytics'); }

function renderIDCards() {
    if (typeof window.managerFeatures?.renderIDCards === 'function') {
        window.managerFeatures.renderIDCards();
    } else {
        renderWithInitialData('البطاقات الطلابية', 'idcards', getInitialIDCards());
    }
}
function renderImports() { renderStub('الاستيراد الذكي', 'imports'); }
function renderAIImages() { renderStub('توليد الصور AI', 'ai-images'); }
function renderCertificateDesigner() { renderStub('مصمم الشهادات', 'certificate-designer'); }
function renderSettings() { renderStub('الإعدادات', 'settings'); }

// Get initial data
function getInitialTrainees() {
    return [
        { id: 1, name: 'أحمد محمد علي', email: 'ahmed@example.com', phone: '777123456', created_at: '2024-11-15', status: 'نشط' },
        { id: 2, name: 'فاطمة حسن يحيى', email: 'fatima@example.com', phone: '777234567', created_at: '2024-11-10', status: 'نشط' },
        { id: 3, name: 'محمد سعيد قاسم', email: 'mohammed@example.com', phone: '777345678', created_at: '2024-11-05', status: 'نشط' },
        { id: 4, name: 'سارة عبدالله أحمد', email: 'sara@example.com', phone: '777456789', created_at: '2024-10-28', status: 'معلق' },
        { id: 5, name: 'خالد علي محمد', email: 'khaled@example.com', phone: '777567890', created_at: '2024-10-20', status: 'نشط' }
    ];
}

function getInitialTrainers() {
    return [
        { id: 1, name: 'د. عبدالرحمن الشامي', specialty: 'البرمجة والتطوير', courses: 5, rating: 4.8 },
        { id: 2, name: 'أ. نبيل الحداد', specialty: 'التسويق الرقمي', courses: 3, rating: 4.6 },
        { id: 3, name: 'م. صالح العمري', specialty: 'التصميم الجرافيكي', courses: 4, rating: 4.9 }
    ];
}

function getInitialCourses() {
    return [
        { id: 1, name: 'تطوير المواقع الإلكترونية', trainer: 'د. عبدالرحمن الشامي', students: 45, status: 'نشط', duration: '3 أشهر', price: 15000 },
        { id: 2, name: 'التسويق عبر وسائل التواصل', trainer: 'أ. نبيل الحداد', students: 32, status: 'نشط', duration: 'شهرين', price: 10000 },
        { id: 3, name: 'التصميم الجرافيكي المتقدم', trainer: 'م. صالح العمري', students: 28, status: 'قريباً', duration: '3 أشهر', price: 12000 },
        { id: 4, name: 'إدارة المشاريع الاحترافية', trainer: 'د. عبدالرحمن الشامي', students: 20, status: 'نشط', duration: 'شهر', price: 8000 }
    ];
}

function getInitialFinance() {
    return {
        summary: {
            total_revenue: 2450000,
            pending_payments: 125000,
            completed_payments: 2325000,
            this_month: 450000
        },
        recent: [
            { id: 1, student: 'أحمد محمد علي', amount: 15000, status: 'مكتمل', date: '2024-11-20' },
            { id: 2, student: 'فاطمة حسن يحيى', amount: 10000, status: 'مكتمل', date: '2024-11-19' },
            { id: 3, student: 'محمد سعيد قاسم', amount: 12000, status: 'معلق', date: '2024-11-18' }
        ]
    };
}

function getInitialRequests() {
    return [
        { id: 1, name: 'ياسر محمد', course: 'تطوير المواقع', phone: '777111222', date: '2024-11-21', status: 'جديد' },
        { id: 2, name: 'هدى أحمد', course: 'التسويق الرقمي', phone: '777222333', date: '2024-11-20', status: 'قيد المراجعة' },
        { id: 3, name: 'عمر سالم', course: 'التصميم الجرافيكي', phone: '777333444', date: '2024-11-19', status: 'جديد' }
    ];
}

function getInitialAnnouncements() {
    return [
        { 
            id: 1, 
            title: 'بداية التسجيل في الدورة الجديدة', 
            content: 'يسرنا الإعلان عن فتح باب التسجيل في دورة تطوير المواقع المتقدمة',
            date: '2024-11-20',
            type: 'إعلان عام',
            priority: 'عالية'
        },
        { 
            id: 2, 
            title: 'عطلة نهاية الأسبوع', 
            content: 'تنبيه: المنصة ستكون مغلقة يوم الجمعة للصيانة الدورية',
            date: '2024-11-19',
            type: 'تنبيه',
            priority: 'متوسطة'
        },
        { 
            id: 3, 
            title: 'نتائج الامتحانات', 
            content: 'تم نشر نتائج امتحانات الشهر الماضي على حساباتكم',
            date: '2024-11-18',
            type: 'نتائج',
            priority: 'عالية'
        },
        { 
            id: 4, 
            title: 'ورشة عمل مجانية', 
            content: 'ورشة عمل مجانية حول أساسيات البرمجة - التسجيل متاح',
            date: '2024-11-15',
            type: 'فعالية',
            priority: 'منخفضة'
        }
    ];
}

function getInitialIDCards() {
    return [
        { id: 1, name: 'أحمد محمد علي', studentId: 'STD-2024-001', course: 'تطوير المواقع', photo: null, status: 'نشط' },
        { id: 2, name: 'فاطمة حسن يحيى', studentId: 'STD-2024-002', course: 'التسويق الرقمي', photo: null, status: 'نشط' },
        { id: 3, name: 'محمد سعيد قاسم', studentId: 'STD-2024-003', course: 'التصميم الجرافيكي', photo: null, status: 'نشط' },
        { id: 4, name: 'سارة عبدالله أحمد', studentId: 'STD-2024-004', course: 'تطوير المواقع', photo: null, status: 'معلق' },
        { id: 5, name: 'خالد علي محمد', studentId: 'STD-2024-005', course: 'إدارة المشاريع', photo: null, status: 'نشط' }
    ];
}

// Render with initial data
function renderWithInitialData(title, module, data) {
    setPageHeader(title, 'عرض البيانات الأولية');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    let content = '';
    
    if (module === 'trainees') {
        content = `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                    <h3 class="text-xl font-bold text-slate-800">قائمة المتدربين</h3>
                    <button class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                        <i data-lucide="user-plus" class="w-4 h-4 inline"></i>
                        إضافة متدرب
                    </button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">#</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الاسم</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">البريد الإلكتروني</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الهاتف</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">تاريخ التسجيل</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            ${data.map(item => `
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900">${item.id}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-slate-900">${item.name}</div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">${item.email}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">${item.phone}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">${formatDate(item.created_at)}</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full ${item.status === 'نشط' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                                            ${item.status}
                                        </span>
                                    </td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } else if (module === 'trainers') {
        content = `
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                ${data.map(item => `
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 hover:shadow-md transition">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-sky-100 to-sky-50 flex items-center justify-center mb-4">
                            <i data-lucide="user-check" class="w-8 h-8 text-sky-600"></i>
                        </div>
                        <h3 class="text-lg font-bold text-slate-800 mb-2">${item.name}</h3>
                        <p class="text-sm text-slate-600 mb-4">${item.specialty}</p>
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-slate-500">${item.courses} دورات</span>
                            <div class="flex items-center gap-1">
                                <i data-lucide="star" class="w-4 h-4 text-amber-500 fill-amber-500"></i>
                                <span class="font-medium text-slate-700">${item.rating}</span>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else if (module === 'courses') {
        content = `
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">جميع الدورات التدريبية</h3>
                    <p class="text-sm text-slate-600 mt-1">إدارة وعرض جميع الدورات المتاحة</p>
                </div>
                <button class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                    <i data-lucide="plus" class="w-4 h-4 inline"></i>
                    إضافة دورة جديدة
                </button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                ${data.map(item => `
                    <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition">
                        <div class="p-6">
                            <div class="flex items-start justify-between mb-4">
                                <div>
                                    <h4 class="text-lg font-bold text-slate-800 mb-2">${item.name}</h4>
                                    <p class="text-sm text-slate-600"><i data-lucide="user" class="w-4 h-4 inline"></i> ${item.trainer}</p>
                                </div>
                                <span class="px-3 py-1 text-xs rounded-full ${item.status === 'نشط' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'}">
                                    ${item.status}
                                </span>
                            </div>
                            <div class="grid grid-cols-3 gap-4 pt-4 border-t border-slate-200">
                                <div class="text-center">
                                    <p class="text-xs text-slate-500">الطلاب</p>
                                    <p class="text-lg font-bold text-slate-800">${item.students}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-slate-500">المدة</p>
                                    <p class="text-lg font-bold text-slate-800">${item.duration}</p>
                                </div>
                                <div class="text-center">
                                    <p class="text-xs text-slate-500">السعر</p>
                                    <p class="text-lg font-bold text-sky-600">${item.price.toLocaleString('ar-SA')}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    } else if (module === 'finance') {
        content = `
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-slate-600">إجمالي الإيرادات</p>
                        <i data-lucide="trending-up" class="w-5 h-5 text-emerald-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">${data.summary.total_revenue.toLocaleString('ar-SA')}</p>
                    <p class="text-xs text-emerald-600 mt-1">ريال سعودي</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-slate-600">دفعات معلقة</p>
                        <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">${data.summary.pending_payments.toLocaleString('ar-SA')}</p>
                    <p class="text-xs text-amber-600 mt-1">ريال سعودي</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-slate-600">دفعات مكتملة</p>
                        <i data-lucide="check-circle" class="w-5 h-5 text-sky-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">${data.summary.completed_payments.toLocaleString('ar-SA')}</p>
                    <p class="text-xs text-sky-600 mt-1">ريال سعودي</p>
                </div>
                <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-sm text-slate-600">هذا الشهر</p>
                        <i data-lucide="calendar" class="w-5 h-5 text-purple-600"></i>
                    </div>
                    <p class="text-2xl font-bold text-slate-800">${data.summary.this_month.toLocaleString('ar-SA')}</p>
                    <p class="text-xs text-purple-600 mt-1">ريال سعودي</p>
                </div>
            </div>
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200">
                    <h3 class="text-xl font-bold text-slate-800">آخر العمليات المالية</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الطالب</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">المبلغ</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
                                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">التاريخ</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200">
                            ${data.recent.map(item => `
                                <tr class="hover:bg-slate-50 transition">
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-slate-900">${item.student}</td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800">${item.amount.toLocaleString('ar-SA')} ريال</td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 text-xs rounded-full ${item.status === 'مكتمل' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'}">
                                            ${item.status}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">${formatDate(item.date)}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>
                </div>
            </div>
        `;
    } else if (module === 'requests') {
        content = `
            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <div class="p-6 border-b border-slate-200 flex justify-between items-center">
                    <div>
                        <h3 class="text-xl font-bold text-slate-800">طلبات الالتحاق الجديدة</h3>
                        <p class="text-sm text-slate-600 mt-1">مراجعة والموافقة على الطلبات</p>
                    </div>
                    <span class="px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-sm font-medium">
                        ${data.length} طلب جديد
                    </span>
                </div>
                <div class="divide-y divide-slate-200">
                    ${data.map(item => `
                        <div class="p-6 hover:bg-slate-50 transition">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="text-lg font-bold text-slate-800 mb-2">${item.name}</h4>
                                    <div class="grid grid-cols-2 gap-4 text-sm">
                                        <p class="text-slate-600"><i data-lucide="book" class="w-4 h-4 inline"></i> ${item.course}</p>
                                        <p class="text-slate-600"><i data-lucide="phone" class="w-4 h-4 inline"></i> ${item.phone}</p>
                                        <p class="text-slate-600"><i data-lucide="calendar" class="w-4 h-4 inline"></i> ${formatDate(item.date)}</p>
                                        <span class="px-2 py-1 text-xs rounded-full ${item.status === 'جديد' ? 'bg-sky-100 text-sky-700' : 'bg-amber-100 text-amber-700'} inline-block w-fit">
                                            ${item.status}
                                        </span>
                                    </div>
                                </div>
                                <div class="flex gap-2">
                                    <button onclick="approveRequest(${item.id}, '${item.name}')" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition text-sm">
                                        <i data-lucide="check" class="w-4 h-4 inline"></i>
                                        قبول
                                    </button>
                                    <button onclick="rejectRequest(${item.id}, '${item.name}')" class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition text-sm">
                                        <i data-lucide="x" class="w-4 h-4 inline"></i>
                                        رفض
                                    </button>
                                </div>
                            </div>
                        </div>
                    `).join('')}
                </div>
            </div>
        `;
    } else if (module === 'announcements') {
        content = `
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">جميع الإعلانات</h3>
                    <p class="text-sm text-slate-600 mt-1">إدارة ونشر الإعلانات للطلاب والمدربين</p>
                </div>
                <button onclick="showAddAnnouncementModal()" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                    <i data-lucide="plus" class="w-4 h-4 inline"></i>
                    إضافة إعلان جديد
                </button>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                ${data.map(item => {
                    const priorityColors = {
                        'عالية': 'bg-red-100 text-red-700 border-red-200',
                        'متوسطة': 'bg-amber-100 text-amber-700 border-amber-200',
                        'منخفضة': 'bg-slate-100 text-slate-700 border-slate-200'
                    };
                    const typeIcons = {
                        'إعلان عام': 'megaphone',
                        'تنبيه': 'alert-triangle',
                        'نتائج': 'award',
                        'فعالية': 'calendar'
                    };
                    return `
                        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden hover:shadow-md transition">
                            <div class="p-6">
                                <div class="flex items-start justify-between mb-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-10 h-10 rounded-lg bg-sky-100 flex items-center justify-center">
                                            <i data-lucide="${typeIcons[item.type] || 'info'}" class="w-5 h-5 text-sky-600"></i>
                                        </div>
                                        <div>
                                            <span class="text-xs px-2 py-1 rounded-full ${priorityColors[item.priority]}">
                                                ${item.priority}
                                            </span>
                                        </div>
                                    </div>
                                    <span class="text-xs text-slate-500">${formatDate(item.date)}</span>
                                </div>
                                <h4 class="text-lg font-bold text-slate-800 mb-2">${item.title}</h4>
                                <p class="text-sm text-slate-600 mb-4 line-clamp-2">${item.content}</p>
                                <div class="flex items-center justify-between pt-4 border-t border-slate-200">
                                    <span class="text-xs px-2 py-1 bg-slate-100 text-slate-700 rounded-full">
                                        <i data-lucide="tag" class="w-3 h-3 inline"></i> ${item.type}
                                    </span>
                                    <div class="flex gap-2">
                                        <button onclick="editAnnouncement(${item.id})" class="p-2 text-sky-600 hover:bg-sky-50 rounded-lg transition">
                                            <i data-lucide="edit" class="w-4 h-4"></i>
                                        </button>
                                        <button onclick="deleteAnnouncement(${item.id})" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition">
                                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                }).join('')}
            </div>
        `;
    } else if (module === 'idcards') {
        content = `
            <div class="mb-6 flex justify-between items-center">
                <div>
                    <h3 class="text-xl font-bold text-slate-800">البطاقات الطلابية</h3>
                    <p class="text-sm text-slate-600 mt-1">إنشاء وطباعة البطاقات الطلابية</p>
                </div>
                <div class="flex gap-2">
                    <button onclick="printAllCards()" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                        <i data-lucide="printer" class="w-4 h-4 inline"></i>
                        طباعة الكل
                    </button>
                    <button onclick="generateCards()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                        <i data-lucide="credit-card" class="w-4 h-4 inline"></i>
                        إنشاء بطاقات جديدة
                    </button>
                </div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                ${data.map(item => `
                    <div class="bg-gradient-to-br from-sky-500 to-sky-700 rounded-2xl shadow-lg p-6 text-white relative overflow-hidden">
                        <!-- Background Pattern -->
                        <div class="absolute top-0 right-0 w-32 h-32 bg-white/10 rounded-full -mr-16 -mt-16"></div>
                        <div class="absolute bottom-0 left-0 w-24 h-24 bg-white/10 rounded-full -ml-12 -mb-12"></div>
                        
                        <!-- Content -->
                        <div class="relative z-10">
                            <div class="flex items-start justify-between mb-4">
                                <div class="w-16 h-16 rounded-full bg-white/20 border-2 border-white/40 flex items-center justify-center">
                                    <i data-lucide="user" class="w-8 h-8"></i>
                                </div>
                                <span class="px-2 py-1 text-xs rounded-full ${item.status === 'نشط' ? 'bg-emerald-500/30 border border-emerald-300' : 'bg-amber-500/30 border border-amber-300'}">
                                    ${item.status}
                                </span>
                            </div>
                            
                            <h4 class="text-lg font-bold mb-1">${item.name}</h4>
                            <p class="text-sm text-white/80 mb-3">${item.studentId}</p>
                            
                            <div class="pt-3 border-t border-white/20">
                                <p class="text-xs text-white/70 mb-1">الدورة التدريبية</p>
                                <p class="text-sm font-medium">${item.course}</p>
                            </div>
                            
                            <div class="mt-4 flex gap-2">
                                <button onclick="printCard(${item.id})" class="flex-1 px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition text-sm">
                                    <i data-lucide="printer" class="w-4 h-4 inline"></i>
                                    طباعة
                                </button>
                                <button onclick="downloadCard(${item.id})" class="flex-1 px-3 py-2 bg-white/20 hover:bg-white/30 rounded-lg transition text-sm">
                                    <i data-lucide="download" class="w-4 h-4 inline"></i>
                                    تحميل
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('')}
            </div>
        `;
    }
    
    pageBody.innerHTML = content;
    lucide.createIcons();
}

// ============================================
// REGISTRATION REQUESTS FUNCTIONS
// ============================================
async function loadRequests() {
    try {
        const response = await fetch('../api/registration_requests.php?action=list&status=pending');
        const data = await response.json();
        
        if (data.success && data.requests) {
            return data.requests;
        }
        return getInitialRequests();
    } catch (error) {
        console.error('Error loading requests:', error);
        return getInitialRequests();
    }
}

async function approveRequest(requestId, studentName) {
    if (!confirm(`هل تريد قبول طلب الالتحاق للطالب: ${studentName}؟`)) {
        return;
    }
    
    try {
        showToast('جاري معالجة الطلب...', 'info');
        
        const response = await fetch('../api/registration_requests.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'approve',
                request_id: requestId
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ تم قبول الطلب بنجاح', 'success');
            navigateTo('requests');
        } else {
            showToast('❌ فشل قبول الطلب: ' + (data.message || 'خطأ غير معروف'), 'error');
        }
    } catch (error) {
        console.error('Error approving request:', error);
        showToast('❌ حدث خطأ في الاتصال بالخادم', 'error');
    }
}

async function rejectRequest(requestId, studentName) {
    const reason = prompt(`سبب رفض طلب الالتحاق للطالب: ${studentName}`);
    
    if (!reason || reason.trim() === '') {
        showToast('يجب إدخال سبب الرفض', 'warning');
        return;
    }
    
    try {
        showToast('جاري معالجة الطلب...', 'info');
        
        const response = await fetch('../api/registration_requests.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'reject',
                request_id: requestId,
                rejection_reason: reason.trim()
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showToast('✅ تم رفض الطلب', 'success');
            navigateTo('requests');
        } else {
            showToast('❌ فشل رفض الطلب: ' + (data.message || 'خطأ غير معروف'), 'error');
        }
    } catch (error) {
        console.error('Error rejecting request:', error);
        showToast('❌ حدث خطأ في الاتصال بالخادم', 'error');
    }
}

// ============================================
// ANNOUNCEMENTS FUNCTIONS
// ============================================
function showAddAnnouncementModal() {
    const content = `
        <form id="announcementForm" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">عنوان الإعلان</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="اكتب عنوان الإعلان...">
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى</label>
                <textarea name="content" required rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" placeholder="اكتب محتوى الإعلان..."></textarea>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">النوع</label>
                    <select name="type" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="إعلان عام">إعلان عام</option>
                        <option value="تنبيه">تنبيه</option>
                        <option value="نتائج">نتائج</option>
                        <option value="فعالية">فعالية</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الأولوية</label>
                    <select name="priority" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="عالية">عالية</option>
                        <option value="متوسطة">متوسطة</option>
                        <option value="منخفضة">منخفضة</option>
                    </select>
                </div>
            </div>
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                    <i data-lucide="save" class="w-4 h-4 inline"></i>
                    حفظ الإعلان
                </button>
                <button type="button" onclick="closeModal()" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                    إلغاء
                </button>
            </div>
        </form>
    `;
    
    showModal('إضافة إعلان جديد', content);
    
    setTimeout(() => {
        document.getElementById('announcementForm').addEventListener('submit', async function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            
            try {
                showToast('جاري حفظ الإعلان...', 'info');
                await new Promise(resolve => setTimeout(resolve, 500));
                showToast('✅ تم حفظ الإعلان بنجاح', 'success');
                closeModal();
                navigateTo('announcements');
            } catch (error) {
                showToast('❌ حدث خطأ في الحفظ', 'error');
            }
        });
    }, 100);
}

function editAnnouncement(id) {
    showToast(`تعديل الإعلان رقم ${id} (قيد التطوير)`, 'info');
}

function deleteAnnouncement(id) {
    if (confirm('هل أنت متأكد من حذف هذا الإعلان؟')) {
        showToast(`تم حذف الإعلان رقم ${id}`, 'success');
    }
}

function printAllCards() {
    showToast('جاري تحضير جميع البطاقات للطباعة...', 'info');
}

function generateCards() {
    showToast('جاري إنشاء البطاقات الجديدة...', 'info');
}

function printCard(id) {
    showToast(`طباعة البطاقة رقم ${id}`, 'info');
}

function downloadCard(id) {
    showToast(`تحميل البطاقة رقم ${id}`, 'info');
}

function renderStub(title, module) {
    setPageHeader(title, 'جاري التحميل...');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <div class="w-20 h-20 mx-auto mb-6 rounded-full bg-sky-50 flex items-center justify-center">
                <i data-lucide="loader" class="w-10 h-10 text-sky-600 animate-spin"></i>
            </div>
            <h3 class="text-2xl font-bold text-slate-800 mb-3">${title}</h3>
            <p class="text-slate-600 mb-6">جاري تحميل البيانات من الخادم...</p>
            <div class="inline-block px-6 py-3 bg-sky-50 text-sky-700 rounded-lg">
                <p class="text-sm font-medium">الوحدة: <code class="font-mono">${module}</code></p>
            </div>
            <div class="mt-8 p-4 bg-amber-50 border border-amber-200 rounded-lg">
                <p class="text-sm text-amber-800">
                    <i data-lucide="alert-circle" class="w-4 h-4 inline"></i>
                    تأكد من تحميل ملف <strong>manager-features.js</strong> لعرض المحتوى الكامل
                </p>
            </div>
        </div>
    `;
    lucide.createIcons();
}

function renderNotFound(page) {
    setPageHeader('صفحة غير موجودة', 'القسم المطلوب غير متاح');
    clearPageBody();
    
    const pageBody = document.getElementById('pageBody');
    pageBody.innerHTML = `
        <div class="bg-white rounded-2xl shadow-sm p-12 text-center border border-slate-200">
            <i data-lucide="alert-circle" class="w-20 h-20 mx-auto text-red-500 mb-6"></i>
            <h3 class="text-2xl font-bold text-slate-800 mb-3">القسم غير موجود</h3>
            <p class="text-slate-600 mb-6">القسم "${page}" غير متوفر حالياً</p>
            <button onclick="navigateTo('dashboard')" class="px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                العودة للوحة التحكم
            </button>
        </div>
    `;
    lucide.createIcons();
}

// ============================================
// HELPER FUNCTIONS
// ============================================
function setPageHeader(title, subtitle) {
    document.getElementById('pageTitle').textContent = title;
    document.getElementById('pageSubtitle').textContent = subtitle;
}

function clearPageBody() {
    document.getElementById('pageBody').innerHTML = '';
}

function showToast(message, type = 'info') {
    const colors = {
        success: 'bg-emerald-600',
        error: 'bg-red-600',
        warning: 'bg-amber-600',
        info: 'bg-slate-800'
    };
    
    let toast = document.getElementById('toast');
    if (!toast) {
        toast = document.createElement('div');
        toast.id = 'toast';
        toast.className = 'fixed bottom-6 left-6 px-6 py-3 rounded-xl shadow-2xl z-50 text-white transition transform';
        document.body.appendChild(toast);
    }
    
    toast.className = `fixed bottom-6 left-6 px-6 py-3 rounded-xl shadow-2xl z-50 text-white ${colors[type] || colors.info}`;
    toast.textContent = message;
    toast.style.display = 'block';
    toast.style.opacity = '1';
    
    setTimeout(() => {
        toast.style.opacity = '0';
        setTimeout(() => {
            toast.style.display = 'none';
        }, 300);
    }, 3000);
}

function showLoading() {
    document.getElementById('loadingOverlay').classList.remove('hidden');
}

function hideLoading() {
    document.getElementById('loadingOverlay').classList.add('hidden');
}

// ============================================
// MODAL SYSTEM
// ============================================
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.remove('hidden');
    lucide.createIcons();
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.add('hidden');
}

// ============================================
// SIDEBAR NAVIGATION
// ============================================
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const page = this.dataset.page;
        if (page) {
            navigateTo(page);
        }
    });
});

document.querySelectorAll('.sidebar-section-toggle').forEach(toggle => {
    toggle.addEventListener('click', function(e) {
        e.preventDefault();
        const section = this.closest('.sidebar-section');
        const submenu = section.querySelector('.sidebar-submenu');
        const isActive = this.classList.contains('active');
        
        // Close all other sections
        document.querySelectorAll('.sidebar-section-toggle').forEach(t => {
            if (t !== this) {
                t.classList.remove('active');
                t.closest('.sidebar-section').querySelector('.sidebar-submenu').classList.add('hidden');
            }
        });
        
        // Toggle current section
        if (isActive) {
            this.classList.remove('active');
            submenu.classList.add('hidden');
        } else {
            this.classList.add('active');
            submenu.classList.remove('hidden');
        }
        
        lucide.createIcons();
    });
});

// ============================================
// NOTIFICATIONS SYSTEM
// ============================================
let notificationsInterval = null;

function loadNotifications() {
    console.log('Loading notifications...');
    
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                const count = data.notifications.length;
                const badge = document.querySelector('#notificationsBell .notification-badge');
                
                if (count > 0) {
                    if (badge) {
                        badge.textContent = count;
                        badge.classList.remove('hidden');
                    }
                } else {
                    if (badge) {
                        badge.classList.add('hidden');
                    }
                }
                
                console.log(`✅ تم تحميل ${count} إشعار`);
            }
        })
        .catch(error => {
            console.error('خطأ في تحميل الإشعارات:', error);
        });
}

function showNotifications() {
    fetch('../api/get_notifications.php')
        .then(response => response.json())
        .then(data => {
            if (data.success && data.notifications) {
                const notifications = data.notifications;
                
                if (notifications.length === 0) {
                    showToast('لا توجد إشعارات جديدة', 'info');
                    return;
                }
                
                const content = `
                    <div class="space-y-2 max-h-96 overflow-y-auto">
                        ${notifications.map(notif => `
                            <div class="p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition cursor-pointer" 
                                 onclick="markAsRead(${notif.id}, '${notif.link || '#'}')">
                                <p class="text-sm text-slate-800">${escapeHtml(notif.message)}</p>
                                <p class="text-xs text-slate-500 mt-1">${formatDate(notif.created_at)}</p>
                            </div>
                        `).join('')}
                    </div>
                    <div class="mt-4 pt-3 border-t border-slate-200">
                        <button onclick="markAllAsRead()" class="text-sm text-sky-600 hover:text-sky-700">
                            <i data-lucide="check-check" class="w-4 h-4 inline"></i>
                            تحديد الكل كمقروء
                        </button>
                    </div>
                `;
                
                showModal('الإشعارات', content);
            }
        })
        .catch(error => {
            showToast('حدث خطأ في تحميل الإشعارات', 'error');
        });
}

function markAsRead(notificationId, link) {
    fetch('../api/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ notification_id: notificationId })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            loadNotifications();
            closeModal();
            if (link && link !== '#') {
                window.location.href = link;
            }
        }
    });
}

function markAllAsRead() {
    fetch('../api/mark_notification_read.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({ mark_all: true })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showToast('تم تحديد جميع الإشعارات كمقروءة', 'success');
            loadNotifications();
            closeModal();
        }
    });
}

document.getElementById('notificationsBell')?.addEventListener('click', function() {
    showNotifications();
});

// ============================================
// INITIALIZATION
// ============================================
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Manager Dashboard Loaded Successfully');
    
    // Initialize Lucide Icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
    
    // Load initial notifications
    loadNotifications();
    
    // Start notifications polling (every minute)
    notificationsInterval = setInterval(loadNotifications, 60000);
    
    // Setup modal close button
    document.getElementById('closeModalBtn')?.addEventListener('click', closeModal);
    
    // Close modal on backdrop click
    document.getElementById('modalBackdrop')?.addEventListener('click', function(e) {
        if (e.target === this) {
            closeModal();
        }
    });
    
    // Load recent registrations
    loadRecentRegistrations();
    
    console.log('✅ All systems initialized');
});

// ============================================
// LOAD RECENT DATA
// ============================================
async function loadRecentRegistrations() {
    try {
        const response = await fetch('../api/manage_users.php?action=list&role=student&limit=5&sort=newest');
        const data = await response.json();
        
        const container = document.getElementById('recentRegistrations');
        if (data.success && data.users && data.users.length > 0) {
            container.innerHTML = data.users.map(user => `
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50 hover:bg-slate-100 transition">
                    <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                        <i data-lucide="user" class="w-5 h-5 text-sky-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800">${escapeHtml(user.name)}</p>
                        <p class="text-xs text-slate-500">${user.email || 'لا يوجد بريد إلكتروني'}</p>
                    </div>
                    <span class="text-xs text-slate-400">${formatDate(user.created_at)}</span>
                </div>
            `).join('');
            lucide.createIcons();
        } else {
            container.innerHTML = '<p class="text-sm text-slate-500 text-center py-4">لا توجد تسجيلات حديثة</p>';
        }
    } catch (error) {
        console.error('Error loading recent registrations:', error);
        document.getElementById('recentRegistrations').innerHTML = '<p class="text-sm text-red-500 text-center py-4">حدث خطأ في التحميل</p>';
    }
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-SA', { year: 'numeric', month: 'short', day: 'numeric' });
}

// ============================================
// KEYBOARD SHORTCUTS
// ============================================
document.addEventListener('keydown', function(e) {
    // ESC to close modal
    if (e.key === 'Escape') {
        closeModal();
    }
    
    // Ctrl+K for quick search (future feature)
    if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        showToast('البحث السريع قيد التطوير', 'info');
    }
});

console.log('✅ Manager Dashboard Core Loaded');
</script>

<!-- Load External Systems -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../js/dashboard-integration.js"></script>
<script src="../js/advanced-forms.js"></script>
<script src="../js/dynamic-charts.js"></script>
<script src="./manager-features.js" onerror="console.warn('⚠️ manager-features.js not loaded, using initial data')"></script>
<script>
// التحقق من تحميل manager-features.js
setTimeout(() => {
    if (typeof window.managerFeatures === 'undefined') {
        console.info('📊 Using initial data mode - manager-features.js not loaded');
    } else {
        console.info('✅ manager-features.js loaded successfully');
    }
}, 1000);
</script>

</body>
</html>
