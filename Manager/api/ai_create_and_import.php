<?php
header('Content-Type: application/json');

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

// --- Security & Input Validation ---
if ($userRole !== 'manager') {
    json_response(false, 'غير مصرح لك بالقيام بهذه العملية.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(false, 'يجب استخدام طلب POST.');
}

$tableName = $_POST['table_name'] ?? null;
$columns = $_POST['columns'] ?? null;
$tempFile = $_POST['temp_file'] ?? null;

if (empty($tableName) || empty($columns) || !is_array($columns) || empty($tempFile)) {
    json_response(false, 'بيانات غير مكتملة. اسم الجدول، الأعمدة، والملف المؤقت مطلوبة.');
}

// --- Sanitization ---

// 1. Sanitize Table Name
if (!preg_match('/^[a-zA-Z0-9_]{3,64}$/', $tableName)) {
    json_response(false, 'اسم الجدول غير صالح. يجب أن يتكون من 3-64 حرف أبجدي أو رقمي أو شرطة سفلية.');
}

// 2. Sanitize Columns
$allowed_types = ['VARCHAR(255)', 'TEXT', 'INT', 'FLOAT', 'DATE', 'DATETIME', 'BOOLEAN'];
$sanitized_columns = [];
$primary_key = null;
foreach ($columns as $col) {
    if (empty($col['name']) || !preg_match('/^[a-zA-Z0-9_]+$/', $col['name'])) {
        json_response(false, "اسم العمود '{$col['name']}' غير صالح.");
    }
    if (empty($col['type']) || !in_array($col['type'], $allowed_types)) {
        json_response(false, "نوع البيانات '{$col['type']}' غير صالح.");
    }
    $is_primary = isset($col['is_primary']) && $col['is_primary'] === 'on';
    if ($is_primary) {
        if ($primary_key !== null) {
            json_response(false, 'يمكن تحديد مفتاح أساسي واحد فقط.');
        }
        $primary_key = $col['name'];
    }
    $sanitized_columns[] = [
        'name' => $col['name'],
        'type' => $col['type'],
        'is_primary' => $is_primary,
        'is_nullable' => isset($col['is_nullable']) && $col['is_nullable'] === 'on',
    ];
}

if (empty($sanitized_columns)) {
    json_response(false, 'لا توجد أعمدة صالحة لإنشاء الجدول.');
}

// 3. Sanitize Temp File Name (Security against path traversal)
$tempFile = basename($tempFile); // Strip directory paths
$temp_filepath = __DIR__ . '/../../uploads/temp/' . $tempFile;

if (!file_exists($temp_filepath)) {
    json_response(false, 'الملف المؤقت غير موجود أو تم حذفه.');
}


// --- Database Operations ---
$conn->begin_transaction();

try {
    // Check if table already exists
    $result = $conn->query("SHOW TABLES LIKE '{$tableName}'");
    if ($result->num_rows > 0) {
        json_response(false, "جدول بالاسم '{$tableName}' موجود بالفعل.");
    }

    // 1. Generate CREATE TABLE SQL
    $sql = "CREATE TABLE `{$tableName}` (";
    foreach ($sanitized_columns as $col) {
        $sql .= "`{$col['name']}` {$col['type']}";
        if ($col['is_primary'] && $col['type'] === 'INT') {
            $sql .= " NOT NULL AUTO_INCREMENT, ";
        } elseif ($col['is_primary']) {
            $sql .= " NOT NULL, ";
        } elseif (!$col['is_nullable']) {
            $sql .= " NOT NULL, ";
        } else {
            $sql .= " NULL, ";
        }
    }
    if ($primary_key) {
        $sql .= "PRIMARY KEY (`{$primary_key}`)";
    } else {
        $sql = rtrim($sql, ', '); // Remove trailing comma
    }
    $sql .= ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

    // Execute CREATE TABLE
    if (!$conn->query($sql)) {
        throw new Exception("فشل في إنشاء الجدول: " . $conn->error);
    }

    // 2. Prepare for data import
    $spreadsheet = IOFactory::load($temp_filepath);
    $worksheet = $spreadsheet->getActiveSheet();
    $dataRows = $worksheet->toArray();
    
    array_shift($dataRows); // Remove header row

    if (empty($dataRows)) {
        $conn->commit();
        unlink($temp_filepath); // Cleanup
        json_response(true, ['message' => 'تم إنشاء الجدول بنجاح. لم يتم العثور على بيانات للاستيراد.', 'imported_rows' => 0]);
    }

    // 3. Generate and execute INSERT statements
    $column_names = array_column($sanitized_columns, 'name');
    $placeholders = rtrim(str_repeat('?,', count($column_names)), ',');
    $insert_sql = "INSERT INTO `{$tableName}` (`" . implode('`,`', $column_names) . "`) VALUES ({$placeholders})";
    
    $stmt = $conn->prepare($insert_sql);
    if (!$stmt) {
        throw new Exception("فشل في تحضير جملة الإدخال: " . $conn->error);
    }

    $types = str_repeat('s', count($column_names)); // Treat all as strings for simplicity in binding
    $imported_rows = 0;

    foreach ($dataRows as $row) {
        // Ensure row has the same number of columns as the target table
        $row_values = array_slice($row, 0, count($column_names));
        
        // Handle potential null values or empty strings
        $params = [];
        foreach($row_values as $value) {
            $params[] = (trim($value) === '') ? null : $value;
        }

        $stmt->bind_param($types, ...$params);
        if ($stmt->execute()) {
            $imported_rows++;
        }
    }
    $stmt->close();

    // Commit transaction
    $conn->commit();

    // Cleanup
    unlink($temp_filepath);

    json_response(true, [
        'message' => "نجاح! تم إنشاء جدول '{$tableName}' واستيراد {$imported_rows} سجل.",
        'imported_rows' => $imported_rows,
        'table_name' => $tableName
    ]);

} catch (Exception $e) {
    $conn->rollback(); // Rollback on error
    if (file_exists($temp_filepath)) {
        unlink($temp_filepath); // Cleanup on error
    }
    error_log("Create & Import Error: " . $e->getMessage());
    json_response(false, $e->getMessage());
}
?>
