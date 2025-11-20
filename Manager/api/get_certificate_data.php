<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../../database/db.php';
require_once __DIR__ . '/../../includes/session_security.php';

SessionSecurity::startSecureSession();

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$student_id = $_GET['student_id'] ?? 0;
$course_id = $_GET['course_id'] ?? 0;

if (empty($student_id) || empty($course_id)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Student ID and Course ID are required.']);
    exit;
}

// In a real app, you would have proper tables for courses, students, and enrollments.
// Here we will use placeholder data.
$student_name = 'عبدالله الحاشدي';
$course_name = 'تطوير تطبيقات الويب';
$completion_date = date('Y-m-d');
$certificate_id = 'IBDAA-' . $course_id . '-' . $student_id . '-' . date('Y');

echo json_encode([
    'success' => true,
    'data' => [
        'student_name' => $student_name,
        'course_name' => $course_name,
        'completion_date' => $completion_date,
        'certificate_id' => $certificate_id,
    ]
]);

$conn->close();
