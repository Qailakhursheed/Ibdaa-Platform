<link rel="stylesheet" href="dashboards/css/certificate-designer.css">

<div class="bg-white rounded-2xl shadow p-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 rounded-full bg-gradient-to-br from-amber-500 to-orange-500 text-white shadow-lg">
            <i data-lucide="award" class="w-8 h-8"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800">مصمم الشهادات</h3>
            <p class="text-slate-600">تصميم وتخصيص قوالب الشهادات بسهولة</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
        <!-- Controls -->
        <div class="lg:col-span-1 space-y-6">
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">اختر قالب محفوظ</label>
                <select id="templateSelector" class="w-full px-4 py-2 border border-slate-300 rounded-lg"></select>
            </div>
            <div>
                <h4 class="font-bold text-slate-800 mb-2">عناصر التصميم</h4>
                <div class="space-y-2">
                    <button id="addTextBtn" class="w-full text-left bg-slate-100 text-slate-700 px-3 py-2 rounded text-sm hover:bg-slate-200">إضافة نص</button>
                    <label class="w-full text-left bg-slate-100 text-slate-700 px-3 py-2 rounded text-sm hover:bg-slate-200 cursor-pointer">
                        <span>إضافة صورة</span>
                        <input type="file" id="addImageBtn" accept="image/*" class="hidden">
                    </label>
                </div>
            </div>
            
            <div id="propertiesPanel" class="bg-slate-50 p-4 rounded-lg space-y-4 transform translate-x-full lg:translate-x-0">
                <h4 class="font-bold text-slate-800">خصائص العنصر</h4>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-slate-700">حجم الخط</label>
                        <input type="number" id="fontSize" min="8" max="120" class="w-full mt-1 p-2 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">لون الخط</label>
                        <input type="color" id="fontColor" class="w-full h-10 mt-1 p-1 border rounded">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-slate-700">عائلة الخط</label>
                        <select id="fontFamily" class="w-full mt-1 p-2 border rounded">
                            <option value="Cairo, sans-serif">Cairo</option>
                            <option value="Arial, sans-serif">Arial</option>
                            <option value="Times New Roman, serif">Times New Roman</option>
                            <option value="Courier New, monospace">Courier New</option>
                        </select>
                    </div>
                </div>
            </div>

            <div class="flex gap-3 pt-4">
                <button id="saveTemplateBtn" class="flex-1 px-4 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700">حفظ كقالب جديد</button>
            </div>
        </div>

        <!-- Certificate Preview -->
        <div class="lg:col-span-3">
            <div id="certificatePreview" class="w-full aspect-[297/210] border-2 border-slate-300 rounded-lg p-8 bg-white" style="font-family: 'Cairo', sans-serif;">
                <!-- Draggable elements will be loaded here -->
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/interactjs/dist/interact.min.js"></script>
<script src="dashboards/js/certificate-designer.js"></script>