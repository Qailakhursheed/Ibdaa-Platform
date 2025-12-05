<?php
// Load student data using Helper Class
global $studentHelper, $userId, $userName;

$courses = $studentHelper->getMyCourses();
$gpaData = $studentHelper->getGPA();
$attendanceData = $studentHelper->getAttendanceRate();
$recentCourses = array_slice($courses, 0, 4);

$stats = [
    'enrolled_courses' => count(array_filter($courses, fn($c) => $c['enrollment_status'] === 'active')),
    'completed_courses' => count(array_filter($courses, fn($c) => $c['enrollment_status'] === 'completed')),
    'gpa' => $gpaData['gpa'],
    'attendance_rate' => $attendanceData['rate']
];
?>

<div class="space-y-6">
    <!-- Welcome Banner - PHP Data -->
    <div class="bg-gradient-to-br from-emerald-500 to-green-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h1 class="text-3xl font-bold mb-2">مرحباً، <?php echo htmlspecialchars($userName); ?> 👋</h1>
                <p class="text-emerald-100 text-lg">استمر في تحقيق أهدافك التعليمية - معدلك: <?php echo number_format($stats['gpa'], 2); ?></p>
            </div>
            <div class="hidden md:block">
                <i data-lucide="graduation-cap" class="w-24 h-24 opacity-20"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-8">
        <button onclick="location.href='?page=courses'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
            <i data-lucide="book-open" class="w-8 h-8 mx-auto mb-2 text-emerald-600"></i>
            <p class="font-semibold text-slate-800">دوراتي</p>
        </button>
        <button onclick="location.href='?page=assignments'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
            <i data-lucide="clipboard-list" class="w-8 h-8 mx-auto mb-2 text-amber-600"></i>
            <p class="font-semibold text-slate-800">الواجبات</p>
        </button>
        <button onclick="location.href='?page=grades'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
            <i data-lucide="award" class="w-8 h-8 mx-auto mb-2 text-sky-600"></i>
            <p class="font-semibold text-slate-800">درجاتي</p>
        </button>
        <button onclick="location.href='?page=id-card'" class="bg-white rounded-xl shadow-lg p-4 hover:shadow-xl transition-shadow text-center">
            <i data-lucide="credit-card" class="w-8 h-8 mx-auto mb-2 text-violet-600"></i>
            <p class="font-semibold text-slate-800">بطاقتي</p>
        </button>
    </div>

    <!-- Statistics Cards - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <!-- Enrolled Courses -->
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-white/20 flex items-center justify-center">
                    <i data-lucide="book-open" class="w-6 h-6 text-white"></i>
                </div>
            </div>
            <h3 class="text-4xl font-bold mb-1"><?php echo $stats['enrolled_courses']; ?></h3>
            <p class="text-emerald-100 text-sm font-semibold">دورة مسجلة</p>
        </div>

        <!-- GPA -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-sky-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-sky-100 flex items-center justify-center">
                    <i data-lucide="star" class="w-6 h-6 text-sky-600"></i>
                </div>
            </div>
            <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo number_format($stats['gpa'], 2); ?></h3>
            <p class="text-slate-500 text-sm font-semibold">المعدل التراكمي</p>
        </div>

        <!-- Attendance -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                    <i data-lucide="calendar-check" class="w-6 h-6 text-amber-600"></i>
                </div>
            </div>
            <h3 class="text-3xl font-bold text-slate-800 mb-1"><?php echo number_format($stats['attendance_rate'], 1); ?>%</h3>
            <p class="text-slate-500 text-sm">نسبة الحضور</p>
        </div>

        <!-- Completed Courses -->
        <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-violet-500">
            <div class="flex items-center justify-between mb-4">
                <div class="w-12 h-12 rounded-lg bg-violet-100 flex items-center justify-center">
                    <i data-lucide="check-circle" class="w-6 h-6 text-violet-600"></i>
                </div>
            </div>
            <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $stats['completed_courses']; ?></h3>
            <p class="text-slate-500 text-sm font-semibold">دورات مكتملة</p>
        </div>
    </div>

    <!-- Interactive Charts from Python API -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <!-- Courses Progress Chart -->
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="trending-up" class="w-6 h-6 text-blue-600"></i>
                تقدم الدورات
            </h3>
            <div id="coursesProgressChart" class="h-80"></div>
        </div>
        
        <!-- Grades Overview Chart -->
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="bar-chart-3" class="w-6 h-6 text-green-600"></i>
                نظرة على الدرجات
            </h3>
            <div id="gradesOverviewChart" class="h-80"></div>
        </div>
    </div>
    
    <!-- Attendance Rate Chart -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
        <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="calendar-check" class="w-6 h-6 text-purple-600"></i>
            معدل الحضور حسب الدورة
        </h3>
        <div id="attendanceRateChart" class="h-80"></div>
    </div>

    <!-- Recent Activity & Upcoming -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Recent Courses -->
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">دوراتي النشطة</h3>
            <div id="recentCourses" class="space-y-3">
                <div class="text-center py-4">
                    <i data-lucide="loader" class="w-6 h-6 mx-auto animate-spin text-slate-400"></i>
                </div>
            </div>
        </div>
        
        <!-- Upcoming Deadlines -->
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">المواعيد القادمة</h3>
            <div id="upcomingDeadlines" class="space-y-3">
                <div class="text-center py-4">
                    <i data-lucide="loader" class="w-6 h-6 mx-auto animate-spin text-slate-400"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Courses - PHP Data -->
