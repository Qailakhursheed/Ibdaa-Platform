<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="users" class="w-6 h-6 text-sky-600"></i>
                إدارة الطلاب (المتدربين)
            </h2>
            <p class="text-slate-600 mt-1">إدارة شاملة لجميع الطلاب المسجلين في المنصة</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openImportStudents()" class="flex items-center gap-2 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                <i data-lucide="file-spreadsheet" class="w-5 h-5"></i>
                <span>استيراد Excel</span>
            </button>
            <button onclick="exportStudents()" class="flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <i data-lucide="download" class="w-5 h-5"></i>
                <span>تصدير</span>
            </button>
            <button onclick="openAddStudent()" class="flex items-center gap-2 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span>إضافة طالب</span>
            </button>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-8 h-8 text-sky-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="totalStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">إجمالي الطلاب</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="user-check" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="activeStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">طلاب نشطون</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clock" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="pendingStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">بانتظار الموافقة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="credit-card" class="w-8 h-8 text-purple-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="paidStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">دفعوا الرسوم</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="newThisMonth">0</span>
            </div>
            <p class="text-sm text-slate-600">جدد هذا الشهر</p>
        </div>
    </div>

    <!-- Filters -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">البحث</label>
                <div class="relative">
                    <i data-lucide="search" class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="searchStudents" placeholder="اسم، بريد، هاتف..."
                        class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                <select id="filterStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="all">جميع الحالات</option>
                    <option value="pending">بانتظار الموافقة</option>
                    <option value="approved">مقبول</option>
                    <option value="active">نشط</option>
                    <option value="suspended">موقوف</option>
                    <option value="graduated">متخرج</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الدورة</label>
                <select id="filterCourse" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">حالة الدفع</label>
                <select id="filterPayment" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                    <option value="all">الكل</option>
                    <option value="paid">مدفوع</option>
                    <option value="pending">بانتظار الدفع</option>
                    <option value="partial">دفع جزئي</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white border border-slate-200 rounded-xl overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">
                            <input type="checkbox" id="selectAll" class="rounded border-slate-300">
                        </th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">معلومات الاتصال</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">حالة الدفع</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">تاريخ التسجيل</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <tr>
                        <td colspan="8" class="px-6 py-12 text-center">
                            <i data-lucide="loader" class="w-8 h-8 mx-auto animate-spin text-slate-400 mb-3"></i>
                            <p class="text-slate-500">جاري تحميل البيانات...</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
                عرض <span id="showingFrom">0</span> - <span id="showingTo">0</span> من <span id="totalCount">0</span> طالب
            </div>
            <div class="flex gap-2" id="paginationButtons">
                <!-- Pagination buttons will be inserted here -->
            </div>
        </div>
    </div>
</div>

<!-- Add Student Modal -->
<div id="addStudentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="user-plus" class="w-6 h-6 text-sky-600"></i>
                إضافة طالب جديد
            </h3>
            <button onclick="closeAddStudent()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <form id="addStudentForm" class="p-6 space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Personal Information -->
                <div class="col-span-2">
                    <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="user" class="w-5 h-5 text-sky-600"></i>
                        المعلومات الشخصية
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل *</label>
                    <input type="text" name="full_name" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">تاريخ الميلاد</label>
                    <input type="date" name="birth_date"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الجنس</label>
                    <select name="gender" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="">اختر...</option>
                        <option value="male">ذكر</option>
                        <option value="female">أنثى</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهوية / جواز السفر</label>
                    <input type="text" name="national_id"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Contact Information -->
                <div class="col-span-2 pt-4 border-t border-slate-200">
                    <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="phone" class="w-5 h-5 text-sky-600"></i>
                        معلومات الاتصال
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني *</label>
                    <input type="email" name="email" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف *</label>
                    <input type="tel" name="phone" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">هاتف ولي الأمر</label>
                    <input type="tel" name="guardian_phone"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
                    <input type="text" name="address"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <!-- Course Information -->
                <div class="col-span-2 pt-4 border-t border-slate-200">
                    <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="book-open" class="w-5 h-5 text-sky-600"></i>
                        معلومات الدورة
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الدورة *</label>
                    <select name="course_id" required id="courseSelect"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="">اختر الدورة...</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">المستوى التعليمي</label>
                    <select name="education_level" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="">اختر...</option>
                        <option value="elementary">ابتدائي</option>
                        <option value="middle">متوسط</option>
                        <option value="high">ثانوي</option>
                        <option value="diploma">دبلوم</option>
                        <option value="bachelor">بكالوريوس</option>
                        <option value="master">ماجستير</option>
                        <option value="phd">دكتوراه</option>
                    </select>
                </div>
                
                <!-- Account Information -->
                <div class="col-span-2 pt-4 border-t border-slate-200">
                    <h4 class="text-lg font-semibold text-slate-800 mb-4 flex items-center gap-2">
                        <i data-lucide="key" class="w-5 h-5 text-sky-600"></i>
                        معلومات الحساب
                    </h4>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">كلمة المرور *</label>
                    <div class="relative">
                        <input type="password" name="password" required id="passwordInput"
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <button type="button" onclick="togglePassword()" class="absolute left-3 top-1/2 -translate-y-1/2">
                            <i data-lucide="eye" class="w-5 h-5 text-slate-400"></i>
                        </button>
                    </div>
                    <button type="button" onclick="generatePassword()" class="text-sm text-sky-600 hover:text-sky-700 mt-1">
                        توليد كلمة مرور قوية
                    </button>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                    <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="pending">بانتظار الموافقة</option>
                        <option value="approved">مقبول</option>
                        <option value="active">نشط</option>
                        <option value="suspended">موقوف</option>
                    </select>
                </div>
                
                <div class="col-span-2">
                    <label class="flex items-center gap-2">
                        <input type="checkbox" name="send_email" checked class="rounded border-slate-300">
                        <span class="text-sm text-slate-700">إرسال بيانات الحساب عبر البريد الإلكتروني</span>
                    </label>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-sky-600 text-white py-3 rounded-lg hover:bg-sky-700 transition-colors font-semibold">
                    إضافة الطالب
                </button>
                <button type="button" onclick="closeAddStudent()" class="px-6 py-3 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Edit Student Modal -->
