# 🎯 Smart Excel Import System - Quick Guide

## What's New?

**Before**: Rigid Excel template required (exact column names, exact order)  
**Now**: Upload ANY Excel file and map columns dynamically! 🚀

---

## How It Works (3 Simple Steps)

### Step 1️⃣: Upload Your File
- Click "استيراد Excel" in dashboard
- Choose any .xlsx, .xls, or .csv file
- Click "قراءة العناوين والمتابعة"

### Step 2️⃣: Map Your Columns
- System shows all your columns
- Select which column matches each field
- **Smart auto-detection** suggests matches
- Required fields: Name, Email, Course

### Step 3️⃣: Import & Review
- Click "تنفيذ الاستيراد النهائي"
- See detailed results report
- Success count, failed count, error messages

---

## Example Mapping

Your Excel might have:
```
م | الاسم الكامل | إيميل الطالب | رقم الجوال | البرنامج التدريبي
```

You map it to:
```
الاسم الكامل      →  اسم الطالب الكامل ✓
إيميل الطالب      →  البريد الإلكتروني ✓
رقم الجوال        →  رقم الهاتف ✓
البرنامج التدريبي  →  اسم الدورة ✓
```

System auto-detects keywords in Arabic & English!

---

## Features

✅ **No Template Required** - Upload any format  
✅ **Smart Auto-Mapping** - AI suggests matches  
✅ **Multi-Language** - Arabic & English columns  
✅ **Full Validation** - Email format, duplicates, course existence  
✅ **Detailed Reports** - Row-by-row error tracking  
✅ **Safe Transactions** - Rollback on failures  
✅ **Automatic Cleanup** - Temp files deleted  

---

## Supported Fields

**Required**:
- اسم الطالب الكامل (Full Name)
- البريد الإلكتروني (Email)
- اسم الدورة (Course Name)

**Optional**:
- رقم الهاتف (Phone)
- المحافظة (Governorate)
- المديرية (District)
- تاريخ الميلاد (Date of Birth)
- الدرجة (Grade)

---

## File Requirements

- **Formats**: .xlsx, .xls, .csv
- **First Row**: Must contain column headers
- **Data Rows**: Starting from row 2
- **Size**: Tested up to 1000+ rows
- **Encoding**: UTF-8 recommended for Arabic text

---

## Error Messages Explained

| Message | Meaning | Solution |
|---------|---------|----------|
| بيانات ناقصة | Missing required field | Fill Name, Email, Course |
| إيميل غير صحيح | Invalid email format | Use proper email format |
| الإيميل موجود مسبقاً | Duplicate email | Student already registered |
| الدورة غير موجودة | Course not found | Check course name spelling |

---

## Tips for Best Results

1. **Clean Your Data**: Remove empty rows
2. **Consistent Names**: Use same course names as in system
3. **Valid Emails**: Check email format before upload
4. **Test Small First**: Try with 5-10 rows first
5. **Check Report**: Review errors before re-importing

---

## Technical Details

### APIs:
- `excel_read_headers.php` - Reads column headers
- `excel_process_mapped_import.php` - Processes import

### Database:
- Inserts into `users` table (role: student)
- Creates `enrollments` (status: pending)
- Adds `grades` if provided

### Security:
- Session authentication required
- Manager/Technical roles only
- SQL injection protection
- Unique file naming

---

## Troubleshooting

**Problem**: Auto-mapping doesn't detect columns  
**Reason**: Column names don't match keywords  
**Fix**: Manually select from dropdowns

**Problem**: Import fails silently  
**Check**: Browser console for errors  
**Check**: PHP error logs in XAMPP

**Problem**: Course not found  
**Fix**: Ensure course exists in database:
```sql
SELECT * FROM courses WHERE title LIKE '%coursename%';
```

---

## Upgrading from Old System

**Old Way** (`import_excel.php`):
- Required exact columns: full_name, email, phone, course_name
- Fixed order
- No flexibility

**New Way** (Smart Import):
- Any column names
- Any order
- Dynamic mapping

**Migration**: No changes to existing data. Both systems work independently.

---

## Developer Notes

### Adding New Fields:

Edit `renderImport()` in `dashboard.php`:

```javascript
const systemFields = [
    { key: 'new_field', label: 'New Field Name', required: false },
    // ... existing fields
];
```

Edit `excel_process_mapped_import.php`:

```php
$newField = $studentData['new_field'] ?? '';
// Use $newField in INSERT query
```

### Custom Validation:

Add checks in `excel_process_mapped_import.php`:

```php
if (!isValidCustomField($studentData['custom_field'])) {
    $errors[] = "صف {$row}: Custom validation failed";
    continue;
}
```

---

## Credits

Built as part of Phase 4 of the Ibdaa-Taiz Platform Reconstruction Project.

**Technologies**:
- PHP 7.4+
- PhpSpreadsheet
- MySQLi
- Tailwind CSS
- Lucide Icons

---

## Support

For issues or questions:
1. Check `PHASE4_TESTING_GUIDE.md` for testing steps
2. Review `PHASE4_COMPLETION.md` for technical details
3. Contact technical supervisor

---

*Smart Import System v1.0 - Making data import effortless!* 🎉
