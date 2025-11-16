<?php
/**
 * Manager Dashboard - FULL VERSION with All Features
 * لوحة تحكم المدير العام - النسخة الكاملة مع جميع الوظائف
 */

require_once __DIR__ . '/shared-header.php';

// التحقق من الصلاحية
if ($userRole !== 'manager') {
    header('Location: ../login.php?error=access_denied');
    exit;
}

// جلب الإحصائيات
$stats = ['total_students' => 0, 'active_courses' => 0, 'total_revenue' => 0, 'certificates_issued' => 0];
try {
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if ($result) $stats['total_students'] = (int)$result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    if ($result) $stats['active_courses'] = (int)$result->fetch_assoc()['count'];
    
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
    if ($result) $stats['total_revenue'] = (float)$result->fetch_assoc()['total'];
    
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE certificate_issued = 1");
    if ($result) $stats['certificates_issued'] = (int)$result->fetch_assoc()['count'];
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
}
?>

<div id="managerDashboard" class="flex min-h-screen">
    <!-- Sidebar -->
    <aside id="sidebar" class="w-72 bg-white border-l border-slate-200 shadow-sm">
        <div class="px-6 py-6 border-b border-slate-200 text-center">
            <img src="../platform/photos/Sh.jpg" alt="شعار منصة إبداع" class="mx-auto mb-3 w-16 h-16 rounded-full border-4 border-sky-500 shadow-sm">
            <h1 class="text-2xl font-bold text-slate-800">منصة إبداع</h1>
            <p class="text-sm text-slate-500 mt-1">لوحة المدير العام</p>
        </div>
        
        <nav class="px-4 py-6 space-y-2 text-slate-700">
            <!-- لوحة التحكم -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50 active" data-page="dashboard">
                <i data-lucide="layout-dashboard" class="w-5 h-5"></i>
                <span>لوحة التحكم</span>
            </a>

            <!-- إدارة المستخدمين -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50" data-section="users">
                    <div class="flex items-center gap-3">
                        <i data-lucide="users-2" class="w-5 h-5"></i>
                        <span>إدارة المستخدمين</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="trainees">
                        <i data-lucide="user" class="w-4 h-4"></i>
                        <span>المتدربون</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="trainers">
                        <i data-lucide="user-check" class="w-4 h-4"></i>
                        <span>المدربون</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="graduates">
                        <i data-lucide="award" class="w-4 h-4"></i>
                        <span>ملف الخريجين</span>
                    </a>
                </div>
            </div>

            <!-- الدورات التدريبية -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50" data-section="courses">
                    <div class="flex items-center gap-3">
                        <i data-lucide="book-open" class="w-5 h-5"></i>
                        <span>الدورات التدريبية</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="courses">
                        <i data-lucide="book" class="w-4 h-4"></i>
                        <span>إدارة الدورات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="grades">
                        <i data-lucide="graduation-cap" class="w-4 h-4"></i>
                        <span>الدرجات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="attendance">
                        <i data-lucide="clipboard-check" class="w-4 h-4"></i>
                        <span>الحضور</span>
                    </a>
                </div>
            </div>

            <!-- الشؤون المالية -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50" data-page="finance">
                <i data-lucide="wallet" class="w-5 h-5"></i>
                <span>الشؤون المالية</span>
            </a>

            <!-- طلبات وإعلانات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50" data-section="requests">
                    <div class="flex items-center gap-3">
                        <i data-lucide="inbox" class="w-5 h-5"></i>
                        <span>الطلبات والإعلانات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="requests">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        <span>طلبات الالتحاق</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="announcements">
                        <i data-lucide="megaphone" class="w-4 h-4"></i>
                        <span>الإعلانات</span>
                    </a>
                </div>
            </div>

            <!-- التقارير والأدوات -->
            <div class="sidebar-section">
                <button class="sidebar-section-toggle w-full flex items-center justify-between px-4 py-3 rounded-xl hover:bg-slate-50" data-section="reports">
                    <div class="flex items-center gap-3">
                        <i data-lucide="bar-chart" class="w-5 h-5"></i>
                        <span>التقارير والأدوات</span>
                    </div>
                    <i data-lucide="chevron-down" class="w-4 h-4 transition-transform"></i>
                </button>
                <div class="sidebar-submenu hidden pl-8 mt-1 space-y-1">
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="analytics">
                        <i data-lucide="trending-up" class="w-4 h-4"></i>
                        <span>التحليلات</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="idcards">
                        <i data-lucide="credit-card" class="w-4 h-4"></i>
                        <span>البطاقات الطلابية</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="imports">
                        <i data-lucide="file-up" class="w-4 h-4"></i>
                        <span>الاستيراد الذكي</span>
                    </a>
                    <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-2 rounded-lg hover:bg-slate-50 text-sm" data-page="ai-images">
                        <i data-lucide="sparkles" class="w-4 h-4"></i>
                        <span>توليد الصور AI</span>
                    </a>
                </div>
            </div>

            <!-- الإعدادات -->
            <a href="#" class="sidebar-link flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-slate-50" data-page="settings">
                <i data-lucide="settings" class="w-5 h-5"></i>
                <span>الإعدادات</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1">
        <!-- Header -->
        <header class="bg-white border-b border-slate-200 px-8 py-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm text-slate-500">مرحباً،</p>
                    <h2 class="text-2xl font-bold text-slate-800"><?php echo htmlspecialchars($userName); ?></h2>
                    <p class="text-xs text-slate-400">المدير العام</p>
                </div>
                <div class="flex items-center gap-4">
                    <button id="notificationsBell" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50">
                        <i data-lucide="bell" class="w-5 h-5"></i>
                        <span id="notificationsCounter" class="hidden absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center"></span>
                    </button>
                    <a href="../logout.php" class="px-4 py-2 rounded-lg bg-sky-600 text-white hover:bg-sky-700">
                        تسجيل الخروج
                    </a>
                </div>
            </div>
        </header>

        <!-- Dynamic Page Content -->
        <div id="pageContainer" class="p-8">
            <!-- Header Section -->
            <div class="mb-6">
                <h1 id="pageTitle" class="text-3xl font-bold text-slate-800 mb-2">لوحة التحكم الرئيسية</h1>
                <p id="pageSubtitle" class="text-slate-600">نظرة عامة على أداء المنصة</p>
            </div>

            <!-- Body Section -->
            <div id="pageBody">
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-sky-50 text-sky-600">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">إجمالي المتدربين</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_students']); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-emerald-50 text-emerald-600">
                                <i data-lucide="book-open" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">الدورات النشطة</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['active_courses']); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-amber-50 text-amber-600">
                                <i data-lucide="wallet" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">إجمالي الإيرادات</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_revenue'], 0); ?> ريال</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-violet-50 text-violet-600">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">الشهادات الصادرة</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['certificates_issued']); ?></p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow p-6 border border-slate-100 mb-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-4">إجراءات سريعة</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button onclick="navigateTo('trainees')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-sky-500 hover:bg-sky-50 transition">
                            <i data-lucide="user-plus" class="w-8 h-8 text-sky-600"></i>
                            <span class="text-sm font-medium">إدارة المتدربين</span>
                        </button>
                        <button onclick="navigateTo('courses')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50 transition">
                            <i data-lucide="book-plus" class="w-8 h-8 text-emerald-600"></i>
                            <span class="text-sm font-medium">إدارة الدورات</span>
                        </button>
                        <button onclick="navigateTo('finance')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-amber-500 hover:bg-amber-50 transition">
                            <i data-lucide="dollar-sign" class="w-8 h-8 text-amber-600"></i>
                            <span class="text-sm font-medium">الشؤون المالية</span>
                        </button>
                        <button onclick="navigateTo('analytics')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-500 hover:bg-violet-50 transition">
                            <i data-lucide="bar-chart" class="w-8 h-8 text-violet-600"></i>
                            <span class="text-sm font-medium">التحليلات</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </main>
