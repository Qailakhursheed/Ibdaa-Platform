<?php
// Load materials data using StudentHelper
global $studentHelper;
$allCourses = $studentHelper->getMyCourses();
$courseId = $_GET['course_id'] ?? null;
$materials = [];
if ($courseId) {
    $materials = $studentHelper->getCourseMaterials($courseId);
}
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">المواد الدراسية</h2>
            <p class="text-slate-600 mt-1">تحميل ومشاهدة المواد التعليمية - <?php echo count($materials); ?> مادة</p>
        </div>
    </div>

    <!-- Filter - PHP Generated -->
    <div class="bg-white border border-slate-200 rounded-xl p-6 shadow-md">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    الدورة
                </label>
                <select id="materialCourse" onchange="window.location.href='?page=materials&course_id='+this.value" 
                    class="w-full px-4 py-3 border border-slate-300 rounded-lg hover:border-amber-500 focus:border-amber-500 transition-all">
                    <option value="">اختر دورة</option>
                    <?php foreach ($allCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $courseId == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">نوع المادة</label>
                <select id="materialType" onchange="filterMaterials()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الأنواع</option>
                    <option value="pdf">PDF</option>
                    <option value="video">فيديو</option>
                    <option value="ppt">عرض تقديمي</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
                <input type="text" id="materialSearch" placeholder="ابحث في المواد..." 
                    oninput="searchMaterials()"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
            </div>
        </div>
    </div>

    <!-- Materials Grid -->
    <div id="materialsGrid" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="col-span-full text-center py-12">
            <i data-lucide="folder" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
            <p class="text-slate-600">اختر دورة لعرض المواد</p>
        </div>
    </div>
</div>

<script>
let allMaterials = [];

async function loadCourses() {
    const response = await StudentFeatures.courses.getMyCourses();
    if (response.success && response.data) {
        const select = document.getElementById('materialCourse');
        response.data.forEach(course => {
            const option = document.createElement('option');
            option.value = course.id;
            option.textContent = course.course_name;
            select.appendChild(option);
        });
    }
}

async function loadMaterials() {
    const courseId = document.getElementById('materialCourse').value;
    if (!courseId) return;
    
    const response = await StudentFeatures.materials.getCourseMaterials(courseId);
    
    if (response.success && response.data) {
        allMaterials = response.data;
        renderMaterials(allMaterials);
    }
    
    lucide.createIcons();
}

function renderMaterials(materials) {
    const grid = document.getElementById('materialsGrid');
    
    if (materials.length === 0) {
        grid.innerHTML = `
            <div class="col-span-full text-center py-12">
                <i data-lucide="folder-x" class="w-12 h-12 mx-auto text-slate-400 mb-3"></i>
                <p class="text-slate-600">لا توجد مواد متاحة</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = materials.map(material => {
        const icon = material.type === 'pdf' ? 'file-text' : 
                     material.type === 'video' ? 'video' : 
                     material.type === 'ppt' ? 'presentation' : 'file';
        const color = material.type === 'pdf' ? 'red' : 
                      material.type === 'video' ? 'purple' : 
                      material.type === 'ppt' ? 'orange' : 'slate';
        
        return `
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-32 bg-gradient-to-br from-${color}-500 to-${color}-600 flex items-center justify-center">
                    <i data-lucide="${icon}" class="w-12 h-12 text-white"></i>
                </div>
                
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-2 line-clamp-1">${material.title}</h3>
                    <p class="text-sm text-slate-600 mb-4 line-clamp-2">${material.description || 'لا يوجد وصف'}</p>
                    
                    <div class="flex items-center justify-between text-sm text-slate-500 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>${material.upload_date}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="file" class="w-4 h-4"></i>
                            <span>${material.size || '0'} MB</span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        ${material.type === 'video' ? `
                            <button onclick="playVideo('${material.file_url}')" 
                                class="flex-1 px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors text-sm font-semibold">
                                <i data-lucide="play" class="w-4 h-4 inline"></i>
                                تشغيل
                            </button>
                        ` : `
                            <button onclick="previewMaterial(${material.id})" 
                                class="flex-1 px-4 py-2 bg-slate-100 text-slate-700 rounded-lg hover:bg-slate-200 transition-colors text-sm font-semibold">
                                <i data-lucide="eye" class="w-4 h-4 inline"></i>
                                عرض
                            </button>
                        `}
                        <button onclick="downloadMaterial(${material.id})" 
                            class="flex-1 px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition-colors text-sm font-semibold">
                            <i data-lucide="download" class="w-4 h-4 inline"></i>
                            تحميل
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

async function downloadMaterial(materialId) {
    const response = await StudentFeatures.materials.downloadMaterial(materialId);
    if (response.success) {
        DashboardIntegration.ui.showToast('تم تحميل المادة بنجاح', 'success');
    } else {
        DashboardIntegration.ui.showToast('فشل تحميل المادة', 'error');
    }
}

function previewMaterial(materialId) {
    DashboardIntegration.ui.showToast('سيتم فتح معاينة المادة قريباً', 'info');
}

function playVideo(videoUrl) {
    DashboardIntegration.ui.showToast('سيتم فتح مشغل الفيديو قريباً', 'info');
}

function filterMaterials() {
    const type = document.getElementById('materialType').value;
    const filtered = type === 'all' ? allMaterials : allMaterials.filter(m => m.type === type);
    renderMaterials(filtered);
}

function searchMaterials() {
    const search = document.getElementById('materialSearch').value.toLowerCase();
    const filtered = allMaterials.filter(m => 
        m.title.toLowerCase().includes(search) ||
        (m.description && m.description.toLowerCase().includes(search))
    );
    renderMaterials(filtered);
}

// Initialize with conditional loading
if (typeof StudentFeatures !== 'undefined') {
    loadCourses();
} else {
    console.log('Waiting for StudentFeatures to load...');
    setTimeout(() => {
        if (typeof StudentFeatures !== 'undefined') {
            loadCourses();
        } else {
            console.error('StudentFeatures failed to load');
        }
    }, 1000);
}
</script>
