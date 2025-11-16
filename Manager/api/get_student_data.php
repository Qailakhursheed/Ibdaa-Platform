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

if (!$userId || $userRole !== 'student') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك - هذا القسم للطلاب فقط'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    $studentStmt = $conn->prepare('SELECT id, full_name, full_name_en, email, phone, governorate, district, dob, created_at, profile_picture FROM users WHERE id = ? AND role = "student" LIMIT 1');
    $studentStmt->bind_param('i', $userId);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
    $studentStmt->close();

    if (!$student) {
        echo json_encode(['success' => false, 'message' => 'بيانات الطالب غير موجودة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $coursesStmt = $conn->prepare(
        'SELECT e.enrollment_id, e.course_id, e.status, e.progress, e.last_activity_at, e.created_at,
                c.title AS course_title, c.description, c.duration, c.start_date, c.end_date,
                u.full_name AS trainer_name
         FROM enrollments e
         JOIN courses c ON c.course_id = e.course_id
         LEFT JOIN users u ON u.id = c.trainer_id
         WHERE e.user_id = ?
         ORDER BY e.created_at DESC'
    );
    $coursesStmt->bind_param('i', $userId);
    $coursesStmt->execute();
    $coursesResult = $coursesStmt->get_result();
    $courses = [];
    while ($row = $coursesResult->fetch_assoc()) {
        $row['enrolled_at'] = $row['created_at'];
        $courses[] = $row;
    }
    $coursesStmt->close();

    $grades = [];
    if ($conn->query("SHOW TABLES LIKE 'grades'")->num_rows > 0) {
        $gradesStmt = $conn->prepare('SELECT grade_id, course_id, assignment_name, grade_value, graded_at, created_at FROM grades WHERE user_id = ? ORDER BY COALESCE(graded_at, created_at) DESC');
        $gradesStmt->bind_param('i', $userId);
        $gradesStmt->execute();
        $gradesResult = $gradesStmt->get_result();
        while ($row = $gradesResult->fetch_assoc()) {
            $grades[] = $row;
        }
        $gradesStmt->close();
    }

    $stats = [
        'total_courses' => count($courses),
        'active_courses' => count(array_filter($courses, static fn($c) => $c['status'] === 'active')),
        'completed_courses' => count(array_filter($courses, static fn($c) => $c['status'] === 'completed')),
        'total_grades' => count($grades),
        'average_grade' => 0,
        'attendance_present' => 0,
        'attendance_absent' => 0,
        'attendance_late' => 0,
        'attendance_sessions' => 0
    ];

    if ($stats['total_grades'] > 0) {
        $sum = 0;
        foreach ($grades as $grade) {
            $sum += (float) $grade['grade_value'];
        }
        $stats['average_grade'] = round($sum / $stats['total_grades'], 2);
    }

    $attendanceSummary = [
        'present' => 0,
        'absent' => 0,
        'late' => 0,
        'total_sessions' => 0,
        'by_course' => []
    ];

    if ($conn->query("SHOW TABLES LIKE 'attendance_records'")->num_rows > 0) {
        $attendanceStmt = $conn->prepare('SELECT course_id, status, COUNT(*) AS cnt FROM attendance_records WHERE user_id = ? GROUP BY course_id, status');
        $attendanceStmt->bind_param('i', $userId);
        $attendanceStmt->execute();
        $attendanceResult = $attendanceStmt->get_result();
        while ($row = $attendanceResult->fetch_assoc()) {
            $courseId = (int) $row['course_id'];
            $status = $row['status'];
            $count = (int) $row['cnt'];
            if (!isset($attendanceSummary['by_course'][$courseId])) {
                $attendanceSummary['by_course'][$courseId] = ['present' => 0, 'absent' => 0, 'late' => 0, 'total' => 0];
            }
            if (isset($attendanceSummary[$status])) {
                $attendanceSummary[$status] += $count;
            }
            if (isset($attendanceSummary['by_course'][$courseId][$status])) {
                $attendanceSummary['by_course'][$courseId][$status] += $count;
            }
            $attendanceSummary['by_course'][$courseId]['total'] += $count;
            $attendanceSummary['total_sessions'] += $count;
        }
        $attendanceStmt->close();
    }

    $stats['attendance_present'] = $attendanceSummary['present'];
    $stats['attendance_absent'] = $attendanceSummary['absent'];
    $stats['attendance_late'] = $attendanceSummary['late'];
    $stats['attendance_sessions'] = $attendanceSummary['total_sessions'];

    $certificates = [];
    if ($conn->query("SHOW TABLES LIKE 'certificates'")->num_rows > 0) {
        $certStmt = $conn->prepare('SELECT course_id, certificate_code, verification_code, file_path, issued_at FROM certificates WHERE user_id = ?');
        $certStmt->bind_param('i', $userId);
        $certStmt->execute();
        $certResult = $certStmt->get_result();
        while ($row = $certResult->fetch_assoc()) {
            $certificates[(int) $row['course_id']] = $row;
        }
        $certStmt->close();
    }

    foreach ($courses as &$course) {
        $cid = (int) $course['course_id'];
        $course['certificate'] = $certificates[$cid] ?? null;
        if (isset($attendanceSummary['by_course'][$cid])) {
            $course['attendance_summary'] = $attendanceSummary['by_course'][$cid];
        }
    }
    unset($course);

    $recentActivities = [];
    if (!empty($courses)) {
        $latest = $courses[0];
        $recentActivities[] = [
            'type' => 'enrollment',
            'title' => 'تسجيل في دورة',
            'description' => 'تم تسجيلك في دورة ' . ($latest['course_title'] ?? ''),
            'date' => $latest['enrolled_at'],
            'icon' => 'book-open'
        ];
    }
    if (!empty($grades)) {
        $latestGrade = $grades[0];
        $recentActivities[] = [
            'type' => 'grade',
            'title' => 'درجة جديدة',
            'description' => 'حصلت على ' . $latestGrade['grade_value'] . ' في ' . ($latestGrade['assignment_name'] ?? 'تقييم'),
            'date' => $latestGrade['graded_at'] ?? $latestGrade['created_at'],
            'icon' => 'award'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => [
            'student' => $student,
            'courses' => $courses,
            'grades' => $grades,
            'stats' => $stats,
            'recent_activities' => $recentActivities,
            'attendance_summary' => $attendanceSummary
        ]
    ], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'خطأ في الخادم: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