<div id="editStudentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="edit" class="w-6 h-6 text-amber-600"></i>
                تعديل بيانات الطالب
            </h3>
            <button onclick="closeEditStudent()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <form id="editStudentForm" class="p-6 space-y-6">
            <input type="hidden" name="student_id" id="editStudentId">
            <!-- Same fields as add form -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل *</label>
                    <input type="text" name="full_name" required id="editFullName"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني *</label>
                    <input type="email" name="email" required id="editEmail"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف *</label>
                    <input type="tel" name="phone" required id="editPhone"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الحالة</label>
                    <select name="status" id="editStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
                        <option value="pending">بانتظار الموافقة</option>
                        <option value="approved">مقبول</option>
                        <option value="active">نشط</option>
                        <option value="suspended">موقوف</option>
                        <option value="graduated">متخرج</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4 border-t border-slate-200">
                <button type="submit" class="flex-1 bg-amber-600 text-white py-3 rounded-lg hover:bg-amber-700 transition-colors font-semibold">
                    حفظ التعديلات
                </button>
                <button type="button" onclick="closeEditStudent()" class="px-6 py-3 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Student Details Modal -->
<div id="viewStudentModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl max-h-[90vh] overflow-y-auto">
        <div class="sticky top-0 bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="user" class="w-6 h-6 text-sky-600"></i>
                تفاصيل الطالب
            </h3>
            <button onclick="closeViewStudent()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <div id="studentDetailsContent" class="p-6">
            <!-- Content will be loaded dynamically -->
        </div>
    </div>
</div>

<!-- Import Students Modal -->
<div id="importStudentsModal" class="fixed inset-0 bg-slate-900/50 backdrop-blur-sm hidden items-center justify-center z-50 p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl">
        <div class="border-b border-slate-200 px-6 py-4 flex items-center justify-between">
            <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="file-spreadsheet" class="w-6 h-6 text-emerald-600"></i>
                استيراد الطلاب من Excel
            </h3>
            <button onclick="closeImportStudents()" class="p-2 hover:bg-slate-100 rounded-lg transition-colors">
                <i data-lucide="x" class="w-5 h-5 text-slate-600"></i>
            </button>
        </div>
        
        <div class="p-6 space-y-6">
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex gap-3">
                    <i data-lucide="info" class="w-5 h-5 text-blue-600 flex-shrink-0 mt-0.5"></i>
                    <div class="text-sm text-blue-800">
                        <p class="font-semibold mb-2">متطلبات ملف Excel:</p>
                        <ul class="list-disc list-inside space-y-1">
                            <li>الاسم الكامل (إجباري)</li>
                            <li>البريد الإلكتروني (إجباري)</li>
                            <li>رقم الهاتف (إجباري)</li>
                            <li>رقم الدورة (ID)</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر ملف Excel</label>
                <input type="file" id="excelFile" accept=".xlsx,.xls" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500">
            </div>
            
            <div class="flex gap-3">
                <button onclick="importExcel()" class="flex-1 bg-emerald-600 text-white py-3 rounded-lg hover:bg-emerald-700 transition-colors font-semibold">
                    استيراد
                </button>
                <button onclick="downloadTemplate()" class="px-6 py-3 border border-slate-300 rounded-lg hover:bg-slate-50 transition-colors">
                    تحميل نموذج
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentPage = 1;
let studentsData = [];

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    loadStudents();
    loadCourses();
    
    // Event listeners
    document.getElementById('searchStudents').addEventListener('input', debounce(loadStudents, 500));
    document.getElementById('filterStatus').addEventListener('change', loadStudents);
    document.getElementById('filterCourse').addEventListener('change', loadStudents);
    document.getElementById('filterPayment').addEventListener('change', loadStudents);
    
    // Form submissions
    document.getElementById('addStudentForm').addEventListener('submit', handleAddStudent);
    document.getElementById('editStudentForm').addEventListener('submit', handleEditStudent);
});

