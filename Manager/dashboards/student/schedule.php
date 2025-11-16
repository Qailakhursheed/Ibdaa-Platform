<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">الجدول الدراسي</h2>
            <p class="text-slate-600 mt-1">جدول المحاضرات والدروس الأسبوعية</p>
        </div>
        <button onclick="exportSchedule()" 
            class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors font-semibold">
            <i data-lucide="download" class="w-4 h-4 inline"></i>
            تصدير الجدول
        </button>
    </div>

    <!-- Current Week Info -->
    <div class="bg-gradient-to-r from-amber-600 to-orange-600 rounded-xl p-6 text-white">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-2xl font-bold mb-2">الأسبوع الدراسي الحالي</h3>
                <p class="text-amber-100" id="currentWeek">جاري التحميل...</p>
            </div>
            <div class="text-center">
                <div class="text-4xl font-bold" id="totalClasses">0</div>
                <p class="text-amber-100 mt-1">محاضرة هذا الأسبوع</p>
            </div>
        </div>
    </div>

    <!-- Weekly Schedule Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الوقت</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الأحد</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الإثنين</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الثلاثاء</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الأربعاء</th>
                        <th class="px-6 py-3 text-right text-xs font-semibold text-slate-700 uppercase tracking-wider">الخميس</th>
                    </tr>
                </thead>
                <tbody id="scheduleTable" class="bg-white divide-y divide-slate-200">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-2"></i>
                            <p class="text-slate-500">جاري تحميل الجدول...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Today's Classes -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">محاضرات اليوم</h3>
        <div id="todayClasses" class="space-y-3">
            <div class="text-center py-8">
                <i data-lucide="calendar" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>

    <!-- Upcoming Classes -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">المحاضرات القادمة</h3>
        <div id="upcomingClasses" class="space-y-3">
            <div class="text-center py-8">
                <i data-lucide="clock" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
let scheduleData = [];

async function loadSchedule() {
    const response = await StudentFeatures.schedule.getMySchedule();
    
    if (response.success && response.data) {
        scheduleData = response.data;
        
        // Update week info
        const today = new Date();
        document.getElementById('currentWeek').textContent = 
            `${today.toLocaleDateString('ar-SA', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' })}`;
        document.getElementById('totalClasses').textContent = scheduleData.length;
        
        renderScheduleTable();
        renderTodayClasses();
        renderUpcomingClasses();
    } else {
        // Show sample data
        scheduleData = generateSampleSchedule();
        renderScheduleTable();
        renderTodayClasses();
        renderUpcomingClasses();
    }
    
    lucide.createIcons();
}

