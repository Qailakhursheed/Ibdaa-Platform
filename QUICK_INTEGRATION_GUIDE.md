# 📘 دليل الاستخدام السريع - نظام التكامل

## 🚀 بدء الاستخدام

### 1. نظام الدعم الفني

#### للمستخدم (الموقع الخارجي):

**إرسال تذكرة:**
```
1. افتح: http://localhost/Ibdaa-Taiz/platform/support.php
2. املأ النموذج:
   - الاسم
   - البريد الإلكتروني
   - رقم الهاتف
   - الموضوع
   - الفئة (تقني، حساب، دورات، مالي، أخرى)
   - الأولوية (عالية، متوسطة، منخفضة)
   - الرسالة
3. اضغط "إرسال"
4. احفظ رقم التذكرة (TKT-YYYYMMDD-XXXXXX)
5. ستستلم بريد تأكيد
```

**تتبع التذكرة:**
```
1. افتح: http://localhost/Ibdaa-Taiz/platform/track_ticket.php
2. أدخل رقم التذكرة
3. شاهد:
   - الحالة الحالية
   - الأولوية
   - جميع الردود
   - Timeline الزمني
```

#### للإدارة (لوحة التحكم):

**عرض التذاكر:**
```
1. افتح: http://localhost/Ibdaa-Taiz/Manager/dashboards/technical/support.php
2. اختر التبويب:
   - المعلقة (Pending)
   - قيد المعالجة (In Progress)
   - المحلولة (Resolved)
   - المغلقة (Closed)
3. شاهد قائمة التذاكر
```

**الرد على تذكرة:**
```javascript
// في console المتصفح أو الكود
await TechnicalFeatures.support.respond(
    'TKT-20250101-123456',
    'شكراً لتواصلك، سيتم حل مشكلتك خلال 24 ساعة',
    'أحمد المدير'
);

// أو استخدم API مباشرةً
POST http://localhost/Ibdaa-Taiz/Manager/api/support_api.php
{
    "action": "respond",
    "ticket_id": "TKT-20250101-123456",
    "message": "تم حل المشكلة",
    "user_name": "فريق الدعم"
}
```

**إغلاق تذكرة:**
```javascript
await TechnicalFeatures.support.close('TKT-20250101-123456');
```

**تحديث الحالة:**
```javascript
await TechnicalFeatures.support.updateStatus(
    'TKT-20250101-123456',
    'in-progress' // أو resolved, closed
);
```

**الإحصائيات:**
```javascript
const stats = await TechnicalFeatures.support.getStats();
console.log(stats.data);
// {
//   total: 45,
//   pending: 12,
//   in_progress: 8,
//   resolved: 20,
//   closed: 5,
//   high_priority: 3,
//   avg_resolution_hours: 2.5
// }
```

---

### 2. نظام الإعلانات

#### للإدارة (إنشاء إعلان):

**باستخدام API:**
```bash
POST http://localhost/Ibdaa-Taiz/Manager/api/announcements_api.php

# بيانات JSON
{
    "action": "create",
    "title_ar": "دورة Excel المتقدم",
    "content_ar": "انضم لدورة Excel المتقدم المكثفة...",
    "category": "courses",
    "priority": 5,
    "image_url": "uploads/courses/excel.jpg",
    "link_url": "platform/courses.html#excel",
    "start_date": "2025-01-01",
    "end_date": "2025-01-31",
    "is_active": 1
}
```

**الفئات المتاحة:**
```
important → إعلانات هامة
events    → الفعاليات
courses   → الدورات
news      → الأخبار
offers    → العروض
```

#### للمستخدم (الموقع الخارجي):

**عرض الإعلانات:**
```
1. افتح: http://localhost/Ibdaa-Taiz/platform/announcements.html
2. فلتر حسب الفئة (اختياري)
3. اضغط على أي إعلان للتفاصيل
```

**جلب الإعلانات برمجياً:**
```javascript
// جميع الإعلانات النشطة
fetch('../Manager/api/announcements_api.php?action=public')
    .then(res => res.json())
    .then(data => {
        console.log(data.data); // array of announcements
    });

// فئة محددة
fetch('../Manager/api/announcements_api.php?action=public&category=courses&limit=10')
    .then(res => res.json())
    .then(data => {
        console.log(data.data);
    });

// إعلان واحد
fetch('../Manager/api/announcements_api.php?action=get&id=5')
    .then(res => res.json())
    .then(data => {
        console.log(data.data);
    });
```

---

### 3. نظام العلامات المائية

**إضافة علامة مائية لصورة واحدة:**
```php
<?php
require_once 'watermark_system.php';

$watermark = new WatermarkSystem();

$result = $watermark->addWatermark(
    'uploads/courses/python.jpg',  // الصورة الأصلية
    'uploads/watermarked/python.jpg', // الصورة المعالجة
    [
        'opacity' => 30,
        'position' => 'bottom-right',
        'size' => 20,
        'padding' => 20
    ]
);

if ($result['success']) {
    echo "تم إضافة العلامة المائية بنجاح!";
} else {
    echo "خطأ: " . $result['error'];
}
?>
```

**معالجة مجلد كامل:**
```php
<?php
$watermark = new WatermarkSystem();

$result = $watermark->processDirectory(
    'uploads/courses',           // المجلد الأصلي
    'uploads/courses_watermarked' // مجلد الإخراج
);

echo "تم معالجة: " . $result['processed_count'] . " صورة\n";
echo "فشل: " . $result['failed_count'] . " صورة\n";
?>
```

