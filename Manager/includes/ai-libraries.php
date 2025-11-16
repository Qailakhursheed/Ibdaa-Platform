<!-- 
  ═══════════════════════════════════════════════════════════════
  AI LIBRARIES DEPENDENCIES
  مكتبات الذكاء الاصطناعي والتعلم الآلي
  ═══════════════════════════════════════════════════════════════
  
  This file loads all AI/ML libraries needed for:
  - AI-Powered Notifications System
  - AI-Powered Import System
  
  Features:
  - TensorFlow.js (Machine Learning)
  - Natural Language Processing
  - Computer Vision (OCR, Object Detection)
  - Data Processing
  - Neural Networks
  
  Include in your HTML:
  <?php include 'includes/ai-libraries.php'; ?>
  
  ═══════════════════════════════════════════════════════════════
-->

<!-- ═══════════════════════════════════════════════════════════════
     TENSORFLOW.JS - Machine Learning Framework
     ═══════════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow/tfjs@4.11.0/dist/tf.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
     NATURAL LANGUAGE PROCESSING (NLP)
     ═══════════════════════════════════════════════════════════════ -->

<!-- Compromise - NLP library for text processing -->
<script src="https://unpkg.com/compromise@14.9.0/builds/compromise.min.js"></script>

<!-- Sentiment - Sentiment analysis -->
<script src="https://unpkg.com/sentiment@5.0.2/build/sentiment.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
     COMPUTER VISION & OCR
     ═══════════════════════════════════════════════════════════════ -->

<!-- Tesseract.js - OCR (Optical Character Recognition) -->
<script src="https://cdn.jsdelivr.net/npm/tesseract.js@4.1.1/dist/tesseract.min.js"></script>

<!-- TensorFlow Models - Pre-trained models -->
<script src="https://cdn.jsdelivr.net/npm/@tensorflow-models/coco-ssd@2.2.3"></script>

<!-- ═══════════════════════════════════════════════════════════════
     DATA PROCESSING & PARSING
     ═══════════════════════════════════════════════════════════════ -->

<!-- Papa Parse - Advanced CSV parsing -->
<script src="https://cdn.jsdelivr.net/npm/papaparse@5.4.1/papaparse.min.js"></script>

<!-- PDF.js - PDF processing -->
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js"></script>

<!-- XLSX - Excel file processing -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
     NEURAL NETWORKS
     ═══════════════════════════════════════════════════════════════ -->

<!-- Brain.js - Neural networks in JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/brain.js@2.0.0/dist/brain-browser.min.js"></script>

<!-- ml5.js - Friendly machine learning library -->
<script src="https://unpkg.com/ml5@0.12.2/dist/ml5.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
     UTILITY LIBRARIES
     ═══════════════════════════════════════════════════════════════ -->

<!-- Natural - Advanced NLP toolkit (optional) -->
<!-- <script src="https://cdn.jsdelivr.net/npm/natural@5.2.2/dist/natural.min.js"></script> -->

<!-- Fuse.js - Fuzzy search -->
<script src="https://cdn.jsdelivr.net/npm/fuse.js@6.6.2"></script>

<!-- Chart.js - Data visualization (for analytics) -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<!-- ═══════════════════════════════════════════════════════════════
     CONFIGURATION
     ═══════════════════════════════════════════════════════════════ -->

<script>
/**
 * AI Libraries Configuration
 */
