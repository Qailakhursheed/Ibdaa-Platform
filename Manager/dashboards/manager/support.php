<?php
/**
 * Manager - Technical Support
 * الدعم الفني
 */

global $conn;

// Get filter parameters
$status_filter = $_GET['status'] ?? 'all';
$priority_filter = $_GET['priority'] ?? 'all';

// Get support tickets
$query = "SELECT 
    t.*,
    u.full_name as user_name,
    u.email as user_email,
    u.role as user_role,
    admin.full_name as assigned_to_name
FROM support_tickets t
JOIN users u ON t.user_id = u.id
LEFT JOIN users admin ON t.assigned_to = admin.id
WHERE 1=1";

if ($status_filter !== 'all') {
    $query .= " AND t.status = '" . $conn->real_escape_string($status_filter) . "'";
}

if ($priority_filter !== 'all') {
    $query .= " AND t.priority = '" . $conn->real_escape_string($priority_filter) . "'";
}

$query .= " ORDER BY 
    CASE t.priority 
        WHEN 'urgent' THEN 1
        WHEN 'high' THEN 2
        WHEN 'medium' THEN 3
        WHEN 'low' THEN 4
    END,
    t.created_at DESC";

$tickets = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $tickets[] = $row;
    }
}

// Statistics
$total = count($tickets);
$open_tickets = count(array_filter($tickets, fn($t) => $t['status'] === 'open'));
$in_progress = count(array_filter($tickets, fn($t) => $t['status'] === 'in_progress'));
$resolved = count(array_filter($tickets, fn($t) => $t['status'] === 'resolved'));
$urgent = count(array_filter($tickets, fn($t) => $t['priority'] === 'urgent'));
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-red-500 to-pink-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="headphones" class="w-10 h-10"></i>
        الدعم الفني
    </h1>
    <p class="text-red-100 text-lg">إدارة طلبات الدعم والمساعدة</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $total; ?></h3>
        <p class="text-slate-600">إجمالي الطلبات</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $open_tickets; ?></h3>
        <p class="text-slate-600">مفتوحة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $in_progress; ?></h3>
        <p class="text-slate-600">قيد المعالجة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $resolved; ?></h3>
        <p class="text-slate-600">محلولة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-600">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $urgent; ?></h3>
        <p class="text-slate-600">عاجلة</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="support">
        
        <select name="status" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الحالات</option>
            <option value="open" <?php echo $status_filter === 'open' ? 'selected' : ''; ?>>مفتوحة</option>
            <option value="in_progress" <?php echo $status_filter === 'in_progress' ? 'selected' : ''; ?>>قيد المعالجة</option>
            <option value="resolved" <?php echo $status_filter === 'resolved' ? 'selected' : ''; ?>>محلولة</option>
            <option value="closed" <?php echo $status_filter === 'closed' ? 'selected' : ''; ?>>مغلقة</option>
        </select>

        <select name="priority" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الأولويات</option>
            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>عاجل</option>
            <option value="high" <?php echo $priority_filter === 'high' ? 'selected' : ''; ?>>مرتفع</option>
            <option value="medium" <?php echo $priority_filter === 'medium' ? 'selected' : ''; ?>>متوسط</option>
            <option value="low" <?php echo $priority_filter === 'low' ? 'selected' : ''; ?>>منخفض</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition">
            <i data-lucide="filter" class="w-4 h-4 inline"></i> تطبيق
        </button>
    </form>
</div>

<!-- Tickets List -->
<div class="space-y-4">
    <?php if (empty($tickets)): ?>
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i data-lucide="headphones" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
            <p class="text-slate-500 text-lg">لا توجد طلبات دعم</p>
        </div>
    <?php else: ?>
        <?php foreach ($tickets as $ticket): 
            $priority_colors = [
                'urgent' => 'border-red-600 bg-red-50',
                'high' => 'border-orange-500 bg-orange-50',
                'medium' => 'border-amber-500 bg-amber-50',
                'low' => 'border-blue-500 bg-blue-50'
            ];
            $priority_labels = [
                'urgent' => 'عاجل',
                'high' => 'مرتفع',
                'medium' => 'متوسط',
                'low' => 'منخفض'
            ];
            $status_colors = [
                'open' => 'bg-amber-100 text-amber-700',
                'in_progress' => 'bg-blue-100 text-blue-700',
                'resolved' => 'bg-green-100 text-green-700',
                'closed' => 'bg-slate-100 text-slate-700'
            ];
            $status_labels = [
                'open' => 'مفتوحة',
                'in_progress' => 'قيد المعالجة',
                'resolved' => 'محلولة',
                'closed' => 'مغلقة'
            ];
        ?>
            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 <?php echo $priority_colors[$ticket['priority']] ?? ''; ?>">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <span class="text-sm font-mono text-slate-500">#{<?php echo $ticket['ticket_id']; ?>}</span>
                            <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($ticket['subject']); ?></h3>
                            <span class="px-3 py-1 text-xs rounded-full bg-<?php echo $ticket['priority'] === 'urgent' ? 'red' : ($ticket['priority'] === 'high' ? 'orange' : ($ticket['priority'] === 'medium' ? 'amber' : 'blue')); ?>-100 text-<?php echo $ticket['priority'] === 'urgent' ? 'red' : ($ticket['priority'] === 'high' ? 'orange' : ($ticket['priority'] === 'medium' ? 'amber' : 'blue')); ?>-700">
                                <?php echo $priority_labels[$ticket['priority']]; ?>
                            </span>
                            <span class="px-3 py-1 text-xs rounded-full <?php echo $status_colors[$ticket['status']] ?? ''; ?>">
                                <?php echo $status_labels[$ticket['status']]; ?>
                            </span>
                        </div>
                        <p class="text-slate-600 mb-3"><?php echo nl2br(htmlspecialchars($ticket['message'])); ?></p>
                        <div class="flex items-center gap-4 text-sm text-slate-500">
                            <span><i data-lucide="user" class="w-4 h-4 inline"></i> <?php echo htmlspecialchars($ticket['user_name']); ?> (<?php echo $ticket['user_role']; ?>)</span>
                            <span><i data-lucide="mail" class="w-4 h-4 inline"></i> <?php echo htmlspecialchars($ticket['user_email']); ?></span>
                            <span><i data-lucide="calendar" class="w-4 h-4 inline"></i> <?php echo date('Y/m/d H:i', strtotime($ticket['created_at'])); ?></span>
                            <?php if ($ticket['assigned_to_name']): ?>
                                <span><i data-lucide="user-check" class="w-4 h-4 inline"></i> مع الفني: <?php echo htmlspecialchars($ticket['assigned_to_name']); ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="flex gap-2">
                        <?php if ($ticket['status'] !== 'resolved' && $ticket['status'] !== 'closed'): ?>
                            <button class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition text-sm">
                                <i data-lucide="check" class="w-4 h-4 inline"></i> حل
                            </button>
                        <?php endif; ?>
                        <button class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-sm">
                            <i data-lucide="message-square" class="w-4 h-4 inline"></i> رد
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
