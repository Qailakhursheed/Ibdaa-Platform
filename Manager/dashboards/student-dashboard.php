<?php
require_once __DIR__ . '/shared-header.php';
require_once __DIR__ . '/../includes/student_helper.php';

if ($userRole !== 'student') {
    header('Location: ' . $managerBaseUrl . '/login.php?error=access_denied');
    exit;
}

// Initialize Student Helper - Hybrid System
$studentHelper = new StudentHelper($conn, $userId);

// Get student information from the shared session context
$student_name = $userName;
$student_email = $userEmail;
$student_photo = $userPhoto ?: $platformBaseUrl . '/photos/default-avatar.svg';

// Get statistics from StudentHelper (PHP + Database)
$gpaData = $studentHelper->getGPA();
$attendanceData = $studentHelper->getAttendanceRate();
$courses = $studentHelper->getMyCourses();
$balance = $studentHelper->getAccountBalance();

$stats = [
    'enrolled_courses' => count(array_filter($courses, fn($c) => $c['enrollment_status'] === 'active')),
    'completed_courses' => count(array_filter($courses, fn($c) => $c['enrollment_status'] === 'completed')),
    'gpa' => $gpaData['gpa'],
    'attendance_rate' => $attendanceData['rate'],
    'pending_assignments' => 0, // Will be loaded via AJAX if needed
    'total_materials' => 0,
    'unread_notifications' => 0,
    'balance' => $balance
];

// Get current page
$page = $_GET['page'] ?? 'overview';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الطالب - منصة إبداع</title>
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        
        * {
            font-family: 'Cairo', sans-serif;
        }
        
        .sidebar-link.active {
            background: linear-gradient(to right, #f59e0b, #d97706);
            color: white;
        }
        
        .notification-badge {
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.5; }
        }
    </style>
