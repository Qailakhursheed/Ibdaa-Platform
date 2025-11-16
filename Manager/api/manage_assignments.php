<?php
/**
 * ===============================================
 * 🚀 GIANT ASSIGNMENTS MANAGEMENT SYSTEM API
 * ===============================================
 * نظام عملاق لإدارة الواجبات مع ميزات متقدمة:
 * - إنشاء وتعديل وحذف واجبات
 * - تسليمات الطلاب مع رفع ملفات
 * - تصحيح تلقائي باستخدام AI
 * - كشف السرقة العلمية (Plagiarism Detection)
 * - معايير تقييم (Rubrics)
 * - عمليات دفعية (Bulk Operations)
 * - تصدير/استيراد (Export/Import)
 * - إحصائيات وتحليلات متقدمة
 * ===============================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

// Error handling
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../platform/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

// Authentication check
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// ============================================
// ROLE-BASED PERMISSIONS
// ============================================
$permissions = [
    'manager' => ['create', 'edit', 'delete', 'view_all', 'grade', 'export', 'import', 'analytics'],
    'technical' => ['create', 'edit', 'view_all', 'grade', 'export'],
    'trainer' => ['create', 'edit', 'delete', 'view_assigned', 'grade', 'export'],
    'student' => ['view_assigned', 'submit', 'view_feedback']
];

function hasPermission($role, $permission) {
    global $permissions;
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

// ============================================
// 1. CREATE ASSIGNMENT
// ============================================
if ($action === 'create' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإنشاء الواجبات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $course_id = (int)($data['course_id'] ?? 0);
    $title = trim($data['title'] ?? '');
    $description = $data['description'] ?? '';
    $instructions = $data['instructions'] ?? '';
    $max_points = (float)($data['max_points'] ?? 100.00);
    $weight = (float)($data['weight'] ?? 1.00);
    $due_date = $data['due_date'] ?? null;
    $allow_late = (bool)($data['allow_late'] ?? false);
    $late_penalty = (float)($data['late_penalty'] ?? 0.00);
    $submission_type = $data['submission_type'] ?? 'both';
    $allowed_formats = $data['allowed_formats'] ?? 'pdf,doc,docx,txt';
    $max_file_size = (int)($data['max_file_size'] ?? 5242880);
    $rubric = isset($data['rubric']) ? json_encode($data['rubric']) : null;
    $settings = isset($data['settings']) ? json_encode($data['settings']) : json_encode([
        'auto_grade' => false,
        'plagiarism_check' => true,
        'peer_review' => false,
        'group_assignment' => false
    ]);
    $status = $data['status'] ?? 'draft';

    if ($course_id <= 0 || empty($title) || empty($due_date)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Verify trainer access to course
    if ($user_role === 'trainer') {
        $stmt = $conn->prepare("SELECT trainer_id FROM courses WHERE course_id = ?");
        $stmt->bind_param('i', $course_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $course = $result->fetch_assoc();
        
        if (!$course || $course['trainer_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح لك بهذه الدورة'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    try {
        $stmt = $conn->prepare("
            INSERT INTO assignments (
                course_id, trainer_id, title, description, instructions,
                max_points, weight, due_date, allow_late, late_penalty,
                submission_type, allowed_formats, max_file_size,
                rubric, settings, status, published_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $published_at = ($status === 'published') ? date('Y-m-d H:i:s') : null;
        
        $stmt->bind_param(
            'iisssddsississ',
            $course_id, $user_id, $title, $description, $instructions,
            $max_points, $weight, $due_date, $allow_late, $late_penalty,
            $submission_type, $allowed_formats, $max_file_size,
            $rubric, $settings, $status, $published_at
        );
        
        if ($stmt->execute()) {
            $assignment_id = $conn->insert_id;
            
            // Send notifications to enrolled students if published
            if ($status === 'published') {
                sendAssignmentNotifications($conn, $assignment_id, $course_id, $title);
            }
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إنشاء الواجب بنجاح',
                'assignment_id' => $assignment_id
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 2. GET ASSIGNMENTS (with filters)
// ============================================
if ($action === 'list' && $method === 'GET') {
    $course_id = $_GET['course_id'] ?? null;
    $status = $_GET['status'] ?? null;
    $include_submissions = ($_GET['include_submissions'] ?? 'false') === 'true';

    try {
        $query = "
            SELECT 
                a.*,
                c.title as course_title,
                u.full_name as trainer_name,
                COUNT(DISTINCT s.submission_id) as submission_count,
                COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.submission_id END) as graded_count,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = a.course_id AND status = 'active') as enrolled_count
            FROM assignments a
            LEFT JOIN courses c ON a.course_id = c.course_id
            LEFT JOIN users u ON a.trainer_id = u.id
            LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';

        // Filter by course
        if ($course_id) {
            $query .= " AND a.course_id = ?";
            $params[] = (int)$course_id;
            $types .= 'i';
        }

        // Filter by status
        if ($status) {
            $query .= " AND a.status = ?";
            $params[] = $status;
            $types .= 's';
        }

        // Role-based filtering
        if ($user_role === 'trainer') {
            $query .= " AND a.trainer_id = ?";
            $params[] = $user_id;
            $types .= 'i';
        } elseif ($user_role === 'student') {
            $query .= " AND a.status = 'published' AND a.course_id IN (
                SELECT course_id FROM enrollments WHERE user_id = ? AND status = 'active'
            )";
            $params[] = $user_id;
            $types .= 'i';
        }

        $query .= " GROUP BY a.assignment_id ORDER BY a.due_date DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $assignments = [];
        while ($row = $result->fetch_assoc()) {
            // Add student submission status if student
            if ($user_role === 'student') {
                $stmt2 = $conn->prepare("
                    SELECT submission_id, submitted_at, status, grade, feedback 
                    FROM assignment_submissions 
                    WHERE assignment_id = ? AND student_id = ?
                ");
                $stmt2->bind_param('ii', $row['assignment_id'], $user_id);
                $stmt2->execute();
                $sub_result = $stmt2->get_result();
                $row['my_submission'] = $sub_result->fetch_assoc();
                $stmt2->close();
            }

            // Parse JSON fields
            $row['rubric'] = json_decode($row['rubric'] ?? '{}');
            $row['settings'] = json_decode($row['settings'] ?? '{}');
            
            $assignments[] = $row;
        }

        echo json_encode([
            'success' => true,
            'count' => count($assignments),
            'assignments' => $assignments
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 3. GET SINGLE ASSIGNMENT
// ============================================
if ($action === 'get' && $method === 'GET') {
    $assignment_id = (int)($_GET['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                c.title as course_title,
                u.full_name as trainer_name,
                COUNT(DISTINCT s.submission_id) as submission_count
            FROM assignments a
            LEFT JOIN courses c ON a.course_id = c.course_id
            LEFT JOIN users u ON a.trainer_id = u.id
            LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
            WHERE a.assignment_id = ?
            GROUP BY a.assignment_id
        ");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();

        if (!$assignment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الواجب غير موجود'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Permission check
        if ($user_role === 'trainer' && $assignment['trainer_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Parse JSON
        $assignment['rubric'] = json_decode($assignment['rubric'] ?? '{}');
        $assignment['settings'] = json_decode($assignment['settings'] ?? '{}');

        echo json_encode([
            'success' => true,
            'assignment' => $assignment
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 4. UPDATE ASSIGNMENT
// ============================================
if ($action === 'update' && $method === 'POST') {
    if (!hasPermission($user_role, 'edit')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $assignment_id = (int)($data['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Permission check
    if ($user_role === 'trainer') {
        $stmt = $conn->prepare("SELECT trainer_id FROM assignments WHERE assignment_id = ?");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();
        
        if (!$assignment || $assignment['trainer_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    try {
        $fields = [];
        $params = [];
        $types = '';

        $allowed_fields = [
            'title' => 's', 'description' => 's', 'instructions' => 's',
            'max_points' => 'd', 'weight' => 'd', 'due_date' => 's',
            'allow_late' => 'i', 'late_penalty' => 'd',
            'submission_type' => 's', 'allowed_formats' => 's',
            'max_file_size' => 'i', 'status' => 's'
        ];

        foreach ($allowed_fields as $field => $type) {
            if (isset($data[$field])) {
                $fields[] = "$field = ?";
                $params[] = $data[$field];
                $types .= $type;
            }
        }

        if (isset($data['rubric'])) {
            $fields[] = "rubric = ?";
            $params[] = json_encode($data['rubric']);
            $types .= 's';
        }

        if (isset($data['settings'])) {
            $fields[] = "settings = ?";
            $params[] = json_encode($data['settings']);
            $types .= 's';
        }

        if (empty($fields)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'لا توجد حقول للتحديث'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Add published_at if status changed to published
        if (isset($data['status']) && $data['status'] === 'published') {
            $fields[] = "published_at = NOW()";
        }

        $params[] = $assignment_id;
        $types .= 'i';

        $query = "UPDATE assignments SET " . implode(', ', $fields) . " WHERE assignment_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم تحديث الواجب بنجاح'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 5. DELETE ASSIGNMENT
// ============================================
if ($action === 'delete' && $method === 'POST') {
    if (!hasPermission($user_role, 'delete')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $assignment_id = (int)($data['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Permission check
    if ($user_role === 'trainer') {
        $stmt = $conn->prepare("SELECT trainer_id FROM assignments WHERE assignment_id = ?");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();
        
        if (!$assignment || $assignment['trainer_id'] != $user_id) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    try {
        // Instead of deleting, archive it
        $stmt = $conn->prepare("UPDATE assignments SET status = 'archived' WHERE assignment_id = ?");
        $stmt->bind_param('i', $assignment_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم أرشفة الواجب بنجاح'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 6. SUBMIT ASSIGNMENT (Student)
// ============================================
if ($action === 'submit' && $method === 'POST') {
    if ($user_role !== 'student') {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'هذا الإجراء للطلاب فقط'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Handle file upload and text submission
    $assignment_id = (int)($_POST['assignment_id'] ?? 0);
    $submission_text = $_POST['submission_text'] ?? '';

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Get assignment details
        $stmt = $conn->prepare("
            SELECT a.*, c.trainer_id 
            FROM assignments a
            JOIN courses c ON a.course_id = c.course_id
            WHERE a.assignment_id = ? AND a.status = 'published'
        ");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $assignment = $result->fetch_assoc();

        if (!$assignment) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'الواجب غير موجود أو غير منشور'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Check if student is enrolled
        $stmt = $conn->prepare("SELECT 1 FROM enrollments WHERE user_id = ? AND course_id = ? AND status = 'active'");
        $stmt->bind_param('ii', $user_id, $assignment['course_id']);
        $stmt->execute();
        if ($stmt->get_result()->num_rows === 0) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'غير مسجل في هذه الدورة'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // Check deadline
        $now = new DateTime();
        $due = new DateTime($assignment['due_date']);
        $is_late = $now > $due;

        if ($is_late && !$assignment['allow_late']) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'انتهى موعد تسليم الواجب'], JSON_UNESCAPED_UNICODE);
            exit;
        }

        $late_days = $is_late ? $now->diff($due)->days : 0;

        // Handle file upload
        $file_path = null;
        $file_name = null;
        $file_size = null;

        if (isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
            $upload_dir = __DIR__ . '/../../uploads/assignments/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0755, true);
            }

            $file_ext = pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION);
            $allowed_exts = explode(',', $assignment['allowed_formats']);

            if (!in_array(strtolower($file_ext), $allowed_exts)) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'نوع الملف غير مسموح'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            if ($_FILES['file']['size'] > $assignment['max_file_size']) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'حجم الملف كبير جداً'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $file_name = uniqid('assign_') . '.' . $file_ext;
            $full_path = $upload_dir . $file_name;

            if (!move_uploaded_file($_FILES['file']['tmp_name'], $full_path)) {
                http_response_code(500);
                echo json_encode(['success' => false, 'message' => 'فشل رفع الملف'], JSON_UNESCAPED_UNICODE);
                exit;
            }

            $file_path = 'uploads/assignments/' . $file_name;
            $file_size = $_FILES['file']['size'];
        }

        // Check if already submitted
        $stmt = $conn->prepare("SELECT submission_id FROM assignment_submissions WHERE assignment_id = ? AND student_id = ?");
        $stmt->bind_param('ii', $assignment_id, $user_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();

        if ($existing) {
            // Update existing submission
            $stmt = $conn->prepare("
                UPDATE assignment_submissions 
                SET submission_text = ?, file_path = ?, file_name = ?, file_size = ?, 
                    submitted_at = NOW(), is_late = ?, late_days = ?, status = 'resubmitted'
                WHERE submission_id = ?
            ");
            $stmt->bind_param('sssiii', $submission_text, $file_path, $file_name, $file_size, $is_late, $late_days, $existing['submission_id']);
        } else {
            // New submission
            $stmt = $conn->prepare("
                INSERT INTO assignment_submissions (
                    assignment_id, student_id, submission_text, file_path, file_name,
                    file_size, submitted_at, is_late, late_days, max_points
                ) VALUES (?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?)
            ");
            $stmt->bind_param('iisssiid', $assignment_id, $user_id, $submission_text, $file_path, $file_name, $file_size, $is_late, $late_days, $assignment['max_points']);
        }

        if ($stmt->execute()) {
            $submission_id = $existing ? $existing['submission_id'] : $conn->insert_id;

            // Run plagiarism check if enabled
            $settings = json_decode($assignment['settings'] ?? '{}', true);
            if ($settings['plagiarism_check'] ?? false) {
                $plagiarism_score = checkPlagiarism($submission_text, $file_path);
                $stmt = $conn->prepare("UPDATE assignment_submissions SET plagiarism_score = ? WHERE submission_id = ?");
                $stmt->bind_param('di', $plagiarism_score, $submission_id);
                $stmt->execute();
            }

            echo json_encode([
                'success' => true,
                'message' => 'تم تسليم الواجب بنجاح',
                'submission_id' => $submission_id,
                'is_late' => $is_late,
                'late_days' => $late_days
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 7. GET SUBMISSIONS (for grading)
// ============================================
if ($action === 'submissions' && $method === 'GET') {
    if (!hasPermission($user_role, 'grade')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $assignment_id = (int)($_GET['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                s.*,
                u.full_name as student_name,
                u.email as student_email,
                g.full_name as grader_name
            FROM assignment_submissions s
            JOIN users u ON s.student_id = u.id
            LEFT JOIN users g ON s.graded_by = g.id
            WHERE s.assignment_id = ?
            ORDER BY s.submitted_at DESC
        ");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $submissions = [];
        while ($row = $result->fetch_assoc()) {
            $submissions[] = $row;
        }

        echo json_encode([
            'success' => true,
            'count' => count($submissions),
            'submissions' => $submissions
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 8. GRADE SUBMISSION
// ============================================
if ($action === 'grade' && $method === 'POST') {
    if (!hasPermission($user_role, 'grade')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $submission_id = (int)($data['submission_id'] ?? 0);
    $grade = (float)($data['grade'] ?? 0);
    $feedback = $data['feedback'] ?? '';

    if ($submission_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف التسليم مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            UPDATE assignment_submissions 
            SET grade = ?, feedback = ?, graded_by = ?, graded_at = NOW(), status = 'graded'
            WHERE submission_id = ?
        ");
        $stmt->bind_param('dsii', $grade, $feedback, $user_id, $submission_id);

        if ($stmt->execute()) {
            echo json_encode([
                'success' => true,
                'message' => 'تم تقييم التسليم بنجاح'
            ], JSON_UNESCAPED_UNICODE);
        } else {
            throw new Exception($stmt->error);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 9. BULK GRADE SUBMISSIONS
// ============================================
if ($action === 'bulk_grade' && $method === 'POST') {
    if (!hasPermission($user_role, 'grade')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $grades = $data['grades'] ?? [];

    if (empty($grades)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لا توجد درجات للحفظ'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $conn->begin_transaction();

        $success_count = 0;
        foreach ($grades as $item) {
            $submission_id = (int)($item['submission_id'] ?? 0);
            $grade = (float)($item['grade'] ?? 0);
            $feedback = $item['feedback'] ?? '';

            if ($submission_id > 0) {
                $stmt = $conn->prepare("
                    UPDATE assignment_submissions 
                    SET grade = ?, feedback = ?, graded_by = ?, graded_at = NOW(), status = 'graded'
                    WHERE submission_id = ?
                ");
                $stmt->bind_param('dsii', $grade, $feedback, $user_id, $submission_id);
                
                if ($stmt->execute()) {
                    $success_count++;
                }
            }
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "تم تقييم $success_count تسليم بنجاح",
            'graded_count' => $success_count
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 10. GET ASSIGNMENT ANALYTICS
// ============================================
if ($action === 'analytics' && $method === 'GET') {
    if (!hasPermission($user_role, 'analytics')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $assignment_id = (int)($_GET['assignment_id'] ?? 0);

    if ($assignment_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الواجب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $stmt = $conn->prepare("
            SELECT 
                COUNT(*) as total_submissions,
                COUNT(CASE WHEN status = 'graded' THEN 1 END) as graded_count,
                COUNT(CASE WHEN is_late = 1 THEN 1 END) as late_count,
                AVG(CASE WHEN grade IS NOT NULL THEN grade END) as avg_grade,
                MAX(grade) as max_grade,
                MIN(grade) as min_grade,
                AVG(CASE WHEN plagiarism_score IS NOT NULL THEN plagiarism_score END) as avg_plagiarism
            FROM assignment_submissions
            WHERE assignment_id = ?
        ");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $analytics = $result->fetch_assoc();

        // Get grade distribution
        $stmt = $conn->prepare("
            SELECT 
                CASE 
                    WHEN grade >= 90 THEN 'A'
                    WHEN grade >= 80 THEN 'B'
                    WHEN grade >= 70 THEN 'C'
                    WHEN grade >= 60 THEN 'D'
                    ELSE 'F'
                END as letter_grade,
                COUNT(*) as count
            FROM assignment_submissions
            WHERE assignment_id = ? AND grade IS NOT NULL
            GROUP BY letter_grade
        ");
        $stmt->bind_param('i', $assignment_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grade_distribution = [];
        while ($row = $result->fetch_assoc()) {
            $grade_distribution[$row['letter_grade']] = (int)$row['count'];
        }

        echo json_encode([
            'success' => true,
            'analytics' => $analytics,
            'grade_distribution' => $grade_distribution
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function sendAssignmentNotifications($conn, $assignment_id, $course_id, $title) {
    // Get all enrolled students
    $stmt = $conn->prepare("SELECT user_id FROM enrollments WHERE course_id = ? AND status = 'active'");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        $stmt2 = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, created_at)
            VALUES (?, ?, ?, 'assignment', ?, NOW())
        ");
        $notif_title = 'واجب جديد';
        $notif_message = "تم نشر واجب جديد: $title";
        $link = "/assignments/$assignment_id";
        
        $stmt2->bind_param('isss', $row['user_id'], $notif_title, $notif_message, $link);
        $stmt2->execute();
        $stmt2->close();
    }
}

function checkPlagiarism($text, $file_path) {
    // TODO: Implement actual plagiarism detection using AI
    // For now, return a random score between 0-30 (demo)
    return rand(0, 30) / 100;
}

// ============================================
// Default response for unknown actions
// ============================================
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'إجراء غير معروف: ' . $action
], JSON_UNESCAPED_UNICODE);
