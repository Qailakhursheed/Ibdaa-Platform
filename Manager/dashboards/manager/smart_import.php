<?php
/**
 * Manager Dashboard - Smart Import System (AI-Powered)
 * نظام الاستيراد الذكي بالذكاء الاصطناعي
 * Features: Excel/CSV Import, Auto-mapping, Data Validation, Conflict Resolution
 */

global $managerHelper;

// Handle import submission
$import_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['import_file'])) {
    $import_result = $managerHelper->processSmartImport($_FILES['import_file'], $_POST);
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="upload-cloud" class="w-10 h-10"></i>
                الاستيراد الذكي بالذكاء الاصطناعي
            </h1>
            <p class="text-purple-100 text-lg">استيراد بيانات الطلاب والمدربين والدورات بذكاء اصطناعي</p>
        </div>
        <div class="flex gap-3">
            <button onclick="downloadTemplate('students')" class="bg-white text-purple-600 px-6 py-3 rounded-xl font-bold hover:bg-purple-50 transition-all flex items-center gap-2">
                <i data-lucide="download" class="w-5 h-5"></i>
                تحميل نموذج
            </button>
        </div>
    </div>
</div>

<?php if ($import_result): ?>
<div class="bg-white rounded-xl shadow-lg p-8 mb-8 border-r-4 <?php echo $import_result['success'] ? 'border-emerald-500' : 'border-red-500'; ?>">
    <div class="flex items-center gap-4 mb-4">
        <i data-lucide="<?php echo $import_result['success'] ? 'check-circle' : 'alert-circle'; ?>" 
           class="w-8 h-8 <?php echo $import_result['success'] ? 'text-emerald-600' : 'text-red-600'; ?>"></i>
        <h3 class="text-xl font-bold <?php echo $import_result['success'] ? 'text-emerald-800' : 'text-red-800'; ?>">
            <?php echo $import_result['success'] ? 'تم الاستيراد بنجاح!' : 'فشل الاستيراد'; ?>
        </h3>
    </div>
    
    <?php if (!empty($import_result['stats'])): ?>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-4">
        <div class="bg-emerald-50 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-emerald-700"><?php echo $import_result['stats']['imported']; ?></div>
            <div class="text-sm text-emerald-600">تم استيرادها</div>
        </div>
        <div class="bg-amber-50 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-amber-700"><?php echo $import_result['stats']['updated']; ?></div>
            <div class="text-sm text-amber-600">تم تحديثها</div>
        </div>
        <div class="bg-red-50 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-red-700"><?php echo $import_result['stats']['failed']; ?></div>
            <div class="text-sm text-red-600">فشلت</div>
        </div>
        <div class="bg-blue-50 rounded-lg p-4 text-center">
            <div class="text-3xl font-bold text-blue-700"><?php echo $import_result['stats']['skipped']; ?></div>
            <div class="text-sm text-blue-600">تم تجاوزها</div>
        </div>
    </div>
    <?php endif; ?>
    
    <?php if (!empty($import_result['errors'])): ?>
    <div class="bg-red-50 rounded-lg p-4">
        <h4 class="font-bold text-red-800 mb-2">الأخطاء:</h4>
        <ul class="list-disc list-inside text-red-700 space-y-1">
            <?php foreach ($import_result['errors'] as $error): ?>
            <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- Import Types -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all cursor-pointer border-2 border-transparent hover:border-blue-500" onclick="selectImportType('students')">
        <div class="w-16 h-16 bg-gradient-to-br from-blue-500 to-indigo-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <i data-lucide="users" class="w-8 h-8 text-white"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-slate-800 mb-2">استيراد الطلاب</h3>
        <p class="text-center text-slate-600 text-sm">استيراد بيانات الطلاب من Excel أو CSV</p>
        <div class="mt-4 text-center">
            <span class="text-blue-600 font-semibold text-sm">الحقول المدعومة: 15 حقل</span>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all cursor-pointer border-2 border-transparent hover:border-emerald-500" onclick="selectImportType('trainers')">
        <div class="w-16 h-16 bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <i data-lucide="user-check" class="w-8 h-8 text-white"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-slate-800 mb-2">استيراد المدربين</h3>
        <p class="text-center text-slate-600 text-sm">استيراد بيانات المدربين مع التخصصات</p>
        <div class="mt-4 text-center">
            <span class="text-emerald-600 font-semibold text-sm">الحقول المدعومة: 12 حقل</span>
        </div>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-all cursor-pointer border-2 border-transparent hover:border-amber-500" onclick="selectImportType('courses')">
        <div class="w-16 h-16 bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl flex items-center justify-center mb-4 mx-auto">
            <i data-lucide="book-open" class="w-8 h-8 text-white"></i>
        </div>
        <h3 class="text-xl font-bold text-center text-slate-800 mb-2">استيراد الدورات</h3>
        <p class="text-center text-slate-600 text-sm">استيراد معلومات الدورات التدريبية</p>
        <div class="mt-4 text-center">
            <span class="text-amber-600 font-semibold text-sm">الحقول المدعومة: 10 حقول</span>
        </div>
    </div>
</div>

<!-- Import Form -->
<div id="importFormContainer" class="bg-white rounded-xl shadow-lg p-8 hidden">
    <form method="POST" enctype="multipart/form-data" id="importForm" class="space-y-6">
        <input type="hidden" name="import_type" id="importType" value="">
        
        <div>
            <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
                <i data-lucide="upload" class="w-6 h-6 text-purple-600"></i>
                <span id="importTypeLabel">رفع الملف</span>
            </h3>
        </div>
        
        <!-- File Upload Area -->
        <div class="border-2 border-dashed border-slate-300 rounded-xl p-12 text-center hover:border-purple-500 transition-colors" id="dropZone">
            <i data-lucide="upload-cloud" class="w-16 h-16 mx-auto mb-4 text-slate-400"></i>
            <h4 class="text-lg font-bold text-slate-800 mb-2">اسحب وأفلت الملف هنا</h4>
            <p class="text-slate-600 mb-4">أو انقر للاختيار من جهازك</p>
            <input type="file" name="import_file" id="importFile" accept=".xlsx,.xls,.csv" class="hidden" required onchange="handleFileSelect(this)">
            <button type="button" onclick="document.getElementById('importFile').click()" class="bg-purple-600 text-white px-6 py-3 rounded-lg font-bold hover:bg-purple-700 transition-all">
                اختيار ملف
            </button>
            <p class="text-sm text-slate-500 mt-4">الصيغ المدعومة: Excel (.xlsx, .xls), CSV (.csv)</p>
        </div>
        
        <div id="filePreview" class="hidden bg-gradient-to-r from-purple-50 to-indigo-50 rounded-xl p-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-12 h-12 bg-purple-100 rounded-lg flex items-center justify-center">
                        <i data-lucide="file-text" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div>
                        <h4 class="font-bold text-slate-800" id="fileName"></h4>
                        <p class="text-sm text-slate-600" id="fileSize"></p>
                    </div>
                </div>
                <button type="button" onclick="removeFile()" class="text-red-600 hover:bg-red-50 rounded-lg p-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                </button>
            </div>
        </div>
        
        <!-- Import Options -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="skip_duplicates" value="1" checked class="w-5 h-5 text-purple-600 rounded">
                    <span class="text-slate-700 font-semibold">تجاوز السجلات المكررة</span>
                </label>
                <p class="text-sm text-slate-500 mt-2 mr-8">عدم استيراد البيانات الموجودة مسبقاً</p>
            </div>
            
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="update_existing" value="1" class="w-5 h-5 text-purple-600 rounded">
                    <span class="text-slate-700 font-semibold">تحديث السجلات الموجودة</span>
                </label>
                <p class="text-sm text-slate-500 mt-2 mr-8">تحديث البيانات إذا كانت موجودة</p>
            </div>
            
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="validate_data" value="1" checked class="w-5 h-5 text-purple-600 rounded">
                    <span class="text-slate-700 font-semibold">التحقق من صحة البيانات</span>
                </label>
                <p class="text-sm text-slate-500 mt-2 mr-8">فحص البيانات قبل الاستيراد</p>
            </div>
            
            <div>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="send_notifications" value="1" class="w-5 h-5 text-purple-600 rounded">
                    <span class="text-slate-700 font-semibold">إرسال إشعارات</span>
                </label>
                <p class="text-sm text-slate-500 mt-2 mr-8">إبلاغ المستخدمين بعد الاستيراد</p>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="flex gap-4">
            <button type="submit" class="flex-1 bg-gradient-to-r from-purple-600 to-indigo-600 text-white px-8 py-4 rounded-xl font-bold hover:from-purple-700 hover:to-indigo-700 transition-all flex items-center justify-center gap-2 shadow-lg">
                <i data-lucide="upload" class="w-5 h-5"></i>
                بدء الاستيراد
            </button>
            <button type="button" onclick="hideImportForm()" class="px-8 py-4 bg-slate-100 text-slate-700 rounded-xl font-bold hover:bg-slate-200 transition-all">
                إلغاء
            </button>
        </div>
    </form>
</div>

<!-- Features Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
    <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-blue-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i data-lucide="brain" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">ذكاء اصطناعي متقدم</h3>
                <p class="text-slate-600 text-sm">التعرف التلقائي على الأعمدة وتحويل البيانات بذكاء</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-emerald-50 to-teal-50 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-emerald-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i data-lucide="shield-check" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">التحقق التلقائي</h3>
                <p class="text-slate-600 text-sm">فحص صحة البيانات واكتشاف الأخطاء تلقائياً</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-amber-50 to-orange-50 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-amber-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i data-lucide="git-merge" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">حل التعارضات الذكي</h3>
                <p class="text-slate-600 text-sm">معالجة السجلات المكررة تلقائياً</p>
            </div>
        </div>
    </div>
    
    <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-xl p-6">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-purple-500 rounded-lg flex items-center justify-center flex-shrink-0">
                <i data-lucide="zap" class="w-6 h-6 text-white"></i>
            </div>
            <div>
                <h3 class="text-lg font-bold text-slate-800 mb-2">استيراد دفعي سريع</h3>
                <p class="text-slate-600 text-sm">معالجة آلاف السجلات في ثوانٍ</p>
            </div>
        </div>
    </div>
</div>

<!-- Import History -->
<div class="bg-white rounded-xl shadow-lg p-8">
    <h3 class="text-xl font-bold text-slate-800 mb-6 flex items-center gap-2">
        <i data-lucide="history" class="w-6 h-6 text-purple-600"></i>
        سجل الاستيراد
    </h3>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50">
                <tr>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">التاريخ</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">النوع</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الملف</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">السجلات</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">الحالة</th>
                    <th class="px-6 py-4 text-right text-sm font-bold text-slate-700">المستخدم</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                <tr>
                    <td colspan="6" class="px-6 py-12 text-center text-slate-500">
                        <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                        <p>لا توجد عمليات استيراد سابقة</p>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
function selectImportType(type) {
    document.getElementById('importType').value = type;
    document.getElementById('importFormContainer').classList.remove('hidden');
    
    const labels = {
        'students': 'استيراد بيانات الطلاب',
        'trainers': 'استيراد بيانات المدربين',
        'courses': 'استيراد بيانات الدورات'
    };
    
    document.getElementById('importTypeLabel').textContent = labels[type];
    document.getElementById('importFormContainer').scrollIntoView({ behavior: 'smooth', block: 'start' });
}

function hideImportForm() {
    document.getElementById('importFormContainer').classList.add('hidden');
    document.getElementById('importForm').reset();
    document.getElementById('filePreview').classList.add('hidden');
}

function handleFileSelect(input) {
    if (input.files && input.files[0]) {
        const file = input.files[0];
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = formatFileSize(file.size);
        document.getElementById('filePreview').classList.remove('hidden');
    }
}

function removeFile() {
    document.getElementById('importFile').value = '';
    document.getElementById('filePreview').classList.add('hidden');
}

function formatFileSize(bytes) {
    if (bytes < 1024) return bytes + ' بايت';
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(2) + ' كيلوبايت';
    return (bytes / (1024 * 1024)).toFixed(2) + ' ميجابايت';
}

function downloadTemplate(type) {
    window.location.href = `../api/download_template.php?type=${type}`;
}

// Drag and drop
const dropZone = document.getElementById('dropZone');

dropZone.addEventListener('dragover', (e) => {
    e.preventDefault();
    dropZone.classList.add('border-purple-500', 'bg-purple-50');
});

dropZone.addEventListener('dragleave', () => {
    dropZone.classList.remove('border-purple-500', 'bg-purple-50');
});

dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('border-purple-500', 'bg-purple-50');
    
    const files = e.dataTransfer.files;
    if (files.length > 0) {
        document.getElementById('importFile').files = files;
        handleFileSelect(document.getElementById('importFile'));
    }
});

lucide.createIcons();
</script>
