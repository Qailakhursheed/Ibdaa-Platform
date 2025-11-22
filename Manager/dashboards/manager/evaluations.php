<?php
/**
 * Manager - Course Evaluations
 * تقييمات الدورات
 */

global $managerHelper, $conn;

// Get filter parameters
$course_filter = $_GET['course'] ?? 'all';
$rating_filter = $_GET['rating'] ?? 'all';

// Get all courses
$courses = $managerHelper->getAllCourses();

// Get evaluations
$query = "SELECT 
    e.*,
    u.full_name as student_name,
    c.name as course_name,
    t.full_name as trainer_name
FROM course_evaluations e
JOIN users u ON e.student_id = u.id
JOIN courses c ON e.course_id = c.course_id
JOIN users t ON c.trainer_id = t.id
WHERE 1=1";

if ($course_filter !== 'all') {
    $query .= " AND c.course_id = " . intval($course_filter);
}

if ($rating_filter !== 'all') {
    switch ($rating_filter) {
        case 'excellent':
            $query .= " AND e.rating >= 4.5";
            break;
        case 'good':
            $query .= " AND e.rating >= 3.5 AND e.rating < 4.5";
            break;
        case 'average':
            $query .= " AND e.rating >= 2.5 AND e.rating < 3.5";
            break;
        case 'poor':
            $query .= " AND e.rating < 2.5";
            break;
    }
}

$query .= " ORDER BY e.evaluation_date DESC";

$evaluations = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $evaluations[] = $row;
    }
}

// Statistics
$total = count($evaluations);
$avg_rating = $total > 0 ? array_sum(array_column($evaluations, 'rating')) / $total : 0;
$excellent = count(array_filter($evaluations, fn($e) => $e['rating'] >= 4.5));
$good = count(array_filter($evaluations, fn($e) => $e['rating'] >= 3.5 && $e['rating'] < 4.5));
$average = count(array_filter($evaluations, fn($e) => $e['rating'] >= 2.5 && $e['rating'] < 3.5));
$poor = count(array_filter($evaluations, fn($e) => $e['rating'] < 2.5));
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="star" class="w-10 h-10"></i>
        تقييمات الدورات
    </h1>
    <p class="text-amber-100 text-lg">آراء وتقييمات الطلاب</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-6 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 col-span-2">
        <div class="flex items-center gap-4">
            <div class="w-16 h-16 rounded-full bg-amber-100 flex items-center justify-center">
                <i data-lucide="star" class="w-8 h-8 text-amber-600"></i>
            </div>
            <div>
                <h3 class="text-4xl font-bold text-slate-800"><?php echo number_format($avg_rating, 1); ?></h3>
                <p class="text-slate-600">متوسط التقييم</p>
                <div class="flex gap-1 mt-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                        <i data-lucide="star" class="w-4 h-4 <?php echo $i <= round($avg_rating) ? 'text-amber-500 fill-current' : 'text-slate-300'; ?>"></i>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $excellent; ?></h3>
        <p class="text-slate-600">ممتاز</p>
        <p class="text-sm text-green-600 mt-1">5-4.5 نجوم</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $good; ?></h3>
        <p class="text-slate-600">جيد</p>
        <p class="text-sm text-blue-600 mt-1">4.5-3.5 نجوم</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $average; ?></h3>
        <p class="text-slate-600">متوسط</p>
        <p class="text-sm text-amber-600 mt-1">3.5-2.5 نجوم</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $poor; ?></h3>
        <p class="text-slate-600">ضعيف</p>
        <p class="text-sm text-red-600 mt-1">&lt;2.5 نجوم</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="evaluations">
        
        <select name="course" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الدورات</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="rating" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع التقييمات</option>
            <option value="excellent" <?php echo $rating_filter === 'excellent' ? 'selected' : ''; ?>>ممتاز (5-4.5)</option>
            <option value="good" <?php echo $rating_filter === 'good' ? 'selected' : ''; ?>>جيد (4.5-3.5)</option>
            <option value="average" <?php echo $rating_filter === 'average' ? 'selected' : ''; ?>>متوسط (3.5-2.5)</option>
            <option value="poor" <?php echo $rating_filter === 'poor' ? 'selected' : ''; ?>>ضعيف (&lt;2.5)</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
            <i data-lucide="filter" class="w-4 h-4 inline"></i> تطبيق
        </button>
    </form>
</div>

<!-- Evaluations List -->
<div class="space-y-4">
    <?php if (empty($evaluations)): ?>
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i data-lucide="star" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
            <p class="text-slate-500 text-lg">لا توجد تقييمات</p>
        </div>
    <?php else: ?>
        <?php foreach ($evaluations as $evaluation): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($evaluation['course_name']); ?></h3>
                            <div class="flex gap-1">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i data-lucide="star" class="w-4 h-4 <?php echo $i <= round($evaluation['rating']) ? 'text-amber-500 fill-current' : 'text-slate-300'; ?>"></i>
                                <?php endfor; ?>
                            </div>
                            <span class="px-3 py-1 text-sm rounded-full bg-amber-100 text-amber-700 font-bold">
                                <?php echo number_format($evaluation['rating'], 1); ?>
                            </span>
                        </div>
                        <p class="text-slate-600 mb-3"><?php echo nl2br(htmlspecialchars($evaluation['comment'] ?? '')); ?></p>
                        <div class="flex items-center gap-4 text-sm text-slate-500">
                            <span><i data-lucide="user" class="w-4 h-4 inline"></i> <?php echo htmlspecialchars($evaluation['student_name']); ?></span>
                            <span><i data-lucide="user-check" class="w-4 h-4 inline"></i> المدرب: <?php echo htmlspecialchars($evaluation['trainer_name']); ?></span>
                            <span><i data-lucide="calendar" class="w-4 h-4 inline"></i> <?php echo date('Y/m/d', strtotime($evaluation['evaluation_date'])); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
