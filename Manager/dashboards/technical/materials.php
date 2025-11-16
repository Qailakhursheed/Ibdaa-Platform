<?php
/**
 * Technical Dashboard - Materials Management
 * إدارة المواد التدريبية
 */
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">المواد التدريبية</h1>
    <p class="text-slate-600">مراجعة والموافقة على المواد التدريبية</p>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <select id="filterCourse" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="">جميع الدورات</option>
        </select>
        <select id="filterType" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="">جميع الأنواع</option>
            <option value="pdf">PDF</option>
            <option value="video">فيديو</option>
            <option value="document">مستند</option>
        </select>
        <select id="filterStatus" class="px-4 py-2 border border-slate-300 rounded-lg">
            <option value="">جميع الحالات</option>
            <option value="pending">معلق</option>
            <option value="approved">موافق عليه</option>
            <option value="rejected">مرفوض</option>
        </select>
    </div>
</div>

<!-- Materials List -->
<div id="materialsContainer" class="space-y-4">
    <div class="text-center py-16">
        <i data-lucide="loader" class="w-12 h-12 mx-auto text-slate-400 mb-4 animate-spin"></i>
        <p class="text-slate-500">جاري تحميل المواد...</p>
    </div>
</div>

<script>
async function loadMaterials() {
    const container = document.getElementById('materialsContainer');
    
    try {
        const response = await TechnicalFeatures.materials.getAll();
        
        if (response.success && response.data && response.data.length > 0) {
            container.innerHTML = response.data.map(material => `
                <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                    <div class="flex items-start gap-4">
                        <div class="flex-shrink-0 w-16 h-16 rounded-lg bg-sky-100 flex items-center justify-center">
                            <i data-lucide="${material.type === 'video' ? 'video' : material.type === 'pdf' ? 'file-text' : 'file'}" 
                               class="w-8 h-8 text-sky-600"></i>
                        </div>
                        <div class="flex-1">
                            <h3 class="text-lg font-bold text-slate-800 mb-1">${material.title}</h3>
                            <p class="text-sm text-slate-600 mb-2">${material.course_title}</p>
                            <div class="flex items-center gap-4 text-sm text-slate-500">
                                <span>${material.trainer_name}</span>
                                <span>•</span>
                                <span>${new Date(material.uploaded_at).toLocaleDateString('ar-EG')}</span>
                                <span class="px-2 py-1 rounded-full text-xs font-bold
                                    ${material.status === 'approved' ? 'bg-emerald-100 text-emerald-700' :
                                      material.status === 'pending' ? 'bg-amber-100 text-amber-700' :
                                      'bg-red-100 text-red-700'}">
                                    ${material.status === 'approved' ? 'موافق عليه' : 
                                      material.status === 'pending' ? 'معلق' : 'مرفوض'}
                                </span>
                            </div>
                        </div>
                        <div class="flex gap-2">
                            <button onclick="viewMaterial('${material.file_url}')" 
                                class="p-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100">
                                <i data-lucide="eye" class="w-5 h-5"></i>
                            </button>
                            ${material.status === 'pending' ? `
                            <button onclick="approveMaterial(${material.material_id})" 
                                class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100">
                                <i data-lucide="check" class="w-5 h-5"></i>
                            </button>
                            <button onclick="rejectMaterial(${material.material_id})" 
                                class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100">
                                <i data-lucide="x" class="w-5 h-5"></i>
                            </button>
                            ` : ''}
                        </div>
                    </div>
                </div>
            `).join('');
        } else {
            container.innerHTML = '<div class="bg-white rounded-xl shadow-lg p-12 text-center"><p class="text-slate-500">لا توجد مواد تدريبية</p></div>';
        }
    } catch (error) {
        container.innerHTML = '<div class="bg-red-50 rounded-xl p-8 text-center"><p class="text-red-700">فشل تحميل المواد</p></div>';
    }
    
    lucide.createIcons();
}

function viewMaterial(url) {
    window.open(url, '_blank');
}

async function approveMaterial(materialId) {
    const response = await TechnicalFeatures.materials.approve(materialId);
    if (response.success) {
        DashboardIntegration.ui.showToast('تمت الموافقة على المادة', 'success');
        loadMaterials();
    } else {
        DashboardIntegration.ui.showToast('فشلت الموافقة', 'error');
    }
}

async function rejectMaterial(materialId) {
    const reason = prompt('سبب الرفض:');
    if (!reason) return;
    
    const response = await TechnicalFeatures.materials.reject(materialId, reason);
    if (response.success) {
        DashboardIntegration.ui.showToast('تم رفض المادة', 'success');
        loadMaterials();
    } else {
        DashboardIntegration.ui.showToast('فشل الرفض', 'error');
    }
}

document.addEventListener('DOMContentLoaded', loadMaterials);
</script>
