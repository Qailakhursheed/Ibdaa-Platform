<?php
/**
 * AI-Powered Import Page
 * صفحة الاستيراد الذكي المعززة بالذكاء الاصطناعي
 */
?>
<div class="bg-white rounded-2xl shadow p-8">
    <div class="flex items-center gap-4 mb-6">
        <div class="p-3 rounded-full bg-gradient-to-br from-violet-500 to-sky-500 text-white shadow-lg">
            <i data-lucide="wand-2" class="w-8 h-8"></i>
        </div>
        <div>
            <h3 class="text-2xl font-bold text-slate-800">الاستيراد الشامل (تجريبي)</h3>
            <p class="text-slate-600">ارفع أي ملف (نص، CSV، JSON، أو حتى PDF) وسيقوم الذكاء الاصطناعي بتحليله وعرضه</p>
        </div>
    </div>

    <!-- File Upload Area -->
    <form id="aiImportForm" class="space-y-4">
        <div class="border-2 border-dashed border-slate-300 rounded-xl p-8 text-center hover:border-violet-500 transition cursor-pointer" id="aiDropzone">
            <i data-lucide="file-up" class="w-12 h-12 mx-auto text-slate-400 mb-4"></i>
            <p class="text-slate-600 mb-2">اسحب وأفلت أي ملف هنا، أو انقر للاختيار</p>
            <p id="aiFileName" class="text-sm text-slate-500 font-medium"></p>
            <input type="file" name="ai_import_file" id="ai_import_file_input" class="hidden">
        </div>
        <div class="flex gap-3 pt-2">
            <button type="submit" class="flex-1 px-6 py-3 bg-violet-600 text-white rounded-lg hover:bg-violet-700 disabled:bg-slate-400" disabled>
                <i data-lucide="sparkles" class="w-5 h-5 inline"></i>
                بدء التحليل بالذكاء الاصطناعي
            </button>
        </div>
    </form>

    <!-- AI Analysis Result Area -->
    <div id="aiImportResult" class="mt-8 hidden">
        <h4 class="text-xl font-bold text-slate-800 mb-4">نتائج التحليل:</h4>
        <div id="aiResultContainer" class="space-y-6">
            <!-- AI will populate this area -->
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('aiImportForm');
    const dropzone = document.getElementById('aiDropzone');
    const fileInput = document.getElementById('ai_import_file_input');
    const fileNameDisplay = document.getElementById('aiFileName');
    const submitButton = form.querySelector('button[type="submit"]');
    const resultContainer = document.getElementById('aiResultContainer');
    const resultWrapper = document.getElementById('aiImportResult');

    const handleFileSelect = (files) => {
        if (files.length > 0) {
            fileInput.files = files;
            fileNameDisplay.textContent = files[0].name;
            submitButton.disabled = false;
        }
    };

    dropzone.addEventListener('click', () => fileInput.click());
    fileInput.addEventListener('change', () => handleFileSelect(fileInput.files));

    dropzone.addEventListener('dragover', (e) => {
        e.preventDefault();
        dropzone.classList.add('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('dragleave', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
    });

    dropzone.addEventListener('drop', (e) => {
        e.preventDefault();
        dropzone.classList.remove('border-violet-500', 'bg-violet-50');
        handleFileSelect(e.dataTransfer.files);
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const formData = new FormData(form);
        if (!formData.get('ai_import_file')) {
            showToast('الرجاء اختيار ملف أولاً', 'error');
            return;
        }

        submitButton.innerHTML = '<i data-lucide="loader" class="w-5 h-5 inline animate-spin"></i> جاري التحليل...';
        submitButton.disabled = true;
        resultWrapper.classList.remove('hidden');
        resultContainer.innerHTML = `
            <div class="text-center py-8">
                <i data-lucide="loader" class="w-12 h-12 animate-spin mx-auto text-violet-600"></i>
                <p class="mt-4 text-slate-600">يقوم الذكاء الاصطناعي بتحليل الملف، قد يستغرق هذا بعض الوقت...</p>
            </div>
        `;
        lucide.createIcons();

        try {
            const response = await fetch('api/ai_import_processor.php', { method: 'POST', body: formData });
            const result = await response.json();

            if (result.success) {
                renderAiAnalysis(result.analysis);
                showToast('تم تحليل الملف بنجاح!', 'success');
            } else {
                resultContainer.innerHTML = `<p class="text-red-500">${result.message || 'فشل تحليل الملف'}</p>`;
                showToast(result.message || 'فشل تحليل الملف', 'error');
            }

        } catch (error) {
            console.error('AI Import error:', error);
            resultContainer.innerHTML = `<p class="text-red-500">حدث خطأ فادح أثناء التحليل.</p>`;
            showToast('حدث خطأ فادح أثناء التحليل', 'error');
        } finally {
            submitButton.innerHTML = '<i data-lucide="sparkles" class="w-5 h-5 inline"></i> بدء التحليل بالذكاء الاصطناعي';
            submitButton.disabled = false;
            lucide.createIcons();
        }
    });

    function renderAiAnalysis(analysis) {
        resultContainer.innerHTML = ''; // Clear loading spinner

        const summaryCard = `
            <div class="bg-slate-50 rounded-lg p-4 border border-slate-200">
                <h5 class="font-bold text-slate-800 mb-2">ملخص الملف</h5>
                <p class="text-sm text-slate-600"><strong>نوع الملف المكتشف:</strong> ${escapeHtml(analysis.file_type)}</p>
                <p class="text-sm text-slate-600"><strong>ملخص المحتوى:</strong> ${escapeHtml(analysis.summary)}</p>
            </div>
        `;
        resultContainer.insertAdjacentHTML('beforeend', summaryCard);

        analysis.content.forEach(item => {
            if (item.type === 'table') {
                const tableHtml = `
                    <div class="bg-white rounded-lg border border-slate-200">
                        <h5 class="font-bold text-slate-800 p-4 border-b">${escapeHtml(item.title)}</h5>
                        <div class="overflow-x-auto">
                            <table class="w-full">
                                <thead class="bg-slate-50">
                                    <tr>
                                        ${item.headers.map(h => `<th class="px-4 py-3 text-right text-sm font-semibold text-slate-700">${escapeHtml(h)}</th>`).join('')}
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200">
                                    ${item.rows.map(row => `
                                        <tr class="hover:bg-slate-50">
                                            ${row.map(cell => `<td class="px-4 py-3 text-sm">${escapeHtml(cell)}</td>`).join('')}
                                        </tr>
                                    `).join('')}
                                </tbody>
                            </table>
                        </div>
                    </div>
                `;
                resultContainer.insertAdjacentHTML('beforeend', tableHtml);
            } else if (item.type === 'key_value') {
                const kvHtml = `
                    <div class="bg-white rounded-lg border border-slate-200">
                        <h5 class="font-bold text-slate-800 p-4 border-b">${escapeHtml(item.title)}</h5>
                        <div class="p-4 space-y-2">
                            ${Object.entries(item.data).map(([key, value]) => `
                                <div class="flex justify-between text-sm">
                                    <strong class="text-slate-600">${escapeHtml(key)}:</strong>
                                    <span>${escapeHtml(value)}</span>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
                resultContainer.insertAdjacentHTML('beforeend', kvHtml);
            }
        });
        lucide.createIcons();
    }
});
</script>
