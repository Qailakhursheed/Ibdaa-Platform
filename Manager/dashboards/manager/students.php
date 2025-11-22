<?php
/**
 * Manager Dashboard - Students Management
 * إدارة الطلاب
 */

// Get students data from ManagerHelper
global $managerHelper;
$students = $managerHelper->getAllStudents();

// Apply filters
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$status_filter = isset($_GET['status']) ? $_GET['status'] : 'all';

if (!empty($search)) {
    $students = array_filter($students, function($s) use ($search) {
        return stripos($s['full_name'], $search) !== false || 
               stripos($s['email'], $search) !== false;
    });
}

if ($status_filter !== 'all') {
    $students = array_filter($students, function($s) use ($status_filter) {
        return $s['status'] === $status_filter;
    });
}

// Get statistics
$total_students = count($students);
$active_students = count(array_filter($students, function($s) { return $s['status'] === 'active'; }));
$inactive_students = $total_students - $active_students;
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-blue-500 to-indigo-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="users" class="w-10 h-10"></i>
                إدارة الطلاب
            </h1>
            <p class="text-blue-100 text-lg">عرض وإدارة جميع الطلاب المسجلين</p>
        </div>
        <div class="text-left">
            <p class="text-3xl font-bold"><?php echo number_format($total_students); ?></p>
            <p class="text-blue-100">إجمالي الطلاب</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="user-check" class="w-6 h-6 text-green-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($active_students); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">طلاب نشطون</h3>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i data-lucide="user-x" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($inactive_students); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">غير نشطون</h3>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="percent" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800">
                <?php echo $total_students > 0 ? round(($active_students / $total_students) * 100) : 0; ?>%
            </span>
        </div>
        <h3 class="text-slate-600 font-semibold">نسبة النشاط</h3>
    </div>
</div>

<!-- Filters and Search -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <input type="hidden" name="page" value="students">
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">بحث</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="اسم الطالب أو البريد الإلكتروني..." 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg">
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
            <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>الكل</option>
                <option value="active" <?php echo $status_filter === 'active' ? 'selected' : ''; ?>>نشط</option>
                <option value="inactive" <?php echo $status_filter === 'inactive' ? 'selected' : ''; ?>>غير نشط</option>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold">
                <i data-lucide="search" class="w-5 h-5 inline"></i> بحث
            </button>
            <a href="?page=students" class="px-6 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 font-semibold">
                <i data-lucide="x" class="w-5 h-5 inline"></i>
            </a>
        </div>
    </form>
</div>

<!-- Students Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="p-6 border-b border-slate-200">
        <h3 class="text-xl font-bold text-slate-800">قائمة الطلاب</h3>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">#</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الاسم</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">البريد الإلكتروني</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الدورات</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">تاريخ التسجيل</th>
                    <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">إجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($students)): ?>
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                            <p class="text-slate-500">لا توجد نتائج</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($students as $index => $student): ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo $index + 1; ?></td>
                            <td class="px-6 py-4">
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></p>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600"><?php echo htmlspecialchars($student['email']); ?></td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">
                                    <?php echo $student['courses_count']; ?> دورة
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($student['status'] === 'active'): ?>
                                    <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-sm font-semibold">
                                        <i data-lucide="check-circle" class="w-4 h-4 inline"></i> نشط
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-red-100 text-red-700 rounded-full text-sm font-semibold">
                                        <i data-lucide="x-circle" class="w-4 h-4 inline"></i> غير نشط
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-slate-600">
                                <?php echo date('Y-m-d', strtotime($student['created_at'])); ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex gap-2">
                                    <a href="?page=chat&user=<?php echo $student['id']; ?>" 
                                       class="px-3 py-1 bg-purple-100 text-purple-700 rounded-lg hover:bg-purple-200 text-sm font-semibold">
                                        <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                                    </a>
                                    <button onclick="showStudentDetails(<?php echo $student['id']; ?>)" 
                                            class="px-3 py-1 bg-blue-100 text-blue-700 rounded-lg hover:bg-blue-200 text-sm font-semibold">
                                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function showStudentDetails(studentId) {
    alert('سيتم عرض تفاصيل الطالب #' + studentId);
}

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>
