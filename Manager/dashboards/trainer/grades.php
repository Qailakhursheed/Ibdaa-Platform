<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">إدارة الدرجات</h2>
            <p class="text-slate-600 mt-1">تسجيل ومراجعة درجات الطلاب</p>
        </div>
        <button onclick="addNewGrade()" 
            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إضافة درجة
        </button>
    </div>

    <!-- Course Selection -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">اختر الدورة</label>
                <select id="gradeCourse" onchange="loadGrades()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر دورة لعرض الدرجات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">نوع التقييم</label>
                <select id="gradeType" onchange="filterGrades()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع التقييمات</option>
                    <option value="assignment">واجبات</option>
                    <option value="quiz">اختبارات</option>
                    <option value="midterm">منتصف الفصل</option>
                    <option value="final">نهائي</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="gradedStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">طلاب تم تقييمهم</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">المتوسط العام</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="topGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">أعلى درجة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-down" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="lowGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">أدنى درجة</p>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="bg-white border border-slate-200 rounded-xl">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">درجات الطلاب</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">واجبات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">اختبارات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">منتصف الفصل</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">نهائي</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">المجموع</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">التقدير</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="gradesTable">
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i data-lucide="clipboard" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                            <p class="text-slate-500">اختر دورة لعرض الدرجات</p>
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
        const select = document.getElementById('gradeCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

async function loadGrades() {
    const courseId = document.getElementById('gradeCourse').value;
    if (!courseId) return;
    
    const response = await TrainerFeatures.grades.getGrades(courseId);
    
    if (response.success && response.data) {
        const grades = response.data;
        
        // Calculate statistics
        const gradedStudents = grades.filter(g => g.total > 0).length;
        const avgGrade = grades.reduce((sum, g) => sum + (g.total || 0), 0) / grades.length || 0;
        const topGrade = Math.max(...grades.map(g => g.total || 0));
        const lowGrade = Math.min(...grades.filter(g => g.total > 0).map(g => g.total));
        
        document.getElementById('gradedStudents').textContent = gradedStudents;
        document.getElementById('avgGrade').textContent = avgGrade.toFixed(1);
        document.getElementById('topGrade').textContent = topGrade;
        document.getElementById('lowGrade').textContent = lowGrade > 0 ? lowGrade : 0;
        
        renderGrades(grades);
    }
    
    lucide.createIcons();
}

function renderGrades(grades) {
    const tbody = document.getElementById('gradesTable');
    
    if (grades.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <i data-lucide="clipboard-x" class="w-8 h-8 mx-auto text-slate-400 mb-3"></i>
                    <p class="text-slate-600">لا توجد درجات مسجلة</p>
                </td>
            </tr>
        `;
        lucide.createIcons();
        return;
    }
    
    tbody.innerHTML = grades.map(grade => {
        const total = (grade.assignments || 0) + (grade.quizzes || 0) + (grade.midterm || 0) + (grade.final || 0);
        const gradeLevel = total >= 90 ? 'ممتاز' : total >= 80 ? 'جيد جداً' : total >= 70 ? 'جيد' : total >= 60 ? 'مقبول' : 'ضعيف';
        const gradeColor = total >= 80 ? 'emerald' : total >= 60 ? 'amber' : 'red';
        
        return `
            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4">
                    <div class="flex items-center gap-3">
                        <img src="${grade.student_photo || '../platform/photos/default-avatar.png'}" 
                            class="w-10 h-10 rounded-full object-cover">
                        <div>
                            <p class="font-semibold text-slate-800">${grade.student_name}</p>
                            <p class="text-sm text-slate-500">${grade.student_email}</p>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-slate-700">${grade.assignments || 0}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-slate-700">${grade.quizzes || 0}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-slate-700">${grade.midterm || 0}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-sm font-semibold text-slate-700">${grade.final || 0}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="text-lg font-bold text-${gradeColor}-600">${total}</span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-${gradeColor}-100 text-${gradeColor}-700">
                        ${gradeLevel}
                    </span>
                </td>
                <td class="px-6 py-4">
                    <button onclick="editGrade(${grade.student_id}, ${grade.course_id})" 
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

function addNewGrade() {
    const courseId = document.getElementById('gradeCourse').value;
    if (!courseId) {
        DashboardIntegration.ui.showToast('الرجاء اختيار الدورة أولاً', 'error');
        return;
    }
    
    // TODO: Show modal for adding grade
    DashboardIntegration.ui.showToast('سيتم إضافة نموذج إدخال الدرجات قريباً', 'info');
}

function editGrade(studentId, courseId) {
    // TODO: Show edit grade modal
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة التعديل قريباً', 'info');
}

function filterGrades() {
    // TODO: Implement filtering
}

// Initialize
loadCourses();
lucide.createIcons();
</script>
