<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        echo $encoded === false
            ? '{"success":false,"message":"Fatal Error & JSON encoding failed."}'
            : $encoded;
    }
});

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../platform/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        echo '{"success":false,"message":"JSON encoding failed."}';
    } else {
        echo $encoded;
    }
    exit;
}

function normalizeText(?string $value): string
{
    if ($value === null) {
        return '';
    }
    $value = trim((string) $value);
    if ($value === '') {
        return '';
    }
    $value = mb_strtolower($value, 'UTF-8');
    $value = str_replace(['.', '-', '_', '\\', '/', '(', ')'], ' ', $value);
    $value = preg_replace('/\s+/', ' ', $value);
    return trim($value);
}

function detectColumns(array $headers): array
{
    $keywords = [
        'full_name' => ['name', 'full name', 'fullname', 'student name', 'اسم', 'الاسم', 'اسم الطالب', 'اسم المتدرب'],
        'full_name_en' => ['english name', 'name en', 'full name en'],
        'email' => ['email', 'البريد', 'البريد الالكتروني', 'البريد الإلكتروني'],
        'phone' => ['phone', 'رقم', 'mobile', 'رقم الهاتف', 'الجوال'],
        'gender' => ['gender', 'الجنس', 'النوع'],
        'governorate' => ['governorate', 'المحافظة', 'المحافطه'],
        'district' => ['district', 'المديرية', 'المديريه'],
        'sub_district' => ['sub district', 'العزلة', 'القرية', 'الناحية', 'subdistrict'],
        'course_title' => ['course', 'course title', 'اسم الدورة', 'الدورة', 'برنامج'],
        'status' => ['status', 'الحالة', 'حالة التسجيل', 'حالة الانجاز', 'التقدم'],
        'grade_value' => ['grade', 'الدرجة', 'mark', 'score', 'result', 'التقدير'],
        'grade_max' => ['max grade', 'الدرجة النهائية', 'من'],
        'assignment_name' => ['assignment', 'الواجب', 'التقييم', 'الامتحان', 'الاختبار'],
        'enrolled_at' => ['enrolled', 'enrollment date', 'registration date', 'تاريخ التسجيل', 'تاريخ الالتحاق'],
        'dob' => ['dob', 'birth', 'تاريخ الميلاد', 'ميلاد'],
        'progress' => ['progress', 'نسبة التقدم', 'التقدم']
    ];

    $detected = [];
    foreach ($headers as $index => $header) {
        $normalized = normalizeText($header);
        if ($normalized === '') {
            continue;
        }
        foreach ($keywords as $field => $candidates) {
            if (isset($detected[$field])) {
                continue;
            }
            foreach ($candidates as $candidate) {
                $candidateNorm = normalizeText($candidate);
                if ($candidateNorm !== '' && (mb_strpos($normalized, $candidateNorm) !== false || $normalized === $candidateNorm)) {
                    $detected[$field] = $index;
                    break 2;
                }
            }
        }
    }

    return $detected;
}

function sanitizeGender(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $normalized = normalizeText($value);
    if ($normalized === '') {
        return null;
    }
    if (in_array($normalized, ['male', 'm', 'ذكر'], true)) {
        return 'male';
    }
    if (in_array($normalized, ['female', 'f', 'انثى', 'أنثى'], true)) {
        return 'female';
    }
    return null;
}

function normalizeStatus(?string $value): string
{
    $default = 'active';
    if ($value === null) {
        return $default;
    }
    $normalized = normalizeText($value);
    if ($normalized === '') {
        return $default;
    }
    $map = [
        'pending' => 'pending',
        'قيد الانتظار' => 'pending',
        'معلق' => 'pending',
        'active' => 'active',
        'نشط' => 'active',
        'فعال' => 'active',
        'قيد التنفيذ' => 'active',
        'completed' => 'completed',
        'مكتمل' => 'completed',
        'منجز' => 'completed',
        'finished' => 'completed',
        'dropped' => 'dropped',
        'منسحب' => 'dropped',
        'انسحاب' => 'dropped'
    ];
    foreach ($map as $key => $status) {
        if (mb_strpos($normalized, normalizeText($key)) !== false) {
            return $status;
        }
    }
    return $default;
}

