# 📢 نظام الإعلانات والإشعارات المتطور
## Enhanced Announcements & Real-time Notifications System

**تاريخ التطوير:** 20 نوفمبر 2025  
**الحالة:** ✅ جاهز للإنتاج  
**المطور:** AI Assistant

---

## 🎯 ملخص التطويرات

### 1. نظام الإعلانات المطور (Announcements System)

#### ✨ الميزات الجديدة:

**دعم الوسائط المتعددة:**
- ✅ رفع الصور (JPG, PNG, GIF, WEBP) - حد أقصى 5MB
- ✅ رفع الفيديوهات (MP4, MOV, AVI) - حد أقصى 50MB
- ✅ عرض الوسائط في الموقع العام ولوحة التحكم

**إدارة متقدمة:**
- ✅ تثبيت الإعلانات (Pinning) في الأعلى
- ✅ تحديد أولوية الإعلان (منخفضة, متوسطة, عالية, عاجلة)
- ✅ تحديد تاريخ انتهاء الإعلان
- ✅ التحكم في عرض الإعلان على الموقع العام
- ✅ تتبع عدد المشاهدات والتفاعل

**قاعدة البيانات:**
```sql
-- أعمدة جديدة في جدول announcements
image_path VARCHAR(255)           -- مسار الصورة
video_path VARCHAR(255)           -- مسار الفيديو
media_type ENUM('none', 'image', 'video')
display_on_website TINYINT(1)    -- عرض في الموقع
is_pinned TINYINT(1)              -- تثبيت الإعلان
expires_at DATETIME               -- تاريخ الانتهاء
priority ENUM('low','medium','high','urgent')
```

---

### 2. نظام الإشعارات الفورية (Real-time Notifications)

#### ✨ الميزات الجديدة:

**قنوات الإرسال المتعددة:**
- ✅ **WebSocket** - إشعارات فورية داخل التطبيق
- ✅ **البريد الإلكتروني** - إرسال عبر SMTP
- ✅ **WhatsApp** - دعم APIs مثل UltraMsg, Twilio
- ✅ **إشعارات المتصفح** (Browser Push Notifications)

**إدارة الإشعارات:**
- ✅ تصنيف الإشعارات (نظام, إعلان, دورة, درجة, دفع, محادثة)
- ✅ أولويات متعددة (منخفضة, عادية, عالية, عاجلة)
- ✅ ربط الإشعار بإجراء (Action URL)
- ✅ تخصيص الأيقونة واللون لكل إشعار
- ✅ تتبع حالة التسليم (معلق, مرسل, فشل, مقروء)

**تفضيلات المستخدم:**
- ✅ تفعيل/تعطيل إشعارات البريد
- ✅ تفعيل/تعطيل إشعارات WhatsApp
- ✅ تفعيل/تعطيل الإشعارات الفورية
- ✅ تفضيلات خاصة لكل نوع إشعار
- ✅ أوقات الهدوء (Quiet Hours)

**قاعدة البيانات الجديدة:**
```sql
-- جداول جديدة
user_notification_preferences     -- تفضيلات المستخدمين
notification_delivery_log          -- سجل التسليم
websocket_connections              -- الاتصالات النشطة
whatsapp_config                    -- إعدادات WhatsApp
notification_templates             -- قوالب الرسائل
```

---

## 📁 هيكل الملفات الجديدة

```
Ibdaa-Taiz/
├── Manager/
│   ├── api/
│   │   ├── announcements_enhanced.php      ✅ جديد - API الإعلانات المطور
│   │   └── notifications_realtime.php      ✅ جديد - API الإشعارات الفورية
│   └── assets/
│       └── js/
│           └── notification_client.js      ✅ جديد - عميل WebSocket
│
├── websocket_server.php                    ✅ جديد - خادم WebSocket
├── composer_websocket.json                 ✅ جديد - تبعيات WebSocket
│
└── database/
    └── upgrade_announcements_notifications.sql  ✅ جديد - تحديثات قاعدة البيانات
```

