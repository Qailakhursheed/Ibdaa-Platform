<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">سجل الحضور</h2>
            <p class="text-slate-600 mt-1">متابعة حضورك وغيابك في جميع الدورات</p>
        </div>
    </div>

    <!-- Attendance Summary -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar-check" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalPresent">0</span>
            </div>
            <p class="text-sm text-slate-600">أيام حضور</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar-x" class="w-8 h-8 text-red-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalAbsent">0</span>
            </div>
            <p class="text-sm text-slate-600">أيام غياب</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="attendanceRate">0%</span>
            </div>
            <p class="text-sm text-slate-600">نسبة الحضور</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="warningCount">0</span>
            </div>
            <p class="text-sm text-slate-600">إنذارات</p>
        </div>
    </div>

    <!-- Course Filter -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">اختر الدورة</label>
                <select id="attendanceCourse" onchange="loadAttendance()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الفترة</label>
                <select id="attendancePeriod" onchange="loadAttendance()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الفترات</option>
                    <option value="month">هذا الشهر</option>
                    <option value="week">هذا الأسبوع</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white border border-slate-200 rounded-xl">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">سجل الحضور التفصيلي</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">التاريخ</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الملاحظات</th>
                    </tr>
                </thead>
                <tbody id="attendanceTable">
                    <tr>
                        <td colspan="4" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-3"></i>
                            <p class="text-slate-500">جاري التحميل...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance Chart -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">الحضور خلال الشهر</h3>
        <canvas id="attendanceChart" height="80"></canvas>
    </div>
</div>

<script>
async function loadCourses() {
    const response = await StudentFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('attendanceCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

async function loadAttendance() {
    const courseId = document.getElementById('attendanceCourse').value;
    const response = await StudentFeatures.attendance.getMyAttendance(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        const attendance = response.data.records || [];
        
        // Update statistics
        const present = attendance.filter(a => a.status === 'present').length;
        const absent = attendance.filter(a => a.status === 'absent').length;
        const total = present + absent;
        const rate = total > 0 ? ((present / total) * 100).toFixed(0) : 0;
        
        document.getElementById('totalPresent').textContent = present;
        document.getElementById('totalAbsent').textContent = absent;
        document.getElementById('attendanceRate').textContent = rate + '%';
        document.getElementById('warningCount').textContent = absent >= 3 ? Math.floor(absent / 3) : 0;
        
        renderAttendance(attendance);
    }
    
    lucide.createIcons();
}

function renderAttendance(records) {
    const tbody = document.getElementById('attendanceTable');
    
    if (records.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="4" class="px-6 py-12 text-center">
                    <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                    <p class="text-slate-600">لا يوجد سجل حضور</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = records.map(record => `
        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
                <span class="text-sm font-semibold text-slate-800">${record.date}</span>
            </td>
            <td class="px-6 py-4">
                <span class="text-sm text-slate-700">${record.course_name}</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                    record.status === 'present' ? 'bg-emerald-100 text-emerald-700' :
                    record.status === 'absent' ? 'bg-red-100 text-red-700' :
                    'bg-amber-100 text-amber-700'
                }">
                    <i data-lucide="${
                        record.status === 'present' ? 'check-circle' :
                        record.status === 'absent' ? 'x-circle' :
                        'alert-circle'
                    }" class="w-3 h-3 inline"></i>
                    ${record.status === 'present' ? 'حاضر' : record.status === 'absent' ? 'غائب' : 'عذر'}
                </span>
            </td>
            <td class="px-6 py-4">
                <span class="text-sm text-slate-600">${record.notes || '-'}</span>
            </td>
        </tr>
    `).join('');
    
    lucide.createIcons();
}

// Create Attendance Chart
function createAttendanceChart() {
    const ctx = document.getElementById('attendanceChart');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['الأسبوع 1', 'الأسبوع 2', 'الأسبوع 3', 'الأسبوع 4'],
            datasets: [
                {
                    label: 'حاضر',
                    data: [4, 5, 4, 5],
                    backgroundColor: 'rgba(16, 185, 129, 0.5)',
                    borderColor: 'rgb(16, 185, 129)',
                    borderWidth: 2
                },
                {
                    label: 'غائب',
                    data: [1, 0, 1, 0],
                    backgroundColor: 'rgba(239, 68, 68, 0.5)',
                    borderColor: 'rgb(239, 68, 68)',
                    borderWidth: 2
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });
}

// Initialize
loadCourses();
loadAttendance();
createAttendanceChart();
</script>
