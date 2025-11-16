# 🚀 استيراد قاعدة البيانات - خطوة بخطوة

## المشكلة: الاستيراد لا يكتمل؟

### الحل: تقسيم الاستيراد إلى خطوات صغيرة

---

## 📁 الملفات المقسمة:

### الخطوة 1: إنشاء قاعدة البيانات
**الملف:** `STEP1_create_database.sql`
```sql
CREATE DATABASE IF NOT EXISTS `ibdaa_taiz`;
USE `ibdaa_taiz`;
```

### الخطوة 2: إنشاء الجداول
**الملف:** `exams_grades_schema.sql`
- يحتوي على 6 جداول
- 2 views
- 1 trigger

### الخطوة 3: إدخال البيانات التجريبية
**الملف:** `test_data.sql`
- 5 طلاب
- 3 دورات
- 2 اختبار

---

## 🔧 إذا فشل الاستيراد:

### الطريقة البديلة - استيراد يدوي:

1. **افتح phpMyAdmin**
2. **اذهب إلى SQL**
3. **انسخ والصق هذا الكود:**

```sql
-- إنشاء قاعدة البيانات
CREATE DATABASE IF NOT EXISTS `ibdaa_taiz`;
USE `ibdaa_taiz`;
```

4. **اضغط Go**

5. **اختر قاعدة البيانات `ibdaa_taiz` من القائمة اليسرى**

6. **اذهب إلى تبويب Import**

7. **استورد الملفات بالترتيب:**
   - ❌ لا تستورد `exams_grades_schema.sql` إذا كان كبيراً
   - ✅ بدلاً من ذلك، استخدم الطريقة اليدوية أدناه

---

## ✅ الطريقة المضمونة - استيراد يدوي للجداول:

### افتح phpMyAdmin → SQL → انسخ والصق كل قسم على حدة:

### القسم 1: جدول الاختبارات

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `exams` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `course_id` INT NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `description` TEXT,
    `duration_minutes` INT NOT NULL DEFAULT 60,
    `total_marks` INT NOT NULL DEFAULT 100,
    `passing_percentage` DECIMAL(5,2) NOT NULL DEFAULT 50.00,
    `start_time` DATETIME NULL,
    `end_time` DATETIME NULL,
    `created_by` INT NOT NULL,
    `settings` JSON,
    `status` ENUM('draft', 'published', 'archived') DEFAULT 'draft',
    `published_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

### القسم 2: جدول الأسئلة

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `exam_questions` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `question_type` ENUM('mcq', 'true_false', 'short_answer', 'essay', 'fill_blank') NOT NULL,
    `question_text` TEXT NOT NULL,
    `options` JSON NULL,
    `correct_answer` TEXT NULL,
    `marks` DECIMAL(5,2) NOT NULL DEFAULT 1.00,
    `order_num` INT NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

### القسم 3: جدول المحاولات

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `exam_attempts` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `exam_id` INT NOT NULL,
    `student_id` INT NOT NULL,
    `start_time` DATETIME NOT NULL,
    `end_time` DATETIME NULL,
    `status` ENUM('in_progress', 'submitted', 'graded') DEFAULT 'in_progress',
    `score` DECIMAL(5,2) NULL,
    `percentage` DECIMAL(5,2) NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

### القسم 4: جدول الإجابات

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `exam_answers` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT NOT NULL,
    `question_id` INT NOT NULL,
    `student_answer` TEXT NULL,
    `is_correct` BOOLEAN NULL,
    `marks_awarded` DECIMAL(5,2) NULL,
    `graded_by` INT NULL,
    `graded_at` DATETIME NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

### القسم 5: جدول منع الغش

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `exam_anti_cheat_log` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT NOT NULL,
    `event_type` VARCHAR(50) NOT NULL,
    `event_data` JSON NULL,
    `severity` ENUM('low', 'medium', 'high') DEFAULT 'medium',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

### القسم 6: جدول الدرجات

```sql
USE `ibdaa_taiz`;

CREATE TABLE IF NOT EXISTS `student_grades` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `student_id` INT NOT NULL,
    `course_id` INT NOT NULL,
    `component` VARCHAR(100) NOT NULL,
    `value` DECIMAL(5,2) NOT NULL,
    `max_value` DECIMAL(5,2) NOT NULL,
    `weight` DECIMAL(3,2) NOT NULL,
    `entered_by` INT NOT NULL,
    `entered_at` DATETIME NOT NULL,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**اضغط Go** ✅

---

## ✅ التحقق:

```sql
SHOW TABLES;
```

يجب أن ترى:
- exams
- exam_questions
- exam_attempts
- exam_answers
- exam_anti_cheat_log
- student_grades

---

## 🎉 بعد إنشاء الجداول:

الآن يمكنك استخدام الواجهات:
- `Manager/exam_interface.html?exam_id=1`
- `Manager/components/student_grades_widget.html`
- `Manager/grades_entry.html`

---

**💡 نصيحة:** إذا كان الاستيراد يتوقف عند نسبة معينة، استخدم الطريقة اليدوية أعلاه!
