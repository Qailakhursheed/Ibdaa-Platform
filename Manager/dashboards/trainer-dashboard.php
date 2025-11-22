<?php
/**
 * Trainer Dashboard - لوحة تحكم المدرب
 * Complete Trainer Dashboard
 */

require_once __DIR__ . '/shared-header.php';

if ($userRole !== 'trainer') {
    header('Location: ' . $managerBaseUrl . '/login.php?error=access_denied');
    exit;
}

// Get current page
$currentPage = $_GET['page'] ?? 'overview';

// Load TrainerHelper for hybrid system
require_once __DIR__ . '/../includes/trainer_helper.php';
$trainerHelper = new TrainerHelper($conn, $userId);

// Get trainer statistics using TrainerHelper
$stats = $trainerHelper->getStatistics();

// Make trainerHelper globally available for included pages
$GLOBALS['trainerHelper'] = $trainerHelper;
?>

<div class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-l border-slate-200 shadow-lg fixed h-screen overflow-y-auto">
        <div class="px-6 py-6 border-b border-slate-200 text-center bg-gradient-to-br from-emerald-50 to-green-50">
            <div class="relative inline-block">
                <img src="<?php echo $userPhoto ?? ($platformBaseUrl . '/photos/Sh.jpg'); ?>" 
                     alt="<?php echo htmlspecialchars($userName); ?>" 
                     class="mx-auto mb-3 w-20 h-20 rounded-full border-4 border-emerald-500 object-cover shadow-lg">
                <span class="absolute bottom-3 right-0 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full"></span>
            </div>
            <h1 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($userName); ?></h1>
            <p class="text-sm text-emerald-600 font-semibold mt-1">مدرب</p>
        </div>
        
        <nav class="px-4 py-6 space-y-2">
            <a href="?page=overview" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'overview' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>نظرة عامة</span>
            </a>
            
            <div class="pt-4 pb-2 px-4">
                <p class="text-xs font-semibold text-slate-400 uppercase">التدريس</p>
            </div>
            
            <a href="?page=courses" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'courses' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="book-open" class="w-5 h-5"></i>
                <span>دوراتي</span>
                <span class="mr-auto text-xs text-slate-400"><?php echo $stats['total_courses']; ?></span>
            </a>
            
            <a href="?page=students" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'students' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="users" class="w-5 h-5"></i>
                <span>طلابي</span>
                <span class="mr-auto text-xs text-slate-400"><?php echo $stats['active_students']; ?></span>
            </a>
            
            <a href="?page=attendance" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'attendance' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="clipboard-check" class="w-5 h-5"></i>
                <span>الحضور والغياب</span>
            </a>
            
            <a href="?page=grades" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'grades' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="award" class="w-5 h-5"></i>
                <span>الدرجات</span>
                <?php if ($stats['pending_grades'] > 0): ?>
                <span class="mr-auto bg-amber-100 text-amber-700 text-xs font-bold px-2 py-1 rounded-full"><?php echo $stats['pending_grades']; ?></span>
                <?php endif; ?>
            </a>
            
            <div class="pt-4 pb-2 px-4">
                <p class="text-xs font-semibold text-slate-400 uppercase">المحتوى</p>
            </div>
            
            <a href="?page=materials" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'materials' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="file-text" class="w-5 h-5"></i>
                <span>المواد التدريبية</span>
                <span class="mr-auto text-xs text-slate-400"><?php echo $stats['total_materials']; ?></span>
            </a>
            
            <a href="?page=assignments" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'assignments' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                <span>الواجبات</span>
            </a>
            
            <div class="pt-4 pb-2 px-4">
                <p class="text-xs font-semibold text-slate-400 uppercase">التواصل</p>
            </div>
            
            <a href="?page=chat" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'chat' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span>الدردشة</span>
                <span id="unreadMessages" class="mr-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded-full hidden">0</span>
            </a>
            
            <a href="?page=announcements" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'announcements' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="megaphone" class="w-5 h-5"></i>
                <span>الإعلانات</span>
            </a>
            
            <div class="pt-4 pb-2 px-4">
                <p class="text-xs font-semibold text-slate-400 uppercase">التقارير</p>
            </div>
            
            <a href="?page=reports" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl transition-all <?php echo $currentPage === 'reports' ? 'bg-emerald-50 text-emerald-700 font-semibold' : 'hover:bg-slate-50 text-slate-700'; ?>">
                <i data-lucide="bar-chart" class="w-5 h-5"></i>
                <span>تقاريري</span>
            </a>
        </nav>
        
        <div class="p-4 border-t border-slate-200 mt-auto">
            <a href="<?php echo $managerBaseUrl; ?>/logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-all font-semibold">
                <i data-lucide="log-out" class="w-5 h-5"></i>
                <span>تسجيل الخروج</span>
            </a>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 mr-72">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4 sticky top-0 z-40 shadow-sm">
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                        <i data-lucide="graduation-cap" class="w-6 h-6 text-emerald-600"></i>
                        لوحة المدرب
                    </h2>
                    <p class="text-sm text-slate-500 mt-1">إدارة الدورات والطلاب</p>
                </div>
                <div class="flex items-center gap-4">
                    <!-- Unified Notifications Bell Component -->
                    <?php include __DIR__ . '/components/notifications-bell.php'; ?>
                    
                    <!-- User Info -->
                    <div class="text-left">
                        <p class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($userName); ?></p>
                        <p class="text-xs text-slate-500">مدرب</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Page Content -->
        <div class="p-8">
            <?php
            // Route to different pages
            switch ($currentPage) {
                case 'overview':
                    include __DIR__ . '/trainer/overview.php';
                    break;
                case 'courses':
                    include __DIR__ . '/trainer/courses.php';
                    break;
                case 'students':
                    include __DIR__ . '/trainer/students.php';
                    break;
                case 'attendance':
                    include __DIR__ . '/trainer/attendance.php';
                    break;
                case 'grades':
                    include __DIR__ . '/trainer/grades.php';
                    break;
                case 'materials':
                    include __DIR__ . '/trainer/materials.php';
                    break;
                case 'assignments':
                    include __DIR__ . '/trainer/assignments.php';
                    break;
                case 'chat':
                    include __DIR__ . '/trainer/chat.php';
                    break;
                case 'announcements':
                    include __DIR__ . '/trainer/announcements.php';
                    break;
                case 'reports':
                    include __DIR__ . '/trainer/reports.php';
                    break;
                default:
                    include __DIR__ . '/trainer/overview.php';
            }
            ?>
        </div>
    </main>
</div>

<!-- Modals Container -->
<div id="modalsContainer"></div>

<!-- Load Scripts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<script>
// Initialize Lucide Icons
lucide.createIcons();

// Initialize current user BEFORE loading other scripts
window.CURRENT_USER = {
    id: <?php echo $userId; ?>,
    name: '<?php echo htmlspecialchars($userName); ?>',
    email: '<?php echo htmlspecialchars($userEmail ?? ''); ?>',
    role: 'trainer'
};
</script>

<!-- Load integration libraries AFTER CURRENT_USER is defined -->
<script src="../js/dashboard-integration.js"></script>
<script src="../js/trainer-features.js"></script>
</body>
</html>