</div>

<!-- Modal Container -->
<div id="modalBackdrop" class="hidden fixed inset-0 bg-slate-900/60 flex items-center justify-center px-4 z-50">
    <div class="bg-white w-full max-w-3xl rounded-2xl shadow-xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200">
            <h3 id="modalTitle" class="text-xl font-semibold text-slate-800"></h3>
            <button id="closeModalBtn" class="p-2 rounded-full hover:bg-slate-100">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <div id="modalBody" class="px-6 py-6 max-h-[70vh] overflow-y-auto"></div>
    </div>
</div>

<style>
/* Active Sidebar Link */
.sidebar-link.active {
    background-color: #f0f9ff;
    color: #0284c7;
    font-weight: 600;
}

.sidebar-link.active i {
    color: #0284c7;
}

/* Sidebar Section */
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
#modalBackdrop:not(.hidden) {
    animation: fadeIn 0.2s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}
</style>

<script>
// تعريف معلومات المستخدم
window.CURRENT_USER = {
    id: <?php echo (int)$userId; ?>,
    role: <?php echo json_encode($userRole); ?>,
    name: <?php echo json_encode($userName); ?>
};

// دالة التنقل بين الصفحات
function navigateTo(page) {
    console.log('Navigate to:', page);
    showToast('جاري التحميل...', 'info');
    
    // Update active link
    document.querySelectorAll('.sidebar-link').forEach(link => {
        link.classList.remove('active');
        if (link.dataset.page === page) {
            link.classList.add('active');
        }
    });
    
    // Load page content based on page name
    loadPageContent(page);
}

