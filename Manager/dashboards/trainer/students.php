<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">طلابي</h2>
            <p class="text-slate-600 mt-1">متابعة أداء وحضور الطلاب</p>
        </div>
        <div class="flex gap-3">
            <select id="courseFilterStudents" class="px-4 py-2 border border-slate-300 rounded-lg">
                <option value="all">جميع الدورات</option>
            </select>
            <input type="text" id="searchStudents" placeholder="بحث عن طالب..." 
                class="px-4 py-2 border border-slate-300 rounded-lg">
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الطلاب</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="excellentStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">متميزون</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="alert-triangle" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="needsAttention">0</span>
            </div>
            <p class="text-sm text-slate-600">يحتاجون متابعة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgAttendance">0%</span>
            </div>
            <p class="text-sm text-slate-600">متوسط الحضور</p>
        </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الحضور</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدرجة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الأداء</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-3"></i>
                            <p class="text-slate-500">جاري التحميل...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
async function loadStudents() {
    const courseId = document.getElementById('courseFilterStudents').value;
    const response = await TrainerFeatures.students.getMyStudents(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        const students = response.data;
        
        // Update statistics
        document.getElementById('totalStudents').textContent = students.length;
        document.getElementById('excellentStudents').textContent = students.filter(s => s.grade >= 90).length;
        document.getElementById('needsAttention').textContent = students.filter(s => s.attendance < 70).length;
        document.getElementById('avgAttendance').textContent = 
            (students.reduce((sum, s) => sum + (s.attendance || 0), 0) / students.length || 0).toFixed(0) + '%';
        
        renderStudents(students);
    }
    
    lucide.createIcons();
}

function renderStudents(students) {
    const tbody = document.getElementById('studentsTableBody');
    
    if (students.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="6" class="px-6 py-12 text-center">
                    <i data-lucide="users" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                    <p class="text-slate-600">لا يوجد طلاب</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = students.map(student => `
        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <img src="${student.photo || '../platform/photos/default-avatar.png'}" 
                        class="w-10 h-10 rounded-full object-cover">
                    <div>
                        <p class="font-semibold text-slate-800">${student.full_name}</p>
                        <p class="text-sm text-slate-500">${student.email}</p>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="text-sm text-slate-700">${student.course_name}</span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <div class="flex-1 bg-slate-200 rounded-full h-2">
                        <div class="h-2 rounded-full ${
                            student.attendance >= 90 ? 'bg-emerald-500' :
                            student.attendance >= 70 ? 'bg-amber-500' :
                            'bg-red-500'
                        }" style="width: ${student.attendance || 0}%"></div>
                    </div>
                    <span class="text-sm font-semibold text-slate-700">${student.attendance || 0}%</span>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="text-sm font-semibold ${
                    student.grade >= 90 ? 'text-emerald-600' :
                    student.grade >= 70 ? 'text-amber-600' :
                    'text-red-600'
                }">${student.grade || 0}</span>
            </td>
            <td class="px-6 py-4">
                <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                    student.grade >= 90 ? 'bg-emerald-100 text-emerald-700' :
                    student.grade >= 70 ? 'bg-amber-100 text-amber-700' :
                    'bg-red-100 text-red-700'
                }">
                    ${student.grade >= 90 ? 'ممتاز' : student.grade >= 70 ? 'جيد' : 'ضعيف'}
                </span>
            </td>
            <td class="px-6 py-4">
                <div class="flex gap-2">
                    <button onclick="viewStudentProfile(${student.id})" 
                        class="px-3 py-1.5 text-sm bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100">
                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                    </button>
                    <button onclick="sendMessage(${student.id})" 
                        class="px-3 py-1.5 text-sm bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200">
                        <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
    
    lucide.createIcons();
}

function viewStudentProfile(studentId) {
    window.location.href = `?page=reports&student_id=${studentId}`;
}

function sendMessage(studentId) {
    window.location.href = `?page=chat&student_id=${studentId}`;
}

// Search functionality
document.getElementById('searchStudents').addEventListener('input', function() {
    const searchTerm = this.value.toLowerCase();
    const rows = document.querySelectorAll('#studentsTableBody tr');
    
    rows.forEach(row => {
        const text = row.textContent.toLowerCase();
        row.style.display = text.includes(searchTerm) ? '' : 'none';
    });
});

// Load courses for filter
async function loadCoursesFilter() {
    const response = await TrainerFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('courseFilterStudents');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

// Initialize
loadCoursesFilter();
loadStudents();

document.getElementById('courseFilterStudents').addEventListener('change', loadStudents);
</script>
