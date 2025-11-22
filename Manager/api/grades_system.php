<?php
/**
 * grades_system - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../platform/db.php';

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_REQUEST['action'] ?? '';

// ============================================
// ADD/UPDATE GRADES (Technical/Manager)
// ============================================
if ($action === 'update_grades' && $method === 'POST') {
    if (!in_array($user_role, ['manager', 'technical'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_id = (int)($data['student_id'] ?? 0);
    $course_id = (int)($data['course_id'] ?? 0);
    $grades = $data['grades'] ?? []; // Array of component => grade

    if ($student_id <= 0 || $course_id <= 0 || empty($grades)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->begin_transaction();

    try {
        foreach ($grades as $component => $grade_data) {
            $grade_value = (float)($grade_data['value'] ?? 0);
            $max_grade = (float)($grade_data['max'] ?? 100);
            $weight = (float)($grade_data['weight'] ?? 1);

            $stmt = $conn->prepare("
                INSERT INTO student_grades (
                    student_id, course_id, grade_component, grade_value, 
                    max_grade, weight, entered_by, entered_at
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
                ON DUPLICATE KEY UPDATE
                    grade_value = VALUES(grade_value),
                    max_grade = VALUES(max_grade),
                    weight = VALUES(weight),
                    entered_by = VALUES(entered_by),
                    updated_at = NOW()
            ");
            
            $stmt->bind_param(
                'iisdddi',
                $student_id, $course_id, $component, $grade_value,
                $max_grade, $weight, $user_id
            );
            $stmt->execute();
            $stmt->close();
        }

        // Calculate total grade
        $total = calculateTotalGrade($conn, $student_id, $course_id);

        // Update enrollment with total grade
        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET final_grade = ?, updated_at = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->bind_param('dii', $total, $student_id, $course_id);
        $stmt->execute();
        $stmt->close();

        // Check if student should complete course
        $passing_grade = 50; // Can be made dynamic per course
        if ($total >= $passing_grade) {
            $stmt = $conn->prepare("
                UPDATE enrollments 
                SET status = 'completed', completed_at = NOW()
                WHERE user_id = ? AND course_id = ? AND status != 'completed'
            ");
            $stmt->bind_param('ii', $student_id, $course_id);
            
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                // Generate certificate automatically
                generateCertificate($conn, $student_id, $course_id, $user_id);
            }
            $stmt->close();
        }

        // Notify student
        $stmt = $conn->prepare("
            INSERT INTO notifications (
                user_id, title, message, type, link, created_at
            )
            VALUES (
                ?, 'تحديث الدرجات', 
                'تم تحديث درجاتك في أحد الدورات', 
                'grade', 
                '/grades', 
                NOW()
            )
        ");
        $stmt->bind_param('i', $student_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم تحديث الدرجات بنجاح',
            'total_grade' => $total,
            'certificate_generated' => $total >= $passing_grade
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'خطأ: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// GET STUDENT GRADES (Real-time)
// ============================================
if ($action === 'get_grades' && $method === 'GET') {
    $student_id = $user_role === 'student' ? $user_id : (int)($_GET['student_id'] ?? 0);
    $course_id = (int)($_GET['course_id'] ?? 0);

    if ($student_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الطالب مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $where = "student_id = ?";
    $params = [$student_id];
    $types = 'i';

    if ($course_id > 0) {
        $where .= " AND course_id = ?";
        $params[] = $course_id;
        $types .= 'i';
    }

    $stmt = $conn->prepare("
        SELECT 
            sg.*,
            c.title as course_title,
            u.full_name as entered_by_name,
            e.final_grade,
            e.status as enrollment_status
        FROM student_grades sg
        JOIN courses c ON sg.course_id = c.course_id
        JOIN users u ON sg.entered_by = u.id
        JOIN enrollments e ON sg.student_id = e.user_id AND sg.course_id = e.course_id
        WHERE $where
        ORDER BY sg.course_id, sg.grade_component
    ");
    
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $grades = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Group by course
    $grouped = [];
    foreach ($grades as $grade) {
        $cid = $grade['course_id'];
        if (!isset($grouped[$cid])) {
            $grouped[$cid] = [
                'course_id' => $cid,
                'course_title' => $grade['course_title'],
                'final_grade' => $grade['final_grade'],
                'enrollment_status' => $grade['enrollment_status'],
                'components' => []
            ];
        }
        $grouped[$cid]['components'][] = [
            'component' => $grade['grade_component'],
            'value' => $grade['grade_value'],
            'max' => $grade['max_grade'],
            'weight' => $grade['weight'],
            'percentage' => ($grade['grade_value'] / $grade['max_grade']) * 100,
            'entered_by' => $grade['entered_by_name'],
            'entered_at' => $grade['entered_at'],
            'updated_at' => $grade['updated_at']
        ];
    }

    echo json_encode([
        'success' => true,
        'grades' => array_values($grouped)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// GET ALL STUDENTS GRADES (Manager/Technical)
// ============================================
if ($action === 'get_all_grades' && $method === 'GET') {
    if (!in_array($user_role, ['manager', 'technical'])) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $course_id = (int)($_GET['course_id'] ?? 0);

    if ($course_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            e.user_id as student_id,
            u.full_name as student_name,
            u.email as student_email,
            e.final_grade,
            e.status,
            e.enrollment_date,
            e.completed_at
        FROM enrollments e
        JOIN users u ON e.user_id = u.id
        WHERE e.course_id = ?
        ORDER BY u.full_name
    ");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    // Get grade components for each student
    foreach ($students as &$student) {
        $stmt = $conn->prepare("
            SELECT grade_component, grade_value, max_grade, weight
            FROM student_grades
            WHERE student_id = ? AND course_id = ?
        ");
        $stmt->bind_param('ii', $student['student_id'], $course_id);
        $stmt->execute();
        $student['grade_components'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt->close();
    }

    echo json_encode([
        'success' => true,
        'students' => $students
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function calculateTotalGrade($conn, $student_id, $course_id) {
    $stmt = $conn->prepare("
        SELECT 
            SUM((grade_value / max_grade) * weight * 100) as total
        FROM student_grades
        WHERE student_id = ? AND course_id = ?
    ");
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    return round($result['total'] ?? 0, 2);
}

function generateCertificate($conn, $student_id, $course_id, $issued_by) {
    // Check if already exists
    $stmt = $conn->prepare("
        SELECT certificate_id 
        FROM certificates 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $existing = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return;
    }

    // Make internal API call to generate certificate
    $baseUrl = 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');
    $apiUrl = $baseUrl . '/Manager/api/generate_certificate.php';

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'student_id' => $student_id,
        'course_id' => $course_id
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Cookie: PHPSESSID=' . session_id()
    ]);

    $response = curl_exec($ch);
    curl_close($ch);

    // Notify student about certificate
    $stmt = $conn->prepare("
        INSERT INTO notifications (
            user_id, title, message, type, link, created_at
        )
        VALUES (
            ?, 'تهانينا! شهادتك جاهزة', 
            'لقد أكملت الدورة بنجاح وتم إصدار شهادتك', 
            'certificate', 
            '/certificates', 
            NOW()
        )
    ");
    $stmt->bind_param('i', $student_id);
    $stmt->execute();
    $stmt->close();
}

echo json_encode(['success' => false, 'message' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
?>