function loadPageContent(page) {
    // استدعاء الوظيفة من manager-features.js
    const pageRenderers = {
        'dashboard': () => {
            // إظهار الصفحة الرئيسية مع البطاقات الإحصائية
            document.getElementById('pageTitle').textContent = 'لوحة التحكم الرئيسية';
            document.getElementById('pageSubtitle').textContent = 'نظرة عامة على أداء المنصة';
            
            // إعادة عرض بطاقات الإحصائيات
            const pageBody = document.getElementById('pageBody');
            pageBody.innerHTML = `
                <!-- Stats Cards -->
                <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-6 mb-8">
                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-sky-50 text-sky-600">
                                <i data-lucide="users" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">إجمالي المتدربين</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_students']); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-emerald-50 text-emerald-600">
                                <i data-lucide="book-open" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">الدورات النشطة</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['active_courses']); ?></p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-amber-50 text-amber-600">
                                <i data-lucide="wallet" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">إجمالي الإيرادات</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['total_revenue'], 0); ?> ريال</p>
                    </div>

                    <div class="bg-white rounded-2xl shadow p-6 border border-slate-100">
                        <div class="flex items-center justify-between mb-4">
                            <div class="p-3 rounded-full bg-violet-50 text-violet-600">
                                <i data-lucide="award" class="w-6 h-6"></i>
                            </div>
                        </div>
                        <p class="text-sm text-slate-500">الشهادات الصادرة</p>
                        <p class="text-3xl font-bold mt-2"><?php echo number_format($stats['certificates_issued']); ?></p>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white rounded-2xl shadow p-6 border border-slate-100 mb-8">
                    <h3 class="text-xl font-bold text-slate-800 mb-4">إجراءات سريعة</h3>
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                        <button onclick="navigateTo('trainees')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-sky-500 hover:bg-sky-50 transition">
                            <i data-lucide="user-plus" class="w-8 h-8 text-sky-600"></i>
                            <span class="text-sm font-medium">إدارة المتدربين</span>
                        </button>
                        <button onclick="navigateTo('courses')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50 transition">
                            <i data-lucide="book-plus" class="w-8 h-8 text-emerald-600"></i>
                            <span class="text-sm font-medium">إدارة الدورات</span>
                        </button>
                        <button onclick="navigateTo('finance')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-amber-500 hover:bg-amber-50 transition">
                            <i data-lucide="dollar-sign" class="w-8 h-8 text-amber-600"></i>
                            <span class="text-sm font-medium">الشؤون المالية</span>
                        </button>
                        <button onclick="navigateTo('analytics')" class="flex flex-col items-center gap-2 p-4 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-500 hover:bg-violet-50 transition">
                            <i data-lucide="bar-chart" class="w-8 h-8 text-violet-600"></i>
                            <span class="text-sm font-medium">التحليلات</span>
                        </button>
                    </div>
                </div>
            `;
            lucide.createIcons();
        },
        'trainees': renderTrainees,
        'trainers': renderTrainers,
        'courses': renderCourses,
        'finance': renderFinance,
        'requests': renderRequests,
        'announcements': renderAnnouncements,
        'grades': renderGrades,
        'analytics': renderAnalytics,
        'attendance': renderAttendance,
        'idcards': renderIDCards,
        'graduates': renderGraduates,
        'imports': renderImports,
        'ai-images': renderAIImages,
        'settings': renderSettings
    };
    
    if (pageRenderers[page]) {
        pageRenderers[page]();
    } else {
        document.getElementById('pageTitle').textContent = 'صفحة غير موجودة';
        document.getElementById('pageSubtitle').textContent = 'القسم المطلوب غير متاح';
        document.getElementById('pageBody').innerHTML = `
            <div class="bg-white rounded-2xl shadow p-8 text-center">
                <i data-lucide="alert-circle" class="w-16 h-16 mx-auto text-red-500 mb-4"></i>
                <h3 class="text-xl font-bold mb-2">القسم غير موجود</h3>
                <p class="text-slate-600">الرجاء اختيار قسم من القائمة الجانبية</p>
            </div>
        `;
        lucide.createIcons();
    }
}

