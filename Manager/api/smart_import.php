<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);
ini_set('max_execution_time', 300); // 5 دقائق

require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

header('Content-Type: application/json; charset=utf-8');

/**
 * نظام استيراد البيانات الذكي - Smart Data Import System
 * يدعم: Drag & Drop, Excel/CSV, معالجة ذكية, تقارير مفصلة
 */

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الصلاحيات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? ($_POST['action'] ?? '');

try {
    // ==============================================
    // POST: رفع ومعالجة ملف
    // ==============================================
    if ($method === 'POST' && $action === 'upload') {
        
        // التحقق من وجود ملف
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            respond(['success' => false, 'message' => 'لم يتم رفع ملف صحيح'], 400);
        }
        
        $file = $_FILES['file'];
        $import_type = $_POST['import_type'] ?? 'students'; // students, trainers, courses, payments
        
        $allowed_extensions = ['xlsx', 'xls', 'csv'];
        $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_extension, $allowed_extensions)) {
            respond(['success' => false, 'message' => 'نوع الملف غير مدعوم. استخدم Excel أو CSV'], 400);
        }
        
        // إنشاء مجلد للتخزين المؤقت
        $upload_dir = __DIR__ . '/../../uploads/imports/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $temp_file = $upload_dir . uniqid('import_') . '.' . $file_extension;
        
        if (!move_uploaded_file($file['tmp_name'], $temp_file)) {
            respond(['success' => false, 'message' => 'فشل حفظ الملف'], 500);
        }
        
        // قراءة الملف
        try {
            $spreadsheet = IOFactory::load($temp_file);
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);
            
            if (empty($rows)) {
                unlink($temp_file);
                respond(['success' => false, 'message' => 'الملف فارغ'], 400);
            }
            
            // الصف الأول هو العناوين
            $headers = array_shift($rows);
            $headers = array_map('trim', $headers);
            $total_rows = count($rows);
            
            // معالجة البيانات حسب النوع
            $results = [];
            $success_count = 0;
            $failed_count = 0;
            $errors = [];
            
            switch ($import_type) {
                case 'students':
                    $results = importStudents($conn, $headers, $rows, $errors, $success_count, $failed_count);
                    break;
                    
                case 'trainers':
                    $results = importTrainers($conn, $headers, $rows, $errors, $success_count, $failed_count);
                    break;
                    
                case 'courses':
                    $results = importCourses($conn, $headers, $rows, $errors, $success_count, $failed_count);
                    break;
                    
                case 'payments':
                    $results = importPayments($conn, $headers, $rows, $errors, $success_count, $failed_count);
                    break;
                    
                default:
                    unlink($temp_file);
                    respond(['success' => false, 'message' => 'نوع استيراد غير معروف'], 400);
            }
            
            // حفظ الملف في الأرشيف
            $archive_dir = __DIR__ . '/../../uploads/imports/archive/';
            if (!is_dir($archive_dir)) {
                mkdir($archive_dir, 0755, true);
            }
            $archive_filename = date('Ymd_His_') . preg_replace('/[^a-zA-Z0-9._-]/', '', $file['name']);
            $archive_path = $archive_dir . $archive_filename;
            
            // نقل الملف بدلاً من حذفه
            if (file_exists($temp_file)) {
                rename($temp_file, $archive_path);
            }
            $relative_path = 'uploads/imports/archive/' . $archive_filename;

            // حفظ سجل الاستيراد في import_history
            $status = ($failed_count > 0) ? ($success_count > 0 ? 'partial' : 'failed') : 'success';
            
            $hist_stmt = $conn->prepare("
                INSERT INTO import_history 
                (file_name, original_name, import_type, imported_by, records_count, status, file_path) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $hist_stmt->bind_param('sssiiss', $archive_filename, $file['name'], $import_type, $user_id, $total_rows, $status, $relative_path);
            $hist_stmt->execute();
            $import_id = $hist_stmt->insert_id;
            $hist_stmt->close();
            
            respond([
                'success' => true,
                'message' => "تم استيراد {$success_count} من {$total_rows} بنجاح. تم أرشفة الملف.",
                'import_id' => $import_id,
                'statistics' => [
                    'total' => $total_rows,
                    'success' => $success_count,
                    'failed' => $failed_count
                ],
                'errors' => $errors,
                'results' => $results
            ]);
            
        } catch (Exception $e) {
            if (file_exists($temp_file)) {
                unlink($temp_file);
            }
            respond(['success' => false, 'message' => 'خطأ في قراءة الملف: ' . $e->getMessage()], 500);
        }
    }
    
    // ==============================================
    // GET: جلب سجل الاستيراد
    // ==============================================
    if ($method === 'GET' && $action === 'history') {
        $limit = (int)($_GET['limit'] ?? 20);
        
        $stmt = $conn->prepare("
            SELECT 
                ih.id as import_id,
                ih.imported_by,
                ih.original_name as file_name,
                ih.import_type,
                ih.records_count as total_rows,
                ih.status,
                ih.imported_at as created_at,
                ih.file_path,
                u.full_name AS imported_by_name
            FROM import_history ih
            LEFT JOIN users u ON ih.imported_by = u.id
            ORDER BY ih.imported_at DESC
            LIMIT ?
        ");
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $history = [];
        while ($row = $result->fetch_assoc()) {
            $history[] = [
                'import_id' => (int)$row['import_id'],
                'file_name' => $row['file_name'],
                'import_type' => $row['import_type'],
                'total_rows' => (int)$row['total_rows'],
                'status' => $row['status'],
                'created_at' => $row['created_at'],
                'imported_by_name' => $row['imported_by_name'] ?? 'Unknown',
                'has_file' => !empty($row['file_path']) && file_exists(__DIR__ . '/../../' . $row['file_path'])
            ];
        }
        
        $stmt->close();
        respond(['success' => true, 'history' => $history]);
    }

    // ==============================================
    // GET: تحميل الملف المؤرشف
    // ==============================================
    if ($method === 'GET' && $action === 'download') {
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) respond(['success' => false, 'message' => 'معرف غير صحيح'], 400);

        $stmt = $conn->prepare("SELECT file_path, original_name FROM import_history WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $file_path = __DIR__ . '/../../' . $row['file_path'];
            if (file_exists($file_path)) {
                header('Content-Description: File Transfer');
                header('Content-Type: application/octet-stream');
                header('Content-Disposition: attachment; filename="' . $row['original_name'] . '"');
                header('Expires: 0');
                header('Cache-Control: must-revalidate');
                header('Pragma: public');
                header('Content-Length: ' . filesize($file_path));
                readfile($file_path);
                exit;
            } else {
                respond(['success' => false, 'message' => 'الملف غير موجود في الخادم'], 404);
            }
        } else {
            respond(['success' => false, 'message' => 'السجل غير موجود'], 404);
        }
    }
    
    respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}

