# إعداد نظام المحادثات
## Setup Messages System

### الطريقة الأولى: تلقائياً عبر المتصفح

1. افتح المتصفح واذهب إلى:
```
http://localhost/Ibdaa-Taiz/database/setup_messages.php
```

2. سيتم إنشاء الجدول تلقائياً مع عرض النتائج

---

### الطريقة الثانية: يدوياً عبر phpMyAdmin

1. افتح phpMyAdmin: `http://localhost/phpmyadmin`
2. اختر قاعدة البيانات الخاصة بك
3. اذهب إلى تبويب SQL
4. انسخ والصق الكود التالي:

```sql
CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_conversation` (`sender_id`, `recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

ALTER TABLE `messages`
  ADD CONSTRAINT `messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `messages_recipient_fk` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
```

5. اضغط على "تنفيذ" / "Go"

---

### الطريقة الثالثة: عبر سطر الأوامر

```bash
mysql -u root -p ibdaa_taiz < database/messages_table_schema.sql
```

---

## التحقق من نجاح الإعداد

بعد تنفيذ أي من الطرق أعلاه:

1. اذهب إلى لوحة تحكم المدير
2. اضغط على "المحادثات" من القائمة الجانبية
3. يجب أن تظهر صفحة المحادثات بدون خطأ "جدول الرسائل غير موجود"

---

## هيكل جدول الرسائل

| العمود | النوع | الوصف |
|--------|------|-------|
| message_id | INT | المعرف الفريد للرسالة |
| sender_id | INT | معرف المرسل (FK → users.id) |
| recipient_id | INT | معرف المستقبل (FK → users.id) |
| message | TEXT | نص الرسالة |
| is_read | TINYINT | حالة القراءة (0 = غير مقروءة، 1 = مقروءة) |
| created_at | TIMESTAMP | تاريخ الإرسال |
| read_at | TIMESTAMP | تاريخ القراءة |

---

## المميزات

✅ دعم المحادثات الثنائية بين المستخدمين
✅ تتبع حالة القراءة
✅ الفهرسة للأداء الأمثل
✅ Foreign Keys لضمان سلامة البيانات
✅ View جاهز للمحادثات الأخيرة

---

## الاستخدام

بعد الإعداد، يمكن للمستخدمين:
- إرسال رسائل للمدربين والطلاب
- عرض المحادثات النشطة
- تتبع الرسائل غير المقروءة
- البحث في السجلات

---

## دعم فني

إذا واجهت أي مشاكل:
1. تأكد من أن جدول `users` موجود
2. تأكد من وجود عمود `id` في جدول users
3. تحقق من صلاحيات المستخدم في MySQL
