# 📥 تعليمات استيراد قاعدة البيانات

**التاريخ:** 2025-11-12  
**قاعدة البيانات:** ibdaa_taiz (موحدة)

---

## 🎯 الخطوة 1: استيراد القاعدة الموحدة

### طريقة 1: عبر phpMyAdmin (الأسهل) ✅

1. **افتح phpMyAdmin:**
   ```
   http://localhost/phpmyadmin
   ```

2. **إنشاء القاعدة (إذا لم تكن موجودة):**
   - اضغط على "New" في القائمة اليسرى
   - اسم القاعدة: `ibdaa_taiz`
   - Collation: `utf8mb4_unicode_ci`
   - اضغط "Create"

3. **استيراد الملف:**
   - اختر قاعدة بيانات `ibdaa_taiz`
   - اضغط على تبويب "Import"
   - اضغط "Choose File"
   - اختر: `database/UNIFIED_DATABASE.sql`
   - اضغط "Go"

4. **التحقق من النجاح:**
   - يجب أن تشاهد: "Import has been successfully finished"
   - عدد الجداول المتوقع: 12+ جدول

---

### طريقة 2: عبر سطر الأوامر (للمحترفين)

```bash
# في PowerShell:
cd C:\xampp\mysql\bin

# إنشاء القاعدة:
.\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS ibdaa_taiz CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# استيراد الملف:
.\mysql.exe -u root ibdaa_taiz < C:\xampp\htdocs\Ibdaa-Taiz\database\UNIFIED_DATABASE.sql

# التحقق:
.\mysql.exe -u root -e "USE ibdaa_taiz; SHOW TABLES;"
```

---

## 🧪 الخطوة 2: اختبار قاعدة البيانات

### اختبار 1: التحقق من الجداول

```sql
-- في phpMyAdmin -> SQL tab:
USE ibdaa_taiz;
SHOW TABLES;
```

**النتيجة المتوقعة:**
```
+------------------------+
| Tables_in_ibdaa_taiz   |
+------------------------+
| announcements          |
| attendance             |
| certificates           |
| chats                  |
| courses                |
| enrollments            |
| exam_answers           |
| exam_anti_cheat_log    |
| exam_attempts          |
| exam_questions         |
| exams                  |
| notifications          |
| student_grades         |
| users                  |
+------------------------+
```

---

### اختبار 2: التحقق من البيانات التجريبية

```sql
-- عدد المستخدمين:
SELECT COUNT(*) as total_users FROM users;

-- الطلاب:
SELECT COUNT(*) as students FROM users WHERE role = 'student';

-- الدورات:
SELECT COUNT(*) as courses FROM courses;

-- الاختبارات:
SELECT COUNT(*) as exams FROM exams;
```

---

## 🔍 الخطوة 3: اختبار الملفات المصلحة

### اختبار API 1: student_courses.php

**الطريقة:**
1. سجل دخول كطالب في:
   ```
   http://localhost/Ibdaa-Taiz/platform/login.php
   ```

2. ثم اذهب إلى:
   ```
   http://localhost/Ibdaa-Taiz/Manager/api/student_courses.php?action=list
   ```

**النتيجة المتوقعة:**
```json
{
  "success": true,
  "courses": [...]
}
```

---

### اختبار API 2: student_grades.php

```
http://localhost/Ibdaa-Taiz/Manager/api/student_grades.php?action=list
```

**النتيجة المتوقعة:**
```json
{
  "success": true,
  "grades": [...]
}
```

---

### اختبار API 3: student_attendance.php

```
http://localhost/Ibdaa-Taiz/Manager/api/student_attendance.php?action=list
```

---

## 🧪 اختبار شامل للنظام

### 1. اختبار تسجيل الدخول

**للمنصة الخارجية:**
```
http://localhost/Ibdaa-Taiz/platform/login.php
```

**لوحة التحكم:**
```
http://localhost/Ibdaa-Taiz/Manager/login.php
```

**بيانات التجربة (إن وجدت):**
- Email: `student@test.com`
- Password: `123456`

---

### 2. اختبار نظام الاختبارات

```
http://localhost/Ibdaa-Taiz/Manager/exam_interface.html
```

**يجب أن يظهر:**
- ✅ واجهة إنشاء اختبار
- ✅ 4 أنواع أسئلة
- ✅ إعدادات منع الغش

---

### 3. اختبار نظام الدرجات

```
http://localhost/Ibdaa-Taiz/Manager/grades_entry.html
```

**يجب أن يظهر:**
- ✅ جدول إدخال الدرجات
- ✅ حساب تلقائي للدرجة النهائية
- ✅ توليد شهادات تلقائي

---

### 4. اختبار إزالة خلفية الصور

```
http://localhost/Ibdaa-Taiz/Manager/components/photo_upload_widget.html
```

---

## 🐛 استكشاف الأخطاء

### خطأ: "Table doesn't exist"

**الحل:**
```sql
-- تحقق من اسم القاعدة:
SELECT DATABASE();

-- يجب أن يكون: ibdaa_taiz
-- إذا كان مختلفاً، استورد من جديد
```

---

### خطأ: "Access denied for user"

**الحل:**
```sql
-- في MySQL:
GRANT ALL PRIVILEGES ON ibdaa_taiz.* TO 'root'@'localhost';
FLUSH PRIVILEGES;
```

---

### خطأ: "PDO connection failed"

**الحل:**
تحقق من ملف `Manager/config/database.php`:
```php
$host = 'localhost';
$db   = 'ibdaa_taiz'; // ✅ تأكد من هذا
$user = 'root';
$pass = ''; // أو كلمة المرور الخاصة بك
```

---

### خطأ: "Class 'Database' not found"

**الحل:**
تأكد من وجود الملف:
```
Manager/config/database.php
```

إذا لم يكن موجوداً، فقد تحتاج لإنشائه مرة أخرى.

---

## ✅ قائمة التحقق النهائية

- [ ] تم استيراد UNIFIED_DATABASE.sql بنجاح
- [ ] جميع الجداول موجودة (14 جدول)
- [ ] ملف Manager/config/database.php موجود
- [ ] اختبار student_courses.php يعمل
- [ ] اختبار student_grades.php يعمل
- [ ] تسجيل الدخول يعمل
- [ ] نظام الاختبارات يظهر بشكل صحيح
- [ ] نظام الدرجات يظهر بشكل صحيح

---

## 📞 الدعم

إذا واجهت أي مشاكل:

1. **تحقق من أخطاء PHP:**
   ```
   C:\xampp\apache\logs\error.log
   ```

2. **تحقق من أخطاء MySQL:**
   ```
   C:\xampp\mysql\data\*.err
   ```

3. **تفعيل عرض الأخطاء:**
   في أي ملف PHP، أضف في البداية:
   ```php
   ini_set('display_errors', 1);
   error_reporting(E_ALL);
   ```

---

**ملاحظة:** بعد الانتهاء من الاختبار، أخبرني بالنتائج!

✅ نجح  
❌ فشل  
⚠️ تحذير
