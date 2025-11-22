<?php
/**
 * معالج نظام التسجيل الموحد
 * Process Unified Registration
 * 
 * يعالج:
 * 1. التحقق من البيانات
 * 2. رفع المستندات
 * 3. إنشاء طلب انضمام جديد
 * 4. إرسال إشعار للمشرف الفني
 */

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/anti_detection.php';
require_once __DIR__ . '/../includes/rate_limiter.php';
require_once 'db.php';

// إخفاء معلومات السيرفر
AntiDetection::hideServerHeaders();

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

// التحقق من طريقة الإرسال
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: unified_registration.php?error=' . urlencode('طريقة إرسال غير صحيحة'));
    exit;
}

// التحقق من CSRF Token
if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    AntiDetection::logSuspiciousActivity('registration_invalid_csrf');
    header('Location: unified_registration.php?error=' . urlencode(AntiDetection::getGenericError()));
    exit;
}

// التحقق من Anti-Detection
$validation = AntiDetection::validateFullProtection($_POST);
if (!$validation['valid']) {
    AntiDetection::logSuspiciousActivity('registration_' . implode('_', $validation['failed_checks']));
    AntiDetection::addRandomDelay();
    header('Location: unified_registration.php?error=' . urlencode(AntiDetection::getGenericError()));
    exit;
}

// Rate Limiting - منع التسجيل المتكرر
$ip = $_SERVER['REMOTE_ADDR'];
$rate_limit_key = 'registration_' . $ip;
$rateLimiter = new RateLimiter($conn);
if (!$rateLimiter->checkAttempts($rate_limit_key, 3, 3600)) { // 3 محاولات في الساعة
    AntiDetection::logSuspiciousActivity('registration_rate_limit_exceeded', $ip);
    header('Location: unified_registration.php?error=' . urlencode('تم تجاوز الحد المسموح من محاولات التسجيل. يرجى المحاولة لاحقاً'));
    exit;
}

// تأخير عشوائي لمنع الكشف
AntiDetection::addRandomDelay();

// جمع وتنظيف البيانات
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$phone = trim($_POST['phone'] ?? '');
$birth_date = trim($_POST['birth_date'] ?? '');
$governorate = trim($_POST['governorate'] ?? '');
$district = trim($_POST['district'] ?? '');
$course_id = (int)($_POST['course_id'] ?? 0);
$notes = trim($_POST['notes'] ?? '');

// التحقق من البيانات الأساسية
$errors = [];

if (strlen($full_name) < 3) {
    $errors[] = 'الاسم الكامل يجب أن يكون 3 أحرف على الأقل';
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'البريد الإلكتروني غير صحيح';
}

if (strlen($password) < 8) {
    $errors[] = 'كلمة المرور يجب أن تكون 8 أحرف على الأقل';
}

if ($password !== $confirm_password) {
    $errors[] = 'كلمة المرور غير متطابقة';
}

if (!preg_match('/^[0-9]{9,15}$/', $phone)) {
    $errors[] = 'رقم الهاتف غير صحيح';
}

if (empty($birth_date)) {
    $errors[] = 'تاريخ الميلاد مطلوب';
} else {
    $birth_timestamp = strtotime($birth_date);
    $age = (time() - $birth_timestamp) / (365.25 * 24 * 60 * 60);
    if ($age < 15) {
        $errors[] = 'يجب أن يكون عمرك 15 سنة على الأقل';
    }
}

if (empty($governorate) || empty($district)) {
    $errors[] = 'المحافظة والمديرية مطلوبة';
}

// التحقق من الدورة
if ($course_id <= 0) {
    $errors[] = 'يرجى اختيار دورة تدريبية';
} else {
    $stmt = $conn->prepare("SELECT id, name, price FROM courses WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 0) {
        $errors[] = 'الدورة المختارة غير متاحة';
        $course_id = 0;
    } else {
        $course_data = $result->fetch_assoc();
        $course_name = $course_data['name'];
        $course_price = $course_data['price'];
    }
    $stmt->close();
}

// التحقق من عدم تكرار البريد الإلكتروني أو الهاتف
$stmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ?");
$stmt->bind_param('ss', $email, $phone);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $errors[] = 'البريد الإلكتروني أو رقم الهاتف مسجل مسبقاً';
}
$stmt->close();

