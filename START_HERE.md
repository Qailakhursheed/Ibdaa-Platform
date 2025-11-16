# 🎯 تعليمات التشغيل - Start Here

## للمدير/المطور الجديد: ابدأ من هنا! 👇

---

## 🚀 خطوة واحدة للتشغيل

### افتح PowerShell وقم بتنفيذ:

```powershell
cd C:\xampp\htdocs\Ibdaa-Taiz
.\quick_setup.ps1]
```

**هذا كل شيء!** 🎉

السكريبت سيقوم بكل شيء تلقائياً:
- ✅ تثبيت المكتبات
- ✅ إنشاء المجلدات
- ✅ تطبيق قاعدة البيانات
- ✅ اختبار الاتصال

---

## 📖 بعد التشغيل

### 1. افتح لوحة المدير
```
http://localhost/Ibdaa-Taiz/Manager/dashboard.php
```

### 2. تحقق من Console المتصفح (F12)
يجب أن ترى:
```
✅ Dynamic Charts System Initialized!
✅ All charts loaded successfully!
```

### 3. جرّب الميزات الجديدة

**💬 الدردشة:**
- انتقل إلى قسم "الرسائل"
- ابحث عن مستخدم
- أرسل رسالة

**🔔 الإشعارات:**
- انقر على أيقونة الجرس
- شاهد الإشعارات

**📝 طلبات التسجيل:**
- انتقل إلى "الطلبات المعلقة"
- اقبل أو ارفض طلباً

**📊 الاستيراد:**
- انتقل إلى "استيراد البيانات"
- اسحب ملف Excel إلى المنطقة
- شاهد النتائج

**🎫 إصدار البطاقة:**
- اختر طالباً
- اضغط "إصدار بطاقة"
- نزّل البطاقة بالـ QR Code

**📈 الرسوم البيانية:**
- انتقل إلى "التحليلات"
- شاهد 6 أنواع رسوم بيانية حية
- تحدث تلقائياً كل 5 دقائق

---

## 📚 الوثائق

### للتفاصيل الكاملة:
- 📖 **IMPLEMENTATION_GUIDE_COMPLETE.md** - الدليل الشامل
- 📊 **COMPREHENSIVE_DEVELOPMENT_REPORT.md** - التقرير التفصيلي
- ⚡ **QUICK_SUMMARY.md** - الملخص السريع

---

## 🐛 إذا واجهت مشكلة

### مشكلة شائعة #1: Composer
```powershell
composer install
```

### مشكلة شائعة #2: قاعدة البيانات
```powershell
C:\xampp\mysql\bin\mysql.exe -u root ibdaa_platform < database\schema_enhancements.sql
```

### مشكلة شائعة #3: المجلدات
```powershell
mkdir uploads/qrcodes, uploads/imports, uploads/cards -Force
icacls "uploads" /grant "Everyone:(OI)(CI)F" /T
```

---

## ✅ قائمة التحقق

- [ ] Apache يعمل
- [ ] MySQL يعمل
- [ ] قاعدة البيانات موجودة: `ibdaa_platform`
- [ ] تم تنفيذ `schema_enhancements.sql`
- [ ] تم تثبيت Composer Dependencies
- [ ] المجلدات موجودة: `uploads/qrcodes`, `uploads/imports`
- [ ] لوحة المدير تفتح بدون أخطاء
- [ ] الرسوم البيانية تظهر
- [ ] Console نظيف (لا أخطاء)

---

## 🎓 للمطورين

### API Endpoints الجديدة:

```
/Manager/api/chat_system.php
/Manager/api/notifications_system.php
/Manager/api/registration_requests.php
/Manager/api/smart_import.php
/Manager/api/id_cards_system.php
/Manager/api/dynamic_analytics.php
```

### JavaScript الجديد:

```javascript
// النماذج
openAdvancedStudentModal();
openAdvancedPaymentModal();

// الرسوم البيانية
ChartsSystem.loadAllCharts();
ChartsSystem.renderStudentsByStatusChart();
ChartsSystem.renderMonthlyRevenueChart();
```

---

## 📞 المساعدة

### للدعم:
1. راجع قسم "حل المشاكل" في `IMPLEMENTATION_GUIDE_COMPLETE.md`
2. افتح Console المتصفح (F12) وابحث عن الأخطاء
3. راجع `error_log` في Apache

---

## 🎉 جاهز؟

### ابدأ الآن:
```powershell
.\quick_setup.ps1
```

**أو افتح مباشرة:**
```
http://localhost/Ibdaa-Taiz/Manager/dashboard.php
```

---

**✨ بالتوفيق!**

**المطور: GitHub Copilot 🤖**  
**التاريخ: 10 نوفمبر 2025**
