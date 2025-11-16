# 🔄 دليل التحويل من JSON إلى MySQL

## الخطوة 1️⃣: إنشاء قاعدة البيانات

### افتح phpMyAdmin
```
http://localhost/phpmyadmin
```

### نفّذ سكريبت SQL
استخدم الملف: `database/schema.sql`

أو نفّذ مباشرة:
```sql
CREATE DATABASE IF NOT EXISTS ibdaa_platform CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE ibdaa_platform;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('student', 'trainer', 'technical', 'manager') DEFAULT 'student',
    governorate VARCHAR(100),
    district VARCHAR(100),
    birth_date DATE,
    photo VARCHAR(255),
    verified TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_email (email),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE course_requests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    student_id INT,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    course VARCHAR(150) NOT NULL,
    governorate VARCHAR(100) NOT NULL,
    district VARCHAR(100),
    id_card VARCHAR(255),
    status ENUM('قيد المراجعة', 'مقبول', 'مرفوض', 'تم الدفع') DEFAULT 'قيد المراجعة',
    fees DECIMAL(10,2) DEFAULT 0,
    note TEXT,
    assigned_by VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (student_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_course (course),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

---

## الخطوة 2️⃣: استبدال الملفات

### ✅ الملفات المُجهزة (NEW)

| الملف القديم | الملف الجديد | الوصف |
|-------------|-------------|-------|
| `platform/apply.php` | تم التعديل ✓ | يحفظ في MySQL بدلاً من JSON |
| `Manager/requests.php` | `Manager/requests_new.php` | يقرأ من MySQL |
| `Manager/updateRequest.php` | `Manager/updateRequest_new.php` | يحدث MySQL |

### 📝 خطوات الاستبدال

#### 1. نسخ احتياطي للملفات القديمة
```powershell
cd C:\xampp\htdocs\Ibdaa-Taiz
mkdir backup_json
copy Manager\requests.php backup_json\
copy Manager\updateRequest.php backup_json\
```

#### 2. استبدال الملفات
```powershell
copy Manager\requests_new.php Manager\requests.php
copy Manager\updateRequest_new.php Manager\updateRequest.php
```

أو **يدوياً:**
1. احذف `Manager/requests.php`
2. أعد تسمية `Manager/requests_new.php` إلى `Manager/requests.php`
3. احذف `Manager/updateRequest.php`
4. أعد تسمية `Manager/updateRequest_new.php` إلى `Manager/updateRequest.php`

---

## الخطوة 3️⃣: نقل البيانات القديمة (اختياري)

إذا كان لديك طلبات في `database/requests.json`، استخدم هذا السكريبت:

```php
<?php
require_once 'database/db.php';

$jsonFile = 'database/requests.json';
if (file_exists($jsonFile)) {
    $requests = json_decode(file_get_contents($jsonFile), true);
    
    foreach ($requests as $req) {
        $stmt = $conn->prepare("INSERT INTO course_requests (full_name, email, phone, course, governorate, district, id_card, status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        $createdAt = $req['date'] ?? date('Y-m-d H:i:s');
        $stmt->bind_param(
            "ssssssssss",
            $req['full_name'],
            $req['email'],
            $req['phone'],
            $req['course'],
            $req['governorate'],
            $req['district'],
            $req['id_card'],
            $req['status'],
            $req['notes'],
            $createdAt
        );
        
        $stmt->execute();
    }
    
    echo "تم نقل " . count($requests) . " طلب بنجاح!";
    
    // نسخ احتياطي للملف القديم
    rename($jsonFile, 'database/requests_backup_' . date('Ymd_His') . '.json');
}
?>
```

احفظه كـ `migrate_json_to_mysql.php` وشغله مرة واحدة.

---

## الخطوة 4️⃣: تثبيت PHPMailer

```powershell
cd C:\xampp\htdocs\Ibdaa-Taiz\platform
composer require phpmailer/phpmailer
```

### إذا لم يكن Composer مثبت:
1. حمّل من: https://getcomposer.org/download/
2. ثبته
3. أعد تشغيل PowerShell
4. نفّذ الأمر أعلاه

---

## الخطوة 5️⃣: إعداد Gmail SMTP

### 1. فعّل المصادقة الثنائية
```
https://myaccount.google.com/security
```

### 2. أنشئ App Password
```
https://myaccount.google.com/apppasswords
```

### 3. عدّل sendMail.php
افتح `Mailer/sendMail.php` واستبدل:
```php
$mail->Password = 'ضع_كلمة_مرور_التطبيق_هنا';
```
بكلمة المرور الجديدة (16 حرف).

---

## ✅ اختبار النظام

### 1. اختبر التقديم
```
http://localhost/Ibdaa-Taiz/platform/courses.html
```
- اختر دورة
- عبئ الاستمارة
- أرسل الطلب
- تحقق من قاعدة البيانات

### 2. اختبر لوحة المدير
```
http://localhost/Ibdaa-Taiz/Manager/requests.php
```
- يجب أن تظهر الطلبات من MySQL
- جرب قبول/رفض طلب
- تحقق من وصول البريد

---

## 📊 المميزات الجديدة

✅ **قاعدة بيانات منظمة** - MySQL بدلاً من JSON  
✅ **أداء أفضل** - استعلامات سريعة ومفهرسة  
✅ **علاقات بين الجداول** - Foreign Keys  
✅ **تتبع التحديثات** - updated_at تلقائي  
✅ **رقم طلب تلقائي** - AUTO_INCREMENT  
✅ **قابل للتوسع** - إضافة جداول جديدة بسهولة  

---

## 🔍 استعلامات مفيدة

### عدد الطلبات حسب الحالة
```sql
SELECT status, COUNT(*) as count 
FROM course_requests 
GROUP BY status;
```

### آخر 10 طلبات
```sql
SELECT * FROM course_requests 
ORDER BY created_at DESC 
LIMIT 10;
```

### الدورات الأكثر طلباً
```sql
SELECT course, COUNT(*) as count 
FROM course_requests 
GROUP BY course 
ORDER BY count DESC;
```

---

## ⚠️ ملاحظات مهمة

1. **لا تحذف** `database/requests.json` قبل نقل البيانات
2. **احتفظ بنسخة احتياطية** من الملفات القديمة
3. **اختبر النظام** قبل الاستخدام الفعلي
4. **كلمة مرور Gmail** يجب أن تكون App Password وليست كلمة المرور العادية

---

## 📞 الدعم

إذا واجهت مشكلة:
1. تحقق من `C:\xampp\apache\logs\error.log`
2. تحقق من اتصال قاعدة البيانات في `database/db.php`
3. تأكد من تشغيل XAMPP (Apache + MySQL)

---

**تم التطوير: أكتوبر 2025**
