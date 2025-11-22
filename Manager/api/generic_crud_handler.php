<?php
header('Content-Type: application/json');

require_once '../../includes/config.php';
require_once '../../includes/session_security.php';
require_once '../../includes/csrf.php';
require_once '../../includes/rate_limiter.php';

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

// Apply Rate Limiter (e.g., 60 requests per minute)
apply_rate_limiter($conn, 60, 60);

// Protect state-changing methods
protect_from_csrf();

$action = $_REQUEST['action'] ?? null;
$table = $_REQUEST['table'] ?? null;

// --- Table Name Sanitization (CRITICAL) ---
if (empty($table) || !preg_match('/^[a-zA-Z0-9_]{3,64}$/', $table)) {
    json_response(false, 'اسم الجدول المحدد غير صالح.');
}

// A simple whitelist of tables that can be managed.
// For now, we can assume any table created by the wizard is safe,
// but a more robust system would register these tables.
// For this implementation, we rely on the strict regex.


try {
    switch ($action) {
        case 'describe':
            $stmt = $conn->prepare("DESCRIBE `{$table}`");
            $stmt->execute();
            $result = $stmt->get_result();
            $schema = [];
            $primary_key = '';
            while ($row = $result->fetch_assoc()) {
                $schema['columns'][] = [
                    'name' => $row['Field'],
                    'type' => $row['Type'],
                    'nullable' => $row['Null'] === 'YES',
                    'is_primary' => $row['Key'] === 'PRI',
                ];
                if ($row['Key'] === 'PRI') {
                    $schema['primary_key'] = $row['Field'];
                }
            }
            $stmt->close();
            if (empty($schema)) {
                json_response(false, 'لا يمكن وصف الجدول أو أنه غير موجود.');
            }
            json_response(true, ['schema' => $schema]);
            break;

        case 'read':
            $stmt = $conn->prepare("SELECT * FROM `{$table}`");
            $stmt->execute();
            $result = $stmt->get_result();
            $data = $result->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
            json_response(true, ['data' => $data]);
            break;

        case 'create':
            $data = $_POST;
            unset($data['action'], $data['table']);
            
            if (empty($data)) {
                json_response(false, 'لا توجد بيانات لإنشاء السجل.');
            }

            $columns = array_keys($data);
            $placeholders = rtrim(str_repeat('?,', count($columns)), ',');
            
            $cols_sanitized = array_map(fn($col) => "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $col) . "`", $columns);

            $sql = "INSERT INTO `{$table}` (" . implode(', ', $cols_sanitized) . ") VALUES ({$placeholders})";
            
            $stmt = $conn->prepare($sql);
            $types = str_repeat('s', count($data));
            $values = array_values($data);
            $stmt->bind_param($types, ...$values);
            
            if ($stmt->execute()) {
                json_response(true, ['message' => 'تم إنشاء السجل بنجاح.', 'id' => $conn->insert_id]);
            } else {
                json_response(false, 'فشل في إنشاء السجل: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'update':
            $data = $_POST;
            $pk_col = $data['pk_col'] ?? null;
            $pk_val = $data['pk_val'] ?? null;

            unset($data['action'], $data['table'], $data['pk_col'], $data['pk_val']);

            if (empty($data) || !$pk_col || !$pk_val) {
                json_response(false, 'بيانات التحديث غير مكتملة.');
            }

            $pk_col = preg_replace('/[^a-zA-Z0-9_]/', '', $pk_col);

            $set_parts = [];
            $values = [];
            $types = '';
            foreach ($data as $key => $value) {
                $key_sanitized = "`" . preg_replace('/[^a-zA-Z0-9_]/', '', $key) . "`";
                $set_parts[] = "{$key_sanitized} = ?";
                $values[] = $value;
                $types .= 's';
            }

            $values[] = $pk_val;
            $types .= 's';

            $sql = "UPDATE `{$table}` SET " . implode(', ', $set_parts) . " WHERE `{$pk_col}` = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);

            if ($stmt->execute()) {
                json_response(true, ['message' => 'تم تحديث السجل بنجاح.']);
            } else {
                json_response(false, 'فشل في تحديث السجل: ' . $stmt->error);
            }
            $stmt->close();
            break;

        case 'delete':
            $pk_col = $_POST['pk_col'] ?? null;
            $pk_val = $_POST['pk_val'] ?? null;

            if (!$pk_col || !$pk_val) {
                json_response(false, 'معلومات المفتاح الأساسي مطلوبة للحذف.');
            }
            
            $pk_col = preg_replace('/[^a-zA-Z0-9_]/', '', $pk_col);

            $sql = "DELETE FROM `{$table}` WHERE `{$pk_col}` = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('s', $pk_val);

            if ($stmt->execute()) {
                json_response(true, ['message' => 'تم حذف السجل بنجاح.']);
            } else {
                json_response(false, 'فشل في حذف السجل: ' . $stmt->error);
            }
            $stmt->close();
            break;

        default:
            json_response(false, 'الإجراء المطلوب غير معروف.');
            break;
    }
} catch (Exception $e) {
    error_log("Generic CRUD Error: " . $e->getMessage());
    json_response(false, 'حدث خطأ في الخادم: ' . $e->getMessage());
}
?>
