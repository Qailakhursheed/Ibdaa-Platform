<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">واجباتي</h2>
            <p class="text-slate-600 mt-1">تسليم ومتابعة الواجبات الدراسية</p>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clipboard-list" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الواجبات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clock" class="w-8 h-8 text-red-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="pendingAssignments">0</span>
            </div>
            <p class="text-sm text-slate-600">لم يتم تسليمه</p>
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
                <i data-lucide="star" class="w-8 h-8 text-blue-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">متوسط الدرجات</p>
        </div>
    </div>

    <!-- Filter -->
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
                    <option value="pending">لم يتم تسليمه</option>
                    <option value="submitted">تم التسليم</option>
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
    const response = await StudentFeatures.courses.getMyCourses();
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
    const response = await StudentFeatures.assignments.getMyAssignments(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        allAssignments = response.data;
        
        // Update statistics
        document.getElementById('totalAssignments').textContent = allAssignments.length;
        document.getElementById('pendingAssignments').textContent = allAssignments.filter(a => !a.submitted).length;
        document.getElementById('submittedAssignments').textContent = allAssignments.filter(a => a.submitted).length;
        const graded = allAssignments.filter(a => a.grade != null);
        const avgGrade = graded.length > 0 
            ? (graded.reduce((sum, a) => sum + a.grade, 0) / graded.length).toFixed(0)
            : 0;
        document.getElementById('avgGrade').textContent = avgGrade;
        
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
                <p class="text-slate-600">لا توجد واجبات</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = assignments.map(assignment => {
        const dueDate = new Date(assignment.due_date);
        const today = new Date();
        const daysLeft = Math.ceil((dueDate - today) / (1000 * 60 * 60 * 24));
        const isOverdue = daysLeft < 0;
        const isUrgent = daysLeft <= 2 && daysLeft >= 0;
        
        return `
            <div class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-lg transition-shadow">
                <div class="flex items-start justify-between mb-4">
                    <div class="flex-1">
                        <h3 class="text-lg font-bold text-slate-800 mb-2">${assignment.title}</h3>
                        <p class="text-sm text-slate-600">${assignment.course_name}</p>
                    </div>
                    ${assignment.submitted ? `
                        <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                            assignment.grade != null ? 'bg-blue-100 text-blue-700' : 'bg-emerald-100 text-emerald-700'
                        }">
                            ${assignment.grade != null ? `مُقيّم (${assignment.grade})` : 'تم التسليم'}
                        </span>
                    ` : `
                        <span class="px-3 py-1 text-xs font-semibold rounded-full ${
                            isOverdue ? 'bg-red-100 text-red-700' :
                            isUrgent ? 'bg-amber-100 text-amber-700' :
                            'bg-slate-100 text-slate-700'
                        }">
                            ${isOverdue ? 'متأخر' : isUrgent ? 'عاجل' : 'معلق'}
                        </span>
                    `}
                </div>
                
                <p class="text-slate-600 text-sm mb-4 line-clamp-2">${assignment.description || 'لا يوجد وصف'}</p>
                
                <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-200">
                    <div class="flex items-center gap-2 text-sm ${isOverdue && !assignment.submitted ? 'text-red-600' : 'text-slate-700'}">
                        <i data-lucide="calendar" class="w-4 h-4"></i>
                        <span>الموعد: ${assignment.due_date}</span>
                    </div>
                    <div class="flex items-center gap-2 text-sm text-slate-700">
                        <i data-lucide="star" class="w-4 h-4"></i>
                        <span>${assignment.max_grade || 100} درجة</span>
                    </div>
                </div>
                
                ${!assignment.submitted ? `
                    ${isOverdue ? `
                        <div class="bg-red-50 border border-red-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="alert-circle" class="w-5 h-5 text-red-600"></i>
                                <span class="text-sm font-semibold text-red-800">
                                    تأخرت ${Math.abs(daysLeft)} يوم
                                </span>
                            </div>
                        </div>
                    ` : isUrgent ? `
                        <div class="bg-amber-50 border border-amber-200 rounded-lg p-3 mb-4">
                            <div class="flex items-center gap-2">
                                <i data-lucide="clock" class="w-5 h-5 text-amber-600"></i>
                                <span class="text-sm font-semibold text-amber-800">
                                    باقي ${daysLeft} يوم على الموعد
                                </span>
                            </div>
                        </div>
                    ` : ''}
                    
                    <button onclick="submitAssignment(${assignment.id}, '${assignment.title}')" 
                        class="w-full px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-semibold">
                        <i data-lucide="upload" class="w-4 h-4 inline"></i>
                        تسليم الواجب
                    </button>
                ` : `
                    <div class="flex gap-2">
                        <button onclick="viewSubmission(${assignment.id})" 
                            class="flex-1 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-semibold">
                            <i data-lucide="eye" class="w-4 h-4 inline"></i>
                            عرض التسليم
                        </button>
                        ${assignment.grade != null ? `
                            <button onclick="viewFeedback(${assignment.id})" 
                                class="flex-1 px-4 py-2 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 transition-colors text-sm font-semibold">
                                <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                                الملاحظات
                            </button>
                        ` : ''}
                    </div>
                `}
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

function submitAssignment(assignmentId, assignmentTitle) {
    StudentFeatures.ui.showAssignmentSubmitModal(assignmentId, assignmentTitle);
}

function viewSubmission(assignmentId) {
    DashboardIntegration.ui.showToast('سيتم عرض التسليم قريباً', 'info');
}

function viewFeedback(assignmentId) {
    DashboardIntegration.ui.showToast('سيتم عرض الملاحظات قريباً', 'info');
}

function filterAssignments() {
    const status = document.getElementById('assignmentStatus').value;
    let filtered = allAssignments;
    
    if (status === 'pending') {
        filtered = allAssignments.filter(a => !a.submitted);
    } else if (status === 'submitted') {
        filtered = allAssignments.filter(a => a.submitted && a.grade == null);
    } else if (status === 'graded') {
        filtered = allAssignments.filter(a => a.grade != null);
    }
    
    renderAssignments(filtered);
}

function searchAssignments() {
    const search = document.getElementById('assignmentSearch').value.toLowerCase();
    const filtered = allAssignments.filter(a => 
        a.title.toLowerCase().includes(search) ||
        a.course_name.toLowerCase().includes(search) ||
        (a.description && a.description.toLowerCase().includes(search))
    );
    renderAssignments(filtered);
}

// Initialize
loadCourses();
loadAssignments();
</script>
