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
require_once __DIR__ . '/../../vendor/autoload.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE); exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST مطلوب'], JSON_UNESCAPED_UNICODE); exit;
}

// دعم حقل اسم ملف متنوع
$upload = $_FILES['excel_file'] ?? ($_FILES['file'] ?? ($_FILES['upload'] ?? null));
if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'لم يتم استقبال الملف أو حدث خطأ في الرفع'], JSON_UNESCAPED_UNICODE); exit;
}

$filename = $upload['name'];
$tmp = $upload['tmp_name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

$result = [ 'success'=>true, 'imported'=>0, 'failed'=>0, 'errors'=>[] ];

// Helpers
function col_exists($conn, $col) {
    $res = $conn->query("SHOW COLUMNS FROM users LIKE '".$conn->real_escape_string($col)."'");
    return $res && $res->num_rows>0;
}

function get_course_id($conn, $title) {
    if (!$title) return null;
    // حاول حسب id مباشرة
    if (is_numeric($title)) {
        $cid = (int)$title; $res = $conn->query("SELECT id FROM courses WHERE id=".$cid." LIMIT 1");
        if ($res && $res->num_rows) return $cid;
    }
    $stmt = $conn->prepare("SELECT id FROM courses WHERE title = ? LIMIT 1");
    $stmt->bind_param('s', $title); $stmt->execute(); $r=$stmt->get_result();
    if ($r && $r->num_rows) return $r->fetch_assoc()['id'];
    return null;
}
function get_region_id($conn, $name) {
    if (!$name) return null; $stmt=$conn->prepare("SELECT id FROM regions WHERE name=? LIMIT 1"); $stmt->bind_param('s',$name); $stmt->execute(); $r=$stmt->get_result(); return ($r&&$r->num_rows)?$r->fetch_assoc()['id']:null;
}
function get_district_id($conn, $name, $region_id) {
    if (!$name || !$region_id) return null; $stmt=$conn->prepare("SELECT id FROM districts WHERE name=? AND region_id=? LIMIT 1"); $stmt->bind_param('si',$name,$region_id); $stmt->execute(); $r=$stmt->get_result(); return ($r&&$r->num_rows)?$r->fetch_assoc()['id']:null;
}

function email_exists($conn, $email) {
    $stmt=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $stmt->bind_param('s',$email); $stmt->execute(); $r=$stmt->get_result(); return ($r&&$r->num_rows)>0;
}

$hasHash = col_exists($conn, 'password_hash');
$hasPwd = col_exists($conn, 'password');
if (!$hasHash && !$hasPwd) {
    echo json_encode(['success'=>false,'message'=>'لا يوجد عمود كلمة مرور (password أو password_hash) في جدول المستخدمين'], JSON_UNESCAPED_UNICODE); exit;
}

// خوان CSV fallback
function import_csv($conn, $tmp, &$result) {
    if (($fh = fopen($tmp, 'r')) === false) { $result['success']=false; $result['errors'][]='تعذر فتح ملف CSV'; return; }
    $headers = fgetcsv($fh);
    $map = [];
    if ($headers) {
        // طبيع أسماء العرب/الانجليزي
        foreach ($headers as $i=>$h) {
            $h=trim($h);
            $key = strtolower($h);
            if ($key==='name' || $h==='الاسم') $map['name']=$i;
            elseif ($key==='email' || $h==='الإيميل' || $h==='البريد' || $h==='البريد الإلكتروني') $map['email']=$i;
            elseif ($key==='phone' || $h==='الهاتف' || $h==='رقم الهاتف') $map['phone']=$i;
            elseif ($key==='course' || $key==='course_title' || $h==='اسم_الدورة' || $h==='اسم الدورة') $map['course']=$i;
            elseif ($key==='region' || $h==='المحافظة') $map['region']=$i;
            elseif ($key==='district' || $h==='المديرية') $map['district']=$i;
        }
    }
    $rowNum=1;
    while (($row = fgetcsv($fh)) !== false) {
        $rowNum++;
        $name = $row[$map['name'] ?? 0] ?? '';
        $email = $row[$map['email'] ?? 1] ?? '';
        $phone = $row[$map['phone'] ?? 2] ?? '';
        $courseTitle = $row[$map['course'] ?? 3] ?? '';
        $regionName = $row[$map['region'] ?? 4] ?? '';
        $districtName = $row[$map['district'] ?? 5] ?? '';
        process_row($conn, $rowNum, $name, $email, $phone, $courseTitle, $regionName, $districtName, $result);
    }
    fclose($fh);
}

function process_row($conn, $rowNum, $name, $email, $phone, $courseTitle, $regionName, $districtName, &$result) {
    $name = trim($name); $email = trim($email); $phone = trim($phone);
    $courseTitle = trim($courseTitle); $regionName=trim($regionName); $districtName=trim($districtName);

    if ($name==='' || $email==='' || $courseTitle==='') {
        $result['failed']++; $result['errors'][] = "الصف {$rowNum}: بيانات ناقصة"; return;
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $result['failed']++; $result['errors'][] = "الصف {$rowNum}: بريد غير صالح"; return;
    }
    $course_id = get_course_id($conn, $courseTitle);
    if (!$course_id) { $result['failed']++; $result['errors'][] = "الصف {$rowNum}: الدورة '{$courseTitle}' غير موجودة"; return; }

    $region_id = $regionName ? get_region_id($conn, $regionName) : null;
    $district_id = $districtName && $region_id ? get_district_id($conn, $districtName, $region_id) : null;

    // إذا البريد موجود: استخدم نفس المستخدم
    $user_id = null; $generated_password = null;
    $stmt=$conn->prepare("SELECT id FROM users WHERE email=? LIMIT 1"); $stmt->bind_param('s',$email); $stmt->execute(); $res=$stmt->get_result();
    if ($res && $res->num_rows) {
        $user_id = $res->fetch_assoc()['id'];
    } else {
        // إنشاء مستخدم
        $generated_password = preg_replace('/[^0-9]/','',$phone);
        if (!$generated_password) $generated_password = substr(bin2hex(random_bytes(4)),0,8);
        $hash = password_hash($generated_password, PASSWORD_DEFAULT);

        if (col_exists($conn,'password_hash')) {
            $sql = "INSERT INTO users (full_name, email, phone, password_hash, role, governorate, district, created_at) VALUES (?, ?, ?, ?, 'student', ?, ?, NOW())";
        } else {
            $sql = "INSERT INTO users (full_name, email, phone, password, role, governorate, district, created_at) VALUES (?, ?, ?, ?, 'student', ?, ?, NOW())";
        }
        $stmt = $conn->prepare($sql);
        $gov = $regionName ?: null; $dist = $districtName ?: null;
        $stmt->bind_param('ssssss', $name, $email, $phone, $hash, $gov, $dist);
        if (!$stmt->execute()) { $result['failed']++; $result['errors'][] = "الصف {$rowNum}: فشل إنشاء المستخدم - {$stmt->error}"; return; }
        $user_id = $conn->insert_id;
    }

    // إنشاء تسجيل بالدورة (enrollments) إن لم يكن موجوداً
    $enrollTbl = ($conn->query("SHOW TABLES LIKE 'enrollments'") && $conn->query("SHOW TABLES LIKE 'enrollments'")->num_rows>0);
    if ($enrollTbl) {
        $check=$conn->prepare("SELECT id FROM enrollments WHERE user_id=? AND course_id=? LIMIT 1");
        $check->bind_param('ii',$user_id,$course_id); $check->execute(); $cres=$check->get_result();
        if (!($cres && $cres->num_rows)) {
            $ins=$conn->prepare("INSERT INTO enrollments (user_id, course_id, status, enrollment_date) VALUES (?, ?, 'active', CURDATE())");
            if (!$ins) { $result['failed']++; $result['errors'][] = "الصف {$rowNum}: فشل تحضير استعلام التسجيل"; return; }
            $ins->bind_param('ii',$user_id,$course_id);
            if (!$ins->execute()) { $result['failed']++; $result['errors'][] = "الصف {$rowNum}: فشل تسجيل المتدرب - {$ins->error}"; return; }
        }
    }

    $result['imported']++;
}

// مسار الاستيراد
if ($ext === 'csv') {
    import_csv($conn, $tmp, $result);
} else {
    // XSLX عبر PhpSpreadsheet
    if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $result['success'] = false;
        $result['errors'][] = 'يلزم تثبيت phpoffice/phpspreadsheet لاستخراج ملفات Excel. يمكنك رفع CSV بدلاً من ذلك.';
        echo json_encode($result, JSON_UNESCAPED_UNICODE); exit;
    }
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($tmp);
        $spreadsheet = $reader->load($tmp);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestColumn = $sheet->getHighestColumn();
        $headers = [];
        // الصف 1: رؤوس
        $headersRow = 1;
        for ($col='A'; $col <= $highestColumn; $col = chr(ord($col)+1)) {
            $headers[] = trim((string)$sheet->getCell($col.$headersRow)->getValue());
        }
        $map = [];
        foreach ($headers as $i=>$h) {
            $lh = strtolower($h);
            if ($lh==='name' || $h==='الاسم') $map['name']=$i;
            elseif ($lh==='email' || $h==='الإيميل' || $h==='البريد' || $h==='البريد الإلكتروني') $map['email']=$i;
            elseif ($lh==='phone' || $h==='الهاتف' || $h==='رقم الهاتف') $map['phone']=$i;
            elseif ($lh==='course' || $lh==='course_title' || $h==='اسم_الدورة' || $h==='اسم الدورة') $map['course']=$i;
            elseif ($lh==='region' || $h==='المحافظة') $map['region']=$i;
            elseif ($lh==='district' || $h==='المديرية') $map['district']=$i;
        }
        for ($row=2; $row <= $highestRow; $row++) {
            $vals = [];
            for ($col='A'; $col <= $highestColumn; $col = chr(ord($col)+1)) {
                $vals[] = trim((string)$sheet->getCell($col.$row)->getValue());
            }
            $name = $vals[$map['name'] ?? 0] ?? '';
            $email = $vals[$map['email'] ?? 1] ?? '';
            $phone = $vals[$map['phone'] ?? 2] ?? '';
            $courseTitle = $vals[$map['course'] ?? 3] ?? '';
            $regionName = $vals[$map['region'] ?? 4] ?? '';
            $districtName = $vals[$map['district'] ?? 5] ?? '';
            process_row($conn, $row, $name, $email, $phone, $courseTitle, $regionName, $districtName, $result);
        }
    } catch (Exception $e) {
        $result['success'] = false;
        $result['errors'][] = 'خطأ أثناء قراءة ملف Excel: ' . $e->getMessage();
    }
}

echo json_encode($result, JSON_UNESCAPED_UNICODE);
