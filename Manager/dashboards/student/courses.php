<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">دوراتي التدريبية</h2>
            <p class="text-slate-600 mt-1">جميع الدورات المسجلة والمكتملة</p>
        </div>
        <div class="flex gap-3">
            <select id="courseStatus" onchange="filterCourses()" 
                class="px-4 py-2 border border-slate-300 rounded-lg">
                <option value="all">جميع الدورات</option>
                <option value="active">نشطة</option>
                <option value="completed">مكتملة</option>
            </select>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalCourses">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الدورات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="play-circle" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="activeCourses">0</span>
            </div>
            <p class="text-sm text-slate-600">دورات نشطة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-blue-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="completedCourses">0</span>
            </div>
            <p class="text-sm text-slate-600">مكتملة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgProgress">0%</span>
            </div>
            <p class="text-sm text-slate-600">متوسط التقدم</p>
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
let allCourses = [];

async function loadCourses() {
    const response = await StudentFeatures.courses.getMyCourses();
    
    if (response.success && response.data) {
        allCourses = response.data;
        
        // Update statistics
        document.getElementById('totalCourses').textContent = allCourses.length;
        document.getElementById('activeCourses').textContent = allCourses.filter(c => c.status === 'active').length;
        document.getElementById('completedCourses').textContent = allCourses.filter(c => c.status === 'completed').length;
        document.getElementById('avgProgress').textContent = 
            (allCourses.reduce((sum, c) => sum + (c.progress || 0), 0) / allCourses.length || 0).toFixed(0) + '%';
        
        renderCourses(allCourses);
    } else {
        document.getElementById('coursesGrid').innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد دورات مسجلة</p>
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
                <p class="text-slate-600">لا توجد دورات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = courses.map(course => `
        <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
            <div class="h-40 bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center">
                <i data-lucide="book-open" class="w-16 h-16 text-white"></i>
            </div>
            
            <div class="p-6">
                <div class="flex items-start justify-between mb-3">
                    <h3 class="text-lg font-bold text-slate-800 flex-1">${course.course_name}</h3>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                        course.status === 'active' ? 'bg-emerald-100 text-emerald-700' :
                        course.status === 'completed' ? 'bg-blue-100 text-blue-700' :
                        'bg-slate-100 text-slate-700'
                    }">${
                        course.status === 'active' ? 'نشطة' :
                        course.status === 'completed' ? 'مكتملة' :
                        'معلقة'
                    }</span>
                </div>
                
                <p class="text-slate-600 text-sm mb-4 line-clamp-2">${course.description || 'لا يوجد وصف'}</p>
                
                <div class="mb-4">
                    <div class="flex justify-between text-sm mb-2">
                        <span class="text-slate-600">التقدم</span>
                        <span class="font-semibold text-amber-600">${course.progress || 0}%</span>
                    </div>
                    <div class="h-2 bg-slate-200 rounded-full overflow-hidden">
                        <div class="h-full bg-amber-500 transition-all" style="width: ${course.progress || 0}%"></div>
                    </div>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                        <span class="text-slate-700">${course.trainer_name || 'مدرب'}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                        <span class="text-slate-700">${course.duration || 0} ساعة</span>
                    </div>
                </div>
                
                <button onclick="viewCourseDetails(${course.id})" 
                    class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-semibold">
                    <i data-lucide="arrow-left" class="w-4 h-4 inline"></i>
                    متابعة الدورة
                </button>
            </div>
        </div>
    `).join('');
    
    lucide.createIcons();
}

function viewCourseDetails(courseId) {
    window.location.href = `?page=materials&course_id=${courseId}`;
}

function filterCourses() {
    const status = document.getElementById('courseStatus').value;
    const filtered = status === 'all' ? allCourses : allCourses.filter(c => c.status === status);
    renderCourses(filtered);
}

// Initialize
loadCourses();
</script>
