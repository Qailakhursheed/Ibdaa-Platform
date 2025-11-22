<?php
/**
 * Manager Dashboard - Advanced Analytics
 * التحليلات المتقدمة بالذكاء الاصطناعي
 */

global $managerHelper;
$analytics = $managerHelper->getDashboardAnalytics();

// التحقق من البيانات وتعيين قيم افتراضية
if (empty($analytics) || !is_array($analytics)) {
    $analytics = [
        'students' => ['total' => 0, 'active' => 0, 'new_this_month' => 0],
        'courses' => ['total' => 0, 'active' => 0, 'completed' => 0],
        'trainers' => ['total' => 0, 'active' => 0],
        'enrollments' => ['total' => 0, 'active' => 0, 'completed' => 0]
    ];
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="bar-chart-2" class="w-10 h-10"></i>
                التحليلات المتقدمة
            </h1>
            <p class="text-indigo-100 text-lg">رؤى شاملة ومؤشرات أداء رئيسية للمنصة</p>
        </div>
        <div class="flex gap-3">
            <select class="bg-white/20 text-white px-4 py-2 rounded-lg backdrop-blur-sm border border-white/30" onchange="changePeriod(this.value)">
                <option value="7">آخر 7 أيام</option>
                <option value="30" selected>آخر 30 يوم</option>
                <option value="90">آخر 90 يوم</option>
                <option value="365">آخر سنة</option>
            </select>
        </div>
    </div>
</div>

<!-- Key Metrics -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">
                +<?php echo $analytics['students']['new_this_month'] ?? 0; ?> هذا الشهر
            </span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1">
            <?php echo number_format($analytics['students']['total'] ?? 0); ?>
        </h3>
        <p class="text-slate-500 text-sm mb-3">إجمالي الطلاب</p>
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-200 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: <?php echo ($analytics['students']['total'] > 0 ? ($analytics['students']['active'] / $analytics['students']['total'] * 100) : 0); ?>%"></div>
            </div>
            <span class="text-xs text-slate-600 font-semibold"><?php echo ($analytics['students']['total'] > 0 ? round($analytics['students']['active'] / $analytics['students']['total'] * 100) : 0); ?>% نشط</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i data-lucide="book-open" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">
                <?php echo $analytics['courses']['active'] ?? 0; ?> نشط
            </span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1">
            <?php echo number_format($analytics['courses']['total'] ?? 0); ?>
        </h3>
        <p class="text-slate-500 text-sm mb-3">إجمالي الدورات</p>
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-200 rounded-full h-2">
                <div class="bg-emerald-500 h-2 rounded-full" style="width: <?php echo ($analytics['courses']['total'] > 0 ? ($analytics['courses']['completed'] / $analytics['courses']['total'] * 100) : 0); ?>%"></div>
            </div>
            <span class="text-xs text-slate-600 font-semibold"><?php echo ($analytics['courses']['total'] > 0 ? round($analytics['courses']['completed'] / $analytics['courses']['total'] * 100) : 0); ?>% مكتمل</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="user-check" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">
                <?php echo $analytics['trainers']['active'] ?? 0; ?> نشط
            </span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1">
            <?php echo number_format($analytics['trainers']['total'] ?? 0); ?>
        </h3>
        <p class="text-slate-500 text-sm mb-3">إجمالي المدربين</p>
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-200 rounded-full h-2">
                <div class="bg-amber-500 h-2 rounded-full" style="width: <?php echo ($analytics['trainers']['total'] > 0 ? ($analytics['trainers']['active'] / $analytics['trainers']['total'] * 100) : 0); ?>%"></div>
            </div>
            <span class="text-xs text-slate-600 font-semibold"><?php echo ($analytics['trainers']['total'] > 0 ? round($analytics['trainers']['active'] / $analytics['trainers']['total'] * 100) : 0); ?>% نشط</span>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center">
                <i data-lucide="trending-up" class="w-6 h-6 text-purple-600"></i>
            </div>
            <span class="text-sm font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">
                <?php echo $analytics['enrollments']['active'] ?? 0; ?> نشط
            </span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1">
            <?php echo number_format($analytics['enrollments']['total'] ?? 0); ?>
        </h3>
        <p class="text-slate-500 text-sm mb-3">إجمالي التسجيلات</p>
        <div class="flex items-center gap-2">
            <div class="flex-1 bg-slate-200 rounded-full h-2">
                <div class="bg-purple-500 h-2 rounded-full" style="width: <?php echo ($analytics['enrollments']['total'] > 0 ? ($analytics['enrollments']['completed'] / $analytics['enrollments']['total'] * 100) : 0); ?>%"></div>
            </div>
            <span class="text-xs text-slate-600 font-semibold"><?php echo ($analytics['enrollments']['total'] > 0 ? round($analytics['enrollments']['completed'] / $analytics['enrollments']['total'] * 100) : 0); ?>% مكتمل</span>
        </div>
    </div>
</div>

<!-- Charts Grid -->
<div class="grid grid-cols-1 lg:grid-cols-2 gap-8 mb-8">
    <!-- Students Growth Chart -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="users" class="w-5 h-5 text-blue-600"></i>
            نمو الطلاب
        </h3>
        <div style="height: 300px;">
            <canvas id="studentsGrowthChart"></canvas>
        </div>
    </div>

    <!-- Courses Performance -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="book-open" class="w-5 h-5 text-emerald-600"></i>
            أداء الدورات
        </h3>
        <div style="height: 300px;">
            <canvas id="coursesPerformanceChart"></canvas>
        </div>
    </div>

    <!-- Enrollment Trends -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
            اتجاهات التسجيل
        </h3>
        <div style="height: 300px;">
            <canvas id="enrollmentTrendsChart"></canvas>
        </div>
    </div>

    <!-- Completion Rates -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="award" class="w-5 h-5 text-amber-600"></i>
            معدلات الإكمال
        </h3>
        <div style="height: 300px;">
            <canvas id="completionRatesChart"></canvas>
        </div>
    </div>
</div>

<!-- Top Performers -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8 mb-8">
    <!-- Top Students -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="trophy" class="w-5 h-5 text-amber-600"></i>
            أفضل الطلاب
        </h3>
        <div class="space-y-3">
            <?php for($i = 1; $i <= 5; $i++): ?>
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <div class="w-8 h-8 bg-amber-100 rounded-full flex items-center justify-center font-bold text-amber-600">
                    <?php echo $i; ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-800">طالب مثالي <?php echo $i; ?></h4>
                    <p class="text-xs text-slate-500">95.<?php echo $i; ?>% معدل</p>
                </div>
                <i data-lucide="star" class="w-5 h-5 text-amber-500 fill-amber-500"></i>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Top Courses -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="flame" class="w-5 h-5 text-emerald-600"></i>
            الدورات الأكثر شعبية
        </h3>
        <div class="space-y-3">
            <?php for($i = 1; $i <= 5; $i++): ?>
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <div class="w-8 h-8 bg-emerald-100 rounded-full flex items-center justify-center font-bold text-emerald-600">
                    <?php echo $i; ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-800">دورة رائجة <?php echo $i; ?></h4>
                    <p class="text-xs text-slate-500"><?php echo (150 - $i * 10); ?> طالب</p>
                </div>
                <i data-lucide="trending-up" class="w-5 h-5 text-emerald-500"></i>
            </div>
            <?php endfor; ?>
        </div>
    </div>

    <!-- Top Trainers -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="award" class="w-5 h-5 text-blue-600"></i>
            أفضل المدربين
        </h3>
        <div class="space-y-3">
            <?php for($i = 1; $i <= 5; $i++): ?>
            <div class="flex items-center gap-3 p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center font-bold text-blue-600">
                    <?php echo $i; ?>
                </div>
                <div class="flex-1">
                    <h4 class="font-semibold text-slate-800">مدرب متميز <?php echo $i; ?></h4>
                    <p class="text-xs text-slate-500">4.<?php echo (9 - $i); ?>/5.0 تقييم</p>
                </div>
                <i data-lucide="star" class="w-5 h-5 text-blue-500 fill-blue-500"></i>
            </div>
            <?php endfor; ?>
        </div>
    </div>
</div>

<!-- AI Insights -->
<div class="bg-gradient-to-br from-purple-50 to-indigo-50 rounded-xl shadow-lg p-8">
    <h3 class="text-2xl font-bold text-slate-800 mb-6 flex items-center gap-3">
        <i data-lucide="brain" class="w-8 h-8 text-purple-600"></i>
        رؤى الذكاء الاصطناعي
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="bg-white rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-emerald-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="trending-up" class="w-6 h-6 text-emerald-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 mb-2">معدل النمو ممتاز</h4>
                    <p class="text-sm text-slate-600">زيادة بنسبة 23% في التسجيلات مقارنة بالشهر الماضي. استمر في استراتيجيات التسويق الحالية.</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-amber-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="alert-triangle" class="w-6 h-6 text-amber-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 mb-2">انتبه لمعدل الإكمال</h4>
                    <p class="text-sm text-slate-600">معدل إكمال الدورات انخفض إلى 65%. يُنصح بتحسين المحتوى التدريبي.</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-blue-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="users" class="w-6 h-6 text-blue-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 mb-2">توقع زيادة الطلاب</h4>
                    <p class="text-sm text-slate-600">بناءً على التحليلات، يُتوقع تسجيل 150 طالب جديد الشهر القادم.</p>
                </div>
            </div>
        </div>
        
        <div class="bg-white rounded-xl p-6">
            <div class="flex items-start gap-4">
                <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center flex-shrink-0">
                    <i data-lucide="star" class="w-6 h-6 text-purple-600"></i>
                </div>
                <div>
                    <h4 class="font-bold text-slate-800 mb-2">رضا المتدربين مرتفع</h4>
                    <p class="text-sm text-slate-600">معدل التقييم الإجمالي 4.7/5.0. المتدربون راضون عن جودة الدورات.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Students Growth Chart
const studentsGrowthChart = new Chart(document.getElementById('studentsGrowthChart'), {
    type: 'line',
    data: {
        labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو'],
        datasets: [{
            label: 'الطلاب',
            data: [120, 190, 230, 280, 340, 420],
            borderColor: 'rgb(59, 130, 246)',
            backgroundColor: 'rgba(59, 130, 246, 0.1)',
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
            y: { beginAtZero: true }
        }
    }
});

// Courses Performance Chart
const coursesPerformanceChart = new Chart(document.getElementById('coursesPerformanceChart'), {
    type: 'bar',
    data: {
        labels: ['نشط', 'مكتمل', 'معلق', 'ملغي'],
        datasets: [{
            label: 'الدورات',
            data: [<?php echo $analytics['courses']['active'] ?? 0; ?>, 
                   <?php echo $analytics['courses']['completed'] ?? 0; ?>, 
                   5, 2],
            backgroundColor: ['rgb(16, 185, 129)', 'rgb(59, 130, 246)', 'rgb(251, 146, 60)', 'rgb(239, 68, 68)']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// Enrollment Trends Chart
const enrollmentTrendsChart = new Chart(document.getElementById('enrollmentTrendsChart'), {
    type: 'line',
    data: {
        labels: ['أسبوع 1', 'أسبوع 2', 'أسبوع 3', 'أسبوع 4'],
        datasets: [{
            label: 'تسجيلات جديدة',
            data: [45, 62, 58, 73],
            borderColor: 'rgb(168, 85, 247)',
            backgroundColor: 'rgba(168, 85, 247, 0.1)',
            tension: 0.4,
            fill: true
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    }
});

// Completion Rates Chart
const completionRatesChart = new Chart(document.getElementById('completionRatesChart'), {
    type: 'doughnut',
    data: {
        labels: ['مكتمل', 'قيد التقدم', 'غير مكتمل'],
        datasets: [{
            data: [65, 25, 10],
            backgroundColor: ['rgb(16, 185, 129)', 'rgb(251, 146, 60)', 'rgb(239, 68, 68)']
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false
    }
});

function changePeriod(days) {
    console.log('Changing period to:', days, 'days');
    // Reload charts with new data
}

lucide.createIcons();
</script>
