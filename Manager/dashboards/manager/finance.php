<?php
/**
 * Manager - Financial Management
 * الإدارة المالية
 */

global $conn;

// Get financial statistics
$stats = [
    'total_revenue' => 0,
    'pending_payments' => 0,
    'completed_payments' => 0,
    'refunds' => 0
];

// Total revenue
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['total_revenue'] = $row['total'] ?? 0;
}

// Pending payments
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'pending'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['pending_payments'] = $row['total'] ?? 0;
}

// Completed count
$result = $conn->query("SELECT COUNT(*) as count FROM payments WHERE status = 'completed'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['completed_payments'] = $row['count'] ?? 0;
}

// Refunds
$result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'refunded'");
if ($result && $row = $result->fetch_assoc()) {
    $stats['refunds'] = $row['total'] ?? 0;
}

// Get recent transactions
$transactions = [];
$result = $conn->query("
    SELECT p.*, u.full_name, c.name as course_name 
    FROM payments p
    LEFT JOIN users u ON p.user_id = u.id
    LEFT JOIN courses c ON p.course_id = c.course_id
    ORDER BY p.payment_date DESC
    LIMIT 50
");

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $transactions[] = $row;
    }
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-green-500 to-emerald-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="dollar-sign" class="w-10 h-10"></i>
        الإدارة المالية
    </h1>
    <p class="text-green-100 text-lg">متابعة المدفوعات والإيرادات</p>
</div>

<!-- Financial Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-600">إجمالي الإيرادات</span>
            <i data-lucide="trending-up" class="w-5 h-5 text-green-500"></i>
        </div>
        <h3 class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['total_revenue'], 0); ?></h3>
        <p class="text-sm text-slate-500 mt-1">ريال يمني</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-600">مدفوعات معلقة</span>
            <i data-lucide="clock" class="w-5 h-5 text-amber-500"></i>
        </div>
        <h3 class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['pending_payments'], 0); ?></h3>
        <p class="text-sm text-slate-500 mt-1">ريال يمني</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-600">معاملات مكتملة</span>
            <i data-lucide="check-circle" class="w-5 h-5 text-blue-500"></i>
        </div>
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $stats['completed_payments']; ?></h3>
        <p class="text-sm text-slate-500 mt-1">معاملة</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <div class="flex items-center justify-between mb-2">
            <span class="text-slate-600">استردادات</span>
            <i data-lucide="arrow-left-circle" class="w-5 h-5 text-red-500"></i>
        </div>
        <h3 class="text-3xl font-bold text-slate-800"><?php echo number_format($stats['refunds'], 0); ?></h3>
        <p class="text-sm text-slate-500 mt-1">ريال يمني</p>
    </div>
</div>

<!-- Transactions Table -->
<div class="bg-white rounded-2xl shadow-sm border overflow-hidden">
    <div class="px-6 py-4 border-b bg-slate-50">
        <h2 class="text-xl font-bold text-slate-800">المعاملات الأخيرة</h2>
    </div>
    <table class="w-full">
        <thead class="bg-slate-50">
            <tr>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">#</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الطالب</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الدورة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">المبلغ</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">الحالة</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">التاريخ</th>
                <th class="px-6 py-3 text-right text-xs font-medium text-slate-500 uppercase">إجراءات</th>
            </tr>
        </thead>
        <tbody class="divide-y">
            <?php if (empty($transactions)): ?>
                <tr>
                    <td colspan="7" class="px-6 py-12 text-center text-slate-500">
                        <i data-lucide="dollar-sign" class="w-12 h-12 mx-auto mb-2 text-slate-300"></i>
                        <p>لا توجد معاملات</p>
                    </td>
                </tr>
            <?php else: ?>
                <?php foreach ($transactions as $idx => $transaction): ?>
                    <tr class="hover:bg-slate-50">
                        <td class="px-6 py-4 text-sm text-slate-600"><?php echo $idx + 1; ?></td>
                        <td class="px-6 py-4">
                            <p class="font-medium"><?php echo htmlspecialchars($transaction['full_name'] ?? 'غير معروف'); ?></p>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php echo htmlspecialchars($transaction['course_name'] ?? '-'); ?>
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-bold text-green-600"><?php echo number_format($transaction['amount'], 0); ?> ريال</span>
                        </td>
                        <td class="px-6 py-4">
                            <?php
                            $status_colors = [
                                'completed' => 'bg-green-100 text-green-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'failed' => 'bg-red-100 text-red-700',
                                'refunded' => 'bg-slate-100 text-slate-700'
                            ];
                            $status_labels = [
                                'completed' => 'مكتمل',
                                'pending' => 'معلق',
                                'failed' => 'فشل',
                                'refunded' => 'مسترد'
                            ];
                            ?>
                            <span class="px-2 py-1 text-xs rounded-full <?php echo $status_colors[$transaction['status']] ?? 'bg-slate-100 text-slate-700'; ?>">
                                <?php echo $status_labels[$transaction['status']] ?? $transaction['status']; ?>
                            </span>
                        </td>
                        <td class="px-6 py-4 text-sm text-slate-600">
                            <?php echo date('Y/m/d', strtotime($transaction['payment_date'])); ?>
                        </td>
                        <td class="px-6 py-4">
                            <button class="text-blue-600 hover:text-blue-700 text-sm">
                                <i data-lucide="eye" class="w-4 h-4 inline"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
