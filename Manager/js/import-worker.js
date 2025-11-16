/**
 * ═══════════════════════════════════════════════════════════════
 * WEB WORKER FOR PARALLEL IMPORT PROCESSING
 * معالج الخلفية للاستيراد المتوازي
 * ═══════════════════════════════════════════════════════════════
 * Features:
 * - Background chunk processing
 * - Data validation
 * - Duplicate detection
 * - Quality scoring
 * - No UI blocking
 * ═══════════════════════════════════════════════════════════════
 */

// Worker state
let workerState = {
    workerId: null,
    processedChunks: 0,
    totalRecords: 0,
    duplicates: new Set(),
    errors: []
};

/**
 * Message handler
 */
self.onmessage = async function(e) {
    const { type, data } = e.data;
    
    switch (type) {
        case 'init':
            initWorker(data);
            break;
            
        case 'process_chunk':
            await processChunk(data);
            break;
            
        case 'validate':
            validateData(data);
            break;
            
        case 'detect_duplicates':
            detectDuplicates(data);
            break;
            
        case 'score_quality':
            scoreQuality(data);
            break;
            
        case 'terminate':
            terminateWorker();
            break;
            
        default:
            sendError('Unknown message type: ' + type);
    }
};

/**
 * Initialize worker
 */
function initWorker(config) {
    workerState.workerId = config.workerId;
    
    sendMessage('initialized', {
        workerId: workerState.workerId,
        timestamp: Date.now()
    });
}

/**
 * Process data chunk
 */
async function processChunk(chunk) {
    const startTime = performance.now();
    
    const results = {
        workerId: workerState.workerId,
        chunkSize: chunk.data.length,
        processed: [],
        errors: [],
        duplicates: [],
        quality: { total: 0, valid: 0, empty: 0, invalid: 0 }
    };
    
    try {
        for (let i = 0; i < chunk.data.length; i++) {
            const record = chunk.data[i];
            const rowIndex = chunk.startIndex + i;
            
            // Validate record
            const validation = validateRecord(record, chunk.mapping);
            
            if (validation.isValid) {
                // Check for duplicates
                const duplicateCheck = checkDuplicate(record, chunk.keyFields);
                
                if (duplicateCheck.isDuplicate) {
                    results.duplicates.push({
                        row: rowIndex,
                        original: duplicateCheck.originalIndex,
                        similarity: duplicateCheck.similarity
                    });
                } else {
                    // Add to processed
                    results.processed.push({
                        row: rowIndex,
                        data: record,
                        quality: validation.quality
                    });
                    
                    // Track for duplicate detection
                    addToIndex(record, rowIndex, chunk.keyFields);
                }
                
                results.quality.valid++;
            } else {
                results.errors.push({
                    row: rowIndex,
                    errors: validation.errors
                });
                results.quality.invalid++;
            }
            
            // Count empty fields
            results.quality.empty += validation.emptyCount;
            results.quality.total++;
        }
        
        const processingTime = performance.now() - startTime;
        
        workerState.processedChunks++;
        workerState.totalRecords += chunk.data.length;
        
        sendMessage('chunk_processed', {
            ...results,
            processingTime,
            totalProcessed: workerState.totalRecords
        });
        
    } catch (error) {
        sendError('Chunk processing failed: ' + error.message, error);
    }
}

/**
 * Validate single record
 */
function validateRecord(record, mapping) {
    const result = {
        isValid: true,
        errors: [],
        emptyCount: 0,
        quality: 100
    };
    
    let totalFields = 0;
    let validFields = 0;
    
    for (const [sourceField, targetField] of Object.entries(mapping)) {
        totalFields++;
        
        const value = record[sourceField];
        
        // Check if empty
        if (!value || String(value).trim() === '') {
            result.emptyCount++;
            result.errors.push(`حقل فارغ: ${targetField}`);
            continue;
        }
        
        // Validate based on field type
        const validation = validateFieldType(targetField, value);
        
        if (!validation.valid) {
            result.isValid = false;
            result.errors.push(validation.error);
        } else {
            validFields++;
        }
    }
    
    // Calculate quality score
    result.quality = totalFields > 0 ? Math.round((validFields / totalFields) * 100) : 0;
    
    return result;
}

/**
 * Validate field type
 */
function validateFieldType(fieldName, value) {
    const strValue = String(value).trim();
    
    // Email validation
    if (fieldName.includes('بريد') || fieldName.includes('email')) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!emailRegex.test(strValue)) {
            return { valid: false, error: `بريد إلكتروني غير صحيح: ${strValue}` };
        }
    }
    
    // Phone validation
    else if (fieldName.includes('هاتف') || fieldName.includes('phone')) {
        const phoneRegex = /^[\d\s\-\+\(\)]{9,}$/;
        if (!phoneRegex.test(strValue)) {
            return { valid: false, error: `رقم هاتف غير صحيح: ${strValue}` };
        }
    }
    
    // Number validation
    else if (fieldName.includes('درجة') || fieldName.includes('grade') || fieldName.includes('سعر') || fieldName.includes('price')) {
        if (isNaN(strValue)) {
            return { valid: false, error: `قيمة رقمية غير صحيحة: ${strValue}` };
        }
    }
    
    // Date validation
    else if (fieldName.includes('تاريخ') || fieldName.includes('date')) {
        if (isNaN(Date.parse(strValue))) {
            return { valid: false, error: `تاريخ غير صحيح: ${strValue}` };
        }
    }
    
    return { valid: true };
}

