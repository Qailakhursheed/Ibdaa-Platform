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

// التحقق من الصلاحيات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success'=>false,'message'=>'POST مطلوب'], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من course_id
$course_id = $_POST['course_id'] ?? null;
if (!$course_id || !is_numeric($course_id)) {
    echo json_encode(['success'=>false,'message'=>'يجب تحديد الدورة'], JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الملف المرفوع
$upload = $_FILES['grades_file'] ?? null;
if (!$upload || $upload['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success'=>false,'message'=>'لم يتم استقبال الملف أو حدث خطأ في الرفع'], JSON_UNESCAPED_UNICODE);
    exit;
}

$filename = $upload['name'];
$tmp = $upload['tmp_name'];
$ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

// تهيئة التقرير
$result = [
    'success' => true,
    'imported' => 0,
    'updated' => 0,
    'failed' => 0,
    'errors' => []
];

// دالة مساعدة للبحث عن الطالب
function find_student($conn, $identifier) {
    // البحث بالاسم الكامل أولاً
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE full_name = ? AND role = 'trainee' LIMIT 1");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['user_id'];
    }
    
    // إذا لم يتم العثور، جرب البحث بالبريد الإلكتروني
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? AND role = 'trainee' LIMIT 1");
    $stmt->bind_param("s", $identifier);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($row = $res->fetch_assoc()) {
        return $row['user_id'];
    }
    
    return null;
}

// دالة معالجة صف واحد
function process_grade_row($conn, $row_data, $course_id, &$result) {
    // استخراج البيانات من الصف
    $student_identifier = trim($row_data[0] ?? ''); // الاسم أو الإيميل
    $grade_value = trim($row_data[1] ?? ''); // الدرجة
    $max_grade = trim($row_data[2] ?? 100); // الدرجة القصوى (اختياري، افتراضي 100)
    $assignment_name = trim($row_data[3] ?? 'الامتحان النهائي'); // اسم المهمة (اختياري)
    $notes = trim($row_data[4] ?? ''); // ملاحظات (اختياري)
    
    // التحقق من البيانات الأساسية
    if (empty($student_identifier)) {
        $result['failed']++;
        $result['errors'][] = "صف فارغ: لم يتم تحديد اسم الطالب";
        return;
    }
    
    if (!is_numeric($grade_value)) {
        $result['failed']++;
        $result['errors'][] = "الطالب '$student_identifier': الدرجة غير صالحة";
        return;
    }
    
    // البحث عن الطالب
    $user_id = find_student($conn, $student_identifier);
    if (!$user_id) {
        $result['failed']++;
        $result['errors'][] = "الطالب '$student_identifier' غير موجود في النظام";
        return;
    }
    
    // التحقق من أن الطالب مسجل في الدورة
    $check_stmt = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $check_stmt->bind_param("ii", $user_id, $course_id);
    $check_stmt->execute();
    $check_res = $check_stmt->get_result();
    
    if ($check_res->num_rows === 0) {
        $result['failed']++;
        $result['errors'][] = "الطالب '$student_identifier' غير مسجل في هذه الدورة";
        return;
    }
    
    // إدراج أو تحديث الدرجة
    $stmt = $conn->prepare("
        INSERT INTO grades (user_id, course_id, assignment_name, grade_value, max_grade, notes, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE 
            grade_value = VALUES(grade_value),
            max_grade = VALUES(max_grade),
            notes = VALUES(notes),
            updated_at = NOW()
    ");
    
    $stmt->bind_param("iisdds", $user_id, $course_id, $assignment_name, $grade_value, $max_grade, $notes);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 1) {
            $result['updated']++;
        } else {
            $result['imported']++;
        }
    } else {
        $result['failed']++;
        $result['errors'][] = "فشل حفظ درجة الطالب '$student_identifier': " . $stmt->error;
    }
}

// معالجة CSV
function import_grades_csv($conn, $filepath, $course_id, &$result) {
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        $result['success'] = false;
        $result['errors'][] = 'فشل فتح ملف CSV';
        return;
    }
    
    // تخطي صف العناوين
    $headers = fgetcsv($handle);
    
    $row_number = 1;
    while (($row = fgetcsv($handle)) !== false) {
        $row_number++;
        
        // تخطي الصفوف الفارغة
        if (empty(array_filter($row))) {
            continue;
        }
        
        process_grade_row($conn, $row, $course_id, $result);
    }
    
    fclose($handle);
}

// معالجة Excel (XLSX)
function import_grades_excel($conn, $filepath, $course_id, &$result) {
    try {
        $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($filepath);
        $spreadsheet = $reader->load($filepath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        
        // البدء من الصف 2 (تخطي العناوين)
        for ($row = 2; $row <= $highestRow; $row++) {
            $row_data = [];
            
            // قراءة أول 5 أعمدة (اسم، درجة، درجة قصوى، اسم المهمة، ملاحظات)
            for ($col = 'A'; $col <= 'E'; $col++) {
                $cellValue = $sheet->getCell($col . $row)->getValue();
                $row_data[] = $cellValue;
            }
            
            // تخطي الصفوف الفارغة
            if (empty(array_filter($row_data))) {
                continue;
            }
            
            process_grade_row($conn, $row_data, $course_id, $result);
        }
        
    } catch (Exception $e) {
        $result['success'] = false;
        $result['errors'][] = 'خطأ في قراءة ملف Excel: ' . $e->getMessage();
    }
}

// تحديد نوع الملف والمعالجة
if ($ext === 'csv') {
    import_grades_csv($conn, $tmp, $course_id, $result);
} elseif (in_array($ext, ['xlsx', 'xls'])) {
    if (!class_exists('PhpOffice\\PhpSpreadsheet\\IOFactory')) {
        $result['success'] = false;
        $result['errors'][] = 'يلزم تثبيت phpoffice/phpspreadsheet لاستخراج ملفات Excel. يمكنك رفع CSV بدلاً من ذلك.';
    } else {
        import_grades_excel($conn, $tmp, $course_id, $result);
    }
} else {
    $result['success'] = false;
    $result['errors'][] = 'صيغة الملف غير مدعومة. استخدم CSV أو XLSX';
}

// إرجاع النتيجة
echo json_encode($result, JSON_UNESCAPED_UNICODE);
?>
