<?php
/**
 * Manager Dashboard - Registration Requests System
 * نظام طلبات الالتحاق الشامل
 */

global $managerHelper, $conn;

// Handle actions
$action_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    switch ($_POST['action']) {
        case 'approve':
            $action_result = $managerHelper->approveRequest($_POST['request_id']);
            break;
        case 'reject':
            $action_result = $managerHelper->rejectRequest($_POST['request_id'], $_POST['reason'] ?? '');
            break;
        case 'bulk_approve':
            $ids = explode(',', $_POST['request_ids']);
            $action_result = $managerHelper->bulkApproveRequests($ids);
            break;
    }
}

// Get all requests
$requests = $managerHelper->getAllRequests();

// Apply filters
$status_filter = $_GET['status'] ?? 'all';
$course_filter = $_GET['course'] ?? 'all';
$search = $_GET['search'] ?? '';

if ($status_filter !== 'all') {
    $requests = array_filter($requests, fn($r) => $r['status'] === $status_filter);
}

if ($course_filter !== 'all') {
    $requests = array_filter($requests, fn($r) => $r['course_id'] == $course_filter);
}

if (!empty($search)) {
    $requests = array_filter($requests, function($r) use ($search) {
        return stripos($r['full_name'], $search) !== false || 
               stripos($r['email'], $search) !== false ||
               stripos($r['phone'] ?? '', $search) !== false;
    });
}

// Get statistics
$total_requests = count($requests);
$pending_requests = count(array_filter($requests, fn($r) => $r['status'] === 'pending'));
$approved_requests = count(array_filter($requests, fn($r) => $r['status'] === 'approved'));
$rejected_requests = count(array_filter($requests, fn($r) => $r['status'] === 'rejected'));

// Get courses for filter
$courses = $managerHelper->getAllCourses();
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="inbox" class="w-10 h-10"></i>
                طلبات الالتحاق
            </h1>
            <p class="text-indigo-100 text-lg">إدارة ومراجعة طلبات التسجيل في الدورات</p>
        </div>
        <?php if ($pending_requests > 0): ?>
        <div class="bg-white/20 backdrop-blur-sm rounded-xl px-6 py-4 border border-white/30">
            <div class="text-center">
                <div class="text-4xl font-bold"><?php echo $pending_requests; ?></div>
                <div class="text-sm opacity-90 mt-1">طلب معلق</div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($action_result): ?>
<div class="bg-white rounded-xl shadow-lg p-6 mb-8 border-r-4 <?php echo $action_result['success'] ? 'border-emerald-500' : 'border-red-500'; ?>">
    <div class="flex items-center gap-3">
        <i data-lucide="<?php echo $action_result['success'] ? 'check-circle' : 'alert-circle'; ?>" 
           class="w-6 h-6 <?php echo $action_result['success'] ? 'text-emerald-600' : 'text-red-600'; ?>"></i>
        <span class="font-semibold <?php echo $action_result['success'] ? 'text-emerald-800' : 'text-red-800'; ?>">
            <?php echo $action_result['message']; ?>
        </span>
    </div>
