<?php
/**
 * Manager - Certificates Management
 * إدارة الشهادات
 */

global $managerHelper, $conn;

// Get filter parameters
$course_filter = $_GET['course'] ?? 'all';
$status_filter = $_GET['status'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get all courses for filter
$courses = $managerHelper->getAllCourses();

// Get certificates data
$query = "SELECT 
    u.id, u.full_name, u.email,
    c.course_id, c.name as course_name,
    e.completion_date, e.final_grade,
    cert.certificate_id, cert.certificate_number, cert.issue_date
FROM enrollments e
JOIN users u ON e.user_id = u.id
JOIN courses c ON e.course_id = c.course_id
LEFT JOIN certificates cert ON e.enrollment_id = cert.enrollment_id
WHERE e.status = 'completed' AND e.final_grade >= 60";

if ($course_filter !== 'all') {
    $query .= " AND c.course_id = " . intval($course_filter);
}

if ($status_filter === 'issued') {
    $query .= " AND cert.certificate_id IS NOT NULL";
} elseif ($status_filter === 'pending') {
    $query .= " AND cert.certificate_id IS NULL";
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND u.full_name LIKE '%{$search_safe}%'";
}

$query .= " ORDER BY e.completion_date DESC";

$certificates = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $certificates[] = $row;
    }
}

// Statistics
$total = count($certificates);
$issued = count(array_filter($certificates, fn($c) => !empty($c['certificate_id'])));
$pending = $total - $issued;
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="award" class="w-10 h-10"></i>
        إدارة الشهادات
    </h1>
    <p class="text-purple-100 text-lg">إصدار ومتابعة شهادات إتمام الدورات</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $total; ?></h3>
        <p class="text-slate-600">إجمالي المؤهلين</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $issued; ?></h3>
        <p class="text-slate-600">شهادات صادرة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $pending; ?></h3>
        <p class="text-slate-600">بانتظار الإصدار</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="certificates">
        
        <select name="course" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الدورات</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="status" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الحالات</option>
            <option value="issued" <?php echo $status_filter === 'issued' ? 'selected' : ''; ?>>صادرة</option>
            <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>بانتظار الإصدار</option>
        </select>

        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="ابحث باسم الطالب..." class="flex-1 px-4 py-2 border border-slate-300 rounded-lg">
        
        <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition">
            <i data-lucide="search" class="w-4 h-4 inline"></i> بحث
        </button>
    </form>
</div>

<!-- Certificates Table -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الطالب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدرجة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">تاريخ الإنهاء</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">رقم الشهادة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">إجراءات</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (empty($certificates)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                        <i data-lucide="award" class="w-12 h-12 mx-auto mb-2 text-slate-300"></i>
                        <p>لا توجد شهادات</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($certificates as $cert): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4">
                            <div>
                                <p class="font-medium"><?php echo htmlspecialchars($cert['full_name']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($cert['email']); ?></p>
                            </div>
                        </td>
                        <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($cert['course_name']); ?></td>
                        <td class="px-6 py-4">
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $cert['final_grade'] >= 80 ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700'; ?>">
                                <?php echo $cert['final_grade']; ?>%
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php echo $cert['completion_date'] ? date('Y/m/d', strtotime($cert['completion_date'])) : 'غير متاح'; ?>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php echo $cert['certificate_number'] ?? '-'; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($cert['certificate_id']): ?>
                                <span class="px-2 py-1 text-xs rounded-full bg-green-100 text-green-700">
                                    <i data-lucide="check-circle" class="w-3 h-3 inline"></i> صادرة
                                </span>
                            <?php else: ?>
                                <span class="px-2 py-1 text-xs rounded-full bg-amber-100 text-amber-700">
                                    <i data-lucide="clock" class="w-3 h-3 inline"></i> بانتظار
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="px-6 py-4">
                            <?php if ($cert['certificate_id']): ?>
                                <button class="text-purple-600 hover:text-purple-700 text-sm">
                                    <i data-lucide="download" class="w-4 h-4 inline"></i> تحميل
                                </button>
                            <?php else: ?>
                                <button class="text-green-600 hover:text-green-700 text-sm">
                                    <i data-lucide="file-plus" class="w-4 h-4 inline"></i> إصدار
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
