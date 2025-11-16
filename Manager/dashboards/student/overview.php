<div class="space-y-6">
    <!-- Welcome Banner -->
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl p-8 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-3xl font-bold mb-2">مرحباً بك مجدداً! 👋</h2>
                <p class="text-amber-100">استمر في تحقيق أهدافك التعليمية</p>
            </div>
            <div class="hidden md:block">
                <i data-lucide="graduation-cap" class="w-24 h-24 opacity-30"></i>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <button onclick="window.location.href='?page=courses'" 
            class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-amber-200 transition-colors">
                <i data-lucide="book-open" class="w-6 h-6 text-amber-600"></i>
            </div>
            <p class="font-semibold text-slate-800">دوراتي</p>
        </button>
        
        <button onclick="window.location.href='?page=assignments'" 
            class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-blue-200 transition-colors">
                <i data-lucide="clipboard-list" class="w-6 h-6 text-blue-600"></i>
            </div>
            <p class="font-semibold text-slate-800">الواجبات</p>
        </button>
        
        <button onclick="window.location.href='?page=grades'" 
            class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-emerald-200 transition-colors">
                <i data-lucide="star" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <p class="font-semibold text-slate-800">درجاتي</p>
        </button>
        
        <button onclick="window.location.href='?page=id-card'" 
            class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow text-center group">
            <div class="w-12 h-12 bg-purple-100 rounded-xl flex items-center justify-center mx-auto mb-3 group-hover:bg-purple-200 transition-colors">
                <i data-lucide="credit-card" class="w-6 h-6 text-purple-600"></i>
            </div>
            <p class="font-semibold text-slate-800">بطاقتي</p>
        </button>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="enrolledCourses">0</span>
            </div>
            <p class="text-sm text-slate-600">دورة مسجلة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="gpa">0.0</span>
            </div>
            <p class="text-sm text-slate-600">المعدل التراكمي</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar-check" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="attendanceRate">0%</span>
            </div>
            <p class="text-sm text-slate-600">نسبة الحضور</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clipboard-list" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="pendingAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">واجبات معلقة</p>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- GPA Trend -->
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">تطور المعدل التراكمي</h3>
            <canvas id="gpaChart" height="200"></canvas>
        </div>
        
        <!-- Course Progress -->
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <h3 class="text-lg font-bold text-slate-800 mb-4">تقدم الدورات</h3>
            <canvas id="progressChart" height="200"></canvas>
        </div>
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

<script>
// Load statistics
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
            maintainAspectRatio: false,
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
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
}

// Initialize
loadStatistics();
createGPAChart();
createProgressChart();
</script>