// ==============================================
// دوال الاستيراد المخصصة
// ==============================================

function importStudents($conn, $headers, $rows, &$errors, &$success, &$failed) {
    $results = [];
    
    foreach ($rows as $index => $row) {
        $row_num = $index + 2; // +2 لأننا بدأنا من 0 وحذفنا العناوين
        
        try {
            // استخراج البيانات
            $full_name = trim($row['A'] ?? '');
            $email = trim($row['B'] ?? '');
            $phone = trim($row['C'] ?? '');
            $dob = $row['D'] ?? null;
            $gender = strtolower(trim($row['E'] ?? 'male'));
            $course_name = trim($row['F'] ?? '');
            $region = trim($row['G'] ?? '');
            
            if ($full_name === '' || $email === '') {
                $errors[] = "صف {$row_num}: الاسم أو البريد فارغ";
                $failed++;
                continue;
            }
            
            // التحقق من عدم تكرار البريد
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $check->close();
                $errors[] = "صف {$row_num}: البريد {$email} موجود مسبقاً";
                $failed++;
                continue;
            }
            $check->close();
            
            // إنشاء كلمة مرور
            $password = 'Ibdaa@' . rand(1000, 9999);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            // إدراج الطالب
            $stmt = $conn->prepare("
                INSERT INTO users 
                (full_name, email, phone, password_hash, role, dob, gender, course_region, account_status) 
                VALUES (?, ?, ?, ?, 'student', ?, ?, ?, 'pending')
            ");
            $stmt->bind_param('sssssss', $full_name, $email, $phone, $password_hash, $dob, $gender, $region);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                $results[] = [
                    'row' => $row_num,
                    'user_id' => $user_id,
                    'email' => $email,
                    'password' => $password
                ];
                $success++;
            } else {
                $errors[] = "صف {$row_num}: فشل الإدراج - " . $stmt->error;
                $failed++;
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "صف {$row_num}: خطأ - " . $e->getMessage();
            $failed++;
        }
    }
    
    return $results;
}

