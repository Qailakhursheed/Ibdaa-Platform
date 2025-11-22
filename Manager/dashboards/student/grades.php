<?php
// Load grades and GPA data using StudentHelper
global $studentHelper;
$gpaData = $studentHelper->getGPA();
$allGrades = $studentHelper->getMyGrades(); // Get all grades
$allCourses = $studentHelper->getMyCourses();

// Calculate semester GPA (current active courses only)
$activeCourseIds = array_column(array_filter($allCourses, fn($c) => $c['enrollment_status'] === 'active'), 'course_id');
$semesterGrades = array_filter($allGrades, fn($g) => in_array($g['course_id'], $activeCourseIds));
$semesterGPA = count($semesterGrades) > 0 
    ? array_sum(array_column($semesterGrades, 'total_grade')) / (count($semesterGrades) * 20) 
    : 0;

// Calculate total credits (assuming each course is worth credits)
$totalCredits = count($allCourses) * 3; // Example: 3 credits per course
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">درجاتي وأدائي الأكاديمي</h2>
            <p class="text-slate-600 mt-1">متابعة درجاتك ومعدلك التراكمي - <?php echo count($allGrades); ?> تقييم</p>
        </div>
        <button onclick="window.print()" 
            class="px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-semibold flex items-center gap-2 shadow-md hover:shadow-lg transition-all">
            <i data-lucide="download" class="w-5 h-5"></i>
            تصدير كشف الدرجات
        </button>
    </div>

    <!-- GPA Cards - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-gradient-to-br from-amber-500 to-orange-600 text-white rounded-xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="star" class="w-10 h-10"></i>
                <span class="text-5xl font-bold"><?php echo number_format($gpaData['gpa'], 2); ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">المعدل التراكمي (GPA)</p>
            <p class="text-xs opacity-75 mt-1">من <?php echo $gpaData['courses_count']; ?> دورة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-5xl font-bold text-slate-800"><?php echo number_format($semesterGPA, 2); ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">معدل الفصل الحالي</p>
            <p class="text-xs text-slate-500 mt-1"><?php echo count($semesterGrades); ?> دورة نشطة</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="award" class="w-10 h-10 text-amber-600"></i>
                <span class="text-5xl font-bold text-slate-800"><?php echo $totalCredits; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">الساعات المعتمدة</p>
            <p class="text-xs text-slate-500 mt-1"><?php echo count($allCourses); ?> دورة إجمالاً</p>
        </div>
    </div>

    <!-- Course Filter - PHP Generated -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="filter" class="w-4 h-4 inline mr-1"></i>
                    اختر الدورة
                </label>
                <select id="gradeCourse" onchange="filterGrades()" 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg hover:border-amber-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="all">جميع الدورات (<?php echo count($allCourses); ?>)</option>
                    <?php foreach ($allCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>">
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                    الفصل الدراسي
                </label>
                <select id="semester" onchange="filterGrades()" 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg hover:border-amber-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="all">جميع الفصول</option>
                    <option value="active">الدورات النشطة</option>
                    <option value="completed">الدورات المكتملة</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Grades Table - PHP Rendered -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-md">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="clipboard-list" class="w-5 h-5 text-amber-600"></i>
                كشف الدرجات التفصيلي
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-300">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">الواجبات</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">الاختبارات</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">منتصف الفصل</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">النهائي</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">المجموع</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">التقدير</th>
                    </tr>
                </thead>
                <tbody id="gradesTable">
                    <?php if (empty($allGrades)): ?>
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <i data-lucide="clipboard-x" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                                <p class="text-slate-600 font-semibold">لا توجد درجات مسجلة بعد</p>
                                <p class="text-slate-500 text-sm mt-2">سيتم عرض درجاتك هنا عند توفرها</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Map course IDs to course names and status
                        $courseMap = [];
                        foreach ($allCourses as $course) {
                            $courseMap[$course['course_id']] = [
                                'name' => $course['course_name'],
                                'status' => $course['enrollment_status']
                            ];
                        }
                        
                        foreach ($allGrades as $grade): 
                            $courseName = $courseMap[$grade['course_id']]['name'] ?? 'دورة غير معروفة';
                            $courseStatus = $courseMap[$grade['course_id']]['status'] ?? 'unknown';
                            
                            // Calculate letter grade
                            $totalGrade = $grade['total_grade'];
                            if ($totalGrade >= 90) $letter = 'A';
                            elseif ($totalGrade >= 80) $letter = 'B';
                            elseif ($totalGrade >= 70) $letter = 'C';
                            elseif ($totalGrade >= 60) $letter = 'D';
                            else $letter = 'F';
                            
                            $gradeColor = $totalGrade >= 80 ? 'emerald' : ($totalGrade >= 60 ? 'amber' : 'red');
                        ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors grade-row" 
                                data-course-id="<?php echo $grade['course_id']; ?>"
                                data-status="<?php echo $courseStatus; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-800"><?php echo htmlspecialchars($courseName); ?></span>
                                        <span class="text-xs text-slate-500 mt-1">
                                            <?php echo $courseStatus === 'active' ? '🟢 نشطة' : '🔵 مكتملة'; ?>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-slate-700"><?php echo $grade['assignments_grade'] ?? '-'; ?></span>
                                    <span class="text-xs text-slate-500">/20</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-slate-700"><?php echo $grade['quizzes_grade'] ?? '-'; ?></span>
                                    <span class="text-xs text-slate-500">/20</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-slate-700"><?php echo $grade['midterm_grade'] ?? '-'; ?></span>
                                    <span class="text-xs text-slate-500">/20</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="font-semibold text-slate-700"><?php echo $grade['final_grade'] ?? '-'; ?></span>
                                    <span class="text-xs text-slate-500">/40</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-lg font-bold text-<?php echo $gradeColor; ?>-600">
                                        <?php echo number_format($totalGrade, 1); ?>
                                    </span>
                                    <span class="text-xs text-slate-500">/100</span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-3 py-1.5 rounded-full text-xs font-bold bg-<?php echo $gradeColor; ?>-100 text-<?php echo $gradeColor; ?>-700 inline-block">
                                        <?php echo $letter; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Grades Distribution Chart - Python API -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-md">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart-3" class="w-5 h-5 text-amber-600"></i>
            توزيع الدرجات التفصيلي
        </h3>
        <div id="gradesDistributionChart" style="height: 400px; position: relative;"></div>
    </div>
</div>

<!-- Plotly.js for interactive charts -->
<script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
<script src="<?php echo $managerBaseUrl; ?>/assets/js/chart-loader.js"></script>

<script>
// Simple filtering function using CSS
function filterGrades() {
    const courseId = document.getElementById('gradeCourse').value;
    const semester = document.getElementById('semester').value;
    const rows = document.querySelectorAll('.grade-row');
    
    rows.forEach(row => {
        const rowCourseId = row.dataset.courseId;
        const rowStatus = row.dataset.status;
        
        let showRow = true;
        
        // Filter by course
        if (courseId !== 'all' && rowCourseId !== courseId) {
            showRow = false;
        }
        
        // Filter by semester/status
        if (semester === 'active' && rowStatus !== 'active') {
            showRow = false;
        } else if (semester === 'completed' && rowStatus !== 'completed') {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const studentId = <?php echo $userId; ?>;
    
    // Load grades distribution chart from Python API
    ChartLoader.loadStudentGradesOverview('gradesDistributionChart', studentId);
    
    lucide.createIcons();
});
</script>
