<?php
/**
 * Manager Dashboard - Overview Page (PHP Only)
 * لوحة التحكم الرئيسية - بدون JavaScript معقد
 */

// جلب الإحصائيات الأساسية
$stats = [
    'total_students' => 0,
    'active_courses' => 0,
    'total_revenue' => 0,
    'certificates_issued' => 0,
    'pending_requests' => 0,
    'active_trainers' => 0,
    'total_payments' => 0,
    'this_month_revenue' => 0
];

try {
    // إجمالي الطلاب
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    if ($result) $stats['total_students'] = (int)$result->fetch_assoc()['count'];
    
    // الدورات النشطة
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    if ($result) $stats['active_courses'] = (int)$result->fetch_assoc()['count'];
    
    // إجمالي الإيرادات (من enrollments)
    $result = $conn->query("SELECT COUNT(*) * 500 as total FROM enrollments WHERE payment_status = 'paid'");
    if ($result) $stats['total_revenue'] = (float)$result->fetch_assoc()['total'];
    
    // الشهادات الصادرة
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE certificate_issued = 1");
    if ($result) $stats['certificates_issued'] = (int)$result->fetch_assoc()['count'];
    
    // الطلبات المعلقة
    $tableCheck = $conn->query("SHOW TABLES LIKE 'registration_requests'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT COUNT(*) as count FROM registration_requests WHERE status = 'pending'");
        if ($result) $stats['pending_requests'] = (int)$result->fetch_assoc()['count'];
    }
    
    // المدربون النشطون
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer' AND status = 'active'");
    if ($result) $stats['active_trainers'] = (int)$result->fetch_assoc()['count'];
    
    // إجمالي الدفعات
    $result = $conn->query("SELECT COUNT(*) as count FROM enrollments WHERE payment_status = 'paid'");
    if ($result) $stats['total_payments'] = (int)$result->fetch_assoc()['count'];
    
    // إيرادات هذا الشهر
    $result = $conn->query("SELECT COUNT(*) * 500 as total FROM enrollments WHERE payment_status = 'paid' AND MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    if ($result) $stats['this_month_revenue'] = (float)$result->fetch_assoc()['total'];
    
} catch (Exception $e) {
    error_log("Stats Error: " . $e->getMessage());
}

// جلب أحدث المتدربين
$recent_students = [];
try {
    $result = $conn->query("SELECT id, name, email, created_at, status FROM users WHERE role = 'student' ORDER BY created_at DESC LIMIT 5");
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $recent_students[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Recent students error: " . $e->getMessage());
}

// جلب الطلبات المعلقة
$pending_requests = [];
try {
    $tableCheck = $conn->query("SHOW TABLES LIKE 'registration_requests'");
    if ($tableCheck && $tableCheck->num_rows > 0) {
        $result = $conn->query("SELECT id, name, phone, course_name, created_at, status FROM registration_requests WHERE status = 'pending' ORDER BY created_at DESC LIMIT 5");
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $pending_requests[] = $row;
            }
        }
    }
} catch (Exception $e) {
    error_log("Pending requests error: " . $e->getMessage());
}
?>

<!-- Stats Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Students -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-sky-50 text-sky-600">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
            <span class="text-xs text-emerald-600 font-semibold">
                <i data-lucide="trending-up" class="w-3 h-3 inline"></i>
            </span>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي المتدربين</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_students']); ?></p>
        <a href="?page=students" class="mt-3 text-xs text-sky-600 hover:text-sky-700 font-medium inline-block">
            عرض التفاصيل ←
        </a>
    </div>

    <!-- Active Courses -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i data-lucide="book-open" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">الدورات النشطة</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['active_courses']); ?></p>
        <a href="?page=courses" class="mt-3 text-xs text-emerald-600 hover:text-emerald-700 font-medium inline-block">
            إدارة الدورات ←
        </a>
    </div>

    <!-- Total Revenue -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
                <i data-lucide="wallet" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي الإيرادات</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_revenue'], 0); ?></p>
        <p class="text-xs text-slate-500 mt-1">ريال يمني</p>
        <a href="?page=finance" class="mt-3 text-xs text-amber-600 hover:text-amber-700 font-medium inline-block">
            التقرير المالي ←
        </a>
    </div>

    <!-- Certificates Issued -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 hover:shadow-md transition">
        <div class="flex items-center justify-between mb-4">
            <div class="p-3 rounded-xl bg-violet-50 text-violet-600">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">الشهادات الصادرة</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['certificates_issued']); ?></p>
        <a href="?page=graduates" class="mt-3 text-xs text-violet-600 hover:text-violet-700 font-medium inline-block">
            ملف الخريجين ←
        </a>
    </div>
