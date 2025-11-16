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
        echo json_encode(['success' => false, 'message' => 'Error: ' . $error['message']], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

function respond($payload, $status = 200) {
    http_response_code($status);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || !in_array($userRole, ['manager', 'technical'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح'], 403);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
}

$rawInput = file_get_contents('php://input');
$data = json_decode($rawInput, true);

if (!is_array($data) || !isset($data['processed_data'])) {
    respond(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
}

$processedRecords = $data['processed_data'];
$stats = ['total_records' => count($processedRecords), 'created_users' => 0, 'errors' => []];

try {
    $conn->begin_transaction();

    foreach ($processedRecords as $index => $record) {
        $recordData = $record['data'] ?? $record;
        $studentName = $recordData['student_name'] ?? null;
        $studentEmail = $recordData['student_email'] ?? null;

        if (empty($studentName)) {
            $stats['errors'][] = "Row " . ($index + 1) . ": Name required";
            continue;
        }

        if (empty($studentEmail)) {
            $studentEmail = 'temp_' . time() . '@ibdaa.local';
        }
        
        $passwordHash = password_hash('Ibdaa@2025', PASSWORD_DEFAULT);
        $stmt = $conn->prepare('INSERT INTO users (full_name, email, password_hash, role, verified) VALUES (?, ?, ?, "student", 1)');
        $stmt->bind_param('sss', $studentName, $studentEmail, $passwordHash);
        
        if ($stmt->execute()) {
            $stats['created_users']++;
        }
        $stmt->close();
    }

    $conn->commit();
    respond(['success' => true, 'message' => 'Import successful', 'created_users' => $stats['created_users'], 'errors' => $stats['errors']]);

} catch (Throwable $e) {
    if (isset($conn)) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            // Already rolled back or not in transaction
        }
    }
    respond(['success' => false, 'message' => $e->getMessage()], 500);
}
?>
