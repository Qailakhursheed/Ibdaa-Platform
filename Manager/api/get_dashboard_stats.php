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

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

function tableExists(mysqli $connection, string $table): bool {
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

function columnExists(mysqli $connection, string $table, string $column): bool {
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

function fetchSingleValue(?mysqli_result $result, string $key) {
    if (!$result) {
        return null;
    }
    $row = $result->fetch_assoc();
    $result->free();
    if (!$row || !array_key_exists($key, $row)) {
        return null;
    }
    return $row[$key];
}

// التحقق من الجلسة - للمدير والمشرف الفني فقط
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك - هذا القسم للمدير والمشرف الفني فقط'], JSON_UNESCAPED_UNICODE);
    exit;
}

try {
    // 1. إجمالي المتدربين (الطلاب)
    $total_trainees = 0;
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $totalValue = fetchSingleValue($res, 'count');
    if ($totalValue !== null) {
        $total_trainees = (int) $totalValue;
    }
    
    // 2. عدد الدورات النشطة
    $active_courses = 0;
    // التحقق من وجود جدول courses
    if (tableExists($conn, 'courses')) {
        $query = "SELECT COUNT(*) as count FROM courses";
        if (columnExists($conn, 'courses', 'status')) {
            $query .= " WHERE status = 'active'";
        }
        $res = $conn->query($query);
        $countValue = fetchSingleValue($res, 'count');
        if ($countValue !== null) {
            $active_courses = (int) $countValue;
        }
    }
    
    // 3. إجمالي الإيرادات
    $total_revenue = 0;
    // التحقق من وجود جدول payments
    if (tableExists($conn, 'payments')) {
        $query = "SELECT SUM(amount) as total FROM payments";
        if (columnExists($conn, 'payments', 'status')) {
            $query .= " WHERE status = 'completed'";
        } elseif (columnExists($conn, 'payments', 'payment_status')) {
            $query .= " WHERE payment_status IN ('paid','completed')";
        }
        $res = $conn->query($query);
        $totalRow = fetchSingleValue($res, 'total');
        if ($totalRow !== null) {
            $total_revenue = $totalRow ? (float) $totalRow : 0;
        }
    }
    
    // 4. عدد الشهادات الصادرة
    $certs_issued = 0;
    // التحقق من وجود جدول enrollments
    if (tableExists($conn, 'enrollments')) {
        $query = "SELECT COUNT(*) as count FROM enrollments";
        if (columnExists($conn, 'enrollments', 'status')) {
            $query .= " WHERE status = 'completed'";
        } elseif (columnExists($conn, 'enrollments', 'certificate_issued')) {
            $query .= " WHERE certificate_issued = 1";
        }
        $res = $conn->query($query);
        $certsValue = fetchSingleValue($res, 'count');
        if ($certsValue !== null) {
            $certs_issued = (int) $certsValue;
        }
    } else {
        // البديل: من جدول grades
        if (tableExists($conn, 'grades')) {
            $res = $conn->query("SELECT COUNT(*) as count FROM grades WHERE certificate_issued = 1");
            $certsValue = fetchSingleValue($res, 'count');
            if ($certsValue !== null) {
                $certs_issued = (int) $certsValue;
            }
        }
    }
    
    // 5. إحصائيات إضافية (اختيارية)
    $total_trainers = 0;
    $res = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
    $trainerValue = fetchSingleValue($res, 'count');
    if ($trainerValue !== null) {
        $total_trainers = (int) $trainerValue;
    }
    
    $pending_requests = 0;
    if (tableExists($conn, 'requests')) {
        $query = "SELECT COUNT(*) as count FROM requests";
        if (columnExists($conn, 'requests', 'status')) {
            $query .= " WHERE status = 'pending'";
        }
        $res = $conn->query($query);
        $pendingValue = fetchSingleValue($res, 'count');
        if ($pendingValue !== null) {
            $pending_requests = (int) $pendingValue;
        }
    }
    
    // إرجاع البيانات
    echo json_encode([
        'success' => true,
        'stats' => [
            'total_trainees' => (int)$total_trainees,
            'active_courses' => (int)$active_courses,
            'total_revenue' => round($total_revenue, 2),
            'certs_issued' => (int)$certs_issued,
            'total_trainers' => (int)$total_trainers,
            'pending_requests' => (int)$pending_requests
        ]
    ], JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'خطأ: ' . $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}
?>
