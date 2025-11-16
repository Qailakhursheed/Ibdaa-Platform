# ================================================================
# سكريبت التشغيل السريع - Quick Setup Script
# منصة إبداع للتدريب - Ibdaa Training Platform
# ================================================================

Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  منصة إبداع للتدريب" -ForegroundColor Yellow
Write-Host "  Quick Setup & Installation" -ForegroundColor Yellow
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# المتغيرات الأساسية
$projectPath = "C:\xampp\htdocs\Ibdaa-Taiz"
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"
$phpPath = "C:\xampp\php\php.exe"
$dbName = "ibdaa_platform"
$dbUser = "root"

# التحقق من مسار المشروع
if (!(Test-Path $projectPath)) {
    Write-Host "❌ خطأ: مسار المشروع غير موجود: $projectPath" -ForegroundColor Red
    exit 1
}

Set-Location $projectPath
Write-Host "✅ المسار الحالي: $projectPath" -ForegroundColor Green
Write-Host ""

# ================================================================
# الخطوة 1: تثبيت Composer Dependencies
# ================================================================
Write-Host "[1/5] تثبيت مكتبات PHP (Composer)..." -ForegroundColor Yellow

if (Test-Path "composer.json") {
    if (Test-Path "composer.phar") {
        & $phpPath composer.phar install --no-interaction
    } elseif (Get-Command composer -ErrorAction SilentlyContinue) {
        composer install --no-interaction
    } else {
        Write-Host "⚠️  Composer غير موجود. قم بتنزيله من: https://getcomposer.org/" -ForegroundColor Yellow
        Write-Host "   أو استخدم: php composer-setup.php" -ForegroundColor Yellow
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ تم تثبيت المكتبات بنجاح" -ForegroundColor Green
    } else {
        Write-Host "⚠️  فشل تثبيت بعض المكتبات (سيتم المتابعة)" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠️  ملف composer.json غير موجود" -ForegroundColor Yellow
}

Write-Host ""

# ================================================================
# الخطوة 2: إنشاء المجلدات المطلوبة
# ================================================================
Write-Host "[2/5] إنشاء مجلدات التخزين..." -ForegroundColor Yellow

$folders = @(
    "uploads/qrcodes",
    "uploads/imports",
    "uploads/cards",
    "uploads/profiles"
)

foreach ($folder in $folders) {
    $fullPath = Join-Path $projectPath $folder
    if (!(Test-Path $fullPath)) {
        New-Item -Path $fullPath -ItemType Directory -Force | Out-Null
        Write-Host "  ✅ تم إنشاء: $folder" -ForegroundColor Green
    } else {
        Write-Host "  ℹ️  موجود: $folder" -ForegroundColor Gray
    }
}

# منح صلاحيات الكتابة
try {
    icacls "uploads" /grant "Everyone:(OI)(CI)F" /T | Out-Null
    Write-Host "✅ تم منح صلاحيات الكتابة للمجلدات" -ForegroundColor Green
} catch {
    Write-Host "⚠️  تحذير: فشل منح الصلاحيات (قد تحتاج صلاحيات المدير)" -ForegroundColor Yellow
}

Write-Host ""

# ================================================================
# الخطوة 3: تطبيق تحسينات قاعدة البيانات
# ================================================================
Write-Host "[3/5] تطبيق تحسينات قاعدة البيانات..." -ForegroundColor Yellow

$sqlFile = Join-Path $projectPath "database\schema_enhancements.sql"

if (Test-Path $sqlFile) {
    Write-Host "  📄 ملف SQL موجود: schema_enhancements.sql" -ForegroundColor Gray
    
    # طلب كلمة المرور
    Write-Host "  🔐 أدخل كلمة مرور MySQL (اترك فارغاً إذا لم تكن موجودة):" -ForegroundColor Cyan
    $securePassword = Read-Host -AsSecureString
    $password = [Runtime.InteropServices.Marshal]::PtrToStringAuto(
        [Runtime.InteropServices.Marshal]::SecureStringToBSTR($securePassword)
    )
    
    # تنفيذ ملف SQL
    if ([string]::IsNullOrWhiteSpace($password)) {
        & $mysqlPath -u $dbUser $dbName < $sqlFile 2>&1 | Out-Null
    } else {
        & $mysqlPath -u $dbUser -p"$password" $dbName < $sqlFile 2>&1 | Out-Null
    }
    
    if ($LASTEXITCODE -eq 0) {
        Write-Host "✅ تم تطبيق التحسينات على قاعدة البيانات" -ForegroundColor Green
    } else {
        Write-Host "⚠️  فشل تطبيق بعض التغييرات (قد تكون موجودة مسبقاً)" -ForegroundColor Yellow
    }
} else {
    Write-Host "⚠️  ملف SQL غير موجود: $sqlFile" -ForegroundColor Yellow
}

Write-Host ""

# ================================================================
# الخطوة 4: التحقق من الملفات المطلوبة
# ================================================================
Write-Host "[4/5] التحقق من الملفات الأساسية..." -ForegroundColor Yellow

$requiredFiles = @(
    "Manager/api/chat_system.php",
    "Manager/api/notifications_system.php",
    "Manager/api/registration_requests.php",
    "Manager/api/smart_import.php",
    "Manager/api/id_cards_system.php",
    "Manager/api/dynamic_analytics.php",
    "Manager/js/advanced-forms.js",
    "Manager/js/dynamic-charts.js"
)

$missingFiles = @()

foreach ($file in $requiredFiles) {
    $fullPath = Join-Path $projectPath $file
    if (Test-Path $fullPath) {
        Write-Host "  ✅ $file" -ForegroundColor Green
    } else {
        Write-Host "  ❌ $file" -ForegroundColor Red
        $missingFiles += $file
    }
}

if ($missingFiles.Count -eq 0) {
    Write-Host "✅ جميع الملفات موجودة" -ForegroundColor Green
} else {
    Write-Host "⚠️  عدد الملفات الناقصة: $($missingFiles.Count)" -ForegroundColor Yellow
}

Write-Host ""

# ================================================================
# الخطوة 5: عرض معلومات الوصول
# ================================================================
Write-Host "[5/5] معلومات الوصول..." -ForegroundColor Yellow

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  🎉 تم إكمال التثبيت!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""
Write-Host "📍 روابط الوصول:" -ForegroundColor Yellow
Write-Host "   لوحة المدير: http://localhost/Ibdaa-Taiz/Manager/dashboard.php" -ForegroundColor White
Write-Host "   API الدردشة: http://localhost/Ibdaa-Taiz/Manager/api/chat_system.php" -ForegroundColor White
Write-Host "   API الإشعارات: http://localhost/Ibdaa-Taiz/Manager/api/notifications_system.php" -ForegroundColor White
Write-Host "   API التحليلات: http://localhost/Ibdaa-Taiz/Manager/api/dynamic_analytics.php" -ForegroundColor White
Write-Host ""
Write-Host "📚 الوثائق:" -ForegroundColor Yellow
Write-Host "   دليل التطبيق: IMPLEMENTATION_GUIDE_COMPLETE.md" -ForegroundColor White
Write-Host ""
Write-Host "🔧 الخطوات التالية:" -ForegroundColor Yellow
Write-Host "   1. تأكد من تشغيل Apache و MySQL" -ForegroundColor White
Write-Host "   2. افتح لوحة المدير وسجل الدخول" -ForegroundColor White
Write-Host "   3. اختبر الرسوم البيانية والنماذج" -ForegroundColor White
Write-Host "   4. راجع Console المتصفح للتحقق من عدم وجود أخطاء" -ForegroundColor White
Write-Host ""
Write-Host "⚡ أوامر مفيدة:" -ForegroundColor Yellow
Write-Host "   اختبار API: " -ForegroundColor White -NoNewline
Write-Host "Invoke-WebRequest http://localhost/Ibdaa-Taiz/Manager/api/dynamic_analytics.php?action=dashboard_stats" -ForegroundColor Gray
Write-Host ""
Write-Host "❓ المساعدة:" -ForegroundColor Yellow
Write-Host "   إذا واجهت مشاكل، راجع قسم 'حل المشاكل' في الدليل" -ForegroundColor White
Write-Host ""

# ================================================================
# اختبار سريع للاتصال
# ================================================================
Write-Host "🧪 اختبار الاتصال..." -ForegroundColor Yellow

try {
    $testUrl = "http://localhost/Ibdaa-Taiz/Manager/api/dynamic_analytics.php?action=dashboard_stats"
    $response = Invoke-WebRequest -Uri $testUrl -UseBasicParsing -TimeoutSec 5 -ErrorAction Stop
    
    if ($response.StatusCode -eq 200) {
        Write-Host "✅ API يعمل بشكل صحيح!" -ForegroundColor Green
        
        # محاولة تحليل JSON
        try {
            $jsonData = $response.Content | ConvertFrom-Json
            if ($jsonData.success) {
                Write-Host "✅ البيانات صحيحة!" -ForegroundColor Green
                Write-Host ""
                Write-Host "📊 عينة من الإحصائيات:" -ForegroundColor Cyan
                Write-Host "   الطلاب: $($jsonData.statistics.total_students)" -ForegroundColor White
                Write-Host "   المدربين: $($jsonData.statistics.total_trainers)" -ForegroundColor White
                Write-Host "   الدورات: $($jsonData.statistics.total_courses)" -ForegroundColor White
            }
        } catch {
            Write-Host "⚠️  تحذير: استجابة غير متوقعة من API" -ForegroundColor Yellow
        }
    }
} catch {
    Write-Host "⚠️  تحذير: فشل الاتصال بـ API" -ForegroundColor Yellow
    Write-Host "   تأكد من تشغيل Apache و MySQL" -ForegroundColor Gray
}

Write-Host ""
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host "  🚀 جاهز للانطلاق!" -ForegroundColor Green
Write-Host "=====================================" -ForegroundColor Cyan
Write-Host ""

# إيقاف مؤقت
Write-Host "اضغط أي مفتاح للإغلاق..." -ForegroundColor Gray
$null = $Host.UI.RawUI.ReadKey("NoEcho,IncludeKeyDown")