---

## 🚀 دليل التثبيت والتشغيل

### الخطوة 1: تحديث قاعدة البيانات

```bash
# تطبيق التحديثات على قاعدة البيانات
cd c:\xampp\htdocs\Ibdaa-Taiz
Get-Content database\upgrade_announcements_notifications.sql | c:\xampp\mysql\bin\mysql.exe -u root ibdaa_platform
```

**ملاحظة:** إذا كان الجدول `announcements` غير موجود، قم بإنشائه أولاً:
```sql
CREATE TABLE announcements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_by INT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

---

### الخطوة 2: تثبيت مكتبة WebSocket (Ratchet)

```bash
cd c:\xampp\htdocs\Ibdaa-Taiz

# تثبيت Composer إذا لم يكن مثبتاً
# تحميل من: https://getcomposer.org/download/

# تثبيت Ratchet
composer require cboden/ratchet
```

---

### الخطوة 3: تشغيل خادم WebSocket

```bash
# تشغيل الخادم في نافذة Terminal منفصلة
cd c:\xampp\htdocs\Ibdaa-Taiz
php websocket_server.php

# يجب أن ترى:
# ✅ WebSocket Server Initialized
# 📡 Listening on ws://localhost:8080
```

**ملاحظة:** يجب إبقاء هذا Terminal مفتوحاً طوال فترة عمل النظام.

---

### الخطوة 4: إعداد WhatsApp API (اختياري)

#### استخدام UltraMsg:

1. اذهب إلى: https://ultramsg.com
2. أنشئ حساباً واحصل على API Key
3. قم بتحديث الإعدادات في قاعدة البيانات:

```sql
UPDATE whatsapp_config 
SET api_key = 'YOUR_API_KEY_HERE',
    phone_number_id = 'YOUR_INSTANCE_ID',
    is_active = 1
WHERE id = 1;
```

#### استخدام Twilio:

```sql
UPDATE whatsapp_config 
SET provider = 'twilio',
    api_key = 'YOUR_ACCOUNT_SID',
    api_secret = 'YOUR_AUTH_TOKEN',
    phone_number_id = '+967XXXXXXXXX',
    is_active = 1
WHERE id = 1;
```

---

### الخطوة 5: ربط الواجهة بالنظام الجديد

#### في `dashboard.php` أضف:

```html
<!-- في <head> -->
<script src="assets/js/notification_client.js"></script>

<!-- في <body> أضف data-user-id -->
<body data-user-id="<?php echo $_SESSION['user_id']; ?>">
```

#### تحديث API calls:

استبدل:
```javascript
fetch('api/manage_announcements.php')
```

بـ:
```javascript
fetch('api/announcements_enhanced.php')
```

استبدل:
```javascript
fetch('api/get_notifications.php')
```

بـ:
```javascript
fetch('api/notifications_realtime.php')
```

---

## 📚 دليل الاستخدام - للمطورين

### 1. إنشاء إعلان مع صورة

```javascript
// رفع الصورة أولاً
const formData = new FormData();
formData.append('media', imageFile);
formData.append('media_type', 'image');

const uploadResponse = await fetch('api/announcements_enhanced.php?action=upload_media', {
    method: 'POST',
    body: formData
});

const uploadData = await uploadResponse.json();

// ثم إنشاء الإعلان
const announcementData = new FormData();
announcementData.append('action', 'create');
announcementData.append('title', 'إعلان مهم');
announcementData.append('content', 'محتوى الإعلان...');
announcementData.append('priority', 'high');
announcementData.append('is_pinned', 1);
announcementData.append('display_on_website', 1);
announcementData.append('media_type', 'image');
announcementData.append('image_path', uploadData.path);

