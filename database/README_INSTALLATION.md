# 📦 تعليمات تثبيت قاعدة البيانات

## ✅ تم الحل! الملفات محدّثة

الملفات الآن تحتوي على:
- ✅ `CREATE DATABASE IF NOT EXISTS ibdaa_taiz` - إنشاء تلقائي
- ✅ `USE ibdaa_taiz` - اختيار قاعدة البيانات

**لا حاجة لإنشاء قاعدة البيانات يدوياً!**

---

## 🚀 طريقة التثبيت (خياران)

### الخيار 1: استيراد الملفات (الأسهل) ⭐

1. **افتح phpMyAdmin**
   ```
   http://localhost/phpmyadmin
   ```

2. **استورد الملف الأول (Schema)**
   - اذهب إلى تبويب **Import**
   - اضغط **Choose File**
   - اختر: `exams_grades_schema.sql`
   - اضغط **Go**
   - انتظر رسالة: ✅ "Import has been successfully finished"

3. **استورد الملف الثاني (Test Data)**
   - اذهب إلى تبويب **Import** مرة أخرى
   - اضغط **Choose File**
   - اختر: `test_data.sql`
   - اضغط **Go**
   - انتظر رسالة: ✅ "Import has been successfully finished"

---

### الخيار 2: نسخ ولصق

1. **افتح phpMyAdmin** → تبويب **SQL**

2. **افتح `exams_grades_schema.sql` في Notepad**
   - اضغط **Ctrl+A** (تحديد الكل)
   - اضغط **Ctrl+C** (نسخ)

3. **في phpMyAdmin:**
   - الصق في نافذة SQL (Ctrl+V)
   - اضغط **Go**

4. **كرر نفس الخطوات لـ `test_data.sql`**

---

## ✅ التحقق من النجاح

بعد التثبيت، شغّل هذا الاستعلام في phpMyAdmin:

```sql
-- عرض الجداول الجديدة
SHOW TABLES LIKE 'exam%';

-- عرض عدد الطلاب التجريبيين
SELECT COUNT(*) FROM users WHERE role = 'student';

-- عرض البيانات التجريبية
SELECT 
    e.title AS 'Exam',
    COUNT(eq.id) AS 'Questions',
    e.status
FROM exams e
LEFT JOIN exam_questions eq ON e.id = eq.exam_id
GROUP BY e.id;
```

**النتيجة المتوقعة:**
```
✅ 6 جداول جديدة (exams, exam_questions, ...)
✅ 5 طلاب تجريبيين
✅ 2 اختبار مع 17 سؤال
```

---

## ❌ لن تواجه مشكلة "No database selected"

✅ **تم الحل!** الملفات الآن تُنشئ قاعدة البيانات تلقائياً!

الأوامر المضافة في كل ملف:
```sql
CREATE DATABASE IF NOT EXISTS `ibdaa_taiz`;
USE `ibdaa_taiz`;
```

**لا حاجة لأي خطوات إضافية!** فقط استورد الملفات مباشرة.

---

## 📁 ترتيب التثبيت الصحيح

```
1. exams_grades_schema.sql   ← أولاً (إنشاء الجداول)
2. test_data.sql              ← ثانياً (إدخال البيانات)
```

**⚠️ مهم:** لا تعكس الترتيب!

---

## 🔍 استعلامات مفيدة

### عرض جميع الاختبارات:
```sql
SELECT * FROM exams;
```

### عرض الدرجات التجريبية:
```sql
SELECT * FROM student_grades_summary;
```

### عرض سجل منع الغش:
```sql
SELECT * FROM exam_anti_cheat_log;
```

### حذف البيانات التجريبية (إذا أردت البدء من جديد):
```sql
-- احذر! هذا سيمسح كل البيانات
DELETE FROM exam_anti_cheat_log;
DELETE FROM exam_answers;
DELETE FROM exam_attempts;
DELETE FROM exam_questions;
DELETE FROM exams;
DELETE FROM student_grades;
DELETE FROM enrollments WHERE student_id IN (
    SELECT id FROM users WHERE username LIKE 'student%'
);
DELETE FROM users WHERE username LIKE 'student%';
DELETE FROM courses WHERE name IN (
    'دورة البرمجة المتقدمة', 
    'التصميم الجرافيكي', 
    'التسويق الرقمي'
);
```

---

## 🎉 بعد التثبيت الناجح

يمكنك الآن:

1. ✅ اختبار واجهة الطالب:
   ```
   Manager/components/student_grades_widget.html
   ```

2. ✅ اختبار واجهة المشرفين:
   ```
   Manager/grades_entry.html
   ```

3. ✅ اختبار الاختبار:
   ```
   Manager/exam_interface.html?exam_id=1
   ```

---

## 📞 هل تحتاج مساعدة؟

راجع الأدلة:
- ✅ `QUICK_TEST_GUIDE.md` - اختبار سريع (5 دقائق)
- ✅ `EXAMS_GRADES_COMPLETE_GUIDE.md` - دليل شامل
- ✅ `EXAMS_GRADES_COMPLETION_REPORT.md` - تقرير الإنجاز

---

**✨ الآن يمكنك البدء!**
