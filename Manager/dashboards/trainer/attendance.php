<?php
// Load attendance using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$selectedCourse = $_GET['course_id'] ?? ($myCourses[0]['course_id'] ?? null);
$selectedDate = $_GET['date'] ?? date('Y-m-d');
$attendanceList = [];
if ($selectedCourse) {
    $attendanceList = $trainerHelper->getCourseAttendance($selectedCourse, $selectedDate);
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">تسجيل الحضور</h2>
            <p class="text-slate-600 mt-1">إدارة حضور وغياب الطلاب - <?php echo count($attendanceList); ?> طالب</p>
        </div>
    </div>

    <!-- Date and Course Selection - PHP -->
    <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <input type="hidden" name="page" value="attendance">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    <i data-lucide="calendar" class="w-4 h-4 inline mr-1"></i>
                    التاريخ
                </label>
                <input type="date" name="date" 
                    class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-emerald-500"
                    value="<?php echo htmlspecialchars($selectedDate); ?>">
            </div>
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    الدورة
                </label>
                <select name="course_id" class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg focus:border-emerald-500">
                    <option value="">اختر الدورة</option>
                    <?php foreach ($myCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selectedCourse == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="flex items-end">
                <button type="submit" 
                    class="w-full px-4 py-3 bg-gradient-to-r from-emerald-600 to-green-600 text-white rounded-lg hover:from-emerald-700 hover:to-green-700 font-bold shadow-lg">
                    <i data-lucide="search" class="w-5 h-5 inline mr-1"></i>
                    عرض السجل
                </button>
            </div>
        </form>
    </div>

    <!-- Attendance Calendar -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <h3 class="text-lg font-bold text-slate-800 mb-4">سجل الحضور الشهري</h3>
        <div id="attendanceCalendar" class="grid grid-cols-7 gap-2">
            <!-- Calendar will be generated here -->
        </div>
    </div>

    <!-- Attendance Records - PHP Rendered -->
    <div class="bg-white border-2 border-slate-200 rounded-xl shadow-lg">
        <div class="p-6 border-b-2 border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <h3 class="text-xl font-bold text-slate-800">
                <i data-lucide="clipboard-check" class="w-6 h-6 inline text-emerald-600 mr-2"></i>
                قائمة الحضور والغياب
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b-2 border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الطالب</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الحالة</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الوقت</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">ملاحظات</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الإجراءات</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($attendanceList)): ?>
                        <tr>
                            <td colspan="5" class="px-6 py-16 text-center">
                                <i data-lucide="calendar" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                                <p class="text-xl text-slate-600 font-semibold">اختر الدورة والتاريخ لعرض السجل</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($attendanceList as $record): 
                            $status = $record['status'] ?? 'absent';
                            $statusColor = $status === 'present' ? 'emerald' : ($status === 'late' ? 'amber' : 'red');
                            $statusText = $status === 'present' ? 'حاضر' : ($status === 'late' ? 'متأخر' : 'غائب');
                            $statusIcon = $status === 'present' ? 'check-circle' : ($status === 'late' ? 'clock' : 'x-circle');
                        ?>
                            <tr class="border-b border-slate-100 hover:bg-<?php echo $statusColor; ?>-50 transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($record['photo'] ?? '../photos/default-avatar.png'); ?>" 
                                            class="w-12 h-12 rounded-full object-cover border-2 border-<?php echo $statusColor; ?>-200 shadow-md">
                                        <div>
                                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($record['full_name']); ?></p>
                                            <p class="text-sm text-slate-500">ID: <?php echo $record['user_id']; ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-4 py-2 text-sm font-bold rounded-full bg-<?php echo $statusColor; ?>-100 text-<?php echo $statusColor; ?>-700 border-2 border-<?php echo $statusColor; ?>-300 inline-flex items-center gap-2">
                                        <i data-lucide="<?php echo $statusIcon; ?>" class="w-4 h-4"></i>
                                        <?php echo $statusText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm font-semibold text-slate-700">
                                        <?php echo $record['recorded_at'] ? date('H:i', strtotime($record['recorded_at'])) : '-'; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-600"><?php echo htmlspecialchars($record['notes'] ?? '-'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <form method="POST" action="?page=attendance&course_id=<?php echo $selectedCourse; ?>&date=<?php echo $selectedDate; ?>" class="inline-flex gap-2">
                                        <input type="hidden" name="action" value="record_attendance">
                                        <input type="hidden" name="student_id" value="<?php echo $record['user_id']; ?>">
                                        <input type="hidden" name="course_id" value="<?php echo $selectedCourse; ?>">
                                        <input type="hidden" name="date" value="<?php echo $selectedDate; ?>">
                                        <select name="status" class="px-3 py-1.5 text-sm border-2 border-slate-300 rounded-lg">
                                            <option value="present" <?php echo $status === 'present' ? 'selected' : ''; ?>>حاضر</option>
                                            <option value="late" <?php echo $status === 'late' ? 'selected' : ''; ?>>متأخر</option>
                                            <option value="absent" <?php echo $status === 'absent' ? 'selected' : ''; ?>>غائب</option>
                                        </select>
                                        <button type="submit" class="px-4 py-1.5 text-sm bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-bold">
                                            <i data-lucide="save" class="w-4 h-4 inline"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
// Handle attendance recording
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'record_attendance') {
    $studentId = $_POST['student_id'];
    $courseId = $_POST['course_id'];
    $date = $_POST['date'];
    $status = $_POST['status'];
    $notes = $_POST['notes'] ?? '';
    
    if ($trainerHelper->recordAttendance($courseId, $studentId, $date, $status, $notes)) {
        echo '<script>window.location.href = "?page=attendance&course_id=' . $courseId . '&date=' . $date . '";</script>';
    }
}
?>