function parseDateValue(?string $value): ?string
{
    if ($value === null) {
        return null;
    }
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return null;
    }
    // Try Excel serialized value
    if (is_numeric($trimmed)) {
        $excelDate = (float) $trimmed;
        // PhpSpreadsheet stores as days since 1899-12-30
        $timestamp = ($excelDate - 25569) * 86400;
        if ($timestamp > 0) {
            return gmdate('Y-m-d', (int) $timestamp);
        }
    }
    $trimmed = str_replace('/', '-', $trimmed);
    $time = strtotime($trimmed);
    if ($time === false) {
        return null;
    }
    return date('Y-m-d', $time);
}

function parseProgress(?string $value): ?int
{
    if ($value === null) {
        return null;
    }
    $trimmed = trim((string) $value);
    if ($trimmed === '') {
        return null;
    }
    if (!is_numeric($trimmed)) {
        return null;
    }
    $progress = (int) round((float) $trimmed);
    if ($progress < 0) {
        $progress = 0;
    }
    if ($progress > 100) {
        $progress = 100;
    }
    return $progress;
}

function parseGrade(?string $value): ?float
{
    if ($value === null) {
        return null;
    }
    $trimmed = str_replace(',', '.', trim((string) $value));
    if ($trimmed === '') {
        return null;
    }
    if (!is_numeric($trimmed)) {
        return null;
    }
    return round((float) $trimmed, 2);
}

function getUserRecord(mysqli $conn, ?string $email, ?string $phone, string $fullName): ?array
{
    if ($email) {
        $stmt = $conn->prepare('SELECT id, full_name, full_name_en, email, phone, gender, governorate, district, sub_district, dob FROM users WHERE email = ? LIMIT 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();
        if ($record) {
            return $record;
        }
    }

    if ($phone) {
    $stmt = $conn->prepare('SELECT id, full_name, full_name_en, email, phone, gender, governorate, district, sub_district, dob FROM users WHERE phone = ? LIMIT 1');
        $stmt->bind_param('s', $phone);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();
        if ($record) {
            return $record;
        }
    }

    $stmt = $conn->prepare('SELECT id, full_name, full_name_en, email, phone, gender, governorate, district, sub_district, dob FROM users WHERE full_name = ? LIMIT 1');
    $stmt->bind_param('s', $fullName);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    return $record ?: null;
}

function ensureEmail(mysqli $conn, ?string $email, string $fullName, int $rowNumber, array &$summary): string
{
    $candidate = $email ? trim(mb_strtolower($email, 'UTF-8')) : '';
    if ($candidate !== '') {
        return $candidate;
    }
    $placeholder = 'student.' . uniqid() . '@ibdaa-temp.local';
    $summary['warnings'][] = "الصف {$rowNumber}: لم يتم تزويد بريد إلكتروني، تم إنشاء بريد مؤقت {$placeholder}";
    return $placeholder;
}

function resolveCourseId(mysqli $conn, string $title, array &$cache): ?array
{
    $normalized = normalizeText($title);
    if ($normalized === '') {
        return null;
    }

    if (isset($cache[$normalized])) {
        return $cache[$normalized];
    }

    $stmt = $conn->prepare('SELECT course_id, title FROM courses WHERE title = ? LIMIT 1');
    $stmt->bind_param('s', $title);
    $stmt->execute();
    $result = $stmt->get_result();
    $record = $result->fetch_assoc();
    $stmt->close();

    if (!$record) {
        $like = '%' . $title . '%';
        $stmt = $conn->prepare('SELECT course_id, title FROM courses WHERE title LIKE ? LIMIT 1');
        $stmt->bind_param('s', $like);
        $stmt->execute();
        $result = $stmt->get_result();
        $record = $result->fetch_assoc();
        $stmt->close();
    }

    if ($record) {
        $cache[$normalized] = $record;
        return $record;
    }

    return null;
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || !in_array($userRole, ['manager', 'technical'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
}

if (!isset($_FILES['import_file'])) {
    respond(['success' => false, 'message' => 'الرجاء اختيار ملف للاستيراد'], 400);
}

$file = $_FILES['import_file'];
if (!is_array($file) || ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'message' => 'تعذر رفع الملف، الرجاء المحاولة مجدداً'], 400);
}

$extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
$allowed = ['csv', 'xlsx', 'xls'];
if (!in_array($extension, $allowed, true)) {
    respond(['success' => false, 'message' => 'صيغة الملف غير مدعومة، الرجاء استخدام CSV أو Excel'], 400);
}

$tempPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('ibdaa_import_', true) . '.' . $extension;
if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
    respond(['success' => false, 'message' => 'فشل في تجهيز الملف المؤقت'], 500);
}

