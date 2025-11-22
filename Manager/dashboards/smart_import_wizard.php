<!-- Step 1: Upload File -->
<div id="wizard-step-1" class="bg-white rounded-2xl shadow p-8 border border-slate-100">
    <h3 class="text-xl font-bold text-slate-800 mb-2">الخطوة 1: رفع الملف</h3>
    <p class="text-slate-600 mb-6">اختر ملف Excel (xlsx, xls) أو CSV لتحليله.</p>
    
    <form id="upload-form" enctype="multipart/form-data">
        <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center cursor-pointer hover:border-sky-500 hover:bg-sky-50 transition" id="drop-zone">
            <i data-lucide="upload-cloud" class="w-16 h-16 mx-auto text-slate-400 mb-4"></i>
            <p class="text-slate-700 font-medium mb-2">اسحب وأفلت الملف هنا، أو انقر للاختيار</p>
            <p class="text-sm text-slate-500">الحد الأقصى لحجم الملف: 20MB</p>
            <input type="file" name="data_file" id="file-input" class="hidden" accept=".xlsx,.xls,.csv">
        </div>
        <div id="file-preview" class="hidden mt-4 text-center">
            <p class="font-medium text-slate-700">الملف المختار: <span id="file-name"></span></p>
            <button type="button" id="remove-file" class="text-red-500 hover:underline text-sm">إزالة</button>
        </div>
        <div class="text-right mt-6">
            <button type="submit" id="analyze-button" class="bg-sky-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-sky-700 disabled:bg-slate-400 disabled:cursor-not-allowed" disabled>
                <span class="flex items-center justify-center gap-2">
                    <i data-lucide="wand-2" class="w-5 h-5"></i>
                    <span>تحليل الملف</span>
                </span>
            </button>
        </div>
    </form>
</div>

<!-- Step 2: Analyze & Confirm Schema -->
<div id="wizard-step-2" class="hidden bg-white rounded-2xl shadow p-8 border border-slate-100 mt-8">
    <div id="analysis-loader" class="text-center py-8">
        <i data-lucide="loader-2" class="w-12 h-12 mx-auto text-sky-600 animate-spin"></i>
        <p class="mt-4 text-slate-600 font-medium">جاري تحليل الملف باستخدام الذكاء الاصطناعي...</p>
        <p class="text-sm text-slate-500">قد تستغرق هذه العملية بضع لحظات.</p>
    </div>
    
    <div id="analysis-results" class="hidden">
        <h3 class="text-xl font-bold text-slate-800 mb-2">الخطوة 2: تأكيد بنية الجدول</h3>
        <p class="text-slate-600 mb-6">هذه هي البنية التي اقترحها النظام. يمكنك تعديلها قبل إنشاء الجدول.</p>
        
        <form id="schema-form">
            <input type="hidden" name="temp_file" id="temp-file-input">
            <div class="mb-6">
                <label for="table-name" class="block text-sm font-medium text-slate-700 mb-1">اسم الجدول المقترح</label>
                <input type="text" id="table-name" name="table_name" class="w-full border-slate-300 rounded-lg shadow-sm focus:border-sky-500 focus:ring-sky-500">
            </div>
            
            <div id="columns-editor">
                <!-- Column editor will be dynamically generated here -->
            </div>
            
            <div class="flex justify-between items-center mt-8">
                <button type="button" id="back-to-upload" class="text-slate-600 hover:underline">العودة</button>
                <button type="submit" id="create-table-button" class="bg-emerald-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-emerald-700">
                    <span class="flex items-center justify-center gap-2">
                        <i data-lucide="database-zap" class="w-5 h-5"></i>
                        <span>إنشاء الجدول واستيراد البيانات</span>
                    </span>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Step 3: Import Results -->
<div id="wizard-step-3" class="hidden bg-white rounded-2xl shadow p-8 border border-slate-100 mt-8">
     <div id="import-loader" class="text-center py-8">
        <i data-lucide="loader-2" class="w-12 h-12 mx-auto text-emerald-600 animate-spin"></i>
        <p class="mt-4 text-slate-600 font-medium">جاري إنشاء الجدول واستيراد البيانات...</p>
    </div>
    <div id="import-results" class="hidden">
        <!-- Import results will be shown here -->
    </div>
