<?php
/**
 * Manager Dashboard - Main System
 * لوحة المدير العام - النظام الرئيسي
 * 
 * Simple PHP-based dashboard without complex JavaScript
 * Built with PHP, MySQL, and Chart.js only
 */

require_once __DIR__ . '/shared-header.php';

// التحقق من الصلاحية - المدير فقط
if ($userRole !== 'manager') {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// تحديد الصفحة المطلوبة
$page = isset($_GET['page']) ? $_GET['page'] : 'overview';
$allowedPages = [
    'overview', 'students', 'trainers', 'courses', 'requests', 
    'finance', 'certificates', 'reports', 'announcements', 
    'materials', 'evaluations', 'chat', 'support'
];

if (!in_array($page, $allowedPages)) {
    $page = 'overview';
}

$pageFile = __DIR__ . '/manager/' . $page . '.php';
?>

<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة المدير العام - منصة إبداع تعز</title>
    
    <!-- Tailwind CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@3.3.5/dist/tailwind.min.css" rel="stylesheet">
    
    <!-- Cairo Font -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    
    <style>
        body {
            font-family: 'Cairo', sans-serif;
        }
        
        .sidebar-link.active {
            background: linear-gradient(135deg, #0ea5e9 0%, #0284c7 100%);
            color: white;
        }
        
        .sidebar-link.active i {
            color: white;
        }
        
        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .animate-slide-in {
            animation: slideIn 0.3s ease-out;
        }
    </style>
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
            
            <!-- Navigation Menu -->
            <nav class="px-4 py-6 space-y-1">
                <a href="?page=overview" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'overview' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                    <span class="font-medium">لوحة التحكم</span>
                </a>
                
                <a href="?page=students" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'students' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="users" class="w-5 h-5"></i>
                    <span class="font-medium">الطلاب</span>
                </a>
                
                <a href="?page=trainers" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'trainers' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="graduation-cap" class="w-5 h-5"></i>
                    <span class="font-medium">المدربون</span>
                </a>
                
                <a href="?page=courses" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'courses' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="book-open" class="w-5 h-5"></i>
                    <span class="font-medium">الدورات</span>
                </a>
                
                <a href="?page=requests" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'requests' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="clipboard-list" class="w-5 h-5"></i>
                    <span class="font-medium">طلبات التسجيل</span>
                </a>
                
                <a href="?page=finance" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'finance' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="wallet" class="w-5 h-5"></i>
                    <span class="font-medium">الإدارة المالية</span>
                </a>
                
                <a href="?page=certificates" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'certificates' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="award" class="w-5 h-5"></i>
                    <span class="font-medium">الشهادات</span>
                </a>
                
                <a href="?page=reports" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'reports' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="bar-chart-3" class="w-5 h-5"></i>
                    <span class="font-medium">التقارير</span>
                </a>
                
                <a href="?page=announcements" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'announcements' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="megaphone" class="w-5 h-5"></i>
                    <span class="font-medium">الإعلانات</span>
                </a>
                
                <a href="?page=materials" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'materials' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="file-text" class="w-5 h-5"></i>
                    <span class="font-medium">المواد التعليمية</span>
                </a>
                
                <a href="?page=evaluations" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'evaluations' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="star" class="w-5 h-5"></i>
                    <span class="font-medium">التقييمات</span>
                </a>
                
                <a href="?page=chat" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'chat' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                    <span class="font-medium">المحادثات</span>
                </a>
                
                <a href="?page=support" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-sky-50 transition <?php echo $page === 'support' ? 'active' : 'text-slate-700'; ?>">
                    <i data-lucide="life-buoy" class="w-5 h-5"></i>
                    <span class="font-medium">الدعم الفني</span>
                </a>
                
                <div class="border-t border-slate-200 my-4"></div>
                
                <a href="../logout.php" class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-red-50 text-red-600 transition">
                    <i data-lucide="log-out" class="w-5 h-5"></i>
                    <span class="font-medium">تسجيل الخروج</span>
                </a>
            </nav>
        </aside>
        
        <!-- Main Content -->
        <main class="flex-1 mr-72 p-8">
            <div class="animate-slide-in">
                <?php
                if (file_exists($pageFile)) {
                    include $pageFile;
                } else {
                    echo '<div class="bg-red-50 border border-red-200 rounded-xl p-6 text-center">';
                    echo '<i data-lucide="alert-circle" class="w-12 h-12 text-red-500 mx-auto mb-3"></i>';
                    echo '<h3 class="text-xl font-bold text-red-800 mb-2">الصفحة غير موجودة</h3>';
                    echo '<p class="text-red-600">الصفحة المطلوبة غير متوفرة حالياً</p>';
                    echo '</div>';
                }
                ?>
            </div>
        </main>
    </div>
    
    <script>
        // Initialize Lucide icons
        lucide.createIcons();
        
        // Simple notification function
        function showNotification(message, type = 'success') {
            const bgColor = type === 'success' ? 'bg-green-500' : type === 'error' ? 'bg-red-500' : 'bg-blue-500';
            const notification = document.createElement('div');
            notification.className = `fixed top-4 left-4 ${bgColor} text-white px-6 py-4 rounded-xl shadow-lg z-50 animate-slide-in`;
            notification.textContent = message;
            document.body.appendChild(notification);
            
            setTimeout(() => {
                notification.remove();
            }, 3000);
        }
        
        // Confirm delete function
        function confirmDelete(message) {
            return confirm(message || 'هل أنت متأكد من الحذف؟');
        }
    </script>
</body>
</html>
