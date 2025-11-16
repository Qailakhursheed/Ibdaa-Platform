<?php
/**
 * Smart Import - Step 2: Process Mapped Import
 * Executes the actual data import based on user's column mapping
 */

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

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as XlsDate;

header('Content-Type: application/json; charset=utf-8');

// التحقق من الصلاحيات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك بالوصول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // قراءة البيانات من الطلب
    $input = json_decode(file_get_contents('php://input'), true);
    $filePath = $input['filePath'] ?? '';
    $mapping = $input['mapping'] ?? [];

    if (empty($filePath) || empty($mapping)) {
        echo json_encode([
            'success' => false,
            'message' => 'بيانات غير كاملة'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // تحديد المسار الفعلي للملف المؤقت بشكل آمن
    $candidatePaths = [];
    if (!empty($filePath) && is_string($filePath)) {
        // إذا كان مساراً كاملاً
        if (file_exists($filePath)) {
            $candidatePaths[] = $filePath;
        }
        // جرّب ضمن المجلدات المعروفة
        $candidatePaths[] = __DIR__ . '/../../uploads/temp/' . basename($filePath);
        $candidatePaths[] = __DIR__ . '/../../uploads/tmp_imports/' . basename($filePath);
    }

    $fullPath = null;
    foreach ($candidatePaths as $cand) {
        if (file_exists($cand)) {
            $fullPath = realpath($cand);
            break;
        }
    }

    if (!$fullPath) {
        echo json_encode([
            'success' => false,
            'message' => 'الملف غير موجود'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // تأمين أن الملف داخل مجلدات الرفع المؤقتة فقط
    $allowedRoots = [
        realpath(__DIR__ . '/../../uploads/temp/'),
        realpath(__DIR__ . '/../../uploads/tmp_imports/')
    ];
    $isAllowed = false;
    foreach ($allowedRoots as $root) {
        if ($root && strpos($fullPath, $root) === 0) { $isAllowed = true; break; }
    }
    if (!$isAllowed) {
        echo json_encode([
            'success' => false,
            'message' => 'مسار الملف غير مسموح'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // فتح الملف
    $spreadsheet = IOFactory::load($fullPath);
    $worksheet = $spreadsheet->getActiveSheet();
    $highestRow = $worksheet->getHighestRow();

    // قراءة الصف الأول (العناوين) لإنشاء خريطة الفهارس
    $headerRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1', null, true, false)[0];
    
    // إنشاء خريطة عكسية: column_name => column_index
    $columnIndexMap = [];
    foreach ($headerRow as $index => $header) {
        if (!empty($header)) {
            $columnIndexMap[trim($header)] = $index;
        }
    }

    // متغيرات التقرير
    $successCount = 0;
    $failedCount = 0;
    $errors = [];
    $totalRows = $highestRow - 1; // استبعاد صف العناوين

    // قراءة كل صف وتنفيذ الاستيراد
    for ($row = 2; $row <= $highestRow; $row++) {
        try {
            // قراءة الصف الكامل
            $rowData = $worksheet->rangeToArray('A' . $row . ':' . $worksheet->getHighestColumn() . $row, null, true, false)[0];

            // استخراج البيانات بناءً على الخريطة
            $studentData = [];
            foreach ($mapping as $fieldKey => $columnName) {
                if (!empty($columnName) && isset($columnIndexMap[$columnName])) {
                    $columnIndex = $columnIndexMap[$columnName];
                    $studentData[$fieldKey] = isset($rowData[$columnIndex]) ? trim($rowData[$columnIndex]) : '';
                } else {
                    $studentData[$fieldKey] = '';
                }
            }

            // التحقق من الحقول الإلزامية
            if (empty($studentData['full_name']) || empty($studentData['email']) || empty($studentData['course_name'])) {
                $errors[] = "صف {$row}: بيانات ناقصة (الاسم، الإيميل، أو الدورة)";
                $failedCount++;
                continue;
            }

            // التحقق من صحة الإيميل
            if (!filter_var($studentData['email'], FILTER_VALIDATE_EMAIL)) {
                $errors[] = "صف {$row}: إيميل غير صحيح ({$studentData['email']})";
                $failedCount++;
                continue;
            }

            // التحقق من وجود المستخدم مسبقاً
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->bind_param('s', $studentData['email']);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $errors[] = "صف {$row}: الإيميل موجود مسبقاً ({$studentData['email']})";
                $failedCount++;
                continue;
            }

            // البحث عن الدورة
            $courseId = null;
            $stmt = $conn->prepare("SELECT course_id FROM courses WHERE title LIKE ? LIMIT 1");
            $courseLike = '%' . $studentData['course_name'] . '%';
            $stmt->bind_param('s', $courseLike);
            $stmt->execute();
            $result = $stmt->get_result();
            if ($result && $result->num_rows > 0) {
                $courseRow = $result->fetch_assoc();
                $courseId = (int)$courseRow['course_id'];
            } else {
                $errors[] = "صف {$row}: الدورة غير موجودة ({$studentData['course_name']})";
                $failedCount++;
                continue;
            }

            // إنشاء كلمة مرور عشوائية
            $password = bin2hex(random_bytes(4)); // 8 characters
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

            // بدء Transaction
            $conn->begin_transaction();

            try {
                // تحويل تاريخ الميلاد إذا كان رقم Excel
                $dob = null;
                if (!empty($studentData['dob'])) {
                    if (is_numeric($studentData['dob'])) {
                        try {
                            $dt = XlsDate::excelToDateTimeObject((float)$studentData['dob']);
                            $dob = $dt ? $dt->format('Y-m-d') : null;
                        } catch (\Throwable $e) {
                            $dob = null;
                        }
                    } else {
                        $ts = strtotime($studentData['dob']);
                        $dob = $ts ? date('Y-m-d', $ts) : null;
                    }
                }

                $fullNameEn = $studentData['full_name']; // يمكن تحسينه لاحقاً بإزالة الأحرف العربية
                $phone = $studentData['phone'] ?? '';
                $governorate = $studentData['governorate'] ?? '';
                $district = $studentData['district'] ?? '';

                // إدراج المستخدم وفق المخطط الحالي
                $stmt = $conn->prepare(
                    "INSERT INTO users (full_name, full_name_en, email, phone, password_hash, role, dob, governorate, district)
                     VALUES (?, ?, ?, ?, ?, 'student', ?, ?, ?)"
                );
                $stmt->bind_param(
                    'ssssssss',
                    $studentData['full_name'],
                    $fullNameEn,
                    $studentData['email'],
                    $phone,
                    $hashedPassword,
                    $dob,
                    $governorate,
                    $district
                );
                $stmt->execute();
                $newUserId = (int)$conn->insert_id;

                // تسجيل الطالب في الدورة
                $stmt = $conn->prepare(
                    "INSERT INTO enrollments (user_id, course_id, status) VALUES (?, ?, 'pending')"
                );
                $stmt->bind_param('ii', $newUserId, $courseId);
                $stmt->execute();

                // إدخال الدرجة إذا كانت موجودة
                if (!empty($studentData['grade']) && is_numeric($studentData['grade'])) {
                    $stmt = $conn->prepare(
                        "INSERT INTO grades (user_id, course_id, assignment_name, grade_value) VALUES (?, ?, ?, ?)"
                    );
                    $grade = (float)$studentData['grade'];
                    $assignment = 'استيراد Excel';
                    $stmt->bind_param('iisd', $newUserId, $courseId, $assignment, $grade);
                    $stmt->execute();
                }

                // تأكيد Transaction
                $conn->commit();
                $successCount++;

            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = "صف {$row}: فشل الإدراج - " . $e->getMessage();
                $failedCount++;
            }

        } catch (Exception $e) {
            $errors[] = "صف {$row}: خطأ في المعالجة - " . $e->getMessage();
            $failedCount++;
        }
    }

    // حذف الملف المؤقت
    if (file_exists($fullPath)) {
        unlink($fullPath);
    }

    // إرجاع التقرير النهائي
    echo json_encode([
        'success' => true,
        'total_rows' => $totalRows,
        'success_count' => $successCount,
        'failed_count' => $failedCount,
        'errors' => $errors,
        'message' => "اكتمل الاستيراد: {$successCount} نجح، {$failedCount} فشل"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // حذف الملف في حالة الخطأ
    if (isset($fullPath) && file_exists($fullPath)) {
        unlink($fullPath);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