</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('upload-form')) {
        const dropZone = document.getElementById('drop-zone');
        const fileInput = document.getElementById('file-input');
        const analyzeButton = document.getElementById('analyze-button');
        const filePreview = document.getElementById('file-preview');
        const fileNameSpan = document.getElementById('file-name');
        const removeFileBtn = document.getElementById('remove-file');
        const uploadForm = document.getElementById('upload-form');

        const wizardStep1 = document.getElementById('wizard-step-1');
        const wizardStep2 = document.getElementById('wizard-step-2');
        const analysisLoader = document.getElementById('analysis-loader');
        const analysisResults = document.getElementById('analysis-results');
        const backToUploadBtn = document.getElementById('back-to-upload');

        // File Upload Logic
        dropZone.addEventListener('click', () => fileInput.click());
        dropZone.addEventListener('dragover', (e) => {
            e.preventDefault();
            dropZone.classList.add('border-sky-500', 'bg-sky-50');
        });
        dropZone.addEventListener('dragleave', () => {
            dropZone.classList.remove('border-sky-500', 'bg-sky-50');
        });
        dropZone.addEventListener('drop', (e) => {
            e.preventDefault();
            dropZone.classList.remove('border-sky-500', 'bg-sky-50');
            if (e.dataTransfer.files.length) {
                fileInput.files = e.dataTransfer.files;
                handleFile(fileInput.files[0]);
            }
        });
        fileInput.addEventListener('change', () => {
            if (fileInput.files.length) {
                handleFile(fileInput.files[0]);
            }
        });
        removeFileBtn.addEventListener('click', () => {
            fileInput.value = '';
            filePreview.classList.add('hidden');
            analyzeButton.disabled = true;
        });

        function handleFile(file) {
            fileNameSpan.textContent = file.name;
            filePreview.classList.remove('hidden');
            analyzeButton.disabled = false;
        }

        // Form Submission for Analysis
        uploadForm.addEventListener('submit', (e) => {
            e.preventDefault();
            wizardStep1.classList.add('hidden');
            wizardStep2.classList.remove('hidden');
            analysisLoader.classList.remove('hidden');
            analysisResults.classList.add('hidden');

            const formData = new FormData();
            formData.append('data_file', fileInput.files[0]);

            fetch('<?php echo $managerBaseUrl; ?>/api/ai_analyze_file.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                analysisLoader.classList.add('hidden');
                analysisResults.classList.remove('hidden');
                if (data.success) {
                    displaySchemaEditor(data.schema, data.temp_file);
                } else {
                    showToast(data.message || 'حدث خطأ أثناء تحليل الملف.', 'error');
                    // Show error in the results div
                    analysisResults.innerHTML = `<p class="text-red-500">${data.message}</p>`;
                }
            })
            .catch(error => {
                console.error('Analysis Error:', error);
                analysisLoader.classList.add('hidden');
                analysisResults.classList.remove('hidden');
                analysisResults.innerHTML = '<p class="text-red-500">حدث خطأ فادح في الشبكة.</p>';
                showToast('حدث خطأ فادح في الشبكة.', 'error');
            });
        });
        
        backToUploadBtn.addEventListener('click', () => {
            wizardStep1.classList.remove('hidden');
            wizardStep2.classList.add('hidden');
            fileInput.value = '';
            filePreview.classList.add('hidden');
            analyzeButton.disabled = true;
        });

        function displaySchemaEditor(schema, tempFile) {
            document.getElementById('temp-file-input').value = tempFile;
            const tableNameInput = document.getElementById('table-name');
            const columnsEditor = document.getElementById('columns-editor');

            tableNameInput.value = schema.table_name;
            
            let columnsHtml = `
                <div class="grid grid-cols-12 gap-4 text-sm font-semibold text-slate-600 px-4 mb-2">
                    <div class="col-span-4">اسم العمود</div>
                    <div class="col-span-4">نوع البيانات</div>
                    <div class="col-span-2">مفتاح أساسي؟</div>
                    <div class="col-span-2">nullable?</div>
                </div>
            `;
            
            schema.columns.forEach((col, index) => {
                columnsHtml += `
                    <div class="grid grid-cols-12 gap-4 items-center bg-slate-50 p-4 rounded-lg mb-2">
                        <div class="col-span-4">
                            <input type="text" name="columns[${index}][name]" value="${col.name}" class="w-full border-slate-300 rounded-md shadow-sm text-sm">
                        </div>
                        <div class="col-span-4">
                            <select name="columns[${index}][type]" class="w-full border-slate-300 rounded-md shadow-sm text-sm">
                                ${getSqlTypeOptions(col.type)}
                            </select>
                        </div>
                        <div class="col-span-2 text-center">
                            <input type="checkbox" name="columns[${index}][is_primary]" ${col.is_primary ? 'checked' : ''} class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        </div>
                        <div class="col-span-2 text-center">
                            <input type="checkbox" name="columns[${index}][is_nullable]" ${col.is_nullable ? 'checked' : ''} class="h-5 w-5 rounded border-slate-300 text-sky-600 focus:ring-sky-500">
                        </div>
                    </div>
                `;
            });
            columnsEditor.innerHTML = columnsHtml;
        }

        function getSqlTypeOptions(selectedType) {
            const types = ['VARCHAR(255)', 'TEXT', 'INT', 'FLOAT', 'DATE', 'DATETIME', 'BOOLEAN'];
            return types.map(type => 
                `<option value="${type}" ${type === selectedType ? 'selected' : ''}>${type}</option>`
            ).join('');
        }

        // Step 3: Handle Schema Form Submission
        const schemaForm = document.getElementById('schema-form');
        schemaForm.addEventListener('submit', (e) => {
            e.preventDefault();
            
            const wizardStep2 = document.getElementById('wizard-step-2');
            const wizardStep3 = document.getElementById('wizard-step-3');
            const importLoader = document.getElementById('import-loader');
            const importResults = document.getElementById('import-results');

            wizardStep2.classList.add('hidden');
            wizardStep3.classList.remove('hidden');
            importLoader.classList.remove('hidden');
            importResults.classList.add('hidden');

            const formData = new FormData(schemaForm);

            fetch('<?php echo $managerBaseUrl; ?>/api/ai_create_and_import.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                importLoader.classList.add('hidden');
                importResults.classList.remove('hidden');
                if (data.success) {
                    importResults.innerHTML = `
                        <div class="text-center">
                            <i data-lucide="check-circle-2" class="w-16 h-16 mx-auto text-emerald-500 mb-4"></i>
                            <h3 class="text-xl font-bold mb-2">اكتمل الاستيراد بنجاح!</h3>
                            <p class="text-slate-600">${data.message}</p>
                            <div class="mt-6 flex justify-center gap-4">
                                <button onclick="navigateTo('smart-import-wizard')" class="bg-sky-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-sky-700">
                                    استيراد ملف آخر
                                </button>
                                <button onclick="navigateTo('generic-crud', { table: data.table_name })" class="bg-slate-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-slate-700">
                                    إدارة الجدول الجديد
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    importResults.innerHTML = `
                        <div class="text-center">
                            <i data-lucide="alert-triangle" class="w-16 h-16 mx-auto text-red-500 mb-4"></i>
                            <h3 class="text-xl font-bold mb-2">فشل الاستيراد</h3>
                            <p class="text-slate-600 bg-red-50 p-4 rounded-lg">${data.message || 'حدث خطأ غير متوقع.'}</p>
                             <div class="mt-6">
                                <button id="try-again-btn" class="bg-sky-600 text-white px-6 py-2 rounded-lg font-semibold hover:bg-sky-700">
                                    المحاولة مرة أخرى
                                </button>
                            </div>
                        </div>
                    `;
                    document.getElementById('try-again-btn').addEventListener('click', () => {
                        wizardStep3.classList.add('hidden');
                        wizardStep2.classList.remove('hidden');
                    });
                }
                lucide.createIcons();
            })
            .catch(error => {
                console.error('Import Error:', error);
                importLoader.classList.add('hidden');
                importResults.classList.remove('hidden');
                importResults.innerHTML = '<p class="text-red-500">حدث خطأ فادح في الشبكة.</p>';
                showToast('حدث خطأ فادح في الشبكة.', 'error');
            });
        });
    }
});
</script>