</div>

<!-- Secondary Stats -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Active Trainers -->
    <div class="bg-gradient-to-br from-blue-50 to-white rounded-xl p-5 border border-blue-100">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-blue-100 text-blue-600">
                <i data-lucide="user-check" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-slate-600">المدربون النشطون</p>
                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['active_trainers']); ?></p>
            </div>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-gradient-to-br from-orange-50 to-white rounded-xl p-5 border border-orange-100">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-orange-100 text-orange-600">
                <i data-lucide="clock" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-slate-600">طلبات معلقة</p>
                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['pending_requests']); ?></p>
            </div>
        </div>
    </div>

    <!-- Total Payments -->
    <div class="bg-gradient-to-br from-green-50 to-white rounded-xl p-5 border border-green-100">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-green-100 text-green-600">
                <i data-lucide="credit-card" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-slate-600">إجمالي الدفعات</p>
                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['total_payments']); ?></p>
            </div>
        </div>
    </div>

    <!-- This Month Revenue -->
    <div class="bg-gradient-to-br from-purple-50 to-white rounded-xl p-5 border border-purple-100">
        <div class="flex items-center gap-3">
            <div class="p-2 rounded-lg bg-purple-100 text-purple-600">
                <i data-lucide="trending-up" class="w-5 h-5"></i>
            </div>
            <div>
                <p class="text-xs text-slate-600">إيرادات الشهر</p>
                <p class="text-2xl font-bold text-slate-800"><?php echo number_format($stats['this_month_revenue'], 0); ?></p>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6 mb-8">
    <!-- Students Status Chart -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4">حالة الطلاب</h3>
        <div style="height: 250px; position: relative;">
            <canvas id="studentsStatusChart"></canvas>
        </div>
    </div>

    <!-- Courses Status Chart -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4">حالة الدورات</h3>
        <div style="height: 250px; position: relative;">
            <canvas id="coursesStatusChart"></canvas>
        </div>
    </div>

    <!-- Revenue Chart -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4">الإيرادات الشهرية</h3>
        <div style="height: 250px; position: relative;">
            <canvas id="revenueChart"></canvas>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100 mb-8">
    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i data-lucide="zap" class="w-5 h-5 text-sky-600"></i>
        إجراءات سريعة
    </h3>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <a href="?page=students" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-sky-500 hover:bg-sky-50 transition">
            <div class="p-3 rounded-xl bg-sky-100 text-sky-600 group-hover:scale-110 transition">
                <i data-lucide="user-plus" class="w-7 h-7"></i>
            </div>
            <span class="text-sm font-medium text-slate-700">إدارة المتدربين</span>
        </a>
        
        <a href="?page=courses" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-emerald-500 hover:bg-emerald-50 transition">
            <div class="p-3 rounded-xl bg-emerald-100 text-emerald-600 group-hover:scale-110 transition">
                <i data-lucide="book-plus" class="w-7 h-7"></i>
            </div>
            <span class="text-sm font-medium text-slate-700">إدارة الدورات</span>
        </a>
        
        <a href="?page=finance" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-amber-500 hover:bg-amber-50 transition">
            <div class="p-3 rounded-xl bg-amber-100 text-amber-600 group-hover:scale-110 transition">
                <i data-lucide="dollar-sign" class="w-7 h-7"></i>
            </div>
            <span class="text-sm font-medium text-slate-700">الشؤون المالية</span>
        </a>
        
        <a href="?page=analytics" class="group flex flex-col items-center gap-3 p-5 rounded-xl border-2 border-dashed border-slate-300 hover:border-violet-500 hover:bg-violet-50 transition">
            <div class="p-3 rounded-xl bg-violet-100 text-violet-600 group-hover:scale-110 transition">
                <i data-lucide="bar-chart" class="w-7 h-7"></i>
            </div>
            <span class="text-sm font-medium text-slate-700">التحليلات</span>
        </a>
    </div>
