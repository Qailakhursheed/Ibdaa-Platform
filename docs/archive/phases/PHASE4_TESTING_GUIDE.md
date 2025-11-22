# 🚀 Quick Start: Testing Smart Import System

## Prerequisites
- XAMPP running (Apache + MySQL)
- PhpSpreadsheet library installed in `vendor/`
- Logged in as Manager or Technical Supervisor

---

## Step 1: Prepare Test Excel File

### Option A: Create Sample Excel
Open Excel and create a file with ANY column names:

| الاسم | البريد الإلكتروني | الهاتف | الدورة | المحافظة | المديرية |
|-------|-------------------|--------|--------|----------|---------|
| أحمد علي | ahmed@test.com | 0771234567 | دورة البرمجة | تعز | الت سلام |
| فاطمة محمد | fatima@test.com | 0777654321 | دورة التصميم | عدن | المنصورة |

Save as `students.xlsx`

### Option B: Use Different Column Names
The system is flexible! Try:

| Name | Email | Phone | Course Name | City | Area |
|------|-------|-------|-------------|------|------|
| Ali Hassan | ali@example.com | 777111222 | Web Development | Taiz | Al-Mudhaffar |

---

## Step 2: Test Step 1 - Upload & Read Headers

### Access Dashboard:
```
http://localhost/Ibdaa-Taiz/Manager/dashboard.php
```

### Navigate to Import:
1. Click "استيراد Excel" in sidebar
2. See new "استيراد ذكي من Excel" interface

### Upload File:
1. Click "اختر ملف Excel أو CSV"
2. Select your `students.xlsx` file
3. Click "قراءة العناوين والمتابعة"

### Expected Result:
✅ Step 1 (upload) disappears  
✅ Step 2 (mapping) appears  
✅ File info shows: "students.xlsx - X صف بيانات"  
✅ Shows all your column names: "الأعمدة الموجودة: الاسم, البريد الإلكتروني, الهاتف, الدورة..."

---

## Step 3: Test Step 2 - Dynamic Mapping

### Check Auto-Mapping:
✅ **Smart detection should auto-map**:
- "الاسم" → اسم الطالب الكامل (green background)
- "البريد" → البريد الإلكتروني (green background)
- "الهاتف" → رقم الهاتف (green background)
- "الدورة" → اسم الدورة (green background)

### Manual Adjustment:
1. If auto-mapping missed something, select from dropdowns
2. Required fields marked with red *:
   - اسم الطالب الكامل *
   - البريد الإلكتروني *
   - اسم الدورة *

### Test Cancel:
1. Click "إلغاء وإعادة الرفع"
2. ✅ Returns to Step 1 (upload interface)

---

## Step 4: Test Step 3 - Execute Import

### Before Testing:
Ensure at least one course exists in database:
```sql
INSERT INTO courses (title, description, category) 
VALUES ('دورة البرمجة', 'دورة تطوير الويب', 'برمجة');
```

### Execute Import:
1. Return to mapping step (re-upload if needed)
2. Verify mappings are correct
3. Click "تنفيذ الاستيراد النهائي"
4. Wait for processing...

### Expected Results:
✅ Step 2 (mapping) disappears  
✅ Step 3 (results) appears  
✅ See statistics:
```
✅ نجح: 2
❌ فشل: 0
📊 إجمالي: 2
```

✅ Success message: "تم استيراد X طالب بنجاح!"

### Check Database:
```sql
-- Verify users created
SELECT id, email, full_name, role FROM users WHERE role = 'student' ORDER BY id DESC LIMIT 5;

-- Verify enrollments
SELECT u.full_name, c.title, e.status 
FROM enrollments e
JOIN users u ON e.user_id = u.id
JOIN courses c ON e.course_id = c.id
ORDER BY e.id DESC LIMIT 5;
```

---

## Step 5: Test Error Handling

### Test 1: Duplicate Email
1. Upload same file again
2. Execute import
3. ✅ Should see:
```
❌ فشل: 2
الأخطاء:
- صف 2: الإيميل موجود مسبقاً (ahmed@test.com)
- صف 3: الإيميل موجود مسبقاً (fatima@test.com)
```

### Test 2: Invalid Email
Create Excel with bad email:
| الاسم | البريد | الدورة |
|-------|--------|--------|
| Test | invalid-email | دورة البرمجة |

