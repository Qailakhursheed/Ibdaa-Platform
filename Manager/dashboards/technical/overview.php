<?php
/**
 * Technical Dashboard - Overview Page
 * صفحة النظرة العامة للمشرف الفني
 */
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">مرحباً، <?php echo htmlspecialchars($userName); ?> 👋</h1>
            <p class="text-sky-100 text-lg">نظرة عامة على حالة النظام التعليمي</p>
        </div>
        <div class="hidden md:block">
            <i data-lucide="briefcase" class="w-24 h-24 opacity-20"></i>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Courses -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-sky-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center">
                <i data-lucide="book-open" class="w-6 h-6 text-sky-600"></i>
            </div>
            <span class="text-sm font-semibold text-sky-600 bg-sky-50 px-3 py-1 rounded-full">الدورات</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['total_courses']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي الدورات</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <div class="flex items-center justify-between text-sm">
                <span class="text-emerald-600">● نشط: <?php echo $stats['active_courses']; ?></span>
                <span class="text-amber-600">● معلق: <?php echo $stats['pending_courses']; ?></span>
            </div>
        </div>
    </div>

    <!-- Total Trainers -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">المدربون</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['total_trainers']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي المدربين</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="?page=trainers" class="text-sm text-emerald-600 hover:text-emerald-700 font-semibold flex items-center gap-1">
                عرض الكل
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Support Tickets -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="life-buoy" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">الدعم</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['support_tickets']; ?></h3>
        <p class="text-slate-500 text-sm">تذاكر الدعم المعلقة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="?page=support" class="text-sm text-amber-600 hover:text-amber-700 font-semibold flex items-center gap-1">
                عرض الكل
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
        </div>
    </div>

    <!-- Pending Reviews -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-violet-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-violet-100 flex items-center justify-center">
                <i data-lucide="star" class="w-6 h-6 text-violet-600"></i>
            </div>
            <span class="text-sm font-semibold text-violet-600 bg-violet-50 px-3 py-1 rounded-full">التقييمات</span>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['pending_reviews']; ?></h3>
        <p class="text-slate-500 text-sm">مراجعات معلقة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <a href="?page=evaluations" class="text-sm text-violet-600 hover:text-violet-700 font-semibold flex items-center gap-1">
                عرض الكل
                <i data-lucide="arrow-left" class="w-4 h-4"></i>
            </a>
        </div>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Courses Status Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-5 h-5 text-sky-600"></i>
            حالة الدورات
        </h3>
        <canvas id="coursesStatusChart" height="250"></canvas>
    </div>

    <!-- Trainers Performance Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart" class="w-5 h-5 text-emerald-600"></i>
            أداء المدربين
        </h3>
        <canvas id="trainersPerformanceChart" height="250"></canvas>
    </div>
</div>

<!-- Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Pending Course Reviews -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="clipboard-check" class="w-5 h-5 text-sky-600"></i>
                دورات تحتاج مراجعة
            </h3>
            <a href="?page=courses" class="text-sm text-sky-600 hover:text-sky-700 font-semibold">عرض الكل</a>
        </div>
        <div id="pendingCoursesContainer" class="space-y-3">
            <div class="text-center py-8 text-slate-500">
                <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Recent Support Tickets -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="help-circle" class="w-5 h-5 text-amber-600"></i>
                تذاكر الدعم الأخيرة
            </h3>
            <a href="?page=support" class="text-sm text-amber-600 hover:text-amber-700 font-semibold">عرض الكل</a>
        </div>
        <div id="supportTicketsContainer" class="space-y-3">
            <div class="text-center py-8 text-slate-500">
                <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Courses Status Chart
    const coursesCtx = document.getElementById('coursesStatusChart');
    if (coursesCtx) {
        new Chart(coursesCtx, {
            type: 'doughnut',
            data: {
                labels: ['نشط', 'معلق', 'مكتمل', 'ملغي'],
                datasets: [{
                    data: [<?php echo $stats['active_courses']; ?>, <?php echo $stats['pending_courses']; ?>, 5, 2],
                    backgroundColor: ['#10b981', '#f59e0b', '#3b82f6', '#ef4444'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Cairo', size: 12 } }
                    }
                }
            }
        });
    }
    
    // Trainers Performance Chart
    const trainersCtx = document.getElementById('trainersPerformanceChart');
    if (trainersCtx) {
        new Chart(trainersCtx, {
            type: 'bar',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                datasets: [{
                    label: 'متوسط التقييم',
                    data: [4.2, 4.5, 4.3, 4.7, 4.6, 4.8],
                    backgroundColor: '#10b981',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 5,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    }
    
    // Load pending courses
    loadPendingCourses();
    
    // Load support tickets
    loadSupportTickets();
});

async function loadPendingCourses() {
    try {
        const response = await fetch('../api/courses.php?action=get_pending');
        const data = await response.json();
        
        const container = document.getElementById('pendingCoursesContainer');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.slice(0, 5).map(course => `
                <div class="p-4 border border-slate-200 rounded-lg hover:border-sky-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${course.title}</h4>
                            <p class="text-sm text-slate-500 mt-1">${course.trainer_name || 'لم يحدد'}</p>
                            <span class="inline-block mt-2 text-xs bg-amber-100 text-amber-700 px-2 py-1 rounded-full">معلق</span>
                        </div>
                        <a href="?page=courses&id=${course.course_id}" class="text-sky-600 hover:text-sky-700">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا توجد دورات معلقة</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading pending courses:', error);
        document.getElementById('pendingCoursesContainer').innerHTML = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
    }
}

async function loadSupportTickets() {
    try {
        const response = await fetch('../api/support.php?action=get_recent');
        const data = await response.json();
        
        const container = document.getElementById('supportTicketsContainer');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = data.data.slice(0, 5).map(ticket => `
                <div class="p-4 border border-slate-200 rounded-lg hover:border-amber-300 transition-colors">
                    <div class="flex items-start justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${ticket.subject}</h4>
                            <p class="text-sm text-slate-500 mt-1">${ticket.user_name}</p>
                            <span class="inline-block mt-2 text-xs bg-red-100 text-red-700 px-2 py-1 rounded-full">${ticket.priority}</span>
                        </div>
                        <a href="?page=support&id=${ticket.ticket_id}" class="text-amber-600 hover:text-amber-700">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا توجد تذاكر دعم</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading support tickets:', error);
        document.getElementById('supportTicketsContainer').innerHTML = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
    }
}
</script>