const response = await fetch('api/announcements_enhanced.php', {
    method: 'POST',
    body: announcementData
});
```

---

### 2. إرسال إشعار مع اختيار قنوات الإرسال

```javascript
const notificationData = {
    action: 'send',
    user_id: 123,
    message: 'تم رصد درجتك في الاختبار النهائي',
    type: 'grade',
    priority: 'high',
    action_url: 'grades.php?exam_id=45',
    icon: 'award',
    color: 'green',
    send_email: true,      // إرسال عبر البريد
    send_whatsapp: true    // إرسال عبر WhatsApp
};

const response = await fetch('api/notifications_realtime.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(notificationData)
});
```

---

### 3. الاتصال بـ WebSocket من الواجهة

```javascript
// الاتصال يتم تلقائياً عند تحميل الصفحة
// يمكنك التحكم فيه يدوياً:

// الاتصال
window.notificationClient.connect();

// قطع الاتصال
window.notificationClient.disconnect();

// وضع علامة مقروء
window.notificationClient.markAsRead(notificationId);

// الاستماع للإشعارات الجديدة
window.notificationClient.onNotification = (notification) => {
    console.log('إشعار جديد:', notification);
    // عرض في الواجهة
    addNotificationToUI(notification);
};
```

---

### 4. إدارة تفضيلات الإشعارات

```javascript
// جلب التفضيلات
const prefs = await fetch('api/notifications_realtime.php?action=get_preferences')
    .then(res => res.json());

// تحديث التفضيلات
const updateData = {
    action: 'update_preferences',
    email_enabled: 1,
    whatsapp_enabled: 0,
    push_enabled: 1
};

await fetch('api/notifications_realtime.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(updateData)
});
```

---

## 🔐 الصلاحيات

### نظام الإعلانات:
- ✅ **Manager + Technical:** إنشاء/تعديل/حذف الإعلانات
- ✅ **Trainer:** عرض الإعلانات فقط
- ✅ **Student:** عرض الإعلانات العامة فقط

### نظام الإشعارات:
- ✅ **Manager + Technical:** إرسال إشعارات لأي مستخدم
- ✅ **Trainer:** إرسال إشعارات لطلابه فقط (يحتاج تطوير)
- ✅ **جميع المستخدمين:** استقبال وإدارة إشعاراتهم الخاصة

---

## 📊 تقارير وإحصائيات

### عرض إحصائيات الإشعارات:

```sql
-- إحصائيات يومية
SELECT * FROM notification_stats 
WHERE date >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
ORDER BY date DESC;

-- نسبة القراءة
SELECT 
    notification_type,
    COUNT(*) as total,
    SUM(is_read) as read_count,
    ROUND(SUM(is_read) * 100.0 / COUNT(*), 2) as read_rate
FROM notifications
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
GROUP BY notification_type;

-- حالة التسليم
SELECT 
    channel,
    status,
    COUNT(*) as count
FROM notification_delivery_log
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
GROUP BY channel, status;
```

---

## 🎨 واجهة المستخدم المقترحة

### 1. صفحة إدارة الإعلانات (`announcements_management.php`)

```html
<div class="announcement-form">
    <input type="text" name="title" placeholder="عنوان الإعلان">
    <textarea name="content" placeholder="محتوى الإعلان"></textarea>
    
    <!-- رفع الوسائط -->
    <div class="media-upload">
        <label>
            <input type="radio" name="media_type" value="none" checked> بدون وسائط
        </label>
        <label>
            <input type="radio" name="media_type" value="image"> صورة
            <input type="file" name="image" accept="image/*" class="hidden">
        </label>
        <label>
            <input type="radio" name="media_type" value="video"> فيديو
            <input type="file" name="video" accept="video/*" class="hidden">
        </label>
    </div>
    
    <!-- خيارات متقدمة -->
    <select name="priority">
        <option value="low">أولوية منخفضة</option>
        <option value="medium" selected>أولوية متوسطة</option>
        <option value="high">أولوية عالية</option>
        <option value="urgent">عاجل</option>
    </select>
    
    <label>
        <input type="checkbox" name="is_pinned"> تثبيت في الأعلى
    </label>
    
    <label>
        <input type="checkbox" name="display_on_website" checked> عرض في الموقع العام
    </label>
    
    <input type="datetime-local" name="expires_at" placeholder="تاريخ الانتهاء (اختياري)">
    
    <button type="submit">نشر الإعلان</button>
