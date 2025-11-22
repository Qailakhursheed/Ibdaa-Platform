<?php
/**
 * Manager - Graduates Management Page
 * صفحة إدارة الخريجين
 */

// جلب قائمة الخريجين
$graduates = [];
$query = "SELECT u.id, u.name, u.email, u.phone, 
        c.name as course_name,
        e.completion_date, e.final_grade, e.certificate_number
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        JOIN courses c ON e.course_id = c.id
        WHERE e.certificate_issued = 1 AND u.role = 'student'
        ORDER BY e.completion_date DESC";

try {
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $graduates[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Graduates query error: " . $e->getMessage());
}

// إحصائيات
$stats = [
    'total' => count($graduates),
    'this_month' => 0,
    'excellent' => 0,
    'very_good' => 0
];

$current_month = date('Y-m');
foreach ($graduates as $grad) {
    if (strpos($grad['completion_date'], $current_month) === 0) {
        $stats['this_month']++;
    }
    $grade = (int)$grad['final_grade'];
    if ($grade >= 90) {
        $stats['excellent']++;
    } elseif ($grade >= 80) {
        $stats['very_good']++;
    }
}
?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-violet-50 text-violet-600">
                <i data-lucide="award" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي الخريجين</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['total']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i data-lucide="calendar" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">خريجو هذا الشهر</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['this_month']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-amber-50 text-amber-600">
                <i data-lucide="star" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">تقدير ممتاز</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['excellent']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-sky-50 text-sky-600">
                <i data-lucide="trending-up" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">تقدير جيد جداً</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['very_good']; ?></p>
    </div>
</div>

<!-- Graduates List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
        <div>
            <h3 class="text-xl font-bold text-slate-800">ملف الخريجين</h3>
            <p class="text-sm text-slate-600 mt-1">قائمة الطلاب الذين حصلوا على شهادات</p>
        </div>
        <div class="flex gap-2">
            <button onclick="window.print()" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition">
                <i data-lucide="printer" class="w-4 h-4 inline"></i>
                طباعة
            </button>
            <button onclick="exportGraduates()" class="px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition">
                <i data-lucide="download" class="w-4 h-4 inline"></i>
                تصدير Excel
            </button>
        </div>
    </div>

    <div class="overflow-x-auto">
        <?php if (!empty($graduates)): ?>
            <table class="w-full">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">#</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الاسم</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدورة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">التقدير</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدرجة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">تاريخ التخرج</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">رقم الشهادة</th>
                        <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-200">
                    <?php 
                    $counter = 1;
                    foreach ($graduates as $grad): 
                        $grade = (int)$grad['final_grade'];
                        $grade_label = $grade >= 90 ? 'ممتاز' : ($grade >= 80 ? 'جيد جداً' : ($grade >= 70 ? 'جيد' : 'مقبول'));
                        $grade_color = $grade >= 90 ? 'bg-emerald-100 text-emerald-700' : ($grade >= 80 ? 'bg-sky-100 text-sky-700' : ($grade >= 70 ? 'bg-amber-100 text-amber-700' : 'bg-slate-100 text-slate-700'));
                    ?>
                        <tr class="hover:bg-slate-50 transition">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-900"><?php echo $counter++; ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center">
                                        <i data-lucide="user" class="w-5 h-5 text-violet-600"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-slate-900"><?php echo htmlspecialchars($grad['name']); ?></p>
                                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($grad['email']); ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-700"><?php echo htmlspecialchars($grad['course_name']); ?></td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-3 py-1 text-xs rounded-full <?php echo $grade_color; ?>">
                                    <?php echo $grade_label; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-slate-800"><?php echo $grade; ?>%</td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-slate-600">
                                <?php echo date('Y/m/d', strtotime($grad['completion_date'])); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-mono text-sky-600">
                                <?php echo htmlspecialchars($grad['certificate_number']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <div class="flex gap-2">
                                    <a href="?page=certificates&view=<?php echo $grad['certificate_number']; ?>" class="p-2 text-violet-600 hover:bg-violet-50 rounded-lg transition" title="عرض الشهادة">
                                        <i data-lucide="eye" class="w-4 h-4"></i>
                                    </a>
                                    <a href="?page=certificates&download=<?php echo $grad['certificate_number']; ?>" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition" title="تحميل الشهادة">
                                        <i data-lucide="download" class="w-4 h-4"></i>
                                    </a>
                                    <a href="?page=chat&user=<?php echo $grad['id']; ?>" class="p-2 text-sky-600 hover:bg-sky-50 rounded-lg transition" title="مراسلة">
                                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <div class="text-center py-12">
                <i data-lucide="inbox" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
                <h3 class="text-lg font-medium text-slate-700 mb-2">لا يوجد خريجون بعد</h3>
                <p class="text-slate-500">سيتم عرض الطلاب الذين حصلوا على شهادات هنا</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Graduate Highlights -->
<?php if (!empty($graduates)): ?>
<div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mt-6">
    <!-- Top Graduates -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="trophy" class="w-5 h-5 text-amber-500"></i>
            أفضل الخريجين
        </h3>
        <div class="space-y-3">
            <?php
            $top_graduates = array_slice($graduates, 0, 5);
            usort($top_graduates, function($a, $b) {
                return (int)$b['final_grade'] - (int)$a['final_grade'];
            });
            $rank = 1;
            foreach ($top_graduates as $grad):
            ?>
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50">
                    <div class="w-8 h-8 rounded-full bg-amber-100 text-amber-700 flex items-center justify-center font-bold text-sm">
                        <?php echo $rank++; ?>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($grad['name']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($grad['course_name']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-lg font-bold text-emerald-600"><?php echo $grad['final_grade']; ?>%</p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- Recent Graduates -->
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="clock" class="w-5 h-5 text-sky-500"></i>
            أحدث الخريجين
        </h3>
        <div class="space-y-3">
            <?php
            $recent_graduates = array_slice($graduates, 0, 5);
            foreach ($recent_graduates as $grad):
            ?>
                <div class="flex items-center gap-3 p-3 rounded-lg bg-slate-50">
                    <div class="w-10 h-10 rounded-full bg-violet-100 flex items-center justify-center">
                        <i data-lucide="user" class="w-5 h-5 text-violet-600"></i>
                    </div>
                    <div class="flex-1">
                        <p class="text-sm font-medium text-slate-800"><?php echo htmlspecialchars($grad['name']); ?></p>
                        <p class="text-xs text-slate-500"><?php echo htmlspecialchars($grad['course_name']); ?></p>
                    </div>
                    <div class="text-right">
                        <p class="text-xs text-slate-500"><?php echo date('Y/m/d', strtotime($grad['completion_date'])); ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function exportGraduates() {
    alert('سيتم تصدير البيانات إلى Excel قريباً');
}

if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
