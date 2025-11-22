<?php
/**
 * Manager Dashboard - Courses Management
 * إدارة الدورات
 */

global $managerHelper;

// Get all courses using helper
$courses = $managerHelper->getAllCourses();

// Apply filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

if (!empty($search)) {
    $courses = array_filter($courses, function($c) use ($search) {
        return stripos($c['course_name'], $search) !== false;
    });
}

if ($status_filter !== 'all') {
    $courses = array_filter($courses, fn($c) => $c['status'] === $status_filter);
}

// Get statistics  
$total_courses = count($courses);
$active_courses = count(array_filter($courses, fn($c) => $c['status'] === 'active'));
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="book-open" class="w-10 h-10"></i>
        إدارة الدورات
    </h1>
    <p class="text-emerald-100 text-lg">متابعة جميع الدورات التدريبية</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $total_courses; ?></h3>
        <p class="text-slate-600">إجمالي الدورات</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $active_courses; ?></h3>
        <p class="text-slate-600">دورات نشطة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $total_courses - $active_courses; ?></h3>
        <p class="text-slate-600">دورات أخرى</p>
    </div>
</div>

<!-- Courses Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($courses)): ?>
        <div class="col-span-full bg-white rounded-xl shadow-lg p-12 text-center">
            <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
            <p class="text-slate-500 text-lg">لا توجد دورات</p>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
        <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
            <h3 class="text-lg font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h3>
            <p class="text-sm text-slate-600 mb-4"><?php echo htmlspecialchars($course['description'] ?? 'لا يوجد وصف'); ?></p>
            <div class="flex justify-between items-center">
                <span class="text-sm text-slate-500">المدرب: <?php echo htmlspecialchars($course['instructor_name']); ?></span>
                <span class="bg-emerald-100 text-emerald-700 px-3 py-1 rounded-full text-sm"><?php echo $course['students_count']; ?> طالب</span>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>lucide.createIcons();</script>