<div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i data-lucide="layers" class="w-6 h-6 text-indigo-600"></i>
        دوراتي الأخيرة
    </h3>
    
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <?php foreach ($recentCourses as $course): ?>
        <div class="border border-slate-200 rounded-lg p-4 hover:border-blue-300 hover:shadow-md transition-all">
            <div class="flex items-start justify-between mb-3">
                <span class="px-3 py-1 text-xs font-semibold rounded-full 
                    <?php echo $course['enrollment_status'] === 'active' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'; ?>">
                    <?php echo $course['enrollment_status'] === 'active' ? 'نشط' : 'مكتمل'; ?>
                </span>
            </div>
            
            <h4 class="font-bold text-slate-800 mb-2 line-clamp-2"><?php echo htmlspecialchars($course['course_name']); ?></h4>
            <p class="text-sm text-slate-600 mb-3">المدرب: <?php echo htmlspecialchars($course['trainer_name'] ?? 'غير محدد'); ?></p>
            
            <!-- Progress Bar -->
            <div class="space-y-1">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-slate-600">التقدم</span>
                    <span class="font-semibold text-blue-600"><?php echo $course['progress'] ?? 0; ?>%</span>
                </div>
                <div class="w-full bg-slate-200 rounded-full h-2">
                    <div class="bg-blue-600 h-2 rounded-full transition-all" 
                         style="width: <?php echo $course['progress'] ?? 0; ?>%"></div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
        
        <?php if (empty($recentCourses)): ?>
        <div class="col-span-full text-center py-12">
            <i data-lucide="inbox" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
            <p class="text-slate-500 text-lg">لم تسجل في أي دورات بعد</p>
            <a href="?page=courses" class="inline-block mt-4 px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                تصفح الدورات المتاحة
            </a>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Plotly.js for interactive charts -->
<script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
<script src="<?php echo $managerBaseUrl; ?>/assets/js/chart-loader.js"></script>

<script>
// Load interactive charts from Python API
document.addEventListener('DOMContentLoaded', function() {
    const studentId = <?php echo $userId; ?>;
    
    // Load Courses Progress Chart
    ChartLoader.loadStudentCoursesProgress('coursesProgressChart', studentId);
    
    // Load Grades Overview Chart
    ChartLoader.loadStudentGradesOverview('gradesOverviewChart', studentId);
    
    // Load Attendance Rate Chart
    ChartLoader.loadStudentAttendanceRate('attendanceRateChart', studentId);
    
    // Initialize Lucide icons
    lucide.createIcons();
});