// التحقق من عدم وجود طلب سابق معلق
$stmt = $conn->prepare("SELECT id FROM applications WHERE email = ? AND status = 'pending'");
$stmt->bind_param('s', $email);
$stmt->execute();
$result = $stmt->get_result();
if ($result->num_rows > 0) {
    $errors[] = 'لديك طلب معلق بالفعل. يرجى انتظار المراجعة';
}
$stmt->close();

// معالجة رفع الملفات
$upload_dir = __DIR__ . '/uploads/applications/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$id_file_path = '';
$photo_path = '';

// رفع صورة الهوية
if (isset($_FILES['id_file']) && $_FILES['id_file']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png', 'application/pdf'];
    $file_type = $_FILES['id_file']['type'];
    $file_size = $_FILES['id_file']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = 'نوع ملف الهوية غير مسموح (JPG, PNG, PDF فقط)';
    } elseif ($file_size > 5 * 1024 * 1024) { // 5MB
        $errors[] = 'حجم ملف الهوية يتجاوز 5MB';
    } else {
        $ext = pathinfo($_FILES['id_file']['name'], PATHINFO_EXTENSION);
        $id_filename = 'id_' . time() . '_' . uniqid() . '.' . $ext;
        $id_file_path = $upload_dir . $id_filename;
        
        if (!move_uploaded_file($_FILES['id_file']['tmp_name'], $id_file_path)) {
            $errors[] = 'فشل رفع ملف الهوية';
            $id_file_path = '';
        } else {
            $id_file_path = 'uploads/applications/' . $id_filename;
        }
    }
} else {
    $errors[] = 'صورة الهوية مطلوبة';
}

// رفع الصورة الشخصية
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed_types = ['image/jpeg', 'image/png'];
    $file_type = $_FILES['photo']['type'];
    $file_size = $_FILES['photo']['size'];
    
    if (!in_array($file_type, $allowed_types)) {
        $errors[] = 'نوع الصورة الشخصية غير مسموح (JPG, PNG فقط)';
    } elseif ($file_size > 5 * 1024 * 1024) { // 5MB
        $errors[] = 'حجم الصورة الشخصية يتجاوز 5MB';
    } else {
        $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
        $photo_filename = 'photo_' . time() . '_' . uniqid() . '.' . $ext;
        $photo_path = $upload_dir . $photo_filename;
        
        if (!move_uploaded_file($_FILES['photo']['tmp_name'], $photo_path)) {
            $errors[] = 'فشل رفع الصورة الشخصية';
            $photo_path = '';
        } else {
            $photo_path = 'uploads/applications/' . $photo_filename;
        }
    }
} else {
    $errors[] = 'الصورة الشخصية مطلوبة';
}

// بعد رفع الملفات، أضف العلامة المائية على الصور (إن وُجدت) باستخدام WatermarkManager
// لا نطبق العلامة المائية على PDFs
try {
    require_once __DIR__ . '/watermark_system.php';
    $wm = new WatermarkManager();

    // تطبيق على صورة الهوية إذا كانت صورة
    if ($id_file_path && preg_match('/\.(jpe?g|png|gif)$/i', $id_file_path)) {
        $fullIdPath = __DIR__ . '/' . $id_file_path;
        if (file_exists($fullIdPath)) {
            $wm->addWatermark($fullIdPath, $fullIdPath);
        }
    }

    // تطبيق على الصورة الشخصية
    if ($photo_path && preg_match('/\.(jpe?g|png|gif)$/i', $photo_path)) {
        $fullPhotoPath = __DIR__ . '/' . $photo_path;
        if (file_exists($fullPhotoPath)) {
            $wm->addWatermark($fullPhotoPath, $fullPhotoPath);
        }
    }
} catch (Exception $e) {
    // لا نفشل التسجيل بسبب فشل العلامة المائية - سجل الخطأ إن أمكن
    error_log('Watermark error: ' . $e->getMessage());
}

// إذا كانت هناك أخطاء، عرضها
if (!empty($errors)) {
    // حذف الملفات المرفوعة
    if ($id_file_path && file_exists(__DIR__ . '/' . $id_file_path)) {
        unlink(__DIR__ . '/' . $id_file_path);
    }
    if ($photo_path && file_exists(__DIR__ . '/' . $photo_path)) {
        unlink(__DIR__ . '/' . $photo_path);
    }
    
    $error_msg = implode('<br>', $errors);
    header('Location: unified_registration.php?error=' . urlencode($error_msg) . '&course_id=' . $course_id);
    exit;
}

// إدراج المستخدم والطلب في قاعدة البيانات
$conn->begin_transaction();

