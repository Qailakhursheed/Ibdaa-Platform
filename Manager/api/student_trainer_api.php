<?php
/**
 * Unified Student & Trainer API
 * API موحد للطالب والمدرب مع أمان عالي
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

// Verify role is student or trainer
if (!in_array($userRole, ['student', 'trainer'])) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'صلاحيات غير كافية'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Rate Limiting: 200 requests per minute
$rateLimiter = new RateLimiter($conn, 200, 1);
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
        // STUDENT APIs
        // =====================================
        case 'my_courses':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            echo json_encode(getMyCourses($conn, $userId));
            break;

        case 'course_details':
            $courseId = $_GET['course_id'] ?? 0;
            if ($userRole === 'student') {
                echo json_encode(getStudentCourseDetails($conn, $userId, $courseId));
            } else {
                echo json_encode(getTrainerCourseDetails($conn, $userId, $courseId));
            }
            break;

        case 'my_grades':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            $courseId = $_GET['course_id'] ?? null;
            echo json_encode(getMyGrades($conn, $userId, $courseId));
            break;

        case 'my_attendance':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            echo json_encode(getMyAttendance($conn, $userId));
            break;

        case 'my_assignments':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            $courseId = $_GET['course_id'] ?? null;
            echo json_encode(getMyAssignments($conn, $userId, $courseId));
            break;

        case 'submit_assignment':
            if ($userRole !== 'student' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(submitAssignment($conn, $userId, $data));
            break;

        case 'my_materials':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            $courseId = $_GET['course_id'] ?? null;
            echo json_encode(getMyMaterials($conn, $userId, $courseId));
            break;

        case 'my_schedule':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            echo json_encode(getMySchedule($conn, $userId));
            break;

        case 'my_payments':
            if ($userRole !== 'student') {
                throw new Exception('هذه الخدمة للطلاب فقط');
            }
            echo json_encode(getMyPayments($conn, $userId));
            break;

        // =====================================
        // TRAINER APIs
        // =====================================
        case 'trainer_courses':
            if ($userRole !== 'trainer') {
                throw new Exception('هذه الخدمة للمدربين فقط');
            }
            echo json_encode(getTrainerCourses($conn, $userId));
            break;

        case 'course_students':
            if ($userRole !== 'trainer') {
                throw new Exception('هذه الخدمة للمدربين فقط');
            }
            $courseId = $_GET['course_id'] ?? 0;
            echo json_encode(getCourseStudents($conn, $userId, $courseId));
            break;

        case 'mark_attendance':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(markAttendance($conn, $userId, $data));
            break;

        case 'enter_grades':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(enterGrades($conn, $userId, $data));
            break;

        case 'upload_material':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            echo json_encode(uploadMaterial($conn, $userId, $_POST, $_FILES));
            break;

        case 'create_assignment':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createAssignment($conn, $userId, $data));
            break;

        case 'grade_assignment':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(gradeAssignment($conn, $userId, $data));
            break;

        case 'student_profile':
            if ($userRole !== 'trainer') {
                throw new Exception('هذه الخدمة للمدربين فقط');
            }
            $studentId = $_GET['student_id'] ?? 0;
            echo json_encode(getStudentProfile($conn, $userId, $studentId));
            break;

        case 'trainer_stats':
            if ($userRole !== 'trainer') {
                throw new Exception('هذه الخدمة للمدربين فقط');
            }
            echo json_encode(getTrainerStats($conn, $userId));
            break;

        // =====================================
        // SHARED APIs (Student & Trainer)
        // =====================================
        case 'notifications':
            echo json_encode(getNotifications($conn, $userId));
            break;

        case 'mark_notification_read':
            if ($method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            $notifId = $_GET['id'] ?? 0;
            echo json_encode(markNotificationRead($conn, $userId, $notifId));
            break;

        case 'chat_messages':
            $recipientId = $_GET['recipient_id'] ?? null;
            echo json_encode(getChatMessages($conn, $userId, $recipientId));
            break;

        case 'send_message':
            if ($method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(sendMessage($conn, $userId, $data));
            break;

        case 'announcements':
            $courseId = $_GET['course_id'] ?? null;
            echo json_encode(getAnnouncements($conn, $userId, $userRole, $courseId));
            break;

        case 'create_announcement':
            if ($userRole !== 'trainer' || $method !== 'POST') {
                throw new Exception('طلب غير صالح');
            }
            CSRFProtection::validateToken();
            $data = json_decode(file_get_contents('php://input'), true);
            echo json_encode(createAnnouncement($conn, $userId, $data));
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
    
    // Log error
    error_log("Student/Trainer API Error: " . $e->getMessage());
}

// ======================================
// STUDENT FUNCTIONS
// ======================================

function getMyCourses($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            c.course_id,
            c.course_name,
            c.description,
            c.start_date,
            c.end_date,
            c.status as course_status,
            e.status as enrollment_status,
            e.progress,
            e.enrollment_date,
            CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
            u.photo as trainer_photo
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN users u ON c.trainer_id = u.user_id
        WHERE e.user_id = ?
        ORDER BY e.enrollment_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getStudentCourseDetails($conn, $userId, $courseId) {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            e.progress,
            e.status as enrollment_status,
            e.midterm_grade,
            e.final_grade,
            CONCAT(u.first_name, ' ', u.last_name) as trainer_name,
            u.email as trainer_email,
            u.photo as trainer_photo
        FROM courses c
        JOIN enrollments e ON c.course_id = e.course_id
        LEFT JOIN users u ON c.trainer_id = u.user_id
        WHERE c.course_id = ? AND e.user_id = ?
    ");
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function getMyGrades($conn, $userId, $courseId = null) {
    if ($courseId) {
        $stmt = $conn->prepare("
            SELECT 
                c.course_name,
                c.course_id,
                e.midterm_grade,
                e.final_grade,
                e.status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = ? AND c.course_id = ?
        ");
        $stmt->bind_param("ii", $userId, $courseId);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                c.course_name,
                c.course_id,
                e.midterm_grade,
                e.final_grade,
                e.status
            FROM enrollments e
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = ?
            ORDER BY e.enrollment_date DESC
        ");
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getMyAttendance($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            a.attendance_id,
            a.date,
            a.status,
            c.course_name,
            c.course_id
        FROM attendance a
        JOIN courses c ON a.course_id = c.course_id
        WHERE a.student_id = ?
        ORDER BY a.date DESC
        LIMIT 100
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getMyAssignments($conn, $userId, $courseId = null) {
    if ($courseId) {
        $stmt = $conn->prepare("
            SELECT 
                a.assignment_id,
                a.title,
                a.description,
                a.due_date,
                a.max_grade,
                asub.submission_id,
                asub.submitted_at,
                asub.grade,
                asub.graded,
                asub.feedback,
                c.course_name
            FROM assignments a
            LEFT JOIN assignment_submissions asub ON a.assignment_id = asub.assignment_id AND asub.student_id = ?
            JOIN courses c ON a.course_id = c.course_id
            WHERE a.course_id = ?
            ORDER BY a.due_date DESC
        ");
        $stmt->bind_param("ii", $userId, $courseId);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                a.assignment_id,
                a.title,
                a.description,
                a.due_date,
                a.max_grade,
                asub.submission_id,
                asub.submitted_at,
                asub.grade,
                asub.graded,
                asub.feedback,
                c.course_name
            FROM assignments a
            LEFT JOIN assignment_submissions asub ON a.assignment_id = asub.assignment_id AND asub.student_id = ?
            JOIN courses c ON a.course_id = c.course_id
            JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
            ORDER BY a.due_date DESC
            LIMIT 50
        ");
        $stmt->bind_param("ii", $userId, $userId);
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function submitAssignment($conn, $userId, $data) {
    $assignmentId = $data['assignment_id'] ?? 0;
    $content = $data['content'] ?? '';
    $fileUrl = $data['file_url'] ?? null;
    
    // Check if already submitted
    $check = $conn->prepare("SELECT submission_id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
    $check->bind_param("ii", $assignmentId, $userId);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update existing submission
        $stmt = $conn->prepare("
            UPDATE assignment_submissions 
            SET content = ?, file_url = ?, submitted_at = NOW(), graded = 0
            WHERE submission_id = ?
        ");
        $stmt->bind_param("ssi", $content, $fileUrl, $existing['submission_id']);
    } else {
        // Create new submission
        $stmt = $conn->prepare("
            INSERT INTO assignment_submissions (assignment_id, student_id, content, file_url, submitted_at)
            VALUES (?, ?, ?, ?, NOW())
        ");
        $stmt->bind_param("iiss", $assignmentId, $userId, $content, $fileUrl);
    }
    
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إرسال الواجب بنجاح' : 'فشل في إرسال الواجب'
    ];
}

function getMyMaterials($conn, $userId, $courseId = null) {
    if ($courseId) {
        $stmt = $conn->prepare("
            SELECT 
                m.material_id,
                m.title,
                m.description,
                m.file_url,
                m.file_type,
                m.uploaded_at,
                c.course_name
            FROM materials m
            JOIN courses c ON m.course_id = c.course_id
            WHERE m.course_id = ?
            ORDER BY m.uploaded_at DESC
        ");
        $stmt->bind_param("i", $courseId);
    } else {
        $stmt = $conn->prepare("
            SELECT 
                m.material_id,
                m.title,
                m.description,
                m.file_url,
                m.file_type,
                m.uploaded_at,
                c.course_name
            FROM materials m
            JOIN courses c ON m.course_id = c.course_id
            JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
            ORDER BY m.uploaded_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $userId);
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getMySchedule($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            s.schedule_id,
            s.day_of_week,
            s.start_time,
            s.end_time,
            s.room,
            c.course_name,
            c.course_id
        FROM schedules s
        JOIN courses c ON s.course_id = c.course_id
        JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
        WHERE e.status = 'active'
        ORDER BY s.day_of_week, s.start_time
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getMyPayments($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            p.payment_id,
            p.amount,
            p.payment_date,
            p.payment_method,
            p.status,
            p.description,
            c.course_name
        FROM payments p
        LEFT JOIN courses c ON p.course_id = c.course_id
        WHERE p.user_id = ?
        ORDER BY p.payment_date DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    // Get balance
    $balanceStmt = $conn->prepare("
        SELECT 
            COALESCE(SUM(CASE WHEN type = 'debit' THEN amount ELSE -amount END), 0) as balance
        FROM financial_transactions
        WHERE user_id = ?
    ");
    $balanceStmt->bind_param("i", $userId);
    $balanceStmt->execute();
    $balanceData = $balanceStmt->get_result()->fetch_assoc();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC),
        'balance' => $balanceData['balance']
    ];
}

// ======================================
// TRAINER FUNCTIONS
// ======================================

function getTrainerCourses($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            c.course_id,
            c.course_name,
            c.description,
            c.start_date,
            c.end_date,
            c.status,
            COUNT(DISTINCT e.user_id) as students_count,
            AVG(e.progress) as avg_progress
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
        WHERE c.trainer_id = ?
        GROUP BY c.course_id
        ORDER BY c.start_date DESC
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function getTrainerCourseDetails($conn, $userId, $courseId) {
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            COUNT(DISTINCT e.user_id) as total_students,
            COUNT(DISTINCT CASE WHEN e.status = 'active' THEN e.user_id END) as active_students,
            AVG(e.final_grade) as avg_grade,
            AVG(e.progress) as avg_progress
        FROM courses c
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        WHERE c.course_id = ? AND c.trainer_id = ?
        GROUP BY c.course_id
    ");
    $stmt->bind_param("ii", $courseId, $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function getCourseStudents($conn, $userId, $courseId) {
    // Verify trainer owns this course
    $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
    $verify->bind_param("ii", $courseId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية للوصول لهذه الدورة');
    }
    
    $stmt = $conn->prepare("
        SELECT 
            u.user_id,
            u.full_name,
            u.email,
            u.photo,
            e.enrollment_date,
            e.progress,
            e.midterm_grade,
            e.final_grade,
            e.status
        FROM enrollments e
        JOIN users u ON e.user_id = u.user_id
        WHERE e.course_id = ?
        ORDER BY u.full_name
    ");
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function markAttendance($conn, $userId, $data) {
    $courseId = $data['course_id'] ?? 0;
    $studentId = $data['student_id'] ?? 0;
    $date = $data['date'] ?? date('Y-m-d');
    $status = $data['status'] ?? 'present'; // present, absent, late
    
    // Verify trainer owns this course
    $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
    $verify->bind_param("ii", $courseId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية للوصول لهذه الدورة');
    }
    
    // Check if attendance already exists
    $check = $conn->prepare("SELECT attendance_id FROM attendance WHERE course_id = ? AND student_id = ? AND date = ?");
    $check->bind_param("iis", $courseId, $studentId, $date);
    $check->execute();
    $existing = $check->get_result()->fetch_assoc();
    
    if ($existing) {
        // Update
        $stmt = $conn->prepare("UPDATE attendance SET status = ? WHERE attendance_id = ?");
        $stmt->bind_param("si", $status, $existing['attendance_id']);
    } else {
        // Insert
        $stmt = $conn->prepare("INSERT INTO attendance (course_id, student_id, date, status) VALUES (?, ?, ?, ?)");
        $stmt->bind_param("iiss", $courseId, $studentId, $date, $status);
    }
    
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تسجيل الحضور بنجاح' : 'فشل في تسجيل الحضور'
    ];
}

function enterGrades($conn, $userId, $data) {
    $enrollmentId = $data['enrollment_id'] ?? 0;
    $midtermGrade = $data['midterm_grade'] ?? null;
    $finalGrade = $data['final_grade'] ?? null;
    
    // Verify trainer owns this course
    $verify = $conn->prepare("
        SELECT e.enrollment_id 
        FROM enrollments e
        JOIN courses c ON e.course_id = c.course_id
        WHERE e.enrollment_id = ? AND c.trainer_id = ?
    ");
    $verify->bind_param("ii", $enrollmentId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية لإدخال درجات هذا الطالب');
    }
    
    $stmt = $conn->prepare("
        UPDATE enrollments 
        SET midterm_grade = COALESCE(?, midterm_grade),
            final_grade = COALESCE(?, final_grade)
        WHERE enrollment_id = ?
    ");
    $stmt->bind_param("ddi", $midtermGrade, $finalGrade, $enrollmentId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إدخال الدرجات بنجاح' : 'فشل في إدخال الدرجات'
    ];
}

function uploadMaterial($conn, $userId, $postData, $files) {
    $courseId = $postData['course_id'] ?? 0;
    $title = $postData['title'] ?? '';
    $description = $postData['description'] ?? '';
    
    // Verify trainer owns this course
    $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
    $verify->bind_param("ii", $courseId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية لرفع مواد لهذه الدورة');
    }
    
    // Handle file upload
    if (!isset($files['file']) || $files['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('فشل في رفع الملف');
    }
    
    $uploadDir = __DIR__ . '/../../uploads/materials/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    
    $fileName = uniqid() . '_' . basename($files['file']['name']);
    $filePath = $uploadDir . $fileName;
    $fileUrl = '/uploads/materials/' . $fileName;
    $fileType = $files['file']['type'];
    
    if (!move_uploaded_file($files['file']['tmp_name'], $filePath)) {
        throw new Exception('فشل في حفظ الملف');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO materials (course_id, title, description, file_url, file_type, uploaded_by, uploaded_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("issssi", $courseId, $title, $description, $fileUrl, $fileType, $userId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم رفع المادة بنجاح' : 'فشل في رفع المادة',
        'file_url' => $fileUrl
    ];
}

function createAssignment($conn, $userId, $data) {
    $courseId = $data['course_id'] ?? 0;
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $dueDate = $data['due_date'] ?? null;
    $maxGrade = $data['max_grade'] ?? 100;
    
    // Verify trainer owns this course
    $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
    $verify->bind_param("ii", $courseId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية لإنشاء واجبات لهذه الدورة');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO assignments (course_id, title, description, due_date, max_grade, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("isssd", $courseId, $title, $description, $dueDate, $maxGrade);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إنشاء الواجب بنجاح' : 'فشل في إنشاء الواجب',
        'assignment_id' => $success ? $conn->insert_id : null
    ];
}

function gradeAssignment($conn, $userId, $data) {
    $submissionId = $data['submission_id'] ?? 0;
    $grade = $data['grade'] ?? 0;
    $feedback = $data['feedback'] ?? '';
    
    // Verify trainer owns this course
    $verify = $conn->prepare("
        SELECT asub.submission_id
        FROM assignment_submissions asub
        JOIN assignments a ON asub.assignment_id = a.assignment_id
        JOIN courses c ON a.course_id = c.course_id
        WHERE asub.submission_id = ? AND c.trainer_id = ?
    ");
    $verify->bind_param("ii", $submissionId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية لتقييم هذا الواجب');
    }
    
    $stmt = $conn->prepare("
        UPDATE assignment_submissions 
        SET grade = ?, feedback = ?, graded = 1, graded_at = NOW()
        WHERE submission_id = ?
    ");
    $stmt->bind_param("dsi", $grade, $feedback, $submissionId);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم تقييم الواجب بنجاح' : 'فشل في تقييم الواجب'
    ];
}

function getStudentProfile($conn, $userId, $studentId) {
    $stmt = $conn->prepare("
        SELECT 
            u.*,
            COUNT(DISTINCT e.course_id) as enrolled_courses,
            AVG(e.final_grade) as gpa,
            (SELECT COUNT(*) FROM attendance a 
             JOIN courses c ON a.course_id = c.course_id 
             WHERE a.student_id = u.user_id AND c.trainer_id = ? AND a.status = 'present') as present_count,
            (SELECT COUNT(*) FROM attendance a 
             JOIN courses c ON a.course_id = c.course_id 
             WHERE a.student_id = u.user_id AND c.trainer_id = ?) as total_attendance
        FROM users u
        LEFT JOIN enrollments e ON u.user_id = e.user_id
        WHERE u.user_id = ?
        GROUP BY u.user_id
    ");
    $stmt->bind_param("iii", $userId, $userId, $studentId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

function getTrainerStats($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            (SELECT COUNT(*) FROM courses WHERE trainer_id = ?) as total_courses,
            (SELECT COUNT(DISTINCT e.user_id) FROM enrollments e JOIN courses c ON e.course_id = c.course_id WHERE c.trainer_id = ? AND e.status = 'active') as active_students,
            (SELECT COUNT(*) FROM materials m JOIN courses c ON m.course_id = c.course_id WHERE c.trainer_id = ?) as total_materials,
            (SELECT ROUND(AVG(CASE WHEN a.status = 'present' THEN 100 ELSE 0 END), 1) FROM attendance a JOIN courses c ON a.course_id = c.course_id WHERE c.trainer_id = ?) as avg_attendance,
            (SELECT COUNT(*) FROM assignments a JOIN courses c ON a.course_id = c.course_id WHERE c.trainer_id = ?) as total_assignments,
            (SELECT COUNT(*) FROM assignment_submissions asub JOIN assignments a ON asub.assignment_id = a.assignment_id JOIN courses c ON a.course_id = c.course_id WHERE c.trainer_id = ? AND asub.graded = 0) as pending_grades
    ");
    $stmt->bind_param("iiiiii", $userId, $userId, $userId, $userId, $userId, $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_assoc()
    ];
}

// ======================================
// SHARED FUNCTIONS
// ======================================

function getNotifications($conn, $userId) {
    $stmt = $conn->prepare("
        SELECT 
            notification_id,
            title,
            message,
            type,
            link,
            is_read,
            created_at
        FROM notifications
        WHERE user_id = ?
        ORDER BY created_at DESC
        LIMIT 50
    ");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function markNotificationRead($conn, $userId, $notifId) {
    $stmt = $conn->prepare("UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?");
    $stmt->bind_param("ii", $notifId, $userId);
    $success = $stmt->execute();
    
    return [
        'success' => $success
    ];
}

function getChatMessages($conn, $userId, $recipientId = null) {
    if ($recipientId) {
        $stmt = $conn->prepare("
            SELECT 
                cm.message_id,
                cm.sender_id,
                cm.receiver_id,
                cm.message,
                cm.is_read,
                cm.created_at,
                u.full_name as sender_name,
                u.photo as sender_photo
            FROM chat_messages cm
            JOIN users u ON cm.sender_id = u.user_id
            WHERE (cm.sender_id = ? AND cm.receiver_id = ?) OR (cm.sender_id = ? AND cm.receiver_id = ?)
            ORDER BY cm.created_at ASC
            LIMIT 100
        ");
        $stmt->bind_param("iiii", $userId, $recipientId, $recipientId, $userId);
    } else {
        // Get recent conversations
        $stmt = $conn->prepare("
            SELECT DISTINCT
                CASE WHEN cm.sender_id = ? THEN cm.receiver_id ELSE cm.sender_id END as user_id,
                u.full_name,
                u.photo,
                MAX(cm.created_at) as last_message_time
            FROM chat_messages cm
            JOIN users u ON (CASE WHEN cm.sender_id = ? THEN cm.receiver_id ELSE cm.sender_id END) = u.user_id
            WHERE cm.sender_id = ? OR cm.receiver_id = ?
            GROUP BY user_id
            ORDER BY last_message_time DESC
            LIMIT 20
        ");
        $stmt->bind_param("iiii", $userId, $userId, $userId, $userId);
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function sendMessage($conn, $userId, $data) {
    $receiverId = $data['receiver_id'] ?? 0;
    $message = $data['message'] ?? '';
    
    $stmt = $conn->prepare("
        INSERT INTO chat_messages (sender_id, receiver_id, message, created_at)
        VALUES (?, ?, ?, NOW())
    ");
    $stmt->bind_param("iis", $userId, $receiverId, $message);
    $success = $stmt->execute();
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إرسال الرسالة بنجاح' : 'فشل في إرسال الرسالة',
        'message_id' => $success ? $conn->insert_id : null
    ];
}

function getAnnouncements($conn, $userId, $userRole, $courseId = null) {
    if ($courseId) {
        // Specific course announcements
        if ($userRole === 'student') {
            // Verify student is enrolled
            $verify = $conn->prepare("SELECT enrollment_id FROM enrollments WHERE course_id = ? AND user_id = ?");
            $verify->bind_param("ii", $courseId, $userId);
            $verify->execute();
            if (!$verify->get_result()->fetch_assoc()) {
                throw new Exception('ليس لديك صلاحية لعرض إعلانات هذه الدورة');
            }
        } elseif ($userRole === 'trainer') {
            // Verify trainer owns course
            $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
            $verify->bind_param("ii", $courseId, $userId);
            $verify->execute();
            if (!$verify->get_result()->fetch_assoc()) {
                throw new Exception('ليس لديك صلاحية لعرض إعلانات هذه الدورة');
            }
        }
        
        $stmt = $conn->prepare("
            SELECT 
                an.announcement_id,
                an.title,
                an.content,
                an.created_at,
                u.full_name as author_name
            FROM announcements an
            JOIN users u ON an.created_by = u.user_id
            WHERE an.course_id = ?
            ORDER BY an.created_at DESC
            LIMIT 50
        ");
        $stmt->bind_param("i", $courseId);
    } else {
        // All announcements for user's courses
        if ($userRole === 'student') {
            $stmt = $conn->prepare("
                SELECT 
                    an.announcement_id,
                    an.title,
                    an.content,
                    an.created_at,
                    c.course_name,
                    u.full_name as author_name
                FROM announcements an
                JOIN courses c ON an.course_id = c.course_id
                JOIN enrollments e ON c.course_id = e.course_id AND e.user_id = ?
                JOIN users u ON an.created_by = u.user_id
                ORDER BY an.created_at DESC
                LIMIT 50
            ");
            $stmt->bind_param("i", $userId);
        } else {
            $stmt = $conn->prepare("
                SELECT 
                    an.announcement_id,
                    an.title,
                    an.content,
                    an.created_at,
                    c.course_name,
                    u.full_name as author_name
                FROM announcements an
                JOIN courses c ON an.course_id = c.course_id
                JOIN users u ON an.created_by = u.user_id
                WHERE c.trainer_id = ?
                ORDER BY an.created_at DESC
                LIMIT 50
            ");
            $stmt->bind_param("i", $userId);
        }
    }
    
    $stmt->execute();
    return [
        'success' => true,
        'data' => $stmt->get_result()->fetch_all(MYSQLI_ASSOC)
    ];
}

function createAnnouncement($conn, $userId, $data) {
    $courseId = $data['course_id'] ?? 0;
    $title = $data['title'] ?? '';
    $content = $data['content'] ?? '';
    
    // Verify trainer owns this course
    $verify = $conn->prepare("SELECT course_id FROM courses WHERE course_id = ? AND trainer_id = ?");
    $verify->bind_param("ii", $courseId, $userId);
    $verify->execute();
    if (!$verify->get_result()->fetch_assoc()) {
        throw new Exception('ليس لديك صلاحية لإنشاء إعلانات لهذه الدورة');
    }
    
    $stmt = $conn->prepare("
        INSERT INTO announcements (course_id, title, content, created_by, created_at)
        VALUES (?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param("issi", $courseId, $title, $content, $userId);
    $success = $stmt->execute();
    
    // Create notifications for all enrolled students
    if ($success) {
        $notifStmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, created_at)
            SELECT 
                e.user_id,
                ?,
                CONCAT('إعلان جديد: ', ?),
                'info',
                CONCAT('?page=announcements&course_id=', ?),
                NOW()
            FROM enrollments e
            WHERE e.course_id = ? AND e.status = 'active'
        ");
        $notifStmt->bind_param("ssii", $title, $title, $courseId, $courseId);
        $notifStmt->execute();
    }
    
    return [
        'success' => $success,
        'message' => $success ? 'تم إنشاء الإعلان بنجاح' : 'فشل في إنشاء الإعلان',
        'announcement_id' => $success ? $conn->insert_id : null
    ];
}
