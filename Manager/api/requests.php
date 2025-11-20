<?php
/**
 * Requests Management API
 * نظام إدارة طلبات الالتحاق - API
 * 
 * للمشرف الفني والمدير العام
 * 
 * الوظائف:
 * - عرض جميع الطلبات
 * - الموافقة على الطلبات
 * - رفض الطلبات
 * - تعيين مدرب
 * - إرسال إشعارات
 */

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Only manager and technical supervisor can access
if (!in_array($userRole, ['manager', 'technical'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listRequests($conn);
            break;
        case 'get':
            getRequest($conn);
            break;
        case 'approve':
            approveRequest($conn, $userId);
            break;
        case 'reject':
            rejectRequest($conn, $userId);
            break;
        case 'assign_trainer':
            assignTrainer($conn, $userId);
            break;
        case 'statistics':
            getStatistics($conn);
            break;
        case 'monthly_stats':
            getMonthlyStats($conn);
            break;
        case 'confirm_payment':
            confirmPayment($conn, $userId);
            break;
        default:
            throw new Exception('إجراء غير صحيح');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * List all registration requests
 */
function listRequests($conn) {
    $status = $_GET['status'] ?? 'all';
    $courseId = $_GET['course_id'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'newest';
    
    $whereConditions = ["u.role = 'student'"];
    $params = [];
    $types = '';
    
    // Status filter
    if ($status !== 'all') {
        $whereConditions[] = "u.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Course filter
    if ($courseId !== 'all') {
        $whereConditions[] = "e.course_id = ?";
        $params[] = intval($courseId);
        $types .= 'i';
    }
    
    // Search filter
    if (!empty($search)) {
        $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $whereClause = 'WHERE ' . implode(' AND ', $whereConditions);
    
    // Sort order
    $orderBy = match($sortBy) {
        'oldest' => 'u.created_at ASC',
        'name' => 'u.full_name ASC',
        default => 'u.created_at DESC'
    };
    
    $sql = "SELECT 
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.photo,
                u.status,
                u.payment_status,
                u.created_at,
                u.birth_date,
                u.gender,
                u.address,
                u.education_level,
                c.title as course_name,
                c.course_id,
                c.trainer_id,
                t.full_name as trainer_name,
                e.enrollment_date,
                e.status as enrollment_status
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            LEFT JOIN users t ON c.trainer_id = t.id
            $whereClause
            ORDER BY $orderBy";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $requests = [];
    
    while ($row = $result->fetch_assoc()) {
        // Calculate days since request
        $createdDate = new DateTime($row['created_at']);
        $now = new DateTime();
        $row['days_since_request'] = $now->diff($createdDate)->days;
        
        $requests[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $requests,
        'count' => count($requests)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get single request details
 */
function getRequest($conn) {
    $requestId = intval($_GET['id'] ?? 0);
    
    if ($requestId === 0) {
        throw new Exception('معرف الطلب مطلوب');
    }
    
    $stmt = $conn->prepare("SELECT 
                                u.*,
                                c.title as course_name,
                                c.course_id,
                                c.description as course_description,
                                c.duration as course_duration,
                                c.price as course_price,
                                t.full_name as trainer_name,
                                t.email as trainer_email,
                                e.enrollment_date,
                                e.status as enrollment_status
                            FROM users u
                            LEFT JOIN enrollments e ON u.id = e.user_id
                            LEFT JOIN courses c ON e.course_id = c.course_id
                            LEFT JOIN users t ON c.trainer_id = t.id
                            WHERE u.id = ? AND u.role = 'student'
                            LIMIT 1");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الطلب غير موجود');
    }
    
    $request = $result->fetch_assoc();
    
    // Get payment history
    $stmt = $conn->prepare("SELECT * FROM payments WHERE student_id = ? ORDER BY created_at DESC");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $payments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $request['payments'] = $payments;
    
    echo json_encode([
        'success' => true,
        'data' => $request
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Approve request
 */
function approveRequest($conn, $userId) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $trainerId = intval($_POST['trainer_id'] ?? 0);
    $notes = trim($_POST['notes'] ?? '');
    $sendEmail = isset($_POST['send_email']);
    $sendSMS = isset($_POST['send_sms']);
    
    if ($requestId === 0) {
        throw new Exception('معرف الطلب مطلوب');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Update user status
        $stmt = $conn->prepare("UPDATE users 
                               SET status = 'approved', 
                                   approved_by = ?,
                                   approved_at = NOW() 
                               WHERE id = ? AND role = 'student'");
        $stmt->bind_param('ii', $userId, $requestId);
        
        if (!$stmt->execute()) {
            throw new Exception('فشل في الموافقة على الطلب');
        }
        
        // Enroll in course if provided
        if ($courseId > 0) {
            // Check if already enrolled
            $checkStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
            $checkStmt->bind_param('ii', $requestId, $courseId);
            $checkStmt->execute();
            
            if ($checkStmt->get_result()->num_rows === 0) {
                // Create enrollment
                $enrollStmt = $conn->prepare("INSERT INTO enrollments 
                                             (user_id, course_id, enrollment_date, status) 
                                             VALUES (?, ?, NOW(), 'active')");
                $enrollStmt->bind_param('ii', $requestId, $courseId);
                $enrollStmt->execute();
            }
            
            // Assign trainer to course if provided
            if ($trainerId > 0) {
                $updateCourse = $conn->prepare("UPDATE courses SET trainer_id = ? WHERE course_id = ?");
                $updateCourse->bind_param('ii', $trainerId, $courseId);
                $updateCourse->execute();
            }
        }
        
        // Get student details
        $stmt = $conn->prepare("SELECT email, full_name, phone FROM users WHERE id = ?");
        $stmt->bind_param('i', $requestId);
        $stmt->execute();
        $student = $stmt->get_result()->fetch_assoc();
        
        // Create notification
        $message = 'تهانينا! تم الموافقة على طلبك للانضمام إلى المنصة. ';
        $message .= 'يمكنك الآن إتمام عملية الدفع للوصول لجميع الخدمات.';
        if (!empty($notes)) {
            $message .= ' ملاحظات: ' . $notes;
        }
        
        $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                                     VALUES (?, ?, 'approval', NOW())");
        $notifStmt->bind_param('is', $requestId, $message);
        $notifStmt->execute();
        
        // Send email if requested
        if ($sendEmail && $student) {
            sendApprovalEmail($student['email'], $student['full_name'], $notes);
        }
        
        // Send SMS if requested
        if ($sendSMS && $student) {
            sendApprovalSMS($student['phone'], $student['full_name']);
        }
        
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم الموافقة على الطلب بنجاح'
        ], JSON_UNESCAPED_UNICODE);
        
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

/**
 * Reject request
 */
function rejectRequest($conn, $userId) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? 'لم يتم توضيح السبب');
    $sendEmail = isset($_POST['send_email']);
    
    if ($requestId === 0) {
        throw new Exception('معرف الطلب مطلوب');
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users 
                           SET status = 'rejected', 
                               rejection_reason = ?,
                               rejected_by = ?,
                               rejected_at = NOW() 
                           WHERE id = ? AND role = 'student'");
    $stmt->bind_param('sii', $reason, $userId, $requestId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في رفض الطلب');
    }
    
    // Get student details
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    // Create notification
    $message = 'نأسف لإبلاغك بأنه تم رفض طلبك. السبب: ' . $reason;
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                                 VALUES (?, ?, 'rejection', NOW())");
    $notifStmt->bind_param('is', $requestId, $message);
    $notifStmt->execute();
    
    // Send email if requested
    if ($sendEmail && $student) {
        sendRejectionEmail($student['email'], $student['full_name'], $reason);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم رفض الطلب'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Assign trainer to student's course
 */
function assignTrainer($conn, $userId) {
    $requestId = intval($_POST['request_id'] ?? 0);
    $trainerId = intval($_POST['trainer_id'] ?? 0);
    
    if ($requestId === 0 || $trainerId === 0) {
        throw new Exception('الرجاء إدخال البيانات المطلوبة');
    }
    
    // Get student's course
    $stmt = $conn->prepare("SELECT course_id FROM enrollments WHERE user_id = ? LIMIT 1");
    $stmt->bind_param('i', $requestId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الطالب غير مسجل في أي دورة');
    }
    
    $courseId = $result->fetch_assoc()['course_id'];
    
    // Assign trainer to course
    $stmt = $conn->prepare("UPDATE courses SET trainer_id = ? WHERE course_id = ?");
    $stmt->bind_param('ii', $trainerId, $courseId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تعيين المدرب');
    }
    
    // Notify student
    $stmt = $conn->prepare("SELECT full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $trainerId);
    $stmt->execute();
    $trainerName = $stmt->get_result()->fetch_assoc()['full_name'];
    
    $message = "تم تعيين المدرب: $trainerName لدورتك.";
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                                 VALUES (?, ?, 'trainer_assigned', NOW())");
    $notifStmt->bind_param('is', $requestId, $message);
    $notifStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تعيين المدرب بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get statistics
 */
function getStatistics($conn) {
    $stats = [
        'total' => 0,
        'pending' => 0,
        'approved' => 0,
        'rejected' => 0,
        'active' => 0,
        'this_month' => 0
    ];
    
    // Total requests
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stats['total'] = intval($result->fetch_assoc()['count']);
    
    // Pending
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'pending'");
    $stats['pending'] = intval($result->fetch_assoc()['count']);
    
    // Approved
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'approved'");
    $stats['approved'] = intval($result->fetch_assoc()['count']);
    
    // Rejected
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'rejected'");
    $stats['rejected'] = intval($result->fetch_assoc()['count']);
    
    // Active
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'");
    $stats['active'] = intval($result->fetch_assoc()['count']);
    
    // This month
    $result = $conn->query("SELECT COUNT(*) as count FROM users 
                           WHERE role = 'student' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE())
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['this_month'] = intval($result->fetch_assoc()['count']);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get monthly statistics for chart
 */
function getMonthlyStats($conn) {
    $months = 6;
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected
            FROM users
            WHERE role = 'student'
            AND created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $data = [];
    while ($row = $result->fetch_assoc()) {
        $data[] = [
            'month' => $row['month'],
            'total' => intval($row['total']),
            'pending' => intval($row['pending']),
            'approved' => intval($row['approved']),
            'rejected' => intval($row['rejected'])
        ];
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Confirm payment and activate account
 */
function confirmPayment($conn, $adminId) {
    $studentId = intval($_POST['student_id'] ?? 0);
    if ($studentId <= 0) throw new Exception('رقم الطالب مطلوب');

    $conn->begin_transaction();
    try {
        // Update user status
        $stmt = $conn->prepare("UPDATE users SET account_status = 'active', payment_complete = 1, status = 'active' WHERE id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();

        // Update enrollment status
        $stmt = $conn->prepare("UPDATE enrollments SET payment_status = 'completed', status = 'active' WHERE user_id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();

        // Notify student
        $msg = "تم تأكيد الدفع وتفعيل حسابك بنجاح. يمكنك الآن الدخول للمنصة.";
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) VALUES (?, ?, 'success', NOW())");
        $stmt->bind_param('is', $studentId, $msg);
        $stmt->execute();

        // Send Email (via log)
        $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
        $stmt->bind_param('i', $studentId);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        
        if ($user) {
             $email_subject = 'تم تفعيل حسابك - منصة إبداع';
             $email_message = "مرحباً {$user['full_name']}،\n\nتم استلام الرسوم وتفعيل حسابك بنجاح.\nيمكنك الآن الدخول إلى المنصة والاستفادة من جميع الخدمات.\n\nبالتوفيق!";
             $log_stmt = $conn->prepare("INSERT INTO notification_log (recipient_email, subject, message, status, created_at) VALUES (?, ?, ?, 'pending', NOW())");
             $log_stmt->bind_param('sss', $user['email'], $email_subject, $email_message);
             $log_stmt->execute();
        }

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'تم تأكيد الدفع وتفعيل الحساب'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Send approval email
 */
function sendApprovalEmail($email, $fullName, $notes) {
    // Integrate with existing email system
    error_log("Approval email sent to: $email");
    // TODO: Implement actual email sending
}

/**
 * Send approval SMS
 */
function sendApprovalSMS($phone, $fullName) {
    // Integrate with SMS service
    error_log("Approval SMS sent to: $phone");
    // TODO: Implement actual SMS sending
}

/**
 * Send rejection email
 */
function sendRejectionEmail($email, $fullName, $reason) {
    // Integrate with existing email system
    error_log("Rejection email sent to: $email - Reason: $reason");
    // TODO: Implement actual email sending
}