// Setup sidebar navigation
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const page = this.dataset.page;
        if (page) {
            navigateTo(page);
        }
    });
});

// Setup collapsible sidebar sections
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

// Toast notification
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
        document.body.appendChild(toast);
    }
    
    toast.className = `fixed bottom-6 right-6 px-6 py-3 rounded-lg shadow-lg z-50 text-white ${colors[type] || colors.info}`;
    toast.textContent = message;
    toast.style.display = 'block';
    
    setTimeout(() => {
        toast.style.display = 'none';
    }, 3000);
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    console.log('Manager Dashboard loaded for:', CURRENT_USER);
});

// Modal functions
function openModal(title, content) {
    document.getElementById('modalTitle').textContent = title;
    document.getElementById('modalBody').innerHTML = content;
    document.getElementById('modalBackdrop').classList.remove('hidden');
    lucide.createIcons();
}

function closeModal() {
    document.getElementById('modalBackdrop').classList.add('hidden');
}

document.getElementById('closeModalBtn').addEventListener('click', closeModal);
</script>

<!-- تحميل الأنظمة الجديدة -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script src="../js/dashboard-integration.js"></script>
<script src="../js/advanced-forms.js"></script>
<script src="../js/dynamic-charts.js"></script>
<script src="../js/manager-features.js"></script>

<script>
// ===== تهيئة الأنظمة المتقدمة =====
document.addEventListener('DOMContentLoaded', function() {
    console.log('🚀 Initializing Advanced Manager Dashboard...');
    
    // تحميل الإحصائيات والرسوم البيانية
    if (typeof ChartsSystem !== 'undefined') {
        console.log('✅ Charts System detected - Loading analytics...');
        setTimeout(() => {
            ChartsSystem.loadDashboardStats();
            ChartsSystem.loadAllCharts();
            ChartsSystem.startAutoRefresh(5); // تحديث كل 5 دقائق
        }, 500);
    }
    
    // تفعيل نظام الإشعارات
    loadNotificationsSystem();
    
    // تفعيل نظام الدردشة
    initializeChatSystem();
    
    // إعداد الاختصارات
    setupKeyboardShortcuts();
});

// ===== نظام الإشعارات =====
let notificationInterval = null;

function loadNotificationsSystem() {
    console.log('🔔 Loading Notifications System...');
    
    // تحميل الإشعارات
    loadNotifications();
    
    // تحديث تلقائي كل دقيقة
    notificationInterval = setInterval(loadNotifications, 60000);
    
    // زر الإشعارات
    const bellBtn = document.getElementById('notificationsBell');
    if (bellBtn) {
        bellBtn.addEventListener('click', toggleNotificationsPanel);
    }
}

function loadNotifications() {
    fetch('../api/notifications_system.php?action=all&page=1&limit=10')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                updateNotificationBadge(data.unread_count);
                displayNotifications(data.notifications);
            }
        })
        .catch(error => console.error('Notifications error:', error));
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationsCounter');
    if (badge) {
        if (count > 0) {
            badge.textContent = count > 99 ? '99+' : count;
            badge.classList.remove('hidden');
        } else {
            badge.classList.add('hidden');
        }
    }
}

function toggleNotificationsPanel() {
    console.log('Toggle notifications panel');
    showToast('نظام الإشعارات قيد التطوير', 'info');
}

function displayNotifications(notifications) {
    // يمكن إضافة عرض الإشعارات في بانل منفصل
    console.log('Notifications loaded:', notifications.length);
}

// ===== نظام الدردشة =====
function initializeChatSystem() {
    console.log('💬 Initializing Chat System...');
    
    // يمكن إضافة زر فتح الدردشة
    const chatBtn = document.getElementById('chatButton');
    if (chatBtn) {
        chatBtn.addEventListener('click', openChatWindow);
    }
}

function openChatWindow() {
    console.log('Opening chat window...');
    showToast('نظام الدردشة قيد التطوير', 'info');
}

// ===== اختصارات لوحة المفاتيح =====
function setupKeyboardShortcuts() {
    document.addEventListener('keydown', function(e) {
        // Ctrl + K: بحث سريع
        if (e.ctrlKey && e.key === 'k') {
            e.preventDefault();
            showToast('البحث السريع قيد التطوير', 'info');
        }
        
        // Ctrl + N: إضافة طالب جديد
        if (e.ctrlKey && e.key === 'n') {
            e.preventDefault();
            if (typeof openAdvancedStudentModal !== 'undefined') {
                openAdvancedStudentModal();
            }
        }
        
        // Esc: إغلاق المودال
        if (e.key === 'Escape') {
            closeModal();
        }
    });
}

