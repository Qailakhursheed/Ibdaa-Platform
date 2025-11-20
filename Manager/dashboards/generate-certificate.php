<?php
/**
 * Generate Certificate Page
 * صفحة إنشاء الشهادات
 */
?>
<div class="bg-white rounded-2xl shadow p-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 rounded-full bg-gradient-to-br from-green-500 to-teal-500 text-white shadow-lg">
            <i data-lucide="file-text" class="w-8 h-8"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800">إنشاء شهادات</h3>
            <p class="text-slate-600">إنشاء شهادات للطلاب بشكل فردي أو جماعي</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Controls -->
        <div class="lg:col-span-1 space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر الدورة</label>
                <select id="courseSelector" class="w-full px-4 py-2 border border-slate-300 rounded-lg"></select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر الطالب</label>
                <select id="studentSelector" class="w-full px-4 py-2 border border-slate-300 rounded-lg"></select>
            </div>
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر قالب الشهادة</label>
                <select id="templateSelector" class="w-full px-4 py-2 border border-slate-300 rounded-lg"></select>
            </div>
            <div class="flex gap-3 pt-4">
                <button id="generateBtn" class="flex-1 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">إنشاء ومعاينة</button>
            </div>
        </div>

        <!-- Certificate Preview -->
        <div class="lg:col-span-2">
            <div id="certificatePreview" class="w-full aspect-[297/210] border-2 border-slate-300 rounded-lg p-8 bg-white" style="font-family: 'Cairo', sans-serif;">
                <!-- Certificate will be previewed here -->
            </div>
        </div>
    </div>
</div>

<script src="dashboards/js/generate-certificate.js"></script>
