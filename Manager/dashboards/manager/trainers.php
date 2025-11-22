<?php
/**
 * Manager Dashboard - Trainers Management
 * إدارة المدربين
 */

global $managerHelper;

// Get trainers using helper
$trainers = $managerHelper->getAllTrainers();

// Apply filters
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

if (!empty($search)) {
    $trainers = array_filter($trainers, function($t) use ($search) {
        return stripos($t['full_name'], $search) !== false || 
               stripos($t['email'], $search) !== false;
    });
}

if ($status_filter !== 'all') {
    $trainers = array_filter($trainers, fn($t) => $t['account_status'] === $status_filter);
}
?>

<div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2"><i data-lucide="graduation-cap" class="w-10 h-10 inline"></i> إدارة المدربين</h1>
    <p class="text-amber-100 text-lg">عرض وإدارة المدربين</p>
</div>

<div class="bg-white rounded-xl shadow-lg p-6">
    <h3 class="text-xl font-bold mb-4">قائمة المدربين</h3>
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-4 py-3 text-right">#</th>
                <th class="px-4 py-3 text-right">الاسم</th>
                <th class="px-4 py-3 text-right">البريد</th>
                <th class="px-4 py-3 text-right">الدورات</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trainers as $i => $t): ?>
            <tr class="border-b">
                <td class="px-4 py-3"><?php echo $i+1; ?></td>
                <td class="px-4 py-3 font-semibold"><?php echo htmlspecialchars($t['full_name']); ?></td>
                <td class="px-4 py-3"><?php echo htmlspecialchars($t['email']); ?></td>
                <td class="px-4 py-3"><span class="bg-amber-100 text-amber-700 px-3 py-1 rounded-full"><?php echo $t['courses_count']; ?></span></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>lucide.createIcons();</script>
