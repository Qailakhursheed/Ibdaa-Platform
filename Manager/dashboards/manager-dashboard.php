<?php
/**
 * Manager Dashboard - Main Controller (PHP Only - No Complex JavaScript)
 * لوحة تحكم المدير العام - بدون JavaScript معقد
 * 
 * @version 4.0 - Simple PHP Edition
 * @author Ibdaa Platform Team
 */

require_once __DIR__ . '/shared-header.php';

// التحقق من الصلاحية - المدير فقط
if ($userRole !== 'manager') {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// Initialize Manager Helper
require_once __DIR__ . '/../includes/manager_helper.php';
$managerHelper = new ManagerHelper($conn, $userId);

// الحصول على الصفحة المطلوبة
$current_page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';

// تحديد الصفحات المتاحة وملفاتها
$pages = [
    'dashboard' => ['title' => 'لوحة التحكم', 'file' => 'dashboard.php', 'icon' => 'layout-dashboard'],
    
    // إدارة المستخدمين
    'students' => ['title' => 'المتدربون', 'file' => 'students.php', 'icon' => 'user'],
    'trainers' => ['title' => 'المدربون', 'file' => 'trainers.php', 'icon' => 'user-check'],
    'graduates' => ['title' => 'الخريجون', 'file' => 'graduates.php', 'icon' => 'award'],
    'users' => ['title' => 'إدارة المستخدمين', 'file' => 'users.php', 'icon' => 'users'],
    
    // الدورات التدريبية
    'courses' => ['title' => 'الدورات', 'file' => 'courses.php', 'icon' => 'book-open'],
    'grades' => ['title' => 'الدرجات', 'file' => 'grades.php', 'icon' => 'graduation-cap'],
    'attendance' => ['title' => 'الحضور', 'file' => 'attendance.php', 'icon' => 'clipboard-check'],
    'certificates' => ['title' => 'الشهادات', 'file' => 'certificates.php', 'icon' => 'award'],
    
    // الشؤون المالية والطلبات
    'finance' => ['title' => 'الشؤون المالية', 'file' => 'finance.php', 'icon' => 'wallet'],
    'requests' => ['title' => 'طلبات الالتحاق', 'file' => 'requests.php', 'icon' => 'inbox'],
    'announcements' => ['title' => 'الإعلانات', 'file' => 'announcements.php', 'icon' => 'megaphone'],
    
    // التقارير والأدوات
    'analytics' => ['title' => 'التحليلات المتقدمة', 'file' => 'analytics.php', 'icon' => 'bar-chart'],
    'smart_import' => ['title' => 'الاستيراد الذكي', 'file' => 'smart_import.php', 'icon' => 'upload-cloud'],
    'materials' => ['title' => 'المواد التدريبية', 'file' => 'materials.php', 'icon' => 'file-text'],
    'reports' => ['title' => 'التقارير', 'file' => 'reports.php', 'icon' => 'file-bar-chart'],
    'evaluations' => ['title' => 'التقييمات', 'file' => 'evaluations.php', 'icon' => 'star'],
    'support' => ['title' => 'الدعم الفني', 'file' => 'support.php', 'icon' => 'life-buoy'],
    
    // الإعدادات
    'settings' => ['title' => 'الإعدادات', 'file' => 'settings.php', 'icon' => 'settings'],
    'chat' => ['title' => 'الرسائل', 'file' => 'chat.php', 'icon' => 'message-circle']
];

// التحقق من صحة الصفحة
if (!isset($pages[$current_page])) {
    $current_page = 'dashboard';
}

$page_info = $pages[$current_page];

// جلب عدد الطلبات المعلقة للإشعارات
$pending_count = 0;
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'registration_requests'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM registration_requests WHERE status = 'pending'");
        if ($result) {
            $pending_count = (int)$result->fetch_assoc()['count'];
        }
    }
} catch (Exception $e) {
    error_log("Pending count error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_info['title']; ?> - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-slate-50">

<div class="flex min-h-screen">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-l border-slate-200 shadow-sm fixed h-screen overflow-y-auto z-50">
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
            <a href="?page=dashboard" class="sidebar-link <?php echo $current_page === 'dashboard' ? 'active' : ''; ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span class="font-medium">لوحة التحكم</span>
            </a>

            <!-- إدارة المستخدمين -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle <?php echo in_array($current_page, ['students', 'trainers', 'graduates', 'users']) ? 'active' : ''; ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="users-2" class="w-5 h-5"></i>
                        <span class="font-medium">إدارة المستخدمين</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform <?php echo in_array($current_page, ['students', 'trainers', 'graduates', 'users']) ? 'rotate-180' : ''; ?>"></i>
                </button>
                <div class="sidebar-submenu <?php echo in_array($current_page, ['students', 'trainers', 'graduates', 'users']) ? '' : 'hidden'; ?>">
                    <a href="?page=students" class="sidebar-sublink <?php echo $current_page === 'students' ? 'active' : ''; ?>">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>المتدربون</span>
                    </a>
                    <a href="?page=trainers" class="sidebar-sublink <?php echo $current_page === 'trainers' ? 'active' : ''; ?>">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        <span>المدربون</span>
                    </a>
                    <a href="?page=graduates" class="sidebar-sublink <?php echo $current_page === 'graduates' ? 'active' : ''; ?>">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>ملف الخريجين</span>
                    </a>
                    <a href="?page=users" class="sidebar-sublink <?php echo $current_page === 'users' ? 'active' : ''; ?>">
                        <i data-lucide="users" class="w-4 h-4"></i>
                        <span>إدارة شاملة</span>
                    </a>
                </div>
            </div>

            <!-- الدورات التدريبية -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle <?php echo in_array($current_page, ['courses', 'grades', 'attendance', 'certificates']) ? 'active' : ''; ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span class="font-medium">الدورات التدريبية</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform <?php echo in_array($current_page, ['courses', 'grades', 'attendance', 'certificates']) ? 'rotate-180' : ''; ?>"></i>
                </button>
                <div class="sidebar-submenu <?php echo in_array($current_page, ['courses', 'grades', 'attendance', 'certificates']) ? '' : 'hidden'; ?>">
                    <a href="?page=courses" class="sidebar-sublink <?php echo $current_page === 'courses' ? 'active' : ''; ?>">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>إدارة الدورات</span>
                    </a>
                    <a href="?page=grades" class="sidebar-sublink <?php echo $current_page === 'grades' ? 'active' : ''; ?>">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span>الدرجات</span>
                    </a>
                    <a href="?page=attendance" class="sidebar-sublink <?php echo $current_page === 'attendance' ? 'active' : ''; ?>">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>الحضور</span>
                    </a>
                    <a href="?page=certificates" class="sidebar-sublink <?php echo $current_page === 'certificates' ? 'active' : ''; ?>">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>الشهادات</span>
                    </a>
                </div>
            </div>

            <!-- الشؤون المالية -->
            <a href="?page=finance" class="sidebar-link <?php echo $current_page === 'finance' ? 'active' : ''; ?>">
                <i data-lucide="wallet" class="w-5 h-5"></i>
                <span class="font-medium">الشؤون المالية</span>
            </a>

            <!-- طلبات وإعلانات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle <?php echo in_array($current_page, ['requests', 'announcements']) ? 'active' : ''; ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="inbox" class="w-5 h-5"></i>
                        <span class="font-medium">الطلبات والإعلانات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform <?php echo in_array($current_page, ['requests', 'announcements']) ? 'rotate-180' : ''; ?>"></i>
                </button>
                <div class="sidebar-submenu <?php echo in_array($current_page, ['requests', 'announcements']) ? '' : 'hidden'; ?>">
                    <a href="?page=requests" class="sidebar-sublink <?php echo $current_page === 'requests' ? 'active' : ''; ?>">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        <span>طلبات الالتحاق</span>
                        <?php if ($pending_count > 0): ?>
                        <span class="mr-auto bg-red-500 text-white text-xs px-2 py-0.5 rounded-full">
                            <?php echo $pending_count; ?>
                        </span>
                        <?php endif; ?>
                    </a>
                    <a href="?page=announcements" class="sidebar-sublink <?php echo $current_page === 'announcements' ? 'active' : ''; ?>">
                        <i data-lucide="megaphone" class="w-4 h-4"></i>
                        <span>الإعلانات</span>
                    </a>
                </div>
            </div>

            <!-- التقارير والأدوات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle <?php echo in_array($current_page, ['analytics', 'idcards', 'imports', 'ai-images', 'ai-charts', 'certificate-designer']) ? 'active' : ''; ?>">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        <span class="font-medium">التقارير والأدوات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform <?php echo in_array($current_page, ['analytics', 'idcards', 'imports', 'ai-images', 'ai-charts', 'certificate-designer']) ? 'rotate-180' : ''; ?>"></i>
                </button>
                <div class="sidebar-submenu <?php echo in_array($current_page, ['analytics', 'idcards', 'imports', 'ai-images', 'ai-charts', 'certificate-designer']) ? '' : 'hidden'; ?>">
                    <a href="?page=analytics" class="sidebar-sublink <?php echo $current_page === 'analytics' ? 'active' : ''; ?>">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>التحليلات</span>
                    </a>
                    <a href="?page=idcards" class="sidebar-sublink <?php echo $current_page === 'idcards' ? 'active' : ''; ?>">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                        <span>البطاقات الطلابية</span>
                    </a>
                    <a href="?page=imports" class="sidebar-sublink <?php echo $current_page === 'imports' ? 'active' : ''; ?>">
                        <i data-lucide="file-up" class="w-4 h-4"></i>
                        <span>الاستيراد الذكي</span>
                    </a>
                    <a href="?page=ai-images" class="sidebar-sublink <?php echo $current_page === 'ai-images' ? 'active' : ''; ?>">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>توليد الصور AI</span>
                    </a>
                    <a href="?page=ai-charts" class="sidebar-sublink <?php echo $current_page === 'ai-charts' ? 'active' : ''; ?>">
                        <i data-lucide="bar-chart-3" class="w-4 h-4"></i>
                        <span>رسوم بيانية AI</span>
                    </a>
                    <a href="?page=certificate-designer" class="sidebar-sublink <?php echo $current_page === 'certificate-designer' ? 'active' : ''; ?>">
                        <i data-lucide="pen-tool" class="w-4 h-4"></i>
                        <span>مصمم الشهادات</span>
                    </a>
                </div>
            </div>

            <!-- الرسائل -->
            <a href="?page=chat" class="sidebar-link <?php echo $current_page === 'chat' ? 'active' : ''; ?>">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span class="font-medium">الرسائل</span>
            </a>

            <!-- الإعدادات -->
            <a href="?page=settings" class="sidebar-link <?php echo $current_page === 'settings' ? 'active' : ''; ?>">
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
                    <!-- Unified Notifications Bell Component -->
                    <?php include __DIR__ . '/components/notifications-bell.php'; ?>
                    
                    <!-- Quick Actions -->
                    <a href="?page=students" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition" title="إضافة متدرب">
                        <i data-lucide="user-plus" class="w-5 h-5"></i>
                    </a>
                    
                    <a href="?page=courses" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition" title="إضافة دورة">
                        <i data-lucide="book-plus" class="w-5 h-5"></i>
                    </a>

                    <a href="?page=chat" class="p-2 rounded-lg border border-slate-200 hover:bg-slate-50 transition" title="الرسائل">
                        <i data-lucide="message-circle" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="text-3xl font-bold text-slate-800 mb-2"><?php echo $page_info['title']; ?></h1>
            </div>

            <!-- Page Body -->
            <div>
                <?php
                // تحميل محتوى الصفحة
                $page_file = __DIR__ . '/manager/' . $page_info['file'];
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo '<div class="bg-white rounded-2xl shadow-sm p-8 border border-slate-200 text-center">';
                    echo '<div class="w-16 h-16 bg-slate-100 rounded-full flex items-center justify-center mx-auto mb-4">';
                    echo '<i data-lucide="construction" class="w-8 h-8 text-slate-400"></i>';
                    echo '</div>';
                    echo '<h3 class="text-xl font-bold text-slate-800 mb-2">الصفحة قيد التطوير</h3>';
                    echo '<p class="text-slate-600">هذه الصفحة ستكون متاحة قريباً</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </div>
    </main>
</div>

<style>
/* Sidebar Styles */
.sidebar-link {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.sidebar-link:hover {
    background-color: #f8fafc;
}

.sidebar-link.active {
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
    color: #0284c7;
    font-weight: 600;
    border-right: 3px solid #0284c7;
}

.sidebar-section-toggle {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.75rem 1rem;
    border-radius: 0.75rem;
    transition: all 0.2s;
}

.sidebar-section-toggle:hover {
    background-color: #f8fafc;
}

.sidebar-section-toggle.active {
    background-color: #f8fafc;
}

.sidebar-submenu {
    padding-right: 2rem;
    margin-top: 0.25rem;
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}

.sidebar-sublink {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    font-size: 0.875rem;
    transition: all 0.2s;
}

.sidebar-sublink:hover {
    background-color: #f8fafc;
}

.sidebar-sublink.active {
    background-color: #e0f2fe;
    color: #0284c7;
    font-weight: 600;
}

/* Custom Scrollbar */
aside::-webkit-scrollbar {
    width: 6px;
}

aside::-webkit-scrollbar-track {
    background: #f1f5f9;
}

aside::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

aside::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}
</style>

<script>
// Initialize current user for dashboard integration
window.CURRENT_USER = {
    id: <?php echo $userId; ?>,
    name: '<?php echo addslashes($userName); ?>',
    email: '<?php echo addslashes($userEmail ?? ''); ?>',
    role: 'manager'
};

// Load integration libraries
document.addEventListener('DOMContentLoaded', function() {
    // Load dashboard integration script
    const script = document.createElement('script');
    script.src = '../js/dashboard-integration.js';
    script.onload = function() {
        console.log('✅ Dashboard Integration Loaded');
    };
    document.head.appendChild(script);
});

// Toggle sidebar sections
document.querySelectorAll('.sidebar-section-toggle').forEach(button => {
    button.addEventListener('click', function() {
        const submenu = this.nextElementSibling;
        const isHidden = submenu.classList.contains('hidden');
        
        if (isHidden) {
            submenu.classList.remove('hidden');
            this.classList.add('active');
        } else {
            submenu.classList.add('hidden');
            this.classList.remove('active');
        }
    });
});

// Initialize Lucide icons
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>

</body>
</html>
