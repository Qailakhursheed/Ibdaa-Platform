<?php
// Load students using TrainerHelper
global $trainerHelper;
$myStudents = $trainerHelper->getMyStudents();
$totalStudents = count($myStudents);
$excellentStudents = count(array_filter($myStudents, fn($s) => ($s['gpa'] ?? 0) >= 3.5));
$needsAttention = count(array_filter($myStudents, fn($s) => ($s['gpa'] ?? 0) < 2.0));
$avgAttendance = $totalStudents > 0 ? array_sum(array_column($myStudents, 'attendance_rate')) / $totalStudents : 0;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">Ø·Ù„Ø§Ø¨ÙŠ</h2>
            <p class="text-slate-600 mt-1">Ù…ØªØ§Ø¨Ø¹Ø© Ø£Ø¯Ø§Ø¡ ÙˆØ­Ø¶ÙˆØ± Ø§Ù„Ø·Ù„Ø§Ø¨ - <?php echo $totalStudents; ?> Ø·Ø§Ù„Ø¨</p>
        </div>
    </div>

    <!-- Statistics - PHP Data -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="users" class="w-10 h-10"></i>
                <span class="text-4xl font-bold"><?php echo $totalStudents; ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">Ø¥Ø¬Ù…Ø§Ù„ÙŠ Ø§Ù„Ø·Ù„Ø§Ø¨</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="trending-up" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $excellentStudents; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">Ù…ØªÙ…ÙŠØ²ÙˆÙ†</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg <?php echo $needsAttention > 0 ? 'border-amber-300' : ''; ?>">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="alert-triangle" class="w-10 h-10 text-amber-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $needsAttention; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">ÙŠØ­ØªØ§Ø¬ÙˆÙ† Ù…ØªØ§Ø¨Ø¹Ø©</p>
        </div>
        
        <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="percent" class="w-10 h-10 text-emerald-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo round($avgAttendance); ?>%</span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">Ù…ØªÙˆØ³Ø· Ø§Ù„Ø­Ø¶ÙˆØ±</p>
        </div>
    </div>

    <!-- Students Table - PHP Rendered -->
    <div class="bg-white border-2 border-slate-200 rounded-xl shadow-lg">
        <div class="p-6 border-b-2 border-slate-100 bg-gradient-to-r from-slate-50 to-white">
            <h3 class="text-xl font-bold text-slate-800">
                <i data-lucide="users" class="w-6 h-6 inline text-emerald-600 mr-2"></i>
                Ù‚Ø§Ø¦Ù…Ø© Ø§Ù„Ø·Ù„Ø§Ø¨
            </h3>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b-2 border-slate-200">
                    <tr>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ø·Ø§Ù„Ø¨</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ø¯ÙˆØ±Ø©</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ø­Ø¶ÙˆØ±</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ù…Ø¹Ø¯Ù„</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ø£Ø¯Ø§Ø¡</th>
                        <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">Ø§Ù„Ø¥Ø¬Ø±Ø§Ø¡Ø§Øª</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($myStudents)): ?>
                        <tr>
                            <td colspan="6" class="px-6 py-16 text-center">
                                <i data-lucide="users" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
                                <p class="text-xl text-slate-600 font-semibold">Ù„Ø§ ÙŠÙˆØ¬Ø¯ Ø·Ù„Ø§Ø¨</p>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($myStudents as $student): 
                            $grade = $student['gpa'] ?? 0;
                            $attendance = $student['attendance_rate'] ?? 0;
                            $gradeColor = $grade >= 3.5 ? 'emerald' : ($grade >= 2.0 ? 'amber' : 'red');
                            $gradeText = $grade >= 3.5 ? 'Ù…Ù…ØªØ§Ø²' : ($grade >= 2.0 ? 'Ø¬ÙŠØ¯' : 'Ø¶Ø¹ÙŠÙ');
                            $attendanceColor = $attendance >= 90 ? 'emerald' : ($attendance >= 70 ? 'amber' : 'red');
                        ?>
                            <tr class="border-b border-slate-100 hover:bg-emerald-50 transition-all duration-200">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <img src="<?php echo htmlspecialchars($student['photo'] ?? $platformBaseUrl . '/photos/default-avatar.png'); ?>" 
                                            class="w-12 h-12 rounded-full object-cover border-2 border-emerald-200 shadow-md">
                                        <div>
                                            <p class="font-bold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($student['email']); ?></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-sm text-slate-700 font-semibold"><?php echo htmlspecialchars($student['course_name'] ?? 'ØºÙŠØ± Ù…Ø­Ø¯Ø¯'); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex-1 bg-slate-200 rounded-full h-3 overflow-hidden shadow-inner">
                                            <div class="h-3 rounded-full bg-gradient-to-r from-<?php echo $attendanceColor; ?>-400 to-<?php echo $attendanceColor; ?>-600 transition-all" 
                                                 style="width: <?php echo $attendance; ?>%"></div>
                                        </div>
                                        <span class="text-sm font-bold text-slate-700 min-w-[50px]"><?php echo round($attendance); ?>%</span>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="text-lg font-bold text-<?php echo $gradeColor; ?>-600"><?php echo number_format($grade, 2); ?></span>
                                </td>
                                <td class="px-6 py-4">
                                    <span class="px-4 py-1.5 text-xs font-bold rounded-full bg-<?php echo $gradeColor; ?>-100 text-<?php echo $gradeColor; ?>-700 border border-<?php echo $gradeColor; ?>-300">
                                        <?php echo $gradeText; ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="flex gap-2">
                                        <a href="?page=reports&student_id=<?php echo $student['user_id']; ?>" 
                                            class="px-3 py-2 text-sm bg-emerald-50 text-emerald-700 rounded-lg hover:bg-emerald-100 transition-all border border-emerald-200 font-semibold">
                                            <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                        </a>
                                        <a href="?page=chat&student_id=<?php echo $student['user_id']; ?>" 
                                            class="px-3 py-2 text-sm bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-all border border-slate-300 font-semibold">
                                            <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
        document.getElementById('avgAttendance').textContent = 
            (students.reduce((sum, s) => sum + (s.attendance || 0), 0) / students.length || 0).toFixed(0) + '%';
        
        renderStudents(students);
