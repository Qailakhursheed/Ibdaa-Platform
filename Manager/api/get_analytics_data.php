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

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../platform/db.php';

function tableExists(mysqli $connection, string $table): bool
{
    $tableSafe = $connection->real_escape_string($table);
    $sql = "SHOW TABLES LIKE '" . $tableSafe . "'";
    $result = $connection->query($sql);
    if ($result === false) {
        return false;
    }
    $exists = $result->num_rows > 0;
    $result->free();
    return $exists;
}

function columnExists(mysqli $connection, string $table, string $column): bool
{
    if (!tableExists($connection, $table)) {
        return false;
    }
    $tableSafe = $connection->real_escape_string($table);
    $columnSafe = $connection->real_escape_string($column);
    $sql = "SHOW COLUMNS FROM `{$tableSafe}` LIKE '{$columnSafe}'";
    $result = $connection->query($sql);
    if ($result === false) {
        return false;
    }
    $exists = $result->num_rows > 0;
    $result->free();
    return $exists;
}

function fetchAllAssoc(?mysqli_stmt $stmt): array
{
    if (!$stmt) {
        return [];
    }
    $result = $stmt->get_result();
    if (!$result) {
        return [];
    }
    $data = $result->fetch_all(MYSQLI_ASSOC);
    $result->free();
    return $data;
}

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        echo '{"success":false,"message":"JSON encoding failed."}';
    } else {
        echo $encoded;
    }
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || $userRole !== 'manager') {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

try {
    // الإيرادات حسب الدورة
    $revenueData = [];
    if (tableExists($conn, 'courses')) {
        $paymentsJoin = '';
        $filters = '';
        if (tableExists($conn, 'payments')) {
            $statusColumn = columnExists($conn, 'payments', 'status');
            $paymentStatusColumn = columnExists($conn, 'payments', 'payment_status');
            if ($statusColumn) {
                $filters = "COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount ELSE 0 END), 0) AS total_revenue,
                            SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) AS payments_count";
            } elseif ($paymentStatusColumn) {
                $filters = "COALESCE(SUM(CASE WHEN p.payment_status IN ('paid','completed') THEN p.amount ELSE 0 END), 0) AS total_revenue,
                            SUM(CASE WHEN p.payment_status IN ('paid','completed') THEN 1 ELSE 0 END) AS payments_count";
            } else {
                $filters = "COALESCE(SUM(p.amount), 0) AS total_revenue,
                            COUNT(p.payment_id) AS payments_count";
            }
            $paymentsJoin = "LEFT JOIN payments p ON p.course_id = c.course_id";
        } else {
            $filters = "0 AS total_revenue, 0 AS payments_count";
        }

        $sql = "SELECT c.course_id, c.title, {$filters}
                 FROM courses c
                 {$paymentsJoin}
                 GROUP BY c.course_id, c.title
                 HAVING total_revenue > 0
                 ORDER BY total_revenue DESC
                 LIMIT 12";
        $revenueStmt = $conn->prepare($sql);
        if ($revenueStmt && $revenueStmt->execute()) {
            $revenueData = fetchAllAssoc($revenueStmt);
        }
        if ($revenueStmt) {
            $revenueStmt->close();
        }
    }

    // أداء المدربين
    $trainerData = [];
    $hasUsers = tableExists($conn, 'users');
    $hasCourses = tableExists($conn, 'courses');
    if ($hasUsers && $hasCourses) {
        $enrollmentJoin = '';
        $completedExpr = '0';
        $activeExpr = '0';
        if (tableExists($conn, 'enrollments')) {
            $enrollmentJoin = 'LEFT JOIN enrollments e ON e.course_id = c.course_id';
            if (columnExists($conn, 'enrollments', 'status')) {
                $completedExpr = "SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END)";
                $activeExpr = "SUM(CASE WHEN e.status IN ('active','pending') THEN 1 ELSE 0 END)";
            } else {
                $completedExpr = 'COUNT(e.enrollment_id)';
                $activeExpr = 'COUNT(e.enrollment_id)';
            }
        }

        $sql = "SELECT u.id AS trainer_id,
                       u.full_name AS trainer_name,
                       COUNT(DISTINCT c.course_id) AS courses_count,
                       {$completedExpr} AS completed_enrollments,
                       {$activeExpr} AS active_enrollments
                FROM users u
                JOIN courses c ON c.trainer_id = u.id
                {$enrollmentJoin}
                WHERE u.role = 'trainer'
                GROUP BY u.id, u.full_name
                ORDER BY completed_enrollments DESC, courses_count DESC
                LIMIT 12";
        $trainerStmt = $conn->prepare($sql);
        if ($trainerStmt && $trainerStmt->execute()) {
            $trainerData = fetchAllAssoc($trainerStmt);
        }
        if ($trainerStmt) {
            $trainerStmt->close();
        }
    }

    // التوزيع الديموغرافي حسب المحافظة
    $demographicByGovernorate = [];
    if ($hasUsers && columnExists($conn, 'users', 'governorate')) {
        $demographicGovQuery = $conn->prepare(
            "SELECT COALESCE(governorate, 'غير محدد') AS label,
                    COUNT(*) AS total
             FROM users
             WHERE role = 'student'
             GROUP BY governorate
             ORDER BY total DESC, label ASC
             LIMIT 12"
        );
        if ($demographicGovQuery && $demographicGovQuery->execute()) {
            $demographicByGovernorate = fetchAllAssoc($demographicGovQuery);
        }
        if ($demographicGovQuery) {
            $demographicGovQuery->close();
        }
    }

    // التوزيع حسب النوع
    $demographicByGender = [];
    if ($hasUsers && columnExists($conn, 'users', 'gender')) {
        $demographicGenderQuery = $conn->prepare(
            "SELECT CASE WHEN gender IS NULL OR gender = '' THEN 'غير محدد'
                     WHEN gender = 'male' THEN 'ذكر'
                     WHEN gender = 'female' THEN 'أنثى'
                     ELSE gender END AS label,
                    COUNT(*) AS total
             FROM users
             WHERE role = 'student'
             GROUP BY gender"
        );
        if ($demographicGenderQuery && $demographicGenderQuery->execute()) {
            $demographicByGender = fetchAllAssoc($demographicGenderQuery);
        }
        if ($demographicGenderQuery) {
            $demographicGenderQuery->close();
        }
    }

    respond([
        'success' => true,
        'generated_at' => date('c'),
        'revenue_by_course' => $revenueData,
        'trainer_performance' => $trainerData,
        'demographics' => [
            'by_governorate' => $demographicByGovernorate,
            'by_gender' => $demographicByGender
        ]
    ]);
} catch (Throwable $throwable) {
    respond(['success' => false, 'message' => 'فشل جلب بيانات التحليلات: ' . $throwable->getMessage()], 500);
}