// Load students
async function loadStudents() {
    const search = document.getElementById('searchStudents').value;
    const status = document.getElementById('filterStatus').value;
    const course = document.getElementById('filterCourse').value;
    const payment = document.getElementById('filterPayment').value;
    
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/students.php?action=list&search=${search}&status=${status}&course=${course}&payment=${payment}&page=${currentPage}`);
        const result = await response.json();
        
        if (result.success) {
            studentsData = result.data;
            updateStatistics(result.statistics);
            renderStudents(result.data);
            updatePagination(result.pagination);
        }
    } catch (error) {
        console.error('Error loading students:', error);
        showNotification('حدث خطأ أثناء تحميل البيانات', 'error');
    }
    
    lucide.createIcons();
}

// Update statistics
function updateStatistics(stats) {
    document.getElementById('totalStudents').textContent = stats.total || 0;
    document.getElementById('activeStudents').textContent = stats.active || 0;
    document.getElementById('pendingStudents').textContent = stats.pending || 0;
    document.getElementById('paidStudents').textContent = stats.paid || 0;
    document.getElementById('newThisMonth').textContent = stats.new_this_month || 0;
}

// Render students table
function renderStudents(students) {
    const tbody = document.getElementById('studentsTableBody');
    
    if (students.length === 0) {
        tbody.innerHTML = `
            <tr>
                <td colspan="8" class="px-6 py-12 text-center">
                    <i data-lucide="users" class="w-12 h-12 mx-auto text-slate-300 mb-3"></i>
                    <p class="text-slate-600">لا يوجد طلاب</p>
                </td>
            </tr>
        `;
        return;
    }
    
    tbody.innerHTML = students.map(student => `
        <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
            <td class="px-6 py-4">
                <input type="checkbox" class="rounded border-slate-300" value="${student.id}">
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-3">
                    <img src="${student.photo || '<?php echo $managerBaseUrl; ?>/assets/default-avatar.png'}" 
                        class="w-10 h-10 rounded-full object-cover border-2 border-slate-200">
                    <div>
                        <p class="font-semibold text-slate-800">${student.full_name}</p>
                        <p class="text-xs text-slate-500">#${student.id}</p>
                    </div>
                </div>
            </td>
            <td class="px-6 py-4">
                <div class="text-sm">
                    <p class="text-slate-800 flex items-center gap-1">
                        <i data-lucide="mail" class="w-3 h-3"></i>
                        ${student.email}
                    </p>
                    <p class="text-slate-600 flex items-center gap-1 mt-1">
                        <i data-lucide="phone" class="w-3 h-3"></i>
                        ${student.phone}
                    </p>
                </div>
            </td>
            <td class="px-6 py-4">
                <span class="text-sm text-slate-700">${student.course_name || 'غير محدد'}</span>
            </td>
            <td class="px-6 py-4">
                ${getStatusBadge(student.status)}
            </td>
            <td class="px-6 py-4">
                ${getPaymentBadge(student.payment_status)}
            </td>
            <td class="px-6 py-4">
                <span class="text-sm text-slate-600">${formatDate(student.created_at)}</span>
            </td>
            <td class="px-6 py-4">
                <div class="flex items-center gap-2">
                    <button onclick="viewStudent(${student.id})" class="p-2 hover:bg-sky-50 rounded-lg transition-colors" title="عرض">
                        <i data-lucide="eye" class="w-4 h-4 text-sky-600"></i>
                    </button>
                    <button onclick="editStudent(${student.id})" class="p-2 hover:bg-amber-50 rounded-lg transition-colors" title="تعديل">
                        <i data-lucide="edit" class="w-4 h-4 text-amber-600"></i>
                    </button>
                    <button onclick="scanCard(${student.id})" class="p-2 hover:bg-purple-50 rounded-lg transition-colors" title="مسح البطاقة">
                        <i data-lucide="scan" class="w-4 h-4 text-purple-600"></i>
                    </button>
                    <button onclick="deleteStudent(${student.id})" class="p-2 hover:bg-red-50 rounded-lg transition-colors" title="حذف">
                        <i data-lucide="trash-2" class="w-4 h-4 text-red-600"></i>
                    </button>
                </div>
            </td>
        </tr>
    `).join('');
}

// Status badge
function getStatusBadge(status) {
    const badges = {
        pending: '<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">بانتظار الموافقة</span>',
        approved: '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">مقبول</span>',
        active: '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">نشط</span>',
        suspended: '<span class="px-2 py-1 bg-red-100 text-red-700 text-xs font-semibold rounded-full">موقوف</span>',
        graduated: '<span class="px-2 py-1 bg-purple-100 text-purple-700 text-xs font-semibold rounded-full">متخرج</span>'
    };
    return badges[status] || badges.pending;
}

// Payment badge
function getPaymentBadge(status) {
    const badges = {
        paid: '<span class="px-2 py-1 bg-emerald-100 text-emerald-700 text-xs font-semibold rounded-full">مدفوع</span>',
        pending: '<span class="px-2 py-1 bg-amber-100 text-amber-700 text-xs font-semibold rounded-full">بانتظار الدفع</span>',
        partial: '<span class="px-2 py-1 bg-blue-100 text-blue-700 text-xs font-semibold rounded-full">دفع جزئي</span>'
    };
    return badges[status] || badges.pending;
}

// Modal functions
function openAddStudent() {
    document.getElementById('addStudentModal').classList.remove('hidden');
    document.getElementById('addStudentModal').classList.add('flex');
}

function closeAddStudent() {
    document.getElementById('addStudentModal').classList.add('hidden');
    document.getElementById('addStudentModal').classList.remove('flex');
    document.getElementById('addStudentForm').reset();
}

// Handle add student
async function handleAddStudent(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/students.php?action=create', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم إضافة الطالب بنجاح', 'success');
            closeAddStudent();
            loadStudents();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء إضافة الطالب', 'error');
    }
}

// Scan card to view student
function scanCard(studentId) {
    viewStudent(studentId);
}

// View student details
async function viewStudent(studentId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/students.php?action=get&id=${studentId}`);
        const result = await response.json();
        
        if (result.success) {
            const student = result.data;
            document.getElementById('studentDetailsContent').innerHTML = `
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="col-span-1">
                        <img src="${student.photo || '<?php echo $managerBaseUrl; ?>/assets/default-avatar.png'}" 
                            class="w-full rounded-xl shadow-lg">
                        ${student.id_card_number ? `
                        <div class="mt-4 p-4 bg-purple-50 border border-purple-200 rounded-lg">
                            <p class="text-sm font-semibold text-purple-800">رقم البطاقة</p>
                            <p class="text-lg font-bold text-purple-900 mt-1">${student.id_card_number}</p>
                        </div>
                        ` : ''}
                    </div>
                    <div class="col-span-2 space-y-6">
                        <div>
                            <h4 class="font-semibold text-slate-800 mb-3">المعلومات الشخصية</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-slate-600">الاسم الكامل</p>
                                    <p class="font-semibold text-slate-800">${student.full_name}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">البريد الإلكتروني</p>
                                    <p class="font-semibold text-slate-800">${student.email}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">رقم الهاتف</p>
                                    <p class="font-semibold text-slate-800">${student.phone}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">الحالة</p>
                                    ${getStatusBadge(student.status)}
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <h4 class="font-semibold text-slate-800 mb-3">معلومات الدورة</h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <p class="text-sm text-slate-600">الدورة</p>
                                    <p class="font-semibold text-slate-800">${student.course_name || 'غير محدد'}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-slate-600">حالة الدفع</p>
                                    ${getPaymentBadge(student.payment_status)}
                                </div>
                            </div>
                        </div>
                        
                        <div class="flex gap-3 pt-4 border-t border-slate-200">
                            <button onclick="editStudent(${student.id}); closeViewStudent();" class="flex-1 bg-amber-600 text-white py-2 rounded-lg hover:bg-amber-700">
                                تعديل البيانات
                            </button>
                            <button onclick="window.location='?page=id_cards&student=${student.id}'" class="flex-1 bg-purple-600 text-white py-2 rounded-lg hover:bg-purple-700">
                                عرض البطاقة
                            </button>
                        </div>
                    </div>
                </div>
            `;
            
            document.getElementById('viewStudentModal').classList.remove('hidden');
            document.getElementById('viewStudentModal').classList.add('flex');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء تحميل البيانات', 'error');
    }
    
    lucide.createIcons();
}