try {
    $reader = IOFactory::createReaderForFile($tempPath);
    $reader->setReadDataOnly(true);
    $spreadsheet = $reader->load($tempPath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray(null, true, true, true);
} catch (Throwable $throwable) {
    if (is_file($tempPath)) {
        unlink($tempPath);
    }
    respond(['success' => false, 'message' => 'فشل قراءة الملف: ' . $throwable->getMessage()], 500);
}

if (is_file($tempPath)) {
    unlink($tempPath);
}

if (count($rows) < 2) {
    respond(['success' => false, 'message' => 'الملف لا يحتوي على بيانات قابلة للاستيراد'], 400);
}

$headerRow = array_shift($rows);
$headers = array_values($headerRow);
$detectedColumns = detectColumns($headers);

if (!isset($detectedColumns['full_name'])) {
    respond(['success' => false, 'message' => 'تعذر تحديد عمود الاسم في الملف، الرجاء التأكد من وجوده'], 400);
}

$summary = [
    'processed_rows' => 0,
    'created_users' => 0,
    'updated_users' => 0,
    'created_enrollments' => 0,
    'created_grades' => 0,
    'warnings' => [],
    'errors' => [],
    'detected_columns' => $detectedColumns
];

if (!isset($detectedColumns['grade_value'])) {
    $summary['warnings'][] = 'لم يتم التعرف على عمود الدرجة، سيتم تخطي إدخال الدرجات.';
}

$courseCache = [];

foreach ($rows as $lineNumber => $row) {
    $rowValues = array_values($row);
    $rowNumber = $lineNumber + 2; // حساب الصف الحقيقي داخل الملف

    $hasValue = false;
    foreach ($rowValues as $value) {
        if (trim((string) $value) !== '') {
            $hasValue = true;
            break;
        }
    }
    if (!$hasValue) {
        continue;
    }

    $summary['processed_rows']++;

    $getValue = function (string $key) use ($detectedColumns, $rowValues) {
        if (!isset($detectedColumns[$key])) {
            return null;
        }
        $index = $detectedColumns[$key];
        return $rowValues[$index] ?? null;
    };

    $fullName = trim((string) ($getValue('full_name') ?? ''));
    if ($fullName === '') {
        $summary['errors'][] = "الصف {$rowNumber}: الاسم فارغ، تم تخطي السجل.";
        continue;
    }

    $fullNameEn = trim((string) ($getValue('full_name_en') ?? ''));
    $email = $getValue('email');
    $email = $email !== null ? trim((string) $email) : null;
    $phone = $getValue('phone');
    $phone = $phone !== null ? trim((string) $phone) : null;
    $gender = sanitizeGender($getValue('gender'));
    $governorate = trim((string) ($getValue('governorate') ?? '')) ?: null;
    $district = trim((string) ($getValue('district') ?? '')) ?: null;
    $subDistrict = trim((string) ($getValue('sub_district') ?? '')) ?: null;
    $dob = parseDateValue($getValue('dob'));

    $email = ensureEmail($conn, $email, $fullName, $rowNumber, $summary);

    $existingUser = getUserRecord($conn, $email, $phone, $fullName);
    $userId = null;
    $userUpdated = false;

    if ($existingUser) {
        $userId = (int) $existingUser['id'];
        $updateFields = [];
        $updateTypes = '';
        $updateParams = [];

        if ($fullName !== '' && $fullName !== (string) $existingUser['full_name']) {
            $updateFields[] = 'full_name = ?';
            $updateTypes .= 's';
            $updateParams[] = $fullName;
        }
        if ($fullNameEn !== '' && $fullNameEn !== (string) ($existingUser['full_name_en'] ?? '')) {
            $updateFields[] = 'full_name_en = ?';
            $updateTypes .= 's';
            $updateParams[] = $fullNameEn;
        }
        if ($phone && $phone !== (string) ($existingUser['phone'] ?? '')) {
            $updateFields[] = 'phone = ?';
            $updateTypes .= 's';
            $updateParams[] = $phone;
        }
        if ($gender && $gender !== (string) ($existingUser['gender'] ?? '')) {
            $updateFields[] = 'gender = ?';
            $updateTypes .= 's';
            $updateParams[] = $gender;
        }
        if ($governorate && $governorate !== (string) ($existingUser['governorate'] ?? '')) {
            $updateFields[] = 'governorate = ?';
            $updateTypes .= 's';
            $updateParams[] = $governorate;
        }
        if ($district && $district !== (string) ($existingUser['district'] ?? '')) {
            $updateFields[] = 'district = ?';
            $updateTypes .= 's';
            $updateParams[] = $district;
        }
        if ($subDistrict && $subDistrict !== (string) ($existingUser['sub_district'] ?? '')) {
            $updateFields[] = 'sub_district = ?';
            $updateTypes .= 's';
            $updateParams[] = $subDistrict;
        }
        if ($dob) {
            $updateFields[] = 'dob = ?';
            $updateTypes .= 's';
            $updateParams[] = $dob;
        }

        if ($updateFields) {
            $updateFields[] = 'updated_at = CURRENT_TIMESTAMP';
            $sql = 'UPDATE users SET ' . implode(', ', $updateFields) . ' WHERE id = ? LIMIT 1';
            $updateTypes .= 'i';
            $updateParams[] = $userId;
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($updateTypes, ...$updateParams);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                $userUpdated = true;
            }
            $stmt->close();
        }
    } else {
        $password = bin2hex(random_bytes(6));
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
            'INSERT INTO users (full_name, full_name_en, email, phone, password_hash, role, gender, governorate, district, sub_district, dob, verified)
             VALUES (?, ?, ?, ?, ?, "student", ?, ?, ?, ?, ?, 1)'
        );
        $stmt->bind_param(
            'ssssssssss',
            $fullName,
            $fullNameEn,
            $email,
            $phone,
            $passwordHash,
            $gender,
            $governorate,
            $district,
            $subDistrict,
            $dob
        );
        if (!$stmt->execute()) {
            $summary['errors'][] = "الصف {$rowNumber}: فشل إنشاء المستخدم - " . $stmt->error;
            $stmt->close();
            continue;
        }
        $userId = (int) $stmt->insert_id;
        $stmt->close();
        $summary['created_users']++;
    }

    if ($userUpdated) {
        $summary['updated_users']++;
    }

    if (!$userId) {
        $summary['errors'][] = "الصف {$rowNumber}: تعذر تحديد معرف المستخدم";
        continue;
    }

    $courseTitle = trim((string) ($getValue('course_title') ?? ''));
    $courseInfo = $courseTitle !== '' ? resolveCourseId($conn, $courseTitle, $courseCache) : null;

    if ($courseTitle !== '' && !$courseInfo) {
        $summary['warnings'][] = "الصف {$rowNumber}: لم يتم العثور على دورة بعنوان '{$courseTitle}'";
    }

    $enrollmentStatusRaw = $getValue('status');
    $enrollmentStatus = normalizeStatus($enrollmentStatusRaw);
    $progressValue = parseProgress($getValue('progress'));
    $enrolledAt = parseDateValue($getValue('enrolled_at'));

    if ($courseInfo) {
        $courseId = (int) $courseInfo['course_id'];
        $stmt = $conn->prepare('SELECT enrollment_id, status FROM enrollments WHERE user_id = ? AND course_id = ? LIMIT 1');
        $stmt->bind_param('ii', $userId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        $enrollment = $result->fetch_assoc();
        $stmt->close();

        if ($enrollment) {
            $updateFields = [];
            $types = '';
            $params = [];
            if ($enrollmentStatus && $enrollmentStatus !== $enrollment['status']) {
                $updateFields[] = 'status = ?';
                $types .= 's';
                $params[] = $enrollmentStatus;
            }
            if ($progressValue !== null) {
                $updateFields[] = 'progress = ?';
                $types .= 'i';
                $params[] = $progressValue;
            }
            if ($enrolledAt) {
                $updateFields[] = 'created_at = ?';
                $types .= 's';
                $params[] = $enrolledAt . ' 00:00:00';
            }
            if ($updateFields) {
                $updateFields[] = 'last_activity_at = NOW()';
                $sql = 'UPDATE enrollments SET ' . implode(', ', $updateFields) . ' WHERE enrollment_id = ? LIMIT 1';
                $types .= 'i';
                $params[] = (int) $enrollment['enrollment_id'];
                $stmt = $conn->prepare($sql);
                $stmt->bind_param($types, ...$params);
                $stmt->execute();
                $stmt->close();
            }
        } else {
            if ($progressValue === null) {
                $progressValue = 0;
            }
            if ($enrolledAt) {
                $stmt = $conn->prepare(
                    'INSERT INTO enrollments (user_id, course_id, status, payment_status, progress, last_activity_at, created_at)
                     VALUES (?, ?, ?, "pending", ?, NOW(), ?)'
                );
                $createdAt = $enrolledAt . ' 00:00:00';
                $stmt->bind_param('iisis', $userId, $courseId, $enrollmentStatus, $progressValue, $createdAt);
            } else {
                $stmt = $conn->prepare(
                    'INSERT INTO enrollments (user_id, course_id, status, payment_status, progress, last_activity_at)
                     VALUES (?, ?, ?, "pending", ?, NOW())'
                );
                $stmt->bind_param('iisi', $userId, $courseId, $enrollmentStatus, $progressValue);
            }
            if ($stmt->execute()) {
                $summary['created_enrollments']++;
            } else {
                $summary['errors'][] = "الصف {$rowNumber}: فشل إنشاء التسجيل - " . $stmt->error;
            }
            $stmt->close();
        }

        if (isset($detectedColumns['grade_value'])) {
            $gradeValue = parseGrade($getValue('grade_value'));
            if ($gradeValue !== null) {
                $assignmentName = trim((string) ($getValue('assignment_name') ?? ''));
                if ($assignmentName === '') {
                    $assignmentName = 'تقييم مستورد';
                }
                $stmt = $conn->prepare('SELECT grade_id FROM grades WHERE user_id = ? AND course_id = ? AND assignment_name = ? LIMIT 1');
                $stmt->bind_param('iis', $userId, $courseId, $assignmentName);
                $stmt->execute();
                $result = $stmt->get_result();
                $grade = $result->fetch_assoc();
                $stmt->close();

                if ($grade) {
                    $stmt = $conn->prepare('UPDATE grades SET grade_value = ?, graded_by = ?, graded_at = NOW() WHERE grade_id = ? LIMIT 1');
                    $stmt->bind_param('dii', $gradeValue, $userId, $grade['grade_id']);
                    $stmt->execute();
                    $stmt->close();
                } else {
                    $stmt = $conn->prepare(
                        'INSERT INTO grades (user_id, course_id, assignment_name, grade_value, graded_by, graded_at)
                         VALUES (?, ?, ?, ?, ?, NOW())'
                    );
                    $stmt->bind_param('iisdi', $userId, $courseId, $assignmentName, $gradeValue, $userId);
                    if ($stmt->execute()) {
                        $summary['created_grades']++;
                    } else {
                        $summary['warnings'][] = "الصف {$rowNumber}: تعذر حفظ الدرجة - " . $stmt->error;
                    }
                    $stmt->close();
                }
            } else {
                $summary['warnings'][] = "الصف {$rowNumber}: لم يتم العثور على قيمة صالحة في عمود الدرجة.";
            }
        }
    }
}

respond([
    'success' => true,
    'message' => 'تمت معالجة الملف بنجاح',
    'summary' => $summary
]);
