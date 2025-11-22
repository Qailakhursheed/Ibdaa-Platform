<?php
/**
 * Import Graduates List API
 * استيراد قائمة الخريجين - محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من الصلاحيات (مدير أو مشرف فني)
$user = APIAuth::requireAuth(['manager', 'technical']);
$userId = $user['user_id'];
$userRole = $user['role'];

// Rate Limiting للاستيراد (عملية مكثفة)
APIAuth::rateLimit(20, 60);

header('Content-Type: application/json; charset=utf-8');

function respond($payload, $status = 200) {
    if ($payload['success'] ?? false) {
        APIAuth::sendSuccess($payload);
    } else {
        APIAuth::sendError($payload['message'] ?? 'حدث خطأ', $status);
    }

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
