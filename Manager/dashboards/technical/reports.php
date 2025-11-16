<?php
/**
 * Technical Dashboard - Reports
 * التقارير
 */
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">التقارير</h1>
    <p class="text-slate-600">تقارير شاملة عن الأداء والإحصائيات</p>
</div>

<!-- Report Type Selection -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <button onclick="generateReport('courses')" 
        class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="book-open" class="w-12 h-12 mx-auto mb-4 text-sky-600"></i>
        <h3 class="text-lg font-bold text-slate-800 mb-2">تقرير الدورات</h3>
        <p class="text-sm text-slate-600">إحصائيات شاملة عن جميع الدورات</p>
    </button>
    
    <button onclick="generateReport('trainers')" 
        class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="users" class="w-12 h-12 mx-auto mb-4 text-emerald-600"></i>
        <h3 class="text-lg font-bold text-slate-800 mb-2">تقرير المدربين</h3>
        <p class="text-sm text-slate-600">أداء وتقييمات المدربين</p>
    </button>
    
    <button onclick="generateReport('quality')" 
        class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow text-center">
        <i data-lucide="award" class="w-12 h-12 mx-auto mb-4 text-amber-600"></i>
        <h3 class="text-lg font-bold text-slate-800 mb-2">تقرير الجودة</h3>
        <p class="text-sm text-slate-600">معايير الجودة والتحسينات</p>
    </button>
</div>

<!-- Report Display Area -->
<div id="reportContainer" class="bg-white rounded-xl shadow-lg p-8">
    <div class="text-center py-16">
        <i data-lucide="bar-chart" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
        <h3 class="text-xl font-bold text-slate-800 mb-2">اختر نوع التقرير</h3>
        <p class="text-slate-600">اضغط على أحد الأزرار أعلاه لإنشاء التقرير</p>
    </div>
</div>

<script>
async function generateReport(type) {
    const container = document.getElementById('reportContainer');
    container.innerHTML = '<div class="text-center py-16"><i data-lucide="loader" class="w-12 h-12 mx-auto text-sky-600 mb-4 animate-spin"></i><p class="text-slate-600">جاري إنشاء التقرير...</p></div>';
    lucide.createIcons();
    
    try {
        let response;
        const startDate = new Date();
        startDate.setMonth(startDate.getMonth() - 6);
        const endDate = new Date();
        
        if (type === 'courses') {
            response = await TechnicalFeatures.reports.getCoursesReport(
                startDate.toISOString().split('T')[0],
                endDate.toISOString().split('T')[0]
            );
        } else if (type === 'trainers') {
            response = await TechnicalFeatures.reports.getTrainersReport(
                startDate.toISOString().split('T')[0],
                endDate.toISOString().split('T')[0]
            );
        } else {
            response = { success: false, message: 'نوع التقرير غير مدعوم' };
        }
        
        if (response.success) {
            container.innerHTML = `
                <div class="mb-6 flex items-center justify-between">
                    <h3 class="text-2xl font-bold text-slate-800">
                        ${type === 'courses' ? 'تقرير الدورات' : type === 'trainers' ? 'تقرير المدربين' : 'تقرير الجودة'}
                    </h3>
                    <button onclick="exportToPDF('${type}')" 
                        class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 flex items-center gap-2">
                        <i data-lucide="download" class="w-5 h-5"></i>
                        تصدير PDF
                    </button>
                </div>
                <div class="space-y-6">
                    <div class="bg-slate-50 rounded-lg p-6">
                        <h4 class="font-bold text-slate-800 mb-4">الإحصائيات الرئيسية</h4>
                        <p class="text-slate-600">سيتم عرض البيانات التفصيلية هنا</p>
                    </div>
                </div>
            `;
        } else {
            throw new Error(response.message);
        }
    } catch (error) {
        container.innerHTML = `
            <div class="text-center py-16">
                <i data-lucide="alert-circle" class="w-12 h-12 mx-auto text-red-500 mb-4"></i>
                <p class="text-red-700 font-semibold">فشل إنشاء التقرير</p>
            </div>
        `;
    }
    
    lucide.createIcons();
}

async function exportToPDF(type) {
    DashboardIntegration.ui.showToast('جاري تصدير التقرير...', 'info');
    const response = await TechnicalFeatures.reports.generatePDF(type, {});
    if (response.success) {
        DashboardIntegration.ui.showToast('تم تصدير التقرير بنجاح', 'success');
    } else {
        DashboardIntegration.ui.showToast('فشل تصدير التقرير', 'error');
    }
}

lucide.createIcons();
</script>
