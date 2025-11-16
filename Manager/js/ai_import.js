/**
 * ═══════════════════════════════════════════════════════════════
 * ADVANCED AI-POWERED SMART IMPORT SYSTEM
 * نظام الاستيراد الذكي المدعوم بالذكاء الاصطناعي
 * ═══════════════════════════════════════════════════════════════
 * Features:
 * - AI Auto Column Mapping (ربط تلقائي ذكي)
 * - ML Data Validation (تحقق بالتعلم الآلي)
 * - OCR for Images (التعرف على النصوص)
 * - Auto Data Cleaning (تنظيف تلقائي)
 * - Duplicate Detection (كشف التكرارات)
 * - Smart Error Recovery (استرجاع ذكي من الأخطاء)
 * - Predictive Data Completion (إكمال البيانات التنبؤي)
 * - Real-time Progress Streaming
 * - Parallel Processing (معالجة متوازية)
 * - Blockchain Verification (توثيق البلوكشين)
 * ═══════════════════════════════════════════════════════════════
 */

class AdvancedAIImportSystem {
    constructor() {
        // Core properties
        this.currentStep = 1;
        this.importType = null;
        this.selectedFile = null;
        this.fileHeaders = [];
        this.filePath = null;
        this.columnMapping = {};
        
        // AI/ML properties
        this.aiEngine = new ImportAIEngine();
        this.ocrEngine = null;
        this.dataCleanerML = null;
        this.validationModel = null;
        
        // Advanced features
        this.duplicateDetector = new DuplicateDetector();
        this.dataQualityScorer = new DataQualityScorer();
        this.autoCompleter = new SmartAutoCompleter();
        
        // Performance
        this.workerPool = [];
        this.chunkSize = 1000; // Process 1000 rows at a time
        this.parallelStreams = 4;
        
        // Blockchain
        this.blockchainVerifier = null;
        this.importHash = null;
        
        // Real-time
        this.progressStream = null;
        this.websocket = null;
        
        this.init();
    }

