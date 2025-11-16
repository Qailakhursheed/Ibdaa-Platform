<?php
/**
 * API - رفض طلب
 */

require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../platform/db.php';

SessionSecurity::requireLogin();
SessionSecurity::requireRole(['technical', 'manager']);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'طريقة إرسال غير صحيحة']);
    exit;
}

if (!CSRF::validateToken($_POST['csrf_token'] ?? '')) {
    echo json_encode(['success' => false, 'message' => 'CSRF token invalid']);
    exit;
}

$application_id = (int)($_POST['application_id'] ?? 0);
$rejection_reason = trim($_POST['rejection_reason'] ?? '');
$reviewer_id = $_SESSION['user_id'];

if ($application_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'رقم الطلب غير صحيح']);
    exit;
}

if (empty($rejection_reason)) {
    echo json_encode(['success' => false, 'message' => 'سبب الرفض مطلوب']);
    exit;
}

// التحقق من أن الطلب معلق
$check_stmt = $conn->prepare("SELECT status FROM applications WHERE id = ?");
$check_stmt->bind_param('i', $application_id);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows === 0) {
    echo json_encode(['success' => false, 'message' => 'الطلب غير موجود']);
    exit;
}

$app = $result->fetch_assoc();
if ($app['status'] !== 'pending') {
    echo json_encode(['success' => false, 'message' => 'الطلب تمت مراجعته مسبقاً']);
    exit;
}

$check_stmt->close();

// تحديث حالة الطلب إلى "مرفوض"
$stmt = $conn->prepare("
    UPDATE applications 
    SET 
        status = 'rejected',
        reviewed_by = ?,
        reviewed_at = NOW(),
        review_notes = ?
    WHERE id = ?
");

$stmt->bind_param('isi', $reviewer_id, $rejection_reason, $application_id);

if ($stmt->execute()) {
    echo json_encode([
        'success' => true,
        'message' => 'تم رفض الطلب. سيتم إرسال إشعار للطالب.'
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'حدث خطأ أثناء معالجة الطلب'
    ]);
}

$stmt->close();