✅ Should see: "صف 2: إيميل غير صحيح (invalid-email)"

### Test 3: Missing Course
Create Excel with non-existent course:
| الاسم | البريد | الدورة |
|-------|--------|--------|
| Test | test@x.com | دورة غير موجودة |

✅ Should see: "صف 2: الدورة غير موجودة (دورة غير موجودة)"

### Test 4: Missing Required Fields
Create Excel without mapping required fields:
1. Don't select "اسم الدورة" in mapping
2. Try to execute

✅ Should see: "⚠️ يجب ربط الحقول الإلزامية: الاسم، الإيميل، الدورة"

---

## Step 6: Test Multi-Language Support

### Create English Excel:
| Name | Email | Phone | Course |
|------|-------|-------|--------|
| John Doe | john@test.com | 777888999 | دورة البرمجة |

### Upload and Check:
✅ Auto-mapping should detect:
- "Name" → اسم الطالب الكامل
- "Email" → البريد الإلكتروني
- "Phone" → رقم الهاتف
- "Course" → اسم الدورة

---

## Step 7: Test File Formats

### Test .xlsx:
✅ Upload `students.xlsx` → Works

### Test .csv:
1. Save Excel as CSV
2. Upload `students.csv`
✅ Should work identically

### Test .xls (legacy):
1. Save as Excel 97-2003 (.xls)
2. Upload
✅ Should work

### Test Invalid Format:
1. Try to upload .txt or .pdf
✅ Should reject: "نوع الملف غير مدعوم"

---

## Step 8: Verify File Cleanup

### Check temp directory:
```powershell
Get-ChildItem "c:\xampp\htdocs\Ibdaa-Taiz\uploads\temp"
```

✅ Should be empty after successful import  
✅ Files automatically deleted after processing

---

## Step 9: Test with Large File

### Create Excel with 50+ rows:
```excel
م  | الاسم      | البريد           | الدورة
1  | طالب 1    | s1@test.com      | دورة البرمجة
2  | طالب 2    | s2@test.com      | دورة البرمجة
... (50 rows) ...
```

### Import and Check:
✅ Processing time < 10 seconds  
✅ All 50 rows processed  
✅ Report shows accurate statistics

---

## Step 10: Test Reset Functionality

### After Viewing Results:
1. Click "استيراد ملف جديد"
2. ✅ Returns to Step 1 (upload)
3. ✅ All states reset
4. ✅ Can start new import

---

## Troubleshooting

### Problem: "غير مصرح لك بالوصول"
**Solution**: Ensure you're logged in as manager or technical supervisor

### Problem: "الملف غير موجود"
**Check**:
```powershell
Test-Path "c:\xampp\htdocs\Ibdaa-Taiz\uploads\temp"
```
If false, create directory:
```powershell
New-Item -ItemType Directory -Path "c:\xampp\htdocs\Ibdaa-Taiz\uploads\temp" -Force
```

### Problem: "PhpSpreadsheet not found"
**Check**:
```powershell
Test-Path "c:\xampp\htdocs\Ibdaa-Taiz\vendor\autoload.php"
```
If false, install Composer dependencies:
```bash
cd c:\xampp\htdocs\Ibdaa-Taiz
composer install
```

### Problem: Auto-mapping doesn't work
**Reason**: Column names don't match keywords  
**Solution**: Manually select from dropdowns (this is expected behavior)

### Problem: Import fails with database error
**Check**:
1. Verify courses exist:
   ```sql
   SELECT * FROM courses;
   ```
2. Check database connection in `platform/db.php`

---

## Success Criteria ✅

- [x] Upload interface loads
- [x] File upload triggers header reading
- [x] Mapping interface displays all columns
- [x] Auto-mapping detects matches
- [x] Manual mapping works via dropdowns
- [x] Required field validation works
- [x] Import executes successfully
- [x] Results report shows statistics
- [x] Errors are detailed and helpful
- [x] Database records created correctly
- [x] Temp files cleaned up
- [x] Reset button returns to start

---

## Next Steps

✅ Phase 4 Complete - Smart Import Working!

🚀 **Proceed to Phase 5**:
- Technical Supervisor Permissions
- Student ID Card System with QR codes

---

*Testing Guide for Phase 4 of 6-phase Ibdaa-Taiz Reconstruction Project*
