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

if (!headers_sent()) {
    header('Content-Type: application/json; charset=utf-8');
}

/**
 * Helper: unified JSON response
 */
function respond(bool $success, string $message = '', array $extra = []): void {
    $payload = array_merge(['success' => $success], $message !== '' ? ['message' => $message] : [], $extra);
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function sanitizePaymentMethod(?string $method): string {
    $method = strtolower(trim($method ?? 'cash'));
    $allowed = ['cash', 'card', 'transfer', 'other'];
    return in_array($method, $allowed, true) ? $method : 'cash';
}

function sanitizePaymentStatus(?string $status): string {
    $status = strtolower(trim($status ?? 'pending'));
    $allowed = ['pending', 'completed', 'cancelled', 'refunded'];
    return in_array($status, $allowed, true) ? $status : 'pending';
}

function formatPaymentDate(?string $date): string {
    $date = trim($date ?? '');
    return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) ? $date : date('Y-m-d');
}

function fetchFinancialSummary(mysqli $conn): array {
    $sql = "
        SELECT
            COALESCE(SUM(CASE WHEN status = 'completed' THEN amount END), 0) AS total_completed,
            COALESCE(SUM(CASE WHEN status = 'pending' THEN amount END), 0) AS total_pending,
            COALESCE(SUM(CASE WHEN status = 'cancelled' THEN amount END), 0) AS total_cancelled,
            COALESCE(SUM(CASE WHEN status = 'refunded' THEN amount END), 0) AS total_refunded,
            COUNT(*) AS total_payments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed_count,
            SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending_count
        FROM payments
    ";

    $res = $conn->query($sql);
    $row = $res ? $res->fetch_assoc() : [];

    return [
        'total_revenue'   => round((float)($row['total_completed'] ?? 0), 2),
        'pending_amount'  => round((float)($row['total_pending'] ?? 0), 2),
        'cancelled_amount'=> round((float)($row['total_cancelled'] ?? 0), 2),
        'refunded_amount' => round((float)($row['total_refunded'] ?? 0), 2),
        'total_payments'  => (int)($row['total_payments'] ?? 0),
        'completed_count' => (int)($row['completed_count'] ?? 0),
        'pending_count'   => (int)($row['pending_count'] ?? 0)
    ];
}

function fetchMonthlyTrends(mysqli $conn, int $year): array {
    $stmt = $conn->prepare("SELECT YEAR(payment_date) AS year, MONTH(payment_date) AS month, SUM(amount) AS total, COUNT(*) AS count FROM payments WHERE status = 'completed' AND YEAR(payment_date) = ? GROUP BY YEAR(payment_date), MONTH(payment_date) ORDER BY YEAR(payment_date), MONTH(payment_date)");
    $stmt->bind_param('i', $year);
    $stmt->execute();
    $res = $stmt->get_result();

    $rows = [];
    while ($row = $res->fetch_assoc()) {
        $rows[] = [
            'year'  => (int)$row['year'],
            'month' => (int)$row['month'],
            'total' => round((float)$row['total'], 2),
            'count' => (int)$row['count']
        ];
    }

    return $rows;
}

function fetchPayments(mysqli $conn, ?string $status, int $limit): array {
    $limit = max(1, min($limit, 1000));
    $baseSql = "
        SELECT
            p.payment_id,
            p.user_id,
            p.course_id,
            p.amount,
            p.payment_method,
            p.status,
            DATE_FORMAT(p.payment_date, '%Y-%m-%d') AS payment_date,
            p.notes,
            COALESCE(p.processed_by, 0) AS processed_by,
            u.full_name AS student_name,
            u.email AS student_email,
            u.phone AS student_phone,
            c.title AS course_title
        FROM payments p
        INNER JOIN users u ON p.user_id = u.id
        LEFT JOIN courses c ON p.course_id = c.course_id
    ";

    $payments = [];

    if ($status && in_array($status, ['pending', 'completed', 'cancelled', 'refunded'], true)) {
        $sql = $baseSql . " WHERE p.status = ? ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('si', $status, $limit);
    } else {
        $sql = $baseSql . " ORDER BY p.payment_date DESC, p.payment_id DESC LIMIT ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $limit);
    }

    $stmt->execute();
    $res = $stmt->get_result();

    while ($row = $res->fetch_assoc()) {
        $payments[] = [
            'payment_id'     => (int)$row['payment_id'],
            'user_id'        => (int)$row['user_id'],
            'course_id'      => $row['course_id'] !== null ? (int)$row['course_id'] : null,
            'amount'         => round((float)$row['amount'], 2),
            'payment_method' => $row['payment_method'],
            'status'         => $row['status'],
            'payment_date'   => $row['payment_date'],
            'notes'          => $row['notes'],
            'processed_by'   => (int)$row['processed_by'],
            'student_name'   => $row['student_name'],
            'student_email'  => $row['student_email'],
            'student_phone'  => $row['student_phone'],
            'course_title'   => $row['course_title']
        ];
    }

    return $payments;
}

