<?php
/**
 * Trainer Dashboard - لوحة تحكم المدرب
 * Complete Trainer Dashboard
 */

require_once __DIR__ . '/shared-header.php';

if ($userRole !== 'trainer') {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// Get current page
$currentPage = $_GET['page'] ?? 'overview';

// Load trainer statistics
$stats = [
    'total_courses' => 0,
    'active_students' => 0,
    'total_materials' => 0,
    'avg_attendance' => 0,
    'pending_grades' => 0
];

try {
    // Get trainer's courses
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM courses WHERE trainer_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['total_courses'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Get active students
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT e.user_id) as total 
        FROM enrollments e 
        JOIN courses c ON e.course_id = c.course_id 
        WHERE c.trainer_id = ? AND e.status = 'active'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stats['active_students'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    
    // Get materials count (if table exists)
    $stmt = $conn->query("SHOW TABLES LIKE 'course_materials'");
    if ($stmt->num_rows > 0) {
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM course_materials WHERE uploaded_by = ?");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $stats['total_materials'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
    }
} catch (Exception $e) {
    error_log("Trainer Dashboard Stats Error: " . $e->getMessage());
}
?>

<div class="flex min-h-screen bg-slate-50">
    <!-- Sidebar -->
    <aside class="w-72 bg-white border-l border-slate-200 shadow-lg fixed h-screen overflow-y-auto">
        <div class="px-6 py-6 border-b border-slate-200 text-center bg-gradient-to-br from-emerald-50 to-green-50">
            <div class="relative inline-block">
                <img src="<?php echo $userPhoto ?? '../platform/photos/Sh.jpg'; ?>" 
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
            <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl bg-red-50 text-red-600 hover:bg-red-100 transition-all font-semibold">
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
                    <!-- Notifications -->
                    <button id="notificationsBtn" class="relative p-2 rounded-lg hover:bg-slate-100 transition-colors">
                        <i data-lucide="bell" class="w-6 h-6 text-slate-600"></i>
                        <span id="notificationBadge" class="absolute top-0 right-0 w-5 h-5 bg-red-500 text-white text-xs rounded-full flex items-center justify-center font-bold hidden">0</span>
                    </button>
                    
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
<script src="../js/dashboard-integration.js"></script>
<script src="../js/trainer-features.js"></script>

<script>
// Initialize Lucide Icons
lucide.createIcons();

// Set current user for integration system
window.CURRENT_USER = {
    id: <?php echo $userId; ?>,
    name: '<?php echo htmlspecialchars($userName); ?>',
    email: '<?php echo htmlspecialchars($userEmail ?? ''); ?>',
    role: 'trainer'
};

// Initialize notifications
async function loadNotifications() {
    try {
        const response = await DashboardIntegration.api.notifications.getAll();
        if (response.success && response.data) {
            const unreadCount = response.data.filter(n => !n.is_read).length;
            const badge = document.getElementById('notificationBadge');
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

// Initialize chat unread count
async function loadChatUnread() {
    try {
        const response = await DashboardIntegration.api.chat.getConversations();
        if (response.success && response.data) {
            const unreadCount = response.data.reduce((sum, conv) => sum + (conv.unread_count || 0), 0);
            const badge = document.getElementById('unreadMessages');
            if (unreadCount > 0) {
                badge.textContent = unreadCount;
                badge.classList.remove('hidden');
            }
        }
    } catch (error) {
        console.error('Failed to load chat:', error);
    }
}

// Load data on page load
document.addEventListener('DOMContentLoaded', function() {
    loadNotifications();
    loadChatUnread();
    
    // Refresh every minute
    setInterval(loadNotifications, 60000);
    setInterval(loadChatUnread, 30000);
    
    console.log('✅ Trainer Dashboard Initialized');
});

// Notifications button click
document.getElementById('notificationsBtn')?.addEventListener('click', async function() {
    try {
        const response = await DashboardIntegration.api.notifications.getAll();
        if (response.success) {
            const notificationsHTML = response.data.map(n => `
                <div class="p-4 border-b border-slate-200 hover:bg-slate-50 cursor-pointer ${!n.is_read ? 'bg-blue-50' : ''}">
                    <div class="flex items-start gap-3">
                        <div class="flex-shrink-0 w-10 h-10 rounded-full bg-${n.type === 'error' ? 'red' : n.type === 'success' ? 'emerald' : 'sky'}-100 flex items-center justify-center">
                            <i data-lucide="bell" class="w-5 h-5 text-${n.type === 'error' ? 'red' : n.type === 'success' ? 'emerald' : 'sky'}-600"></i>
                        </div>
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${n.title}</h4>
                            <p class="text-sm text-slate-600 mt-1">${n.message}</p>
                            <p class="text-xs text-slate-400 mt-1">${new Date(n.created_at).toLocaleString('ar-EG')}</p>
                        </div>
                    </div>
                </div>
            `).join('');
            
            DashboardIntegration.ui.showModal('الإشعارات', notificationsHTML || '<p class="text-center text-slate-500 py-8">لا توجد إشعارات</p>', [
                { text: 'إغلاق', class: 'bg-slate-600 text-white hover:bg-slate-700', onclick: 'this.closest(".fixed").remove()' }
            ]);
            
            lucide.createIcons();
        }
    } catch (error) {
        DashboardIntegration.ui.showToast('فشل تحميل الإشعارات', 'error');
    }
});
</script>
</body>
</html>
