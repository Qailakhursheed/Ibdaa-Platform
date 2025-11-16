<!-- Import Panel Component - AI Enhanced -->
<?php 
// Include AI Libraries (if not already included)
if (!defined('AI_LIBRARIES_LOADED')) {
    include __DIR__ . '/../includes/ai-libraries.php';
    define('AI_LIBRARIES_LOADED', true);
}
?>

<style>
/* Import Panel Styles */
.import-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    align-items: center;
    justify-content: center;
    backdrop-filter: blur(4px);
}

.import-modal.active {
    display: flex;
}

.import-container {
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.2);
    width: 90%;
    max-width: 900px;
    max-height: 90vh;
    overflow-y: auto;
    animation: slideIn 0.3s ease;
}

@keyframes slideIn {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.import-header {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 24px;
    border-radius: 12px 12px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.import-header h3 {
    margin: 0;
    font-size: 24px;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 12px;
}

.import-close {
    background: rgba(255, 255, 255, 0.2);
    border: none;
    color: white;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.import-close:hover {
    background: rgba(255, 255, 255, 0.3);
    transform: rotate(90deg);
}

.import-body {
    padding: 32px;
}

.import-steps {
    display: flex;
    gap: 16px;
    margin-bottom: 32px;
    justify-content: center;
}

.import-step {
    flex: 1;
    text-align: center;
    padding: 16px;
    background: #f8f9fa;
    border-radius: 8px;
    border: 2px solid transparent;
    transition: all 0.3s;
}

.import-step.active {
    border-color: #f5576c;
    background: #fff;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.1);
}

.import-step.completed {
    background: #e8f5e9;
    border-color: #4caf50;
}

.import-step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: #dee2e6;
    color: #6c757d;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 8px;
    font-weight: bold;
    transition: all 0.3s;
}

.import-step.active .import-step-number {
    background: #f5576c;
    color: white;
}

.import-step.completed .import-step-number {
    background: #4caf50;
    color: white;
}

.import-step-title {
    font-weight: 600;
    color: #495057;
    font-size: 14px;
}

.import-content-step {
    display: none;
}

.import-content-step.active {
    display: block;
    animation: fadeIn 0.3s ease;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Step 1: Type Selection */
.import-type-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 16px;
    margin-bottom: 24px;
}

.import-type-card {
    background: white;
    border: 2px solid #e9ecef;
    border-radius: 12px;
    padding: 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
}

.import-type-card:hover {
    border-color: #f5576c;
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.1);
}

.import-type-card.selected {
    border-color: #f5576c;
    background: #fff5f7;
}

.import-type-icon {
    width: 60px;
    height: 60px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
}

.import-type-title {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.import-type-desc {
    color: #6c757d;
    font-size: 13px;
}

/* Step 2: File Upload */
.dropzone {
    border: 3px dashed #dee2e6;
    border-radius: 12px;
    padding: 48px 24px;
    text-align: center;
    cursor: pointer;
    transition: all 0.3s;
    background: #f8f9fa;
}

.dropzone:hover,
.dropzone.dragover {
    border-color: #f5576c;
    background: #fff5f7;
}

.dropzone-icon {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 16px;
}

.dropzone-text {
    color: #495057;
    font-size: 16px;
    font-weight: 600;
    margin-bottom: 8px;
}

.dropzone-subtext {
    color: #6c757d;
    font-size: 14px;
}

.file-input {
    display: none;
}

.file-info {
    display: none;
    background: #e8f5e9;
    border: 2px solid #4caf50;
    border-radius: 8px;
    padding: 16px;
    margin-top: 16px;
}

.file-info.active {
    display: flex;
    align-items: center;
    gap: 12px;
}

.file-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    background: #4caf50;
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.file-details {
    flex: 1;
}

.file-name {
    font-weight: 600;
    color: #212529;
    margin-bottom: 4px;
}

.file-size {
    color: #6c757d;
    font-size: 13px;
}

.file-remove {
    background: #dc3545;
    border: none;
    color: white;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    justify-content: center;
}

.file-remove:hover {
    background: #c82333;
}

/* Step 3: Column Mapping */
.mapping-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 24px;
    max-height: 400px;
    overflow-y: auto;
}

.mapping-row {
    background: white;
    border-radius: 8px;
    padding: 16px;
    margin-bottom: 12px;
    display: flex;
    align-items: center;
    gap: 16px;
}

.mapping-source {
    flex: 1;
    font-weight: 600;
    color: #495057;
}

.mapping-arrow {
    color: #f5576c;
}

.mapping-target {
    flex: 1;
}

.mapping-target select {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #ced4da;
    border-radius: 6px;
    font-size: 14px;
}

/* Step 4: Progress */
.progress-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 24px;
}

