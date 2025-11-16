<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        echo $encoded === false
            ? '{"success":false,"message":"Fatal Error & JSON encoding failed."}'
            : $encoded;
    }
});

require_once __DIR__ . '/../../platform/db.php';
header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || !in_array($userRole, ['manager', 'technical', 'trainer'], true)) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $query = 'SELECT e.enrollment_id, e.user_id, u.full_name AS student_name, u.email AS student_email, u.phone AS student_phone,
                     c.course_id, c.title AS course_title, e.status, e.created_at AS completed_at,
                     cert.certificate_code, cert.verification_code, cert.issued_at, cert.file_path
              FROM enrollments e
              JOIN users u ON u.id = e.user_id
              JOIN courses c ON c.course_id = e.course_id
              LEFT JOIN certificates cert ON cert.user_id = e.user_id AND cert.course_id = e.course_id
              WHERE e.status = "completed"';

    if ($userRole === 'trainer') {
        $query .= ' AND c.trainer_id = ' . (int) $userId;
    }

    $query .= ' ORDER BY e.created_at DESC';

    $res = $conn->query($query);
    $graduates = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $graduates[] = [
                'enrollment_id' => $row['enrollment_id'],
                'student_id' => $row['user_id'],
                'student_name' => $row['student_name'],
                'student_email' => $row['student_email'],
                'student_phone' => $row['student_phone'],
                'course_id' => $row['course_id'],
                'course_title' => $row['course_title'],
                'completed_at' => $row['completed_at'],
                'certificate_code' => $row['certificate_code'],
                'verification_code' => $row['verification_code'],
                'certificate_issued_at' => $row['issued_at'],
                'certificate_file' => $row['file_path']
            ];
        }
    }

    echo json_encode([
        'success' => true,
        'data' => $graduates,
        'count' => count($graduates)
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
?>
