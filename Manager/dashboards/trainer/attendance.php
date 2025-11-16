<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">تسجيل الحضور</h2>
            <p class="text-slate-600 mt-1">إدارة حضور وغياب الطلاب</p>
        </div>
        <button onclick="recordNewAttendance()" 
            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            تسجيل حضور جديد
        </button>
    </div>

    <!-- Date and Course Selection -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">التاريخ</label>
                <input type="date" id="attendanceDate" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="attendanceCourse" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر الدورة</option>
                </select>
            </div>
            <div class="flex items-end">
                <button onclick="loadAttendanceHistory()" 
                    class="w-full px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold">
                    عرض السجل
                </button>
            </div>
        </div>
    </div>

    <!-- Attendance Calendar -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">سجل الحضور الشهري</h3>
        <div id="attendanceCalendar" class="grid grid-cols-7 gap-2">
            <!-- Calendar will be generated here -->
        </div>
    </div>

    <!-- Attendance Records -->
    <div class="bg-white border border-slate-200 rounded-xl">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">سجل الحضور</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">التاريخ</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الحاضرون</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الغائبون</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">نسبة الحضور</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="attendanceHistory">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="calendar" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                            <p class="text-slate-500">اختر التاريخ والدورة لعرض السجل</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
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

function generateCalendar() {
    const calendar = document.getElementById('attendanceCalendar');
    const today = new Date();
    const year = today.getFullYear();
    const month = today.getMonth();
    
    // Days header
    const days = ['الأحد', 'الإثنين', 'الثلاثاء', 'الأربعاء', 'الخميس', 'الجمعة', 'السبت'];
    calendar.innerHTML = days.map(day => `
        <div class="text-center font-semibold text-slate-700 py-2">${day}</div>
    `).join('');
    
    // Get first day of month
    const firstDay = new Date(year, month, 1).getDay();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    
    // Empty cells before first day
    for (let i = 0; i < firstDay; i++) {
        calendar.innerHTML += '<div class="p-2"></div>';
    }
    
    // Calendar days
    for (let day = 1; day <= daysInMonth; day++) {
        const isToday = day === today.getDate();
        calendar.innerHTML += `
            <div class="p-2 border border-slate-200 rounded-lg text-center cursor-pointer hover:bg-emerald-50 transition-colors ${
                isToday ? 'bg-emerald-100 font-bold' : ''
            }">
                <div class="text-sm text-slate-700">${day}</div>
            </div>
        `;
    }
}

async function loadAttendanceHistory() {
    const courseId = document.getElementById('attendanceCourse').value;
    const date = document.getElementById('attendanceDate').value;
    
    if (!courseId) {
        DashboardIntegration.ui.showToast('الرجاء اختيار الدورة', 'error');
        return;
    }
    
    const response = await TrainerFeatures.attendance.getAttendanceRecords(courseId, date);
    
    if (response.success && response.data) {
        renderAttendanceHistory(response.data);
    }
    
    lucide.createIcons();
}

function renderAttendanceHistory(records) {
    const tbody = document.getElementById('attendanceHistory');
    
    if (records.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                    <p class="text-slate-600">لا يوجد سجل حضور لهذا التاريخ</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = records.map(record => {
        const total = record.present + record.absent;
        const percentage = total > 0 ? ((record.present / total) * 100).toFixed(1) : 0;
        
        return `
            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-slate-800">${record.date}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm text-slate-700">${record.course_name}</span>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="check-circle" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-sm font-semibold text-emerald-700">${record.present}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-2">
                        <i data-lucide="x-circle" class="w-4 h-4 text-red-600"></i>
                        <span class="text-sm font-semibold text-red-700">${record.absent}</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="flex-1 bg-slate-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-emerald-500" style="width: ${percentage}%"></div>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">${percentage}%</span>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <button onclick="editAttendance(${record.id})" 
                        class="px-3 py-1.5 text-sm bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100">
                        <i data-lucide="edit" class="w-4 h-4 inline"></i>
                        تعديل
                    </button>
                </td>
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

function recordNewAttendance() {
    const courseId = document.getElementById('attendanceCourse').value;
    
    if (!courseId) {
        DashboardIntegration.ui.showToast('الرجاء اختيار الدورة أولاً', 'error');
        return;
    }
    
    const date = document.getElementById('attendanceDate').value;
    TrainerFeatures.ui.showAttendanceModal(courseId, date);
}

function editAttendance(recordId) {
    // TODO: Implement edit attendance functionality
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة التعديل قريباً', 'info');
}

// Initialize
loadCourses();
generateCalendar();
lucide.createIcons();
</script>
