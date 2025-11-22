<?php
/**
 * Attendance Management API
 * إدارة الحضور - محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من الصلاحيات (مدراء ومدربين)
$user = APIAuth::requireAuth(['manager', 'technical', 'trainer']);
$userId = $user['user_id'];
$userRole = $user['role'];

APIAuth::rateLimit(120, 60);

header('Content-Type: application/json; charset=utf-8');

if (!$userId || !in_array($userRole, ['manager', 'technical', 'trainer'], true)) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

function canAccessCourse(mysqli $conn, string $role, int $userId, int $courseId): bool
{
    if (in_array($role, ['manager', 'technical'], true)) {
        return true;
    }
    $stmt = $conn->prepare('SELECT 1 FROM courses WHERE course_id = ? AND trainer_id = ? LIMIT 1');
    $stmt->bind_param('ii', $courseId, $userId);
    $stmt->execute();
    $allowed = (bool) $stmt->get_result()->fetch_row();
    $stmt->close();
    return $allowed;
}

if ($method === 'GET') {
    $mode = $_GET['mode'] ?? 'daily';

    if ($mode === 'report') {
        $startDate = $_GET['start_date'] ?? date('Y-m-01');
        $endDate = $_GET['end_date'] ?? date('Y-m-d');
        $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;

        $query = 'SELECT c.course_id, c.title,
                         SUM(CASE WHEN ar.status = "present" THEN 1 ELSE 0 END) AS present_count,
                         SUM(CASE WHEN ar.status = "absent" THEN 1 ELSE 0 END) AS absent_count,
                         SUM(CASE WHEN ar.status = "late" THEN 1 ELSE 0 END) AS late_count,
                         COUNT(*) AS total_records,
                         COUNT(DISTINCT CONCAT(ar.user_id, "::", ar.attendance_date)) AS unique_sessions
                  FROM attendance_records ar
                  JOIN courses c ON c.course_id = ar.course_id
                  WHERE ar.attendance_date BETWEEN ? AND ?';
        $types = 'ss';
        $params = [$startDate, $endDate];

        if ($courseId > 0) {
            $query .= ' AND ar.course_id = ?';
            $types .= 'i';
            $params[] = $courseId;
        }

        if ($userRole === 'trainer') {
            $query .= ' AND c.trainer_id = ?';
            $types .= 'i';
            $params[] = $userId;
        }

        $query .= ' GROUP BY c.course_id, c.title ORDER BY c.title';

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        $reports = [];
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
        $stmt->close();

        echo json_encode([
            'success' => true,
            'mode' => 'report',
            'range' => ['start' => $startDate, 'end' => $endDate],
            'data' => $reports
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $courseId = isset($_GET['course_id']) ? (int) $_GET['course_id'] : 0;
    if ($courseId <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!canAccessCourse($conn, $userRole, (int) $userId, $courseId)) {
        echo json_encode(['success' => false, 'message' => 'ليست لديك صلاحية على هذه الدورة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $date = $_GET['date'] ?? date('Y-m-d');

    $stmt = $conn->prepare(
        'SELECT u.id AS user_id, u.full_name, u.email,
                e.status AS enrollment_status,
                COALESCE(ar.status, "unset") AS attendance_status,
                ar.notes
         FROM enrollments e
         JOIN users u ON u.id = e.user_id
         LEFT JOIN attendance_records ar
            ON ar.course_id = e.course_id
           AND ar.user_id = e.user_id
           AND ar.attendance_date = ?
         WHERE e.course_id = ?
           AND e.status IN ("active", "completed")
         ORDER BY u.full_name'
    );
    $stmt->bind_param('si', $date, $courseId);
    $stmt->execute();
    $res = $stmt->get_result();
    $records = [];
    while ($row = $res->fetch_assoc()) {
        $records[] = $row;
    }
    $stmt->close();

    echo json_encode([
        'success' => true,
        'mode' => 'daily',
        'date' => $date,
        'course_id' => $courseId,
        'records' => $records
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($method === 'POST') {
    $rawInput = file_get_contents('php://input');
    $payload = json_decode($rawInput, true);
    if (!is_array($payload)) {
        $payload = $_POST;
    }

    $courseId = isset($payload['course_id']) ? (int) $payload['course_id'] : 0;
    $date = $payload['date'] ?? date('Y-m-d');
    $records = $payload['records'] ?? [];

    if ($courseId <= 0 || empty($records) || !is_array($records)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة للحفظ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    if (!canAccessCourse($conn, $userRole, (int) $userId, $courseId)) {
        echo json_encode(['success' => false, 'message' => 'ليست لديك صلاحية على هذه الدورة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $allowedStatuses = ['present', 'absent', 'late'];
    $inserted = 0;

    $stmt = $conn->prepare(
        'INSERT INTO attendance_records (course_id, user_id, attendance_date, status, notes, recorded_by)
         VALUES (?, ?, ?, ?, ?, ?)
         ON DUPLICATE KEY UPDATE status = VALUES(status), notes = VALUES(notes), recorded_by = VALUES(recorded_by), recorded_at = NOW()'
    );

    foreach ($records as $record) {
        $studentId = isset($record['user_id']) ? (int) $record['user_id'] : 0;
        $status = $record['status'] ?? '';
        $notes = $record['notes'] ?? null;

        if ($studentId <= 0 || !in_array($status, $allowedStatuses, true)) {
            continue;
        }

        $stmt->bind_param('iisssi', $courseId, $studentId, $date, $status, $notes, $userId);
        if ($stmt->execute()) {
            $inserted++;
        }
    }

    $stmt->close();

    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث سجل الحضور',
        'affected' => $inserted
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
?>
