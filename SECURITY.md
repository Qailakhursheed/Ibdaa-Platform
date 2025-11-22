# 🔐 سياسة الأمان - منصة إبداع

دليل شامل لإجراءات الأمان والحماية من التهديدات

---

## 📋 قائمة المحتويات

1. [الأمان الأساسي](#الأمان-الأساسي)
2. [حماية قاعدة البيانات](#حماية-قاعدة-البيانات)
3. [حماية المصادقة](#حماية-المصادقة)
4. [حماية الملفات](#حماية-الملفات)
5. [التشفير](#التشفير)
6. [الإبلاغ عن الثغرات](#الإبلاغ-عن-الثغرات)

---

## 🛡️ الأمان الأساسي

### 1. SQL Injection Protection

```php
// ❌ غير آمن
$query = "SELECT * FROM users WHERE email = '" . $_POST['email'] . "'";
$result = mysqli_query($conn, $query);

// ✅ آمن - Prepared Statement
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$_POST['email']]);
$result = $stmt->fetch();
```

### 2. XSS (Cross-Site Scripting) Protection

```php
// ❌ غير آمن
echo "مرحباً " . $_GET['name'];

// ✅ آمن
echo "مرحباً " . htmlspecialchars($_GET['name'], ENT_QUOTES, 'UTF-8');

// أو استخدام Vue.js للتصفية التلقائية
<div>{{ name }}</div>
```

### 3. CSRF (Cross-Site Request Forgery) Protection

```php
// إنشاء توكن
session_start();
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// في النموذج
<form method="POST">
    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
    <!-- حقول النموذج -->
</form>

// التحقق من التوكن
if (!hash_equals($_SESSION['csrf_token'], $_POST['csrf_token'])) {
    die('CSRF token validation failed');
}
```

### 4. تصفية المدخلات

```php
class InputValidator {
    public static function email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }
    
    public static function phone($phone) {
        return preg_match('/^\d{9,12}$/', $phone);
    }
    
    public static function file($file) {
        $allowed = ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx'];
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        return in_array(strtolower($ext), $allowed);
    }
}
```

---

## 🗄️ حماية قاعدة البيانات

### 1. إعدادات تسجيل الدخول الآمنة

```sql
-- إنشاء مستخدم بدون صلاحيات جذرية
CREATE USER 'app_user'@'localhost' IDENTIFIED BY 'secure_password_here';

-- منح الصلاحيات المحدودة
GRANT SELECT, INSERT, UPDATE, DELETE ON ibdaa_platform.* TO 'app_user'@'localhost';

-- منع الوصول من بعيد
REVOKE ALL PRIVILEGES ON *.* FROM 'app_user'@'%';

-- تطبيق التغييرات
FLUSH PRIVILEGES;
```

### 2. تشفير البيانات الحساسة

```php
// تشفير كلمات المرور
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// التحقق من كلمة المرور
if (password_verify($password, $password_hash)) {
    // كلمة المرور صحيحة
}

// تشفير البيانات الحساسة الأخرى
$encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
$decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
```

### 3. نسخ احتياطية آمنة

```bash
#!/bin/bash
# نسخ احتياطية مشفرة

BACKUP_DIR="/secure/backups"
DB_NAME="ibdaa_platform"
DB_USER="app_user"

# أخذ النسخة الاحتياطية
mysqldump -u $DB_USER -p $DB_NAME | gzip | openssl enc -aes-256-cbc -e -out $BACKUP_DIR/backup_$(date +%Y%m%d).sql.gz.enc

# حذف النسخ القديمة
find $BACKUP_DIR -name "*.enc" -mtime +30 -delete
```

---

## 🔑 حماية المصادقة

### 1. تسجيل الدخول الآمن

```php
class AuthController {
    /**
     * تسجيل الدخول مع حماية من brute force
     */
    public function login($email, $password) {
        // التحقق من محاولات الدخول الفاشلة
        $attempts = $this->getLoginAttempts($email);
        if ($attempts > 5) {
            // حظر الحساب لمدة 15 دقيقة
            throw new Exception('Account locked. Try again later.');
        }
        
        // البحث عن المستخدم
        $user = $this->getUserByEmail($email);
        if (!$user) {
            $this->recordFailedAttempt($email);
            throw new Exception('Invalid email or password');
        }
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $user['password_hash'])) {
            $this->recordFailedAttempt($email);
            throw new Exception('Invalid email or password');
        }
        
        // إنشاء جلسة آمنة
        $this->createSecureSession($user);
        
        // حذف محاولات الدخول الفاشلة
        $this->clearFailedAttempts($email);
        
        return $user;
    }
    
    /**
     * إنشاء جلسة آمنة
     */
    private function createSecureSession($user) {
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['logged_in'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'];
    }
    
    /**
     * التحقق من الجلسة
     */
    public function verifySession() {
        if (!isset($_SESSION['user_id'])) {
            return false;
        }
        
        // التحقق من تطابق IP والمتصفح
        if ($_SESSION['ip_address'] !== $_SERVER['REMOTE_ADDR']) {
            session_destroy();
            return false;
        }
        
        if ($_SESSION['user_agent'] !== $_SERVER['HTTP_USER_AGENT']) {
            session_destroy();
            return false;
        }
        
        // التحقق من انتهاء الجلسة
        if (time() - $_SESSION['logged_in'] > 3600) {
            session_destroy();
            return false;
        }
        
        return true;
    }
}
```

### 2. Two-Factor Authentication (2FA)

```php
class TwoFactorAuth {
    /**
     * إنشاء رمز OTP
     */
    public function generateOTP($user_id) {
        $otp = random_int(100000, 999999);
        
        // حفظ في قاعدة البيانات مع مدة صلاحية 5 دقائق
        $stmt = $pdo->prepare("UPDATE users SET otp = ?, otp_expiry = DATE_ADD(NOW(), INTERVAL 5 MINUTE) WHERE id = ?");
        $stmt->execute([$otp, $user_id]);
        
        return $otp;
    }
    
    /**
     * التحقق من OTP
     */
    public function verifyOTP($user_id, $otp) {
        $stmt = $pdo->prepare("
            SELECT otp FROM users 
            WHERE id = ? AND otp = ? AND otp_expiry > NOW()
        ");
        $stmt->execute([$user_id, $otp]);
        return $stmt->rowCount() > 0;
    }
}
```

---

## 📁 حماية الملفات

### 1. التحقق من الملفات المرفوعة

```php
class FileUploadValidator {
    private $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    private $max_size = 5242880; // 5MB
    
    /**
     * التحقق من الملف
     */
    public function validate($file) {
        // التحقق من الحجم
        if ($file['size'] > $this->max_size) {
            throw new Exception('File size exceeds limit');
        }
        
        // التحقق من نوع MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        if (!in_array($mime_type, $this->allowed_types)) {
            throw new Exception('Invalid file type');
        }
        
        // التحقق من امتداد الملف
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $allowed_ext = ['jpg', 'jpeg', 'png', 'pdf'];
        if (!in_array(strtolower($ext), $allowed_ext)) {
            throw new Exception('Invalid file extension');
        }
        
        return true;
    }
    
    /**
     * حفظ الملف بأمان
     */
    public function save($file, $upload_dir) {
        $this->validate($file);
        
        // إنشاء اسم ملف عشوائي
        $filename = bin2hex(random_bytes(16)) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $filepath = $upload_dir . '/' . $filename;
        
        // نقل الملف
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Failed to save file');
        }
        
        // تعيين الأذونات
        chmod($filepath, 0644);
        
        return $filename;
    }
}
```

### 2. حماية الوصول للملفات

```apache
# في .htaccess

# منع الوصول المباشر للملفات الحساسة
<FilesMatch "^\.">
    Deny from all
</FilesMatch>

<FilesMatch "\.(env|json|lock)$">
    Deny from all
</FilesMatch>

# السماح فقط بأنواع ملفات معينة
<Directory /var/www/ibdaa/platform/uploads>
    <FilesMatch "\.(php|phtml|php3|php4|php5|phps)$">
        Deny from all
    </FilesMatch>
</Directory>
```

---

## 🔒 التشفير

### 1. تشفير البيانات

```php
class Encryption {
    private $algorithm = 'AES-256-CBC';
    
    /**
     * تشفير البيانات
     */
    public function encrypt($data, $key) {
        $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->algorithm));
        $encrypted = openssl_encrypt($data, $this->algorithm, $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * فك التشفير
     */
    public function decrypt($data, $key) {
        $data = base64_decode($data);
        $iv_length = openssl_cipher_iv_length($this->algorithm);
        $iv = substr($data, 0, $iv_length);
        $encrypted = substr($data, $iv_length);
        return openssl_decrypt($encrypted, $this->algorithm, $key, 0, $iv);
    }
}
```

### 2. HTTPS والـ SSL

```apache
# تفعيل HSTS
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains; preload"

# منع الوصول من HTTP
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# تعطيل TLS 1.0 و 1.1
SSLProtocol -all +TLSv1.2 +TLSv1.3
```

---

## 🚨 الإبلاغ عن الثغرات الأمنية

### سياسة الكشف المسؤول

إذا وجدت ثغرة أمنية في منصة إبداع، يرجى:

1. **عدم الكشف العام** عن الثغرة فوراً
2. **إرسال تقرير** بالتفاصيل على: `security@ibdaa.com`
3. **تضمين المعلومات التالية:**
   - وصف الثغرة
   - خطوات التكرار
   - التأثير المحتمل
   - مقترحات الإصلاح (إن وجدت)

### معايير التقييم

- **حرج جداً**: الوصول غير المصرح، تسرب البيانات
- **حرج**: تنفيذ أكواد بعيد، Privilege Escalation
- **متوسط**: XSS، CSRF، SQL Injection
- **منخفض**: مشاكل في التوثيق، التكوينات الضعيفة

### الجدول الزمني للإصلاح

| الخطورة | الإطار الزمني |
|--------|------------|
| حرج جداً | يوم واحد |
| حرج | 3 أيام |
| متوسط | أسبوع واحد |
| منخفض | أسبوعان |

---

## 📋 قائمة التحقق الأمنية

### قبل الإطلاق

- [ ] تحديث جميع المكتبات والحزم
- [ ] تفعيل HTTPS مع شهادة SSL صحيحة
- [ ] تعطيل الوضع الوثائقي (Debug = false)
- [ ] تأمين قاعدة البيانات
- [ ] تعيين الأذونات الصحيحة
- [ ] إخفاء ملفات الإعدادات
- [ ] تفعيل HSTS
- [ ] إعداد WAF (Web Application Firewall)

### المراقبة المستمرة

- [ ] فحوصات الأمان الأسبوعية
- [ ] تحديثات الأمان الشهرية
- [ ] فحص السجلات اليومي
- [ ] اختبارات الاختراق الربع سنوية
- [ ] تحديث بيانات المراقبة

---

## 📞 جهات الاتصال الأمنية

- 📧 security@ibdaa.com
- 🔔 security-team@ibdaa.com
- 📱 +967-xxxxxxxxx

---

**آخر تحديث: 21 نوفمبر 2025**

**تم الاحتفاظ بحقوق النشر © 2025 منصة إبداع**
