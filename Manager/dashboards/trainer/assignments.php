<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">الواجبات</h2>
            <p class="text-slate-600 mt-1">إنشاء وإدارة واجبات الطلاب</p>
        </div>
        <button onclick="createNewAssignment()" 
            class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold flex items-center gap-2">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إنشاء واجب جديد
        </button>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clipboard-list" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الواجبات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clock" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="pendingAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">في انتظار التسليم</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="submittedAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">تم التسليم</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="edit" class="w-8 h-8 text-blue-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="needsGrading">0</span>
            </div>
            <p class="text-sm text-slate-600">يحتاج تقييم</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="assignmentCourse" onchange="loadAssignments()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
                <select id="assignmentStatus" onchange="filterAssignments()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الحالات</option>
                    <option value="active">نشطة</option>
                    <option value="closed">مغلقة</option>
                    <option value="graded">تم التقييم</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
                <input type="text" id="assignmentSearch" placeholder="ابحث في الواجبات..." 
                    oninput="searchAssignments()"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Assignments Grid -->
    <div id="assignmentsGrid" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div class="col-span-full flex justify-center py-12">
            <div class="text-center">
                <i data-lucide="loader" class="w-12 h-12 mx-auto animate-spin text-slate-400 mb-3"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
let allAssignments = [];

