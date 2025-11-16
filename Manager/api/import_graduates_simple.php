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

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../database/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
    exit;
}

if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'لم يتم رفع الملف بشكل صحيح'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $filePath = $_FILES['excel_file']['tmp_name'];
    $spreadsheet = IOFactory::load($filePath);
    $sheet = $spreadsheet->getActiveSheet();
    $rows = $sheet->toArray();
    
    if (empty($rows) || count($rows) < 2) {
        echo json_encode(['success' => false, 'message' => 'الملف فارغ أو لا يحتوي على بيانات'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $headers = array_map('trim', $rows[0]);
    $col_name = -1;
    $col_grade = -1;
    $col_course = -1;
    
    foreach ($headers as $idx => $header) {
        $h = mb_strtolower($header);
        if ($col_name === -1 && (strpos($h, 'اسم') !== false || strpos($h, 'name') !== false)) {
            $col_name = $idx;
        }
        if ($col_grade === -1 && (strpos($h, 'درجة') !== false || strpos($h, 'grade') !== false)) {
            $col_grade = $idx;
        }
        if ($col_course === -1 && (strpos($h, 'دورة') !== false || strpos($h, 'course') !== false)) {
            $col_course = $idx;
        }
    }
    
    if ($col_name === -1) {
        echo json_encode(['success' => false, 'message' => 'لم يتم العثور على عمود الاسم'], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $imported = 0;
    $failed = 0;
    $errors = [];
    
    for ($i = 1; $i < count($rows); $i++) {
        $row = $rows[$i];
        if (empty(array_filter($row))) continue;
        
        try {
            $full_name = trim($row[$col_name] ?? '');
            if (empty($full_name)) {
                $errors[] = "الصف " . ($i + 1) . ": اسم مفقود";
                $failed++;
                continue;
            }
            
            $email = 'grad_' . $i . '_' . time() . '@ibdaa.edu.ye';
            $password_hash = password_hash('graduate123', PASSWORD_DEFAULT);
            
            $check = $conn->prepare("SELECT id FROM users WHERE full_name = ? LIMIT 1");
            $check->bind_param('s', $full_name);
            $check->execute();
            $result = $check->get_result();
            
            if ($result->num_rows > 0) {
                $student_id = $result->fetch_assoc()['id'];
            } else {
                $ins = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, verified) VALUES (?, ?, ?, 'student', 1)");
                $ins->bind_param('sss', $full_name, $email, $password_hash);
                if ($ins->execute()) {
                    $student_id = $conn->insert_id;
                } else {
                    $errors[] = "الصف " . ($i + 1) . ": فشل الإضافة";
                    $failed++;
                    continue;
                }
            }
            
            $imported++;
            
        } catch (Exception $e) {
            $errors[] = "الصف " . ($i + 1) . ": " . $e->getMessage();
            $failed++;
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "تم استيراد {$imported} خريج بنجاح",
        'imported' => $imported,
        'failed' => $failed,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
