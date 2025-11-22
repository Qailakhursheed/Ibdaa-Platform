# Quick Start: Testing Announcements System 🚀

## Prerequisites
- XAMPP running (Apache + MySQL)
- Database: `ibdaa_platform`
- Logged in as Manager or Technical Supervisor

---

## Step 1: Verify Database Table

Open phpMyAdmin: `http://localhost/phpmyadmin`

```sql
-- Check if announcements table exists
SHOW TABLES LIKE 'announcements';

-- View sample data
SELECT * FROM announcements ORDER BY created_at DESC;
```

**Expected**: 3 sample announcements from `database/announcements.sql`

---

## Step 2: Test Admin Dashboard

### Access Dashboard:
```
http://localhost/Ibdaa-Taiz/Manager/dashboard.php
```

### Login Credentials:
- **Manager**: admin@ibdaa.local / password
- **Technical**: tech@ibdaa.local / password

### Test Create Announcement:
1. Click "إدارة الإعلانات" in sidebar
2. Fill form:
   - **Title**: "إعلان تجريبي"
   - **Content**: "هذا إعلان للاختبار"
3. Click "نشر الإعلان"
4. ✅ Success message should appear
5. ✅ New announcement appears in table below

### Test Delete Announcement:
1. Click trash icon on any announcement
2. Confirm deletion in popup
3. ✅ Success message + announcement removed

---

## Step 3: Test Public Display

### Access Public Homepage:
```
http://localhost/Ibdaa-Taiz/platform/index.php
```

### Verify Display:
1. ✅ "الإعلانات والأخبار" section visible
2. ✅ Latest 3 announcements shown in cards
3. ✅ Each card shows:
   - Gradient header with title
   - Content (truncated if long)
   - Date and time
4. ✅ Hover animation works (card lifts)

### Test Responsive Design:
- Resize browser window
- ✅ Desktop: 3 columns
- ✅ Tablet: 2 columns
- ✅ Mobile: 1 column

---

## Step 4: Test API Directly

### GET All Announcements:
```bash
curl http://localhost/Ibdaa-Taiz/Manager/api/manage_announcements.php
```

**Expected Response**:
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "title": "إطلاق برنامج تدريبي جديد",
      "content": "يسر منصة إبداع...",
      "created_at": "2024-01-15 10:00:00",
      "updated_at": "2024-01-15 10:00:00"
    }
  ],
  "count": 3
}
```

### Create Announcement (via API):
```bash
curl -X POST http://localhost/Ibdaa-Taiz/Manager/api/manage_announcements.php \
  -H "Content-Type: application/json" \
  -d '{"action":"create","title":"Test API","content":"This is a test"}'
```

**Expected Response**:
```json
{
  "success": true,
  "message": "Announcement published successfully",
  "announcement_id": 4
}
```

---

## Step 5: Security Testing

### Test Unauthorized Access:
1. Logout from dashboard
2. Try to access:
   ```
   http://localhost/Ibdaa-Taiz/Manager/api/manage_announcements.php
   ```
3. ✅ Should return: `{"success":false,"message":"Unauthorized"}`

### Test SQL Injection:
1. In dashboard form, enter:
   - **Title**: `'; DROP TABLE announcements; --`
   - **Content**: `<script>alert('XSS')</script>`
2. Submit form
3. ✅ Data should be escaped and stored safely
4. ✅ No SQL errors
5. ✅ Script tags should not execute on public page

---

## Troubleshooting

### Problem: "Unauthorized" error in dashboard
**Solution**: Check session variables
```php
// In Manager/dashboard.php
print_r($_SESSION); // Should show user_id and user_role
```

### Problem: Announcements not showing on public page
**Check**:
1. Database connection in `platform/db.php`
2. Run query in phpMyAdmin:
   ```sql
   SELECT * FROM announcements ORDER BY created_at DESC LIMIT 3;
   ```
3. Check PHP errors: Enable `error_reporting(E_ALL)` in `platform/index.php`

### Problem: "Failed to load announcements" in dashboard
**Check**:
1. Browser Console for JavaScript errors
2. Network tab for API response
3. Verify API file exists: `Manager/api/manage_announcements.php`

---

## Success Criteria ✅

- [x] Dashboard loads without errors
- [x] Create announcement form works
- [x] Announcements table displays all entries
- [x] Delete button removes announcements
- [x] Public page shows latest 3 announcements
- [x] Responsive design works on all devices
- [x] API returns JSON responses
- [x] Unauthorized users are blocked
- [x] HTML/SQL injection prevented

---

## Next Steps

✅ Phase 3 Complete - Announcements System Working

🚀 **Proceed to Phase 4**: Smart Excel Import System
- Build intelligent bulk student import
- Handle .xlsx, .xls, .csv formats
- Validate data and detect duplicates
- Generate detailed import reports

---

*Testing Guide for Phase 3 of 6-phase Ibdaa-Taiz Reconstruction Project*