</div>

<!-- Recent Activity -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <!-- Recent Students -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">أحدث التسجيلات</h3>
            <a href="?page=students" class="text-sm text-sky-600 hover:text-sky-700">
                عرض الكل
            </a>
        </div>
        <div class="space-y-3">
            <?php if (!empty($recent_students)): ?>
                <?php foreach ($recent_students as $student): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50">
                        <div class="w-10 h-10 rounded-full bg-sky-100 flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-sky-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($student['name']); ?></p>
                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                        <span class="text-xs text-slate-500">
                            <?php echo date('d/m', strtotime($student['created_at'])); ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-slate-500 text-center py-4">لا توجد تسجيلات حديثة</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pending Requests -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">طلبات معلقة</h3>
            <a href="?page=requests" class="text-sm text-sky-600 hover:text-sky-700">
                عرض الكل
            </a>
        </div>
        <div class="space-y-3">
            <?php if (!empty($pending_requests)): ?>
                <?php foreach ($pending_requests as $request): ?>
                    <div class="flex items-center gap-3 p-3 rounded-lg bg-amber-50">
                        <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center">
                            <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
                        </div>
                        <div class="flex-1">
                            <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($request['name']); ?></p>
                            <p class="text-xs text-slate-500"><?php echo htmlspecialchars($request['course_name']); ?></p>
                        </div>
                        <a href="?page=requests&action=view&id=<?php echo $request['id']; ?>" class="text-xs text-amber-600 hover:text-amber-700 font-medium">
                            مراجعة
                        </a>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-sm text-slate-500 text-center py-4">لا توجد طلبات معلقة</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Simple Chart.js for Dashboard -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// إعداد الرسوم البيانية البسيطة
document.addEventListener('DOMContentLoaded', function() {
    // Students Status Chart
    const studentsCtx = document.getElementById('studentsStatusChart');
    if (studentsCtx) {
        new Chart(studentsCtx, {
            type: 'doughnut',
            data: {
                labels: ['نشط', 'معلق', 'خريج'],
                datasets: [{
                    data: [<?php echo $stats['total_students'] - $stats['certificates_issued']; ?>, 0, <?php echo $stats['certificates_issued']; ?>],
                    backgroundColor: ['#0ea5e9', '#f59e0b', '#10b981']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Courses Status Chart
    const coursesCtx = document.getElementById('coursesStatusChart');
    if (coursesCtx) {
        new Chart(coursesCtx, {
            type: 'doughnut',
            data: {
                labels: ['نشط', 'قريباً', 'مكتمل'],
                datasets: [{
                    data: [<?php echo $stats['active_courses']; ?>, 0, 0],
                    backgroundColor: ['#10b981', '#0ea5e9', '#6366f1']
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { position: 'bottom' }
                }
            }
        });
    }

    // Revenue Chart
    const revenueCtx = document.getElementById('revenueChart');
    if (revenueCtx) {
        new Chart(revenueCtx, {
            type: 'bar',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
                datasets: [{
                    label: 'الإيرادات',
                    data: [45000, 52000, 48000, 61000, 55000, <?php echo $stats['this_month_revenue']; ?>],
                    backgroundColor: '#0ea5e9'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true }
                }
            }
        });
    }

    // Refresh icons
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
});
</script>
