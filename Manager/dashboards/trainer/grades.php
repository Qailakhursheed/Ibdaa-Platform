<?php
// Load courses and grades using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$courseId = $_GET['course_id'] ?? ($myCourses[0]['course_id'] ?? null);
$grades = [];
if ($courseId) {
    $grades = $trainerHelper->getCourseGrades($courseId);
}
$avgGrade = count($grades) > 0 ? array_sum(array_column($grades, 'total_grade')) / count($grades) : 0;
$topGrade = count($grades) > 0 ? max(array_column($grades, 'total_grade')) : 0;
$lowGrade = count($grades) > 0 ? min(array_column($grades, 'total_grade')) : 0;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">إدارة الدرجات</h2>
            <p class="text-slate-600 mt-1">تسجيل ومراجعة درجات الطلاب - <?php echo count($grades); ?> تقييم</p>
        </div>
    </div>

    <!-- Course Selection - PHP Generated -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    اختر الدورة
                </label>
                <select id="gradeCourse" onchange="window.location.href='?page=grades&course_id='+this.value" 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg hover:border-emerald-500 focus:border-emerald-500 transition-all">
                    <option value="">اختر دورة لعرض الدرجات</option>
                    <?php foreach ($myCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $courseId == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">نوع التقييم</label>
                <select id="gradeType" onchange="filterGrades()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع التقييمات</option>
                    <option value="assignment">واجبات</option>
                    <option value="quiz">اختبارات</option>
                    <option value="midterm">منتصف الفصل</option>
                    <option value="final">نهائي</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Statistics -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="gradedStudents">0</span>
            </div>
            <p class="text-sm text-slate-600">طلاب تم تقييمهم</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="avgGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">المتوسط العام</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-8 h-8 text-emerald-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="topGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">أعلى درجة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-down" class="w-8 h-8 text-amber-600"></i>
                <span class="text-2xl font-bold text-slate-800" id="lowGrade">0</span>
            </div>
            <p class="text-sm text-slate-600">أدنى درجة</p>
        </div>
    </div>

    <!-- Grades Table -->
    <div class="bg-white border border-slate-200 rounded-xl">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800">درجات الطلاب</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">واجبات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">اختبارات</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">منتصف الفصل</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">نهائي</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">المجموع</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">التقدير</th>
                        <th class="px-6 py-4 text-right text-sm font-semibold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$courseId): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <i data-lucide="clipboard" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                                <p class="text-xl text-slate-600 font-semibold">اختر دورة لعرض الدرجات</p>
                            </td>
                        </tr>
                    <?php elseif (empty($grades)): ?>
                        <tr>
                            <td colspan="8" class="px-6 py-16 text-center">
                                <i data-lucide="clipboard-x" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                                <p class="text-xl text-slate-600 font-semibold">لا توجد درجات مسجلة</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($grades as $gradeRow): 
                            $total = ($gradeRow['assignments'] ?? 0) + ($gradeRow['quizzes'] ?? 0) + 
                                     ($gradeRow['midterm'] ?? 0) + ($gradeRow['final'] ?? 0);
                            $gradeLevel = $total >= 90 ? 'ممتاز' : ($total >= 80 ? 'جيد جداً' : 
                                          ($total >= 70 ? 'جيد' : ($total >= 60 ? 'مقبول' : 'ضعيف')));
                            $gradeColor = $total >= 80 ? 'emerald' : ($total >= 60 ? 'amber' : 'red');
                        ?>
                            <tr class="border-b border-slate-100 hover:bg-emerald-50 transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($gradeRow['student_photo'] ?? $platformBaseUrl . '/photos/default-avatar.png'); ?>" 
                                            class="w-12 h-12 rounded-full object-cover border-2 border-emerald-200 shadow-md">
                                        <div>
                                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($gradeRow['student_name']); ?></p>
                                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($gradeRow['student_email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-slate-700"><?php echo $gradeRow['assignments'] ?? 0; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-slate-700"><?php echo $gradeRow['quizzes'] ?? 0; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-slate-700"><?php echo $gradeRow['midterm'] ?? 0; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-bold text-slate-700"><?php echo $gradeRow['final'] ?? 0; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-xl font-extrabold text-<?php echo $gradeColor; ?>-600"><?php echo $total; ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-4 py-1.5 text-xs font-bold rounded-full bg-<?php echo $gradeColor; ?>-100 text-<?php echo $gradeColor; ?>-700 border border-<?php echo $gradeColor; ?>-300">
                                        <?php echo $gradeLevel; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <a href="?page=grades&course_id=<?php echo $courseId; ?>&edit_student=<?php echo $gradeRow['student_id']; ?>" 
                                        class="px-4 py-2 text-sm bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-all border border-emerald-200 font-bold inline-block">
                                        <i data-lucide="edit" class="w-4 h-4 inline mr-1"></i>
                                        تعديل
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
