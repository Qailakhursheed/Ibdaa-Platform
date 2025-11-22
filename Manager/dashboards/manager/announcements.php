<?php
/**
 * Manager - Announcements Management
 * إدارة الإعلانات
 */

global $conn;

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'create') {
        $title = $_POST['title'] ?? '';
        $content = $_POST['content'] ?? '';
        $priority = $_POST['priority'] ?? 'normal';
        $target = $_POST['target'] ?? 'all';
        
        $stmt = $conn->prepare("INSERT INTO announcements (title, content, priority, target_audience, created_by, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->bind_param("ssssi", $title, $content, $priority, $target, $_SESSION['user_id']);
        $stmt->execute();
    }
}

// Get filters
$priority_filter = $_GET['priority'] ?? 'all';
$target_filter = $_GET['target'] ?? 'all';

// Get announcements
$query = "SELECT a.*, u.full_name as creator_name FROM announcements a LEFT JOIN users u ON a.created_by = u.id WHERE 1=1";

if ($priority_filter !== 'all') {
    $query .= " AND a.priority = '" . $conn->real_escape_string($priority_filter) . "'";
}

if ($target_filter !== 'all') {
    $query .= " AND a.target_audience = '" . $conn->real_escape_string($target_filter) . "'";
}

$query .= " ORDER BY a.created_at DESC";

$announcements = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-blue-500 to-cyan-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="megaphone" class="w-10 h-10"></i>
                إدارة الإعلانات
            </h1>
            <p class="text-blue-100 text-lg">نشر وإدارة الإعلانات للطلاب والمدربين</p>
        </div>
        <button onclick="document.getElementById('createModal').classList.remove('hidden')" 
                class="bg-white text-blue-600 px-6 py-3 rounded-lg font-bold hover:bg-blue-50 transition">
            <i data-lucide="plus" class="w-5 h-5 inline"></i> إعلان جديد
        </button>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo count($announcements); ?></h3>
        <p class="text-slate-600">إجمالي الإعلانات</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <h3 class="text-3xl font-bold text-slate-800">
            <?php echo count(array_filter($announcements, fn($a) => $a['priority'] === 'urgent')); ?>
        </h3>
        <p class="text-slate-600">عاجلة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800">
            <?php echo count(array_filter($announcements, fn($a) => $a['priority'] === 'important')); ?>
        </h3>
        <p class="text-slate-600">مهمة</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800">
            <?php echo count(array_filter($announcements, fn($a) => $a['priority'] === 'normal')); ?>
        </h3>
        <p class="text-slate-600">عادية</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="announcements">
        
        <select name="priority" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الأولويات</option>
            <option value="urgent" <?php echo $priority_filter === 'urgent' ? 'selected' : ''; ?>>عاجل</option>
            <option value="important" <?php echo $priority_filter === 'important' ? 'selected' : ''; ?>>مهم</option>
            <option value="normal" <?php echo $priority_filter === 'normal' ? 'selected' : ''; ?>>عادي</option>
        </select>

        <select name="target" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الفئات</option>
            <option value="students" <?php echo $target_filter === 'students' ? 'selected' : ''; ?>>طلاب</option>
            <option value="trainers" <?php echo $target_filter === 'trainers' ? 'selected' : ''; ?>>مدربون</option>
            <option value="all" <?php echo $target_filter === 'all' ? 'selected' : ''; ?>>الجميع</option>
        </select>
        
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
            <i data-lucide="filter" class="w-4 h-4 inline"></i> تطبيق
        </button>
    </form>
</div>

<!-- Announcements List -->
<div class="space-y-4">
    <?php if (empty($announcements)): ?>
        <div class="bg-white rounded-xl shadow-lg p-12 text-center">
            <i data-lucide="megaphone" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
            <p class="text-slate-500 text-lg">لا توجد إعلانات</p>
        </div>
    <?php else: ?>
        <?php foreach ($announcements as $announcement): 
            $priority_colors = [
                'urgent' => 'border-red-500 bg-red-50',
                'important' => 'border-amber-500 bg-amber-50',
                'normal' => 'border-blue-500 bg-blue-50'
            ];
            $priority_labels = [
                'urgent' => 'عاجل',
                'important' => 'مهم',
                'normal' => 'عادي'
            ];
        ?>
            <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 <?php echo $priority_colors[$announcement['priority']] ?? ''; ?>">
                <div class="flex justify-between items-start mb-4">
                    <div class="flex-1">
                        <div class="flex items-center gap-3 mb-2">
                            <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($announcement['title']); ?></h3>
                            <span class="px-3 py-1 text-xs rounded-full bg-<?php echo $announcement['priority'] === 'urgent' ? 'red' : ($announcement['priority'] === 'important' ? 'amber' : 'blue'); ?>-100 text-<?php echo $announcement['priority'] === 'urgent' ? 'red' : ($announcement['priority'] === 'important' ? 'amber' : 'blue'); ?>-700">
                                <?php echo $priority_labels[$announcement['priority']]; ?>
                            </span>
                        </div>
                        <p class="text-slate-600 mb-3"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        <div class="flex items-center gap-4 text-sm text-slate-500">
                            <span><i data-lucide="user" class="w-4 h-4 inline"></i> <?php echo htmlspecialchars($announcement['creator_name'] ?? 'غير معروف'); ?></span>
                            <span><i data-lucide="clock" class="w-4 h-4 inline"></i> <?php echo date('Y/m/d H:i', strtotime($announcement['created_at'])); ?></span>
                            <span><i data-lucide="users" class="w-4 h-4 inline"></i> <?php echo $announcement['target_audience'] === 'all' ? 'الجميع' : ($announcement['target_audience'] === 'students' ? 'الطلاب' : 'المدربون'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Create Modal -->
<div id="createModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-2xl shadow-2xl p-8 max-w-2xl w-full mx-4">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-bold text-slate-800">إعلان جديد</h2>
            <button onclick="document.getElementById('createModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <form method="POST" class="space-y-4">
            <input type="hidden" name="action" value="create">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">العنوان</label>
                <input type="text" name="title" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">المحتوى</label>
                <textarea name="content" required rows="4" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-blue-500"></textarea>
            </div>
            
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الأولوية</label>
                    <select name="priority" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                        <option value="normal">عادي</option>
                        <option value="important">مهم</option>
                        <option value="urgent">عاجل</option>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-slate-700 mb-2">الفئة المستهدفة</label>
                    <select name="target" class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                        <option value="all">الجميع</option>
                        <option value="students">الطلاب</option>
                        <option value="trainers">المدربون</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-lg font-bold hover:bg-blue-700 transition">
                    <i data-lucide="send" class="w-5 h-5 inline"></i> نشر الإعلان
                </button>
                <button type="button" onclick="document.getElementById('createModal').classList.add('hidden')" 
                        class="px-6 py-3 border border-slate-300 rounded-lg hover:bg-slate-50 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
<script>lucide.createIcons();</script>
