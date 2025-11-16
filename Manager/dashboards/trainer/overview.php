<?php
/**
 * Trainer Dashboard - Overview Page
 * صفحة النظرة العامة للمدرب
 */
?>

<!-- Welcome Banner -->
<div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">مرحباً، <?php echo htmlspecialchars($userName); ?> 👋</h1>
            <p class="text-emerald-100 text-lg">نظرة عامة على دوراتك وطلابك</p>
        </div>
        <div class="hidden md:block">
            <i data-lucide="graduation-cap" class="w-24 h-24 opacity-20"></i>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
    <button onclick="location.href='?page=attendance'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="clipboard-check" class="w-8 h-8 mx-auto mb-2 text-emerald-600"></i>
        <p class="font-semibold text-slate-800">تسجيل الحضور</p>
    </button>
    <button onclick="location.href='?page=grades'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="award" class="w-8 h-8 mx-auto mb-2 text-amber-600"></i>
        <p class="font-semibold text-slate-800">إدخال الدرجات</p>
    </button>
    <button onclick="location.href='?page=materials'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="upload" class="w-8 h-8 mx-auto mb-2 text-sky-600"></i>
        <p class="font-semibold text-slate-800">رفع مادة</p>
    </button>
    <button onclick="location.href='?page=announcements'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="megaphone" class="w-8 h-8 mx-auto mb-2 text-violet-600"></i>
        <p class="font-semibold text-slate-800">إعلان جديد</p>
    </button>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- My Courses -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="book-open" class="w-6 h-6 text-emerald-600"></i>
            </div>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['total_courses']; ?></h3>
        <p class="text-slate-500 text-sm">دوراتي</p>
    </div>

    <!-- Active Students -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-sky-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-sky-600"></i>
            </div>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['active_students']; ?></h3>
        <p class="text-slate-500 text-sm">طالب نشط</p>
    </div>

    <!-- Materials -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="file-text" class="w-6 h-6 text-amber-600"></i>
            </div>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['total_materials']; ?></h3>
        <p class="text-slate-500 text-sm">مادة تدريبية</p>
    </div>

    <!-- Avg Attendance -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-violet-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-violet-100 flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-violet-600"></i>
            </div>
        </div>
        <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo $stats['avg_attendance']; ?>%</h3>
        <p class="text-slate-500 text-sm">معدل الحضور</p>
    </div>
</div>

<!-- Charts & Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Attendance Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart" class="w-5 h-5 text-emerald-600"></i>
            الحضور الأسبوعي
        </h3>
        <canvas id="attendanceChart" height="250"></canvas>
    </div>

    <!-- Students Performance -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-5 h-5 text-sky-600"></i>
            أداء الطلاب
        </h3>
        <canvas id="performanceChart" height="250"></canvas>
    </div>
</div>

<!-- Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- My Courses -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="book-open" class="w-5 h-5 text-emerald-600"></i>
                دوراتي النشطة
            </h3>
            <a href="?page=courses" class="text-sm text-emerald-600 hover:text-emerald-700 font-semibold">عرض الكل</a>
        </div>
        <div id="coursesContainer" class="space-y-3">
            <div class="text-center py-8 text-slate-500">
                <i data-lucide="loader" class="w-8 h-8 mx-auto mb-2 animate-spin"></i>
                <p>جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Recent Students -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-sky-600"></i>
                طلاب جدد
            </h3>
            <a href="?page=students" class="text-sm text-sky-600 hover:text-sky-700 font-semibold">عرض الكل</a>
        </div>
        <div id="studentsContainer" class="space-y-3">
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
    
    // Attendance Chart
    const attendanceCtx = document.getElementById('attendanceChart');
    if (attendanceCtx) {
        new Chart(attendanceCtx, {
            type: 'line',
            data: {
                labels: ['السبت', 'الأحد', 'الاثنين', 'الثلاثاء', 'الأربعاء', 'الخميس'],
                datasets: [{
                    label: 'معدل الحضور',
                    data: [85, 90, 78, 88, 92, 87],
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                }
            }
        });
    }
    
    // Performance Chart
    const performanceCtx = document.getElementById('performanceChart');
    if (performanceCtx) {
        new Chart(performanceCtx, {
            type: 'doughnut',
            data: {
                labels: ['ممتاز', 'جيد جداً', 'جيد', 'مقبول'],
                datasets: [{
                    data: [30, 35, 25, 10],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b', '#ef4444']
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
    
    // Load courses
    loadMyCourses();
    loadRecentStudents();
});

async function loadMyCourses() {
    try {
        const response = await TrainerFeatures.courses.getMyCourses();
        const container = document.getElementById('coursesContainer');
        
        if (response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.slice(0, 5).map(course => `
                <div class="p-4 border border-slate-200 rounded-lg hover:border-emerald-300 transition-colors">
                    <div class="flex items-center justify-between">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${course.title}</h4>
                            <p class="text-sm text-slate-500 mt-1">${course.enrolled_count || 0} طالب</p>
                        </div>
                        <a href="?page=courses&id=${course.course_id}" class="text-emerald-600 hover:text-emerald-700">
                            <i data-lucide="arrow-left" class="w-5 h-5"></i>
                        </a>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا توجد دورات</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

async function loadRecentStudents() {
    try {
        const response = await TrainerFeatures.students.getMyStudents();
        const container = document.getElementById('studentsContainer');
        
        if (response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.slice(0, 5).map(student => `
                <div class="p-4 border border-slate-200 rounded-lg hover:border-sky-300 transition-colors">
                    <div class="flex items-center gap-3">
                        <img src="${student.photo || '../platform/photos/default-avatar.png'}" 
                             class="w-10 h-10 rounded-full object-cover">
                        <div class="flex-1">
                            <h4 class="font-semibold text-slate-800">${student.full_name}</h4>
                            <p class="text-sm text-slate-500">${student.course_title}</p>
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<p class="text-center text-slate-500 py-8">لا يوجد طلاب</p>';
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading students:', error);
    }
}
</script>
