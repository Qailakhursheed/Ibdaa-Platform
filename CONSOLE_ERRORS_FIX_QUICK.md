# 🔧 إصلاح سريع لأخطاء Console

## المشكلة
ملفات مكررة ومسارات خاطئة تسبب أخطاء 404

## الحل السريع (نفذ هذه الأوامر)

```powershell
# افتح PowerShell وقم بالتنفيذ:

cd C:\xampp\htdocs\Ibdaa-Taiz\Manager

# حذف الملفات القديمة المكررة
Remove-Item "dashboard.php" -ErrorAction SilentlyContinue
Remove-Item "dashboards\manager-features.js" -ErrorAction SilentlyContinue  
Remove-Item "dashboards\js" -Recurse -Force -ErrorAction SilentlyContinue

Write-Host "✅ تم حذف الملفات القديمة" -ForegroundColor Green
```

## ما تم إصلاحه تلقائياً:
- ✅ login.php - التوجيه عبر dashboard_router
- ✅ dynamic-charts.js - المسارات الصحيحة

## اختبر الآن:
1. افتح: `http://localhost/Ibdaa-Taiz/Manager/login.php`
2. سجل دخول
3. تأكد من عدم وجود أخطاء في Console (F12)

## ✅ النتيجة المتوقعة:
لا أخطاء 404، الرسوم البيانية تعمل، الإشعارات تعمل
