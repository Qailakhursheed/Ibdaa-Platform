<?php
header('Content-Type: application/json');

// Basic security and session management
require_once '../../includes/config.php';
require_once '../../includes/session_security.php';
require_once '../../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\IOFactory;

// --- Response Helper ---
function json_response($success, $data = []) {
    $response = ['success' => (bool)$success];
    if (is_string($data)) {
        $response['message'] = $data;
    } else {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// --- Security Check ---
if ($userRole !== 'manager') {
    json_response(false, 'غير مصرح لك بالقيام بهذه العملية.');
}

// --- File Upload Validation ---
if (!isset($_FILES['data_file']) || $_FILES['data_file']['error'] !== UPLOAD_ERR_OK) {
    $error_messages = [
        UPLOAD_ERR_INI_SIZE   => 'حجم الملف يتجاوز الحد المسموح به في الخادم.',
        UPLOAD_ERR_FORM_SIZE  => 'حجم الملف يتجاوز الحد المسموح به في النموذج.',
        UPLOAD_ERR_PARTIAL    => 'تم رفع جزء من الملف فقط.',
        UPLOAD_ERR_NO_FILE    => 'لم يتم رفع أي ملف.',
        UPLOAD_ERR_NO_TMP_DIR => 'مجلد الملفات المؤقتة مفقود.',
        UPLOAD_ERR_CANT_WRITE => 'فشل في كتابة الملف على القرص.',
        UPLOAD_ERR_EXTENSION  => 'تم إيقاف رفع الملف بسبب امتداده.',
    ];
    $error_code = $_FILES['data_file']['error'] ?? UPLOAD_ERR_NO_FILE;
    json_response(false, $error_messages[$error_code] ?? 'حدث خطأ غير معروف أثناء رفع الملف.');
}

$file = $_FILES['data_file'];
$allowed_mime_types = [
    'application/vnd.ms-excel', // xls
    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // xlsx
    'text/csv', // csv
];
$file_mime_type = mime_content_type($file['tmp_name']);

if (!in_array($file_mime_type, $allowed_mime_types)) {
    json_response(false, 'نوع الملف غير مسموح به. يرجى رفع ملف Excel أو CSV.');
}

// --- Save file to a temporary location ---
$temp_dir = __DIR__ . '/../../uploads/temp/';
if (!is_dir($temp_dir)) {
    mkdir($temp_dir, 0755, true);
}

// Sanitize filename and create a unique name
$original_name = pathinfo($file['name'], PATHINFO_FILENAME);
$extension = pathinfo($file['name'], PATHINFO_EXTENSION);
$safe_original_name = preg_replace("/[^a-zA-Z0-9_-]/", "", $original_name);
$unique_id = uniqid('', true);
$temp_filename = "import_{$safe_original_name}_{$unique_id}.{$extension}";
$temp_filepath = $temp_dir . $temp_filename;

if (!move_uploaded_file($file['tmp_name'], $temp_filepath)) {
    json_response(false, 'فشل في حفظ الملف المؤقت.');
}


// --- AI Analysis Logic ---

/**
 * Sanitizes a string to be a valid SQL column name (snake_case).
 * @param string $header
 * @return string
 */
function sanitize_header($header) {
    $header = preg_replace('/[^\p{L}\p{N}_ ]/u', '', $header); // Remove special chars except letters, numbers, underscore, space
    $header = trim($header);
    $header = str_replace(' ', '_', $header);
    $header = strtolower($header);
    return $header ?: 'column_' . uniqid();
}

/**
 * Infers the SQL data type from a sample of column data.
 * @param array $samples
 * @return string
 */
function infer_sql_type(array $samples) {
    $samples = array_filter($samples, fn($val) => $val !== null && $val !== '');
    if (empty($samples)) return 'VARCHAR(255)';

    $is_int = true;
    $is_float = true;
    $is_date = true;
    $max_length = 0;

    foreach ($samples as $sample) {
        $max_length = max($max_length, mb_strlen((string)$sample));

        if (!is_numeric($sample)) {
            $is_int = false;
            $is_float = false;
        } else {
            if (strpos((string)$sample, '.') !== false) {
                $is_int = false;
            }
        }
        
        if (!strtotime($sample) && !DateTime::createFromFormat('d/m/Y', $sample) && !DateTime::createFromFormat('m-d-Y', $sample)) {
            $is_date = false;
        }
    }

    if ($is_int) return 'INT';
    if ($is_float) return 'FLOAT';
    if ($is_date) return 'DATE';
    if ($max_length > 255) return 'TEXT';
    
    return 'VARCHAR(255)';
}

try {
    $spreadsheet = IOFactory::load($file['tmp_name']);
    $worksheet = $spreadsheet->getActiveSheet();
    
    // Limit rows to read for analysis to prevent performance issues
    $highestRow = min($worksheet->getHighestRow(), 100); 
    $highestColumn = $worksheet->getHighestColumn();

    $data = $worksheet->rangeToArray("A1:{$highestColumn}{$highestRow}", null, true, true, true);

    if (empty($data)) {
        json_response(false, 'الملف فارغ أو لا يمكن قراءته.');
    }

    $headers = array_shift($data);
    $samples = $data; // The rest of the data are samples

    $columns = [];
    $has_primary_key = false;

    foreach ($headers as $col_key => $header_val) {
        if (empty(trim($header_val))) continue;

        $sanitized_name = sanitize_header($header_val);
        $column_samples = array_column($samples, $col_key);
        $inferred_type = infer_sql_type($column_samples);
        
        $is_primary = false;
        if (!$has_primary_key && in_array($sanitized_name, ['id', 'pk', 'primary_key', 'رقم_المعرف'])) {
            $is_primary = true;
            $has_primary_key = true;
        }

        $columns[] = [
            'name' => $sanitized_name,
            'type' => $inferred_type,
            'is_primary' => $is_primary,
            'is_nullable' => true, // Default to nullable
        ];
    }

    // If no primary key was found by name, suggest the first column if it's an INT
    if (!$has_primary_key && !empty($columns) && $columns[0]['type'] === 'INT') {
        $columns[0]['is_primary'] = true;
    }

    $table_name = sanitize_header(pathinfo($file['name'], PATHINFO_FILENAME));

    $schema = [
        'table_name' => $table_name,
        'columns' => $columns,
    ];

    json_response(true, ['schema' => $schema, 'temp_file' => $temp_filename]);

} catch (Exception $e) {
    error_log("AI Analyze Error: " . $e->getMessage());
    json_response(false, 'حدث خطأ أثناء معالجة الملف. تأكد من أن الملف غير تالف.');
}
?>
