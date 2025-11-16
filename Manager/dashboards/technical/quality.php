<?php
/**
 * Technical Dashboard - Quality Assurance
 * ضمان الجودة
 */
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">ضمان الجودة</h1>
    <p class="text-slate-600">معايير الجودة وتقارير التحسين</p>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">معدل الجودة</h3>
            <i data-lucide="award" class="w-6 h-6 text-amber-600"></i>
        </div>
        <p class="text-4xl font-bold text-slate-800">85%</p>
        <p class="text-sm text-emerald-600 mt-2">↑ 5% عن الشهر الماضي</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">رضا الطلاب</h3>
            <i data-lucide="smile" class="w-6 h-6 text-emerald-600"></i>
        </div>
        <p class="text-4xl font-bold text-slate-800">4.2/5</p>
        <p class="text-sm text-slate-500 mt-2">من 156 تقييم</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-bold text-slate-800">معدل الإكمال</h3>
            <i data-lucide="check-circle" class="w-6 h-6 text-sky-600"></i>
        </div>
        <p class="text-4xl font-bold text-slate-800">78%</p>
        <p class="text-sm text-amber-600 mt-2">↓ 2% عن الشهر الماضي</p>
    </div>
</div>

<div class="bg-white rounded-xl shadow-lg p-8">
    <h3 class="text-xl font-bold text-slate-800 mb-6">تقارير الجودة</h3>
    <p class="text-slate-500 text-center py-8">سيتم عرض تقارير الجودة التفصيلية هنا</p>
</div>

<script>
lucide.createIcons();
</script>