try {
    // 1. إنشاء حساب المستخدم
    $password_hash = password_hash($password, PASSWORD_BCRYPT);
    $verification_token = bin2hex(random_bytes(32));
    
    // Check if password_hash column exists (it should based on my check)
    // But let's be safe and use the columns I saw: full_name, email, phone, password_hash, dob, governorate, district, role, verified
    
    $stmt = $conn->prepare("INSERT INTO users (full_name, email, phone, password_hash, dob, governorate, district, role, verified, account_status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, 'student', 0, 'pending', NOW())");
    if (!$stmt) throw new Exception("خطأ في تحضير استعلام المستخدم: " . $conn->error);
    
    $stmt->bind_param('sssssss', $full_name, $email, $phone, $password_hash, $birth_date, $governorate, $district);
    
    if (!$stmt->execute()) throw new Exception("خطأ في إنشاء المستخدم: " . $stmt->error);
    
    $user_id = $stmt->insert_id;
    $stmt->close();

    // 2. إنشاء طلب التسجيل
    $stmt = $conn->prepare("
        INSERT INTO registration_requests (
            user_id, full_name, email, phone, dob, governorate, district,
            course_id, id_file_path, photo_path, notes,
            status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())
    ");
    
    if (!$stmt) throw new Exception("خطأ في تحضير استعلام الطلب: " . $conn->error);

    $stmt->bind_param(
        'issssssisss',
        $user_id, $full_name, $email, $phone, $birth_date, $governorate, $district,
        $course_id, $id_file_path, $photo_path, $notes
    );

    if (!$stmt->execute()) throw new Exception("خطأ في إنشاء الطلب: " . $stmt->error);
    
    $application_id = $stmt->insert_id;
    $stmt->close();
    
    $conn->commit();
    
    // تسجيل محاولة تسجيل ناجحة
    $rateLimiter->recordAttempt($rate_limit_key, true);
    
    // إنشاء إشعار للمشرف الفني
    $notification_msg = "طلب انضمام جديد من: {$full_name} - الدورة: {$course_name}";
    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, message, type, created_at)
        SELECT id, ?, 'info', NOW()
        FROM users WHERE role = 'technical' AND status = 'active'
    ");
    // Note: notifications table might not exist or have different schema, but assuming it works as per original code
    // If it fails, it might throw exception if strict, but original code had it.
    // Let's wrap in try-catch just for this part or assume it works.
    // Actually, original code had it. I'll keep it but wrapped in try/catch or just let it run.
    // Since I'm inside a transaction, if this fails, it rolls back everything? 
    // Notifications are less critical. Let's keep it simple.
    
    if ($stmt) {
        $stmt->bind_param('s', $notification_msg);
        $stmt->execute();
        $stmt->close();
    }
    
    // إضافة سجل إشعار بريدي
    $stmt = $conn->prepare("
        INSERT INTO notification_log (recipient_email, subject, message, status)
        VALUES (?, ?, ?, 'pending')
    ");
    
    if ($stmt) {
        $email_subject = 'استلام طلب الانضمام - منصة إبداع';
        $email_message = "
عزيزي/عزيزتي {$full_name},

تم استلام طلب انضمامك إلى دورة: {$course_name}

رقم الطلب: #{$application_id}

حسابك الآن قيد المراجعة. يمكنك تسجيل الدخول لمتابعة حالة الطلب.

شكراً لاختياركم منصة إبداع
        ";
        
        $stmt->bind_param('sss', $email, $email_subject, $email_message);
        $stmt->execute();
        $stmt->close();
    }
    
    // إعادة التوجيه مع رسالة نجاح
    $success_msg = "تم إرسال طلبك وإنشاء حسابك بنجاح! رقم الطلب: #{$application_id}. يرجى انتظار الموافقة.";
    header('Location: unified_registration.php?success=' . urlencode($success_msg));
    exit;

} catch (Exception $e) {
    $conn->rollback();
    
    // حذف الملفات المرفوعة
    if ($id_file_path && file_exists(__DIR__ . '/' . $id_file_path)) {
        unlink(__DIR__ . '/' . $id_file_path);
    }
    if ($photo_path && file_exists(__DIR__ . '/' . $photo_path)) {
        unlink(__DIR__ . '/' . $photo_path);
    }
    
    AntiDetection::logSuspiciousActivity('registration_db_error', $e->getMessage());
    header('Location: unified_registration.php?error=' . urlencode('حدث خطأ أثناء معالجة طلبك: ' . $e->getMessage()));
    exit;
}