</div>
<?php endif; ?>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="inbox" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">الإجمالي</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $total_requests; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي الطلبات</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-amber-100 rounded-xl flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">معلق</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $pending_requests; ?></h3>
        <p class="text-slate-500 text-sm">بانتظار المراجعة</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-emerald-100 rounded-xl flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">مقبول</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $approved_requests; ?></h3>
        <p class="text-slate-500 text-sm">تمت الموافقة</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-sm font-semibold text-red-600 bg-red-50 px-3 py-1 rounded-full">مرفوض</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $rejected_requests; ?></h3>
        <p class="text-slate-500 text-sm">تم الرفض</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <form method="GET" class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <input type="hidden" name="page" value="requests">
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">بحث</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
                placeholder="اسم، بريد، أو هاتف..." 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
            <select name="status" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all" <?php echo $status_filter === 'all' ? 'selected' : ''; ?>>جميع الحالات</option>
                <option value="pending" <?php echo $status_filter === 'pending' ? 'selected' : ''; ?>>معلق</option>
                <option value="approved" <?php echo $status_filter === 'approved' ? 'selected' : ''; ?>>مقبول</option>
                <option value="rejected" <?php echo $status_filter === 'rejected' ? 'selected' : ''; ?>>مرفوض</option>
            </select>
        </div>
        
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
            <select name="course" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-indigo-500">
                <option value="all">جميع الدورات</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($course['course_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="flex items-end gap-2">
            <button type="submit" class="flex-1 bg-indigo-600 text-white px-4 py-2 rounded-lg hover:bg-indigo-700 transition-all font-semibold">
                <i data-lucide="search" class="w-4 h-4 inline mr-2"></i>
                بحث
            </button>
            <a href="?page=requests" class="px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-all">
                <i data-lucide="x" class="w-4 h-4"></i>
            </a>
        </div>
    </form>
</div>

<!-- Requests Table -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-gradient-to-r from-indigo-50 to-purple-50">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">
                        <input type="checkbox" id="selectAll" class="w-4 h-4 text-indigo-600 rounded">
                    </th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">المتقدم</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الدورة</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">التاريخ</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الإجراءات</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center">
                            <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                            <p class="text-slate-500 text-lg">لا توجد طلبات</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $request): ?>
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="px-6 py-4">
                                <?php if ($request['status'] === 'pending'): ?>
                                    <input type="checkbox" class="request-checkbox w-4 h-4 text-indigo-600 rounded" 
                                           value="<?php echo $request['request_id']; ?>">
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full flex items-center justify-center text-white font-bold">
                                        <?php echo mb_substr($request['full_name'], 0, 1); ?>
                                    </div>
                                    <div>
                                        <h4 class="font-semibold text-slate-800"><?php echo htmlspecialchars($request['full_name']); ?></h4>
                                        <div class="flex items-center gap-3 text-xs text-slate-500">
                                            <span><i data-lucide="mail" class="w-3 h-3 inline"></i> <?php echo htmlspecialchars($request['email']); ?></span>
                                            <?php if (!empty($request['phone'])): ?>
                                            <span><i data-lucide="phone" class="w-3 h-3 inline"></i> <?php echo htmlspecialchars($request['phone']); ?></span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-700 font-medium"><?php echo htmlspecialchars($request['course_name'] ?? 'غير محدد'); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-slate-600"><?php echo date('Y/m/d', strtotime($request['created_at'])); ?></span>
                            </td>
                            <td class="px-6 py-4">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-amber-100 text-amber-700',
                                    'approved' => 'bg-emerald-100 text-emerald-700',
                                    'rejected' => 'bg-red-100 text-red-700'
                                ];
                                $status_labels = [
                                    'pending' => 'معلق',
                                    'approved' => 'مقبول',
                                    'rejected' => 'مرفوض'
                                ];
                                ?>
                                <span class="px-3 py-1 rounded-full text-xs font-bold <?php echo $status_colors[$request['status']]; ?>">
                                    <?php echo $status_labels[$request['status']]; ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-2">
                                    <?php if ($request['status'] === 'pending'): ?>
                                        <form method="POST" class="inline" onsubmit="return confirm('هل تريد الموافقة على هذا الطلب؟')">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?php echo $request['request_id']; ?>">
                                            <button type="submit" class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-all" title="موافقة">
                                                <i data-lucide="check" class="w-4 h-4"></i>
                                            </button>
                                        </form>
                                        
                                        <button onclick="openRejectModal(<?php echo $request['request_id']; ?>)" 
                                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-all" 
                                                title="رفض">
                                            <i data-lucide="x" class="w-4 h-4"></i>
                                        </button>
                                    <?php endif; ?>
                                    
                                    <a href="mailto:<?php echo htmlspecialchars($request['email']); ?>" 
                                       class="p-2 bg-purple-50 text-purple-600 rounded-lg hover:bg-purple-100 transition-all" 
                                       title="إرسال بريد">
                                        <i data-lucide="mail" class="w-4 h-4"></i>
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

<!-- Bulk Actions -->
<?php if ($pending_requests > 0): ?>
<div class="mt-6 bg-white rounded-xl shadow-lg p-6">
    <form method="POST" id="bulkApproveForm" onsubmit="return confirm('هل تريد الموافقة على جميع الطلبات المحددة؟')">
        <input type="hidden" name="action" value="bulk_approve">
        <input type="hidden" name="request_ids" id="selectedRequestIds">
        <div class="flex items-center justify-between">
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-1">إجراءات جماعية</h3>
                <p class="text-sm text-slate-600">تم تحديد <span id="selectedCount">0</span> طلب</p>
            </div>
            <button type="submit" class="bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed" id="bulkApproveBtn" disabled>
                <i data-lucide="check-circle" class="w-5 h-5"></i>
                الموافقة على المحدد
            </button>
        </div>
    </form>
</div>
<?php endif; ?>

<!-- Reject Modal -->
<div id="rejectModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white p-6 rounded-t-2xl">
            <h3 class="text-2xl font-bold flex items-center gap-2">
                <i data-lucide="x-circle" class="w-7 h-7"></i>
                رفض الطلب
            </h3>
        </div>
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="reject">
            <input type="hidden" name="request_id" id="rejectRequestId">
            
            <div class="mb-6">
                <label class="block text-sm font-bold text-slate-700 mb-2">سبب الرفض *</label>
                <textarea name="reason" rows="4" required 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg focus:ring-2 focus:ring-red-500 focus:border-transparent"
                    placeholder="يرجى كتابة سبب رفض الطلب..."></textarea>
            </div>
            
            <div class="flex gap-3">
                <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition-all">
                    تأكيد الرفض
                </button>
                <button type="button" onclick="closeRejectModal()" class="px-6 py-3 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Select All Checkbox
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.request-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateSelectedCount();
});

// Individual Checkboxes
document.querySelectorAll('.request-checkbox').forEach(cb => {
    cb.addEventListener('change', updateSelectedCount);
});

function updateSelectedCount() {
    const selected = document.querySelectorAll('.request-checkbox:checked');
    const count = selected.length;
    document.getElementById('selectedCount').textContent = count;
    
    const bulkBtn = document.getElementById('bulkApproveBtn');
    if (bulkBtn) {
        bulkBtn.disabled = count === 0;
    }
    
    // Update hidden input with selected IDs
    const ids = Array.from(selected).map(cb => cb.value);
    document.getElementById('selectedRequestIds').value = ids.join(',');
}

function openRejectModal(requestId) {
    document.getElementById('rejectRequestId').value = requestId;
    document.getElementById('rejectModal').classList.remove('hidden');
}

function closeRejectModal() {
    document.getElementById('rejectModal').classList.add('hidden');
}

lucide.createIcons();
</script>