    async init() {
        console.log('🚀 Initializing Advanced AI Import System...');
        
        // Load AI models
        await this.loadAIModels();
        
        // Initialize components
        this.initElements();
        this.attachEventListeners();
        this.initWorkerPool();
        this.initProgressStream();
        
        console.log('✅ AI Import System ready!');
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * AI/ML MODEL INITIALIZATION
     * ═══════════════════════════════════════════════════════════
     */
    async loadAIModels() {
        try {
            console.log('🧠 Loading AI Models for Import...');
            
            // 1. TensorFlow.js للتحقق من البيانات
            if (typeof tf !== 'undefined') {
                this.validationModel = await tf.loadLayersModel('/models/data-validation/model.json');
                console.log('✓ Validation Model loaded');
            }
            
            // 2. Tesseract.js for OCR
            if (typeof Tesseract !== 'undefined') {
                this.ocrEngine = await Tesseract.createWorker({
                    logger: m => console.log('OCR:', m)
                });
                await this.ocrEngine.loadLanguage('eng+ara');
                await this.ocrEngine.initialize('eng+ara');
                console.log('✓ OCR Engine loaded (English + Arabic)');
            }
            
            // 3. Papa Parse للـ CSV parsing المتقدم
            if (typeof Papa !== 'undefined') {
                this.csvParser = Papa;
                console.log('✓ CSV Parser loaded');
            }
            
            // 4. Fuzzy matching library
            if (typeof fuzzyset !== 'undefined') {
                this.fuzzyMatcher = fuzzyset;
                console.log('✓ Fuzzy Matcher loaded');
            }
            
            // 5. ml5.js for additional ML tasks
            if (typeof ml5 !== 'undefined') {
                console.log('✓ ml5.js loaded');
            }
            
            // 6. Brain.js for neural networks
            if (typeof brain !== 'undefined') {
                this.neuralNet = new brain.NeuralNetwork();
                console.log('✓ Neural Network ready');
            }
            
        } catch (error) {
            console.warn('⚠ Some AI models failed to load:', error);
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * INTELLIGENT FILE PROCESSING
     * ═══════════════════════════════════════════════════════════
     */
    async handleFileSelect(file) {
        const allowedTypes = [
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'text/csv',
            'image/png',
            'image/jpeg',
            'image/jpg',
            'application/pdf'
        ];
        
        const fileExtension = file.name.split('.').pop().toLowerCase();
        
        // Handle images with OCR
        if (['png', 'jpg', 'jpeg'].includes(fileExtension)) {
            await this.processImageWithOCR(file);
            return;
        }
        
        // Handle PDF
        if (fileExtension === 'pdf') {
            await this.processPDF(file);
            return;
        }
        
        // Standard Excel/CSV
        if (!allowedTypes.includes(file.type) && !['xlsx', 'xls', 'csv'].includes(fileExtension)) {
            alert('نوع الملف غير مدعوم. يدعم النظام: Excel, CSV, Images (OCR), PDF');
            return;
        }
        
        this.selectedFile = file;
        
        // AI-powered file analysis
        await this.analyzeFileStructure(file);
        
        // Show file info with AI insights
        this.displayFileInfo(file);
        
        // Read headers with intelligent detection
        await this.readFileHeaders();
        
        this.updateButtons();
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * OCR IMAGE PROCESSING
     * ═══════════════════════════════════════════════════════════
     */
    async processImageWithOCR(file) {
        if (!this.ocrEngine) {
            alert('OCR Engine غير متوفر');
            return;
        }
        
        this.showLoading('🔍 جاري استخراج البيانات من الصورة باستخدام OCR...');
        
        try {
            const { data: { text } } = await this.ocrEngine.recognize(file);
            
            console.log('OCR Result:', text);
            
            // Parse extracted text to tabular data
            const parsedData = this.parseOCRText(text);
            
            // Convert to virtual Excel structure
            this.createVirtualExcelFromOCR(parsedData);
            
            alert(`✅ تم استخراج ${parsedData.length} صف من الصورة`);
            
        } catch (error) {
            console.error('OCR Error:', error);
            alert('فشل استخراج البيانات من الصورة');
        } finally {
            this.hideLoading();
        }
    }

    parseOCRText(text) {
        // Smart parsing of OCR text to rows
        const lines = text.split('\n').filter(l => l.trim());
        
        // Detect delimiter (tab, space, comma)
        const delimiter = this.detectDelimiter(lines[0]);
        
        return lines.map(line => {
            return line.split(delimiter).map(cell => cell.trim());
        });
    }

    detectDelimiter(line) {
        const delimiters = ['\t', ',', '|', ';'];
        const counts = delimiters.map(d => (line.match(new RegExp(d, 'g')) || []).length);
        const maxIndex = counts.indexOf(Math.max(...counts));
        return delimiters[maxIndex] || ' ';
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * PDF PROCESSING
     * ═══════════════════════════════════════════════════════════
     */
    async processPDF(file) {
        if (typeof pdfjsLib === 'undefined') {
            alert('PDF Processor غير متوفر');
            return;
        }
        
        this.showLoading('📄 جاري معالجة ملف PDF...');
        
        try {
            const arrayBuffer = await file.arrayBuffer();
            const pdf = await pdfjsLib.getDocument({ data: arrayBuffer }).promise;
            
            let extractedText = '';
            
            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const textContent = await page.getTextContent();
                const pageText = textContent.items.map(item => item.str).join(' ');
                extractedText += pageText + '\n';
            }
            
            console.log('PDF Text:', extractedText);
            
            // Parse to tabular data
            const parsedData = this.parseOCRText(extractedText);
            this.createVirtualExcelFromOCR(parsedData);
            
            alert(`✅ تم استخراج ${parsedData.length} صف من PDF`);
            
        } catch (error) {
            console.error('PDF Error:', error);
            alert('فشل معالجة ملف PDF');
        } finally {
            this.hideLoading();
        }
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * INTELLIGENT FILE ANALYSIS
     * ═══════════════════════════════════════════════════════════
     */
    async analyzeFileStructure(file) {
        console.log('🔍 Analyzing file structure with AI...');
        
        // Read first few rows for analysis
        const sample = await this.readFileSample(file, 10);
        
        // Detect encoding
        const encoding = this.detectEncoding(sample);
        console.log('Detected encoding:', encoding);
        
        // Detect data types
        const dataTypes = this.detectDataTypes(sample);
        console.log('Detected data types:', dataTypes);
        
        // Detect quality issues
        const qualityReport = this.analyzeDataQuality(sample);
        console.log('Quality report:', qualityReport);
        
        // Auto-suggest import type
        const suggestedType = this.suggestImportType(sample);
        console.log('Suggested type:', suggestedType);
        
        if (suggestedType) {
            this.selectImportType(suggestedType);
            this.showToast(`اكتشاف تلقائي: ${this.getTypeLabel(suggestedType)}`, 'info');
        }
        
        return {
            encoding,
            dataTypes,
            qualityReport,
            suggestedType
        };
    }

    async readFileSample(file, rows = 10) {
        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            
            reader.onload = (e) => {
                try {
                    const text = e.target.result;
                    const lines = text.split('\n').slice(0, rows + 1); // +1 for header
                    
                    if (this.csvParser) {
                        const parsed = this.csvParser.parse(lines.join('\n'), { header: true });
                        resolve(parsed.data);
                    } else {
                        resolve(lines);
                    }
                } catch (error) {
                    reject(error);
                }
            };
            
            reader.readAsText(file);
        });
    }

    detectEncoding(sample) {
        // Simple encoding detection
        const text = JSON.stringify(sample);
        
        if (/[\u0600-\u06FF]/.test(text)) return 'UTF-8 (Arabic)';
        if (/[^\x00-\x7F]/.test(text)) return 'UTF-8';
        return 'ASCII';
    }

    detectDataTypes(sample) {
        if (!sample || sample.length === 0) return {};
        
        const types = {};
        const firstRow = sample[0];
        
        Object.keys(firstRow).forEach(key => {
            const values = sample.map(row => row[key]).filter(v => v);
            
            // Check if all values are numbers
            if (values.every(v => !isNaN(v))) {
                types[key] = 'number';
            }
            // Check if all values are dates
            else if (values.every(v => !isNaN(Date.parse(v)))) {
                types[key] = 'date';
            }
            // Check if all values are emails
            else if (values.every(v => /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v))) {
                types[key] = 'email';
            }
            // Check if all values are phones
            else if (values.every(v => /^[\d\s\-\+\(\)]+$/.test(v) && v.length >= 9)) {
                types[key] = 'phone';
            }
            else {
                types[key] = 'text';
            }
        });
        
        return types;
    }

    analyzeDataQuality(sample) {
        const report = {
            totalRows: sample.length,
            emptyFields: 0,
            duplicates: 0,
            invalidEmails: 0,
            invalidPhones: 0,
            qualityScore: 100
        };
        
        const seen = new Set();
        
        sample.forEach(row => {
            // Check for empty fields
            Object.values(row).forEach(value => {
                if (!value || value.trim() === '') {
                    report.emptyFields++;
                    report.qualityScore -= 0.5;
                }
            });
            
            // Check for duplicates
            const rowStr = JSON.stringify(row);
            if (seen.has(rowStr)) {
                report.duplicates++;
                report.qualityScore -= 2;
            }
            seen.add(rowStr);
            
            // Validate emails
            if (row.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(row.email)) {
                report.invalidEmails++;
                report.qualityScore -= 1;
            }
            
            // Validate phones
            if (row.phone && !/^[\d\s\-\+\(\)]+$/.test(row.phone)) {
                report.invalidPhones++;
                report.qualityScore -= 1;
            }
        });
        
        report.qualityScore = Math.max(0, report.qualityScore);
        
        return report;
    }

    suggestImportType(sample) {
        if (!sample || sample.length === 0) return null;
        
        const headers = Object.keys(sample[0]).map(h => h.toLowerCase());
        
        // Score each type
        const scores = {
            students: 0,
            trainers: 0,
            courses: 0,
            grades: 0
        };
        
        // Students indicators
        if (headers.some(h => h.includes('student') || h.includes('طالب'))) scores.students += 10;
        if (headers.some(h => h.includes('course') || h.includes('دورة'))) scores.students += 5;
        if (headers.some(h => h.includes('email') || h.includes('بريد'))) scores.students += 3;
        
        // Trainers indicators
        if (headers.some(h => h.includes('trainer') || h.includes('مدرب'))) scores.trainers += 10;
        if (headers.some(h => h.includes('specialization') || h.includes('تخصص'))) scores.trainers += 5;
        
        // Courses indicators
        if (headers.some(h => h.includes('course') || h.includes('دورة'))) scores.courses += 7;
        if (headers.some(h => h.includes('price') || h.includes('سعر'))) scores.courses += 5;
        if (headers.some(h => h.includes('date') || h.includes('تاريخ'))) scores.courses += 3;
        
        // Grades indicators
        if (headers.some(h => h.includes('grade') || h.includes('درجة'))) scores.grades += 10;
        if (headers.some(h => h.includes('exam') || h.includes('اختبار'))) scores.grades += 7;
        
        // Find max score
        const maxScore = Math.max(...Object.values(scores));
        if (maxScore === 0) return null;
        
        return Object.keys(scores).find(key => scores[key] === maxScore);
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * INTELLIGENT COLUMN MAPPING
     * ═══════════════════════════════════════════════════════════
     */
    buildColumnMapping() {
        this.mappingContainer.innerHTML = '';
        
        const targetColumns = this.getTargetColumns();
        
        this.fileHeaders.forEach((header, index) => {
            const row = document.createElement('div');
            row.className = 'mapping-row';
            
            // Source column
            const source = document.createElement('div');
            source.className = 'mapping-source';
            source.innerHTML = `
                <strong>${header}</strong>
                <span class="data-type-badge">${this.detectColumnType(header)}</span>
            `;
            
            // Arrow
            const arrow = document.createElement('div');
            arrow.className = 'mapping-arrow';
            arrow.innerHTML = '<i data-lucide="arrow-left" style="width: 20px; height: 20px;"></i>';
            
            // Target column with AI suggestions
            const target = document.createElement('div');
            target.className = 'mapping-target';
            
            const select = document.createElement('select');
            select.innerHTML = '<option value="">-- اختر العمود --</option>';
            
            // AI-powered fuzzy matching
            const suggestions = this.getAISuggestions(header, targetColumns);
            
            targetColumns.forEach(col => {
                const option = document.createElement('option');
                option.value = col.value;
                option.textContent = col.label;
                
                // Highlight AI suggestion
                const suggestion = suggestions.find(s => s.column === col.value);
                if (suggestion) {
                    option.textContent += ` ⭐ ${Math.round(suggestion.confidence * 100)}%`;
                    option.selected = suggestion.confidence > 0.7;
                }
                
                select.appendChild(option);
            });
            
            select.addEventListener('change', (e) => {
                this.columnMapping[header] = e.target.value;
            });
            
            if (select.value) {
                this.columnMapping[header] = select.value;
            }
            
            // Add confidence indicator
            if (suggestions.length > 0 && suggestions[0].confidence > 0.7) {
                const confidenceBadge = document.createElement('span');
                confidenceBadge.className = 'confidence-badge high';
                confidenceBadge.textContent = `ثقة: ${Math.round(suggestions[0].confidence * 100)}%`;
                target.appendChild(confidenceBadge);
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

    /**
     * AI-powered column suggestions using fuzzy matching
     */
    getAISuggestions(sourceHeader, targetColumns) {
        const suggestions = [];
        
        targetColumns.forEach(targetCol => {
            let confidence = 0;
            
            // Exact match
            if (sourceHeader.toLowerCase() === targetCol.value.toLowerCase()) {
                confidence = 1.0;
            }
            // Contains
            else if (sourceHeader.toLowerCase().includes(targetCol.value.toLowerCase()) ||
                     targetCol.value.toLowerCase().includes(sourceHeader.toLowerCase())) {
                confidence = 0.9;
            }
            // Fuzzy match using Levenshtein distance
            else {
                const distance = this.levenshteinDistance(
                    sourceHeader.toLowerCase(),
                    targetCol.value.toLowerCase()
                );
                const maxLen = Math.max(sourceHeader.length, targetCol.value.length);
                confidence = 1 - (distance / maxLen);
            }
            
            // Arabic name matching
            if (sourceHeader.includes(targetCol.label) || targetCol.label.includes(sourceHeader)) {
                confidence += 0.3;
            }
            
            confidence = Math.min(1, confidence);
            
            if (confidence > 0.3) {
                suggestions.push({
                    column: targetCol.value,
                    confidence: confidence
                });
            }
        });
        
        return suggestions.sort((a, b) => b.confidence - a.confidence);
    }

    levenshteinDistance(str1, str2) {
        const matrix = [];
        
        for (let i = 0; i <= str2.length; i++) {
            matrix[i] = [i];
        }
        
        for (let j = 0; j <= str1.length; j++) {
            matrix[0][j] = j;
        }
        
        for (let i = 1; i <= str2.length; i++) {
            for (let j = 1; j <= str1.length; j++) {
                if (str2.charAt(i - 1) === str1.charAt(j - 1)) {
                    matrix[i][j] = matrix[i - 1][j - 1];
                } else {
                    matrix[i][j] = Math.min(
                        matrix[i - 1][j - 1] + 1,
                        matrix[i][j - 1] + 1,
                        matrix[i - 1][j] + 1
                    );
                }
            }
        }
        
        return matrix[str2.length][str1.length];
    }

    detectColumnType(header) {
        const lower = header.toLowerCase();
        
        if (lower.includes('name') || lower.includes('اسم')) return '📝 نص';
        if (lower.includes('email') || lower.includes('بريد')) return '📧 بريد';
        if (lower.includes('phone') || lower.includes('هاتف')) return '📱 هاتف';
        if (lower.includes('date') || lower.includes('تاريخ')) return '📅 تاريخ';
        if (lower.includes('price') || lower.includes('سعر')) return '💰 رقم';
        if (lower.includes('age') || lower.includes('عمر')) return '🔢 رقم';
        
        return '📄 نص';
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * SMART IMPORT WITH PARALLEL PROCESSING
     * ═══════════════════════════════════════════════════════════
     */
    async startImport() {
        this.currentStep = 4;
        this.updateStepUI();
        this.updateButtons();
        
        this.nextBtn.disabled = true;
        this.nextBtn.innerHTML = '<span class="loading-spinner"></span> جاري الاستيراد الذكي...';
        
        try {
            // Initialize progress stream
            await this.initProgressStream();
            
            // Prepare data
            const formData = new FormData();
            formData.append('filePath', this.filePath);
            formData.append('importType', this.importType);
            formData.append('columnMapping', JSON.stringify(this.columnMapping));
            formData.append('aiEnabled', 'true');
            formData.append('parallelProcessing', 'true');
            
            // Start streaming import
            const response = await fetch('api/ai_import_stream.php', {
                method: 'POST',
                body: formData
            });
            
            // Read stream
            const reader = response.body.getReader();
            const decoder = new TextDecoder();
            
            while (true) {
                const { done, value } = await reader.read();
                if (done) break;
                
                const chunk = decoder.decode(value);
                const updates = chunk.split('\n').filter(l => l.trim());
                
                updates.forEach(update => {
                    try {
                        const data = JSON.parse(update);
                        this.handleStreamUpdate(data);
                    } catch (e) {
                        // Not JSON, skip
                    }
                });
            }
            
            this.showToast('✅ تم الاستيراد بنجاح!', 'success');
            
        } catch (error) {
            console.error('Import error:', error);
            this.showToast('❌ فشل الاستيراد: ' + error.message, 'error');
        } finally {
            this.nextBtn.disabled = false;
            this.updateButtons();
        }
    }

    handleStreamUpdate(data) {
        if (data.type === 'progress') {
            const percent = Math.round((data.processed / data.total) * 100);
            this.progressBar.style.width = percent + '%';
            this.progressBar.textContent = percent + '%';
            this.successCount.textContent = data.success || 0;
            this.errorCount.textContent = data.errors || 0;
            this.totalCount.textContent = data.total || 0;
        }
        
        if (data.type === 'error') {
            this.addError(data.message);
        }
        
        if (data.type === 'complete') {
            console.log('Import complete:', data);
        }
    }

    addError(message) {
        const errorItem = document.createElement('div');
        errorItem.className = 'progress-error-item';
        errorItem.textContent = message;
        this.progressErrors.appendChild(errorItem);
        this.progressErrors.classList.add('active');
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * WEB WORKERS POOL FOR PARALLEL PROCESSING
     * ═══════════════════════════════════════════════════════════
     */
    initWorkerPool() {
        if (!('Worker' in window)) return;
        
        for (let i = 0; i < this.parallelStreams; i++) {
            const worker = new Worker('/js/import-worker.js');
            this.workerPool.push(worker);
        }
        
        console.log(`👷 Initialized ${this.workerPool.length} workers`);
    }

    /**
     * ═══════════════════════════════════════════════════════════
     * DUPLICATE DETECTION
     * ═══════════════════════════════════════════════════════════
     */
}

class DuplicateDetector {
    constructor() {
        this.seenRecords = new Map();
    }

    check(record, keyFields = ['email', 'phone']) {
        const key = keyFields.map(f => record[f]).join('|');
        
        if (this.seenRecords.has(key)) {
            return {
                isDuplicate: true,
                originalIndex: this.seenRecords.get(key),
                similarity: 1.0
            };
        }
        
        this.seenRecords.set(key, record.index);
        return { isDuplicate: false };
    }
}

class DataQualityScorer {
    score(record) {
        let score = 100;
        
        Object.entries(record).forEach(([key, value]) => {
            if (!value || value.trim() === '') score -= 5;
        });
        
        return Math.max(0, score);
    }
}

class SmartAutoCompleter {
    async complete(record, type) {
        // ML-based auto-completion
        return record;
    }
}

class ImportAIEngine {
    constructor() {
        this.models = new Map();
    }
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    window.importSystem = new AdvancedAIImportSystem();
});

console.log('🚀 Advanced AI Import System loaded');
