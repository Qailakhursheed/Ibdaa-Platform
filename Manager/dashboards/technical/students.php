<?php
/**
 * Technical Dashboard - Students Management (Hybrid PHP System)
 * إدارة الطلاب - نظام هجين محدث
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper is already initialized
 */

// Get students data
$students = $technicalHelper->getAllStudents();
$stats = $technicalHelper->getStatistics();

// Handle student actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'update_status':
                $studentId = $_POST['student_id'];
                $status = $_POST['status'];
                if ($technicalHelper->updateStudentStatus($studentId, $status)) {
                    $_SESSION['success_message'] = 'تم تحديث حالة الطالب بنجاح';
                    header('Location: students.php');
                    exit;
                }
                break;
        }
    }
}

// Calculate statistics
$activeStudents = array_filter($students, function($s) { return $s['status'] === 'active'; });
$pendingStudents = array_filter($students, function($s) { return $s['status'] === 'pending'; });
$totalEnrollments = array_sum(array_column($students, 'enrolled_courses'));
$avgGPA = count($students) > 0 ? array_sum(array_column($students, 'gpa')) / count($students) : 0;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-3xl font-bold text-slate-800 flex items-center gap-3">
                <i data-lucide="users" class="w-8 h-8 text-sky-600"></i>
                إدارة الطلاب (المتدربين)
            </h2>
            <p class="text-slate-600 mt-2">إدارة شاملة لجميع الطلاب المسجلين في المنصة - <?php echo count($students); ?> طالب</p>
        </div>
        <div class="flex gap-3">
            <button onclick="exportStudents()" class="flex items-center gap-2 px-4 py-2.5 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors shadow-lg">
                <i data-lucide="download" class="w-5 h-5"></i>
                <span class="font-semibold">تصدير Excel</span>
            </button>
            <button onclick="openAddStudent()" class="flex items-center gap-2 px-4 py-2.5 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition-colors shadow-lg">
                <i data-lucide="user-plus" class="w-5 h-5"></i>
                <span class="font-semibold">إضافة طالب</span>
            </button>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
        <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-10 h-10 opacity-80"></i>
                <span class="text-4xl font-bold"><?php echo count($students); ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">إجمالي الطلاب</p>
        </div>
        
        <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="user-check" class="w-10 h-10 opacity-80"></i>
                <span class="text-4xl font-bold"><?php echo count($activeStudents); ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">طلاب نشطون</p>
        </div>
        
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="clock" class="w-10 h-10 opacity-80"></i>
                <span class="text-4xl font-bold"><?php echo count($pendingStudents); ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">بانتظار الموافقة</p>
        </div>
        
        <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-10 h-10 opacity-80"></i>
                <span class="text-4xl font-bold"><?php echo $totalEnrollments; ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">إجمالي التسجيلات</p>
        </div>
        
        <div class="bg-gradient-to-br from-pink-500 to-rose-600 rounded-xl shadow-lg p-6 text-white">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="award" class="w-10 h-10 opacity-80"></i>
                <span class="text-4xl font-bold"><?php echo round($avgGPA, 1); ?>%</span>
            </div>
            <p class="text-sm opacity-90 font-semibold">متوسط المعدل</p>
        </div>
    </div>

    <!-- Filters and Search -->
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
                <div class="relative">
                    <i data-lucide="search" class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                    <input type="text" id="searchStudents" placeholder="ابحث بالاسم أو البريد..." 
                        class="w-full pr-10 pl-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                        onkeyup="filterStudents()">
                </div>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
                <select id="filterStatus" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterStudents()">
                    <option value="">جميع الحالات</option>
                    <option value="active">نشط</option>
                    <option value="pending">معلق</option>
                    <option value="inactive">غير نشط</option>
                    <option value="graduated">متخرج</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الترتيب</label>
                <select id="sortBy" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterStudents()">
                    <option value="name_asc">الاسم (أ-ي)</option>
                    <option value="name_desc">الاسم (ي-أ)</option>
                    <option value="gpa_desc">المعدل (الأعلى)</option>
                    <option value="gpa_asc">المعدل (الأقل)</option>
                    <option value="date_desc">الأحدث</option>
                    <option value="date_asc">الأقدم</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">عدد الصفوف</label>
                <select id="rowsPerPage" class="w-full px-4 py-2.5 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterStudents()">
                    <option value="10">10</option>
                    <option value="25" selected>25</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="all">الكل</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Students Table -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-slate-700 to-slate-800 text-white">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-bold">#</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">البريد الإلكتروني</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">الدورات</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">المعدل</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">الحضور</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-bold">الإجراءات</th>
                    </tr>
                </thead>
                <tbody id="studentsTableBody">
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <i data-lucide="users" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                                <h3 class="text-xl font-bold text-slate-800 mb-2">لا يوجد طلاب</h3>
                                <p class="text-slate-500">لم يتم إضافة أي طالب بعد</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $index => $student): 
                            $statusColors = [
                                'active' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'inactive' => 'bg-slate-100 text-slate-700',
                                'graduated' => 'bg-sky-100 text-sky-700'
                            ];
                            $statusNames = [
                                'active' => 'نشط',
                                'pending' => 'معلق',
                                'inactive' => 'غير نشط',
                                'graduated' => 'متخرج'
                            ];
                            
                            $attendanceRate = $student['attendance_total'] > 0 
                                ? round(($student['attendance_present'] / $student['attendance_total']) * 100) 
                                : 0;
                        ?>
                            <tr class="student-row border-b border-slate-200 hover:bg-slate-50 transition-colors"
                                data-name="<?php echo strtolower($student['full_name']); ?>"
                                data-email="<?php echo strtolower($student['email']); ?>"
                                data-status="<?php echo $student['status']; ?>"
                                data-gpa="<?php echo $student['gpa']; ?>"
                                data-date="<?php echo strtotime($student['created_at'] ?? 'now'); ?>">
                                <td class="px-6 py-4 text-slate-600 font-semibold"><?php echo $index + 1; ?></td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo $student['photo'] ?? ($platformBaseUrl . '/photos/default-avatar.png'); ?>" 
                                             alt="<?php echo htmlspecialchars($student['full_name']); ?>"
                                             class="w-10 h-10 rounded-full object-cover border-2 border-sky-200">
                                        <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-slate-600"><?php echo htmlspecialchars($student['email']); ?></td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-sm font-semibold">
                                        <?php echo $student['enrolled_courses']; ?> دورة
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-slate-200 rounded-full h-2">
                                            <div class="bg-sky-600 h-2 rounded-full" style="width: <?php echo min($student['gpa'], 100); ?>%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700"><?php echo round($student['gpa'], 1); ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <div class="w-16 bg-slate-200 rounded-full h-2">
                                            <div class="bg-emerald-600 h-2 rounded-full" style="width: <?php echo $attendanceRate; ?>%"></div>
                                        </div>
                                        <span class="text-sm font-semibold text-slate-700"><?php echo $attendanceRate; ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-3 py-1 <?php echo $statusColors[$student['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-xs font-semibold">
                                        <?php echo $statusNames[$student['status']] ?? $student['status']; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-2">
                                        <button onclick="viewStudent(<?php echo $student['user_id']; ?>)" 
                                            class="p-2 text-sky-600 hover:bg-sky-50 rounded-lg transition-colors" title="عرض">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </button>
                                        <button onclick="editStudent(<?php echo $student['user_id']; ?>)" 
                                            class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="تعديل">
                                            <i data-lucide="edit" class="w-5 h-5"></i>
                                        </button>
                                        <button onclick="changeStatus(<?php echo $student['user_id']; ?>, '<?php echo $student['status']; ?>')" 
                                            class="p-2 text-amber-600 hover:bg-amber-50 rounded-lg transition-colors" title="تغيير الحالة">
                                            <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <!-- Pagination -->
        <div class="px-6 py-4 bg-slate-50 border-t border-slate-200 flex items-center justify-between">
            <div class="text-sm text-slate-600">
                عرض <span id="showingCount"><?php echo count($students); ?></span> من <span id="totalCount"><?php echo count($students); ?></span> طالب
            </div>
            <div id="paginationControls" class="flex gap-2">
                <!-- Will be populated by JS if needed -->
            </div>
        </div>
    </div>
</div>

<script>
function filterStudents() {
    const search = document.getElementById('searchStudents').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const sortBy = document.getElementById('sortBy').value;
    const rowsPerPage = document.getElementById('rowsPerPage').value;
    
    let rows = Array.from(document.querySelectorAll('.student-row'));
    
    // Filter
    let visibleRows = rows.filter(row => {
        const name = row.dataset.name;
        const email = row.dataset.email;
        const rowStatus = row.dataset.status;
        
        const matchSearch = !search || name.includes(search) || email.includes(search);
        const matchStatus = !status || rowStatus === status;
        
        return matchSearch && matchStatus;
    });
    
    // Sort
    visibleRows.sort((a, b) => {
        switch(sortBy) {
            case 'name_asc':
                return a.dataset.name.localeCompare(b.dataset.name);
            case 'name_desc':
                return b.dataset.name.localeCompare(a.dataset.name);
            case 'gpa_desc':
                return parseFloat(b.dataset.gpa) - parseFloat(a.dataset.gpa);
            case 'gpa_asc':
                return parseFloat(a.dataset.gpa) - parseFloat(b.dataset.gpa);
            case 'date_desc':
                return parseInt(b.dataset.date) - parseInt(a.dataset.date);
            case 'date_asc':
                return parseInt(a.dataset.date) - parseInt(b.dataset.date);
            default:
                return 0;
        }
    });
    
    // Hide all rows first
    rows.forEach(row => row.style.display = 'none');
    
    // Show visible rows based on pagination
    const limit = rowsPerPage === 'all' ? visibleRows.length : parseInt(rowsPerPage);
    visibleRows.slice(0, limit).forEach(row => row.style.display = '');
    
    // Update counts
    document.getElementById('showingCount').textContent = Math.min(limit, visibleRows.length);
    document.getElementById('totalCount').textContent = visibleRows.length;
    
    // Recreate lucide icons
    lucide.createIcons();
}

function viewStudent(studentId) {
    window.location.href = `student_details.php?id=${studentId}`;
}

function editStudent(studentId) {
    alert('تحرير الطالب #' + studentId);
}

function changeStatus(studentId, currentStatus) {
    const statuses = ['active', 'pending', 'inactive', 'graduated'];
    const statusNames = {'active': 'نشط', 'pending': 'معلق', 'inactive': 'غير نشط', 'graduated': 'متخرج'};
    
    let options = statuses.map(s => `<option value="${s}" ${s === currentStatus ? 'selected' : ''}>${statusNames[s]}</option>`).join('');
    
    const newStatus = prompt(`تغيير حالة الطالب:\n\nالحالة الحالية: ${statusNames[currentStatus]}\n\nاختر الحالة الجديدة (active/pending/inactive/graduated):`);
    
    if (newStatus && statuses.includes(newStatus)) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="action" value="update_status">
            <input type="hidden" name="student_id" value="${studentId}">
            <input type="hidden" name="status" value="${newStatus}">
        `;
        document.body.appendChild(form);
        form.submit();
    }
}

function exportStudents() {
    alert('جاري تصدير البيانات إلى Excel...');
    // Will implement Excel export
}

function openAddStudent() {
    alert('سيتم فتح نموذج إضافة طالب جديد');
}

lucide.createIcons();

<?php if (isset($_SESSION['success_message'])): ?>
    alert('<?php echo $_SESSION['success_message']; ?>');
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
</script>
