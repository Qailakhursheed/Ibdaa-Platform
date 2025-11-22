<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تحديث قاعدة البيانات - Ibdaa Platform</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .log-success { color: #10b981; }
        .log-error { color: #ef4444; }
        .log-info { color: #3b82f6; }
        .log-warning { color: #f59e0b; }
    </style>
</head>
<body class="bg-slate-100">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-5xl mx-auto">
            
            <!-- Header -->
            <div class="bg-gradient-to-r from-sky-600 to-blue-600 rounded-t-2xl p-6 text-white">
                <h1 class="text-3xl font-bold mb-2">🔧 تحديث قاعدة البيانات</h1>
                <p class="text-sky-100">Database Update System - Ibdaa Platform</p>
            </div>

            <!-- Status Card -->
            <div class="bg-white rounded-b-2xl shadow-lg p-6">
                
                <!-- Current Status -->
                <div id="statusCard" class="mb-6">
                    <h2 class="text-xl font-bold text-slate-800 mb-4">📊 الحالة الحالية</h2>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <p class="text-sm text-slate-600">اتصال قاعدة البيانات</p>
                            <p id="dbStatus" class="text-2xl font-bold">...</p>
                        </div>
                        <div class="bg-slate-50 p-4 rounded-lg">
                            <p class="text-sm text-slate-600">عدد الجداول</p>
                            <p id="tableCount" class="text-2xl font-bold">...</p>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="mb-6 space-y-3">
                    <button onclick="checkDatabase()" class="w-full px-6 py-3 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-bold">
                        🔍 فحص قاعدة البيانات
                    </button>
                    <button onclick="rebuildDatabase()" class="w-full px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-bold">
                        🔄 إعادة بناء قاعدة البيانات
                    </button>
                    <button onclick="exportBackup()" class="w-full px-6 py-3 bg-amber-600 text-white rounded-lg hover:bg-amber-700 font-bold">
                        💾 نسخ احتياطي قبل التحديث
                    </button>
                </div>

                <!-- Progress -->
                <div id="progressBar" class="hidden mb-6">
                    <div class="w-full bg-slate-200 rounded-full h-4">
                        <div id="progress" class="bg-blue-600 h-4 rounded-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-center text-sm text-slate-600 mt-2">0%</p>
                </div>

                <!-- Log Output -->
                <div id="logContainer" class="bg-slate-900 text-slate-100 p-4 rounded-lg h-96 overflow-y-auto font-mono text-sm">
                    <div id="logOutput">
                        <p class="log-info">⏳ انتظار الأوامر...</p>
                    </div>
                </div>

            </div>

            <!-- Warning Card -->
            <div class="mt-6 bg-amber-50 border border-amber-200 rounded-lg p-4">
                <h3 class="font-bold text-amber-800 mb-2">⚠️ تحذير مهم</h3>
                <ul class="text-amber-700 text-sm space-y-1">
                    <li>• سيتم حذف جميع البيانات الحالية وإعادة بناء القاعدة من الصفر</li>
                    <li>• تأكد من عمل نسخة احتياطية قبل المتابعة</li>
                    <li>• لا تغلق الصفحة أثناء التحديث</li>
                </ul>
            </div>

        </div>
    </div>

    <script>
        let logDiv = document.getElementById('logOutput');

        function log(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString('ar-YE');
            const className = `log-${type}`;
            logDiv.innerHTML += `<p class="${className}">[${timestamp}] ${message}</p>`;
            logDiv.parentElement.scrollTop = logDiv.parentElement.scrollHeight;
        }

        function setProgress(percent, text) {
            document.getElementById('progressBar').classList.remove('hidden');
            document.getElementById('progress').style.width = percent + '%';
            document.getElementById('progressText').textContent = text || (percent + '%');
        }

        async function checkDatabase() {
            log('🔍 بدء فحص قاعدة البيانات...', 'info');
            logDiv.innerHTML = '';
            
            try {
                const response = await fetch('analyze_database.php');
                const data = await response.json();
                
                if (data.connection_status) {
                    log('✅ الاتصال بقاعدة البيانات ناجح', 'success');
                    document.getElementById('dbStatus').textContent = '✅ متصل';
                    document.getElementById('dbStatus').className = 'text-2xl font-bold text-green-600';
                    
                    log(`📊 عدد الجداول: ${data.tables.length}`, 'info');
                    document.getElementById('tableCount').textContent = data.tables.length;
                    
                    if (data.tables.length > 0) {
                        log('📋 الجداول الموجودة:', 'info');
                        data.tables.forEach(table => {
                            const details = data.table_details[table];
                            log(`  - ${table} (${details.row_count} صفوف، ${details.column_count} أعمدة)`, 'info');
                        });
                    }
                    
                    if (data.users_by_role) {
                        log('👥 المستخدمون حسب الدور:', 'info');
                        Object.entries(data.users_by_role).forEach(([role, count]) => {
                            log(`  - ${role}: ${count}`, 'info');
                        });
                    }
                    
                    if (data.issues && data.issues.length > 0) {
                        log('⚠️ المشاكل المكتشفة:', 'warning');
                        data.issues.forEach(issue => log(`  - ${issue}`, 'warning'));
                    } else {
                        log('✅ لا توجد مشاكل واضحة', 'success');
                    }
                } else {
                    log('❌ فشل الاتصال بقاعدة البيانات', 'error');
                    document.getElementById('dbStatus').textContent = '❌ غير متصل';
                    document.getElementById('dbStatus').className = 'text-2xl font-bold text-red-600';
                }
                
            } catch (error) {
                log('❌ خطأ: ' + error.message, 'error');
            }
        }

        async function rebuildDatabase() {
            if (!confirm('⚠️ هذا الإجراء سيحذف جميع البيانات الحالية!\n\nهل أنت متأكد من المتابعة؟')) {
                return;
            }
            
            logDiv.innerHTML = '';
            log('🔄 بدء إعادة بناء قاعدة البيانات...', 'info');
            setProgress(10, 'جاري القراءة...');
            
            try {
                // قراءة ملف SQL
                log('📖 قراءة ملف REBUILD_DATABASE_COMPLETE.sql...', 'info');
                const sqlResponse = await fetch('REBUILD_DATABASE_COMPLETE.sql');
                const sqlContent = await sqlResponse.text();
                setProgress(30, 'جاري التنفيذ...');
                
                // تقسيم إلى استعلامات
                log('✂️ تقسيم الاستعلامات...', 'info');
                const statements = sqlContent
                    .split(';')
                    .map(s => s.trim())
                    .filter(s => s.length > 0 && !s.startsWith('--'));
                
                log(`📝 عدد الاستعلامات: ${statements.length}`, 'info');
                setProgress(40, 'جاري التطبيق...');
                
                // تنفيذ الاستعلامات
                log('⚙️ تنفيذ الاستعلامات...', 'info');
                const response = await fetch('execute_sql.php', {
                    method: 'POST',
                    headers: {'Content-Type': 'application/json'},
                    body: JSON.stringify({sql: sqlContent})
                });
                
                const result = await response.json();
                setProgress(90, 'جاري التحقق...');
                
                if (result.success) {
                    log('✅ تم تنفيذ جميع الاستعلامات بنجاح!', 'success');
                    log(`📊 عدد الاستعلامات المنفذة: ${result.executed}`, 'success');
                    setProgress(100, 'اكتمل!');
                    
                    if (result.messages && result.messages.length > 0) {
                        result.messages.forEach(msg => log(msg, 'info'));
                    }
                    
                    setTimeout(() => {
                        log('🔍 التحقق من النتائج...', 'info');
                        checkDatabase();
                    }, 1000);
                } else {
                    log('❌ فشل التنفيذ: ' + result.message, 'error');
                    if (result.errors && result.errors.length > 0) {
                        result.errors.forEach(err => log('  - ' + err, 'error'));
                    }
                }
                
            } catch (error) {
                log('❌ خطأ: ' + error.message, 'error');
            }
        }

        async function exportBackup() {
            log('💾 جاري تصدير نسخة احتياطية...', 'info');
            logDiv.innerHTML = '';
            
            try {
                const response = await fetch('backup_database.php');
                const result = await response.json();
                
                if (result.success) {
                    log('✅ تم إنشاء النسخة الاحتياطية: ' + result.filename, 'success');
                    log('📁 الموقع: ' + result.path, 'info');
                    log('💾 الحجم: ' + result.size, 'info');
                } else {
                    log('❌ فشل التصدير: ' + result.message, 'error');
                }
            } catch (error) {
                log('❌ خطأ: ' + error.message, 'error');
            }
        }

        // فحص تلقائي عند تحميل الصفحة
        window.onload = () => {
            checkDatabase();
        };
    </script>
</body>
</html>