.progress-bar-wrapper {
    background: #e9ecef;
    border-radius: 8px;
    height: 40px;
    overflow: hidden;
    margin-bottom: 16px;
}

.progress-bar {
    background: linear-gradient(90deg, #f093fb 0%, #f5576c 100%);
    height: 100%;
    width: 0%;
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}

.progress-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 16px;
    margin-bottom: 16px;
}

.progress-stat {
    background: white;
    border-radius: 8px;
    padding: 16px;
    text-align: center;
}

.progress-stat-value {
    font-size: 32px;
    font-weight: bold;
    margin-bottom: 4px;
}

.progress-stat-value.success {
    color: #4caf50;
}

.progress-stat-value.error {
    color: #f44336;
}

.progress-stat-value.total {
    color: #2196f3;
}

.progress-stat-label {
    color: #6c757d;
    font-size: 14px;
}

.progress-errors {
    background: #ffebee;
    border: 2px solid #f44336;
    border-radius: 8px;
    padding: 16px;
    max-height: 200px;
    overflow-y: auto;
    display: none;
}

.progress-errors.active {
    display: block;
}

.progress-error-item {
    padding: 8px;
    border-bottom: 1px solid #ffcdd2;
    color: #c62828;
    font-size: 13px;
}

.progress-error-item:last-child {
    border-bottom: none;
}

/* Buttons */
.import-actions {
    display: flex;
    gap: 12px;
    justify-content: flex-end;
    margin-top: 24px;
    padding-top: 24px;
    border-top: 1px solid #e9ecef;
}

.btn-import {
    padding: 12px 24px;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s;
    display: flex;
    align-items: center;
    gap: 8px;
}

.btn-import-primary {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.btn-import-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(245, 87, 108, 0.3);
}

.btn-import-secondary {
    background: #6c757d;
    color: white;
}

.btn-import-secondary:hover {
    background: #5a6268;
}

.btn-import:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none !important;
}

