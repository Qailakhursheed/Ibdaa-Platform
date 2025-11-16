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

header('Content-Type: application/json; charset=utf-8');

// التحقق من جلسة المدرب فقط
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
if (!$user_id || $user_role !== 'trainer') {
    http_response_code(403);
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../database/db.php';

try {
    $trainer_id = intval($user_id);

    // جلب دورات المدرب
    $stmtCourses = $conn->prepare("SELECT c.course_id, c.title, c.category, c.duration, c.status, c.start_date, c.end_date, c.fees,
                                          (SELECT COUNT(*) FROM enrollments e WHERE e.course_id = c.course_id) AS enrolled_count
                                   FROM courses c WHERE c.trainer_id = ? ORDER BY c.course_id DESC");
    $stmtCourses->bind_param('i', $trainer_id);
    $stmtCourses->execute();
    $resCourses = $stmtCourses->get_result();
    $courses = [];
    while ($row = $resCourses->fetch_assoc()) { $courses[] = $row; }

    // جلب الطلاب المسجلين في دورات المدرب
    $stmtStudents = $conn->prepare("SELECT DISTINCT u.id, u.full_name, u.email, u.phone, u.governorate, u.district, u.created_at,
                         e.course_id, c.title AS course_title, e.status AS enrollment_status
                     FROM users u
                     JOIN enrollments e ON u.id = e.user_id
                                    JOIN courses c ON e.course_id = c.course_id
                                    WHERE c.trainer_id = ?
                                    ORDER BY u.id DESC");
    $stmtStudents->bind_param('i', $trainer_id);
    $stmtStudents->execute();
    $resStudents = $stmtStudents->get_result();
    $students = [];
    while ($row = $resStudents->fetch_assoc()) { $students[] = $row; }

    echo json_encode(['success'=>true,'courses'=>$courses,'students'=>$students,'counts'=>['courses'=>count($courses),'students'=>count($students)]], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'خطأ: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