function fetchPaymentById(mysqli $conn, int $paymentId): ?array {
    $stmt = $conn->prepare("SELECT payment_id, user_id, course_id, amount, payment_method, status, DATE_FORMAT(payment_date, '%Y-%m-%d') AS payment_date, notes FROM payments WHERE payment_id = ? LIMIT 1");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $res = $stmt->get_result();
    $row = $res ? $res->fetch_assoc() : null;
    if (!$row) {
        return null;
    }
    return [
        'payment_id'     => (int)$row['payment_id'],
        'user_id'        => (int)$row['user_id'],
        'course_id'      => $row['course_id'] !== null ? (int)$row['course_id'] : null,
        'amount'         => round((float)$row['amount'], 2),
        'payment_method' => $row['payment_method'],
        'status'         => $row['status'],
        'payment_date'   => $row['payment_date'],
        'notes'          => $row['notes']
    ];
}

function fetchPendingPayments(mysqli $conn): array {
    $sql = "
        SELECT
            e.enrollment_id,
            e.user_id,
            e.course_id,
            DATE_FORMAT(e.created_at, '%Y-%m-%d') AS enrollment_date,
            u.full_name AS student_name,
            u.email AS student_email,
            u.phone AS student_phone,
            c.title AS course_title,
            COALESCE(c.fees, 0) AS course_price
        FROM enrollments e
        INNER JOIN users u ON e.user_id = u.id
        INNER JOIN courses c ON e.course_id = c.course_id
        WHERE e.payment_status = 'pending'
          AND e.status = 'pending'
        ORDER BY e.created_at DESC
    ";

    $res = $conn->query($sql);
    $rows = [];
    if ($res) {
        while ($row = $res->fetch_assoc()) {
            $rows[] = [
                'enrollment_id' => (int)$row['enrollment_id'],
                'user_id'       => (int)$row['user_id'],
                'course_id'     => (int)$row['course_id'],
                'enrollment_date'=> $row['enrollment_date'],
                'student_name'  => $row['student_name'],
                'student_email' => $row['student_email'],
                'student_phone' => $row['student_phone'],
                'course_title'  => $row['course_title'],
                'course_price'  => round((float)$row['course_price'], 2)
            ];
        }
    }

    return $rows;
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || !in_array($userRole, ['technical', 'manager'], true)) {
    respond(false, 'غير مصرح لك - الشؤون المالية مخصصة للمشرفين التقنيين/الإدارة');
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$queryAction = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        switch ($queryAction) {
            case 'get_financial_summary':
                respond(true, '', ['data' => fetchFinancialSummary($conn)]);

            case 'get_monthly_trends':
                $year = isset($_GET['year']) ? (int)$_GET['year'] : (int)date('Y');
                respond(true, '', ['data' => fetchMonthlyTrends($conn, $year)]);

            case 'get_pending_payments':
                $pending = fetchPendingPayments($conn);
                respond(true, '', ['data' => $pending, 'count' => count($pending)]);

            case 'get_payment':
                $paymentId = isset($_GET['payment_id']) ? (int)$_GET['payment_id'] : 0;
                if ($paymentId <= 0) {
                    respond(false, 'معرف الدفعة مطلوب');
                }
                $payment = fetchPaymentById($conn, $paymentId);
                if (!$payment) {
                    respond(false, 'لم يتم العثور على الدفعة المطلوبة');
                }
                respond(true, '', ['payment' => $payment]);

            case 'list_payments':
            case '':
                $statusFilter = $_GET['status'] ?? null;
                $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 500;
                $payments = fetchPayments($conn, $statusFilter, $limit);
                $summary = fetchFinancialSummary($conn);
                respond(true, '', [
                    'data'    => $payments,
                    'summary' => $summary,
                    'meta'    => ['count' => count($payments)]
                ]);

            default:
                respond(false, 'إجراء GET غير معروف');
        }
    }

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $action = $body['action'] ?? $queryAction;

    if ($method === 'POST') {
        switch ($action) {
            case 'create':
                $userIdRef = isset($body['user_id']) ? (int)$body['user_id'] : 0;
                $courseIdRef = isset($body['course_id']) && $body['course_id'] !== '' ? (int)$body['course_id'] : 0;
                $amount = isset($body['amount']) ? (float)$body['amount'] : 0;
                $paymentMethod = sanitizePaymentMethod($body['payment_method'] ?? 'cash');
                $status = sanitizePaymentStatus($body['status'] ?? 'completed');
                $paymentDate = formatPaymentDate($body['payment_date'] ?? date('Y-m-d'));
                $notes = trim($body['notes'] ?? '');

                if ($userIdRef <= 0 || $amount <= 0) {
                    respond(false, 'الرجاء إدخال بيانات الدفعة الأساسية بشكل صحيح');
                }

                $courseIdParam = $courseIdRef > 0 ? $courseIdRef : 0;

                $stmt = $conn->prepare("INSERT INTO payments (user_id, course_id, amount, payment_method, status, payment_date, notes, processed_by) VALUES (?, NULLIF(?,0), ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param('iidssssi', $userIdRef, $courseIdParam, $amount, $paymentMethod, $status, $paymentDate, $notes, $userId);

                if (!$stmt->execute()) {
                    respond(false, 'فشل إضافة الدفعة: ' . $stmt->error);
                }

                respond(true, 'تم إنشاء الدفعة بنجاح', ['payment_id' => $conn->insert_id]);

            case 'update':
                $paymentId = isset($body['payment_id']) ? (int)$body['payment_id'] : 0;
                $userIdRef = isset($body['user_id']) ? (int)$body['user_id'] : 0;
                $courseIdRef = isset($body['course_id']) && $body['course_id'] !== '' ? (int)$body['course_id'] : 0;
                $amount = isset($body['amount']) ? (float)$body['amount'] : 0;
                $paymentMethod = sanitizePaymentMethod($body['payment_method'] ?? 'cash');
                $status = sanitizePaymentStatus($body['status'] ?? 'pending');
                $paymentDate = formatPaymentDate($body['payment_date'] ?? date('Y-m-d'));
                $notes = trim($body['notes'] ?? '');

                if ($paymentId <= 0) {
                    respond(false, 'معرف الدفعة مطلوب للتحديث');
                }

                if ($userIdRef <= 0 || $amount <= 0) {
                    respond(false, 'بيانات الدفعة غير صالحة للتحديث');
                }

                $courseIdParam = $courseIdRef > 0 ? $courseIdRef : 0;

                $stmt = $conn->prepare("UPDATE payments SET user_id = ?, course_id = NULLIF(?,0), amount = ?, payment_method = ?, status = ?, payment_date = ?, notes = ? WHERE payment_id = ?");
                $stmt->bind_param('iidssssi', $userIdRef, $courseIdParam, $amount, $paymentMethod, $status, $paymentDate, $notes, $paymentId);

                if (!$stmt->execute()) {
                    respond(false, 'فشل تحديث الدفعة: ' . $stmt->error);
                }

                respond(true, 'تم تحديث الدفعة بنجاح');

            case 'delete':
                $paymentId = isset($body['payment_id']) ? (int)$body['payment_id'] : 0;
                if ($paymentId <= 0) {
                    respond(false, 'معرف الدفعة مطلوب للحذف');
                }

                $stmt = $conn->prepare('DELETE FROM payments WHERE payment_id = ?');
                $stmt->bind_param('i', $paymentId);

                if (!$stmt->execute()) {
                    respond(false, 'فشل حذف الدفعة: ' . $stmt->error);
                }

                respond(true, 'تم حذف الدفعة بنجاح');

            case 'confirm_payment':
                $enrollmentId = isset($body['enrollment_id']) ? (int)$body['enrollment_id'] : 0;
                $studentId = isset($body['user_id']) ? (int)$body['user_id'] : 0;
                $courseId = isset($body['course_id']) ? (int)$body['course_id'] : 0;
                $amountPaid = isset($body['amount']) ? (float)$body['amount'] : 0;
                $paymentMethod = sanitizePaymentMethod($body['payment_method'] ?? 'cash');
                $notes = trim($body['notes'] ?? '');

                if ($enrollmentId <= 0 || $studentId <= 0 || $courseId <= 0 || $amountPaid <= 0) {
                    respond(false, 'بيانات تأكيد الدفع غير مكتملة');
                }

                $conn->begin_transaction();

                try {
                    $stmtEnroll = $conn->prepare("UPDATE enrollments SET payment_status = 'paid', status = 'active' WHERE enrollment_id = ? AND user_id = ?");
                    $stmtEnroll->bind_param('ii', $enrollmentId, $studentId);
                    if (!$stmtEnroll->execute()) {
                        throw new Exception('فشل تحديث حالة التسجيل');
                    }

                    $stmtPayment = $conn->prepare("INSERT INTO payments (user_id, course_id, amount, payment_method, status, payment_date, notes, processed_by) VALUES (?, ?, ?, ?, 'completed', NOW(), ?, ?)");
                    $stmtPayment->bind_param('iidssi', $studentId, $courseId, $amountPaid, $paymentMethod, $notes, $userId);
                    if (!$stmtPayment->execute()) {
                        throw new Exception('فشل تسجيل الدفعة');
                    }

                    $stmtUser = $conn->prepare('SELECT full_name, email, phone FROM users WHERE id = ? LIMIT 1');
                    $stmtUser->bind_param('i', $studentId);
                    $stmtUser->execute();
                    $userRes = $stmtUser->get_result();
                    if (!$userRes || !$userRes->num_rows) {
                        throw new Exception('الطالب غير موجود');
                    }
                    $student = $userRes->fetch_assoc();

                    $generatedPassword = preg_replace('/[^0-9]/', '', $student['phone'] ?? '') ?: substr(bin2hex(random_bytes(4)), 0, 8);
                    $passwordHash = password_hash($generatedPassword, PASSWORD_DEFAULT);

                    $hasPasswordHashCol = false;
                    $colCheck = $conn->query("SHOW COLUMNS FROM users LIKE 'password_hash'");
                    if ($colCheck && $colCheck->num_rows > 0) {
                        $hasPasswordHashCol = true;
                    }

                    if ($hasPasswordHashCol) {
                        $stmtPwd = $conn->prepare('UPDATE users SET password_hash = ?, verified = 1 WHERE id = ?');
                    } else {
                        $stmtPwd = $conn->prepare('UPDATE users SET password = ?, verified = 1 WHERE id = ?');
                    }
                    $stmtPwd->bind_param('si', $passwordHash, $studentId);
                    if (!$stmtPwd->execute()) {
                        throw new Exception('فشل تحديث كلمة المرور');
                    }

                    $stmtCourse = $conn->prepare('SELECT title FROM courses WHERE course_id = ? LIMIT 1');
                    $stmtCourse->bind_param('i', $courseId);
                    $stmtCourse->execute();
                    $courseRes = $stmtCourse->get_result();
                    $courseTitle = '';
                    if ($courseRes && $courseRes->num_rows) {
                        $courseTitle = $courseRes->fetch_assoc()['title'];
                    }

                    $commData = [
                        'email'        => $student['email'],
                        'message_type' => 'activation',
                        'student_name' => $student['full_name'],
                        'course_name'  => $courseTitle,
                        'username'     => $student['email'],
                        'password'     => $generatedPassword
                    ];

                    @file_get_contents('http://' . $_SERVER['HTTP_HOST'] . '/Manager/api/send_communication.php', false, stream_context_create([
                        'http' => [
                            'method'  => 'POST',
                            'header'  => 'Content-Type: application/json',
                            'content' => json_encode($commData)
                        ]
                    ]));

                    $stmtManager = $conn->prepare("SELECT id FROM users WHERE role = 'manager' LIMIT 1");
                    if ($stmtManager && $stmtManager->execute()) {
                        $managerRes = $stmtManager->get_result();
                        if ($managerRes && $managerRes->num_rows) {
                            $managerId = (int)$managerRes->fetch_assoc()['id'];
                            $notificationMessage = "تم تأكيد دفع الطالب {$student['full_name']} بمبلغ {$amountPaid} ريال للدورة {$courseTitle}.";
                            $link = '#finance';
                            $stmtNotif = $conn->prepare('INSERT INTO notifications (user_id, message, link) VALUES (?, ?, ?)');
                            $stmtNotif->bind_param('iss', $managerId, $notificationMessage, $link);
                            $stmtNotif->execute();
                        }
                    }

                    $conn->commit();
                    respond(true, 'تم تأكيد الدفع وتفعيل الحساب بنجاح', ['password' => $generatedPassword]);
                } catch (Exception $ex) {
                    $conn->rollback();
                    respond(false, 'خطأ أثناء تأكيد الدفع: ' . $ex->getMessage());
                }

            default:
                respond(false, 'إجراء POST غير معروف');
        }
    }

    respond(false, 'طريقة غير مدعومة');
} catch (Exception $e) {
    respond(false, 'خطأ: ' . $e->getMessage());
}
