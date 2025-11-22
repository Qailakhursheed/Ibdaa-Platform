# 🚀 دليل التشغيل السريع - Quick Start Guide

## تشغيل نظام الإشعارات الفورية

### الطريقة 1: استخدام ملف BAT (Windows)

```bash
# انقر نقراً مزدوجاً على:
start_websocket.bat
```

### الطريقة 2: استخدام PowerShell

```powershell
cd c:\xampp\htdocs\Ibdaa-Taiz
php websocket_server.php
```

### الطريقة 3: استخدام XAMPP Control Panel

1. افتح XAMPP Control Panel
2. اذهب إلى Shell
3. نفذ الأمر:
```bash
cd c:/xampp/htdocs/Ibdaa-Taiz
php websocket_server.php
```

---

## ✅ التحقق من عمل النظام

### 1. تحقق من قاعدة البيانات:

```sql
-- تحقق من الجداول الجديدة
SHOW TABLES LIKE '%notification%';
SHOW TABLES LIKE '%websocket%';

-- اختبر إنشاء إشعار
INSERT INTO notifications (user_id, message, notification_type, priority)
VALUES (1, 'اختبار النظام', 'system', 'normal');
```

### 2. تحقق من WebSocket:

افتح المتصفح واذهب إلى Console (F12)، ثم:

```javascript
// اختبر الاتصال
const ws = new WebSocket('ws://localhost:8080?user_id=1');

ws.onopen = () => console.log('✅ متصل');
ws.onmessage = (e) => console.log('📩 رسالة:', e.data);
ws.onerror = (e) => console.log('❌ خطأ:', e);
```

### 3. اختبر API الإعلانات:

```bash
# اختبر رفع صورة (استخدم Postman أو curl)
curl -X POST http://localhost/Ibdaa-Taiz/Manager/api/announcements_enhanced.php?action=upload_media \
  -F "media=@image.jpg" \
  -F "media_type=image"
```

---

## 🔧 حل المشاكل الشائعة

### المشكلة: "Port 8080 already in use"

**الحل:**
```bash
# إيقاف العملية المستخدمة للـ Port
netstat -ano | findstr :8080
taskkill /PID [PID_NUMBER] /F

# أو غير الـ Port في websocket_server.php:
$port = 8081; // بدلاً من 8080
```

### المشكلة: "Class 'Ratchet\Server\IoServer' not found"

**الحل:**
```bash
# أعد تثبيت Composer dependencies
cd c:\xampp\htdocs\Ibdaa-Taiz
composer install
```

### المشكلة: WhatsApp لا يرسل

**الحل:**
```sql
-- تحديث إعدادات WhatsApp
UPDATE whatsapp_config 
SET api_key = 'YOUR_ACTUAL_KEY',
    phone_number_id = 'YOUR_INSTANCE',
    is_active = 1;
```

---

## 📱 اختبار WhatsApp API

### UltraMsg:

```php
<?php
// test_whatsapp.php
require_once 'database/db.php';

$phone = '967XXXXXXXXX'; // رقم الهاتف بصيغة دولية
$message = 'اختبار نظام الإشعارات من منصة إبداع';

$config = $conn->query("SELECT * FROM whatsapp_config WHERE is_active = 1")->fetch_assoc();

$apiUrl = "https://api.ultramsg.com/{$config['phone_number_id']}/messages/chat";

$data = [
    'token' => $config['api_key'],
    'to' => $phone,
    'body' => $message
];

$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
curl_close($ch);

echo "Response: " . $response;
?>
```

---

## 📊 مراقبة النظام

### سجلات WebSocket:

```bash
# ستظهر في Terminal عند تشغيل الخادم:
✅ User 123 connected
📤 Notification sent to User 123
❌ User 456 disconnected
```

### سجلات قاعدة البيانات:

```sql
-- الاتصالات النشطة
SELECT * FROM websocket_connections WHERE is_active = 1;

-- سجل التسليم
SELECT * FROM notification_delivery_log 
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR)
ORDER BY created_at DESC;

-- الإشعارات غير المقروءة
SELECT u.full_name, COUNT(*) as unread_count
FROM notifications n
JOIN users u ON n.user_id = u.id
WHERE n.is_read = 0
GROUP BY u.id;
```

---

## 🎯 الخطوات التالية

1. ✅ تشغيل WebSocket Server
2. ✅ تحديث dashboard.php لتضمين `notification_client.js`
3. ✅ إعداد WhatsApp API (إذا لزم الأمر)
4. ✅ اختبار إرسال إشعار
5. ✅ اختبار رفع إعلان مع صورة
6. ✅ تدريب المستخدمين على النظام الجديد

---

## 📞 الدعم

لأي استفسارات أو مشاكل، راجع:
- `ANNOUNCEMENTS_NOTIFICATIONS_UPGRADE_GUIDE.md` - الدليل الشامل
- `database/upgrade_announcements_notifications.sql` - تحديثات قاعدة البيانات
- سجلات الأخطاء في PHP و MySQL

---

**تم بنجاح!** 🎉
