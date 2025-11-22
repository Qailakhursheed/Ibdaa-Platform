<?php
/**
 * excel_read_headers - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


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
// الاتصال غير مطلوب هنا لكن جاهز إذا احتجنا التحقق لاحقاً
require_once __DIR__ . '/../../database/db.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

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
    // التحقق من وجود ملف مرفوع
    if (!isset($_FILES['excel_file']) || $_FILES['excel_file']['error'] !== UPLOAD_ERR_OK) {
        echo json_encode([
            'success' => false,
            'message' => 'لم يتم رفع ملف أو حدث خطأ أثناء الرفع'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $file = $_FILES['excel_file'];
    $fileTmpPath = $file['tmp_name'];
    $fileName = $file['name'];
    $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

    // التحقق من نوع الملف
    $allowedExtensions = ['xlsx', 'xls', 'csv'];
    if (!in_array($fileExtension, $allowedExtensions)) {
        echo json_encode([
            'success' => false,
            'message' => 'نوع الملف غير مدعوم. الأنواع المسموحة: .xlsx, .xls, .csv'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // نقل الملف إلى مجلد مؤقت
    // استخدام مجلد tmp_imports وفق الوصف
    $uploadDir = __DIR__ . '/../../uploads/tmp_imports/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    // إنشاء اسم ملف فريد
    $uniqueFileName = 'import_' . $user_id . '_' . time() . '_' . uniqid() . '.' . $fileExtension;
    $destinationPath = $uploadDir . $uniqueFileName;

    if (!move_uploaded_file($fileTmpPath, $destinationPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'فشل حفظ الملف المؤقت'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // قراءة العناوين من الصف الأول
    $spreadsheet = IOFactory::load($destinationPath);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // قراءة الصف الأول (العناوين)
    $headers = [];
    $firstRow = $worksheet->rangeToArray('A1:' . $worksheet->getHighestColumn() . '1', null, true, false);
    
    if (!empty($firstRow[0])) {
        foreach ($firstRow[0] as $cellValue) {
            if (!empty($cellValue)) {
                $headers[] = trim($cellValue);
            }
        }
    }

    // التحقق من وجود عناوين
    if (empty($headers)) {
        unlink($destinationPath); // حذف الملف
        echo json_encode([
            'success' => false,
            'message' => 'لم يتم العثور على عناوين في الصف الأول'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // حساب عدد الصفوف
    $highestRow = $worksheet->getHighestRow();
    $dataRowsCount = $highestRow - 1; // استبعاد صف العناوين

    // إرجاع النتيجة
    echo json_encode([
        'success' => true,
        'headers' => $headers,
        // إعادة المسار الكامل المؤقت كما طلب في الوصف
        'filePath' => $destinationPath,
        'fileName' => $fileName,
        'totalRows' => $dataRowsCount,
        'message' => "تم قراءة {$dataRowsCount} صف من الملف"
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    // حذف الملف في حالة الخطأ
    if (isset($destinationPath) && file_exists($destinationPath)) {
        unlink($destinationPath);
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
