/**
 * Import System - Complete JavaScript
 * Multi-step import with drag&drop, column mapping, progress tracking
 */

class ImportSystem {
    constructor() {
        this.currentStep = 1;
        this.importType = null;
        this.selectedFile = null;
        this.fileHeaders = [];
        this.filePath = null;
        this.columnMapping = {};
        
        this.init();
    }

    init() {
        this.initElements();
        this.attachEventListeners();
    }

    initElements() {
        this.modal = document.getElementById('importModal');
        this.closeBtn = document.getElementById('closeImportModal');
        this.dropzone = document.getElementById('dropzone');
        this.fileInput = document.getElementById('fileInput');
        this.fileInfo = document.getElementById('fileInfo');
        this.fileRemove = document.getElementById('fileRemove');
        this.prevBtn = document.getElementById('prevStepBtn');
        this.nextBtn = document.getElementById('nextStepBtn');
        this.mappingContainer = document.getElementById('mappingContainer');
        this.progressBar = document.getElementById('progressBar');
        this.successCount = document.getElementById('successCount');
        this.errorCount = document.getElementById('errorCount');
        this.totalCount = document.getElementById('totalCount');
        this.progressErrors = document.getElementById('progressErrors');
        
        // Open button (should exist in dashboard)
        this.openBtn = document.getElementById('openImportModal');
    }

    attachEventListeners() {
        // Open modal
        if (this.openBtn) {
            this.openBtn.addEventListener('click', () => this.openModal());
        }
        
        // Close modal
        this.closeBtn?.addEventListener('click', () => this.closeModal());
        
        // Type selection
        document.querySelectorAll('.import-type-card').forEach(card => {
            card.addEventListener('click', (e) => {
                const type = e.currentTarget.dataset.type;
                this.selectImportType(type);
            });
        });
        
        // File upload
        this.dropzone?.addEventListener('click', () => this.fileInput?.click());
        this.dropzone?.addEventListener('dragover', (e) => {
            e.preventDefault();
            this.dropzone.classList.add('dragover');
        });
        this.dropzone?.addEventListener('dragleave', () => {
            this.dropzone.classList.remove('dragover');
        });
        this.dropzone?.addEventListener('drop', (e) => {
            e.preventDefault();
            this.dropzone.classList.remove('dragover');
            if (e.dataTransfer.files.length > 0) {
                this.handleFileSelect(e.dataTransfer.files[0]);
            }
        });
        
        this.fileInput?.addEventListener('change', (e) => {
            if (e.target.files.length > 0) {
                this.handleFileSelect(e.target.files[0]);
            }
        });
        
        this.fileRemove?.addEventListener('click', () => this.removeFile());
        
        // Navigation
        this.prevBtn?.addEventListener('click', () => this.prevStep());
        this.nextBtn?.addEventListener('click', () => this.nextStep());
    }

    openModal() {
        this.modal?.classList.add('active');
        this.reset();
    }

    closeModal() {
        this.modal?.classList.remove('active');
    }

    reset() {
        this.currentStep = 1;
        this.importType = null;
        this.selectedFile = null;
        this.fileHeaders = [];
        this.filePath = null;
        this.columnMapping = {};
        
        this.updateStepUI();
        this.updateButtons();
        
        // Reset type selection
        document.querySelectorAll('.import-type-card').forEach(card => {
            card.classList.remove('selected');
        });
        
        // Reset file
        this.fileInfo?.classList.remove('active');
        if (this.fileInput) this.fileInput.value = '';
    }

    selectImportType(type) {
        this.importType = type;
        
        // Update UI
        document.querySelectorAll('.import-type-card').forEach(card => {
            if (card.dataset.type === type) {
                card.classList.add('selected');
            } else {
                card.classList.remove('selected');
            }
        });
        
        this.updateButtons();
    }

    async handleFileSelect(file) {
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
            'application/vnd.ms-excel', // .xls
            'text/csv' // .csv
        ];
        
        if (!allowedTypes.includes(file.type) && !file.name.match(/\.(xlsx|xls|csv)$/i)) {
            alert('نوع الملف غير مدعوم. يرجى رفع ملف Excel أو CSV');
            return;
        }
        
        this.selectedFile = file;
        
        // Show file info
        document.getElementById('fileName').textContent = file.name;
        document.getElementById('fileSize').textContent = this.formatFileSize(file.size);
        this.fileInfo?.classList.add('active');
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
        