</head>
<body class="bg-slate-50">
    <div class="flex h-screen overflow-hidden">
        <!-- Sidebar -->
        <aside class="w-64 bg-white border-l border-slate-200 flex flex-col">
            <!-- Logo -->
            <div class="p-6 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center">
                        <i data-lucide="graduation-cap" class="w-6 h-6 text-white"></i>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold text-slate-800">لوحة الطالب</h1>
                        <p class="text-xs text-slate-500">منصة إبداع</p>
                    </div>
                </div>
            </div>
            
            <!-- Navigation -->
            <nav class="flex-1 overflow-y-auto p-4 space-y-2">
                <a href="?page=overview" class="sidebar-link <?php echo $page === 'overview' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="home" class="w-5 h-5"></i>
                    <span class="font-semibold">الرئيسية</span>
                </a>
                
                <a href="?page=courses" class="sidebar-link <?php echo $page === 'courses' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                    <span class="font-semibold">دوراتي</span>
                    <span class="mr-auto px-2 py-1 text-xs font-bold bg-amber-100 text-amber-700 rounded-full"><?php echo $stats['enrolled_courses']; ?></span>
                </a>
                
                <a href="?page=grades" class="sidebar-link <?php echo $page === 'grades' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="star" class="w-5 h-5"></i>
                    <span class="font-semibold">درجاتي</span>
                </a>
                
                <a href="?page=attendance" class="sidebar-link <?php echo $page === 'attendance' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                    <span class="font-semibold">الحضور</span>
                </a>
                
                <a href="?page=assignments" class="sidebar-link <?php echo $page === 'assignments' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span class="font-semibold">الواجبات</span>
                    <?php if ($stats['pending_assignments'] > 0): ?>
                        <span class="notification-badge mr-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full"><?php echo $stats['pending_assignments']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?page=materials" class="sidebar-link <?php echo $page === 'materials' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="folder" class="w-5 h-5"></i>
                    <span class="font-semibold">المواد الدراسية</span>
                </a>
                
                <a href="?page=schedule" class="sidebar-link <?php echo $page === 'schedule' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                    <span class="font-semibold">الجدول الدراسي</span>
                </a>
                
                <a href="?page=id-card" class="sidebar-link <?php echo $page === 'id-card' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                    <span class="font-semibold">البطاقة الجامعية</span>
                </a>
                
                <a href="?page=payments" class="sidebar-link <?php echo $page === 'payments' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span class="font-semibold">الحالة المالية</span>
                    <?php if ($stats['balance'] > 0): ?>
                        <span class="notification-badge mr-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">!</span>
                    <?php endif; ?>
                </a>
                
                <a href="?page=chat" class="sidebar-link <?php echo $page === 'chat' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-lg text-slate-700 hover:bg-amber-50 transition-colors">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                    <span class="font-semibold">المحادثات</span>
                </a>
            </nav>
            
            <!-- User Info -->
            <div class="p-4 border-t border-slate-200">
                <div class="flex items-center gap-3">
                    <img src="<?php echo htmlspecialchars($student_photo); ?>" 
                         alt="Student Photo" 
                         class="w-10 h-10 rounded-full object-cover">
                    <div class="flex-1">
                        <p class="font-semibold text-slate-800 text-sm"><?php echo htmlspecialchars($student_name); ?></p>
                        <p class="text-xs text-slate-500">طالب</p>
                    </div>
                    <a href="<?php echo $managerBaseUrl; ?>/logout.php" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors">
                        <i data-lucide="log-out" class="w-5 h-5"></i>
                    </a>
                </div>
            </div>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto">
            <!-- Header -->
            <header class="bg-white border-b border-slate-200 px-8 py-4">
                <div class="flex items-center justify-between">
                    <div>
                        <h2 class="text-2xl font-bold text-slate-800">
                            <?php 
                            $titles = [
                                'overview' => 'الرئيسية',
                                'courses' => 'دوراتي',
                                'grades' => 'درجاتي',
                                'attendance' => 'سجل الحضور',
                                'assignments' => 'الواجبات',
                                'materials' => 'المواد الدراسية',
                                'schedule' => 'الجدول الدراسي',
                                'id-card' => 'البطاقة الجامعية',
                                'payments' => 'الحالة المالية',
                                'chat' => 'المحادثات'
                            ];
                            echo $titles[$page] ?? 'الرئيسية';
                            ?>
                        </h2>
                        <p class="text-slate-600 text-sm mt-1">مرحباً <?php echo htmlspecialchars($student_name); ?> 👋</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Unified Notifications Bell Component -->
                        <?php include __DIR__ . '/components/notifications-bell.php'; ?>
                        
                        <!-- Search -->
                        <div class="relative">
                            <input type="text" 
                                   placeholder="بحث..." 
                                   class="w-64 pl-10 pr-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-amber-500">
                            <i data-lucide="search" class="w-5 h-5 absolute left-3 top-2.5 text-slate-400"></i>
                        </div>
                    </div>
                </div>
            </header>
            
            <!-- Page Content -->
            <div class="p-8">
                <?php
                // Include the appropriate page
                $page_file = __DIR__ . "/student/{$page}.php";
                if (file_exists($page_file)) {
                    include $page_file;
                } else {
                    echo '<div class="text-center py-12">
                            <i data-lucide="alert-circle" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                            <h3 class="text-xl font-bold text-slate-700 mb-2">الصفحة غير موجودة</h3>
                            <p class="text-slate-500">الصفحة المطلوبة غير متوفرة حالياً</p>
                          </div>';
                }
                ?>
            </div>
        </main>
    </div>
    
    <!-- Scripts -->
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Store current user info
        window.CURRENT_USER = {
            id: <?php echo $_SESSION['user_id']; ?>,
            name: '<?php echo addslashes($student_name); ?>',
            email: '<?php echo addslashes($student_email); ?>',
            role: 'student',
            photo: '<?php echo addslashes($student_photo); ?>'
        };
    </script>
    
    <!-- Dashboard Integration -->
    <script src="../js/dashboard-integration.js"></script>
    
    <!-- Student Features -->
    <script src="../js/student-features.js"></script>
</body>
</html>
