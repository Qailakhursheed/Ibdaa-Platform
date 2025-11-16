# Student Dashboard APIs - Testing Guide

## 🎯 Overview
This guide helps you test all 8 Student Dashboard APIs with real data.

## 📋 Prerequisites

### 1. Login Required
Before testing APIs, you MUST login as a student:
```
URL: http://localhost/Ibdaa-Taiz/login.php
Email: student@ibdaa.edu.ye
Password: Ibdaa@Student2024
```

### 2. Ensure Test Data Exists
Run this SQL to add basic test data:
```sql
USE ibdaa_platform;

-- Enroll student in course
INSERT INTO enrollments (user_id, course_id, status) 
VALUES (4, 1, 'active') 
ON DUPLICATE KEY UPDATE status='active';
```

## 🧪 API Testing Steps

### Method 1: Using Test Page
1. Open: `http://localhost/Ibdaa-Taiz/test_student_apis.html`
2. Click individual API test buttons
3. Check results in gray boxes below each section
4. Or click "Test All APIs" button at bottom

### Method 2: Using Browser Developer Tools
1. Login as student first
2. Open browser console (F12)
3. Test individual APIs:

```javascript
// Test Courses API
fetch('Manager/api/student_courses.php?action=list')
  .then(r => r.json())
  .then(d => console.log(d));

// Test Grades API
fetch('Manager/api/student_grades.php?action=gpa')
  .then(r => r.json())
  .then(d => console.log(d));

// Test Attendance API
fetch('Manager/api/student_attendance.php?action=summary')
  .then(r => r.json())
  .then(d => console.log(d));

// Test Payments API
fetch('Manager/api/student_payments.php?action=balance')
  .then(r => r.json())
  .then(d => console.log(d));
```

### Method 3: Using Postman/Insomnia
1. Import these endpoints:
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_courses.php?action=list`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_grades.php?action=gpa`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_attendance.php?action=summary`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_assignments.php?action=list`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_materials.php?action=statistics`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_schedule.php?action=weekly`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_id_card.php?action=info`
   - GET `http://localhost/Ibdaa-Taiz/Manager/api/student_payments.php?action=list`

## 📊 Expected Results

### 1. Courses API
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "course_name": "Course Title",
      "trainer_name": "Trainer Name",
      "progress": 75,
      "status": "active"
    }
  ]
}
```

### 2. Grades API
```json
{
  "success": true,
  "data": {
    "gpa": 3.5,
    "total_courses": 3,
    "total_credits": 12
  }
}
```

### 3. Attendance API
```json
{
  "success": true,
  "data": {
    "total_sessions": 10,
    "present": 8,
    "absent": 2,
    "attendance_rate": 80.0,
    "warnings": 0
  }
}
```

### 4. ID Card API
```json
{
  "success": true,
  "data": {
    "student_number": "STD2024000004",
    "full_name": "Student User",
    "major": "علوم الحاسوب",
    "level": "المستوى الثالث"
  }
}
```

### 5. Payments API
```json
{
  "success": true,
  "data": {
    "total_amount": 108000,
    "paid_amount": 58000,
    "outstanding_amount": 50000
  }
}
```

## 🔍 Troubleshooting

### Error: "غير مصرح" (Unauthorized)
**Solution**: Login first at `http://localhost/Ibdaa-Taiz/login.php`

### Error: "لا توجد بيانات" (No Data)
**Solution**: Run the setup SQL script to create test data

### Error: "Database Connection Failed"
**Solution**: 
1. Start XAMPP MySQL service
2. Check database credentials in `Manager/config/database.php`

### Error: "Table doesn't exist"
**Solution**: 
1. Run `setup_test_data.sql` to create missing tables
2. Or use phpMyAdmin to create them manually

## ✅ Testing Checklist

- [ ] Login as student successful
- [ ] Courses API returns enrolled courses
- [ ] Grades API calculates GPA correctly
- [ ] Attendance API shows attendance rate
- [ ] Assignments API lists all assignments
- [ ] Materials API shows course materials
- [ ] Schedule API displays timetable
- [ ] ID Card API generates card info
- [ ] Payments API shows financial status

## 📝 Test Results Documentation

### Test Session: [Date]
| API | Status | Response Time | Notes |
|-----|--------|---------------|-------|
| Courses | ✅ | - ms | - |
| Grades | ✅ | - ms | - |
| Attendance | ✅ | - ms | - |
| Assignments | ✅ | - ms | - |
| Materials | ✅ | - ms | - |
| Schedule | ✅ | - ms | - |
| ID Card | ✅ | - ms | - |
| Payments | ✅ | - ms | - |

## 🚀 Next Steps

After successful testing:
1. ✅ Test complete Student Dashboard UI
2. ✅ Test file upload (assignments)
3. ✅ Test downloads (materials, ID card)
4. ✅ Test payment plan requests
5. ✅ Create comprehensive documentation

## 📞 Support

If you encounter issues:
1. Check browser console for errors
2. Check MySQL error log
3. Check PHP error log
4. Review API response messages

---

**Happy Testing! 🎉**
