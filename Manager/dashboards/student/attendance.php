<?php
// Load attendance data using StudentHelper
global $studentHelper;
$allCourses = $studentHelper->getMyCourses();
$allAttendance = $studentHelper->getMyAttendance(); // Get all attendance records

// Calculate statistics
$presentCount = count(array_filter($allAttendance, fn($a) => $a['status'] === 'present'));
$absentCount = count(array_filter($allAttendance, fn($a) => $a['status'] === 'absent'));
$lateCount = count(array_filter($allAttendance, fn($a) => $a['status'] === 'late'));
$totalRecords = count($allAttendance);
$attendanceRate = $totalRecords > 0 ? ($presentCount / $totalRecords) * 100 : 0;
$warningCount = floor($absentCount / 3); // Warning every 3 absences
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">سجل الحضور</h2>
            <p class="text-slate-600 mt-1">متابعة حضورك وغيابك - <?php echo $totalRecords; ?> سجل إجمالاً</p>
        </div>
    </div>

    <!-- Attendance Summary - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar-check" class="w-10 h-10"></i>
                <span class="text-5xl font-bold"><?php echo $presentCount; ?></span>
            </div>
            <p class="text-sm font-semibold opacity-90">أيام حضور</p>
            <p class="text-xs opacity-75 mt-1"><?php echo $lateCount; ?> تأخير</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="calendar-x" class="w-10 h-10 text-red-600"></i>
                <span class="text-5xl font-bold text-slate-800"><?php echo $absentCount; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">أيام غياب</p>
            <p class="text-xs text-slate-500 mt-1">من <?php echo $totalRecords; ?> سجل</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-10 h-10 text-amber-600"></i>
                <span class="text-5xl font-bold text-slate-800"><?php echo round($attendanceRate); ?>%</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">نسبة الحضور</p>
            <p class="text-xs text-slate-500 mt-1">
                <?php echo $attendanceRate >= 75 ? '✓ ممتاز' : ($attendanceRate >= 50 ? '⚠ جيد' : '✗ ضعيف'); ?>
            </p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg <?php echo $warningCount > 0 ? 'border-red-300' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="alert-triangle" class="w-10 h-10 <?php echo $warningCount > 0 ? 'text-red-600' : 'text-amber-600'; ?>"></i>
                <span class="text-5xl font-bold text-slate-800"><?php echo $warningCount; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">إنذارات</p>
            <p class="text-xs text-slate-500 mt-1"><?php echo $warningCount > 0 ? 'تحذير: غياب متكرر!' : 'لا توجد إنذارات'; ?></p>
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
                <select id="attendanceCourse" onchange="filterAttendance()" 
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
                    الحالة
                </label>
                <select id="attendanceStatus" onchange="filterAttendance()" 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg hover:border-amber-500 focus:border-amber-500 focus:ring-2 focus:ring-amber-200 transition-all">
                    <option value="all">جميع الحالات</option>
                    <option value="present">حضور (<?php echo $presentCount; ?>)</option>
                    <option value="absent">غياب (<?php echo $absentCount; ?>)</option>
                    <option value="late">تأخير (<?php echo $lateCount; ?>)</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Attendance Records - PHP Rendered -->
    <div class="bg-white border border-slate-200 rounded-xl shadow-md">
        <div class="p-6 border-b border-slate-200">
            <h3 class="text-lg font-bold text-slate-800 flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5 text-amber-600"></i>
                سجل الحضور التفصيلي
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gradient-to-r from-slate-50 to-slate-100 border-b-2 border-slate-300">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">التاريخ</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الدورة</th>
                        <th class="px-6 py-4 text-center text-sm font-bold text-slate-700">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الملاحظات</th>
                    </tr>
                </thead>
                <tbody id="attendanceTable">
                    <?php if (empty($allAttendance)): ?>
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <i data-lucide="calendar-x" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                                <p class="text-slate-600 font-semibold">لا توجد سجلات حضور بعد</p>
                                <p class="text-slate-500 text-sm mt-2">سيظهر سجل حضورك هنا عند بدء الدورات</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php 
                        // Map course IDs to names
                        $courseMap = [];
                        foreach ($allCourses as $course) {
                            $courseMap[$course['course_id']] = $course['course_name'];
                        }
                        
                        // Sort by date descending
                        usort($allAttendance, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));
                        
                        foreach ($allAttendance as $record): 
                            $courseName = $courseMap[$record['course_id']] ?? 'دورة غير معروفة';
                            $status = $record['status'];
                            
                            // Status styling
                            if ($status === 'present') {
                                $statusLabel = 'حضور';
                                $statusIcon = '✓';
                                $statusColor = 'emerald';
                            } elseif ($status === 'absent') {
                                $statusLabel = 'غياب';
                                $statusIcon = '✗';
                                $statusColor = 'red';
                            } elseif ($status === 'late') {
                                $statusLabel = 'تأخير';
                                $statusIcon = '⏰';
                                $statusColor = 'amber';
                            } else {
                                $statusLabel = 'غير محدد';
                                $statusIcon = '?';
                                $statusColor = 'slate';
                            }
                            
                            $formattedDate = date('Y-m-d', strtotime($record['date']));
                            $dayName = date('l', strtotime($record['date']));
                            $dayNameAr = [
                                'Monday' => 'الاثنين',
                                'Tuesday' => 'الثلاثاء',
                                'Wednesday' => 'الأربعاء',
                                'Thursday' => 'الخميس',
                                'Friday' => 'الجمعة',
                                'Saturday' => 'السبت',
                                'Sunday' => 'الأحد'
                            ][$dayName] ?? $dayName;
                        ?>
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors attendance-row" 
                                data-course-id="<?php echo $record['course_id']; ?>"
                                data-status="<?php echo $status; ?>">
                                <td class="px-6 py-4">
                                    <div class="flex flex-col">
                                        <span class="font-semibold text-slate-800"><?php echo $formattedDate; ?></span>
                                        <span class="text-xs text-slate-500 mt-1"><?php echo $dayNameAr; ?></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="font-medium text-slate-700"><?php echo htmlspecialchars($courseName); ?></span>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="px-4 py-1.5 rounded-full text-xs font-bold bg-<?php echo $statusColor; ?>-100 text-<?php echo $statusColor; ?>-700 inline-block">
                                        <?php echo $statusIcon; ?> <?php echo $statusLabel; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Attendance Rate Chart - Python API -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-md">
        <h3 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
            <i data-lucide="bar-chart-2" class="w-5 h-5 text-amber-600"></i>
            معدل الحضور حسب الدورات
        </h3>
        <div id="attendanceRateChart" style="height: 400px; position: relative;"></div>
    </div>
</div>

<!-- Plotly.js for interactive charts -->
<script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
<script src="<?php echo $managerBaseUrl; ?>/assets/js/chart-loader.js"></script>

<script>
// Simple filtering function using CSS
function filterAttendance() {
    const courseId = document.getElementById('attendanceCourse').value;
    const status = document.getElementById('attendanceStatus').value;
    const rows = document.querySelectorAll('.attendance-row');
    
    rows.forEach(row => {
        const rowCourseId = row.dataset.courseId;
        const rowStatus = row.dataset.status;
        
        let showRow = true;
        
        // Filter by course
        if (courseId !== 'all' && rowCourseId !== courseId) {
            showRow = false;
        }
        
        // Filter by status
        if (status !== 'all' && rowStatus !== status) {
            showRow = false;
        }
        
        row.style.display = showRow ? '' : 'none';
    });
}

// Initialize
document.addEventListener('DOMContentLoaded', function() {
    const studentId = <?php echo $userId; ?>;
    
    // Load attendance rate chart from Python API
    ChartLoader.loadStudentAttendanceRate('attendanceRateChart', studentId);
    
    lucide.createIcons();
});
</script>
