<?php
// Load courses using StudentHelper
global $studentHelper;
$allCourses = $studentHelper->getMyCourses();
$gpaData = $studentHelper->getGPA();

$activeCourses = array_filter($allCourses, fn($c) => $c['enrollment_status'] === 'active');
$completedCourses = array_filter($allCourses, fn($c) => $c['enrollment_status'] === 'completed');
$avgProgress = count($allCourses) > 0 
    ? array_sum(array_column($allCourses, 'progress')) / count($allCourses) 
    : 0;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">دوراتي التدريبية</h2>
            <p class="text-slate-600 mt-1">جميع الدورات المسجلة والمكتملة - المعدل: <?php echo number_format($gpaData['gpa'], 2); ?></p>
        </div>
        <div class="flex gap-3">
            <button onclick="filterCourses('all')" id="filter-all"
                class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                جميع الدورات
            </button>
            <button onclick="filterCourses('active')" id="filter-active"
                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                نشطة
            </button>
            <button onclick="filterCourses('completed')" id="filter-completed"
                class="px-4 py-2 bg-white border border-slate-300 text-slate-700 rounded-lg hover:bg-slate-50 transition-colors">
                مكتملة
            </button>
        </div>
    </div>

    <!-- Statistics - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-8 h-8 text-amber-600"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo count($allCourses); ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">إجمالي الدورات</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="play-circle" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo count($activeCourses); ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">دورات نشطة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="check-circle" class="w-8 h-8 text-blue-600"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo count($completedCourses); ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">مكتملة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-8 h-8 text-amber-600"></i>
                <span class="text-3xl font-bold text-slate-800"><?php echo round($avgProgress); ?>%</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">متوسط التقدم</p>
        </div>
    </div>

    <!-- Courses Grid - PHP Rendered -->
    <div id="coursesGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($allCourses)): ?>
            <div class="col-span-full text-center py-12 bg-white rounded-xl border border-slate-200">
                <i data-lucide="book-open" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                <p class="text-xl text-slate-600 font-semibold">لا توجد دورات مسجلة حالياً</p>
                <p class="text-slate-500 mt-2">سجل في دورة جديدة للبدء في رحلة التعلم</p>
            </div>
        <?php else: ?>
            <?php foreach ($allCourses as $course): ?>
                <div class="course-card bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-xl transition-all duration-300" 
                     data-status="<?php echo htmlspecialchars($course['enrollment_status']); ?>">
                    <!-- Course Header -->
                    <div class="h-40 bg-gradient-to-br from-amber-500 to-orange-600 flex items-center justify-center relative">
                        <i data-lucide="book-open" class="w-16 h-16 text-white opacity-90"></i>
                    </div>
                    
                    <!-- Course Content -->
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-bold text-slate-800 flex-1 line-clamp-2">
                                <?php echo htmlspecialchars($course['course_name']); ?>
                            </h3>
                            <span class="px-3 py-1 text-xs font-semibold rounded-full ml-2 
                                <?php 
                                echo $course['enrollment_status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 
                                     ($course['enrollment_status'] === 'completed' ? 'bg-blue-100 text-blue-700' : 
                                     'bg-slate-100 text-slate-700'); 
                                ?>">
                                <?php 
                                echo $course['enrollment_status'] === 'active' ? 'نشطة' : 
                                     ($course['enrollment_status'] === 'completed' ? 'مكتملة' : 'معلقة'); 
                                ?>
                            </span>
                        </div>
                        
                        <p class="text-slate-600 text-sm mb-4 line-clamp-2">
                            <?php echo htmlspecialchars($course['description'] ?? 'دورة تدريبية شاملة'); ?>
                        </p>
                        
                        <!-- Progress -->
                        <div class="mb-4">
                            <div class="flex justify-between text-sm mb-2">
                                <span class="text-slate-600">التقدم</span>
                                <span class="font-bold text-amber-600"><?php echo round($course['progress']); ?>%</span>
                            </div>
                            <div class="h-2.5 bg-slate-200 rounded-full overflow-hidden">
                                <div class="h-full bg-gradient-to-r from-amber-500 to-orange-500" 
                                     style="width: <?php echo round($course['progress']); ?>%"></div>
                            </div>
                        </div>
                        
                        <!-- Details -->
                        <div class="grid grid-cols-2 gap-4 mb-4 pb-4 border-b border-slate-200">
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="user" class="w-4 h-4 text-amber-600"></i>
                                <span class="text-slate-700 truncate"><?php echo htmlspecialchars($course['trainer_name'] ?? 'مدرب'); ?></span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="clock" class="w-4 h-4 text-amber-600"></i>
                                <span class="text-slate-700"><?php echo $course['duration'] ?? 0; ?> ساعة</span>
                            </div>
                        </div>
                        
                        <!-- Button -->
                        <a href="?page=materials&course_id=<?php echo $course['course_id']; ?>" 
                           class="block w-full px-4 py-2.5 bg-amber-600 text-white rounded-lg hover:bg-amber-700 
                                  transition-colors text-sm font-semibold text-center">
                            <i data-lucide="arrow-left" class="w-4 h-4 inline mr-2"></i>
                            متابعة الدورة
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Simple filtering function
function filterCourses(status) {
    const cards = document.querySelectorAll('.course-card');
    const buttons = {
        'all': document.getElementById('filter-all'),
        'active': document.getElementById('filter-active'),
        'completed': document.getElementById('filter-completed')
    };
    
    // Update button styles
    Object.values(buttons).forEach(btn => {
        btn.classList.remove('bg-blue-600', 'text-white');
        btn.classList.add('bg-white', 'border-slate-300', 'text-slate-700');
    });
    
    buttons[status].classList.remove('bg-white', 'border-slate-300', 'text-slate-700');
    buttons[status].classList.add('bg-blue-600', 'text-white');
    
    // Filter cards
    cards.forEach(card => {
        card.style.display = (status === 'all' || card.dataset.status === status) ? 'block' : 'none';
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
});
</script>