function renderScheduleTable() {
    const times = ['08:00', '10:00', '12:00', '14:00', '16:00'];
    const days = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday'];
    const tbody = document.getElementById('scheduleTable');
    
    tbody.innerHTML = times.map(time => {
        const cells = days.map(day => {
            const classes = scheduleData.filter(item => 
                item.day === day && item.start_time === time
            );
            
            if (classes.length > 0) {
                const cls = classes[0];
                const colors = [
                    'bg-amber-100 border-amber-300 text-amber-800',
                    'bg-emerald-100 border-emerald-300 text-emerald-800',
                    'bg-blue-100 border-blue-300 text-blue-800',
                    'bg-purple-100 border-purple-300 text-purple-800',
                    'bg-red-100 border-red-300 text-red-800'
                ];
                const color = colors[Math.floor(Math.random() * colors.length)];
                
                return `
                    <td class="px-6 py-4">
                        <div class="border-r-4 ${color} rounded-lg p-3">
                            <div class="font-semibold text-sm mb-1">${cls.course_name}</div>
                            <div class="text-xs opacity-90 mb-1">${cls.trainer_name}</div>
                            <div class="text-xs opacity-75">${cls.room || 'القاعة 1'}</div>
                        </div>
                    </td>
                `;
            }
            return '<td class="px-6 py-4 bg-slate-50"></td>';
        }).join('');
        
        return `
            <tr>
                <td class="px-6 py-4 font-semibold text-slate-700">${time}</td>
                ${cells}
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

function renderTodayClasses() {
    const today = new Date();
    const dayName = today.toLocaleDateString('en-US', { weekday: 'long' });
    const todayClasses = scheduleData.filter(item => item.day === dayName);
    const container = document.getElementById('todayClasses');
    
    if (todayClasses.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500">لا توجد محاضرات اليوم</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = todayClasses.map(cls => `
        <div class="flex items-center gap-4 p-4 bg-amber-50 border border-amber-200 rounded-lg">
            <div class="flex-shrink-0 w-16 h-16 bg-amber-600 text-white rounded-lg flex items-center justify-center">
                <div class="text-center">
                    <div class="text-xs font-semibold">${cls.start_time}</div>
                    <div class="text-xs mt-1">${cls.end_time}</div>
                </div>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-slate-800">${cls.course_name}</h4>
                <p class="text-sm text-slate-600">${cls.trainer_name}</p>
                <p class="text-xs text-slate-500 mt-1">${cls.room || 'القاعة 1'}</p>
            </div>
            <button class="px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-semibold">
                انضم الآن
            </button>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function renderUpcomingClasses() {
    const upcoming = scheduleData.slice(0, 5);
    const container = document.getElementById('upcomingClasses');
    
    if (upcoming.length === 0) {
        container.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="calendar-x" class="w-8 h-8 mx-auto text-slate-400 mb-2"></i>
                <p class="text-slate-500">لا توجد محاضرات قادمة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    container.innerHTML = upcoming.map(cls => `
        <div class="flex items-center gap-4 p-4 border border-slate-200 rounded-lg hover:bg-slate-50 transition-colors">
            <div class="flex-shrink-0 w-12 h-12 bg-slate-100 text-slate-700 rounded-lg flex items-center justify-center">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
            <div class="flex-1">
                <h4 class="font-bold text-slate-800">${cls.course_name}</h4>
                <p class="text-sm text-slate-600">${cls.trainer_name}</p>
            </div>
            <div class="text-left">
                <div class="text-sm font-semibold text-slate-800">${cls.day}</div>
                <div class="text-xs text-slate-500">${cls.start_time} - ${cls.end_time}</div>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function generateSampleSchedule() {
    return [
        { day: 'Sunday', start_time: '08:00', end_time: '10:00', course_name: 'برمجة الويب', trainer_name: 'أ. أحمد علي', room: 'القاعة 1' },
        { day: 'Sunday', start_time: '12:00', end_time: '14:00', course_name: 'قواعد البيانات', trainer_name: 'أ. محمد سالم', room: 'القاعة 2' },
        { day: 'Monday', start_time: '10:00', end_time: '12:00', course_name: 'الذكاء الاصطناعي', trainer_name: 'د. فاطمة حسن', room: 'القاعة 3' },
        { day: 'Tuesday', start_time: '08:00', end_time: '10:00', course_name: 'الشبكات', trainer_name: 'م. عبدالله ناصر', room: 'المعمل 1' },
        { day: 'Tuesday', start_time: '14:00', end_time: '16:00', course_name: 'أمن المعلومات', trainer_name: 'أ. سارة أحمد', room: 'القاعة 2' },
        { day: 'Wednesday', start_time: '10:00', end_time: '12:00', course_name: 'تطوير التطبيقات', trainer_name: 'م. خالد محمد', room: 'المعمل 2' },
        { day: 'Thursday', start_time: '08:00', end_time: '10:00', course_name: 'إدارة المشاريع', trainer_name: 'د. نادية علي', room: 'القاعة 1' }
    ];
}

function exportSchedule() {
    DashboardIntegration.ui.showToast('سيتم تصدير الجدول قريباً', 'info');
}

// Initialize
loadSchedule();
</script>
