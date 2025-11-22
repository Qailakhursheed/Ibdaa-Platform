<?php
/**
 * Manager - Grades Management
 * إدارة الدرجات
 */

global $managerHelper;

$course_filter = $_GET['course'] ?? 'all';
$search = $_GET['search'] ?? '';

// جلب الدورات للفلتر
$courses_list = [];
$courses_result = $conn->query("SELECT course_id as id, course_name as name FROM courses ORDER BY course_name");
if ($courses_result) {
    while ($row = $courses_result->fetch_assoc()) {
        $courses_list[] = $row;
    }
}

// جلب الدرجات مع معلومات الطلاب والدورات
$query = "SELECT u.user_id as id, u.full_name, u.email, 
                 c.course_name, 
                 e.midterm_grade, e.final_grade,
                 COALESCE(e.midterm_grade, 0) + COALESCE(e.final_grade, 0) as total_grade
          FROM enrollments e
          JOIN users u ON e.user_id = u.user_id
          JOIN courses c ON e.course_id = c.course_id
          WHERE u.role = 'student'";

if ($course_filter !== 'all') {
    $query .= " AND c.course_id = " . intval($course_filter);
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND u.full_name LIKE '%{$search_safe}%'";
}

$query .= " ORDER BY u.full_name, c.course_name";

$grades = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
}
$query = "SELECT 
    u.id as student_id, u.name as student_name, u.email,
    c.id as course_id, c.name as course_name,
    e.midterm_grade, e.final_exam_grade, e.final_grade, e.status
    FROM enrollments e
    JOIN users u ON e.user_id = u.id
    JOIN courses c ON e.course_id = c.id
    WHERE 1=1";

if ($course_filter !== 'all') {
    $query .= " AND c.id = " . (int)$course_filter;
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND u.name LIKE '%{$search_safe}%'";
}

$query .= " ORDER BY c.name, u.name";

$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $grades[] = $row;
    }
}

// إحصائيات
$stats = [
    'total' => count($grades),
    'excellent' => 0,
    'very_good' => 0,
    'good' => 0,
    'pass' => 0,
    'fail' => 0
];

foreach ($grades as $grade) {
    $final = (int)$grade['final_grade'];
    if ($final >= 90) $stats['excellent']++;
    elseif ($final >= 80) $stats['very_good']++;
    elseif ($final >= 70) $stats['good']++;
    elseif ($final >= 60) $stats['pass']++;
    else $stats['fail']++;
}
?>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="grades">
        
        <select name="course" class="px-4 py-2 border rounded-lg">
            <option value="all">جميع الدورات</option>
            <?php foreach ($courses_list as $course): ?>
                <option value="<?php echo $course['id']; ?>" <?php echo $course_filter == $course['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ابحث عن طالب..." class="flex-1 px-4 py-2 border rounded-lg">
        
        <button type="submit" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
            <i data-lucide="search" class="w-4 h-4 inline"></i> بحث
        </button>
    </form>
</div>

<!-- Stats -->
<div class="grid grid-cols-5 gap-4 mb-6">
    <div class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 text-center">
        <p class="text-sm text-emerald-600 mb-1">ممتاز</p>
        <p class="text-2xl font-bold text-emerald-700"><?php echo $stats['excellent']; ?></p>
    </div>
    <div class="bg-sky-50 border border-sky-200 rounded-xl p-4 text-center">
        <p class="text-sm text-sky-600 mb-1">جيد جداً</p>
        <p class="text-2xl font-bold text-sky-700"><?php echo $stats['very_good']; ?></p>
    </div>
    <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 text-center">
        <p class="text-sm text-amber-600 mb-1">جيد</p>
        <p class="text-2xl font-bold text-amber-700"><?php echo $stats['good']; ?></p>
    </div>
    <div class="bg-violet-50 border border-violet-200 rounded-xl p-4 text-center">
        <p class="text-sm text-violet-600 mb-1">مقبول</p>
        <p class="text-2xl font-bold text-violet-700"><?php echo $stats['pass']; ?></p>
    </div>
    <div class="bg-red-50 border border-red-200 rounded-xl p-4 text-center">
        <p class="text-sm text-red-600 mb-1">راسب</p>
        <p class="text-2xl font-bold text-red-700"><?php echo $stats['fail']; ?></p>
    </div>
</div>

<!-- Grades Table -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="p-6 border-b flex justify-between items-center">
        <h3 class="text-xl font-bold">الدرجات</h3>
        <button onclick="window.print()" class="px-4 py-2 bg-slate-100 rounded-lg hover:bg-slate-200 transition">
            <i data-lucide="printer" class="w-4 h-4 inline"></i> طباعة
        </button>
    </div>
    
    <?php if (!empty($grades)): ?>
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الطالب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">نصفي</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">نهائي</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">المجموع</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">التقدير</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($grades as $grade): 
                $final = (int)$grade['final_grade'];
                $grade_label = $final >= 90 ? 'ممتاز' : ($final >= 80 ? 'جيد جداً' : ($final >= 70 ? 'جيد' : ($final >= 60 ? 'مقبول' : 'راسب')));
                $grade_color = $final >= 90 ? 'bg-emerald-100 text-emerald-700' : ($final >= 80 ? 'bg-sky-100 text-sky-700' : ($final >= 70 ? 'bg-amber-100 text-amber-700' : ($final >= 60 ? 'bg-violet-100 text-violet-700' : 'bg-red-100 text-red-700')));
            ?>
            <tr class="hover:bg-slate-50">
                <td class="px-6 py-4">
                    <p class="font-medium"><?php echo htmlspecialchars($grade['student_name']); ?></p>
                    <p class="text-xs text-slate-500"><?php echo htmlspecialchars($grade['email']); ?></p>
                </td>
                <td class="px-6 py-4 text-sm"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                <td class="px-6 py-4 text-sm font-bold"><?php echo $grade['midterm_grade'] ?? '-'; ?></td>
                <td class="px-6 py-4 text-sm font-bold"><?php echo $grade['final_exam_grade'] ?? '-'; ?></td>
                <td class="px-6 py-4 text-lg font-bold text-slate-800"><?php echo $final; ?>%</td>
                <td class="px-6 py-4">
                    <span class="px-3 py-1 text-xs rounded-full <?php echo $grade_color; ?>">
                        <?php echo $grade_label; ?>
                    </span>
                </td>
                <td class="px-6 py-4">
                    <span class="px-2 py-1 text-xs rounded-full <?php echo $grade['status'] === 'completed' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'; ?>">
                        <?php echo $grade['status'] === 'completed' ? 'مكتمل' : 'جاري'; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
    <div class="text-center py-12">
        <i data-lucide="inbox" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
        <p class="text-slate-500">لا توجد درجات</p>
    </div>
    <?php endif; ?>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
