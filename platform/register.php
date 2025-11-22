<?php
require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once 'db.php';

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: signup.php");
    exit;
}

// Detect AJAX request (X-Requested-With) or Accept: application/json
$isAjax = false;
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    $isAjax = true;
} elseif (!empty($_SERVER['HTTP_ACCEPT']) && strpos($_SERVER['HTTP_ACCEPT'], 'application/json') !== false) {
    $isAjax = true;
}

// استقبال البيانات
$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';
$confirm_password = $_POST['confirm_password'] ?? '';
$birth_date = $_POST['birth_date'] ?? '';
$governorate = $_POST['governorate'] ?? '';
$district = ($_POST['district'] === 'أخرى' && !empty($_POST['district_other'])) 
    ? trim($_POST['district_other']) 
    : ($_POST['district'] ?? '');

// 1️⃣ التحقق من CSRF Token
$errors = [];

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    $errors[] = "رمز الأمان غير صحيح. يرجى تحديث الصفحة والمحاولة مرة أخرى.";
}

// 2️⃣ التحقق من صحة البيانات
if (empty($full_name)) $errors[] = "الاسم الكامل مطلوب";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = "البريد الإلكتروني غير صحيح";
}

// تحسين متطلبات كلمة المرور
if (strlen($password) < 8) {
    $errors[] = "كلمة المرور يجب أن تكون 8 أحرف على الأقل";
} elseif (!preg_match('/[A-Z]/', $password)) {
    $errors[] = "كلمة المرور يجب أن تحتوي على حرف كبير واحد على الأقل";
} elseif (!preg_match('/[a-z]/', $password)) {
    $errors[] = "كلمة المرور يجب أن تحتوي على حرف صغير واحد على الأقل";
} elseif (!preg_match('/[0-9]/', $password)) {
    $errors[] = "كلمة المرور يجب أن تحتوي على رقم واحد على الأقل";
}

if ($password !== $confirm_password) {
    $errors[] = "كلمة المرور غير متطابقة";
}
if (empty($birth_date)) $errors[] = "تاريخ الميلاد مطلوب";
if (empty($governorate)) $errors[] = "المحافظة مطلوبة";
if (empty($district)) $errors[] = "المديرية مطلوبة";

// 3️⃣ التحقق من البريد الإلكتروني المكرر
if (empty($errors)) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        $errors[] = "البريد الإلكتروني مسجل مسبقاً";
    }
    $stmt->close();
}

// 4️⃣ التحقق من رفع الصورة
$photo_path = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $allowed = ['jpg', 'jpeg', 'png', 'gif'];
    $allowed_mimes = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    $filename = $_FILES['photo']['name'];
    $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
    
    // التحقق من MIME type للحماية من رفع ملفات ضارة
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $_FILES['photo']['tmp_name']);
    finfo_close($finfo);
    
    if (!in_array($ext, $allowed)) {
        $errors[] = "امتداد الصورة غير مسموح (jpg, jpeg, png, gif فقط)";
    } elseif (!in_array($mime, $allowed_mimes)) {
        $errors[] = "نوع الملف غير صحيح. يجب أن يكون صورة حقيقية";
    } elseif ($_FILES['photo']['size'] > 5 * 1024 * 1024) { // 5MB
        $errors[] = "حجم الصورة كبير جداً (الحد الأقصى 5MB)";
    } else {
        $new_name = uniqid('user_', true) . '.' . $ext;
        $upload_dir = 'uploads/profile_photos/';
        $upload_path = $upload_dir . $new_name;
        // ensure upload directory exists
        if (!is_dir($upload_dir)) {
            @mkdir($upload_dir, 0755, true);
        }

        if (move_uploaded_file($_FILES['photo']['tmp_name'], $upload_path)) {
            $photo_path = $upload_path;
            
            // تطبيق العلامة المائية على الصورة المرفوعة
            try {
                require_once __DIR__ . '/watermark_system.php';
                $wm = new WatermarkManager();
                $wm->addWatermark($upload_path, $upload_path);
            } catch (Exception $e) {
                // لا نفشل التسجيل بسبب فشل العلامة المائية
                error_log('Watermark error in register: ' . $e->getMessage());
            }
        } else {
            $errors[] = "فشل رفع الصورة";
        }
    }
} else {
    $errors[] = "الصورة الشخصية مطلوبة";
}

// إذا كان هناك أخطاء، أرجع للصفحة أو أعد JSON إذا كان الطلب AJAX
if (!empty($errors)) {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => $errors]);
        exit;
    }
    $error_msg = implode(", ", $errors);
    header("Location: signup.php?error=" . urlencode($error_msg));
    exit;
}

