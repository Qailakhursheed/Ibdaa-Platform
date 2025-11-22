<?php
/**
 * Manager - Reports and Analytics
 * التقارير والتحليلات
 */

global $managerHelper, $conn;

$report_type = $_GET['type'] ?? 'overview';
$date_range = $_GET['range'] ?? 'month';

// Calculate date range
$end_date = date('Y-m-d');
switch ($date_range) {
    case 'week':
        $start_date = date('Y-m-d', strtotime('-7 days'));
        break;
    case 'month':
        $start_date = date('Y-m-d', strtotime('-30 days'));
        break;
    case 'year':
        $start_date = date('Y-m-d', strtotime('-365 days'));
        break;
    default:
        $start_date = date('Y-m-d', strtotime('-30 days'));
}

// Get overview statistics
$stats = $managerHelper->getDashboardAnalytics();

// Get enrollment trends
$enrollment_trends = [];
$result = $conn->query("
    SELECT DATE(enrollment_date) as date, COUNT(*) as count
    FROM enrollments
    WHERE enrollment_date BETWEEN '{$start_date}' AND '{$end_date}'
    GROUP BY DATE(enrollment_date)
    ORDER BY date
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $enrollment_trends[] = $row;
    }
}

// Get top courses
$top_courses = [];
$result = $conn->query("
    SELECT c.name as course_name, COUNT(e.enrollment_id) as enrollments,
           AVG(e.final_grade) as avg_grade
    FROM courses c
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    GROUP BY c.course_id
    ORDER BY enrollments DESC
    LIMIT 10
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_courses[] = $row;
    }
}

// Get top trainers
$top_trainers = [];
$result = $conn->query("
    SELECT u.full_name, COUNT(DISTINCT c.course_id) as courses,
           COUNT(DISTINCT e.user_id) as students,
           AVG(e.final_grade) as avg_grade
    FROM users u
    JOIN courses c ON u.id = c.trainer_id
    LEFT JOIN enrollments e ON c.course_id = e.course_id
    WHERE u.role = 'trainer'
    GROUP BY u.id
    ORDER BY students DESC
    LIMIT 10
");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $top_trainers[] = $row;
    }
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-slate-700 to-slate-900 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="bar-chart" class="w-10 h-10"></i>
        التقارير والتحليلات
    </h1>
    <p class="text-slate-300 text-lg">تقارير شاملة عن أداء المنصة</p>
</div>

<!-- Report Type Tabs -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-6 p-4">
    <div class="flex gap-4">
        <a href="?page=reports&type=overview&range=<?php echo $date_range; ?>" 
           class="px-6 py-3 rounded-lg <?php echo $report_type === 'overview' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?> transition">
            <i data-lucide="layout" class="w-4 h-4 inline"></i> نظرة عامة
        </a>
        <a href="?page=reports&type=students&range=<?php echo $date_range; ?>" 
           class="px-6 py-3 rounded-lg <?php echo $report_type === 'students' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?> transition">
            <i data-lucide="users" class="w-4 h-4 inline"></i> الطلاب
        </a>
        <a href="?page=reports&type=courses&range=<?php echo $date_range; ?>" 
           class="px-6 py-3 rounded-lg <?php echo $report_type === 'courses' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?> transition">
            <i data-lucide="book" class="w-4 h-4 inline"></i> الدورات
        </a>
        <a href="?page=reports&type=trainers&range=<?php echo $date_range; ?>" 
           class="px-6 py-3 rounded-lg <?php echo $report_type === 'trainers' ? 'bg-slate-700 text-white' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'; ?> transition">
            <i data-lucide="user-check" class="w-4 h-4 inline"></i> المدربون
        </a>
    </div>
</div>

<!-- Date Range Selector -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 mb-8 p-4">
    <form method="GET" class="flex gap-4 items-center">
        <input type="hidden" name="page" value="reports">
        <input type="hidden" name="type" value="<?php echo $report_type; ?>">
        <span class="text-slate-600">الفترة الزمنية:</span>
        <select name="range" onchange="this.form.submit()" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="week" <?php echo $date_range === 'week' ? 'selected' : ''; ?>>آخر أسبوع</option>
            <option value="month" <?php echo $date_range === 'month' ? 'selected' : ''; ?>>آخر شهر</option>
            <option value="year" <?php echo $date_range === 'year' ? 'selected' : ''; ?>>آخر سنة</option>
        </select>
        <span class="text-sm text-slate-500">من <?php echo date('Y/m/d', strtotime($start_date)); ?> إلى <?php echo date('Y/m/d', strtotime($end_date)); ?></span>
    </form>
</div>

<!-- Overview Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['students']['total'] ?? 0; ?></h3>
        <p class="text-slate-600">إجمالي الطلاب</p>
        <p class="text-sm text-green-600 mt-2">
            <i data-lucide="trending-up" class="w-4 h-4 inline"></i>
            +<?php echo $stats['students']['new_this_month'] ?? 0; ?> هذا الشهر
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['courses']['total'] ?? 0; ?></h3>
        <p class="text-slate-600">إجمالي الدورات</p>
        <p class="text-sm text-blue-600 mt-2">
            <?php echo $stats['courses']['active'] ?? 0; ?> دورة نشطة
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['trainers']['total'] ?? 0; ?></h3>
        <p class="text-slate-600">إجمالي المدربين</p>
        <p class="text-sm text-green-600 mt-2">
            <?php echo $stats['trainers']['active'] ?? 0; ?> مدرب نشط
        </p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['enrollments']['total'] ?? 0; ?></h3>
        <p class="text-slate-600">إجمالي التسجيلات</p>
        <p class="text-sm text-blue-600 mt-2">
            <?php echo $stats['enrollments']['completed'] ?? 0; ?> مكتمل
        </p>
    </div>
</div>

<!-- Top Courses -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden mb-8">
    <div class="px-6 py-4 border-b bg-slate-50">
        <h2 class="text-xl font-bold text-slate-800">أفضل الدورات</h2>
    </div>
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">عدد الطلاب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">متوسط الدرجات</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($top_courses as $course): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($course['course_name']); ?></td>
                    <td class="px-6 py-4 text-slate-600"><?php echo $course['enrollments']; ?> طالب</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full bg-green-100 text-green-700 text-sm font-bold">
                            <?php echo number_format($course['avg_grade'] ?? 0, 1); ?>%
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Top Trainers -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="px-6 py-4 border-b bg-slate-50">
        <h2 class="text-xl font-bold text-slate-800">أفضل المدربين</h2>
    </div>
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">المدرب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">عدد الدورات</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">عدد الطلاب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">متوسط الدرجات</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($top_trainers as $trainer): ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4 font-medium"><?php echo htmlspecialchars($trainer['full_name']); ?></td>
                    <td class="px-6 py-4 text-slate-600"><?php echo $trainer['courses']; ?> دورة</td>
                    <td class="px-6 py-4 text-slate-600"><?php echo $trainer['students']; ?> طالب</td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full bg-blue-100 text-blue-700 text-sm font-bold">
                            <?php echo number_format($trainer['avg_grade'] ?? 0, 1); ?>%
                        </span>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
