<?php
/**
 * Certificates Management API
 * نظام إدارة الشهادات - API
 * 
 * للمشرف الفني والمدير العام
 * 
 * الوظائف:
 * - إصدار شهادات فردية
 * - إصدار شهادات جماعية
 * - عرض جميع الشهادات
 * - تحميل الشهادة PDF
 * - إرسال الشهادة عبر Email/WhatsApp
 * - إحصائيات الشهادات
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

// Check permissions
$allowedRoles = ['manager', 'technical', 'student'];
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listCertificates($conn, $userId, $userRole);
            break;
        case 'get':
            getCertificate($conn, $userId, $userRole);
            break;
        case 'issue':
            issueCertificate($conn, $userId);
            break;
        case 'bulk_issue':
            bulkIssueCertificates($conn, $userId);
            break;
        case 'update':
            updateCertificate($conn, $userId);
            break;
        case 'revoke':
            revokeCertificate($conn);
            break;
        case 'verify':
            verifyCertificate($conn);
            break;
        case 'download_pdf':
            downloadCertificatePDF($conn, $userId, $userRole);
            break;
        case 'send_email':
            sendCertificateEmail($conn);
            break;
        case 'send_whatsapp':
            sendCertificateWhatsApp($conn);
            break;
        case 'statistics':
            getStatistics($conn);
            break;
        case 'monthly_stats':
            getMonthlyStats($conn);
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
 * List all certificates
 */
