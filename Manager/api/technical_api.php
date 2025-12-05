<?php
/**
 * Technical Supervisor API
 * API المشرف الفني - نظام متكامل وآمن
 * 
 * Integrated with Manager Dashboard
 */

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/rate_limiter.php';

SessionSecurity::startSecureSession();

// Database Connection
require_once __DIR__ . '/../../includes/db_connect.php';

// CORS Headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Authentication Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'غير مصرح - يجب تسجيل الدخول'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? '';

// Verify role is technical
if ($userRole !== 'technical') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'صلاحيات غير كافية - المشرف الفني فقط'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate Limiting: 300 requests per minute for technical
$rateLimiter = new RateLimiter($conn, 300, 1);
if (!$rateLimiter->checkLimit($userId)) {
    http_response_code(429);
    echo json_encode([
        'success' => false,
        'error' => 'تم تجاوز عدد الطلبات المسموح به'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Get action from URL
$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

try {
    switch ($action) {
        // =====================================
        // COURSES MANAGEMENT
        // =====================================
        case 'all_courses':
            echo json_encode(getAllCourses($conn));
            break;

        case 'course_details':
            $courseId = $_GET['course_id'] ?? 0;
            echo json_encode(getCourseDetails($conn, $courseId));
            break;

        case 'create_course':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createCourse($conn, $userId, $data));
            break;

        case 'update_course':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateCourse($conn, $data));
            break;

        case 'delete_course':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $courseId = $_GET['course_id'] ?? 0;
            echo json_encode(deleteCourse($conn, $courseId));
            break;

        case 'approve_course':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $courseId = $_GET['course_id'] ?? 0;
            echo json_encode(approveCourse($conn, $userId, $courseId));
            break;

        // =====================================
        // STUDENTS MANAGEMENT
        // =====================================
        case 'all_students':
            echo json_encode(getAllStudents($conn));
            break;

        case 'student_details':
            $studentId = $_GET['student_id'] ?? 0;
            echo json_encode(getStudentDetails($conn, $studentId));
            break;

        case 'update_student':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateStudent($conn, $data));
            break;

        case 'suspend_student':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $studentId = $_GET['student_id'] ?? 0;
            echo json_encode(suspendStudent($conn, $userId, $studentId));
            break;

        case 'activate_student':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $studentId = $_GET['student_id'] ?? 0;
            echo json_encode(activateStudent($conn, $userId, $studentId));
            break;

        // =====================================
        // TRAINERS MANAGEMENT
        // =====================================
        case 'all_trainers':
            echo json_encode(getAllTrainers($conn));
            break;

        case 'trainer_details':
            $trainerId = $_GET['trainer_id'] ?? 0;
            echo json_encode(getTrainerDetails($conn, $trainerId));
            break;

        case 'create_trainer':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createTrainer($conn, $userId, $data));
            break;

        case 'update_trainer':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(updateTrainer($conn, $data));
            break;

        case 'trainer_evaluations':
            $trainerId = $_GET['trainer_id'] ?? 0;
            echo json_encode(getTrainerEvaluations($conn, $trainerId));
            break;

        case 'submit_evaluation':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(submitTrainerEvaluation($conn, $userId, $data));
            break;

        // =====================================
        // MATERIALS MANAGEMENT
        // =====================================
        case 'all_materials':
            $courseId = $_GET['course_id'] ?? null;
            echo json_encode(getAllMaterials($conn, $courseId));
            break;

        case 'approve_material':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $materialId = $_GET['material_id'] ?? 0;
            echo json_encode(approveMaterial($conn, $userId, $materialId));
            break;

        case 'reject_material':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $materialId = $_GET['material_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            echo json_encode(rejectMaterial($conn, $userId, $materialId, $reason));
            break;

        case 'delete_material':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $materialId = $_GET['material_id'] ?? 0;
            echo json_encode(deleteMaterial($conn, $materialId));
            break;

        // =====================================
        // REQUESTS MANAGEMENT
        // =====================================
        case 'all_requests':
            $status = $_GET['status'] ?? null;
            echo json_encode(getAllRequests($conn, $status));
            break;

        case 'approve_request':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $requestId = $_GET['request_id'] ?? 0;
            echo json_encode(approveRequest($conn, $userId, $requestId));
            break;

        case 'reject_request':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $requestId = $_GET['request_id'] ?? 0;
            $reason = $_POST['reason'] ?? '';
            echo json_encode(rejectRequest($conn, $userId, $requestId, $reason));
            break;

        // =====================================
        // ID CARDS MANAGEMENT
        // =====================================
        case 'id_card_requests':
            echo json_encode(getIdCardRequests($conn));
            break;

        case 'generate_id_card':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $studentId = $_GET['student_id'] ?? 0;
            echo json_encode(generateIdCard($conn, $userId, $studentId));
            break;

        case 'print_id_cards':
            $studentIds = $_GET['student_ids'] ?? '';
            echo json_encode(printMultipleIdCards($conn, $studentIds));
            break;

        // =====================================
        // CERTIFICATES MANAGEMENT
        // =====================================
        case 'all_certificates':
            echo json_encode(getAllCertificates($conn));
            break;

        case 'generate_certificate':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(generateCertificate($conn, $userId, $data));
            break;

        case 'certificate_templates':
            echo json_encode(getCertificateTemplates($conn));
            break;

        // =====================================
        // SUPPORT TICKETS
        // =====================================
        case 'support_tickets':
            $status = $_GET['status'] ?? 'open';
            echo json_encode(getSupportTickets($conn, $status));
            break;

        case 'ticket_details':
            $ticketId = $_GET['ticket_id'] ?? 0;
            echo json_encode(getTicketDetails($conn, $ticketId));
            break;

        case 'reply_ticket':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(replyToTicket($conn, $userId, $data));
            break;

        case 'close_ticket':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $ticketId = $_GET['ticket_id'] ?? 0;
            echo json_encode(closeTicket($conn, $userId, $ticketId));
            break;

        // =====================================
        // ANNOUNCEMENTS
        // =====================================
        case 'create_announcement':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createAnnouncement($conn, $userId, $data));
            break;

        case 'all_announcements':
            echo json_encode(getAllAnnouncements($conn));
            break;

        case 'delete_announcement':
            if ($method !== 'POST') throw new Exception('طلب غير صالح');
            CSRFProtection::validateToken();
            $announcementId = $_GET['announcement_id'] ?? 0;
            echo json_encode(deleteAnnouncement($conn, $announcementId));
            break;

        // =====================================
        // STATISTICS & REPORTS
        // =====================================
        case 'statistics':
            echo json_encode(getStatistics($conn));
            break;

        case 'reports':
            $type = $_GET['type'] ?? 'summary';
            echo json_encode(generateReport($conn, $type));
            break;

        case 'quality_metrics':
            echo json_encode(getQualityMetrics($conn));
            break;

        // =====================================
        // FINANCE
        // =====================================
        case 'finance_overview':
            echo json_encode(getFinanceOverview($conn));
            break;

        case 'pending_payments':
            echo json_encode(getPendingPayments($conn));
            break;

        case 'revenue_report':
            $startDate = $_GET['start_date'] ?? null;
            $endDate = $_GET['end_date'] ?? null;
            echo json_encode(getRevenueReport($conn, $startDate, $endDate));
            break;

        default:
            throw new Exception('إجراء غير معروف');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
    
    error_log("Technical API Error: " . $e->getMessage());
}

