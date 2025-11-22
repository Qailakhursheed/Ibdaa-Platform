<?php
/**
 * Technical Dashboard - Materials Management (Hybrid PHP System)
 * إدارة المواد التدريبية - نظام هجين محدث
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper is already initialized
 */

// Get materials data
$materials = $technicalHelper->getAllMaterials();
$courses = $technicalHelper->getAllCourses();
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2 flex items-center gap-3">
                <i data-lucide="file-text" class="w-8 h-8 text-violet-600"></i>
                المواد التدريبية
            </h1>
            <p class="text-slate-600">مراجعة والموافقة على المواد التدريبية - <?php echo count($materials); ?> مادة</p>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="file-text" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo count($materials); ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي المواد</p>
    </div>
    
    <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="file" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                echo count(array_filter($materials, function($m) { return $m['file_type'] === 'pdf'; }));
            ?></span>
        </div>
        <p class="text-sm opacity-90">ملفات PDF</p>
    </div>
    
    <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="video" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                echo count(array_filter($materials, function($m) { return $m['file_type'] === 'video'; }));
            ?></span>
        </div>
        <p class="text-sm opacity-90">فيديوهات</p>
    </div>
    
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="file-text" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                echo count(array_filter($materials, function($m) { return $m['file_type'] === 'document'; }));
            ?></span>
        </div>
        <p class="text-sm opacity-90">مستندات</p>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
            <input type="text" id="searchMaterials" placeholder="ابحث عن مادة..." 
                class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500"
                onkeyup="filterMaterials()">
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
            <select id="filterCourse" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500" onchange="filterMaterials()">
                <option value="">جميع الدورات</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">النوع</label>
            <select id="filterType" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500" onchange="filterMaterials()">
                <option value="">جميع الأنواع</option>
                <option value="pdf">PDF</option>
                <option value="video">فيديو</option>
                <option value="document">مستند</option>
                <option value="image">صورة</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الترتيب</label>
            <select id="sortBy" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-violet-500" onchange="filterMaterials()">
                <option value="date_desc">الأحدث أولاً</option>
                <option value="date_asc">الأقدم أولاً</option>
                <option value="name_asc">الاسم (أ-ي)</option>
            </select>
        </div>
    </div>
</div>

<!-- Materials Grid -->
<div id="materialsContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($materials)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-xl shadow-lg">
            <i data-lucide="file-text" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد مواد</h3>
            <p class="text-slate-500">لم يتم إضافة أي مواد بعد</p>
        </div>
    <?php else: ?>
        <?php foreach ($materials as $material): 
            $typeIcons = [
                'pdf' => 'file-text',
                'video' => 'video',
                'document' => 'file',
                'image' => 'image'
            ];
            $typeColors = [
                'pdf' => 'from-red-500 to-rose-600',
                'video' => 'from-purple-500 to-pink-600',
                'document' => 'from-sky-500 to-blue-600',
                'image' => 'from-emerald-500 to-teal-600'
            ];
        ?>
            <div class="material-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden"
                 data-name="<?php echo strtolower($material['title']); ?>"
                 data-course="<?php echo $material['course_id']; ?>"
                 data-type="<?php echo $material['file_type']; ?>"
                 data-date="<?php echo strtotime($material['upload_date']); ?>">
                <div class="bg-gradient-to-br <?php echo $typeColors[$material['file_type']] ?? 'from-slate-500 to-slate-600'; ?> p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="<?php echo $typeIcons[$material['file_type']] ?? 'file'; ?>" class="w-10 h-10 opacity-80"></i>
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs font-semibold">
                            <?php echo strtoupper($material['file_type']); ?>
                        </span>
                    </div>
                    <h3 class="text-lg font-bold mb-2 line-clamp-2"><?php echo htmlspecialchars($material['title']); ?></h3>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($material['course_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($material['uploader_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo date('Y/m/d', strtotime($material['upload_date'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="viewMaterial(<?php echo $material['material_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-violet-50 text-violet-600 rounded-lg hover:bg-violet-100 transition-colors text-sm font-semibold">
                            عرض
                        </button>
                        <button onclick="downloadMaterial('<?php echo htmlspecialchars($material['file_path']); ?>')" 
                            class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors text-sm font-semibold">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function filterMaterials() {
    const search = document.getElementById('searchMaterials').value.toLowerCase();
    const course = document.getElementById('filterCourse').value;
    const type = document.getElementById('filterType').value;
    const sortBy = document.getElementById('sortBy').value;
    
    let cards = Array.from(document.querySelectorAll('.material-card'));
    
    // Filter
    cards.forEach(card => {
        const name = card.dataset.name;
        const cardCourse = card.dataset.course;
        const cardType = card.dataset.type;
        
        const matchSearch = !search || name.includes(search);
        const matchCourse = !course || cardCourse === course;
        const matchType = !type || cardType === type;
        
        if (matchSearch && matchCourse && matchType) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Sort
    const container = document.getElementById('materialsContainer');
    cards.sort((a, b) => {
        switch(sortBy) {
            case 'date_desc':
                return parseInt(b.dataset.date) - parseInt(a.dataset.date);
            case 'date_asc':
                return parseInt(a.dataset.date) - parseInt(b.dataset.date);
            case 'name_asc':
                return a.dataset.name.localeCompare(b.dataset.name);
            default:
                return 0;
        }
    });
    
    cards.forEach(card => container.appendChild(card));
    lucide.createIcons();
}

function viewMaterial(materialId) {
    window.location.href = `material_details.php?id=${materialId}`;
}

function downloadMaterial(filePath) {
    window.open(filePath, '_blank');
}

lucide.createIcons();
</script>
