<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

// session keys compatibility
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح - هذا القسم للمدير والمشرف الفني فقط'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!in_array($user_role, ['manager','technical'])) {
    echo json_encode(['success' => false, 'message' => 'صلاحيات غير كافية']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'الطلب غير صحيح (POST مطلوب)']);
    exit;
}

$nameAr = trim($_POST['nameAr'] ?? '');
$nameEn = trim($_POST['nameEn'] ?? '');
$dob = trim($_POST['dob'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$governorate = trim($_POST['governorate'] ?? '');
$district = trim($_POST['district'] ?? '');
$courseVal = trim($_POST['course'] ?? '');

if (empty($nameAr) || empty($phone) || empty($courseVal)) {
    echo json_encode(['success' => false, 'message' => 'بيانات ناقصة. الاسم ورقم الهاتف والدورة مطلوبة.']);
    exit;
}

// build email
if (!empty($nameEn)) {
    $email_local = preg_replace('/[^a-z0-9._-]/', '', strtolower(str_replace(' ', '.', $nameEn)));
    if (empty($email_local)) $email_local = 'user' . time();
    $email = $email_local . '@ibdaa.local';
} else {
    // fallback
    $email = preg_replace('/[^0-9]/', '', $phone);
    if (empty($email)) $email = 'u' . time();
    $email .= '@ibdaa.local';
}

// default password: phone or random
$password_default = !empty($phone) ? $phone : bin2hex(random_bytes(4));
$password_hash = password_hash($password_default, PASSWORD_DEFAULT);

// determine available columns
$hasPasswordHash = (bool)($conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'") && $conn->affected_rows >= 0 && $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'")->num_rows > 0);
$hasPassword = (bool)($conn->query("SHOW COLUMNS FROM users LIKE 'password'") && $conn->query("SHOW COLUMNS FROM users LIKE 'password'")->num_rows > 0);
$hasFullNameEn = (bool)($conn->query("SHOW COLUMNS FROM users LIKE 'full_name_en'") && $conn->query("SHOW COLUMNS FROM users LIKE 'full_name_en'")->num_rows > 0);

// prepare insert
$cols = ['full_name','email','phone','role'];
$vals = [$nameAr, $email, $phone, 'student'];
$types = 'ssss';

if ($hasFullNameEn) { $cols[] = 'full_name_en'; $vals[] = $nameEn; $types .= 's'; }
if ($hasPasswordHash) { $cols[] = 'password_hash'; $vals[] = $password_hash; $types .= 's'; }
elseif ($hasPassword) { $cols[] = 'password'; $vals[] = $password_hash; $types .= 's'; }
if (!empty($dob) && $conn->query("SHOW COLUMNS FROM users LIKE 'dob'") && $conn->query("SHOW COLUMNS FROM users LIKE 'dob'")->num_rows>0) { $cols[]='dob'; $vals[]=$dob; $types.='s'; }
if (!empty($governorate) && $conn->query("SHOW COLUMNS FROM users LIKE 'governorate'") && $conn->query("SHOW COLUMNS FROM users LIKE 'governorate'")->num_rows>0) { $cols[]='governorate'; $vals[]=$governorate; $types.='s'; }
if (!empty($district) && $conn->query("SHOW COLUMNS FROM users LIKE 'district'") && $conn->query("SHOW COLUMNS FROM users LIKE 'district'")->num_rows>0) { $cols[]='district'; $vals[]=$district; $types.='s'; }

// created_at if exists
if ($conn->query("SHOW COLUMNS FROM users LIKE 'created_at'") && $conn->query("SHOW COLUMNS FROM users LIKE 'created_at'")->num_rows>0) {
    $cols[] = 'created_at'; $vals[] = date('Y-m-d H:i:s'); $types .= 's';
}

$sql = "INSERT INTO users (" . implode(',', $cols) . ") VALUES (" . implode(',', array_fill(0, count($cols), '?')) . ")";

$transactionStarted = false;
try {
    if (!$conn->begin_transaction()) throw new Exception('فشل بدء المعاملة: ' . $conn->error);
    $transactionStarted = true;

    $stmt = $conn->prepare($sql);
    if (!$stmt) throw new Exception('فشل تحضير الاستعلام: ' . $conn->error);

    // bind dynamically
    $bind_names[] = $types;
    for ($i=0;$i<count($vals);$i++) {
        $bind_name = 'bind' . $i;
        $$bind_name = $vals[$i];
        $bind_names[] = &$$bind_name;
    }
    call_user_func_array([$stmt, 'bind_param'], $bind_names);

    if (!$stmt->execute()) throw new Exception('فشل تنفيذ إدخال المستخدم: ' . $stmt->error);

    $new_user_id = $conn->insert_id;

    // handle enrollment if table exists
    $enrollExists = ($conn->query("SHOW TABLES LIKE 'enrollments'") && $conn->query("SHOW TABLES LIKE 'enrollments'")->num_rows>0);
    $course_id = null;
    if ($enrollExists) {
        if (is_numeric($courseVal)) {
            $course_id = intval($courseVal);
        } else {
            // try to find by title
            $tstmt = $conn->prepare("SELECT id FROM courses WHERE title = ? LIMIT 1");
            if ($tstmt) {
                $tstmt->bind_param('s', $courseVal);
                $tstmt->execute();
                $tres = $tstmt->get_result();
                if ($tres && $tres->num_rows>0) $course_id = $tres->fetch_assoc()['id'];
            }
        }

        if ($course_id) {
            $est = $conn->prepare("INSERT INTO enrollments (user_id, course_id, status, enrollment_date) VALUES (?, ?, 'active', CURDATE())");
            if ($est) {
                $est->bind_param('ii', $new_user_id, $course_id);
                if (!$est->execute()) throw new Exception('فشل تسجيل المتدرب في الدورة: ' . $est->error);
            }
        }
    }

    $conn->commit();

    echo json_encode(['success' => true, 'message' => 'تمت إضافة المتدرب وتسجيله بنجاح.', 'user_id' => $new_user_id, 'email' => $email, 'password' => $password_default]);
    exit;

} catch (Exception $e) {
    if (!empty($transactionStarted)) $conn->rollback();
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()]);
    exit;
}

?>
