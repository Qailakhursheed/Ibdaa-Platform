<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">درجاتي وأدائي الأكاديمي</h2>
            <p class="text-slate-600 mt-1">متابعة درجاتك ومعدلك التراكمي</p>
        </div>
        <button onclick="exportGradesReport()" 
            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold flex items-center gap-2">
            <i data-lucide="download" class="w-5 h-5"></i>
            تصدير كشف الدرجات
        </button>
    </div>

    <!-- GPA Cards -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-8 h-8"></i>
                <span class="text-4xl font-bold" id="currentGPA">0.0</span>
            </div>
            <p class="text-sm opacity-90">المعدل التراكمي (GPA)</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-4xl font-bold text-slate-800" id="semesterGPA">0.0</span>
            </div>
            <p class="text-sm text-slate-600">معدل الفصل الحالي</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="award" class="w-8 h-8 text-amber-600"></i>
                <span class="text-4xl font-bold text-slate-800" id="totalCredits">0</span>
            </div>
            <p class="text-sm text-slate-600">الساعات المعتمدة</p>
        </div>
    </div>

    <!-- Course Filter -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">اختر الدورة</label>
                <select id="gradeCourse" onchange="loadGrades()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الفصل الدراسي</label>
                <select id="semester" onchange="loadGrades()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الفصول</option>
                    <option value="current">الفصل الحالي</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="bg-white border border-slate-200 rounded-xl">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">كشف الدرجات</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الواجبات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الاختبارات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">منتصف الفصل</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">النهائي</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">المجموع</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">التقدير</th>
                    </tr>
                </thead>
                <tbody id="gradesTable">
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-3"></i>
                            <p class="text-slate-500">جاري التحميل...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- GPA Trend Chart -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">تطور المعدل التراكمي</h3>
        <canvas id="gpaT rendChart" height="100"></canvas>
    </div>
</div>

<script>
async function loadCourses() {
    const response = await StudentFeatures.courses.getMyCourses();
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
    const response = await StudentFeatures.grades.getMyGrades(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        const grades = response.data;
        renderGrades(grades);
        
        // Update GPA
        const gpaResponse = await StudentFeatures.grades.getGPA();
        if (gpaResponse.success && gpaResponse.data) {
            document.getElementById('currentGPA').textContent = gpaResponse.data.cumulative_gpa.toFixed(2);
            document.getElementById('semesterGPA').textContent = gpaResponse.data.semester_gpa.toFixed(2);
            document.getElementById('totalCredits').textContent = gpaResponse.data.total_credits;
        }
    }
    
    lucide.createIcons();
}

function renderGrades(grades) {
    const tbody = document.getElementById('gradesTable');
    
    if (grades.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="7" class="px-6 py-12 text-center">
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
        const gradeLevel = total >= 90 ? 'A+' : total >= 85 ? 'A' : total >= 80 ? 'B+' : total >= 75 ? 'B' : total >= 70 ? 'C+' : total >= 65 ? 'C' : total >= 60 ? 'D' : 'F';
        const gradeColor = total >= 80 ? 'emerald' : total >= 60 ? 'amber' : 'red';
        
        return `
            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                <td class="px-6 py-4">
                    <div>
                        <p class="font-semibold text-slate-800">${grade.course_name}</p>
                        <p class="text-sm text-slate-500">${grade.course_code || ''}</p>
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
            </tr>
        `;
    }).join('');
    
    lucide.createIcons();
}

function exportGradesReport() {
    DashboardIntegration.ui.showToast('سيتم إضافة ميزة التصدير قريباً', 'info');
}

// Create GPA Trend Chart
function createGPATrendChart() {
    const ctx = document.getElementById('gpaTrendChart');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: ['الفصل 1', 'الفصل 2', 'الفصل 3', 'الفصل 4'],
            datasets: [{
                label: 'المعدل التراكمي',
                data: [3.2, 3.5, 3.7, 3.8],
                borderColor: 'rgb(245, 158, 11)',
                backgroundColor: 'rgba(245, 158, 11, 0.1)',
                tension: 0.4,
                fill: true
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true, max: 4.0 }
            }
        }
    });
}

// Initialize
loadCourses();
loadGrades();
createGPATrendChart();
</script>
