<?php
/**
 * Manager - Course Materials Management
 * إدارة المواد التعليمية
 */

global $managerHelper, $conn;

// Get filter parameters
$course_filter = $_GET['course'] ?? 'all';
$type_filter = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';

// Get all courses
$courses = $managerHelper->getAllCourses();

// Get materials
$query = "SELECT 
    m.*,
    c.name as course_name,
    u.full_name as uploader_name
FROM course_materials m
JOIN courses c ON m.course_id = c.course_id
LEFT JOIN users u ON m.uploaded_by = u.id
WHERE 1=1";

if ($course_filter !== 'all') {
    $query .= " AND c.course_id = " . intval($course_filter);
}

if ($type_filter !== 'all') {
    $query .= " AND m.file_type = '" . $conn->real_escape_string($type_filter) . "'";
}

if (!empty($search)) {
    $search_safe = $conn->real_escape_string($search);
    $query .= " AND (m.title LIKE '%{$search_safe}%' OR m.description LIKE '%{$search_safe}%')";
}

$query .= " ORDER BY m.upload_date DESC";

$materials = [];
$result = $conn->query($query);
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $materials[] = $row;
    }
}

// Statistics
$total = count($materials);
$by_type = [];
foreach ($materials as $m) {
    $type = $m['file_type'] ?? 'other';
    $by_type[$type] = ($by_type[$type] ?? 0) + 1;
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-indigo-500 to-purple-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
        <i data-lucide="book-open" class="w-10 h-10"></i>
        إدارة المواد التعليمية
    </h1>
    <p class="text-indigo-100 text-lg">متابعة جميع مواد الدورات</p>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-indigo-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $total; ?></h3>
        <p class="text-slate-600">إجمالي المواد</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $by_type['pdf'] ?? 0; ?></h3>
        <p class="text-slate-600">PDF</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $by_type['video'] ?? 0; ?></h3>
        <p class="text-slate-600">فيديو</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-green-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $by_type['document'] ?? 0; ?></h3>
        <p class="text-slate-600">مستندات</p>
    </div>
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <h3 class="text-3xl font-bold text-slate-800"><?php echo $by_type['other'] ?? 0; ?></h3>
        <p class="text-slate-600">أخرى</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-2xl shadow-sm p-4 border border-slate-100 mb-6">
    <form method="GET" class="flex gap-4">
        <input type="hidden" name="page" value="materials">
        
        <select name="course" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الدورات</option>
            <?php foreach ($courses as $course): ?>
                <option value="<?php echo $course['course_id']; ?>" <?php echo $course_filter == $course['course_id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($course['course_name']); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <select name="type" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="all">جميع الأنواع</option>
            <option value="pdf" <?php echo $type_filter === 'pdf' ? 'selected' : ''; ?>>PDF</option>
            <option value="video" <?php echo $type_filter === 'video' ? 'selected' : ''; ?>>فيديو</option>
            <option value="document" <?php echo $type_filter === 'document' ? 'selected' : ''; ?>>مستند</option>
            <option value="other" <?php echo $type_filter === 'other' ? 'selected' : ''; ?>>أخرى</option>
        </select>

        <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" 
               placeholder="ابحث..." class="flex-1 px-4 py-2 border border-slate-300 rounded-lg">
        
        <button type="submit" class="px-6 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition">
            <i data-lucide="search" class="w-4 h-4 inline"></i> بحث
        </button>
    </form>
</div>

<!-- Materials Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($materials)): ?>
        <div class="col-span-full bg-white rounded-xl shadow-lg p-12 text-center">
            <i data-lucide="book-open" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
            <p class="text-slate-500 text-lg">لا توجد مواد تعليمية</p>
        </div>
    <?php else: ?>
        <?php foreach ($materials as $material): 
            $type_icons = [
                'pdf' => 'file-text',
                'video' => 'video',
                'document' => 'file',
                'other' => 'paperclip'
            ];
            $type_colors = [
                'pdf' => 'text-red-600',
                'video' => 'text-blue-600',
                'document' => 'text-green-600',
                'other' => 'text-slate-600'
            ];
        ?>
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all">
                <div class="flex items-start gap-4 mb-4">
                    <div class="w-12 h-12 rounded-lg bg-slate-100 flex items-center justify-center <?php echo $type_colors[$material['file_type']] ?? 'text-slate-600'; ?>">
                        <i data-lucide="<?php echo $type_icons[$material['file_type']] ?? 'file'; ?>" class="w-6 h-6"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <h3 class="font-bold text-slate-800 mb-1 truncate"><?php echo htmlspecialchars($material['title']); ?></h3>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($material['course_name']); ?></p>
                    </div>
                </div>
                <p class="text-sm text-slate-600 mb-4 line-clamp-2"><?php echo htmlspecialchars($material['description'] ?? ''); ?></p>
                <div class="flex items-center justify-between text-xs text-slate-500">
                    <span><i data-lucide="user" class="w-3 h-3 inline"></i> <?php echo htmlspecialchars($material['uploader_name'] ?? 'غير معروف'); ?></span>
                    <span><?php echo date('Y/m/d', strtotime($material['upload_date'])); ?></span>
                </div>
                <div class="mt-4 pt-4 border-t flex gap-2">
                    <button class="flex-1 px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition text-sm">
                        <i data-lucide="download" class="w-4 h-4 inline"></i> تحميل
                    </button>
                    <button class="px-4 py-2 border border-slate-300 rounded-lg hover:bg-slate-50 transition text-sm">
                        <i data-lucide="eye" class="w-4 h-4 inline"></i>
                    </button>
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
