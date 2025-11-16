# توثيق الـ API - منصة إبداع

## نظرة عامة
هذا الملف يوثق جميع endpoints والوظائف البرمجية المتاحة في نظام منصة إبداع.

---

## 📋 User Management (إدارة المستخدمين)

### 1. التسجيل (Register)
**Endpoint:** `platform/register.php`  
**Method:** POST  
**Description:** تسجيل مستخدم جديد في النظام

**Parameters:**
```php
full_name: string (required) - الاسم الكامل
email: string (required) - البريد الإلكتروني
password: string (required) - كلمة المرور
birth_date: date (required) - تاريخ الميلاد (YYYY-MM-DD)
governorate: string (required) - المحافظة
district: string (optional) - المديرية
photo: file (optional) - صورة شخصية (jpg/png, max 5MB)
```

**Response:**
```php
Success: Redirect to verify.php
Error: Redirect back with error message
```

**Database:**
- Table: `users`
- Password: Hashed with `password_hash()`
- Verification Token: Generated with `bin2hex(random_bytes(50))`

---

### 2. التحقق من البريد (Email Verification)
**Endpoint:** `platform/verify.php`  
**Method:** GET  
**Description:** تفعيل حساب المستخدم بعد التسجيل

**Parameters:**
```php
token: string (required) - رمز التحقق من البريد الإلكتروني
```

**Response:**
```php
Success: Sets verified=1, redirects to login
Error: Shows error message
```

---

### 3. تسجيل الدخول (Login)
**Endpoint:** `platform/login.php`  
**Method:** POST  
**Description:** تسجيل دخول المستخدم

**Parameters:**
```php
email: string (required)
password: string (required)
```

**Response:**
```php
Success: Redirect to student-dashboard.php
Error: Show error message
```

**Validation:**
- Email must exist
- Password verified with `password_verify()`
- Account must be verified (verified=1)

---

## 📚 Course Application (التقديم على الدورات)

### 4. تقديم طلب (Submit Application)
**Endpoint:** `platform/apply.php`  
**Method:** POST  
**Description:** تقديم طلب تسجيل في دورة تدريبية

**Parameters:**
```php
full_name: string (required)
email: string (required)
phone: string (required)
governorate: string (required)
district: string (optional)
course: string (required)
id_card: file (required) - صورة الهوية (jpg/jpeg/png/pdf, max 5MB)
notes: text (optional)
```

**Process:**
1. Validate all inputs
2. Upload ID card to `uploads/ids/` with unique filename
3. Generate unique request ID
4. Save to `database/requests.json`
5. Set status to "قيد المراجعة"

**Response:**
```html
Success page with confirmation
```

**JSON Structure:**
```json
{
  "id": "unique_id_123",
  "full_name": "string",
  "email": "email@example.com",
  "phone": "773123456",
  "governorate": "تعز",
  "district": "صالة",
  "course": "ICDL",
  "id_card": "ID_timestamp_random.jpg",
  "notes": "optional text",
  "status": "قيد المراجعة",
  "date": "2025-01-06 12:34:56"
}
```

---

## 👔 Manager Operations (عمليات المدير)

### 5. عرض الطلبات (View Requests)
**Endpoint:** `Manager/requests.php`  
**Method:** GET  
**Description:** عرض جميع طلبات التسجيل

**Response:**
```html
HTML table with all requests from requests.json
```

**Columns:**
- الاسم الكامل
- البريد الإلكتروني
- الدورة
- المحافظة
- المديرية
- الحالة
- الإجراءات (قبول/رفض/تم الدفع)

---

### 6. تحديث حالة الطلب (Update Request Status)
**Endpoint:** `Manager/updateRequest.php`  
**Method:** POST  
**Description:** تغيير حالة طلب معين

**Parameters:**
```php
id: string (required) - معرف الطلب
action: string (required) - الإجراء (approve/reject/paid)
```

**Actions:**
- `approve` → Status: "مقبول" + Send email
- `reject` → Status: "مرفوض"
- `paid` → Status: "تم الدفع" + Send email

**Process:**
1. Read `requests.json`
2. Find request by ID
3. Update status
4. Save back to file
5. If approve/paid: trigger email via `sendMail.php`
6. Redirect to requests.php

---

## 🔧 Technical Operations (عمليات الفني)

### 7. تحديث الطلب (فني)
**Endpoint:** `Technical/updateRequest.php`  
**Method:** POST  
**Description:** نسخة مماثلة لـ Manager/updateRequest.php

*(يمكن إضافة صلاحيات مختلفة مستقبلاً)*

---

## 📧 Email System (نظام البريد الإلكتروني)

### 8. إرسال إشعار بريدي
**File:** `Mailer/sendMail.php`  
**Function:** `sendStatusMail($to, $name, $course, $status)`  
**Description:** إرسال إشعار بتحديث حالة الطلب

**Parameters:**
```php
$to: string - email address
$name: string - applicant name
$course: string - course name
$status: string - new status (مقبول/تم الدفع)
```