        // Read headers
        await this.readFileHeaders();
        
        this.updateButtons();
    }

    removeFile() {
        this.selectedFile = null;
        this.fileHeaders = [];
        this.filePath = null;
        this.fileInfo?.classList.remove('active');
        if (this.fileInput) this.fileInput.value = '';
        this.updateButtons();
    }

    async readFileHeaders() {
        try {
            const formData = new FormData();
            formData.append('excel_file', this.selectedFile);
            
            const response = await fetch('api/excel_read_headers.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                this.fileHeaders = data.headers || [];
                this.filePath = data.filePath;
                document.getElementById('totalCount').textContent = data.totalRows || 0;
            } else {
                alert('خطأ في قراءة الملف: ' + data.message);
                this.removeFile();
            }
            
        } catch (error) {
            console.error('Error reading file:', error);
            alert('حدث خطأ أثناء قراءة الملف');
            this.removeFile();
        }
    }

    async nextStep() {
        // Validate current step
        if (this.currentStep === 1 && !this.importType) {
            alert('يرجى اختيار نوع الاستيراد');
            return;
        }
        
        if (this.currentStep === 2 && !this.selectedFile) {
            alert('يرجى رفع ملف');
            return;
        }
        
        if (this.currentStep === 3) {
            // Start import
            await this.startImport();
            return;
        }
        
        if (this.currentStep < 4) {
            this.currentStep++;
            
            // Prepare step 3 (mapping)
            if (this.currentStep === 3) {
                this.buildColumnMapping();
            }
            
            this.updateStepUI();
            this.updateButtons();
        }
    }

    prevStep() {
        if (this.currentStep > 1 && this.currentStep < 4) {
            this.currentStep--;
            this.updateStepUI();
            this.updateButtons();
        }
    }

    updateStepUI() {
        // Update step indicators
        document.querySelectorAll('.import-step').forEach(step => {
            const stepNum = parseInt(step.dataset.step);
            step.classList.remove('active', 'completed');
            
            if (stepNum === this.currentStep) {
                step.classList.add('active');
            } else if (stepNum < this.currentStep) {
                step.classList.add('completed');
            }
        });
        
        // Update step content
        document.querySelectorAll('.import-content-step').forEach((content, index) => {
            content.classList.remove('active');
            if (index + 1 === this.currentStep) {
                content.classList.add('active');
            }
        });
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    updateButtons() {
        // Show/hide prev button
        if (this.currentStep > 1 && this.currentStep < 4) {
            this.prevBtn.style.display = 'flex';
        } else {
            this.prevBtn.style.display = 'none';
        }
        
        // Update next button text
        if (this.currentStep === 3) {
            this.nextBtn.textContent = 'بدء الاستيراد';
            this.nextBtn.innerHTML = '<i data-lucide="upload" style="width: 16px; height: 16px;"></i> بدء الاستيراد';
        } else if (this.currentStep === 4) {
            this.nextBtn.textContent = 'إغلاق';
            this.nextBtn.innerHTML = 'إغلاق';
            this.nextBtn.onclick = () => this.closeModal();
        } else {
            this.nextBtn.textContent = 'التالي';
            this.nextBtn.innerHTML = 'التالي <i data-lucide="arrow-left" style="width: 16px; height: 16px;"></i>';
            this.nextBtn.onclick = null;
        }
        
        // Disable next button if validation fails
        if (this.currentStep === 1 && !this.importType) {
            this.nextBtn.disabled = true;
        } else if (this.currentStep === 2 && !this.selectedFile) {
            this.nextBtn.disabled = true;
        } else {
            this.nextBtn.disabled = false;
        }
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    buildColumnMapping() {
        this.mappingContainer.innerHTML = '';
        
        // Get target columns based on import type
        const targetColumns = this.getTargetColumns();
        
        this.fileHeaders.forEach((header, index) => {
            const row = document.createElement('div');
            row.className = 'mapping-row';
            
            const source = document.createElement('div');
            source.className = 'mapping-source';
            source.textContent = header;
            
            const arrow = document.createElement('div');
            arrow.className = 'mapping-arrow';
            arrow.innerHTML = '<i data-lucide="arrow-left" style="width: 20px; height: 20px;"></i>';
            
            const target = document.createElement('div');
            target.className = 'mapping-target';
            
            const select = document.createElement('select');
            select.innerHTML = '<option value="">-- اختر العمود --</option>';
            
            targetColumns.forEach(col => {
                const option = document.createElement('option');
                option.value = col.value;
                option.textContent = col.label;
                
                // Auto-match if possible
                if (header.toLowerCase().includes(col.value.toLowerCase()) ||
                    col.label.toLowerCase().includes(header.toLowerCase())) {
                    option.selected = true;
                }
                
                select.appendChild(option);
            });
            
            select.addEventListener('change', (e) => {
                this.columnMapping[header] = e.target.value;
            });
            
            // Initialize mapping
            if (select.value) {
                this.columnMapping[header] = select.value;
            }
            
            target.appendChild(select);
            
            row.appendChild(source);
            row.appendChild(arrow);
            row.appendChild(target);
            
            this.mappingContainer.appendChild(row);
        });
        
        // Initialize Lucide icons
        if (typeof lucide !== 'undefined') {
            lucide.createIcons();
        }
    }

    getTargetColumns() {
        const columns = {
            students: [
                { value: 'name', label: 'الاسم' },
                { value: 'email', label: 'البريد الإلكتروني' },
                { value: 'phone', label: 'رقم الهاتف' },
                { value: 'course_id', label: 'رقم الدورة' },
                { value: 'course_name', label: 'اسم الدورة' },
                { value: 'gender', label: 'الجنس' },
                { value: 'age', label: 'العمر' }
            ],
            trainers: [
                { value: 'name', label: 'الاسم' },
                { value: 'email', label: 'البريد الإلكتروني' },
                { value: 'phone', label: 'رقم الهاتف' },
                { value: 'specialization', label: 'التخصص' }
            ],
            courses: [
                { value: 'course_name', label: 'اسم الدورة' },
                { value: 'description', label: 'الوصف' },
                { value: 'trainer_name', label: 'اسم المدرب' },
                { value: 'start_date', label: 'تاريخ البدء' },
                { value: 'end_date', label: 'تاريخ الانتهاء' },
                { value: 'price', label: 'السعر' }
            ],
            grades: [
                { value: 'student_name', label: 'اسم الطالب' },
                { value: 'student_email', label: 'بريد الطالب' },
                { value: 'course_name', label: 'اسم الدورة' },
                { value: 'grade', label: 'الدرجة' },
                { value: 'exam_name', label: 'اسم الاختبار' }
            ]
        };
        
        return columns[this.importType] || [];
    }

    async startImport() {
        this.currentStep = 4;
        this.updateStepUI();
        this.updateButtons();
        
        // Disable buttons during import
        this.nextBtn.disabled = true;
        this.nextBtn.innerHTML = '<span class="loading-spinner"></span> جاري الاستيراد...';
        
        try {
            const formData = new FormData();
            formData.append('filePath', this.filePath);
            formData.append('importType', this.importType);
            formData.append('columnMapping', JSON.stringify(this.columnMapping));
            
            const response = await fetch('api/excel_process_mapped_import.php', {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Update progress
                this.progressBar.style.width = '100%';
                this.progressBar.textContent = '100%';
                this.successCount.textContent = data.successCount || 0;
                this.errorCount.textContent = data.failedCount || 0;
                
                // Show errors if any
                if (data.errors && data.errors.length > 0) {
                    this.progressErrors.classList.add('active');
                    this.progressErrors.innerHTML = data.errors.map(err => 
                        `<div class="progress-error-item">${err}</div>`
                    ).join('');
                }
                
                this.showToast('تم الاستيراد بنجاح!', 'success');
            } else {
                this.showToast('فشل الاستيراد: ' + data.message, 'error');
            }
            
        } catch (error) {
            console.error('Import error:', error);
            this.showToast('حدث خطأ أثناء الاستيراد', 'error');
        } finally {
            this.nextBtn.disabled = false;
            this.updateButtons();
        }
    }

    formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
    }

    showToast(message, type = 'info') {
        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        toast.textContent = message;
        toast.style.cssText = `
            position: fixed;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            background: ${type === 'success' ? '#4caf50' : type === 'error' ? '#f44336' : '#2196f3'};
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
            z-index: 10001;
            animation: slideUp 0.3s ease;
        `;
        
        document.body.appendChild(toast);
        
        setTimeout(() => {
            toast.style.animation = 'slideDown 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

// Initialize on DOM ready
document.addEventListener('DOMContentLoaded', () => {
    window.importSystem = new ImportSystem();
});
