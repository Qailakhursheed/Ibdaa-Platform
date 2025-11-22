<?php
// Load announcements using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$selectedCourse = $_GET['course_id'] ?? null;
$announcements = $trainerHelper->getCourseAnnouncements($selectedCourse);
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">الإعلانات</h2>
            <p class="text-slate-600 mt-1">إنشاء وإدارة إعلانات الدورات - <?php echo count($announcements); ?> إعلان</p>
        </div>
    </div>

    <!-- Course Selection - PHP -->
    <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="page" value="announcements">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    اختر الدورة
                </label>
                <select name="course_id" class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg" onchange="this.form.submit()">
                    <option value="">جميع الدورات</option>
                    <?php foreach ($myCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selectedCourse == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Announcements List - PHP Rendered -->
    <div class="space-y-4">
        <?php if (empty($announcements)): ?>
            <div class="bg-white border-2 border-slate-200 rounded-xl p-12 text-center">
                <i data-lucide="megaphone" class="w-20 h-20 mx-auto text-slate-400 mb-4"></i>
                <p class="text-2xl text-slate-600 font-semibold">لا توجد إعلانات</p>
            </div>
        <?php else: ?>
            <?php foreach ($announcements as $announcement): 
                $priorityClass = $announcement['priority'] === 'high' ? 'border-red-300 bg-red-50' : 
                                ($announcement['priority'] === 'urgent' ? 'border-amber-300 bg-amber-50' : 'border-slate-200');
                $priorityIcon = $announcement['priority'] === 'high' ? 'alert-circle' : 
                               ($announcement['priority'] === 'urgent' ? 'bell' : 'info');
            ?>
                <div class="bg-white border-2 <?php echo $priorityClass; ?> rounded-xl p-6 shadow-lg">
                    <div class="flex items-start gap-4">
                        <div class="w-12 h-12 rounded-full bg-emerald-100 flex items-center justify-center flex-shrink-0">
                            <i data-lucide="<?php echo $priorityIcon; ?>" class="w-6 h-6 text-emerald-600"></i>
                        </div>
                        <div class="flex-1">
                            <div class="flex items-start justify-between mb-2">
                                <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                                <span class="text-sm text-slate-500"><?php echo date('Y-m-d', strtotime($announcement['created_at'])); ?></span>
                            </div>
                            <p class="text-sm text-emerald-600 font-semibold mb-3"><?php echo htmlspecialchars($announcement['course_name']); ?></p>
                            <p class="text-slate-700 leading-relaxed"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>
