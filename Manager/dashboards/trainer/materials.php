<?php
// Load materials using TrainerHelper
global $trainerHelper;
$myCourses = $trainerHelper->getMyCourses();
$selectedCourse = $_GET['course_id'] ?? null;
$materials = [];
if ($selectedCourse) {
    $materials = $trainerHelper->getCourseMaterials($selectedCourse);
}
$totalMaterials = count($materials);
$pdfCount = count(array_filter($materials, fn($m) => $m['file_type'] === 'pdf'));
$videoCount = count(array_filter($materials, fn($m) => in_array($m['file_type'], ['video', 'mp4', 'avi'])));
$otherCount = $totalMaterials - $pdfCount - $videoCount;
?>

<div class="space-y-6">
    <!-- Header -->
    <div class="flex justify-between items-center">
        <div>
            <h2 class="text-2xl font-bold text-slate-800">المواد التعليمية</h2>
            <p class="text-slate-600 mt-1">رفع وإدارة المواد التدريبية - <?php echo $totalMaterials; ?> مادة</p>
        </div>
    </div>

    <!-- Statistics - PHP -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div class="bg-gradient-to-br from-emerald-500 to-green-600 text-white rounded-xl p-6 shadow-xl">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="folder" class="w-10 h-10"></i>
                <span class="text-4xl font-bold"><?php echo $totalMaterials; ?></span>
            </div>
            <p class="text-sm opacity-90 font-semibold">إجمالي المواد</p>
        </div>
        
        <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="file-text" class="w-10 h-10 text-blue-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $pdfCount; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">ملفات PDF</p>
        </div>
        
        <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="video" class="w-10 h-10 text-purple-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $videoCount; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">مقاطع فيديو</p>
        </div>
        
        <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
            <div class="flex items-center justify-between mb-4">
                <i data-lucide="image" class="w-10 h-10 text-pink-600"></i>
                <span class="text-4xl font-bold text-slate-800"><?php echo $otherCount; ?></span>
            </div>
            <p class="text-sm text-slate-600 font-semibold">أخرى</p>
        </div>
    </div>

    <!-- Course Selection -->
    <div class="bg-white border-2 border-slate-200 rounded-xl p-6 shadow-lg">
        <form method="GET" class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <input type="hidden" name="page" value="materials">
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    <i data-lucide="book-open" class="w-4 h-4 inline mr-1"></i>
                    اختر الدورة
                </label>
                <select name="course_id" class="w-full px-4 py-3 border-2 border-slate-300 rounded-lg" onchange="this.form.submit()">
                    <option value="">جميع الدورات</option>
                    <?php foreach ($myCourses as $course): ?>
                        <option value="<?php echo $course['course_id']; ?>" <?php echo $selectedCourse == $course['course_id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($course['course_name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </form>
    </div>

    <!-- Materials List -->
    <div class="bg-white border border-slate-200 rounded-xl p-6">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="materialCourse" onchange="loadMaterials()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الدورات</option>
                </select>
            </div>
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">نوع المادة</label>
                <select id="materialType" onchange="filterMaterials()" 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="all">جميع الأنواع</option>
                    <option value="pdf">PDF</option>
                    <option value="video">فيديو</option>
                    <option value="image">صورة</option>
                    <option value="other">أخرى</option>
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
    <div id="materialsGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <div class="col-span-full flex justify-center py-12">
            <div class="text-center">
                <i data-lucide="loader" class="w-12 h-12 mx-auto animate-spin text-slate-400 mb-3"></i>
                <p class="text-slate-500">جاري التحميل...</p>
            </div>
        </div>
    </div>
</div>

<script>
let allMaterials = [];

async function loadCourses() {
    const response = await TrainerFeatures.courses.getMyCourses();
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
    const response = await TrainerFeatures.materials.getMaterials(
        courseId === 'all' ? null : courseId
    );
    
    if (response.success && response.data) {
        allMaterials = response.data;
        
        // Update statistics
        document.getElementById('totalMaterials').textContent = allMaterials.length;
        document.getElementById('pdfCount').textContent = allMaterials.filter(m => m.type === 'pdf').length;
        document.getElementById('videoCount').textContent = allMaterials.filter(m => m.type === 'video').length;
        document.getElementById('otherCount').textContent = allMaterials.filter(m => !['pdf', 'video'].includes(m.type)).length;
        
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
                <p class="text-slate-600">لا توجد مواد تعليمية</p>
            </div>
        `;
        lucide.createIcons();
        return;
    }
    
    grid.innerHTML = materials.map(material => {
        const icon = material.type === 'pdf' ? 'file-text' : 
                     material.type === 'video' ? 'video' : 
                     material.type === 'image' ? 'image' : 'file';
        const color = material.type === 'pdf' ? 'blue' : 
                      material.type === 'video' ? 'purple' : 
                      material.type === 'image' ? 'pink' : 'slate';
        
        return `
            <div class="bg-white border border-slate-200 rounded-xl overflow-hidden hover:shadow-lg transition-shadow">
                <div class="h-32 bg-gradient-to-br from-${color}-500 to-${color}-600 flex items-center justify-center">
                    <i data-lucide="${icon}" class="w-12 h-12 text-white"></i>
                </div>
                
                <div class="p-6">
                    <h3 class="text-lg font-bold text-slate-800 mb-2 line-clamp-1">${material.title}</h3>
                    <p class="text-sm text-slate-600 mb-4 line-clamp-2">${material.description || 'لا يوجد وصف'}</p>
                    
                    <div class="flex items-center gap-4 mb-4 pb-4 border-b border-slate-200">
                        <div class="flex items-center gap-2 text-sm text-slate-600">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            <span>${material.course_name}</span>
                        </div>
                    </div>
                    
                    <div class="flex items-center justify-between text-sm text-slate-500 mb-4">
                        <div class="flex items-center gap-2">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span>${material.upload_date}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <i data-lucide="download" class="w-4 h-4"></i>
                            <span>${material.downloads || 0}</span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <a href="${material.file_url}" download 
                            class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors text-sm font-semibold text-center">
                            <i data-lucide="download" class="w-4 h-4 inline"></i>
                            تحميل
                        </a>
                        <button onclick="deleteMaterial(${material.id})" 
                            class="px-4 py-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors text-sm font-semibold">
                            <i data-lucide="trash-2" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        `;
    }).join('');
    
    lucide.createIcons();
}

function showUploadModal() {
    const modalHTML = `
        <form id="uploadMaterialForm" class="space-y-4">
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الدورة</label>
                <select id="uploadCourse" required class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                    <option value="">اختر الدورة</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">عنوان المادة</label>
                <input type="text" id="materialTitle" required 
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="أدخل عنوان المادة">
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الوصف</label>
                <textarea id="materialDescription" rows="3"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg"
                    placeholder="وصف المادة (اختياري)"></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-semibold text-slate-700 mb-2">الملف</label>
                <input type="file" id="materialFile" required accept=".pdf,.doc,.docx,.ppt,.pptx,.mp4,.avi,.jpg,.png"
                    class="w-full px-4 py-2 border border-slate-300 rounded-lg">
                <p class="text-sm text-slate-500 mt-2">الحد الأقصى: 50MB | الصيغ المدعومة: PDF, DOC, PPT, MP4, صور</p>
            </div>
        </form>
    `;
    
    DashboardIntegration.ui.showModal('رفع مادة جديدة', modalHTML, [
        {
            text: 'رفع',
            class: 'bg-emerald-600 text-white hover:bg-emerald-700',
            onclick: 'uploadMaterial()'
        },
        {
            text: 'إلغاء',
            class: 'bg-slate-200 text-slate-700 hover:bg-slate-300',
            onclick: 'this.closest(".fixed").remove()'
        }
    ]);
    
    // Load courses in modal
    TrainerFeatures.courses.getMyCourses().then(response => {
        if (response.success && response.data) {
            const select = document.getElementById('uploadCourse');
            response.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.id;
                option.textContent = course.course_name;
                select.appendChild(option);
            });
        }
    });
}

async function uploadMaterial() {
    const courseId = document.getElementById('uploadCourse').value;
    const title = document.getElementById('materialTitle').value;
    const description = document.getElementById('materialDescription').value;
    const file = document.getElementById('materialFile').files[0];
    
    if (!courseId || !title || !file) {
        DashboardIntegration.ui.showToast('الرجاء إكمال جميع الحقول المطلوبة', 'error');
        return;
    }
    
    const formData = new FormData();
    formData.append('title', title);
    formData.append('description', description);
    formData.append('file', file);
    
    const response = await TrainerFeatures.materials.uploadMaterial(courseId, formData);
    
    if (response.success) {
        DashboardIntegration.ui.showToast('تم رفع المادة بنجاح', 'success');
        document.querySelector('.fixed').remove();
        loadMaterials();
    } else {
        DashboardIntegration.ui.showToast('فشل رفع المادة', 'error');
    }
}

async function deleteMaterial(materialId) {
    if (!confirm('هل أنت متأكد من حذف هذه المادة؟')) return;
    
    const response = await TrainerFeatures.materials.deleteMaterial(materialId);
    
    if (response.success) {
        DashboardIntegration.ui.showToast('تم حذف المادة بنجاح', 'success');
        loadMaterials();
    } else {
        DashboardIntegration.ui.showToast('فشل حذف المادة', 'error');
    }
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

// Initialize - Wait for libraries
if (typeof TrainerFeatures !== 'undefined') {
    loadCourses();
    loadMaterials();
} else {
    setTimeout(() => {
        if (typeof TrainerFeatures !== 'undefined') {
            loadCourses();
            loadMaterials();
        }
    }, 1000);
}
</script>