function closeViewStudent() {
    document.getElementById('viewStudentModal').classList.add('hidden');
    document.getElementById('viewStudentModal').classList.remove('flex');
}

// Load courses
async function loadCourses() {
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/courses.php?action=list');
        const result = await response.json();
        
        if (result.success) {
            const courseSelect = document.getElementById('courseSelect');
            const filterCourse = document.getElementById('filterCourse');
            
            result.data.forEach(course => {
                courseSelect.innerHTML += `<option value="${course.id}">${course.title}</option>`;
                filterCourse.innerHTML += `<option value="${course.id}">${course.title}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG', { year: 'numeric', month: 'long', day: 'numeric' });
}

function generatePassword() {
    const password = Math.random().toString(36).slice(-8) + Math.random().toString(36).slice(-8).toUpperCase();
    document.getElementById('passwordInput').value = password;
    showNotification('تم توليد كلمة المرور: ' + password, 'success');
}

function togglePassword() {
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}

function showNotification(message, type) {
    // Use existing notification system
    if (window.DashboardIntegration && window.DashboardIntegration.showNotification) {
        DashboardIntegration.showNotification(message, type);
    } else {
        alert(message);
    }
}

// Import/Export functions
function openImportStudents() {
    document.getElementById('importStudentsModal').classList.remove('hidden');
    document.getElementById('importStudentsModal').classList.add('flex');
}

function closeImportStudents() {
    document.getElementById('importStudentsModal').classList.add('hidden');
    document.getElementById('importStudentsModal').classList.remove('flex');
}

async function importExcel() {
    const fileInput = document.getElementById('excelFile');
    if (!fileInput.files[0]) {
        showNotification('الرجاء اختيار ملف Excel', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('file', fileInput.files[0]);
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/students.php?action=import', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification(`تم استيراد ${result.count} طالب بنجاح`, 'success');
            closeImportStudents();
            loadStudents();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ أثناء الاستيراد', 'error');
    }
}

function downloadTemplate() {
    window.location.href = '<?php echo $managerBaseUrl; ?>/api/students.php?action=download_template';
}

async function exportStudents() {
    window.location.href = '<?php echo $managerBaseUrl; ?>/api/students.php?action=export';
}

// Edit and delete functions
async function editStudent(studentId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/students.php?action=get&id=${studentId}`);
        const result = await response.json();
        
        if (result.success) {
            const student = result.data;
            document.getElementById('editStudentId').value = student.id;
            document.getElementById('editFullName').value = student.full_name;
            document.getElementById('editEmail').value = student.email;
            document.getElementById('editPhone').value = student.phone;
            document.getElementById('editStatus').value = student.status;
            
            document.getElementById('editStudentModal').classList.remove('hidden');
            document.getElementById('editStudentModal').classList.add('flex');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
}

function closeEditStudent() {
    document.getElementById('editStudentModal').classList.add('hidden');
    document.getElementById('editStudentModal').classList.remove('flex');
}

async function handleEditStudent(e) {
    e.preventDefault();
    const formData = new FormData(e.target);
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/students.php?action=update', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم تحديث البيانات بنجاح', 'success');
            closeEditStudent();
            loadStudents();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
}

async function deleteStudent(studentId) {
    if (!confirm('هل أنت متأكد من حذف هذا الطالب؟ هذا الإجراء لا يمكن التراجع عنه.')) {
        return;
    }
    
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/students.php?action=delete&id=${studentId}`, {
            method: 'DELETE'
        });
        const result = await response.json();
        
        if (result.success) {
            showNotification('تم حذف الطالب بنجاح', 'success');
            loadStudents();
        } else {
            showNotification(result.message || 'حدث خطأ', 'error');
        }
    } catch (error) {
        console.error('Error:', error);
        showNotification('حدث خطأ', 'error');
    }
}

function updatePagination(pagination) {
    document.getElementById('showingFrom').textContent = pagination.from || 0;
    document.getElementById('showingTo').textContent = pagination.to || 0;
    document.getElementById('totalCount').textContent = pagination.total || 0;
}
</script>