function importTrainers($conn, $headers, $rows, &$errors, &$success, &$failed) {
    $results = [];
    
    foreach ($rows as $index => $row) {
        $row_num = $index + 2;
        
        try {
            $full_name = trim($row['A'] ?? '');
            $email = trim($row['B'] ?? '');
            $phone = trim($row['C'] ?? '');
            $specialization = trim($row['D'] ?? '');
            
            if ($full_name === '' || $email === '') {
                $errors[] = "صف {$row_num}: الاسم أو البريد فارغ";
                $failed++;
                continue;
            }
            
            $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            
            if ($check->num_rows > 0) {
                $check->close();
                $errors[] = "صف {$row_num}: البريد موجود مسبقاً";
                $failed++;
                continue;
            }
            $check->close();
            
            $password = 'Trainer@' . rand(1000, 9999);
            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("
                INSERT INTO users 
                (full_name, email, phone, password_hash, role, account_status) 
                VALUES (?, ?, ?, ?, 'trainer', 'active')
            ");
            $stmt->bind_param('ssss', $full_name, $email, $phone, $password_hash);
            
            if ($stmt->execute()) {
                $results[] = [
                    'row' => $row_num,
                    'user_id' => $stmt->insert_id,
                    'email' => $email,
                    'password' => $password
                ];
                $success++;
            } else {
                $errors[] = "صف {$row_num}: فشل الإدراج";
                $failed++;
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "صف {$row_num}: خطأ - " . $e->getMessage();
            $failed++;
        }
    }
    
    return $results;
}

function importCourses($conn, $headers, $rows, &$errors, &$success, &$failed) {
    $results = [];
    
    foreach ($rows as $index => $row) {
        $row_num = $index + 2;
        
        try {
            $title = trim($row['A'] ?? '');
            $description = trim($row['B'] ?? '');
            $fees = (float)($row['C'] ?? 0);
            $region = trim($row['D'] ?? '');
            $city = trim($row['E'] ?? '');
            $max_students = (int)($row['F'] ?? 30);
            
            if ($title === '') {
                $errors[] = "صف {$row_num}: اسم الدورة فارغ";
                $failed++;
                continue;
            }
            
            $stmt = $conn->prepare("
                INSERT INTO courses 
                (title, description, fees, region, city, max_students, status) 
                VALUES (?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->bind_param('ssdssi', $title, $description, $fees, $region, $city, $max_students);
            
            if ($stmt->execute()) {
                $results[] = [
                    'row' => $row_num,
                    'course_id' => $stmt->insert_id,
                    'title' => $title
                ];
                $success++;
            } else {
                $errors[] = "صف {$row_num}: فشل الإدراج";
                $failed++;
            }
            
            $stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "صف {$row_num}: خطأ - " . $e->getMessage();
            $failed++;
        }
    }
    
    return $results;
}

function importPayments($conn, $headers, $rows, &$errors, &$success, &$failed) {
    $results = [];
    
    foreach ($rows as $index => $row) {
        $row_num = $index + 2;
        
        try {
            $email = trim($row['A'] ?? '');
            $amount = (float)($row['B'] ?? 0);
            $payment_method = trim($row['C'] ?? 'cash');
            $payment_date = $row['D'] ?? date('Y-m-d');
            
            if ($email === '' || $amount <= 0) {
                $errors[] = "صف {$row_num}: بيانات غير مكتملة";
                $failed++;
                continue;
            }
            
            // البحث عن المستخدم
            $user_stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND role = 'student'");
            $user_stmt->bind_param('s', $email);
            $user_stmt->execute();
            $user_result = $user_stmt->get_result();
            
            if ($user_result->num_rows === 0) {
                $user_stmt->close();
                $errors[] = "صف {$row_num}: الطالب غير موجود";
                $failed++;
                continue;
            }
            
            $user_row = $user_result->fetch_assoc();
            $user_id = $user_row['id'];
            $user_stmt->close();
            
            // إضافة الدفعة
            $pay_stmt = $conn->prepare("
                INSERT INTO payments 
                (user_id, amount, payment_method, payment_date, status, processed_by) 
                VALUES (?, ?, ?, ?, 'completed', ?)
            ");
            $processed_by = $_SESSION['user_id'];
            $pay_stmt->bind_param('idssi', $user_id, $amount, $payment_method, $payment_date, $processed_by);
            
            if ($pay_stmt->execute()) {
                $results[] = [
                    'row' => $row_num,
                    'payment_id' => $pay_stmt->insert_id,
                    'email' => $email,
                    'amount' => $amount
                ];
                $success++;
            } else {
                $errors[] = "صف {$row_num}: فشل الإدراج";
                $failed++;
            }
            
            $pay_stmt->close();
            
        } catch (Exception $e) {
            $errors[] = "صف {$row_num}: خطأ - " . $e->getMessage();
            $failed++;
        }
    }
    
    return $results;
}
