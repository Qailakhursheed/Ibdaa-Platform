#!/usr/bin/env pwsh
# =============================================================================
# سكريبت تنفيذ ترحيل المرحلة 1 (Phase 1 Migration)
# =============================================================================
# الهدف: تطبيق التعديلات على قاعدة البيانات (حذف slug، تعديل users)
# الاستخدام:
#   .\run_migration_phase1.ps1
# =============================================================================

Write-Host "=============================================" -ForegroundColor Cyan
Write-Host "  تنفيذ ترحيل المرحلة 1 (Phase 1 Migration)" -ForegroundColor Cyan
Write-Host "=============================================" -ForegroundColor Cyan
Write-Host ""

# المسارات
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$migrationFile = Join-Path $scriptPath "database\migration_phase1.sql"
$mysqlPath = "C:\xampp\mysql\bin\mysql.exe"

# التحقق من وجود ملف الترحيل
if (-not (Test-Path $migrationFile)) {
    Write-Host "❌ خطأ: ملف الترحيل غير موجود!" -ForegroundColor Red
    Write-Host "المسار المتوقع: $migrationFile" -ForegroundColor Yellow
    exit 1
}

# التحقق من وجود MySQL
if (-not (Test-Path $mysqlPath)) {
    Write-Host "❌ خطأ: MySQL غير موجود في المسار المتوقع!" -ForegroundColor Red
    Write-Host "المسار المتوقع: $mysqlPath" -ForegroundColor Yellow
    Write-Host ""
    Write-Host "💡 تلميح: تأكد من تثبيت XAMPP وتشغيل MySQL" -ForegroundColor Cyan
    exit 1
}

# معلومات الاتصال بقاعدة البيانات
$dbName = "ibdaa_platform"
$dbUser = "root"
$dbPass = ""

Write-Host "📋 معلومات الترحيل:" -ForegroundColor Green
Write-Host "   - قاعدة البيانات: $dbName" -ForegroundColor Gray
Write-Host "   - ملف الترحيل: migration_phase1.sql" -ForegroundColor Gray
Write-Host ""

# السؤال عن التأكيد
Write-Host "⚠️  تحذير: سيتم تطبيق التعديلات التالية:" -ForegroundColor Yellow
Write-Host "   1. حذف عمود slug من جدول courses" -ForegroundColor White
Write-Host "   2. إعادة تسمية birth_date إلى dob في جدول users" -ForegroundColor White
Write-Host "   3. إضافة الأعمدة المفقودة (phone, full_name_en, governorate, district)" -ForegroundColor White
Write-Host ""

$confirmation = Read-Host "هل تريد المتابعة؟ (نعم/لا) [نعم]"
if ($confirmation -eq "" -or $confirmation -eq "نعم" -or $confirmation -eq "yes" -or $confirmation -eq "y") {
    Write-Host ""
    Write-Host "🚀 بدء تنفيذ الترحيل..." -ForegroundColor Cyan
    Write-Host ""
    
    # تنفيذ الترحيل
    $mysqlArgs = @(
        "-u", $dbUser,
        $dbName,
        "-e", "source $migrationFile"
    )
    
    if ($dbPass -ne "") {
        $mysqlArgs = @("-u", $dbUser, "-p$dbPass") + $mysqlArgs[1..($mysqlArgs.Length-1)]
    }
    
    try {
        & $mysqlPath $mysqlArgs
        
        if ($LASTEXITCODE -eq 0) {
            Write-Host ""
            Write-Host "=============================================" -ForegroundColor Green
            Write-Host "  ✅ تم إكمال الترحيل بنجاح!" -ForegroundColor Green
            Write-Host "=============================================" -ForegroundColor Green
            Write-Host ""
            Write-Host "📝 التعديلات المطبقة:" -ForegroundColor Cyan
            Write-Host "   ✓ تم حذف عمود slug من courses" -ForegroundColor Green
            Write-Host "   ✓ تم تعديل جدول users بنجاح" -ForegroundColor Green
            Write-Host ""
            Write-Host "💡 الخطوات التالية:" -ForegroundColor Yellow
            Write-Host "   1. افتح لوحة التحكم (Manager/dashboard.php)" -ForegroundColor White
            Write-Host "   2. جرّب إضافة دورة جديدة" -ForegroundColor White
            Write-Host "   3. تأكد من عمل جميع الأزرار" -ForegroundColor White
            Write-Host ""
        } else {
            Write-Host ""
            Write-Host "❌ حدث خطأ أثناء تنفيذ الترحيل" -ForegroundColor Red
            Write-Host "الرجاء التحقق من الأخطاء أعلاه" -ForegroundColor Yellow
            exit 1
        }
    } catch {
        Write-Host ""
        Write-Host "❌ خطأ: $($_.Exception.Message)" -ForegroundColor Red
        exit 1
    }
} else {
    Write-Host ""
    Write-Host "❌ تم إلغاء الترحيل" -ForegroundColor Yellow
    exit 0
}
