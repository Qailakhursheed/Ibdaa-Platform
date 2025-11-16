<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار أنظمة الذكاء الاصطناعي</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 900px;
            width: 100%;
        }
        
        h1 {
            color: #667eea;
            margin-bottom: 10px;
            font-size: 32px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #6c757d;
            margin-bottom: 40px;
            font-size: 14px;
        }
        
        .status-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        
        .status-card {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            border: 2px solid #e9ecef;
            transition: all 0.3s;
        }
        
        .status-card.success {
            border-color: #28a745;
            background: #d4edda;
        }
        
        .status-card.error {
            border-color: #dc3545;
            background: #f8d7da;
        }
        
        .status-card.loading {
            border-color: #ffc107;
            background: #fff3cd;
        }
        
        .status-icon {
            font-size: 40px;
            margin-bottom: 10px;
        }
        
        .status-name {
            font-weight: 600;
            color: #212529;
            margin-bottom: 5px;
            font-size: 14px;
        }
        
        .status-message {
            color: #6c757d;
            font-size: 12px;
        }
        
        .test-section {
            background: #f8f9fa;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 20px;
        }
        
        .test-section h2 {
            color: #495057;
            margin-bottom: 20px;
            font-size: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .test-button {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 15px 30px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s;
            margin: 5px;
        }
        
        .test-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        
        .test-button:disabled {
            background: #6c757d;
            cursor: not-allowed;
            transform: none;
        }
        
        .output {
            background: #212529;
            color: #00ff00;
            padding: 20px;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            max-height: 300px;
            overflow-y: auto;
            margin-top: 20px;
            white-space: pre-wrap;
            word-wrap: break-word;
        }
        
        .success-badge {
            display: inline-block;
            background: #28a745;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
        
        .error-badge {
            display: inline-block;
            background: #dc3545;
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-right: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🤖 اختبار أنظمة الذكاء الاصطناعي</h1>
        <p class="subtitle">AI-Powered Notifications & Import Systems Test</p>
        
        <div class="status-grid" id="statusGrid">
            <!-- Will be populated by JavaScript -->
        </div>
        
        <div class="test-section">
            <h2>
                <span>🧪</span>
                اختبارات سريعة
            </h2>
            
            <button class="test-button" onclick="testNotifications()">
                اختبار نظام الإشعارات AI
            </button>
            
            <button class="test-button" onclick="testImport()">
                اختبار نظام الاستيراد AI
            </button>
            
            <button class="test-button" onclick="testAllLibraries()">
                اختبار جميع المكتبات
            </button>
            
            <button class="test-button" onclick="clearOutput()">
                مسح السجل
            </button>
            
            <div class="output" id="testOutput" style="display: none;"></div>
        </div>
    </div>
    
    <?php include 'Manager/includes/ai-libraries.php'; ?>
    
    <script>
        const output = document.getElementById('testOutput');
        const statusGrid = document.getElementById('statusGrid');
        
        function log(message, type = 'info') {
            output.style.display = 'block';
            const timestamp = new Date().toLocaleTimeString('ar-SA');
            const prefix = type === 'success' ? '✅' : type === 'error' ? '❌' : '📝';
            output.innerHTML += `[${timestamp}] ${prefix} ${message}\n`;
            output.scrollTop = output.scrollHeight;
        }
        
        function clearOutput() {
            output.innerHTML = '';
            output.style.display = 'none';
        }
        
        function updateLibraryStatus() {
            const libraries = [
                { name: 'TensorFlow.js', check: () => typeof tf !== 'undefined', icon: '🧠' },
                { name: 'Compromise NLP', check: () => typeof nlp !== 'undefined', icon: '📝' },
                { name: 'Sentiment', check: () => typeof Sentiment !== 'undefined', icon: '😊' },
                { name: 'Tesseract OCR', check: () => typeof Tesseract !== 'undefined', icon: '👁️' },
                { name: 'Papa Parse', check: () => typeof Papa !== 'undefined', icon: '📊' },
                { name: 'Brain.js', check: () => typeof brain !== 'undefined', icon: '🧩' },
                { name: 'ml5.js', check: () => typeof ml5 !== 'undefined', icon: '🎯' },
                { name: 'PDF.js', check: () => typeof pdfjsLib !== 'undefined', icon: '📄' },
                { name: 'XLSX', check: () => typeof XLSX !== 'undefined', icon: '📈' },
                { name: 'Fuse.js', check: () => typeof Fuse !== 'undefined', icon: '🔍' },
                { name: 'Chart.js', check: () => typeof Chart !== 'undefined', icon: '📉' },
                { name: 'AI Config', check: () => typeof AIConfig !== 'undefined', icon: '⚙️' }
            ];
            
            statusGrid.innerHTML = '';
            
            libraries.forEach(lib => {
                const isLoaded = lib.check();
                const card = document.createElement('div');
                card.className = `status-card ${isLoaded ? 'success' : 'error'}`;
                card.innerHTML = `
                    <div class="status-icon">${lib.icon}</div>
                    <div class="status-name">${lib.name}</div>
                    <div class="status-message">${isLoaded ? 'محمّل ✓' : 'غير محمّل ✗'}</div>
                `;
                statusGrid.appendChild(card);
            });
        }
        
        async function testNotifications() {
            log('🚀 بدء اختبار نظام الإشعارات...', 'info');
            
            try {
                if (typeof AdvancedAINotificationsSystem === 'undefined') {
                    log('❌ نظام الإشعارات AI غير محمّل', 'error');
                    return;
                }
                
                log('✅ فئة AdvancedAINotificationsSystem موجودة', 'success');
                
                // Create instance
                const notificationSystem = new AdvancedAINotificationsSystem();
                log('✅ تم إنشاء instance بنجاح', 'success');
                
                // Test methods
                const methods = [
                    'init',
                    'loadAIModels',
                    'processNotificationsWithAI',
                    'analyzeSentiment',
                    'detectCategory',
                    'predictPriority',
                    'createSmartGroups',
                    'speakNotification',
                    'initWebSocket'
                ];
                
                methods.forEach(method => {
                    if (typeof notificationSystem[method] === 'function') {
                        log(`✅ Method: ${method}()`, 'success');
                    } else {
                        log(`❌ Method missing: ${method}()`, 'error');
                    }
                });
                
                log('🎉 اختبار نظام الإشعارات مكتمل!', 'success');
                
            } catch (error) {
                log(`❌ خطأ: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        async function testImport() {
            log('🚀 بدء اختبار نظام الاستيراد...', 'info');
            
            try {
                if (typeof AdvancedAIImportSystem === 'undefined') {
                    log('❌ نظام الاستيراد AI غير محمّل', 'error');
                    return;
                }
                
                log('✅ فئة AdvancedAIImportSystem موجودة', 'success');
                
                // Create instance
                const importSystem = new AdvancedAIImportSystem();
                log('✅ تم إنشاء instance بنجاح', 'success');
                
                // Test methods
                const methods = [
                    'init',
                    'loadAIModels',
                    'handleFileSelect',
                    'processImageWithOCR',
                    'processPDF',
                    'analyzeFileStructure',
                    'detectDataTypes',
                    'getAISuggestions',
                    'levenshteinDistance',
                    'startImport'
                ];
                
                methods.forEach(method => {
                    if (typeof importSystem[method] === 'function') {
                        log(`✅ Method: ${method}()`, 'success');
                    } else {
                        log(`❌ Method missing: ${method}()`, 'error');
                    }
                });
                
                log('🎉 اختبار نظام الاستيراد مكتمل!', 'success');
                
            } catch (error) {
                log(`❌ خطأ: ${error.message}`, 'error');
                console.error(error);
            }
        }
        
        async function testAllLibraries() {
            log('🚀 بدء اختبار جميع المكتبات...', 'info');
            
            if (typeof AIConfig !== 'undefined') {
                const status = AIConfig.getStatus();
                
                log('📊 حالة المكتبات:', 'info');
                Object.entries(status).forEach(([name, loaded]) => {
                    log(`${loaded ? '✅' : '❌'} ${name}: ${loaded ? 'محمّل' : 'غير محمّل'}`, loaded ? 'success' : 'error');
                });
                
                // Test TensorFlow backend
                if (status.tensorflow) {
                    try {
                        const backend = tf.getBackend();
                        log(`✅ TensorFlow Backend: ${backend}`, 'success');
                    } catch (e) {
                        log(`❌ TensorFlow Backend Error: ${e.message}`, 'error');
                    }
                }
                
                log('🎉 اختبار المكتبات مكتمل!', 'success');
            } else {
                log('❌ AIConfig غير موجود', 'error');
            }
        }
        
        // Initialize on load
        window.addEventListener('load', () => {
            updateLibraryStatus();
            log('🎯 صفحة الاختبار جاهزة!', 'success');
        });
        
        window.addEventListener('ai-libraries-ready', (e) => {
            log('✅ AI Libraries تم تحميلها بنجاح!', 'success');
            log(`📦 Configuration: ${JSON.stringify(e.detail.config.features, null, 2)}`, 'info');
            updateLibraryStatus();
        });
    </script>
</body>
</html>