</div>
```

---

### 2. مركز الإشعارات (`notifications_center.php`)

```html
<div class="notifications-center">
    <!-- الفلاتر -->
    <div class="filters">
        <button data-filter="all">الكل</button>
        <button data-filter="unread">غير المقروءة</button>
        <button data-filter="announcement">الإعلانات</button>
        <button data-filter="course">الدورات</button>
        <button data-filter="grade">الدرجات</button>
    </div>
    
    <!-- قائمة الإشعارات -->
    <div class="notifications-list">
        <!-- يتم ملؤها ديناميكياً -->
    </div>
    
    <!-- التفضيلات -->
    <div class="preferences">
        <h3>إعدادات الإشعارات</h3>
        <label>
            <input type="checkbox" name="email_enabled"> إشعارات البريد الإلكتروني
        </label>
        <label>
            <input type="checkbox" name="whatsapp_enabled"> إشعارات WhatsApp
        </label>
        <label>
            <input type="checkbox" name="push_enabled"> الإشعارات الفورية
        </label>
    </div>
</div>
```

---

## 🐛 استكشاف الأخطاء

### WebSocket لا يتصل:

```bash
# 1. تأكد من تشغيل الخادم
php websocket_server.php

# 2. تحقق من الـ Port
netstat -an | findstr "8080"

# 3. تحقق من Firewall
# افتح Port 8080 في Windows Firewall
```

### WhatsApp لا يرسل:

```sql
-- 1. تحقق من الإعدادات
SELECT * FROM whatsapp_config WHERE is_active = 1;

-- 2. تحقق من سجل الأخطاء
SELECT * FROM notification_delivery_log 
WHERE channel = 'whatsapp' AND status = 'failed'
ORDER BY created_at DESC LIMIT 10;
```

### الإشعارات لا تظهر:

```javascript
// 1. تحقق من الاتصال
console.log(window.notificationClient.isConnected);

// 2. تحقق من أذونات المتصفح
console.log(Notification.permission);

// 3. طلب الأذونات
Notification.requestPermission().then(console.log);
```

---

## 📈 التطويرات المستقبلية المقترحة

### 1. إشعارات جماعية محسّنة:
- إرسال إشعار لكل الطلاب في دورة معينة
- إرسال إشعار لكل الطلاب في محافظة معينة
- جدولة الإشعارات المستقبلية

### 2. قوالب الإشعارات الذكية:
- استخدام AI لتوليد محتوى الإشعارات
- قوالب متعددة اللغات (عربي/إنجليزي)
- تخصيص القوالب بناءً على نوع المستخدم

### 3. تحليلات متقدمة:
- معدل فتح الإشعارات (Open Rate)
- معدل التفاعل (Click-through Rate)
- أفضل أوقات الإرسال

### 4. تكامل مع خدمات خارجية:
- Firebase Cloud Messaging (FCM)
- OneSignal
- Pusher

---

## 🏆 الخلاصة

تم تطوير نظامي الإعلانات والإشعارات بشكل كامل ليشمل:

✅ **نظام إعلانات متقدم** مع دعم الصور والفيديوهات  
✅ **إشعارات فورية** عبر WebSocket  
✅ **إشعارات متعددة القنوات** (Email + WhatsApp + Push)  
✅ **إدارة تفضيلات مستخدمين**  
✅ **تتبع حالة التسليم**  
✅ **قوالب رسائل جاهزة**  

---

**تاريخ الإنجاز:** 20 نوفمبر 2025  
**المطور:** AI Assistant  
**الحالة:** ✅ جاهز للإنتاج
