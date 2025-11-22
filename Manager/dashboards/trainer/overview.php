<?php
/**
 * Trainer Dashboard - Overview Page
 * صفحة النظرة العامة للمدرب
 */

// Load trainer data using TrainerHelper
global $trainerHelper, $stats;
$myCourses = $trainerHelper->getMyCourses();
$recentStudents = array_slice($trainerHelper->getMyStudents(), 0, 5); // Latest 5 students
?>

<!-- Welcome Banner - PHP Data -->
<div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2">مرحباً، <?php echo htmlspecialchars($userName); ?> 👋</h1>
            <p class="text-emerald-100 text-lg">نظرة عامة على دوراتك وطلابك - <?php echo count($myCourses); ?> دورة</p>
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

<!-- Statistics Cards - PHP Data -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- My Courses -->
    <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl shadow-xl p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-white/20 flex items-center justify-center">
                <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold mb-1"><?php echo $stats['total_courses']; ?></h3>
        <p class="text-emerald-100 text-sm font-semibold">دوراتي</p>
    </div>

    <!-- Active Students -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-sky-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-sky-600"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $stats['active_students']; ?></h3>
        <p class="text-slate-500 text-sm font-semibold">طالب نشط</p>
    </div>

    <!-- Materials -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="file-text" class="w-6 h-6 text-amber-600"></i>
            </div>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $stats['total_materials']; ?></h3>
        <p class="text-slate-500 text-sm font-semibold">مادة تدريبية</p>
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
        <div style="height: 300px; position: relative;">
            <canvas id="attendanceChart"></canvas>
        </div>
    </div>

    <!-- Students Performance -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="pie-chart" class="w-5 h-5 text-sky-600"></i>
            أداء الطلاب
        </h3>
        <div style="height: 300px; position: relative;">
            <canvas id="performanceChart"></canvas>
        </div>
    </div>
</div>

<!-- Recent Activities -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
    <!-- My Courses - PHP -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="book-open" class="w-5 h-5 text-emerald-600"></i>
                دوراتي النشطة
            </h3>
            <a href="?page=courses" class="text-sm text-emerald-600 hover:text-emerald-700 font-semibold">عرض الكل</a>
        </div>
        <div class="space-y-3">
            <?php if (!empty($myCourses)): ?>
                <?php foreach (array_slice($myCourses, 0, 5) as $course): ?>
                    <div class="p-4 border-2 border-slate-200 rounded-lg hover:border-emerald-300 transition-colors">
                        <div class="flex items-center justify-between">
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($course['course_name']); ?></h4>
                                <p class="text-sm text-slate-500 mt-1"><?php echo $course['enrolled_count'] ?? 0; ?> طالب</p>
                            </div>
                            <a href="?page=courses&id=<?php echo $course['course_id']; ?>" class="text-emerald-600 hover:text-emerald-700">
                                <i data-lucide="arrow-left" class="w-5 h-5"></i>
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-slate-500 py-8">لا توجد دورات</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Students - PHP -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="users" class="w-5 h-5 text-sky-600"></i>
                طلاب جدد
            </h3>
            <a href="?page=students" class="text-sm text-sky-600 hover:text-sky-700 font-semibold">عرض الكل</a>
        </div>
        <div class="space-y-3">
            <?php if (!empty($recentStudents)): ?>
                <?php foreach ($recentStudents as $student): ?>
                    <div class="p-4 border-2 border-slate-200 rounded-lg hover:border-sky-300 transition-colors">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo $student['photo'] ? htmlspecialchars($student['photo']) : $platformBaseUrl . '/photos/default-avatar.png'; ?>" 
                                 class="w-10 h-10 rounded-full object-cover border-2 border-slate-200">
                            <div class="flex-1">
                                <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></h4>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($student['course_name'] ?? 'غير محدد'); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-center text-slate-500 py-8">لا يوجد طلاب</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Charts loaded via Python API -->
<script src="<?php echo $platformBaseUrl; ?>/js/chart-loader.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Load Plotly charts from Python API
    if (typeof ChartLoader !== 'undefined') {
        ChartLoader.loadTrainerChart('attendance_weekly', 'attendanceChart');
        ChartLoader.loadTrainerChart('performance_distribution', 'performanceChart');
    }
});
</script>