// ======================================
// COURSES FUNCTIONS
// ======================================

function getAllCourses($conn) {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as trainer_name,
            u.email as trainer_email,
            COUNT(DISTINCT e.user_id) as students_count,
            AVG(e.progress) as avg_progress,
            AVG(e.final_grade) as avg_grade
        FROM courses c
        LEFT JOIN users u ON c.trainer_id = u.user_id
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
        GROUP BY c.course_id
        ORDER BY c.start_date DESC
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getCourseDetails($conn, $courseId) {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.full_name as trainer_name,
            u.email as trainer_email,
            u.phone as trainer_phone,
            COUNT(DISTINCT e.user_id) as total_students,
            COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.user_id END) as active_students,
            AVG(e.final_grade) as avg_grade
        FROM courses c
        LEFT JOIN users u ON c.trainer_id = u.user_id
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.course_id = ?
        GROUP BY c.course_id
    ");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function createCourse($conn, $userId, $data) {
    $courseName = $data['course_name'] ?? '';
    $description = $data['description'] ?? '';
    $trainerId = $data['trainer_id'] ?? null;
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $price = $data['price'] ?? 0;
    $capacity = $data['capacity'] ?? null;
    
    $stmt = $conn->prepare("
        INSERT INTO courses (course_name, description, trainer_id, start_date, end_date, price, capacity, status, created_by, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW())
    ");
    $stmt->bind_param("ssissdii", $courseName, $description, $trainerId, $startDate, $endDate, $price, $capacity, $userId);
    $success = $stmt->execute();
    
    if ($success) {
        logActivity($conn, $userId, 'course_created', "تم إنشاء دورة: $courseName", ['course_id' => $conn->insert_id]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إنشاء الدورة بنجاح' : 'فشل في إنشاء الدورة',
        'course_id' => $success ? $conn->insert_id : null
    ];
}

function updateCourse($conn, $data) {
    $courseId = $data['course_id'] ?? 0;
    $courseName = $data['course_name'] ?? '';
    $description = $data['description'] ?? '';
    $trainerId = $data['trainer_id'] ?? null;
    $startDate = $data['start_date'] ?? null;
    $endDate = $data['end_date'] ?? null;
    $price = $data['price'] ?? 0;
    $capacity = $data['capacity'] ?? null;
    $status = $data['status'] ?? 'active';
    
    $stmt = $conn->prepare("
        UPDATE courses 
        SET course_name = ?, description = ?, trainer_id = ?, start_date = ?, end_date = ?, 
            price = ?, capacity = ?, status = ?
        WHERE course_id = ?
    ");
    $stmt->bind_param("ssissdisi", $courseName, $description, $trainerId, $startDate, $endDate, $price, $capacity, $status, $courseId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تحديث الدورة بنجاح' : 'فشل في تحديث الدورة'
    ];
}

function deleteCourse($conn, $courseId) {
    // Check if course has enrollments
    $check = $conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE course_id = ?");
    $check->bind_param("i", $courseId);
    $check->execute();
    $result = $check->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        return [
            'success' => false,
            'message' => 'لا يمكن حذف الدورة لوجود طلاب مسجلين'
        ];
    }
    
    $stmt = $conn->prepare("DELETE FROM courses WHERE course_id = ?");
    $stmt->bind_param("i", $courseId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم حذف الدورة بنجاح' : 'فشل في حذف الدورة'
    ];
}

function approveCourse($conn, $userId, $courseId) {
    $stmt = $conn->prepare("UPDATE courses SET status = 'active', approved_by = ?, approved_at = NOW() WHERE course_id = ?");
    $stmt->bind_param("ii", $userId, $courseId);
    $success = $stmt->execute();
    
    if ($success) {
        // Notify trainer
        $courseInfo = $conn->prepare("SELECT course_name, trainer_id FROM courses WHERE course_id = ?");
        $courseInfo->bind_param("i", $courseId);
        $courseInfo->execute();
        $course = $courseInfo->get_result()->fetch_assoc();
        
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, created_at)
            VALUES (?, 'تمت الموافقة على الدورة', ?, 'success', '?page=courses&course_id=?', NOW())
        ");
        $message = "تمت الموافقة على الدورة: " . $course['course_name'];
        $notifStmt->bind_param("iss", $course['trainer_id'], $message, $courseId);
        $notifStmt->execute();
        
        logActivity($conn, $userId, 'course_approved', "تمت الموافقة على دورة: " . $course['course_name'], ['course_id' => $courseId]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تمت الموافقة على الدورة بنجاح' : 'فشل في الموافقة'
    ];
}

// ======================================
// STUDENTS FUNCTIONS
// ======================================

function getAllStudents($conn) {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT e.course_id) as enrolled_courses,
            AVG(e.final_grade) as avg_grade,
            SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_courses
        FROM users u
        LEFT JOIN enrollments e ON u.user_id = e.user_id
        WHERE u.role = 'student'
        GROUP BY u.user_id
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getStudentDetails($conn, $studentId) {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT e.course_id) as enrolled_courses,
            AVG(e.final_grade) as gpa,
            (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.user_id AND a.status = 'present') as total_attendance
        FROM users u
        LEFT JOIN enrollments e ON u.user_id = e.user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->bind_param("i", $studentId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function updateStudent($conn, $data) {
    $userId = $data['user_id'] ?? 0;
    $fullName = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $address = $data['address'] ?? '';
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name = ?, email = ?, phone = ?, address = ?
        WHERE user_id = ? AND role = 'student'
    ");
    $stmt->bind_param("ssssi", $fullName, $email, $phone, $address, $userId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تحديث بيانات الطالب بنجاح' : 'فشل في التحديث'
    ];
}

function suspendStudent($conn, $userId, $studentId) {
    $stmt = $conn->prepare("UPDATE users SET status = 'suspended' WHERE user_id = ? AND role = 'student'");
    $stmt->bind_param("i", $studentId);
    $success = $stmt->execute();
    
    if ($success) {
        logActivity($conn, $userId, 'student_suspended', "تم إيقاف طالب", ['student_id' => $studentId]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إيقاف الطالب بنجاح' : 'فشل في الإيقاف'
    ];
}

function activateStudent($conn, $userId, $studentId) {
    $stmt = $conn->prepare("UPDATE users SET status = 'active' WHERE user_id = ? AND role = 'student'");
    $stmt->bind_param("i", $studentId);
    $success = $stmt->execute();
    
    if ($success) {
        logActivity($conn, $userId, 'student_activated', "تم تفعيل طالب", ['student_id' => $studentId]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تفعيل الطالب بنجاح' : 'فشل في التفعيل'
    ];
}

// ======================================
// TRAINERS FUNCTIONS
// ======================================

function getAllTrainers($conn) {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT c.course_id) as courses_count,
            COUNT(DISTINCT e.user_id) as students_count,
            AVG(ev.rating) as avg_rating
        FROM users u
        LEFT JOIN courses c ON u.user_id = c.trainer_id
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN trainer_evaluations ev ON u.user_id = ev.trainer_id
        WHERE u.role = 'trainer'
        GROUP BY u.user_id
        ORDER BY u.full_name
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getTrainerDetails($conn, $trainerId) {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT c.course_id) as total_courses,
            COUNT(DISTINCT e.user_id) as total_students,
            AVG(ev.rating) as avg_rating,
            COUNT(DISTINCT ev.evaluation_id) as total_evaluations
        FROM users u
        LEFT JOIN courses c ON u.user_id = c.trainer_id
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN trainer_evaluations ev ON u.user_id = ev.trainer_id
        WHERE u.user_id = ? AND u.role = 'trainer'
        GROUP BY u.user_id
    ");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function createTrainer($conn, $userId, $data) {
    $username = $data['username'] ?? '';
    $password = password_hash($data['password'] ?? '', PASSWORD_DEFAULT);
    $fullName = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, full_name, email, phone, role, status, created_at)
        VALUES (?, ?, ?, ?, ?, 'trainer', 'active', NOW())
    ");
    $stmt->bind_param("sssss", $username, $password, $fullName, $email, $phone);
    $success = $stmt->execute();
    
    if ($success) {
        logActivity($conn, $userId, 'trainer_created', "تم إنشاء مدرب: $fullName", ['trainer_id' => $conn->insert_id]);
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إنشاء المدرب بنجاح' : 'فشل في إنشاء المدرب',
        'trainer_id' => $success ? $conn->insert_id : null
    ];
}

function updateTrainer($conn, $data) {
    $userId = $data['user_id'] ?? 0;
    $fullName = $data['full_name'] ?? '';
    $email = $data['email'] ?? '';
    $phone = $data['phone'] ?? '';
    $specialization = $data['specialization'] ?? '';
    
    $stmt = $conn->prepare("
        UPDATE users 
        SET full_name = ?, email = ?, phone = ?, specialization = ?
        WHERE user_id = ? AND role = 'trainer'
    ");
    $stmt->bind_param("ssssi", $fullName, $email, $phone, $specialization, $userId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تحديث بيانات المدرب بنجاح' : 'فشل في التحديث'
    ];
}

function getTrainerEvaluations($conn, $trainerId) {
    $stmt = $conn->prepare("
        SELECT 
            te.*,
            u.full_name as evaluator_name,
            c.course_name
        FROM trainer_evaluations te
        LEFT JOIN users u ON te.evaluated_by = u.user_id
        LEFT JOIN courses c ON te.course_id = c.course_id
        WHERE te.trainer_id = ?
        ORDER BY te.evaluation_date DESC
    ");
    $stmt->bind_param("i", $trainerId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function submitTrainerEvaluation($conn, $userId, $data) {
    $trainerId = $data['trainer_id'] ?? 0;
    $courseId = $data['course_id'] ?? null;
    $rating = $data['rating'] ?? 0;
    $comments = $data['comments'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO trainer_evaluations (trainer_id, course_id, rating, comments, evaluated_by, evaluation_date)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("iidsi", $trainerId, $courseId, $rating, $comments, $userId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تقديم التقييم بنجاح' : 'فشل في تقديم التقييم'
    ];
}

// ======================================
// HELPER FUNCTIONS
// ======================================

function logActivity($conn, $userId, $activityType, $description, $metadata = []) {
    $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
    $stmt = $conn->prepare("
        INSERT INTO activity_log (user_id, activity_type, description, metadata, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isss", $userId, $activityType, $description, $metadataJson);
    $stmt->execute();
}

function getStatistics($conn) {
    $stats = [];
    
    // Total courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses");
    $stats['total_courses'] = $result->fetch_assoc()['count'];
    
    // Active courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $stats['active_courses'] = $result->fetch_assoc()['count'];
    
    // Pending courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'pending'");
    $stats['pending_courses'] = $result->fetch_assoc()['count'];
    
    // Total trainers
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
    $stats['total_trainers'] = $result->fetch_assoc()['count'];
    
    // Support tickets
    $result = $conn->query("SELECT COUNT(*) as count FROM support_tickets WHERE status IN ('open', 'pending')");
    $stats['support_tickets'] = $result->fetch_assoc()['count'];
    
    // Pending reviews
    $result = $conn->query("SELECT COUNT(*) as count FROM course_reviews WHERE status = 'pending'");
    $stats['pending_reviews'] = $result->fetch_assoc()['count'];
    
    return [
        'success' => true,
        'data' => $stats
    ];
}

// Additional functions for materials, requests, ID cards, certificates, support, etc.
// Similar implementation patterns...

function getAllMaterials($conn, $courseId = null) {
    if ($courseId) {
        $stmt = $conn->prepare("
            SELECT m.*, c.course_name, u.full_name as uploader_name
            FROM course_materials m
            JOIN courses c ON m.course_id = c.course_id
            LEFT JOIN users u ON m.uploaded_by = u.user_id
            WHERE m.course_id = ?
            ORDER BY m.upload_date DESC
        ");
        $stmt->bind_param("i", $courseId);
    } else {
        $stmt = $conn->prepare("
            SELECT m.*, c.course_name, u.full_name as uploader_name
            FROM course_materials m
            JOIN courses c ON m.course_id = c.course_id
            LEFT JOIN users u ON m.uploaded_by = u.user_id
            ORDER BY m.upload_date DESC
        ");
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getSupportTickets($conn, $status = 'open') {
    $stmt = $conn->prepare("
        SELECT 
            st.*,
            u.full_name as user_name,
            u.email as user_email
        FROM support_tickets st
        JOIN users u ON st.user_id = u.user_id
        WHERE st.status = ?
        ORDER BY st.created_at DESC
    ");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getAllRequests($conn, $status = null) {
    if ($status) {
        $stmt = $conn->prepare("
            SELECT r.*, u.full_name, u.email
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            WHERE r.status = ?
            ORDER BY r.created_at DESC
        ");
        $stmt->bind_param("s", $status);
    } else {
        $stmt = $conn->prepare("
            SELECT r.*, u.full_name, u.email
            FROM requests r
            JOIN users u ON r.user_id = u.user_id
            ORDER BY r.created_at DESC
        ");
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getIdCardRequests($conn) {
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.photo,
            u.id_number,
            u.created_at
        FROM users u
        WHERE u.role = 'student' AND (u.id_card_issued IS NULL OR u.id_card_issued = 0)
        ORDER BY u.created_at DESC
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getAllCertificates($conn) {
    $stmt = $conn->prepare("
        SELECT 
            cert.*,
            u.full_name as student_name,
            c.course_name,
            t.full_name as trainer_name
        FROM certificates cert
        JOIN users u ON cert.student_id = u.user_id
        JOIN courses c ON cert.course_id = c.course_id
        LEFT JOIN users t ON c.trainer_id = t.user_id
        ORDER BY cert.issue_date DESC
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getAllAnnouncements($conn) {
    $stmt = $conn->prepare("
        SELECT 
            a.*,
            u.full_name as author_name
        FROM announcements a
        LEFT JOIN users u ON a.created_by = u.user_id
        ORDER BY a.created_at DESC
        LIMIT 50
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getFinanceOverview($conn) {
    $stats = [];
    
    // Total revenue
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
    $stats['total_revenue'] = $result->fetch_assoc()['total'];
    
    // Pending payments
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'pending'");
    $stats['pending_payments'] = $result->fetch_assoc()['total'];
    
    // This month revenue
    $result = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(NOW()) AND YEAR(payment_date) = YEAR(NOW())");
    $stats['month_revenue'] = $result->fetch_assoc()['total'];
    
    return [
        'success' => true,
        'data' => $stats
    ];
}

function getPendingPayments($conn) {
    $stmt = $conn->prepare("
        SELECT 
            p.*,
            u.full_name,
            u.email,
            c.course_name
        FROM payments p
        JOIN users u ON p.user_id = u.user_id
        LEFT JOIN courses c ON p.course_id = c.course_id
        WHERE p.status = 'pending'
        ORDER BY p.payment_date DESC
    ");
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}