// Load statistics function
async function loadStatistics() {
    // Load courses
    const coursesResponse = await StudentFeatures.courses.getMyCourses();
    if (coursesResponse.success && coursesResponse.data) {
        document.getElementById('enrolledCourses').textContent = coursesResponse.data.length;
        renderRecentCourses(coursesResponse.data.slice(0, 5));
    }
    
    // Load GPA
    const gpaResponse = await StudentFeatures.grades.getGPA();
    if (gpaResponse.success && gpaResponse.data) {
        document.getElementById('gpa').textContent = gpaResponse.data.gpa.toFixed(2);
    }
    
    // Load attendance
    const attendanceResponse = await StudentFeatures.attendance.getMyAttendance();
    if (attendanceResponse.success && attendanceResponse.data) {
        const rate = attendanceResponse.data.attendance_rate || 0;
        document.getElementById('attendanceRate').textContent = rate + '%';
    }
    
    // Load pending assignments
    const assignmentsResponse = await StudentFeatures.assignments.getMyAssignments();
    if (assignmentsResponse.success && assignmentsResponse.data) {
        const pending = assignmentsResponse.data.filter(a => !a.submitted).length;
        document.getElementById('pendingAssignments').textContent = pending;
        renderUpcomingDeadlines(assignmentsResponse.data.filter(a => !a.submitted).slice(0, 5));
    }
    
    lucide.createIcons();
}

function renderRecentCourses(courses) {
    const container = document.getElementById('recentCourses');
    
    if (courses.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-500">لا توجد دورات</p>';
        return;
    }
    
    container.innerHTML = courses.map(course => `
        <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors cursor-pointer">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-amber-100 rounded-lg flex items-center justify-center">
                    <i data-lucide="book-open" class="w-5 h-5 text-amber-600"></i>
                </div>
                <div>
                    <p class="font-semibold text-slate-800 text-sm">${course.course_name}</p>
                    <p class="text-xs text-slate-500">${course.trainer_name || 'مدرب'}</p>
                </div>
            </div>
            <div class="text-left">
                <p class="text-sm font-bold text-amber-600">${course.progress || 0}%</p>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function renderUpcomingDeadlines(assignments) {
    const container = document.getElementById('upcomingDeadlines');
    
    if (assignments.length === 0) {
        container.innerHTML = '<p class="text-center text-slate-500">لا توجد مواعيد قادمة</p>';
        return;
    }
    
    container.innerHTML = assignments.map(assignment => {
        const dueDate = new Date(assignment.due_date);
        const today = new Date();
        const daysLeft = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        const isUrgent = daysLeft <= 2;
        
        return `
            <div class="flex items-center justify-between p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition-colors">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-${isUrgent ? 'red' : 'blue'}-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="clipboard-list" class="w-5 h-5 text-${isUrgent ? 'red' : 'blue'}-600"></i>
                    </div>
                    <div>
                        <p class="font-semibold text-slate-800 text-sm">${assignment.title}</p>
                        <p class="text-xs text-slate-500">${assignment.course_name}</p>
                    </div>
                </div>
                <div class="text-left">
                    <p class="text-sm font-bold ${isUrgent ? 'text-red-600' : 'text-slate-700'}">${daysLeft} يوم</p>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

// Create GPA Chart
function createGPAChart() {
    const ctx = document.getElementById('gpaChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['الفصل 1', 'الفصل 2', 'الفصل 3', 'الفصل 4'],
            datasets: [{
                label: 'المعدل التراكمي',
                data: [3.2, 3.5, 3.7, 3.8],
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, max: 4.0 }
            }
        }
    });
}

// Create Progress Chart
function createProgressChart() {
    const ctx = document.getElementById('progressChart');
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: ['مكتمل', 'قيد التقدم', 'لم يبدأ'],
            datasets: [{
                data: [45, 35, 20],
                backgroundColor: [
                    'rgb(16, 185, 129)',
                    'rgb(245, 158, 11)',
                    'rgb(148, 163, 184)'
                ]
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

// Initialize with conditional loading
if (typeof StudentFeatures !== 'undefined') {
    loadStatistics();
    createGPAChart();
    createProgressChart();
} else {
    console.log('Waiting for StudentFeatures to load...');
    setTimeout(() => {
        if (typeof StudentFeatures !== 'undefined') {
            loadStatistics();
            createGPAChart();
            createProgressChart();
        } else {
            console.error('StudentFeatures failed to load');
        }
    }, 1000);
}
</script>