**SMTP Configuration:**
```php
Host: smtp.gmail.com
Port: 587
SMTPSecure: tls
Username: ha717781053@gmail.com
Password: [APP_PASSWORD]
```

**Email Template:**
```html
Subject: تحديث حالة طلبك في منصة إبداع
Body: HTML email with applicant info and status
```

**Fallback:**
If PHPMailer fails, uses native PHP `mail()` function.

---

## 🗂️ Data Storage (تخزين البيانات)

### Requests JSON File
**Path:** `database/requests.json`  
**Format:** JSON Array

**Operations:**

#### Read Requests
```php
$json = file_get_contents('../database/requests.json');
$requests = json_decode($json, true);
```

#### Write Requests
```php
$json = json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
file_put_contents('../database/requests.json', $json);
```

#### Find by ID
```php
foreach ($requests as &$req) {
    if ($req['id'] === $targetId) {
        // found
    }
}
```

---

## 🔐 Security Considerations

### Input Validation
- **Email:** `filter_var($email, FILTER_VALIDATE_EMAIL)`
- **File Type:** Check `$_FILES['file']['type']`
- **File Size:** Max 5MB
- **SQL Injection:** Use prepared statements

### Password Security
```php
// Hashing
password_hash($password, PASSWORD_DEFAULT)

// Verification
password_verify($inputPassword, $hashedPassword)
```

### File Upload Security
```php
// Allowed extensions
$allowed = ['jpg', 'jpeg', 'png', 'pdf'];

// Unique filename
$filename = "ID_" . time() . "_" . bin2hex(random_bytes(8)) . "." . $ext;

// Safe directory
$uploadDir = 'uploads/ids/';
```

---

## 🌐 Yemen Locations (المواقع اليمنية)

### JavaScript Object
**File:** `platform/signup.php`, `platform/application.php`

**Structure:**
```javascript
const yemen = {
  "صنعاء": ["أمانة العاصمة", "بني حارث", ...],
  "تعز": ["التعزية", "صالة", "الشمايتين", ...],
  // ... 22 governorates total
  "أخرى": ["أخرى"]
};
```

### Dynamic District Selection
```javascript
governorateSelect.addEventListener('change', function() {
  const districts = yemen[this.value] || [];
  // populate district dropdown
});
```

---

## 📊 Available Courses (الدورات المتاحة)

1. **ICDL** - الرخصة الدولية لقيادة الحاسوب
2. **دبلوم الحاسوب المتكامل** - 18 شهر
3. **علوم الحاسوب وتطبيقاته** - 12 شهر
4. **تصميم الأنظمة التعليمية والإدارية** - 6 أشهر
5. **إكسل المتقدم وتحليل البيانات** - 3 أشهر
6. **اللغة الإنجليزية** - 6 أشهر
7. **تنمية المهارات الشخصية والمهنية** - 3 أشهر

---

## 🔄 Request Status Flow

```
[قيد المراجعة] (Initial)
    ↓
    ├─→ [مقبول] → Email Sent
    ├─→ [مرفوض]
    └─→ [تم الدفع] → Email Sent
```

---

## 📦 Dependencies

### Composer Packages
```json
{
  "require": {
    "phpmailer/phpmailer": "^6.8"
  }
}
```

### CDN Libraries
- **Tailwind CSS:** `https://cdn.tailwindcss.com`
- **Lucide Icons:** `https://unpkg.com/lucide@latest`
- **Google Fonts (Cairo):** `https://fonts.googleapis.com/css2?family=Cairo`

---

## 🐛 Error Handling

### File Upload Errors
```php
switch ($_FILES['file']['error']) {
  case UPLOAD_ERR_OK: break;
  case UPLOAD_ERR_INI_SIZE:
  case UPLOAD_ERR_FORM_SIZE:
    die("الملف كبير جداً");
  case UPLOAD_ERR_NO_FILE:
    die("لم يتم رفع أي ملف");
  default:
    die("خطأ غير معروف");
}
```

### Database Errors
```php
if ($conn->connect_error) {
    die("فشل الاتصال: " . $conn->connect_error);
}
```

### JSON Errors
```php
$data = json_decode($json, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    die("خطأ في قراءة البيانات");
}
```

---

## 🚀 Future Enhancements

### Recommendations
1. **Authentication System** for Manager/Technical portals
2. **Role-based Access Control (RBAC)**
3. **Search & Filter** in requests table
4. **Pagination** for large datasets
5. **Export to Excel/PDF**
6. **Real-time Notifications** (WebSocket)
7. **Dashboard Analytics**
8. **Payment Gateway Integration**
9. **SMS Notifications**
10. **Mobile App** (React Native/Flutter)

---

## 📞 Support

**Developer:** GitHub Copilot  
**Contact:** ha717781053@gmail.com  
**Documentation:** README.md, SETUP.md  

---

**Last Updated:** January 2025  
**Version:** 1.0.0