/* Loading Spinner */
.loading-spinner {
    display: inline-block;
    width: 16px;
    height: 16px;
    border: 2px solid rgba(255, 255, 255, 0.3);
    border-top-color: white;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

/* Responsive */
@media (max-width: 768px) {
    .import-container {
        width: 95%;
    }
    
    .import-steps {
        flex-direction: column;
    }
    
    .import-type-grid {
        grid-template-columns: 1fr;
    }
    
    .progress-stats {
        grid-template-columns: 1fr;
    }
}
</style>

<!-- Import Modal -->
<div class="import-modal" id="importModal">
    <div class="import-container">
        <!-- Header -->
        <div class="import-header">
            <h3>
                <i data-lucide="upload" style="width: 28px; height: 28px;"></i>
                استيراد البيانات
            </h3>
            <button class="import-close" id="closeImportModal">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>

        <!-- Body -->
        <div class="import-body">
            <!-- Steps Indicator -->
            <div class="import-steps">
                <div class="import-step active" data-step="1">
                    <div class="import-step-number">1</div>
                    <div class="import-step-title">اختر النوع</div>
                </div>
                <div class="import-step" data-step="2">
                    <div class="import-step-number">2</div>
                    <div class="import-step-title">رفع الملف</div>
                </div>
                <div class="import-step" data-step="3">
                    <div class="import-step-number">3</div>
                    <div class="import-step-title">ربط الأعمدة</div>
                </div>
                <div class="import-step" data-step="4">
                    <div class="import-step-number">4</div>
                    <div class="import-step-title">الاستيراد</div>
                </div>
            </div>

            <!-- Step 1: Type Selection -->
            <div class="import-content-step active" id="step1">
                <div class="import-type-grid">
                    <div class="import-type-card" data-type="students">
                        <div class="import-type-icon">
                            <i data-lucide="users" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div class="import-type-title">الطلاب</div>
                        <div class="import-type-desc">استيراد بيانات الطلاب</div>
                    </div>
                    <div class="import-type-card" data-type="trainers">
                        <div class="import-type-icon">
                            <i data-lucide="user-check" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div class="import-type-title">المدربين</div>
                        <div class="import-type-desc">استيراد بيانات المدربين</div>
                    </div>
                    <div class="import-type-card" data-type="courses">
                        <div class="import-type-icon">
                            <i data-lucide="book-open" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div class="import-type-title">الدورات</div>
                        <div class="import-type-desc">استيراد بيانات الدورات</div>
                    </div>
                    <div class="import-type-card" data-type="grades">
                        <div class="import-type-icon">
                            <i data-lucide="award" style="width: 28px; height: 28px;"></i>
                        </div>
                        <div class="import-type-title">الدرجات</div>
                        <div class="import-type-desc">استيراد درجات الطلاب</div>
                    </div>
                </div>
            </div>

            <!-- Step 2: File Upload -->
            <div class="import-content-step" id="step2">
                <div class="dropzone" id="dropzone">
                    <div class="dropzone-icon">
                        <i data-lucide="cloud-upload" style="width: 36px; height: 36px;"></i>
                    </div>
                    <div class="dropzone-text">اسحب الملف هنا أو انقر للتحميل</div>
                    <div class="dropzone-subtext">يدعم: Excel (.xlsx, .xls) أو CSV</div>
                </div>
                <input type="file" id="fileInput" class="file-input" accept=".xlsx,.xls,.csv">
                
                <div class="file-info" id="fileInfo">
                    <div class="file-icon">
                        <i data-lucide="file-text" style="width: 24px; height: 24px;"></i>
                    </div>
                    <div class="file-details">
                        <div class="file-name" id="fileName">اسم الملف</div>
                        <div class="file-size" id="fileSize">0 KB</div>
                    </div>
                    <button class="file-remove" id="fileRemove">
                        <i data-lucide="x" style="width: 16px; height: 16px;"></i>
                    </button>
                </div>
            </div>

            <!-- Step 3: Column Mapping -->
            <div class="import-content-step" id="step3">
                <div class="mapping-container" id="mappingContainer">
                    <!-- سيتم ملؤها بواسطة JavaScript -->
                </div>
            </div>

            <!-- Step 4: Progress -->
            <div class="import-content-step" id="step4">
                <div class="progress-container">
                    <div class="progress-bar-wrapper">
                        <div class="progress-bar" id="progressBar">0%</div>
                    </div>
                    
                    <div class="progress-stats">
                        <div class="progress-stat">
                            <div class="progress-stat-value success" id="successCount">0</div>
                            <div class="progress-stat-label">نجح</div>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-value error" id="errorCount">0</div>
                            <div class="progress-stat-label">فشل</div>
                        </div>
                        <div class="progress-stat">
                            <div class="progress-stat-value total" id="totalCount">0</div>
                            <div class="progress-stat-label">الإجمالي</div>
                        </div>
                    </div>
                    
                    <div class="progress-errors" id="progressErrors">
                        <!-- الأخطاء ستظهر هنا -->
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="import-actions">
                <button class="btn-import btn-import-secondary" id="prevStepBtn" style="display: none;">
                    <i data-lucide="arrow-right" style="width: 16px; height: 16px;"></i>
                    السابق
                </button>
                <button class="btn-import btn-import-primary" id="nextStepBtn">
                    التالي
                    <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Load AI-Powered Import System -->
<script src="JS/ai_import.js"></script>

<script>
// Initialize Lucide icons after loading
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}

// Initialize AI Import System
window.addEventListener('ai-libraries-ready', async () => {
    console.log('🤖 AI Libraries loaded, initializing import system...');
    
    try {
        // Create AI import system instance
        window.importSystem = new AdvancedAIImportSystem();
        await window.importSystem.init();
        
        console.log('✅ AI Import System ready!');
        
        // Setup file input handler
        const fileInput = document.getElementById('importFileInput');
        if (fileInput) {
            fileInput.addEventListener('change', async (e) => {
                const file = e.target.files[0];
                if (file) {
                    console.log('📁 File selected:', file.name);
                    await window.importSystem.handleFileSelect(file);
                }
            });
        }
    } catch (error) {
        console.error('❌ Failed to initialize AI import:', error);
    }
});
</script>
