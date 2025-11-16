<?php
/**
 * ===============================================
 * 🚀 ENHANCED GRADES SYSTEM API WITH AI
 * ===============================================
 * نظام درجات عملاق محدث بميزات AI/ML متقدمة
 * ===============================================
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../platform/db.php';
require_once __DIR__ . '/../../vendor/autoload.php';

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
// PERMISSIONS
// ============================================
$permissions = [
    'manager' => ['create', 'edit', 'delete', 'view_all', 'analytics', 'export', 'grade_curves'],
    'technical' => ['create', 'edit', 'view_all', 'analytics', 'export'],
    'trainer' => ['create', 'edit', 'view_assigned', 'analytics'],
    'student' => ['view_own']
];

function hasPermission($role, $permission) {
    global $permissions;
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

// ============================================
// 1. GET GRADES WITH ADVANCED FILTERING
// ============================================
if ($action === 'list' && $method === 'GET') {
    $course_id = $_GET['course_id'] ?? null;
    $student_id = $_GET['student_id'] ?? null;
    $component = $_GET['component'] ?? null;
    $include_analytics = ($_GET['include_analytics'] ?? 'false') === 'true';

    try {
        $query = "
            SELECT 
                sg.*,
                u.full_name as student_name,
                u.email as student_email,
                c.title as course_title,
                gc.component_name,
                gc.component_weight,
                gc.max_points as component_max,
                grader.full_name as grader_name,
                (sg.grade_value / sg.max_grade * 100) as percentage
            FROM student_grades sg
            JOIN users u ON sg.student_id = u.id
            JOIN courses c ON sg.course_id = c.course_id
            JOIN grade_components gc ON sg.component_id = gc.component_id
            LEFT JOIN users grader ON sg.entered_by = grader.id
            WHERE 1=1
        ";
        
        $params = [];
        $types = '';

        if ($course_id) {
            $query .= " AND sg.course_id = ?";
            $params[] = (int)$course_id;
            $types .= 'i';
        }

        if ($student_id) {
            $query .= " AND sg.student_id = ?";
            $params[] = (int)$student_id;
            $types .= 'i';
        } elseif ($user_role === 'student') {
            $query .= " AND sg.student_id = ?";
            $params[] = $user_id;
            $types .= 'i';
        } elseif ($user_role === 'trainer') {
            $query .= " AND c.trainer_id = ?";
            $params[] = $user_id;
            $types .= 'i';
        }

        if ($component) {
            $query .= " AND gc.component_name = ?";
            $params[] = $component;
            $types .= 's';
        }

        $query .= " ORDER BY sg.entered_at DESC";

        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        $grades = [];
        while ($row = $result->fetch_assoc()) {
            // Calculate weighted score
            $row['weighted_score'] = ($row['grade_value'] / $row['max_grade']) * $row['component_weight'];
            
            // Grade letter
            $row['letter_grade'] = calculateLetterGrade($row['percentage']);
            
            $grades[] = $row;
        }

        $response = [
            'success' => true,
            'count' => count($grades),
            'grades' => $grades
        ];

        // Add analytics if requested
        if ($include_analytics && $course_id) {
            $response['analytics'] = getCourseGradeAnalytics($conn, $course_id);
        }

        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 2. GET STUDENT FINAL GRADES
// ============================================
if ($action === 'final_grades' && $method === 'GET') {
    $course_id = (int)($_GET['course_id'] ?? 0);
    $student_id = $_GET['student_id'] ?? null;

    if ($user_role === 'student') {
        $student_id = $user_id;
    }

    try {
        $query = "
            SELECT 
                sg.student_id,
                u.full_name as student_name,
                c.title as course_title,
                c.credits,
                SUM(sg.grade_value / sg.max_grade * gc.component_weight) as final_grade,
                COUNT(DISTINCT sg.component_id) as components_graded,
                (SELECT COUNT(*) FROM grade_components WHERE course_id = sg.course_id AND is_active = 1) as total_components
            FROM student_grades sg
            JOIN users u ON sg.student_id = u.id
            JOIN courses c ON sg.course_id = c.course_id
            JOIN grade_components gc ON sg.component_id = gc.component_id
            WHERE sg.course_id = ?
        ";
        
        $params = [$course_id];
        $types = 'i';

        if ($student_id) {
            $query .= " AND sg.student_id = ?";
            $params[] = (int)$student_id;
            $types .= 'i';
        }

        $query .= " GROUP BY sg.student_id, sg.course_id";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $final_grades = [];
        while ($row = $result->fetch_assoc()) {
            $row['letter_grade'] = calculateLetterGrade($row['final_grade']);
            $row['grade_point'] = calculateGradePoint($row['final_grade']);
            $row['status'] = $row['final_grade'] >= 50 ? 'pass' : 'fail';
            $row['completion'] = ($row['components_graded'] / $row['total_components']) * 100;
            
            $final_grades[] = $row;
        }

        echo json_encode([
            'success' => true,
            'final_grades' => $final_grades
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 3. CALCULATE GPA
// ============================================
if ($action === 'gpa' && $method === 'GET') {
    $student_id = $_GET['student_id'] ?? $user_id;
    $semester = $_GET['semester'] ?? null;

    if ($user_role === 'student') {
        $student_id = $user_id;
    }

    try {
        $query = "
            SELECT 
                c.course_id,
                c.title,
                c.credits,
                SUM(sg.grade_value / sg.max_grade * gc.component_weight) as final_grade
            FROM student_grades sg
            JOIN courses c ON sg.course_id = c.course_id
            JOIN grade_components gc ON sg.component_id = gc.component_id
            JOIN enrollments e ON e.course_id = c.course_id AND e.user_id = sg.student_id
            WHERE sg.student_id = ? AND e.status IN ('active', 'completed')
        ";
        
        $params = [(int)$student_id];
        $types = 'i';

        // Add semester filter if provided
        // (Assuming you have semester field in enrollments or courses)

        $query .= " GROUP BY c.course_id HAVING final_grade IS NOT NULL";

        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $total_credits = 0;
        $weighted_points = 0;
        $courses = [];

        while ($row = $result->fetch_assoc()) {
            $grade_point = calculateGradePoint($row['final_grade']);
            $letter_grade = calculateLetterGrade($row['final_grade']);
            
            $total_credits += $row['credits'];
            $weighted_points += $grade_point * $row['credits'];
            
            $courses[] = [
                'course_id' => $row['course_id'],
                'title' => $row['title'],
                'credits' => $row['credits'],
                'final_grade' => round($row['final_grade'], 2),
                'letter_grade' => $letter_grade,
                'grade_point' => $grade_point
            ];
        }

        $gpa = $total_credits > 0 ? round($weighted_points / $total_credits, 2) : 0.00;

        echo json_encode([
            'success' => true,
            'gpa' => $gpa,
            'total_credits' => $total_credits,
            'total_courses' => count($courses),
            'courses' => $courses,
            'classification' => getGPAClassification($gpa)
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 4. ADD/UPDATE GRADE
// ============================================
if ($action === 'upsert' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $student_id = (int)($data['student_id'] ?? 0);
    $course_id = (int)($data['course_id'] ?? 0);
    $component_id = (int)($data['component_id'] ?? 0);
    $grade_value = (float)($data['grade_value'] ?? 0);
    $max_grade = (float)($data['max_grade'] ?? 100.00);
    $weight = (float)($data['weight'] ?? 1.00);
    $notes = $data['notes'] ?? '';

    if ($student_id <= 0 || $course_id <= 0 || $component_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        // Check if grade exists
        $stmt = $conn->prepare("
            SELECT id, grade_value as old_value 
            FROM student_grades 
            WHERE student_id = ? AND course_id = ? AND component_id = ?
        ");
        $stmt->bind_param('iii', $student_id, $course_id, $component_id);
        $stmt->execute();
        $existing = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($existing) {
            // Update existing
            $stmt = $conn->prepare("
                UPDATE student_grades 
                SET grade_value = ?, max_grade = ?, weight = ?, notes = ?, 
                    entered_by = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('dddsi', $grade_value, $max_grade, $weight, $notes, $user_id, $existing['id']);
            $stmt->execute();
            
            // Log history
            if ($existing['old_value'] != $grade_value) {
                logGradeChange($conn, $existing['id'], $existing['old_value'], $grade_value, $user_id, 'تحديث الدرجة');
            }
            
            $message = 'تم تحديث الدرجة بنجاح';
        } else {
            // Insert new
            $stmt = $conn->prepare("
                INSERT INTO student_grades (
                    student_id, course_id, component_id, grade_value,
                    max_grade, weight, notes, entered_by, entered_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param('iiidddsi', $student_id, $course_id, $component_id, $grade_value, $max_grade, $weight, $notes, $user_id);
            $stmt->execute();
            
            $message = 'تم إضافة الدرجة بنجاح';
        }

        // Update enrollment final grade
        updateEnrollmentGrade($conn, $student_id, $course_id);

        // Send notification to student
        sendGradeNotification($conn, $student_id, $course_id, $component_id);

        echo json_encode([
            'success' => true,
            'message' => $message
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 5. BULK GRADE UPDATE
// ============================================
if ($action === 'bulk_upsert' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $grades = $data['grades'] ?? [];

    if (empty($grades)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'لا توجد درجات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $conn->begin_transaction();

        $success_count = 0;
        $updated_students = [];

        foreach ($grades as $item) {
            $student_id = (int)($item['student_id'] ?? 0);
            $course_id = (int)($item['course_id'] ?? 0);
            $component_id = (int)($item['component_id'] ?? 0);
            $grade_value = (float)($item['grade_value'] ?? 0);
            $max_grade = (float)($item['max_grade'] ?? 100.00);
            $weight = (float)($item['weight'] ?? 1.00);
            $notes = $item['notes'] ?? '';

            if ($student_id > 0 && $course_id > 0 && $component_id > 0) {
                $stmt = $conn->prepare("
                    INSERT INTO student_grades (
                        student_id, course_id, component_id, grade_value,
                        max_grade, weight, notes, entered_by, entered_at
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE
                        grade_value = VALUES(grade_value),
                        max_grade = VALUES(max_grade),
                        weight = VALUES(weight),
                        notes = VALUES(notes),
                        entered_by = VALUES(entered_by),
                        updated_at = NOW()
                ");
                $stmt->bind_param('iiidddsi', $student_id, $course_id, $component_id, $grade_value, $max_grade, $weight, $notes, $user_id);
                
                if ($stmt->execute()) {
                    $success_count++;
                    $updated_students[$student_id] = $course_id;
                }
                $stmt->close();
            }
        }

        // Update all affected enrollments
        foreach ($updated_students as $student_id => $course_id) {
            updateEnrollmentGrade($conn, $student_id, $course_id);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "تم حفظ $success_count درجة بنجاح",
            'saved_count' => $success_count
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 6. GRADE CURVE APPLICATION
// ============================================
if ($action === 'apply_curve' && $method === 'POST') {
    if (!hasPermission($user_role, 'grade_curves')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $course_id = (int)($data['course_id'] ?? 0);
    $component_id = (int)($data['component_id'] ?? 0);
    $curve_type = $data['curve_type'] ?? 'linear'; // linear, sqrt, percentage
    $curve_value = (float)($data['curve_value'] ?? 0);

    try {
        $conn->begin_transaction();

        // Get all grades for component
        $stmt = $conn->prepare("
            SELECT id, student_id, grade_value, max_grade
            FROM student_grades
            WHERE course_id = ? AND component_id = ?
        ");
        $stmt->bind_param('ii', $course_id, $component_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $grades = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        $updated_count = 0;

        foreach ($grades as $grade) {
            $old_value = $grade['grade_value'];
            $new_value = applyCurve($old_value, $grade['max_grade'], $curve_type, $curve_value);

            $stmt = $conn->prepare("
                UPDATE student_grades 
                SET grade_value = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $stmt->bind_param('di', $new_value, $grade['id']);
            $stmt->execute();
            $stmt->close();

            // Log change
            logGradeChange($conn, $grade['id'], $old_value, $new_value, $user_id, "تطبيق منحنى: $curve_type");

            $updated_count++;

            // Update enrollment
            updateEnrollmentGrade($conn, $grade['student_id'], $course_id);
        }

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => "تم تطبيق المنحنى على $updated_count درجة",
            'updated_count' => $updated_count
        ], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $conn->rollback();
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'خطأ: ' . $e->getMessage()], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// 7. COURSE ANALYTICS
// ============================================
if ($action === 'analytics' && $method === 'GET') {
    if (!hasPermission($user_role, 'analytics')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'غير مصرح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $course_id = (int)($_GET['course_id'] ?? 0);

    if ($course_id <= 0) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'معرف الدورة مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    try {
        $analytics = getCourseGradeAnalytics($conn, $course_id);
        
        echo json_encode([
            'success' => true,
            'analytics' => $analytics
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

function calculateLetterGrade($percentage) {
    if ($percentage >= 95) return 'A+';
    if ($percentage >= 90) return 'A';
    if ($percentage >= 85) return 'A-';
    if ($percentage >= 80) return 'B+';
    if ($percentage >= 75) return 'B';
    if ($percentage >= 70) return 'B-';
    if ($percentage >= 65) return 'C+';
    if ($percentage >= 60) return 'C';
    if ($percentage >= 55) return 'C-';
    if ($percentage >= 50) return 'D';
    return 'F';
}

function calculateGradePoint($percentage) {
    if ($percentage >= 95) return 4.0;
    if ($percentage >= 90) return 3.7;
    if ($percentage >= 85) return 3.3;
    if ($percentage >= 80) return 3.0;
    if ($percentage >= 75) return 2.7;
    if ($percentage >= 70) return 2.3;
    if ($percentage >= 65) return 2.0;
    if ($percentage >= 60) return 1.7;
    if ($percentage >= 55) return 1.3;
    if ($percentage >= 50) return 1.0;
    return 0.0;
}

function getGPAClassification($gpa) {
    if ($gpa >= 3.7) return 'ممتاز';
    if ($gpa >= 3.3) return 'جيد جداً';
    if ($gpa >= 2.7) return 'جيد';
    if ($gpa >= 2.0) return 'مقبول';
    return 'ضعيف';
}

function updateEnrollmentGrade($conn, $student_id, $course_id) {
    // Calculate final grade
    $stmt = $conn->prepare("
        SELECT SUM(sg.grade_value / sg.max_grade * gc.component_weight) as final_grade
        FROM student_grades sg
        JOIN grade_components gc ON sg.component_id = gc.component_id
        WHERE sg.student_id = ? AND sg.course_id = ?
        GROUP BY sg.student_id, sg.course_id
    ");
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $final_grade = $result['final_grade'] ?? null;

    if ($final_grade !== null) {
        $letter_grade = calculateLetterGrade($final_grade);
        $grade_point = calculateGradePoint($final_grade);

        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET final_grade = ?, letter_grade = ?, grade_point = ?, updated_at = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->bind_param('dssii', $final_grade, $letter_grade, $grade_point, $student_id, $course_id);
        $stmt->execute();
        $stmt->close();

        // Check if student passed
        if ($final_grade >= 50) {
            $stmt = $conn->prepare("
                UPDATE enrollments 
                SET status = 'completed', completed_at = NOW()
                WHERE user_id = ? AND course_id = ? AND status = 'active'
            ");
            $stmt->bind_param('ii', $student_id, $course_id);
            $stmt->execute();
            $stmt->close();
        }
    }
}

function logGradeChange($conn, $grade_id, $old_value, $new_value, $changed_by, $reason) {
    // Note: This assumes you have a grades table reference
    // Adjust table name if needed based on your schema
    $stmt = $conn->prepare("
        INSERT INTO grade_history (grade_id, old_value, new_value, changed_by, reason, changed_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $stmt->bind_param('iddis', $grade_id, $old_value, $new_value, $changed_by, $reason);
    $stmt->execute();
    $stmt->close();
}

function sendGradeNotification($conn, $student_id, $course_id, $component_id) {
    // Get component name
    $stmt = $conn->prepare("SELECT component_name FROM grade_components WHERE component_id = ?");
    $stmt->bind_param('i', $component_id);
    $stmt->execute();
    $component = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $stmt = $conn->prepare("
        INSERT INTO notifications (user_id, title, message, type, link, created_at)
        VALUES (?, 'درجة جديدة', ?, 'grade', ?, NOW())
    ");
    $title = 'درجة جديدة';
    $message = "تم إضافة درجة جديدة: " . ($component['component_name'] ?? 'تقييم');
    $link = "/grades?course_id=$course_id";
    
    $stmt->bind_param('iss', $student_id, $message, $link);
    $stmt->execute();
    $stmt->close();
}

function applyCurve($grade, $max_grade, $type, $value) {
    $percentage = ($grade / $max_grade) * 100;
    
    switch ($type) {
        case 'linear':
            // Add fixed percentage
            $new_percentage = min(100, $percentage + $value);
            break;
        
        case 'sqrt':
            // Square root curve (helps lower grades more)
            $new_percentage = sqrt($percentage / 100) * 100;
            break;
        
        case 'percentage':
            // Multiply by percentage
            $new_percentage = min(100, $percentage * (1 + $value / 100));
            break;
        
        default:
            $new_percentage = $percentage;
    }
    
    return ($new_percentage / 100) * $max_grade;
}

function getCourseGradeAnalytics($conn, $course_id) {
    $analytics = [];

    // Overall stats
    $stmt = $conn->prepare("
        SELECT 
            COUNT(DISTINCT sg.student_id) as total_students,
            AVG(sg.grade_value / sg.max_grade * 100) as avg_grade,
            MAX(sg.grade_value / sg.max_grade * 100) as max_grade,
            MIN(sg.grade_value / sg.max_grade * 100) as min_grade,
            STDDEV(sg.grade_value / sg.max_grade * 100) as std_dev
        FROM student_grades sg
        WHERE sg.course_id = ?
    ");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $analytics['overall'] = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    // Grade distribution
    $stmt = $conn->prepare("
        SELECT 
            CASE 
                WHEN (sg.grade_value / sg.max_grade * 100) >= 90 THEN 'A'
                WHEN (sg.grade_value / sg.max_grade * 100) >= 80 THEN 'B'
                WHEN (sg.grade_value / sg.max_grade * 100) >= 70 THEN 'C'
                WHEN (sg.grade_value / sg.max_grade * 100) >= 60 THEN 'D'
                ELSE 'F'
            END as letter_grade,
            COUNT(*) as count
        FROM student_grades sg
        WHERE sg.course_id = ?
        GROUP BY letter_grade
    ");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $distribution = [];
    while ($row = $result->fetch_assoc()) {
        $distribution[$row['letter_grade']] = (int)$row['count'];
    }
    $analytics['distribution'] = $distribution;
    $stmt->close();

    // Component breakdown
    $stmt = $conn->prepare("
        SELECT 
            gc.component_name,
            gc.component_weight,
            AVG(sg.grade_value / sg.max_grade * 100) as avg_percentage,
            COUNT(sg.id) as submission_count
        FROM grade_components gc
        LEFT JOIN student_grades sg ON gc.component_id = sg.component_id
        WHERE gc.course_id = ? AND gc.is_active = 1
        GROUP BY gc.component_id
    ");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $analytics['components'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    return $analytics;
}

// ============================================
// DEFAULT RESPONSE
// ============================================
http_response_code(400);
echo json_encode([
    'success' => false,
    'message' => 'إجراء غير معروف: ' . $action
], JSON_UNESCAPED_UNICODE);
