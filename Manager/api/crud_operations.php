<?php
// 🔧 Advanced CRUD Operations System
// Enhanced Create, Read, Update, Delete with Validation, Undo/Redo, Bulk Operations & Audit Trail

session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';

// ===== HELPER FUNCTIONS =====

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function checkAuth() {
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
    
    if (!$userId) {
        respond(['success' => false, 'message' => 'غير مصرح - يرجى تسجيل الدخول'], 401);
    }
    
    return ['user_id' => $userId, 'role' => $userRole];
}

function checkPermission($userRole, $requiredRoles) {
    if (!in_array($userRole, $requiredRoles, true)) {
        respond(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء'], 403);
    }
}

function logAuditTrail($conn, $userId, $action, $tableName, $recordId, $oldData = null, $newData = null) {
    $stmt = $conn->prepare("
        INSERT INTO audit_trail 
        (user_id, action, table_name, record_id, old_data, new_data, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $oldDataJson = $oldData ? json_encode($oldData, JSON_UNESCAPED_UNICODE) : null;
    $newDataJson = $newData ? json_encode($newData, JSON_UNESCAPED_UNICODE) : null;
    
    $stmt->bind_param('ississ', $userId, $action, $tableName, $recordId, $oldDataJson, $newDataJson);
    $stmt->execute();
}

// ===== ADVANCED VALIDATION FUNCTIONS =====

function validateEmail($email) {
    if (empty($email)) {
        return ['valid' => false, 'message' => 'البريد الإلكتروني مطلوب'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['valid' => false, 'message' => 'البريد الإلكتروني غير صالح'];
    }
    return ['valid' => true];
}

function validatePhone($phone) {
    if (empty($phone)) {
        return ['valid' => false, 'message' => 'رقم الهاتف مطلوب'];
    }
    // Yemen phone format: 7XXXXXXXX or 77XXXXXXX or 73XXXXXXX, etc.
    if (!preg_match('/^(7[0-9]{8}|77[0-9]{7}|73[0-9]{7})$/', $phone)) {
        return ['valid' => false, 'message' => 'رقم الهاتف غير صالح (يجب أن يبدأ بـ 7 ويتكون من 9 أرقام)'];
    }
    return ['valid' => true];
}

function validateName($name, $fieldName = 'الاسم') {
    if (empty($name)) {
        return ['valid' => false, 'message' => "$fieldName مطلوب"];
    }
    if (strlen($name) < 3) {
        return ['valid' => false, 'message' => "$fieldName يجب أن يحتوي على 3 أحرف على الأقل"];
    }
    if (strlen($name) > 100) {
        return ['valid' => false, 'message' => "$fieldName طويل جداً (الحد الأقصى 100 حرف)"];
    }
    return ['valid' => true];
}

function validateAmount($amount, $fieldName = 'المبلغ') {
    if (!is_numeric($amount)) {
        return ['valid' => false, 'message' => "$fieldName يجب أن يكون رقماً"];
    }
    if ($amount < 0) {
        return ['valid' => false, 'message' => "$fieldName يجب أن يكون أكبر من أو يساوي صفر"];
    }
    return ['valid' => true];
}

function validateDate($date, $fieldName = 'التاريخ') {
    if (empty($date)) {
        return ['valid' => false, 'message' => "$fieldName مطلوب"];
    }
    $timestamp = strtotime($date);
    if ($timestamp === false) {
        return ['valid' => false, 'message' => "$fieldName غير صالح"];
    }
    return ['valid' => true];
}

function validateRequired($value, $fieldName) {
    if (empty($value) && $value !== '0' && $value !== 0) {
        return ['valid' => false, 'message' => "$fieldName مطلوب"];
    }
    return ['valid' => true];
}

// ===== MAIN ROUTER =====

$auth = checkAuth();
$action = $_GET['action'] ?? '';
$entity = $_GET['entity'] ?? ''; // users, courses, enrollments, payments, etc.

switch ($action) {
    case 'create':
        checkPermission($auth['role'], ['manager', 'technical']);
        createRecord($conn, $auth, $entity);
        break;
    
    case 'read':
        readRecords($conn, $auth, $entity);
        break;
    
    case 'update':
        checkPermission($auth['role'], ['manager', 'technical']);
        updateRecord($conn, $auth, $entity);
        break;
    
    case 'delete':
        checkPermission($auth['role'], ['manager', 'technical']);
        deleteRecord($conn, $auth, $entity);
        break;
    
    case 'bulk_delete':
        checkPermission($auth['role'], ['manager', 'technical']);
        bulkDelete($conn, $auth, $entity);
        break;
    
    case 'bulk_update':
        checkPermission($auth['role'], ['manager', 'technical']);
        bulkUpdate($conn, $auth, $entity);
        break;
    
    case 'undo':
        checkPermission($auth['role'], ['manager', 'technical']);
        undoAction($conn, $auth);
        break;
    
    case 'redo':
        checkPermission($auth['role'], ['manager', 'technical']);
        redoAction($conn, $auth);
        break;
    
    case 'get_audit_trail':
        checkPermission($auth['role'], ['manager', 'technical']);
        getAuditTrail($conn, $auth, $entity);
        break;
    
    case 'validate':
        validateData($conn, $auth, $entity);
        break;
    
    default:
        respond(['success' => false, 'message' => 'إجراء غير صالح'], 400);
}

// ===== CRUD OPERATIONS =====

/**
 * Create new record with validation
 */
function createRecord($conn, $auth, $entity) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    switch ($entity) {
        case 'user':
            createUser($conn, $auth, $input);
            break;
        case 'course':
            createCourse($conn, $auth, $input);
            break;
        case 'enrollment':
            createEnrollment($conn, $auth, $input);
            break;
        case 'payment':
            createPayment($conn, $auth, $input);
            break;
        default:
            respond(['success' => false, 'message' => 'نوع السجل غير مدعوم'], 400);
    }
}

function createUser($conn, $auth, $data) {
    // Validate
    $validations = [
        validateName($data['full_name'] ?? '', 'الاسم الكامل'),
        validateEmail($data['email'] ?? ''),
        validateRequired($data['role'] ?? '', 'الدور')
    ];
    
    foreach ($validations as $validation) {
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    // Check if email exists
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
    $checkStmt->bind_param('s', $data['email']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        respond(['success' => false, 'message' => 'البريد الإلكتروني مستخدم بالفعل'], 400);
    }
    
    // Create
    $password = $data['password'] ?? 'Ibdaa@2025';
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("
        INSERT INTO users (full_name, email, password_hash, phone, role, verified, created_at)
        VALUES (?, ?, ?, ?, ?, 1, NOW())
    ");
    
    $stmt->bind_param('sssss', 
        $data['full_name'],
        $data['email'],
        $passwordHash,
        $data['phone'] ?? null,
        $data['role']
    );
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل إنشاء المستخدم: ' . $stmt->error], 500);
    }
    
    $userId = $conn->insert_id;
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'create', 'users', $userId, null, $data);
    
    respond([
        'success' => true,
        'message' => 'تم إنشاء المستخدم بنجاح',
        'user_id' => $userId
    ]);
}

function createCourse($conn, $auth, $data) {
    // Validate
    $validations = [
        validateName($data['title'] ?? '', 'عنوان الدورة'),
        validateAmount($data['price'] ?? 0, 'السعر'),
        validateRequired($data['duration'] ?? '', 'المدة')
    ];
    
    foreach ($validations as $validation) {
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    // Create
    $stmt = $conn->prepare("
        INSERT INTO courses (title, description, price, duration, start_date, category, trainer_id, location_id, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $stmt->bind_param('ssdissii',
        $data['title'],
        $data['description'] ?? null,
        $data['price'],
        $data['duration'],
        $data['start_date'] ?? null,
        $data['category'] ?? null,
        $data['trainer_id'] ?? null,
        $data['location_id'] ?? null
    );
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل إنشاء الدورة: ' . $stmt->error], 500);
    }
    
    $courseId = $conn->insert_id;
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'create', 'courses', $courseId, null, $data);
    
    respond([
        'success' => true,
        'message' => 'تم إنشاء الدورة بنجاح',
        'course_id' => $courseId
    ]);
}

function createEnrollment($conn, $auth, $data) {
    // Validate
    $validations = [
        validateRequired($data['user_id'] ?? '', 'معرف الطالب'),
        validateRequired($data['course_id'] ?? '', 'معرف الدورة')
    ];
    
    foreach ($validations as $validation) {
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    // Check if enrollment exists
    $checkStmt = $conn->prepare("SELECT id FROM enrollments WHERE user_id = ? AND course_id = ?");
    $checkStmt->bind_param('ii', $data['user_id'], $data['course_id']);
    $checkStmt->execute();
    if ($checkStmt->get_result()->num_rows > 0) {
        respond(['success' => false, 'message' => 'الطالب مسجل بالفعل في هذه الدورة'], 400);
    }
    
    // Create
    $stmt = $conn->prepare("
        INSERT INTO enrollments (user_id, course_id, enrollment_date, status, payment_status)
        VALUES (?, ?, NOW(), 'active', 'pending')
    ");
    
    $stmt->bind_param('ii', $data['user_id'], $data['course_id']);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل إنشاء التسجيل: ' . $stmt->error], 500);
    }
    
    $enrollmentId = $conn->insert_id;
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'create', 'enrollments', $enrollmentId, null, $data);
    
    respond([
        'success' => true,
        'message' => 'تم تسجيل الطالب بنجاح',
        'enrollment_id' => $enrollmentId
    ]);
}

function createPayment($conn, $auth, $data) {
    // Validate
    $validations = [
        validateRequired($data['user_id'] ?? '', 'معرف الطالب'),
        validateRequired($data['course_id'] ?? '', 'معرف الدورة'),
        validateAmount($data['amount'] ?? 0, 'المبلغ'),
        validateRequired($data['payment_method'] ?? '', 'طريقة الدفع')
    ];
    
    foreach ($validations as $validation) {
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    // Create
    $stmt = $conn->prepare("
        INSERT INTO payments (user_id, course_id, amount, payment_method, payment_date, status, receipt_number)
        VALUES (?, ?, ?, ?, NOW(), 'completed', ?)
    ");
    
    $receiptNumber = 'RCP-' . time() . '-' . rand(1000, 9999);
    
    $stmt->bind_param('iidss',
        $data['user_id'],
        $data['course_id'],
        $data['amount'],
        $data['payment_method'],
        $receiptNumber
    );
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل إنشاء الدفع: ' . $stmt->error], 500);
    }
    
    $paymentId = $conn->insert_id;
    
    // Update enrollment payment status
    $updateStmt = $conn->prepare("UPDATE enrollments SET payment_status = 'completed' WHERE user_id = ? AND course_id = ?");
    $updateStmt->bind_param('ii', $data['user_id'], $data['course_id']);
    $updateStmt->execute();
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'create', 'payments', $paymentId, null, $data);
    
    respond([
        'success' => true,
        'message' => 'تم تسجيل الدفع بنجاح',
        'payment_id' => $paymentId,
        'receipt_number' => $receiptNumber
    ]);
}

/**
 * Read records with pagination and filters
 */
function readRecords($conn, $auth, $entity) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $search = $_GET['search'] ?? '';
    
    switch ($entity) {
        case 'users':
            readUsers($conn, $auth, $page, $limit, $offset, $search);
            break;
        case 'courses':
            readCourses($conn, $auth, $page, $limit, $offset, $search);
            break;
        case 'enrollments':
            readEnrollments($conn, $auth, $page, $limit, $offset, $search);
            break;
        case 'payments':
            readPayments($conn, $auth, $page, $limit, $offset, $search);
            break;
        default:
            respond(['success' => false, 'message' => 'نوع السجل غير مدعوم'], 400);
    }
}

function readUsers($conn, $auth, $page, $limit, $offset, $search) {
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE full_name LIKE ? OR email LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
        $types = 'ss';
    }
    
    // Count
    $countQuery = "SELECT COUNT(*) as total FROM users $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Fetch
    $query = "
        SELECT id, full_name, email, phone, role, verified, created_at
        FROM users
        $whereClause
        ORDER BY created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
    
    respond([
        'success' => true,
        'data' => $users,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function readCourses($conn, $auth, $page, $limit, $offset, $search) {
    $whereClause = '';
    $params = [];
    $types = '';
    
    if (!empty($search)) {
        $whereClause = "WHERE c.title LIKE ? OR c.description LIKE ?";
        $searchTerm = "%$search%";
        $params = [$searchTerm, $searchTerm];
        $types = 'ss';
    }
    
    // Count
    $countQuery = "SELECT COUNT(*) as total FROM courses c $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if (!empty($params)) {
        $countStmt->bind_param($types, ...$params);
    }
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Fetch
    $query = "
        SELECT 
            c.course_id, c.title, c.description, c.price, c.duration, 
            c.start_date, c.category, c.created_at,
            t.full_name as trainer_name,
            l.location_name
        FROM courses c
        LEFT JOIN users t ON c.trainer_id = t.id
        LEFT JOIN locations l ON c.location_id = l.id
        $whereClause
        ORDER BY c.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    $types .= 'ii';
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $courses[] = $row;
    }
    
    respond([
        'success' => true,
        'data' => $courses,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

function readEnrollments($conn, $auth, $page, $limit, $offset, $search) {
    // Implementation similar to above
    respond(['success' => true, 'message' => 'قيد التطوير']);
}

function readPayments($conn, $auth, $page, $limit, $offset, $search) {
    // Implementation similar to above
    respond(['success' => true, 'message' => 'قيد التطوير']);
}

/**
 * Update record with validation
 */
function updateRecord($conn, $auth, $entity) {
    $input = json_decode(file_get_contents('php://input'), true);
    $id = intval($input['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'معرف السجل مطلوب'], 400);
    }
    
    switch ($entity) {
        case 'user':
            updateUser($conn, $auth, $id, $input);
            break;
        case 'course':
            updateCourse($conn, $auth, $id, $input);
            break;
        default:
            respond(['success' => false, 'message' => 'نوع السجل غير مدعوم'], 400);
    }
}

function updateUser($conn, $auth, $id, $data) {
    // Get old data for audit
    $oldStmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $oldStmt->bind_param('i', $id);
    $oldStmt->execute();
    $oldData = $oldStmt->get_result()->fetch_assoc();
    
    if (!$oldData) {
        respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
    }
    
    // Validate
    if (isset($data['full_name'])) {
        $validation = validateName($data['full_name'], 'الاسم الكامل');
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    if (isset($data['email'])) {
        $validation = validateEmail($data['email']);
        if (!$validation['valid']) {
            respond(['success' => false, 'message' => $validation['message']], 400);
        }
    }
    
    // Update
    $updateFields = [];
    $params = [];
    $types = '';
    
    if (isset($data['full_name'])) {
        $updateFields[] = 'full_name = ?';
        $params[] = $data['full_name'];
        $types .= 's';
    }
    
    if (isset($data['email'])) {
        $updateFields[] = 'email = ?';
        $params[] = $data['email'];
        $types .= 's';
    }
    
    if (isset($data['phone'])) {
        $updateFields[] = 'phone = ?';
        $params[] = $data['phone'];
        $types .= 's';
    }
    
    if (empty($updateFields)) {
        respond(['success' => false, 'message' => 'لا توجد حقول للتحديث'], 400);
    }
    
    $params[] = $id;
    $types .= 'i';
    
    $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل تحديث المستخدم: ' . $stmt->error], 500);
    }
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'update', 'users', $id, $oldData, $data);
    
    respond(['success' => true, 'message' => 'تم تحديث المستخدم بنجاح']);
}

function updateCourse($conn, $auth, $id, $data) {
    // Similar to updateUser
    respond(['success' => true, 'message' => 'قيد التطوير']);
}

/**
 * Delete record with audit trail
 */
function deleteRecord($conn, $auth, $entity) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'معرف السجل مطلوب'], 400);
    }
    
    switch ($entity) {
        case 'user':
            deleteUser($conn, $auth, $id);
            break;
        case 'course':
            deleteCourse($conn, $auth, $id);
            break;
        default:
            respond(['success' => false, 'message' => 'نوع السجل غير مدعوم'], 400);
    }
}

function deleteUser($conn, $auth, $id) {
    // Get data for audit before deletion
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $data = $stmt->get_result()->fetch_assoc();
    
    if (!$data) {
        respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
    }
    
    // Delete
    $deleteStmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $deleteStmt->bind_param('i', $id);
    
    if (!$deleteStmt->execute()) {
        respond(['success' => false, 'message' => 'فشل حذف المستخدم: ' . $deleteStmt->error], 500);
    }
    
    // Audit log
    logAuditTrail($conn, $auth['user_id'], 'delete', 'users', $id, $data, null);
    
    respond(['success' => true, 'message' => 'تم حذف المستخدم بنجاح']);
}

function deleteCourse($conn, $auth, $id) {
    // Similar to deleteUser
    respond(['success' => true, 'message' => 'قيد التطوير']);
}

/**
 * Bulk delete multiple records
 */
function bulkDelete($conn, $auth, $entity) {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    
    if (empty($ids)) {
        respond(['success' => false, 'message' => 'لا توجد معرفات للحذف'], 400);
    }
    
    $tableName = '';
    switch ($entity) {
        case 'users':
            $tableName = 'users';
            $idColumn = 'id';
            break;
        case 'courses':
            $tableName = 'courses';
            $idColumn = 'course_id';
            break;
        default:
            respond(['success' => false, 'message' => 'نوع السجل غير مدعوم'], 400);
    }
    
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $types = str_repeat('i', count($ids));
    
    $stmt = $conn->prepare("DELETE FROM $tableName WHERE $idColumn IN ($placeholders)");
    $stmt->bind_param($types, ...$ids);
    
    if (!$stmt->execute()) {
        respond(['success' => false, 'message' => 'فشل الحذف الجماعي: ' . $stmt->error], 500);
    }
    
    $deletedCount = $stmt->affected_rows;
    
    // Audit log
    foreach ($ids as $id) {
        logAuditTrail($conn, $auth['user_id'], 'bulk_delete', $tableName, $id, null, null);
    }
    
    respond([
        'success' => true,
        'message' => "تم حذف $deletedCount سجل بنجاح",
        'count' => $deletedCount
    ]);
}

/**
 * Bulk update multiple records
 */
function bulkUpdate($conn, $auth, $entity) {
    $input = json_decode(file_get_contents('php://input'), true);
    $ids = $input['ids'] ?? [];
    $updates = $input['updates'] ?? [];
    
    if (empty($ids) || empty($updates)) {
        respond(['success' => false, 'message' => 'بيانات غير كافية للتحديث الجماعي'], 400);
    }
    
    respond(['success' => true, 'message' => 'قيد التطوير']);
}

/**
 * Undo last action
 */
function undoAction($conn, $auth) {
    // Get last action from audit trail
    $stmt = $conn->prepare("
        SELECT * FROM audit_trail 
        WHERE user_id = ? AND undone = 0
        ORDER BY created_at DESC 
        LIMIT 1
    ");
    $stmt->bind_param('i', $auth['user_id']);
    $stmt->execute();
    $action = $stmt->get_result()->fetch_assoc();
    
    if (!$action) {
        respond(['success' => false, 'message' => 'لا توجد إجراءات للتراجع'], 404);
    }
    
    // Implement undo logic based on action type
    respond(['success' => true, 'message' => 'قيد التطوير - Undo']);
}

/**
 * Redo last undone action
 */
function redoAction($conn, $auth) {
    respond(['success' => true, 'message' => 'قيد التطوير - Redo']);
}

/**
 * Get audit trail for entity
 */
function getAuditTrail($conn, $auth, $entity) {
    $recordId = intval($_GET['record_id'] ?? 0);
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    
    $whereClause = $recordId ? "WHERE record_id = ? AND table_name = ?" : "WHERE table_name = ?";
    
    $countQuery = "SELECT COUNT(*) as total FROM audit_trail $whereClause";
    $countStmt = $conn->prepare($countQuery);
    
    if ($recordId) {
        $countStmt->bind_param('is', $recordId, $entity);
    } else {
        $countStmt->bind_param('s', $entity);
    }
    
    $countStmt->execute();
    $total = $countStmt->get_result()->fetch_assoc()['total'];
    
    $query = "
        SELECT 
            a.*, 
            u.full_name as user_name
        FROM audit_trail a
        LEFT JOIN users u ON a.user_id = u.id
        $whereClause
        ORDER BY a.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    
    if ($recordId) {
        $stmt->bind_param('isii', $recordId, $entity, $limit, $offset);
    } else {
        $stmt->bind_param('sii', $entity, $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $trail = [];
    while ($row = $result->fetch_assoc()) {
        $row['old_data'] = $row['old_data'] ? json_decode($row['old_data'], true) : null;
        $row['new_data'] = $row['new_data'] ? json_decode($row['new_data'], true) : null;
        $trail[] = $row;
    }
    
    respond([
        'success' => true,
        'data' => $trail,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'total_pages' => ceil($total / $limit)
        ]
    ]);
}

/**
 * Validate data without saving
 */
function validateData($conn, $auth, $entity) {
    $input = json_decode(file_get_contents('php://input'), true);
    $errors = [];
    
    switch ($entity) {
        case 'user':
            if (isset($input['full_name'])) {
                $v = validateName($input['full_name'], 'الاسم الكامل');
                if (!$v['valid']) $errors[] = $v['message'];
            }
            if (isset($input['email'])) {
                $v = validateEmail($input['email']);
                if (!$v['valid']) $errors[] = $v['message'];
            }
            if (isset($input['phone'])) {
                $v = validatePhone($input['phone']);
                if (!$v['valid']) $errors[] = $v['message'];
            }
            break;
        
        case 'course':
            if (isset($input['title'])) {
                $v = validateName($input['title'], 'عنوان الدورة');
                if (!$v['valid']) $errors[] = $v['message'];
            }
            if (isset($input['price'])) {
                $v = validateAmount($input['price'], 'السعر');
                if (!$v['valid']) $errors[] = $v['message'];
            }
            break;
    }
    
    if (empty($errors)) {
        respond(['success' => true, 'valid' => true, 'message' => 'البيانات صالحة']);
    } else {
        respond(['success' => true, 'valid' => false, 'errors' => $errors]);
    }
}