window.AIConfig = {
    // TensorFlow.js settings
    tensorflow: {
        backend: 'webgl', // 'webgl', 'cpu', 'wasm'
        enableProfiling: false
    },
    
    // Tesseract OCR settings
    tesseract: {
        langPath: 'https://cdn.jsdelivr.net/npm/tesseract.js@4.1.1/lang-data',
        languages: ['ara', 'eng'], // Arabic + English
        workerOptions: {
            logger: (m) => {
                if (window.AIConfig.debug) {
                    console.log('[Tesseract]', m);
                }
            }
        }
    },
    
    // PDF.js settings
    pdfjs: {
        workerSrc: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/build/pdf.worker.min.js',
        cMapUrl: 'https://cdn.jsdelivr.net/npm/pdfjs-dist@3.11.174/cmaps/',
        cMapPacked: true
    },
    
    // Model paths
    models: {
        notificationPriority: '/Manager/models/notification-priority/model.json',
        dataValidation: '/Manager/models/data-validation/model.json',
        sentimentAnalysis: '/Manager/models/sentiment/model.json'
    },
    
    // Web Workers
    workers: {
        importWorker: '/Manager/JS/import-worker.js',
        notificationWorker: '/Manager/JS/notification-worker.js',
        poolSize: 4 // Number of parallel workers
    },
    
    // WebSocket settings
    websocket: {
        url: 'ws://localhost:8080/notifications',
        reconnectInterval: 5000,
        maxReconnectAttempts: 10
    },
    
    // Features flags
    features: {
        mlPrediction: true,
        nlpProcessing: true,
        ocrSupport: true,
        voiceSynthesis: true,
        realtimeUpdates: true,
        parallelProcessing: true,
        predictiveNotifications: true
    },
    
    // Performance settings
    performance: {
        maxNotificationsCache: 1000,
        pollingInterval: 30000, // 30 seconds
        adaptivePolling: true,
        batchSize: 100,
        chunkSize: 1000
    },
    
    // Debug mode
    debug: false,
    
    // Language settings
    language: 'ar', // 'ar' or 'en'
    
    // Initialize all libraries
    async init() {
        console.log('🤖 Initializing AI Libraries...');
        
        try {
            // Initialize TensorFlow.js
            if (typeof tf !== 'undefined') {
                await tf.setBackend(this.tensorflow.backend);
                await tf.ready();
                console.log('✅ TensorFlow.js ready - Backend:', tf.getBackend());
            }
            
            // Initialize PDF.js
            if (typeof pdfjsLib !== 'undefined') {
                pdfjsLib.GlobalWorkerOptions.workerSrc = this.pdfjs.workerSrc;
                console.log('✅ PDF.js ready');
            }
            
            // Check other libraries
            const libraries = [
                { name: 'Compromise (NLP)', check: typeof nlp !== 'undefined' },
                { name: 'Sentiment', check: typeof Sentiment !== 'undefined' },
                { name: 'Tesseract (OCR)', check: typeof Tesseract !== 'undefined' },
                { name: 'Papa Parse (CSV)', check: typeof Papa !== 'undefined' },
                { name: 'Brain.js', check: typeof brain !== 'undefined' },
                { name: 'ml5.js', check: typeof ml5 !== 'undefined' },
                { name: 'COCO-SSD', check: typeof cocoSsd !== 'undefined' },
                { name: 'XLSX', check: typeof XLSX !== 'undefined' },
                { name: 'Fuse.js', check: typeof Fuse !== 'undefined' },
                { name: 'Chart.js', check: typeof Chart !== 'undefined' }
            ];
            
            libraries.forEach(lib => {
                if (lib.check) {
                    console.log(`✅ ${lib.name} ready`);
                } else {
                    console.warn(`⚠️ ${lib.name} not loaded`);
                }
            });
            
            console.log('🎉 AI Libraries initialization complete!');
            
            // Dispatch event
            window.dispatchEvent(new CustomEvent('ai-libraries-ready', {
                detail: { config: this }
            }));
            
            return true;
            
        } catch (error) {
            console.error('❌ AI Libraries initialization failed:', error);
            return false;
        }
    },
    
    // Get library status
    getStatus() {
        return {
            tensorflow: typeof tf !== 'undefined',
            compromise: typeof nlp !== 'undefined',
            sentiment: typeof Sentiment !== 'undefined',
            tesseract: typeof Tesseract !== 'undefined',
            papaParse: typeof Papa !== 'undefined',
            brainjs: typeof brain !== 'undefined',
            ml5: typeof ml5 !== 'undefined',
            cocoSsd: typeof cocoSsd !== 'undefined',
            pdfjs: typeof pdfjsLib !== 'undefined',
            xlsx: typeof XLSX !== 'undefined',
            fuse: typeof Fuse !== 'undefined',
            chart: typeof Chart !== 'undefined'
        };
    }
};

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        window.AIConfig.init();
    });
} else {
    window.AIConfig.init();
}
</script>

<!-- ═══════════════════════════════════════════════════════════════
     CUSTOM STYLES FOR AI COMPONENTS
     ═══════════════════════════════════════════════════════════════ -->
<style>
/* AI Processing Indicator */
.ai-processing {
    position: relative;
}

.ai-processing::after {
    content: '🤖';
    position: absolute;
    top: -5px;
    right: -5px;
    font-size: 12px;
    animation: ai-pulse 2s infinite;
}

@keyframes ai-pulse {
    0%, 100% { opacity: 1; transform: scale(1); }
    50% { opacity: 0.5; transform: scale(1.2); }
}

/* AI Badge */
.ai-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 600;
    text-transform: uppercase;
}

.ai-badge::before {
    content: '🧠';
    font-size: 12px;
}

/* Confidence Score */
.confidence-score {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 6px;
    border-radius: 8px;
    font-size: 11px;
    font-weight: 600;
}

.confidence-score.high {
    background: #d4edda;
    color: #155724;
}

.confidence-score.medium {
    background: #fff3cd;
    color: #856404;
}

.confidence-score.low {
    background: #f8d7da;
    color: #721c24;
}

/* AI Suggestion */
.ai-suggestion {
    position: relative;
    padding-left: 24px;
}

.ai-suggestion::before {
    content: '💡';
    position: absolute;
    left: 4px;
    top: 2px;
}

/* Processing Bar */
.ai-progress-bar {
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2, #f093fb);
    background-size: 200% 100%;
    animation: ai-progress 2s linear infinite;
}

@keyframes ai-progress {
    0% { background-position: 0% 0%; }
    100% { background-position: 200% 0%; }
}
</style>

<!-- ═══════════════════════════════════════════════════════════════
     USAGE INFORMATION
     ═══════════════════════════════════════════════════════════════
     
     1. Include this file in your HTML:
        <?php include 'includes/ai-libraries.php'; ?>
     
     2. Wait for initialization:
        window.addEventListener('ai-libraries-ready', (e) => {
            console.log('AI is ready!', e.detail.config);
        });
     
     3. Check library status:
        const status = window.AIConfig.getStatus();
        console.log('TensorFlow:', status.tensorflow);
     
     4. Configure settings:
        window.AIConfig.debug = true;
        window.AIConfig.tensorflow.backend = 'cpu';
     
     5. Access models:
        const modelPath = window.AIConfig.models.notificationPriority;
        const model = await tf.loadLayersModel(modelPath);
     
     ═══════════════════════════════════════════════════════════════ -->
