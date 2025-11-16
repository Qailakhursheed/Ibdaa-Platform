# دليل الإعداد السريع - منصة إبداع

## 🚀 خطوات الإعداد السريع (5 دقائق)

### الخطوة 1️⃣: إعداد قاعدة البيانات
1. افتح XAMPP Control Panel
2. ابدأ تشغيل **Apache** و **MySQL**
3. افتح متصفح على: `http://localhost/phpmyadmin`
4. اضغط "New" لإنشاء قاعدة بيانات جديدة
5. اسم القاعدة: `ibdaa_platform`
6. Collation: `utf8mb4_unicode_ci`
7. اضغط "Create"
8. اذهب إلى تبويب "SQL" والصق هذا الكود:

```sql
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    birth_date DATE NOT NULL,
    governorate VARCHAR(50) NOT NULL,
    district VARCHAR(100),
    photo_path VARCHAR(255),
    verified TINYINT(1) DEFAULT 0,
    verification_token VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
```

---

### الخطوة 2️⃣: تثبيت PHPMailer
1. افتح **PowerShell**
2. انتقل إلى مجلد المشروع:
```powershell
cd C:\xampp\htdocs\Ibdaa-Taiz\platform
```
3. ثبت PHPMailer:
```powershell
composer require phpmailer/phpmailer
```
*إذا لم يكن Composer مثبت، حمّله من: https://getcomposer.org/download/*

---

### الخطوة 3️⃣: إعداد Gmail للإشعارات
1. اذهب إلى: https://myaccount.google.com/security
2. فعّل "التحقق بخطوتين" (2-Step Verification)
3. اذهب إلى: https://myaccount.google.com/apppasswords
4. اختر **App**: Mail
5. اختر **Device**: Other (اكتب: Ibdaa Platform)
6. اضغط "Generate"
7. انسخ كلمة المرور المكونة من 16 حرف (مثال: `abcd efgh ijkl mnop`)

---

### الخطوة 4️⃣: تعديل إعدادات البريد
1. افتح الملف: `Mailer/sendMail.php`
2. ابحث عن السطر 18:
```php
$mail->Password   = 'ضع_كلمة_المرور_الخاصة_بك_هنا';
```
3. استبدلها بكلمة المرور التي حصلت عليها:
```php
$mail->Password   = 'abcd efgh ijkl mnop';
```
4. احفظ الملف

---

### الخطوة 5️⃣: إنشاء المجلدات المطلوبة
تأكد من وجود المجلدات التالية (ستُنشأ تلقائياً عند أول استخدام، لكن يمكنك إنشاؤها يدوياً):

```
C:\xampp\htdocs\Ibdaa-Taiz\
├── platform\uploads\ids\          (لحفظ بطاقات الهوية)
└── database\                       (لحفظ requests.json)
```

---

## ✅ التحقق من التثبيت

### 1. اختبار الصفحة الرئيسية
افتح المتصفح على: `http://localhost/Ibdaa-Taiz/platform/`

✅ يجب أن تظهر الصفحة الرئيسية بشكل صحيح

### 2. اختبار التسجيل
- اذهب إلى: `http://localhost/Ibdaa-Taiz/platform/signup.php`
- سجل حساب تجريبي
- تحقق من البريد الإلكتروني للتأكيد

### 3. اختبار التقديم على دورة
- اذهب إلى: `http://localhost/Ibdaa-Taiz/platform/courses.html`
- اضغط على أي دورة → "التسجيل في هذه الدورة"
- عبئ الاستمارة وأرسلها
- تحقق من وجود ملف: `database/requests.json`

### 4. اختبار بوابة المدير
- اذهب إلى: `http://localhost/Ibdaa-Taiz/Manager/`
- اضغط "عرض الطلبات"
- يجب أن تظهر الطلبات التي تم تقديمها

---

## 🔥 روابط سريعة

| الصفحة | الرابط |
|--------|--------|
| الصفحة الرئيسية | http://localhost/Ibdaa-Taiz/platform/ |
| التسجيل | http://localhost/Ibdaa-Taiz/platform/signup.php |
| تسجيل الدخول | http://localhost/Ibdaa-Taiz/platform/login.php |
| الدورات | http://localhost/Ibdaa-Taiz/platform/courses.html |
| فريق العمل | http://localhost/Ibdaa-Taiz/platform/staff.html |
| بوابة المدير | http://localhost/Ibdaa-Taiz/Manager/ |
| بوابة الفني | http://localhost/Ibdaa-Taiz/Technical/Portal.html |
| لوحة التحكم الشاملة | http://localhost/Ibdaa-Taiz/Portal.html |

---

## ⚠️ استكشاف الأخطاء الشائعة

### ❌ خطأ: "Call to undefined function mysqli_connect()"
**الحل:** تأكد من تفعيل extension=mysqli في php.ini

### ❌ خطأ: "Class 'PHPMailer' not found"
**الحل:** قم بتشغيل:
```powershell
composer require phpmailer/phpmailer
```

### ❌ خطأ: "SMTP Error: Could not authenticate"
**الحل:** 
1. تأكد من تفعيل المصادقة الثنائية في Gmail
2. تأكد من استخدام App Password وليس كلمة مرور الحساب العادية
3. تأكد من عدم وجود مسافات زائدة في كلمة المرور

### ❌ الصور لا تُرفع
**الحل:**
1. أنشئ المجلد: `platform/uploads/ids/`
2. تأكد من صلاحيات الكتابة على المجلد

---

## 📞 الدعم الفني

إذا واجهت مشكلة:
1. راجع ملف README.md للتفاصيل الكاملة
2. تحقق من error logs في: `C:\xampp\apache\logs\error.log`
3. تواصل مع: ha717781053@gmail.com

---

## 🎉 مبروك!

إذا اجتزت جميع الخطوات، فقد أصبح نظام إدارة منصة إبداع جاهزاً للاستخدام! 🚀

---

**آخر تحديث: يناير 2025**