async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('assignmentCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

async function loadAssignments() {
    const courseId = document.getElementById('assignmentCourse').value;
    const response = await TrainerFeatures.assignments.getAssignments(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        allAssignments = response.data;
        
        // Update statistics
        document.getElementById('totalAssignments').textContent = allAssignments.length;
        document.getElementById('pendingAssignments').textContent = 
            allAssignments.filter(a => a.status === 'active').length;
        document.getElementById('submittedAssignments').textContent = 
            allAssignments.reduce((sum, a) => sum + (a.submissions || 0), 0);
        document.getElementById('needsGrading').textContent = 
            allAssignments.reduce((sum, a) => sum + (a.ungraded || 0), 0);
        
        renderAssignments(allAssignments);
    }
    
    lucide.createIcons();
}

function renderAssignments(assignments) {
    const grid = document.getElementById('assignmentsGrid');
    
    if (assignments.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="clipboard-x" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد واجبات حالياً</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = assignments.map(assignment => {
        const dueDate = new Date(assignment.due_date);
        const isOverdue = dueDate < new Date();
        const statusColor = assignment.status === 'active' ? 'emerald' : 
                           assignment.status === 'closed' ? 'slate' : 'blue';
        
        return `
            <div class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-800 mb-2">${assignment.title}</h3>
                        <p class="text-sm text-slate-600 line-clamp-2">${assignment.description}</p>
                    </div>
                    <span class="px-3 py-1 text-xs font-semibold rounded-full bg-${statusColor}-100 text-${statusColor}-700">
                        ${assignment.status === 'active' ? 'نشط' : 
                          assignment.status === 'closed' ? 'مغلق' : 'تم التقييم'}
                    </span>
                </div>
                
                <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="book-open" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${assignment.course_name}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="star" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${assignment.max_grade || 100} درجة</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm ${isOverdue ? 'text-red-600' : 'text-slate-700'}">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>استحقاق: ${assignment.due_date}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm">
                        <i data-lucide="users" class="w-4 h-4 text-emerald-600"></i>
                        <span class="text-slate-700">${assignment.submissions || 0} تسليم</span>
                    </div>
                </div>
                
                <div class="flex items-center justify-between mb-4">
                    <div class="flex-1">
                        <div class="flex items-center justify-between text-sm text-slate-600 mb-2">
                            <span>التسليمات</span>
                            <span>${assignment.submissions || 0} / ${assignment.total_students || 0}</span>
                        </div>
                        <div class="bg-slate-200 rounded-full h-2">
                            <div class="h-2 rounded-full bg-emerald-500" 
                                style="width: ${assignment.total_students > 0 ? (assignment.submissions / assignment.total_students * 100) : 0}%"></div>
                        </div>
                    </div>
                </div>
                
                ${assignment.ungraded > 0 ? `
                    <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="alert-triangle" class="w-5 h-5 text-amber-600"></i>
                            <span class="text-sm font-semibold text-amber-800">
                                ${assignment.ungraded} تسليم بحاجة للتقييم
                            </span>
                        </div>
                    </div>
                ` : ''}
                
                <div class="flex gap-2">
                    <button onclick="viewSubmissions(${assignment.id})" 
                        class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold">
                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                        عرض التسليمات
                    </button>
                    <button onclick="editAssignment(${assignment.id})" 
                        class="px-4 py-2 bg-white border border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-50 transition-colors text-sm font-semibold">
                        <i data-lucide="edit" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

function createNewAssignment() {
    const modalHTML = `
        <form id="createAssignmentForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="newAssignmentCourse" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر الدورة</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">عنوان الواجب</label>
                <input type="text" id="assignmentTitle" required 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="أدخل عنوان الواجب">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">التعليمات</label>
                <textarea id="assignmentInstructions" rows="4" required
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="أدخل تعليمات الواجب"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">تاريخ الاستحقاق</label>
                    <input type="datetime-local" id="assignmentDueDate" required 
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                </div>
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">الدرجة الكاملة</label>
                    <input type="number" id="assignmentMaxGrade" required value="100"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">المرفقات (اختياري)</label>
                <input type="file" id="assignmentAttachment" multiple
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
            </div>
        </form>
    `;
    
    DashboardIntegration.ui.showModal('إنشاء واجب جديد', modalHTML, [
        {
            text: 'إنشاء',
            class: 'bg-emerald-600 text-white hover:bg-emerald-700',
            onclick: 'submitNewAssignment()'
        },
        {
            text: 'إلغاء',
            class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
            onclick: 'this.closest(".fixed").remove()'
        }
    ]);
    
    // Load courses
    TrainerFeatures.courses.getMyCourses().then(response => {
        if (response.success && response.data) {
            const select = document.getElementById('newAssignmentCourse');
            response.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.id;
                option.textContent = course.course_name;
                select.appendChild(option);
            });
        }
    });
}

async function submitNewAssignment() {
    const courseId = document.getElementById('newAssignmentCourse').value;
    const title = document.getElementById('assignmentTitle').value;
    const instructions = document.getElementById('assignmentInstructions').value;
    const dueDate = document.getElementById('assignmentDueDate').value;
    const maxGrade = document.getElementById('assignmentMaxGrade').value;
    
    if (!courseId || !title || !instructions || !dueDate) {
        DashboardIntegration.ui.showToast('الرجاء إكمال جميع الحقول المطلوبة', 'error');
        return;
    }
    
    const response = await TrainerFeatures.assignments.createAssignment(courseId, {
        title,
        description: instructions,
        due_date: dueDate,
        max_grade: maxGrade
    });
    
    if (response.success) {
        DashboardIntegration.ui.showToast('تم إنشاء الواجب بنجاح', 'success');
        document.querySelector('.fixed').remove();
        loadAssignments();
    } else {
        DashboardIntegration.ui.showToast('فشل إنشاء الواجب', 'error');
    }
}

function viewSubmissions(assignmentId) {
    window.location.href = `?page=assignments&assignment_id=${assignmentId}`;
}

function editAssignment(assignmentId) {
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة التعديل قريباً', 'info');
}

function filterAssignments() {
    const status = document.getElementById('assignmentStatus').value;
    const filtered = status === 'all' ? allAssignments : allAssignments.filter(a => a.status === status);
    renderAssignments(filtered);
}

function searchAssignments() {
    const search = document.getElementById('assignmentSearch').value.toLowerCase();
    const filtered = allAssignments.filter(a => 
        a.title.toLowerCase().includes(search) || 
        a.description.toLowerCase().includes(search)
    );
    renderAssignments(filtered);
}

// Initialize
loadCourses();
loadAssignments();
</script>
