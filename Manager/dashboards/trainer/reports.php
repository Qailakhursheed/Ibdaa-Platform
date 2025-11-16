<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">التقارير والإحصائيات</h2>
            <p class="text-slate-600 mt-1">تقارير شاملة عن أداء الطلاب والدورات</p>
        </div>
        <button onclick="exportAllReports()" 
            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold flex items-center gap-2">
            <i data-lucide="download" class="w-5 h-5"></i>
            تصدير جميع التقارير
        </button>
    </div>

    <!-- Report Type Selection -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">نوع التقرير</label>
                <select id="reportType" onchange="changeReportType()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="course">تقرير دورة</option>
                    <option value="student">تقرير طالب</option>
                    <option value="attendance">تقرير حضور</option>
                    <option value="grades">تقرير درجات</option>
                </select>
            </div>
            <div id="courseSelect">
                <label class="block text-sm font-semibold text-slate-700 mb-2">اختر الدورة</label>
                <select id="reportCourse" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر دورة</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="generateReport()" 
                    class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold">
                    <i data-lucide="file-text" class="w-4 h-4 inline"></i>
                    إنشاء التقرير
                </button>
            </div>
        </div>
    </div>

    <!-- Report Output -->
    <div id="reportOutput" class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="text-center py-12">
            <i data-lucide="file-text" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
            <h3 class="text-lg font-bold text-slate-700 mb-2">اختر نوع التقرير وأنشئه</h3>
            <p class="text-slate-500">سيظهر التقرير هنا بعد إنشائه</p>
        </div>
    </div>
</div>

<script>
async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('reportCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

function changeReportType() {
    const type = document.getElementById('reportType').value;
    // TODO: Show/hide appropriate filters based on report type
}

async function generateReport() {
    const type = document.getElementById('reportType').value;
    const courseId = document.getElementById('reportCourse').value;
    
    if (!courseId) {
        DashboardIntegration.ui.showToast('الرجاء اختيار الدورة', 'error');
        return;
    }
    
    const output = document.getElementById('reportOutput');
    output.innerHTML = `
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 mx-auto animate-spin text-emerald-600 mb-3"></i>
            <p class="text-slate-700 font-semibold">جاري إنشاء التقرير...</p>
        </div>
    `;
    lucide.createIcons();
    
    const response = await TrainerFeatures.reports.getCourseReport(courseId);
    
    if (response.success && response.data) {
        renderReport(response.data, type);
    } else {
        output.innerHTML = `
            <div class="text-center py-12">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-3"></i>
                <p class="text-red-600 font-semibold">فشل إنشاء التقرير</p>
            </div>
        `;
    }
    
    lucide.createIcons();
}

function renderReport(data, type) {
    const output = document.getElementById('reportOutput');
    
    if (type === 'course') {
        output.innerHTML = `
            <div class="space-y-6">
                <!-- Report Header -->
                <div class="flex items-center justify-between pb-6 border-b border-slate-200">
                    <div>
                        <h3 class="text-2xl font-bold text-slate-800">${data.course_name}</h3>
                        <p class="text-slate-600 mt-1">تقرير شامل عن الدورة</p>
                    </div>
                    <button onclick="exportReport('course', ${data.course_id})" 
                        class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 flex items-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        تصدير PDF
                    </button>
                </div>
                
                <!-- Statistics Grid -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <i data-lucide="users" class="w-6 h-6 text-emerald-600"></i>
                            <span class="text-3xl font-bold text-slate-800">${data.total_students || 0}</span>
                        </div>
                        <p class="text-sm text-slate-600">إجمالي الطلاب</p>
                    </div>
                    
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <i data-lucide="percent" class="w-6 h-6 text-emerald-600"></i>
                            <span class="text-3xl font-bold text-slate-800">${data.avg_attendance || 0}%</span>
                        </div>
                        <p class="text-sm text-slate-600">متوسط الحضور</p>
                    </div>
                    
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <i data-lucide="star" class="w-6 h-6 text-emerald-600"></i>
                            <span class="text-3xl font-bold text-slate-800">${data.avg_grade || 0}</span>
                        </div>
                        <p class="text-sm text-slate-600">متوسط الدرجات</p>
                    </div>
                    
                    <div class="bg-slate-50 rounded-xl p-6">
                        <div class="flex items-center justify-between mb-2">
                            <i data-lucide="clipboard-check" class="w-6 h-6 text-emerald-600"></i>
                            <span class="text-3xl font-bold text-slate-800">${data.completed_assignments || 0}%</span>
                        </div>
                        <p class="text-sm text-slate-600">إكمال الواجبات</p>
                    </div>
                </div>
                
                <!-- Performance Chart -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h4 class="text-lg font-bold text-slate-800 mb-4">توزيع الدرجات</h4>
                    <canvas id="performanceChart" height="80"></canvas>
                </div>
                
                <!-- Top Students -->
                <div class="bg-slate-50 rounded-xl p-6">
                    <h4 class="text-lg font-bold text-slate-800 mb-4">أفضل 5 طلاب</h4>
                    <div class="space-y-3">
                        ${(data.top_students || []).map((student, index) => `
                            <div class="flex items-center justify-between p-3 bg-white rounded-lg">
                                <div class="flex items-center gap-3">
                                    <span class="w-8 h-8 rounded-full bg-emerald-100 text-emerald-700 font-bold flex items-center justify-center">
                                        ${index + 1}
                                    </span>
                                    <span class="font-semibold text-slate-800">${student.name}</span>
                                </div>
                                <span class="text-lg font-bold text-emerald-600">${student.grade}</span>
                            </div>
                        `).join('')}
                    </div>
                </div>
            </div>
        `;
        
        // Draw chart
        const ctx = document.getElementById('performanceChart');
        if (ctx) {
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['90-100', '80-89', '70-79', '60-69', '0-59'],
                    datasets: [{
                        label: 'عدد الطلاب',
                        data: data.grade_distribution || [0, 0, 0, 0, 0],
                        backgroundColor: 'rgba(16, 185, 129, 0.5)',
                        borderColor: 'rgb(16, 185, 129)',
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: true,
                    plugins: {
                        legend: { display: false }
                    }
                }
            });
        }
    }
    
    lucide.createIcons();
}

async function exportReport(type, courseId) {
    const response = await TrainerFeatures.reports.exportReport(type, courseId, 'pdf');
    if (response.success) {
        DashboardIntegration.ui.showToast('تم تصدير التقرير بنجاح', 'success');
    } else {
        DashboardIntegration.ui.showToast('فشل تصدير التقرير', 'error');
    }
}

function exportAllReports() {
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة تصدير جميع التقارير قريباً', 'info');
}

// Initialize
loadCourses();
lucide.createIcons();
</script>