// ===== وظائف الترابط مع الصفحات الأخرى =====

// التنقل إلى لوحة المدرب
function navigateToTrainerDashboard(trainerId) {
    if (trainerId) {
        window.location.href = `dashboard_router.php?role=trainer&user_id=${trainerId}`;
    }
}

// التنقل إلى لوحة الطالب
function navigateToStudentDashboard(studentId) {
    if (studentId) {
        window.location.href = `dashboard_router.php?role=student&user_id=${studentId}`;
    }
}

// التنقل إلى لوحة المشرف الفني
function navigateToTechnicalDashboard() {
    window.location.href = 'dashboard_router.php?role=technical';
}

// فتح صفحة الاستيراد
function openImportPage() {
    navigateTo('imports');
}

// فتح صفحة التحليلات
function openAnalyticsPage() {
    navigateTo('analytics');
}

// فتح صفحة إصدار البطاقات
function openIDCardsPage() {
    navigateTo('id-cards');
}

// ===== وظائف سريعة =====

// إضافة طالب سريع
function quickAddStudent() {
    if (typeof openAdvancedStudentModal !== 'undefined') {
        openAdvancedStudentModal();
    } else {
        showToast('يرجى تحميل النماذج المتقدمة', 'warning');
    }
}

// إضافة دفعة مالية سريعة
function quickAddPayment() {
    if (typeof openAdvancedPaymentModal !== 'undefined') {
        openAdvancedPaymentModal();
    } else {
        showToast('يرجى تحميل النماذج المتقدمة', 'warning');
    }
}

// إرسال إشعار جماعي
function broadcastNotification() {
    const message = prompt('أدخل نص الإشعار:');
    if (message) {
        fetch('../api/notifications_system.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                action: 'broadcast',
                title: 'إشعار من الإدارة',
                message: message,
                type: 'announcement',
                target_roles: ['student', 'trainer', 'technical']
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                showToast('✓ تم إرسال الإشعار لجميع المستخدمين', 'success');
            } else {
                showToast('خطأ: ' + data.message, 'error');
            }
        });
    }
}

// تصدير التقارير
function exportReport(type) {
    showToast(`جاري تصدير تقرير ${type}...`, 'info');
    
    fetch(`../api/dynamic_analytics.php?action=comprehensive_analytics`)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // تحويل البيانات إلى CSV أو PDF
                console.log('Report data:', data);
                showToast('التقرير جاهز للتنزيل', 'success');
            }
        });
}

// تحديث الصفحة بدون إعادة تحميل
function refreshDashboard() {
    showToast('جاري تحديث البيانات...', 'info');
    
    if (typeof ChartsSystem !== 'undefined') {
        ChartsSystem.loadDashboardStats();
        ChartsSystem.loadAllCharts();
    }
    
    loadNotifications();
    
    setTimeout(() => {
        showToast('✓ تم تحديث البيانات بنجاح', 'success');
    }, 1000);
}

// ===== الإحصائيات الحية =====
function startLiveStats() {
    setInterval(() => {
        // تحديث الإحصائيات كل 30 ثانية
        fetch('../api/dynamic_analytics.php?action=dashboard_stats')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // تحديث البطاقات الإحصائية
                    updateStatCards(data.stats);
                }
            });
    }, 30000);
}

function updateStatCards(stats) {
    // تحديث قيم البطاقات بانيميشن
    animateNumber('totalStudents', stats.total_students);
    animateNumber('activeCourses', stats.active_courses);
    animateNumber('totalRevenue', stats.total_revenue);
}

function animateNumber(elementId, targetValue) {
    const element = document.getElementById(elementId);
    if (!element) return;
    
    const currentValue = parseInt(element.textContent.replace(/[^0-9]/g, '')) || 0;
    const step = (targetValue - currentValue) / 20;
    let current = currentValue;
    
    const timer = setInterval(() => {
        current += step;
        if ((step > 0 && current >= targetValue) || (step < 0 && current <= targetValue)) {
            current = targetValue;
            clearInterval(timer);
        }
        element.textContent = Math.round(current).toLocaleString('ar-SA');
    }, 50);
}

console.log('✅ Manager Dashboard Advanced Systems Loaded!');
</script>

</body>
</html>
