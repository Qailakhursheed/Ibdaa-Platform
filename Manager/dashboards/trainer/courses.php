<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">دوراتي التدريبية</h2>
            <p class="text-slate-600 mt-1">إدارة وتتبع دوراتك التدريبية</p>
        </div>
        <div class="flex gap-3">
            <select id="courseFilter" class="px-4 py-2 border border-slate-300 rounded-lg">
                <option value="all">جميع الدورات</option>
                <option value="active">نشطة</option>
                <option value="completed">مكتملة</option>
                <option value="upcoming">قادمة</option>
            </select>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-8 h-8"></i>
                <span class="text-2xl font-bold" id="activeCourses">0</span>
            </div>
            <p class="text-sm opacity-90">دورات نشطة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalEnrolled">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الملتحقين</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="folder" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalMaterials">0</span>
            </div>
            <p class="text-sm text-slate-600">مواد تعليمية</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgRating">0</span>
            </div>
            <p class="text-sm text-slate-600">متوسط التقييم</p>
        </div>
    </div>

    <!-- Courses Grid -->
    <div id="coursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full flex justify-center py-12">
            <div class="text-center">
                <i data-lucide="loader" class="w-12 h-12 mx-auto animate-spin text-slate-400 mb-3"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
// Load courses on page load
async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
    
    if (response.success && response.data) {
        const courses = response.data;
        
        // Update statistics
        document.getElementById('activeCourses').textContent = courses.filter(c => c.status === 'active').length;
        document.getElementById('totalEnrolled').textContent = courses.reduce((sum, c) => sum + (c.enrolled || 0), 0);
        document.getElementById('totalMaterials').textContent = courses.reduce((sum, c) => sum + (c.materials_count || 0), 0);
        document.getElementById('avgRating').textContent = (courses.reduce((sum, c) => sum + (c.rating || 0), 0) / courses.length || 0).toFixed(1);
        
        // Render courses
        renderCourses(courses);
    } else {
        document.getElementById('coursesGrid').innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد دورات حالياً</p>
            </div>
        `;
    }
    
    lucide.createIcons();
}

function renderCourses(courses) {
    const grid = document.getElementById('coursesGrid');
    
    if (courses.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="book-open" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد دورات تدريبية</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = courses.map(course => `
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-40 bg-gradient-to-br from-emerald-500 to-green-600 flex items-center justify-center">
                <i data-lucide="book-open" class="w-16 h-16 text-white"></i>
            </div>
            
            <div class="p-6">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-lg font-bold text-slate-800 flex-1">${course.course_name}</h3>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                        course.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                        course.status === 'completed' ? 'bg-slate-100 text-slate-700' :
                        'bg-amber-100 text-amber-700'
                    }">${
                        course.status === 'active' ? 'نشطة' :
                        course.status === 'completed' ? 'مكتملة' :
                        'قادمة'
                    }</span>
                </div>
                
                <p class="text-slate-600 text-sm mb-4 line-clamp-2">${course.description || 'لا يوجد وصف'}</p>
                
                <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${course.enrolled || 0} طالب</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="folder" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${course.materials_count || 0} مادة</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="calendar" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${course.duration || 0} ساعة</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="star" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${course.rating || 0} / 5</span>
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <button onclick="viewCourseDetails(${course.id})" 
                        class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold">
                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                        عرض
                    </button>
                    <button onclick="recordAttendance(${course.id})" 
                        class="flex-1 px-4 py-2 bg-white border border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-50 transition-colors text-sm font-semibold">
                        <i data-lucide="clipboard-check" class="w-4 h-4 inline"></i>
                        حضور
                    </button>
                </div>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function viewCourseDetails(courseId) {
    window.location.href = `?page=students&course_id=${courseId}`;
}

function recordAttendance(courseId) {
    const today = new Date().toISOString().split('T')[0];
    TrainerFeatures.ui.showAttendanceModal(courseId, today);
}

// Filter courses
document.getElementById('courseFilter').addEventListener('change', function() {
    loadCourses();
});

// Initialize
loadCourses();
</script>
