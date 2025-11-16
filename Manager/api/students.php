<?php
/**
 * Students Management API
 * نظام إدارة الطلاب (المتدربين) - API
 * 
 * التكامل الكامل بين جميع الأدوار:
 * - المدير العام (manager): صلاحيات كاملة
 * - المشرف الفني (technical): صلاحيات كاملة ماعدا الحذف النهائي
 * - المدرب (trainer): عرض طلابه فقط
 * - الطالب (student): عرض وتعديل بياناته فقط
 */

session_start();
require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Check authentication
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];
$action = $_GET['action'] ?? '';

// Role-based permissions
$permissions = [
    'manager' => ['list', 'get', 'create', 'update', 'delete', 'import', 'export', 'approve'],
    'technical' => ['list', 'get', 'create', 'update', 'import', 'export', 'approve'],
    'trainer' => ['list', 'get'], // Can only view their students
    'student' => ['get', 'update'] // Can only view/edit own profile
];

// Check permission
if (!isset($permissions[$userRole]) || !in_array($action, $permissions[$userRole])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء'], JSON_UNESCAPED_UNICODE);
    exit;
}

// Handle actions
try {
    switch ($action) {
        case 'list':
            listStudents($conn, $userId, $userRole);
            break;
        case 'get':
            getStudent($conn, $userId, $userRole);
            break;
        case 'create':
            createStudent($conn, $userId, $userRole);
            break;
        case 'update':
            updateStudent($conn, $userId, $userRole);
            break;
        case 'delete':
            deleteStudent($conn, $userId, $userRole);
            break;
        case 'import':
            importStudents($conn, $userId, $userRole);
            break;
        case 'export':
            exportStudents($conn, $userId, $userRole);
            break;
        case 'approve':
            approveStudent($conn, $userId, $userRole);
            break;
        case 'download_template':
            downloadTemplate();
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
 * List students with filters
 */
function listStudents($conn, $userId, $userRole) {
    $search = $_GET['search'] ?? '';
    $status = $_GET['status'] ?? 'all';
    $course = $_GET['course'] ?? 'all';
    $payment = $_GET['payment'] ?? 'all';
    $page = intval($_GET['page'] ?? 1);
    $limit = 20;
    $offset = ($page - 1) * $limit;
    
    // Build query based on role
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // For trainers: only show their students
    if ($userRole === 'trainer') {
        $whereConditions[] = "e.course_id IN (SELECT course_id FROM courses WHERE trainer_id = ?)";
        $params[] = $userId;
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
    
    // Status filter
    if ($status !== 'all') {
        $whereConditions[] = "u.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Course filter
    if ($course !== 'all') {
        $whereConditions[] = "e.course_id = ?";
        $params[] = intval($course);
        $types .= 'i';
    }
    
    // Payment filter
    if ($payment !== 'all') {
        $whereConditions[] = "u.payment_status = ?";
        $params[] = $payment;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    // Main query
    $sql = "SELECT 
                u.id,
                u.full_name,
                u.email,
                u.phone,
                u.photo,
                u.status,
                u.payment_status,
                u.created_at,
                u.id_card_number,
                c.title as course_name,
                c.course_id
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.role = 'student' AND $whereClause
            ORDER BY u.created_at DESC
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $types .= 'ii';
        $params[] = $limit;
        $params[] = $offset;
        $stmt->bind_param($types, ...$params);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $students = [];
    
    while ($row = $result->fetch_assoc()) {
        $students[] = $row;
    }
    
    // Get statistics
    $stats = getStatistics($conn, $userRole, $userId);
    
    // Get total count
    $countSql = "SELECT COUNT(DISTINCT u.id) as total 
                 FROM users u
                 LEFT JOIN enrollments e ON u.id = e.user_id
                 WHERE u.role = 'student' AND $whereClause";
    
    $countStmt = $conn->prepare($countSql);
    if (!empty($params)) {
        // Remove limit and offset from params
        array_pop($params);
        array_pop($params);
        $countTypes = substr($types, 0, -2);
        if (!empty($countTypes)) {
            $countStmt->bind_param($countTypes, ...$params);
        }
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    echo json_encode([
        'success' => true,
        'data' => $students,
        'statistics' => $stats,
        'pagination' => [
            'current_page' => $page,
            'total' => $total,
            'per_page' => $limit,
            'from' => $offset + 1,
            'to' => min($offset + $limit, $total)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get statistics
 */
function getStatistics($conn, $userRole, $userId) {
    $whereClause = $userRole === 'trainer' 
        ? "AND e.course_id IN (SELECT course_id FROM courses WHERE trainer_id = $userId)" 
        : "";
    
    $stats = [
        'total' => 0,
        'active' => 0,
        'pending' => 0,
        'paid' => 0,
        'new_this_month' => 0
    ];
    
    // Total students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
    $stats['total'] = $result->fetch_assoc()['count'];
    
    // Active students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'active'");
    $stats['active'] = $result->fetch_assoc()['count'];
    
    // Pending students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND status = 'pending'");
    $stats['pending'] = $result->fetch_assoc()['count'];
    
    // Paid students
    $result = $conn->query("SELECT COUNT(*) as count FROM users WHERE role = 'student' AND payment_status = 'paid'");
    $stats['paid'] = $result->fetch_assoc()['count'];
    
    // New this month
    $result = $conn->query("SELECT COUNT(*) as count FROM users 
                           WHERE role = 'student' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE()) 
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['new_this_month'] = $result->fetch_assoc()['count'];
    
    return $stats;
}

/**
 * Get single student
 */
function getStudent($conn, $userId, $userRole) {
    $studentId = intval($_GET['id'] ?? 0);
    
    if ($studentId === 0) {
        throw new Exception('معرف الطالب مطلوب');
    }
    
    // For students: can only view their own profile
    if ($userRole === 'student' && $studentId !== $userId) {
        throw new Exception('ليس لديك صلاحية لعرض بيانات هذا الطالب');
    }
    
    // For trainers: check if student is enrolled in their courses
    if ($userRole === 'trainer') {
        $checkStmt = $conn->prepare("SELECT COUNT(*) as count 
                                     FROM enrollments e
                                     JOIN courses c ON e.course_id = c.course_id
                                     WHERE e.user_id = ? AND c.trainer_id = ?");
        $checkStmt->bind_param('ii', $studentId, $userId);
        $checkStmt->execute();
        if ($checkStmt->get_result()->fetch_assoc()['count'] == 0) {
            throw new Exception('هذا الطالب غير مسجل في دوراتك');
        }
    }
    
    $stmt = $conn->prepare("SELECT 
                                u.*,
                                c.title as course_name,
                                c.course_id,
                                e.enrollment_date,
                                e.status as enrollment_status
                            FROM users u
                            LEFT JOIN enrollments e ON u.id = e.user_id
                            LEFT JOIN courses c ON e.course_id = c.course_id
                            WHERE u.id = ? AND u.role = 'student'
                            LIMIT 1");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الطالب غير موجود');
    }
    
    $student = $result->fetch_assoc();
    
    echo json_encode([
        'success' => true,
        'data' => $student
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Create new student
 */
function createStudent($conn, $userId, $userRole) {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $courseId = intval($_POST['course_id'] ?? 0);
    $status = $_POST['status'] ?? 'pending';
    $sendEmail = isset($_POST['send_email']);
    
    // Validation
    if (empty($fullName) || empty($email) || empty($phone) || empty($password)) {
        throw new Exception('الرجاء ملء جميع الحقول الإجبارية');
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('البريد الإلكتروني غير صحيح');
    }
    
    // Check if email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param('s', $email);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception('البريد الإلكتروني مستخدم مسبقاً');
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Generate student ID
    $studentNumber = generateStudentNumber($conn);
    
    // Insert user
    $stmt = $conn->prepare("INSERT INTO users 
                           (full_name, email, phone, password, role, status, payment_status, 
                            student_number, birth_date, gender, national_id, guardian_phone, 
                            address, education_level, created_at) 
                           VALUES (?, ?, ?, ?, 'student', ?, 'pending', ?, ?, ?, ?, ?, ?, ?, NOW())");
    
    $birthDate = $_POST['birth_date'] ?? null;
    $gender = $_POST['gender'] ?? null;
    $nationalId = $_POST['national_id'] ?? null;
    $guardianPhone = $_POST['guardian_phone'] ?? null;
    $address = $_POST['address'] ?? null;
    $educationLevel = $_POST['education_level'] ?? null;
    
    $stmt->bind_param('sssssssssss', 
        $fullName, $email, $phone, $hashedPassword, $status, $studentNumber,
        $birthDate, $gender, $nationalId, $guardianPhone, $address, $educationLevel
    );
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إضافة الطالب');
    }
    
    $newStudentId = $conn->insert_id;
    
    // Enroll in course if provided
    if ($courseId > 0) {
        $enrollStmt = $conn->prepare("INSERT INTO enrollments (user_id, course_id, enrollment_date, status) 
                                      VALUES (?, ?, NOW(), 'active')");
        $enrollStmt->bind_param('ii', $newStudentId, $courseId);
        $enrollStmt->execute();
    }
    
    // Send welcome email
    if ($sendEmail) {
        sendWelcomeEmail($email, $fullName, $password);
    }
    
    // Create notification
    createNotification($conn, $newStudentId, 'مرحباً بك في منصة إبداع تعز! تم تفعيل حسابك بنجاح.');
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة الطالب بنجاح',
        'student_id' => $newStudentId
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Update student
 */
function updateStudent($conn, $userId, $userRole) {
    $studentId = intval($_POST['student_id'] ?? 0);
    
    if ($studentId === 0) {
        throw new Exception('معرف الطالب مطلوب');
    }
    
    // Students can only update their own profile
    if ($userRole === 'student' && $studentId !== $userId) {
        throw new Exception('ليس لديك صلاحية لتعديل بيانات هذا الطالب');
    }
    
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $status = $_POST['status'] ?? '';
    
    if (empty($fullName) || empty($email) || empty($phone)) {
        throw new Exception('الرجاء ملء جميع الحقول المطلوبة');
    }
    
    // Build update query
    $updates = ['full_name = ?', 'email = ?', 'phone = ?'];
    $params = [$fullName, $email, $phone];
    $types = 'sss';
    
    // Only manager and technical can change status
    if (in_array($userRole, ['manager', 'technical']) && !empty($status)) {
        $updates[] = 'status = ?';
        $params[] = $status;
        $types .= 's';
    }
    
    // Add other optional fields
    if (isset($_POST['birth_date'])) {
        $updates[] = 'birth_date = ?';
        $params[] = $_POST['birth_date'];
        $types .= 's';
    }
    
    if (isset($_POST['address'])) {
        $updates[] = 'address = ?';
        $params[] = $_POST['address'];
        $types .= 's';
    }
    
    $updateClause = implode(', ', $updates);
    $params[] = $studentId;
    $types .= 'i';
    
    $stmt = $conn->prepare("UPDATE users SET $updateClause WHERE id = ? AND role = 'student'");
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث البيانات');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث البيانات بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Delete student
 */
function deleteStudent($conn, $userId, $userRole) {
    $studentId = intval($_GET['id'] ?? 0);
    
    if ($studentId === 0) {
        throw new Exception('معرف الطالب مطلوب');
    }
    
    // Soft delete
    $stmt = $conn->prepare("UPDATE users SET status = 'deleted', deleted_at = NOW() 
                           WHERE id = ? AND role = 'student'");
    $stmt->bind_param('i', $studentId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في حذف الطالب');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف الطالب بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Approve student
 */
function approveStudent($conn, $userId, $userRole) {
    $studentId = intval($_POST['student_id'] ?? 0);
    
    if ($studentId === 0) {
        throw new Exception('معرف الطالب مطلوب');
    }
    
    $stmt = $conn->prepare("UPDATE users SET status = 'approved' WHERE id = ? AND role = 'student'");
    $stmt->bind_param('i', $studentId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في الموافقة على الطالب');
    }
    
    // Create notification
    createNotification($conn, $studentId, 'تهانينا! تم الموافقة على طلبك. يمكنك الآن الوصول لجميع خدمات المنصة.');
    
    // Send email
    $studentStmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $studentStmt->bind_param('i', $studentId);
    $studentStmt->execute();
    $student = $studentStmt->get_result()->fetch_assoc();
    
    if ($student) {
        sendApprovalEmail($student['email'], $student['full_name']);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم الموافقة على الطالب بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Import students from Excel
 */
function importStudents($conn, $userId, $userRole) {
    if (!isset($_FILES['file'])) {
        throw new Exception('الرجاء اختيار ملف Excel');
    }
    
    // This would integrate with the existing AI import system
    echo json_encode([
        'success' => true,
        'message' => 'جاري معالجة الملف...',
        'redirect' => '../api/ai_import_stream.php'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Export students to Excel
 */
function exportStudents($conn, $userId, $userRole) {
    // Set headers for download
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_' . date('Y-m-d') . '.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Get students
    $sql = "SELECT u.*, c.title as course_name 
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.role = 'student'
            ORDER BY u.created_at DESC";
    
    $result = $conn->query($sql);
    
    // Output header
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "الرقم\tالاسم الكامل\tالبريد الإلكتروني\tالهاتف\tالدورة\tالحالة\tحالة الدفع\tتاريخ التسجيل\n";
    
    // Output data
    while ($row = $result->fetch_assoc()) {
        echo "{$row['id']}\t{$row['full_name']}\t{$row['email']}\t{$row['phone']}\t";
        echo "{$row['course_name']}\t{$row['status']}\t{$row['payment_status']}\t{$row['created_at']}\n";
    }
    
    exit;
}

/**
 * Download template
 */
function downloadTemplate() {
    header('Content-Type: application/vnd.ms-excel; charset=utf-8');
    header('Content-Disposition: attachment; filename="students_template.xls"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    echo "\xEF\xBB\xBF"; // UTF-8 BOM
    echo "الاسم الكامل*\tالبريد الإلكتروني*\tرقم الهاتف*\tرقم الدورة\tتاريخ الميلاد\tالجنس\tالعنوان\n";
    echo "محمد أحمد\tmohamed@example.com\t773123456\t1\t2000-01-01\tذكر\tتعز\n";
    
    exit;
}

/**
 * Generate unique student number
 */
function generateStudentNumber($conn) {
    $year = date('Y');
    $prefix = "STD{$year}";
    
    $stmt = $conn->query("SELECT student_number FROM users 
                         WHERE student_number LIKE '{$prefix}%' 
                         ORDER BY student_number DESC LIMIT 1");
    
    if ($stmt->num_rows > 0) {
        $lastNumber = $stmt->fetch_assoc()['student_number'];
        $sequence = intval(substr($lastNumber, -4)) + 1;
    } else {
        $sequence = 1;
    }
    
    return $prefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

/**
 * Send welcome email
 */
function sendWelcomeEmail($email, $fullName, $password) {
    // Integrate with existing email system
    // For now, just log
    error_log("Welcome email sent to: $email");
}

/**
 * Send approval email
 */
function sendApprovalEmail($email, $fullName) {
    // Integrate with existing email system
    error_log("Approval email sent to: $email");
}

/**
 * Create notification
 */
function createNotification($conn, $userId, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                           VALUES (?, ?, 'system', NOW())");
    $stmt->bind_param('is', $userId, $message);
    $stmt->execute();
}