// 5️⃣ تشفير كلمة المرور بمستوى أمان عالي
$password_hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// 6️⃣ إنشاء توكن التفعيل
$verification_token = bin2hex(random_bytes(32));

// 6️⃣ إدراج المستخدم في قاعدة البيانات
// Build insert dynamically to support older schemas (password vs password_hash, photo vs photo_path,
// and optional verification_token column). This avoids fatal errors on mismatched DB schemas.
$cols = ['full_name', 'email'];
$placeholders = ['?', '?'];
$types = 'ss';
$values = [&$full_name, &$email];

// password column name
$resPwd = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
$hasPasswordHash = ($resPwd && $resPwd->num_rows > 0);
if ($hasPasswordHash) {
    $cols[] = 'password_hash';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = &$password_hash;
} else {
    // fallback to legacy 'password'
    $cols[] = 'password';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = &$password_hash;
}

// birth_date, governorate, district
$cols = array_merge($cols, ['birth_date', 'governorate', 'district']);
$placeholders = array_merge($placeholders, ['?', '?', '?']);
$types .= 'sss';
$values[] = &$birth_date;
$values[] = &$governorate;
$values[] = &$district;

// photo column name
$resPhoto = $conn->query("SHOW COLUMNS FROM users LIKE 'photo_path'");
$hasPhotoPath = ($resPhoto && $resPhoto->num_rows > 0);
if ($hasPhotoPath) {
    $cols[] = 'photo_path';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = &$photo_path;
} else {
    // fallback to legacy 'photo'
    $cols[] = 'photo';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = &$photo_path;
}

// optional verification_token column
$resToken = $conn->query("SHOW COLUMNS FROM users LIKE 'verification_token'");
$hasVerificationToken = ($resToken && $resToken->num_rows > 0);
if ($hasVerificationToken) {
    $cols[] = 'verification_token';
    $placeholders[] = '?';
    $types .= 's';
    $values[] = &$verification_token;
}

// verified column exists in schema (default 0) — we'll set it explicitly to 0 in VALUES to be safe
$cols_sql = implode(', ', $cols) . ', verified';
$placeholders_sql = implode(', ', $placeholders) . ', 0';

$sql = "INSERT INTO users ({$cols_sql}) VALUES ({$placeholders_sql})";
$stmt = $conn->prepare($sql);
if ($stmt === false) {
    // prepare failed — return friendly error
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => ['خطأ داخلي في السيرفر أثناء تجهيز الاستعلام.']]);
        exit;
    }
    header("Location: signup.php?error=" . urlencode("حدث خطأ داخلي أثناء التسجيل."));
    exit;
}

// bind params dynamically
$bindParams = [];
$bindParams[] = $types;
foreach ($values as $k => $v) {
    $bindParams[] = &$values[$k];
}
call_user_func_array([$stmt, 'bind_param'], $bindParams);

if ($stmt->execute()) {
    // 7️⃣ إرسال رابط التفعيل عبر البريد
    $verify_link = "http://localhost/Ibdaa-Taiz/platform/verify.php?token=" . $verification_token;
    
    // ⚠️ للاختبار المحلي: نحفظ الرابط في الجلسة بدلاً من إرساله
    // في الإنتاج: استخدم PHPMailer أو mail()
    $_SESSION['verification_link'] = $verify_link;
    $_SESSION['pending_email'] = $email;
    
    // يمكنك إرسال البريد هنا باستخدام PHPMailer
    /*
    require 'vendor/autoload.php';
    $mail = new PHPMailer\PHPMailer\PHPMailer();
    // إعدادات SMTP...
    $mail->setFrom('noreply@ibdaa-platform.com', 'منصة إبداع');
    $mail->addAddress($email, $full_name);
    $mail->Subject = 'تفعيل حسابك في منصة إبداع';
    $mail->Body = "مرحباً $full_name، لتفعيل حسابك اضغط على الرابط: $verify_link";
    $mail->send();
    */
    
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'success' => true,
            'message' => "تم إنشاء الحساب بنجاح! تحقق من بريدك الإلكتروني لتفعيل الحساب.",
            'redirect' => 'login.php'
        ]);
        exit;
    }
    header("Location: signup.php?success=" . urlencode("تم إنشاء الحساب بنجاح! تحقق من بريدك الإلكتروني لتفعيل الحساب."));
    exit;
} else {
    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'errors' => ['حدث خطأ أثناء التسجيل. حاول مرة أخرى.']]);
        exit;
    }
    header("Location: signup.php?error=" . urlencode("حدث خطأ أثناء التسجيل. حاول مرة أخرى."));
    exit;
}

$stmt->close();
$conn->close();
?>
