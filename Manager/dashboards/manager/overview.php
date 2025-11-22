<?php
/**
 * Manager Dashboard - Overview Page
 * لوحة المدير العام - الصفحة الرئيسية
 */

// Get statistics
$stats = [
    'total_students' => 0,
    'active_courses' => 0,
    'total_trainers' => 0,
    'pending_requests' => 0,
    'active_students' => 0,
    'completed_courses' => 0,
    'certificates_issued' => 0,
    'total_revenue' => 0
];

try {
    // Total Students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if ($result) $stats['total_students'] = (int)$result->fetch_assoc()['count'];
    
    // Active Students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'");
    if ($result) $stats['active_students'] = (int)$result->fetch_assoc()['count'];
    
    // Active Courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    if ($result) $stats['active_courses'] = (int)$result->fetch_assoc()['count'];
    
    // Completed Courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'completed'");
    if ($result) $stats['completed_courses'] = (int)$result->fetch_assoc()['count'];
    
    // Total Trainers
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
    if ($result) $stats['total_trainers'] = (int)$result->fetch_assoc()['count'];
    
    // Pending Requests
    $result = $conn->query("SELECT COUNT(*) as count FROM course_requests WHERE status = 'pending'");
    if ($result) $stats['pending_requests'] = (int)$result->fetch_assoc()['count'];
    
    // Certificates Issued
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE status = 'completed'");
    if ($result) $stats['certificates_issued'] = (int)$result->fetch_assoc()['count'];
    
    // Total Revenue (approximate from enrollments)
    $result = $conn->query("SELECT COUNT(*) * 500 as total FROM enrollments WHERE status = 'active' OR status = 'completed'");
    if ($result) $stats['total_revenue'] = (float)$result->fetch_assoc()['total'];
    
} catch (Exception $e) {
    error_log("Stats Error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="layout-dashboard" class="w-10 h-10"></i>
                لوحة المدير العام
            </h1>
            <p class="text-sky-100 text-lg">مرحباً بك في نظام إدارة منصة إبداع تعز</p>
        </div>
        <div class="text-left">
            <p class="text-sky-100 text-sm">اليوم</p>
            <p class="text-2xl font-bold"><?php echo date('Y-m-d'); ?></p>
            <p class="text-sky-100 text-sm"><?php echo date('H:i'); ?></p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Students -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="users" class="w-7 h-7 text-blue-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_students']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">إجمالي الطلاب</h3>
        <p class="text-sm text-slate-500 mt-1">نشط: <?php echo number_format($stats['active_students']); ?></p>
    </div>
    
    <!-- Active Courses -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i data-lucide="book-open" class="w-7 h-7 text-emerald-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['active_courses']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">الدورات النشطة</h3>
        <p class="text-sm text-slate-500 mt-1">مكتملة: <?php echo number_format($stats['completed_courses']); ?></p>
    </div>
    
    <!-- Total Trainers -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="graduation-cap" class="w-7 h-7 text-amber-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_trainers']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">المدربون</h3>
        <p class="text-sm text-slate-500 mt-1">إجمالي المدربين</p>
    </div>
    
    <!-- Total Revenue -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-14 h-14 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="wallet" class="w-7 h-7 text-purple-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_revenue']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">الإيرادات</h3>
        <p class="text-sm text-slate-500 mt-1">ريال يمني</p>
    </div>
</div>

<!-- Additional Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Pending Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['pending_requests']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">طلبات معلقة</h3>
    </div>
    
    <!-- Certificates Issued -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-sky-100 rounded-xl flex items-center justify-center">
                <i data-lucide="award" class="w-6 h-6 text-sky-600"></i>
            </div>
            <span class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['certificates_issued']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">شهادات صادرة</h3>
    </div>
    
    <!-- Completion Rate -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-green-600"></i>
            </div>
            <span class="text-2xl font-bold text-slate-800">
                <?php 
                $total_courses = $stats['active_courses'] + $stats['completed_courses'];
                $completion_rate = $total_courses > 0 ? round(($stats['completed_courses'] / $total_courses) * 100) : 0;
                echo $completion_rate . '%';
                ?>
            </span>
        </div>
        <h3 class="text-slate-600 font-semibold">معدل الإكمال</h3>
    </div>
    
    <!-- Active Ratio -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-indigo-100 rounded-xl flex items-center justify-center">
                <i data-lucide="activity" class="w-6 h-6 text-indigo-600"></i>
            </div>
            <span class="text-2xl font-bold text-slate-800">
                <?php 
                $active_ratio = $stats['total_students'] > 0 ? round(($stats['active_students'] / $stats['total_students']) * 100) : 0;
                echo $active_ratio . '%';
                ?>
            </span>
        </div>
        <h3 class="text-slate-600 font-semibold">نسبة النشاط</h3>
    </div>
</div>

<!-- Charts Section -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Students Status Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-5 h-5 text-blue-600"></i>
            حالة الطلاب
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="studentsStatusChart"></canvas>
        </div>
    </div>
    
    <!-- Courses Status Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart" class="w-5 h-5 text-emerald-600"></i>
            حالة الدورات
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="coursesStatusChart"></canvas>
        </div>
    </div>
</div>

<!-- Monthly Revenue Chart -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
        الإيرادات الشهرية
    </h3>
    <div style="height: 350px; position: relative;">
        <canvas id="revenueChart"></canvas>
    </div>
</div>

<!-- Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- Recent Students -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-5 h-5 text-blue-600"></i>
                آخر الطلاب المسجلين
            </h3>
            <a href="?page=students" class="text-sm text-blue-600 hover:text-blue-700 font-semibold">عرض الكل</a>
        </div>
        <div class="space-y-3">
            <?php
            $recent_students = $conn->query("SELECT id, full_name, email, created_at FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
            if ($recent_students && $recent_students->num_rows > 0):
                while ($student = $recent_students->fetch_assoc()):
            ?>
                <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></p>
                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                    </div>
                    <span class="text-xs text-slate-500"><?php echo date('Y-m-d', strtotime($student['created_at'])); ?></span>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p class="text-center text-slate-500 py-4">لا توجد بيانات</p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Pending Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="clock" class="w-5 h-5 text-red-600"></i>
                طلبات التسجيل المعلقة
            </h3>
            <a href="?page=requests" class="text-sm text-red-600 hover:text-red-700 font-semibold">عرض الكل</a>
        </div>
        <div class="space-y-3">
            <?php
            $pending = $conn->query("SELECT cr.id, u.full_name, c.name as course_name, cr.created_at 
                                    FROM course_requests cr 
                                    JOIN users u ON cr.user_id = u.id 
                                    JOIN courses c ON cr.course_id = c.id 
                                    WHERE cr.status = 'pending' 
                                    ORDER BY cr.created_at DESC LIMIT 5");
            if ($pending && $pending->num_rows > 0):
                while ($req = $pending->fetch_assoc()):
            ?>
                <div class="flex items-center justify-between p-3 bg-red-50 rounded-lg hover:bg-red-100 transition">
                    <div>
                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($req['full_name']); ?></p>
                        <p class="text-xs text-slate-600"><?php echo htmlspecialchars($req['course_name']); ?></p>
                    </div>
                    <span class="text-xs text-slate-500"><?php echo date('Y-m-d', strtotime($req['created_at'])); ?></span>
                </div>
            <?php
                endwhile;
            else:
            ?>
                <p class="text-center text-slate-500 py-4">لا توجد طلبات معلقة</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Students Status Chart
    const studentsCtx = document.getElementById('studentsStatusChart');
    if (studentsCtx) {
        new Chart(studentsCtx, {
            type: 'doughnut',
            data: {
                labels: ['نشط', 'غير نشط', 'معلق'],
                datasets: [{
                    data: [<?php echo $stats['active_students']; ?>, <?php echo $stats['total_students'] - $stats['active_students']; ?>, 0],
                    backgroundColor: ['#10b981', '#ef4444', '#f59e0b'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: { font: { family: 'Cairo', size: 12 } }
                    }
                }
            }
        });
    }
    
    // Courses Status Chart
    const coursesCtx = document.getElementById('coursesStatusChart');
    if (coursesCtx) {
        new Chart(coursesCtx, {
            type: 'bar',
            data: {
                labels: ['نشطة', 'مكتملة', 'معلقة'],
                datasets: [{
                    label: 'عدد الدورات',
                    data: [<?php echo $stats['active_courses']; ?>, <?php echo $stats['completed_courses']; ?>, 0],
                    backgroundColor: ['#10b981', '#3b82f6', '#f59e0b'],
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                },
                plugins: {
                    legend: { display: false }
                }
            }
        });
    }
    
    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'line',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [{
                    label: 'الإيرادات (ريال)',
                    data: [45000, 52000, 48000, 61000, 58000, 67000, 72000, 69000, 75000, 82000, 88000, 95000],
                    borderColor: '#8b5cf6',
                    backgroundColor: 'rgba(139, 92, 246, 0.1)',
                    tension: 0.4,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Cairo' } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                }
            }
        });
    }
});
</script>
