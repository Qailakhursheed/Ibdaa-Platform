<?php
/**
 * Manager - Users Management (Comprehensive)
 * إدارة المستخدمين الشاملة
 */

global $managerHelper;

$role_filter = $_GET['role'] ?? 'all';
$search = $_GET['search'] ?? '';
$status_filter = $_GET['status'] ?? 'all';

// Get users using helper
$students = $managerHelper->getAllStudents();
$trainers = $managerHelper->getAllTrainers();

// Combine based on filter
$all_users = [];
if ($role_filter === 'all' || $role_filter === 'student') {
    foreach ($students as $s) { $s['role'] = 'student'; $all_users[] = $s; }
}
if ($role_filter === 'all' || $role_filter === 'trainer') {
    foreach ($trainers as $t) { $t['role'] = 'trainer'; $all_users[] = $t; }
}

// Apply filters
if (!empty($search)) {
    $all_users = array_filter($all_users, function($u) use ($search) {
        return stripos($u['full_name'], $search) !== false || stripos($u['email'], $search) !== false;
    });
}
if ($status_filter !== 'all') {
    $all_users = array_filter($all_users, fn($u) => $u['account_status'] === $status_filter);
}

// Old query variable for compatibility
$query = "SELECT u.*, 
          COUNT(DISTINCT CASE WHEN u.role = 'student' THEN e.id END) as enrollments_count,
          COUNT(DISTINCT CASE WHEN u.role = 'trainer' THEN c.id END) as courses_count
          FROM users u
          LEFT JOIN enrollments e ON u.id = e.user_id
          LEFT JOIN courses c ON u.id = c.trainer_id
          WHERE 1=1";

// تم نقل منطق الفلترة إلى الأعلى باستخدام Helpers - لا حاجة لاستعلام SQL إضافي

// Stats
$stats = [
    'students' => 0,
    'trainers' => 0,
    'managers' => 0,
    'technical' => 0
];

foreach ($all_users as $user) {
    if (isset($stats[$user['role']])) {
        $stats[$user['role']]++;
    }
}
?>

<!-- Filter Bar -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="users">
        
        <select name="role" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all" <?php echo $role_filter === 'all' ? 'selected' : ''; ?>>جميع الأدوار</option>
            <option value="student" <?php echo $role_filter === 'student' ? 'selected' : ''; ?>>طلاب</option>
            <option value="trainer" <?php echo $role_filter === 'trainer' ? 'selected' : ''; ?>>مدربون</option>
            <option value="manager" <?php echo $role_filter === 'manager' ? 'selected' : ''; ?>>مدراء</option>
            <option value="technical" <?php echo $role_filter === 'technical' ? 'selected' : ''; ?>>فنيون</option>
        </select>

        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="ابحث..." class="flex-1 px-4 py-2 border border-slate-300 rounded-lg">
        
        <button type="submit" class="px-6 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
            <i data-lucide="search" class="w-4 h-4 inline"></i>
            بحث
        </button>
    </form>
</div>

<!-- Stats -->
<div class="grid grid-cols-4 gap-4 mb-6">
    <div class="bg-white rounded-xl p-4 border">
        <p class="text-sm text-slate-500">الطلاب</p>
        <p class="text-2xl font-bold"><?php echo $stats['students']; ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 border">
        <p class="text-sm text-slate-500">المدربون</p>
        <p class="text-2xl font-bold"><?php echo $stats['trainers']; ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 border">
        <p class="text-sm text-slate-500">المدراء</p>
        <p class="text-2xl font-bold"><?php echo $stats['managers']; ?></p>
    </div>
    <div class="bg-white rounded-xl p-4 border">
        <p class="text-sm text-slate-500">الفنيون</p>
        <p class="text-2xl font-bold"><?php echo $stats['technical']; ?></p>
    </div>
</div>

<!-- Users Table -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">المستخدم</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدور</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">التسجيل</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">إجراءات</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php foreach ($all_users as $user): 
                $role_colors = [
                    'student' => 'bg-sky-100 text-sky-700',
                    'trainer' => 'bg-emerald-100 text-emerald-700',
                    'manager' => 'bg-violet-100 text-violet-700',
                    'technical' => 'bg-amber-100 text-amber-700'
                ];
                $role_labels = [
                    'student' => 'طالب',
                    'trainer' => 'مدرب',
                    'manager' => 'مدير',
                    'technical' => 'فني'
                ];
            ?>
                <tr class="hover:bg-slate-50">
                    <td class="px-6 py-4">
                        <div>
                            <p class="font-medium"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-sm text-slate-500"><?php echo htmlspecialchars($user['email']); ?></p>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo $role_colors[$user['role']] ?? 'bg-slate-100 text-slate-700'; ?>">
                            <?php echo $role_labels[$user['role']] ?? $user['role']; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs rounded-full <?php echo ($user['verified'] ?? 0) == 1 ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'; ?>">
                            <?php echo ($user['verified'] ?? 0) == 1 ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-sm text-slate-600">
                        <?php echo isset($user['created_at']) ? date('Y/m/d', strtotime($user['created_at'])) : 'غير متاح'; ?>
                    </td>
                    <td class="px-6 py-4">
                        <a href="?page=chat&user=<?php echo $user['id']; ?>" class="text-sky-600 hover:text-sky-700">
                            <i data-lucide="message-circle" class="w-4 h-4"></i>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
