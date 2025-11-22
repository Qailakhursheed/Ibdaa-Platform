<?php
// Load courses using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$activeCourses = array_filter($myCourses, fn($c) => $c['status'] === 'active');
$totalEnrolled = array_sum(array_column($myCourses, 'student_count'));
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">دوراتي التدريبية</h2>
            <p class="text-slate-600 mt-1">إدارة وتتبع دوراتك التدريبية - <?php echo count($myCourses); ?> دورة</p>
        </div>
    </div>

    <!-- Statistics Cards - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="book-open" class="w-10 h-10"></i>
                <span class="text-4xl font-bold"><?php echo count($activeCourses); ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">دورات نشطة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $totalEnrolled; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">إجمالي الملتحقين</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="folder" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo array_sum(array_column($myCourses, 'materials_count')); ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">مواد تعليمية</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-10 h-10 text-amber-600"></i>
                <span class="text-4xl font-bold text-slate-800">4.5</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">متوسط التقييم</p>
        </div>
    </div>

    <!-- Courses Grid - PHP Rendered -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if (empty($myCourses)): ?>
            <div class="col-span-full text-center py-12 bg-white rounded-xl border border-slate-200">
                <i data-lucide="book-open" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                <p class="text-xl text-slate-600 font-semibold">لا توجد دورات تدريبية</p>
            </div>
        <?php else: ?>
            <?php foreach ($myCourses as $course): 
                $statusClass = $course['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 
                               ($course['status'] === 'completed' ? 'bg-slate-100 text-slate-700' : 'bg-amber-100 text-amber-700');
                $statusText = $course['status'] === 'active' ? 'نشطة' : 
                              ($course['status'] === 'completed' ? 'مكتملة' : 'قادمة');
            ?>
                <div class="bg-white border-2 border-slate-200 rounded-xl overflow-hidden hover:shadow-2xl hover:scale-105 transition-all duration-300">
                    <div class="h-44 bg-gradient-to-br from-emerald-500 via-green-500 to-teal-600 flex items-center justify-center relative overflow-hidden">
                        <i data-lucide="book-open" class="w-20 h-20 text-white opacity-90"></i>
                        <div class="absolute inset-0 bg-white opacity-10 animate-pulse"></div>
                    </div>
                    
                    <div class="p-6">
                        <div class="flex items-start justify-between mb-3">
                            <h3 class="text-lg font-bold text-slate-800 flex-1 line-clamp-2"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                            <span class="px-3 py-1 text-xs font-bold rounded-full <?php echo $statusClass; ?> ml-2">
                                <?php echo $statusText; ?>
                            </span>
                        </div>
                        
                        <p class="text-slate-600 text-sm mb-4 line-clamp-2 min-h-[40px]">
                            <?php echo htmlspecialchars($course['description'] ?? 'لا يوجد وصف'); ?>
                        </p>
                        
                        <div class="grid grid-cols-2 gap-4 mb-5 pb-4 border-b-2 border-slate-100">
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="users" class="w-5 h-5 text-emerald-600"></i>
                                <span class="text-slate-700 font-semibold"><?php echo $course['student_count'] ?? 0; ?> طالب</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="folder" class="w-5 h-5 text-blue-600"></i>
                                <span class="text-slate-700 font-semibold"><?php echo $course['materials_count'] ?? 0; ?> مادة</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="calendar" class="w-5 h-5 text-purple-600"></i>
                                <span class="text-slate-700 font-semibold"><?php echo $course['duration'] ?? 0; ?> ساعة</span>
                            </div>
                            <div class="flex items-center gap-2 text-sm">
                                <i data-lucide="star" class="w-5 h-5 text-amber-500"></i>
                                <span class="text-slate-700 font-semibold">4.5 / 5</span>
                            </div>
                        </div>
                        
                        <div class="flex gap-2">
                            <a href="?page=students&course_id=<?php echo $course['course_id']; ?>" 
                                class="flex-1 px-4 py-2.5 bg-gradient-to-r from-emerald-600 to-green-600 text-white rounded-lg hover:from-emerald-700 hover:to-green-700 transition-all text-sm font-bold text-center shadow-md hover:shadow-lg">
                                <i data-lucide="eye" class="w-4 h-4 inline mr-1"></i>
                                عرض
                            </a>
                            <a href="?page=attendance&course_id=<?php echo $course['course_id']; ?>" 
                                class="flex-1 px-4 py-2.5 bg-white border-2 border-emerald-600 text-emerald-600 rounded-lg hover:bg-emerald-50 transition-all text-sm font-bold text-center shadow-md hover:shadow-lg">
                                <i data-lucide="clipboard-check" class="w-4 h-4 inline mr-1"></i>
                                حضور
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
