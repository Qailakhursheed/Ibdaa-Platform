<?php
// Load course report using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$selectedCourse = $_GET['course_id'] ?? null;
$report = $selectedCourse ? $trainerHelper->getCourseReport($selectedCourse) : null;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">التقارير والإحصائيات</h2>
            <p class="text-slate-600 mt-1">تقارير شاملة عن أداء الطلاب والدورات - <?php echo count($myCourses); ?> دورة</p>
        </div>
    </div>

    <!-- Course Selection - PHP Form -->
    <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="page" value="reports">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    اختر الدورة
                </label>
                <select name="course_id" class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg" onchange="this.form.submit()">
                    <option value="">-- اختر دورة لعرض التقرير --</option>
                    <?php foreach ($myCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selectedCourse == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Report Output - PHP Rendered -->
    <?php if ($report): ?>
        <div class="bg-white border-2 border-slate-200 rounded-xl p-8 shadow-lg">
            <!-- Report Header -->
            <div class="flex items-center justify-between pb-6 border-b-2 border-slate-200 mb-8">
                <div>
                    <h3 class="text-3xl font-bold text-slate-800"><?php echo htmlspecialchars($report['course_name']); ?></h3>
                    <p class="text-slate-600 mt-2 text-lg">تقرير شامل عن أداء الدورة</p>
                </div>
                <div class="w-16 h-16 rounded-full bg-emerald-100 flex items-center justify-center">
                    <i data-lucide="file-text" class="w-8 h-8 text-emerald-600"></i>
                </div>
            </div>
            
            <!-- Statistics Grid -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i data-lucide="users" class="w-8 h-8"></i>
                    </div>
                    <p class="text-2xl text-emerald-100 mb-1">إجمالي الطلاب</p>
                    <span class="text-5xl font-bold"><?php echo $report['total_students'] ?? 0; ?></span>
                </div>
                
                <div class="bg-white border-2 border-sky-200 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i data-lucide="percent" class="w-8 h-8 text-sky-600"></i>
                    </div>
                    <p class="text-lg text-slate-600 mb-1">متوسط الحضور</p>
                    <span class="text-5xl font-bold text-slate-800"><?php echo round($report['avg_attendance'] ?? 0, 1); ?>%</span>
                </div>
                
                <div class="bg-white border-2 border-amber-200 rounded-xl p-6 shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <i data-lucide="star" class="w-8 h-8 text-amber-600"></i>
                    </div>
                    <p class="text-lg text-slate-600 mb-1">متوسط الدرجات</p>
                    <span class="text-5xl font-bold text-slate-800"><?php echo round($report['avg_grade'] ?? 0, 1); ?></span>
                </div>
            </div>
            
            <!-- Top Students -->
            <div class="bg-slate-50 rounded-xl p-6 mb-8">
                <h4 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="trophy" class="w-6 h-6 text-amber-500"></i>
                    أفضل 5 طلاب
                </h4>
                <div class="space-y-3">
                    <?php if (!empty($report['top_students'])): ?>
                        <?php foreach ($report['top_students'] as $index => $student): ?>
                            <div class="flex items-center justify-between p-4 bg-white rounded-lg shadow hover:shadow-md transition-shadow">
                                <div class="flex items-center gap-4">
                                    <span class="w-10 h-10 rounded-full bg-gradient-to-br from-emerald-500 to-green-600 text-white font-bold text-lg flex items-center justify-center shadow">
                                        <?php echo $index + 1; ?>
                                    </span>
                                    <span class="font-bold text-lg text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                </div>
                                <span class="text-2xl font-bold text-emerald-600"><?php echo round($student['final_grade'], 1); ?></span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p class="text-center text-slate-500 py-8 text-lg">لا توجد درجات مسجلة بعد</p>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Performance Chart Placeholder -->
            <div class="bg-slate-50 rounded-xl p-6">
                <h4 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-2">
                    <i data-lucide="bar-chart" class="w-6 h-6 text-emerald-600"></i>
                    توزيع الدرجات
                </h4>
                <div style="height: 300px;" id="gradeDistributionChart"></div>
            </div>
        </div>
    <?php else: ?>
        <div class="bg-white border-2 border-slate-200 rounded-xl p-12 text-center shadow-lg">
            <i data-lucide="file-text" class="w-24 h-24 mx-auto text-slate-400 mb-4"></i>
            <h3 class="text-2xl font-bold text-slate-700 mb-2">اختر دورة لعرض التقرير</h3>
            <p class="text-slate-500 text-lg">سيظهر التقرير التفصيلي هنا بعد اختيار الدورة</p>
        </div>
    <?php endif; ?>
</div>

<!-- Charts via Python API -->
<?php if ($report): ?>
<script src="<?php echo $platformBaseUrl; ?>/js/chart-loader.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Load grade distribution chart from Python API
    if (typeof ChartLoader !== 'undefined' && document.getElementById('gradeDistributionChart')) {
        ChartLoader.loadTrainerChart('grade_distribution', 'gradeDistributionChart', {
            course_id: <?php echo $selectedCourse; ?>,
            distribution: <?php echo json_encode($report['grade_distribution']); ?>
        });
    }
});
</script>
<?php endif; ?>