/**
 * Check for duplicates
 */
function checkDuplicate(record, keyFields) {
    if (!keyFields || keyFields.length === 0) {
        return { isDuplicate: false };
    }
    
    // Generate key from specified fields
    const keyValues = keyFields.map(field => String(record[field] || '').trim().toLowerCase());
    const key = keyValues.join('|');
    
    if (workerState.duplicates.has(key)) {
        // Calculate similarity
        const originalIndex = workerState.duplicates.get(key);
        
        return {
            isDuplicate: true,
            originalIndex: originalIndex,
            similarity: 1.0 // Exact match
        };
    }
    
    return { isDuplicate: false };
}

/**
 * Add record to duplicate index
 */
function addToIndex(record, index, keyFields) {
    if (!keyFields || keyFields.length === 0) return;
    
    const keyValues = keyFields.map(field => String(record[field] || '').trim().toLowerCase());
    const key = keyValues.join('|');
    
    workerState.duplicates.set(key, index);
}

/**
 * Validate data structure
 */
function validateData(data) {
    const validation = {
        isValid: true,
        errors: [],
        warnings: [],
        stats: {
            totalRecords: data.records.length,
            validRecords: 0,
            invalidRecords: 0,
            emptyFields: 0
        }
    };
    
    try {
        // Check if data exists
        if (!data.records || !Array.isArray(data.records)) {
            validation.isValid = false;
            validation.errors.push('البيانات غير صحيحة');
            sendMessage('validation_complete', validation);
            return;
        }
        
        // Check if empty
        if (data.records.length === 0) {
            validation.warnings.push('لا توجد بيانات للمعالجة');
        }
        
        // Validate each record
        for (let i = 0; i < data.records.length; i++) {
            const record = data.records[i];
            const recordValidation = validateRecord(record, data.mapping);
            
            if (recordValidation.isValid) {
                validation.stats.validRecords++;
            } else {
                validation.stats.invalidRecords++;
            }
            
            validation.stats.emptyFields += recordValidation.emptyCount;
        }
        
        sendMessage('validation_complete', validation);
        
    } catch (error) {
        sendError('Validation failed: ' + error.message, error);
    }
}

/**
 * Detect duplicates in dataset
 */
function detectDuplicates(data) {
    try {
        const duplicates = [];
        const seen = new Map();
        
        for (let i = 0; i < data.records.length; i++) {
            const record = data.records[i];
            
            // Generate key
            const keyValues = data.keyFields.map(field => 
                String(record[field] || '').trim().toLowerCase()
            );
            const key = keyValues.join('|');
            
            if (seen.has(key)) {
                duplicates.push({
                    currentIndex: i,
                    originalIndex: seen.get(key),
                    key: key
                });
            } else {
                seen.set(key, i);
            }
        }
        
        sendMessage('duplicates_detected', {
            total: duplicates.length,
            duplicates: duplicates.slice(0, 100) // First 100
        });
        
    } catch (error) {
        sendError('Duplicate detection failed: ' + error.message, error);
    }
}

/**
 * Score data quality
 */
function scoreQuality(data) {
    try {
        const quality = {
            overall: 0,
            completeness: 0,
            validity: 0,
            uniqueness: 0,
            consistency: 0,
            details: {}
        };
        
        let totalFields = 0;
        let validFields = 0;
        let emptyFields = 0;
        
        // Analyze each record
        for (const record of data.records) {
            for (const [field, value] of Object.entries(record)) {
                totalFields++;
                
                if (!value || String(value).trim() === '') {
                    emptyFields++;
                } else {
                    validFields++;
                }
            }
        }
        
        // Calculate scores
        quality.completeness = totalFields > 0 ? ((validFields / totalFields) * 100) : 0;
        quality.validity = 85; // Placeholder - would need actual validation
        quality.uniqueness = 90; // Placeholder - would need duplicate check
        quality.consistency = 80; // Placeholder - would need pattern analysis
        
        quality.overall = (
            quality.completeness * 0.4 +
            quality.validity * 0.3 +
            quality.uniqueness * 0.2 +
            quality.consistency * 0.1
        );
        
        quality.details = {
            totalFields,
            validFields,
            emptyFields,
            emptyPercentage: totalFields > 0 ? ((emptyFields / totalFields) * 100) : 0
        };
        
        sendMessage('quality_scored', quality);
        
    } catch (error) {
        sendError('Quality scoring failed: ' + error.message, error);
    }
}

/**
 * Send message to main thread
 */
function sendMessage(type, data) {
    self.postMessage({
        type: type,
        data: data,
        workerId: workerState.workerId,
        timestamp: Date.now()
    });
}

/**
 * Send error to main thread
 */
function sendError(message, error) {
    self.postMessage({
        type: 'error',
        data: {
            message: message,
            error: error ? {
                message: error.message,
                stack: error.stack
            } : null
        },
        workerId: workerState.workerId,
        timestamp: Date.now()
    });
}

/**
 * Terminate worker
 */
function terminateWorker() {
    sendMessage('terminated', {
        processedChunks: workerState.processedChunks,
        totalRecords: workerState.totalRecords
    });
    
    // Clean up
    workerState.duplicates.clear();
    workerState.errors = [];
    
    self.close();
}

// Log worker ready
sendMessage('ready', { timestamp: Date.now() });