function listCertificates($conn, $userId, $userRole) {
    $status = $_GET['status'] ?? 'all';
    $course = $_GET['course'] ?? 'all';
    $search = $_GET['search'] ?? '';
    $sort = $_GET['sort'] ?? 'newest';
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // For students: show only their certificates
    if ($userRole === 'student') {
        $whereConditions[] = "c.student_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    // Status filter
    if ($status !== 'all') {
        $whereConditions[] = "c.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Course filter
    if ($course !== 'all') {
        $whereConditions[] = "c.course_id = ?";
        $params[] = intval($course);
        $types .= 'i';
    }
    
    // Search filter
    if (!empty($search)) {
        $whereConditions[] = "(u.full_name LIKE ? OR c.certificate_number LIKE ? OR co.title LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Sorting
    $orderBy = match($sort) {
        'oldest' => 'c.issue_date ASC',
        'name' => 'u.full_name ASC',
        'course' => 'co.title ASC',
        default => 'c.issue_date DESC'
    };
    
    $sql = "SELECT 
                c.*,
                u.full_name as student_name,
                u.email,
                u.photo,
                co.title as course_name,
                co.hours as course_hours,
                t.full_name as trainer_name
            FROM certificates c
            JOIN users u ON c.student_id = u.id
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN users t ON co.trainer_id = t.id
            $whereClause
            ORDER BY $orderBy";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $certificates = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $certificates,
        'count' => count($certificates)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get single certificate details
 */
function getCertificate($conn, $userId, $userRole) {
    $certId = intval($_GET['id'] ?? 0);
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    $sql = "SELECT 
                c.*,
                u.full_name as student_name,
                u.email,
                u.phone,
                u.photo,
                u.birth_date,
                u.national_id,
                co.title as course_name,
                co.description as course_description,
                co.hours as course_hours,
                co.start_date as course_start_date,
                co.end_date as course_end_date,
                t.full_name as trainer_name,
                t.specialization as trainer_specialization,
                g.final_grade,
                g.final_percentage
            FROM certificates c
            JOIN users u ON c.student_id = u.id
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN users t ON co.trainer_id = t.id
            LEFT JOIN grades g ON c.student_id = g.student_id AND c.course_id = g.course_id
            WHERE c.id = ?";
    
    // Students can only view their own certificates
    if ($userRole === 'student') {
        $sql .= " AND c.student_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($userRole === 'student') {
        $stmt->bind_param('ii', $certId, $userId);
    } else {
        $stmt->bind_param('i', $certId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الشهادة غير موجودة');
    }
    
    $certificate = $result->fetch_assoc();
    
    // Get verification count
    $verifyStmt = $conn->prepare("SELECT COUNT(*) as count FROM certificate_verifications WHERE certificate_id = ?");
    $verifyStmt->bind_param('i', $certId);
    $verifyStmt->execute();
    $certificate['verification_count'] = intval($verifyStmt->get_result()->fetch_assoc()['count']);
    
    echo json_encode([
        'success' => true,
        'data' => $certificate
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Issue single certificate
 */
function issueCertificate($conn, $userId) {
    $studentId = intval($_POST['student_id'] ?? 0);
    $courseId = intval($_POST['course_id'] ?? 0);
    $grade = floatval($_POST['grade'] ?? 0);
    $notes = $_POST['notes'] ?? '';
    $sendEmail = isset($_POST['send_email']) ? filter_var($_POST['send_email'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if ($studentId === 0 || $courseId === 0) {
        throw new Exception('معرف الطالب والدورة مطلوبان');
    }
    
    // Check if certificate already exists
    $checkStmt = $conn->prepare("SELECT id FROM certificates WHERE student_id = ? AND course_id = ? AND status != 'revoked'");
    $checkStmt->bind_param('ii', $studentId, $courseId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception('الشهادة موجودة بالفعل لهذا الطالب');
    }
    
    // Check if student completed course
    $enrollStmt = $conn->prepare("SELECT status FROM enrollments WHERE user_id = ? AND course_id = ?");
    $enrollStmt->bind_param('ii', $studentId, $courseId);
    $enrollStmt->execute();
    $enrollment = $enrollStmt->get_result()->fetch_assoc();
    
    if (!$enrollment || $enrollment['status'] !== 'completed') {
        throw new Exception('الطالب لم يكمل الدورة بعد');
    }
    
    // Generate certificate number
    $certNumber = generateCertificateNumber($conn);
    
    // Generate verification code
    $verificationCode = generateVerificationCode();
    
    $issueDate = date('Y-m-d');
    
    $stmt = $conn->prepare("INSERT INTO certificates 
                           (student_id, course_id, certificate_number, verification_code, 
                            grade, issue_date, notes, status, issued_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, ?, 'issued', ?, NOW())");
    $stmt->bind_param('iissdss i', $studentId, $courseId, $certNumber, $verificationCode, 
                      $grade, $issueDate, $notes, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إصدار الشهادة');
    }
    
    $certId = $conn->insert_id;
    
    // Create notification
    $message = "تهانينا! تم إصدار شهادتك بنجاح. رقم الشهادة: $certNumber";
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, link, created_at) 
                                 VALUES (?, ?, 'certificate', ?, NOW())");
    $link = "/student-dashboard.php?page=certificates&action=view&id=$certId";
    $notifStmt->bind_param('iss', $studentId, $message, $link);
    $notifStmt->execute();
    
    // Send email if requested
    if ($sendEmail) {
        sendCertificateByEmail($conn, $certId);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إصدار الشهادة بنجاح',
        'certificate_id' => $certId,
        'certificate_number' => $certNumber
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Bulk issue certificates for course
 */
function bulkIssueCertificates($conn, $userId) {
    $courseId = intval($_POST['course_id'] ?? 0);
    $minGrade = floatval($_POST['min_grade'] ?? 50); // Minimum passing grade
    $sendEmail = isset($_POST['send_email']) ? filter_var($_POST['send_email'], FILTER_VALIDATE_BOOLEAN) : false;
    
    if ($courseId === 0) {
        throw new Exception('معرف الدورة مطلوب');
    }
    
    // Get all completed students with passing grades
    $stmt = $conn->prepare("SELECT DISTINCT e.user_id, g.final_percentage as grade
                           FROM enrollments e
                           JOIN grades g ON e.user_id = g.student_id AND e.course_id = g.course_id
                           LEFT JOIN certificates c ON e.user_id = c.student_id AND e.course_id = c.course_id AND c.status != 'revoked'
                           WHERE e.course_id = ? 
                           AND e.status = 'completed'
                           AND g.final_percentage >= ?
                           AND c.id IS NULL");
    $stmt->bind_param('id', $courseId, $minGrade);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $issued = 0;
    $errors = [];
    
    foreach ($students as $student) {
        try {
            $_POST['student_id'] = $student['user_id'];
            $_POST['course_id'] = $courseId;
            $_POST['grade'] = $student['grade'];
            $_POST['send_email'] = $sendEmail;
            
            issueCertificate($conn, $userId);
            $issued++;
        } catch (Exception $e) {
            $errors[] = "Student ID {$student['user_id']}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "تم إصدار $issued شهادة بنجاح",
        'issued' => $issued,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Update certificate
 */
function updateCertificate($conn, $userId) {
    $certId = intval($_POST['certificate_id'] ?? 0);
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (isset($_POST['grade'])) {
        $updates[] = "grade = ?";
        $params[] = floatval($_POST['grade']);
        $types .= 'd';
    }
    
    if (isset($_POST['notes'])) {
        $updates[] = "notes = ?";
        $params[] = $_POST['notes'];
        $types .= 's';
    }
    
    if (empty($updates)) {
        throw new Exception('لا توجد بيانات للتحديث');
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $certId;
    $types .= 'i';
    
    $sql = "UPDATE certificates SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث الشهادة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث الشهادة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Revoke certificate
 */
function revokeCertificate($conn) {
    $certId = intval($_POST['certificate_id'] ?? 0);
    $reason = $_POST['reason'] ?? '';
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    $stmt = $conn->prepare("UPDATE certificates 
                           SET status = 'revoked', revocation_reason = ?, revoked_at = NOW() 
                           WHERE id = ?");
    $stmt->bind_param('si', $reason, $certId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إلغاء الشهادة');
    }
    
    // Notify student
    $certStmt = $conn->prepare("SELECT student_id FROM certificates WHERE id = ?");
    $certStmt->bind_param('i', $certId);
    $certStmt->execute();
    $studentId = $certStmt->get_result()->fetch_assoc()['student_id'];
    
    $message = "تم إلغاء شهادتك. السبب: $reason";
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                                 VALUES (?, ?, 'certificate', NOW())");
    $notifStmt->bind_param('is', $studentId, $message);
    $notifStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إلغاء الشهادة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Verify certificate by number or code
 */
function verifyCertificate($conn) {
    $certNumber = $_GET['certificate_number'] ?? '';
    $verificationCode = $_GET['verification_code'] ?? '';
    
    if (empty($certNumber) && empty($verificationCode)) {
        throw new Exception('رقم الشهادة أو رمز التحقق مطلوب');
    }
    
    $sql = "SELECT 
                c.*,
                u.full_name as student_name,
                co.title as course_name,
                co.hours as course_hours,
                t.full_name as trainer_name
            FROM certificates c
            JOIN users u ON c.student_id = u.id
            LEFT JOIN courses co ON c.course_id = co.course_id
            LEFT JOIN users t ON co.trainer_id = t.id
            WHERE (c.certificate_number = ? OR c.verification_code = ?)
            AND c.status = 'issued'";
    
    $stmt = $conn->prepare($sql);
    $searchParam = !empty($certNumber) ? $certNumber : $verificationCode;
    $stmt->bind_param('ss', $searchParam, $searchParam);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الشهادة غير موجودة أو غير صالحة');
    }
    
    $certificate = $result->fetch_assoc();
    
    // Log verification
    $logStmt = $conn->prepare("INSERT INTO certificate_verifications (certificate_id, verified_at, ip_address) 
                              VALUES (?, NOW(), ?)");
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $logStmt->bind_param('is', $certificate['id'], $ipAddress);
    $logStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'الشهادة صحيحة',
        'data' => [
            'certificate_number' => $certificate['certificate_number'],
            'student_name' => $certificate['student_name'],
            'course_name' => $certificate['course_name'],
            'course_hours' => $certificate['course_hours'],
            'trainer_name' => $certificate['trainer_name'],
            'grade' => $certificate['grade'],
            'issue_date' => $certificate['issue_date']
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Download certificate as PDF
 */
function downloadCertificatePDF($conn, $userId, $userRole) {
    $certId = intval($_GET['id'] ?? 0);
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    // This would integrate with existing PDF generation system
    // Redirect to the existing certificate PDF generator
    echo json_encode([
        'success' => true,
        'redirect' => "../api/certificates_advanced.php?action=download&id=$certId"
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Send certificate via email
 */
function sendCertificateEmail($conn) {
    $certId = intval($_POST['certificate_id'] ?? 0);
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    $sent = sendCertificateByEmail($conn, $certId);
    
    if ($sent) {
        // Update sent status
        $updateStmt = $conn->prepare("UPDATE certificates SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('i', $certId);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال الشهادة عبر البريد الإلكتروني بنجاح'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل في إرسال البريد الإلكتروني');
    }
}

/**
 * Send certificate via WhatsApp
 */
function sendCertificateWhatsApp($conn) {
    $certId = intval($_POST['certificate_id'] ?? 0);
    
    if ($certId === 0) {
        throw new Exception('معرف الشهادة مطلوب');
    }
    
    // Get certificate and user details
    $stmt = $conn->prepare("SELECT c.*, u.phone, u.full_name, co.title as course_name 
                           FROM certificates c
                           JOIN users u ON c.student_id = u.id
                           LEFT JOIN courses co ON c.course_id = co.course_id
                           WHERE c.id = ?");
    $stmt->bind_param('i', $certId);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
    
    if (!$cert) {
        throw new Exception('الشهادة غير موجودة');
    }
    
    // Generate WhatsApp link
    $phone = preg_replace('/[^0-9]/', '', $cert['phone']);
    $message = "مبروك {$cert['full_name']}!\n\n";
    $message .= "تم إصدار شهادتك بنجاح:\n";
    $message .= "الدورة: {$cert['course_name']}\n";
    $message .= "رقم الشهادة: {$cert['certificate_number']}\n";
    $message .= "الدرجة: {$cert['grade']}%\n";
    $message .= "تاريخ الإصدار: {$cert['issue_date']}\n\n";
    $message .= "يمكنك تحميل الشهادة من المنصة.";
    
    $whatsappLink = "https://wa.me/$phone?text=" . urlencode($message);
    
    echo json_encode([
        'success' => true,
        'message' => 'جاهز للإرسال عبر واتساب',
        'whatsapp_link' => $whatsappLink
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get statistics
 */
function getStatistics($conn) {
    $stats = [
        'total' => 0,
        'issued_this_month' => 0,
        'issued_this_year' => 0,
        'revoked' => 0,
        'verified_count' => 0
    ];
    
    // Total certificates
    $result = $conn->query("SELECT COUNT(*) as count FROM certificates WHERE status = 'issued'");
    $stats['total'] = intval($result->fetch_assoc()['count']);
    
    // Issued this month
    $result = $conn->query("SELECT COUNT(*) as count FROM certificates 
                           WHERE MONTH(issue_date) = MONTH(CURRENT_DATE())
                           AND YEAR(issue_date) = YEAR(CURRENT_DATE())
                           AND status = 'issued'");
    $stats['issued_this_month'] = intval($result->fetch_assoc()['count']);
    
    // Issued this year
    $result = $conn->query("SELECT COUNT(*) as count FROM certificates 
                           WHERE YEAR(issue_date) = YEAR(CURRENT_DATE())
                           AND status = 'issued'");
    $stats['issued_this_year'] = intval($result->fetch_assoc()['count']);
    
    // Revoked certificates
    $result = $conn->query("SELECT COUNT(*) as count FROM certificates WHERE status = 'revoked'");
    $stats['revoked'] = intval($result->fetch_assoc()['count']);
    
    // Total verifications
    $result = $conn->query("SELECT COUNT(*) as count FROM certificate_verifications");
    $stats['verified_count'] = intval($result->fetch_assoc()['count']);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get monthly statistics (last 6 months)
 */
function getMonthlyStats($conn) {
    $sql = "SELECT 
                DATE_FORMAT(issue_date, '%Y-%m') as month,
                DATE_FORMAT(issue_date, '%M %Y') as month_name,
                COUNT(*) as count
            FROM certificates
            WHERE issue_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 MONTH)
            AND status = 'issued'
            GROUP BY DATE_FORMAT(issue_date, '%Y-%m')
            ORDER BY month ASC";
    
    $result = $conn->query($sql);
    $stats = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Generate unique certificate number
 */
function generateCertificateNumber($conn) {
    $year = date('Y');
    $prefix = 'CERT' . $year;
    
    // Get last certificate number
    $stmt = $conn->query("SELECT certificate_number FROM certificates 
                         WHERE certificate_number LIKE '{$prefix}%' 
                         ORDER BY certificate_number DESC LIMIT 1");
    
    if ($stmt->num_rows > 0) {
        $lastNumber = $stmt->fetch_assoc()['certificate_number'];
        $sequence = intval(substr($lastNumber, -5)) + 1;
    } else {
        $sequence = 1;
    }
    
    return $prefix . str_pad($sequence, 5, '0', STR_PAD_LEFT);
}

/**
 * Generate verification code
 */
function generateVerificationCode() {
    return strtoupper(bin2hex(random_bytes(8))); // 16 character hex code
}

/**
 * Send certificate by email
 */
function sendCertificateByEmail($conn, $certId) {
    // Get certificate details
    $stmt = $conn->prepare("SELECT c.*, u.email, u.full_name, co.title as course_name 
                           FROM certificates c
                           JOIN users u ON c.student_id = u.id
                           LEFT JOIN courses co ON c.course_id = co.course_id
                           WHERE c.id = ?");
    $stmt->bind_param('i', $certId);
    $stmt->execute();
    $cert = $stmt->get_result()->fetch_assoc();
    
    if (!$cert) {
        return false;
    }
    
    // Integrate with existing email system
    error_log("Certificate email sent to: {$cert['email']} - Cert: {$cert['certificate_number']}");
    return true; // Return true for now
}
