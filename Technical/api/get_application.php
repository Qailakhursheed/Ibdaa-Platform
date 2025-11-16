<?php
/**
 * API - جلب تفاصيل طلب
 */

require_once __DIR__ . '/../../includes/session_security.php';
require_once __DIR__ . '/../../platform/db.php';

SessionSecurity::requireLogin();
SessionSecurity::requireRole(['technical', 'manager']);

header('Content-Type: application/json');

$application_id = (int)($_GET['id'] ?? 0);

if ($application_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'رقم الطلب غير صحيح']);
    exit;
}

$stmt = $conn->prepare("
    SELECT 
        a.*,
        c.name as course_name,
        c.price as course_price
    FROM applications a
    JOIN courses c ON c.id = a.course_id
    WHERE a.id = ?
");
$stmt->bind_param('i', $application_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $application = $result->fetch_assoc();
    echo json_encode([
        'success' => true,
        'application' => $application
    ]);
} else {
    echo json_encode([
        'success' => false,
        'message' => 'الطلب غير موجود'
    ]);
}

$stmt->close();
