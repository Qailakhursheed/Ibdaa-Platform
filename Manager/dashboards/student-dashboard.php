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
            background: linear-gradient(to right, #10b981, #059669);
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
    <div class="flex min-h-screen bg-slate-50">
        <!-- Sidebar -->
        <aside class="w-72 bg-white border-l border-slate-200 shadow-lg fixed h-screen overflow-y-auto">
            <!-- Logo & User Profile -->
            <div class="px-6 py-6 border-b border-slate-200 text-center bg-gradient-to-br from-emerald-50 to-green-50">
                <div class="relative inline-block">
                    <img src="<?php echo htmlspecialchars($student_photo); ?>" 
                         alt="<?php echo htmlspecialchars($student_name); ?>" 
                         class="mx-auto mb-3 w-20 h-20 rounded-full border-4 border-emerald-500 object-cover shadow-lg">
                    <span class="absolute bottom-3 right-0 w-5 h-5 bg-emerald-500 border-2 border-white rounded-full"></span>
                </div>
                <h1 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($student_name); ?></h1>
                <p class="text-sm text-emerald-600 font-semibold mt-1">طالب</p>
            </div>
            
            <!-- Navigation -->
            <nav class="px-4 py-6 space-y-2">
                <a href="?page=overview" class="sidebar-link <?php echo $page === 'overview' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="home" class="w-5 h-5"></i>
                    <span class="font-semibold">الرئيسية</span>
                </a>
                
                <div class="pt-4 pb-2 px-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase">التعليم</p>
                </div>
                
                <a href="?page=courses" class="sidebar-link <?php echo $page === 'courses' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                    <span class="font-semibold">دوراتي</span>
                    <span class="mr-auto px-2 py-1 text-xs font-bold bg-emerald-100 text-emerald-700 rounded-full"><?php echo $stats['enrolled_courses']; ?></span>
                </a>
                
                <a href="?page=grades" class="sidebar-link <?php echo $page === 'grades' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="star" class="w-5 h-5"></i>
                    <span class="font-semibold">درجاتي</span>
                </a>
                
                <a href="?page=attendance" class="sidebar-link <?php echo $page === 'attendance' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="calendar-check" class="w-5 h-5"></i>
                    <span class="font-semibold">الحضور</span>
                </a>
                
                <div class="pt-4 pb-2 px-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase">المحتوى</p>
                </div>
                
                <a href="?page=assignments" class="sidebar-link <?php echo $page === 'assignments' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span class="font-semibold">الواجبات</span>
                    <?php if ($stats['pending_assignments'] > 0): ?>
                        <span class="notification-badge mr-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full"><?php echo $stats['pending_assignments']; ?></span>
                    <?php endif; ?>
                </a>
                
                <a href="?page=materials" class="sidebar-link <?php echo $page === 'materials' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="folder" class="w-5 h-5"></i>
                    <span class="font-semibold">المواد الدراسية</span>
                </a>
                
                <div class="pt-4 pb-2 px-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase">الجدول والبطاقات</p>
                </div>
                
                <a href="?page=schedule" class="sidebar-link <?php echo $page === 'schedule' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="calendar" class="w-5 h-5"></i>
                    <span class="font-semibold">الجدول الدراسي</span>
                </a>
                
                <a href="?page=id-card" class="sidebar-link <?php echo $page === 'id-card' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="credit-card" class="w-5 h-5"></i>
                    <span class="font-semibold">البطاقة الجامعية</span>
                </a>
                
                <div class="pt-4 pb-2 px-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase">المالية</p>
                </div>
                
                <a href="?page=payments" class="sidebar-link <?php echo $page === 'payments' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span class="font-semibold">الحالة المالية</span>
                    <?php if ($stats['balance'] > 0): ?>
                        <span class="notification-badge mr-auto px-2 py-1 text-xs font-bold bg-red-500 text-white rounded-full">!</span>
                    <?php endif; ?>
                </a>
                
                <div class="pt-4 pb-2 px-4">
                    <p class="text-xs font-semibold text-slate-400 uppercase">التواصل</p>
                </div>
                
                <a href="?page=chat" class="sidebar-link <?php echo $page === 'chat' ? 'active' : ''; ?> flex items-center gap-3 px-4 py-3 rounded-xl text-slate-700 hover:bg-slate-50 transition-all">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                    <span class="font-semibold">المحادثات</span>
                    <span id="unreadMessages" class="mr-auto bg-blue-100 text-blue-700 text-xs font-bold px-2 py-1 rounded-full hidden">0</span>
                </a>
            </nav>
            
            <!-- Logout Button -->
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
                            echo $titles[$page] ?? 'لوحة الطالب';
                            ?>
                        </h2>
                        <p class="text-sm text-slate-500 mt-1">إدارة دوراتك ودرجاتك</p>
                    </div>
                    
                    <div class="flex items-center gap-4">
                        <!-- Unified Notifications Bell Component -->
                        <?php include __DIR__ . '/components/notifications-bell.php'; ?>
                        
                        <!-- User Info -->
                        <div class="text-left">
                            <p class="text-sm font-semibold text-slate-800"><?php echo htmlspecialchars($student_name); ?></p>
                            <p class="text-xs text-slate-500">طالب</p>
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