**المواضع المتاحة:**
```
top-left, top-center, top-right
center-left, center, center-right
bottom-left, bottom-center, bottom-right
```

**إضافة نص كعلامة مائية:**
```php
$watermark->addTextWatermark(
    'image.jpg',
    'منصة إبداع © 2025',
    'output.jpg',
    [
        'font_size' => 24,
        'color' => [255, 255, 255],
        'opacity' => 50,
        'position' => 'bottom-right'
    ]
);
```

---

## 🔌 استخدام APIs في JavaScript

### إضافة المكتبة:
```html
<!-- في أي صفحة Dashboard -->
<script src="../js/support_integration.js"></script>
```

### الدوال المتاحة:

#### Support System:
```javascript
// جلب التذاكر
const tickets = await TechnicalFeatures.support.getAll('pending');
console.log(tickets.data);

// جلب تذكرة واحدة
const ticket = await TechnicalFeatures.support.get('TKT-123');
console.log(ticket.data);

// الرد
await TechnicalFeatures.support.respond(ticketId, message, userName);

// تحديث الحالة
await TechnicalFeatures.support.updateStatus(ticketId, 'resolved');

// إغلاق
await TechnicalFeatures.support.close(ticketId);

// بحث
const results = await TechnicalFeatures.support.search('مشكلة تسجيل الدخول');

// حذف (للمدير فقط)
await TechnicalFeatures.support.delete(ticketId);
```

#### مساعدات التنسيق:
```javascript
// تنسيق التاريخ
const formatted = TechnicalFeatures.support.formatDate('2025-01-15 10:30:00');
// "منذ 3 أيام"

// لون الأولوية
const color = TechnicalFeatures.support.getPriorityColor('high');
// "bg-red-100 text-red-800 border-red-200"

// نص الأولوية
const text = TechnicalFeatures.support.getPriorityText('high');
// "عالية"

// لون الحالة
const statusColor = TechnicalFeatures.support.getStatusColor('pending');
// "bg-orange-100 text-orange-800"
```

---

## 🧪 اختبار الأنظمة

### اختبار Support API:

```bash
# PowerShell
$headers = @{
    "Content-Type" = "application/x-www-form-urlencoded"
}

# جلب التذاكر
Invoke-WebRequest -Uri "http://localhost/Ibdaa-Taiz/Manager/api/support_api.php?action=getAll&status=pending" `
    -Method GET | Select-Object -ExpandProperty Content

# إنشاء رد
$body = @{
    action = "respond"
    ticket_id = "TKT-20250101-123456"
    message = "تم استلام طلبك وسيتم الرد خلال 24 ساعة"
    user_name = "فريق الدعم"
}
Invoke-WebRequest -Uri "http://localhost/Ibdaa-Taiz/Manager/api/support_api.php" `
    -Method POST -Body $body -Headers $headers
```

### اختبار Announcements API:

```bash
# جلب الإعلانات
Invoke-WebRequest -Uri "http://localhost/Ibdaa-Taiz/Manager/api/announcements_api.php?action=public&category=courses" `
    -Method GET | Select-Object -ExpandProperty Content

# الإحصائيات
Invoke-WebRequest -Uri "http://localhost/Ibdaa-Taiz/Manager/api/announcements_api.php?action=stats" `
    -Method GET | Select-Object -ExpandProperty Content
```

---

## 🛠️ استكشاف الأخطاء

### مشكلة: لا تظهر التذاكر في Dashboard

**الحل:**
```javascript
1. افتح Console في المتصفح
2. تحقق من:
   console.log(TechnicalFeatures);
   
3. إذا undefined، أضف:
   <script src="../js/support_integration.js"></script>
   
4. اختبر API مباشرةً:
   fetch('../api/support_api.php?action=getAll&status=pending')
       .then(r => r.json())
       .then(console.log);
```

### مشكلة: لا يصل البريد الإلكتروني

**الحل:**
```php
1. تحقق من إعدادات SMTP في php.ini:
   [mail function]
   SMTP = localhost
   smtp_port = 25
   
2. أو استخدم PHPMailer:
   composer require phpmailer/phpmailer
   
3. اختبر:
   php -r "mail('test@example.com', 'Test', 'Body');"
```

### مشكلة: خطأ في قاعدة البيانات

**الحل:**
```sql
1. تحقق من الاتصال:
   SELECT 1 FROM support_tickets LIMIT 1;
   
2. أعد استيراد:
   SOURCE database/support_system.sql;
   
3. تحقق من الأذونات:
   GRANT ALL ON ibdaa_platform.* TO 'your_user'@'localhost';
```

---

## 📞 الدعم

إذا واجهت مشكلة:

1. ✅ راجع INTEGRATION_COMPLETE_REPORT.md
2. ✅ افحص Console في المتصفح
3. ✅ تحقق من error_log في PHP
4. ✅ اختبر APIs مباشرةً باستخدام curl/PowerShell

---

## 🎯 نصائح الأداء

### تحسين البريد الإلكتروني:
```php
// استخدم Queue بدلاً من الإرسال الفوري
// أضف في cron:
php cron_send_emails.php
```

### تحسين الصور:
```php
// معالجة Batch للعلامات المائية
// أضف في cron:
php process_watermarks.php
```

### Caching للإعلانات:
```javascript
// Cache API responses
const cache = {};
if (cache[cacheKey]) {
    return cache[cacheKey];
}
cache[cacheKey] = await fetch(...);
```

---

**✨ نظامك جاهز للاستخدام! استمتع بالتجربة المتكاملة. 🚀**
