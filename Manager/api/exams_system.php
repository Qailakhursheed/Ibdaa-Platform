<?php
/**
 * exams_system - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode([
            'success' => false,
            'message' => 'Fatal Error: ' . $error['message']
        ], JSON_UNESCAPED_UNICODE);
    }
});

require_once __DIR__ . '/../../vendor/autoload.php';
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
// ROLE-BASED PERMISSIONS
// ============================================
$permissions = [
    'manager' => ['create', 'edit', 'delete', 'publish', 'view_all', 'grade', 'view_results', 'export'],
    'technical' => ['create', 'edit', 'publish', 'view_all', 'grade', 'view_results'],
    'trainer' => ['create', 'edit', 'view_assigned', 'send_to_students'],
    'student' => ['take_exam', 'view_my_results']
];

function hasPermission($role, $permission) {
    global $permissions;
    return isset($permissions[$role]) && in_array($permission, $permissions[$role]);
}

// ============================================
// CREATE EXAM (Trainer/Technical/Manager)
// ============================================
if ($action === 'create' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بإنشاء الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $title = $data['title'] ?? '';
    $description = $data['description'] ?? '';
    $course_id = (int)($data['course_id'] ?? 0);
    $duration_minutes = (int)($data['duration_minutes'] ?? 60);
    $total_marks = (int)($data['total_marks'] ?? 100);
    $passing_marks = (int)($data['passing_marks'] ?? 50);
    $start_time = $data['start_time'] ?? null;
    $end_time = $data['end_time'] ?? null;
    $questions = $data['questions'] ?? [];
    $settings = json_encode($data['settings'] ?? [
        'shuffle_questions' => true,
        'show_results_immediately' => false,
        'allow_review' => true,
        'anti_cheat_enabled' => true,
        'prevent_copy_paste' => true,
        'prevent_tab_switch' => true,
        'fullscreen_required' => true,
        'webcam_proctoring' => false
    ]);

    if (empty($title) || $course_id <= 0 || empty($questions)) {
        echo json_encode(['success' => false, 'message' => 'بيانات غير مكتملة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Create exam
        $stmt = $conn->prepare("
            INSERT INTO exams (
                course_id, title, description, duration_minutes, 
                total_marks, passing_marks, start_time, end_time,
                created_by, settings, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')
        ");
        
        $stmt->bind_param(
            'issiiissis',
            $course_id, $title, $description, $duration_minutes,
            $total_marks, $passing_marks, $start_time, $end_time,
            $user_id, $settings
        );
        
        $stmt->execute();
        $exam_id = $conn->insert_id;
        $stmt->close();

        // Insert questions
        $stmt = $conn->prepare("
            INSERT INTO exam_questions (
                exam_id, question_text, question_type, options, 
                correct_answer, marks, order_index
            ) VALUES (?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($questions as $index => $question) {
            $q_text = $question['text'];
            $q_type = $question['type']; // mcq, true_false, short_answer, essay
            $q_options = json_encode($question['options'] ?? []);
            $q_answer = $question['correct_answer'] ?? '';
            $q_marks = (int)($question['marks'] ?? 1);
            
            $stmt->bind_param(
                'issssii',
                $exam_id, $q_text, $q_type, $q_options,
                $q_answer, $q_marks, $index
            );
            $stmt->execute();
        }
        
        $stmt->close();
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم إنشاء الاختبار بنجاح',
            'exam_id' => $exam_id
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في إنشاء الاختبار: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// PUBLISH EXAM (Technical/Manager)
// ============================================
if ($action === 'publish' && $method === 'POST') {
    if (!hasPermission($user_role, 'publish')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بنشر الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $exam_id = (int)($_POST['exam_id'] ?? 0);
    
    if ($exam_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الاختبار غير صحيح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("UPDATE exams SET status = 'published', published_at = NOW() WHERE exam_id = ?");
    $stmt->bind_param('i', $exam_id);
    
    if ($stmt->execute()) {
        // Notify students
        notifyStudents($conn, $exam_id);
        
        echo json_encode([
            'success' => true,
            'message' => 'تم نشر الاختبار وإرسال الإشعارات للطلاب'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل نشر الاختبار'], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    exit;
}

// ============================================
// GET EXAM (Students)
// ============================================
if ($action === 'get_exam' && $method === 'GET') {
    $exam_id = (int)($_GET['exam_id'] ?? 0);
    
    if ($exam_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الاختبار غير صحيح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check if student already took exam
    if ($user_role === 'student') {
        $stmt = $conn->prepare("SELECT * FROM exam_attempts WHERE exam_id = ? AND student_id = ?");
        $stmt->bind_param('ii', $exam_id, $user_id);
        $stmt->execute();
        $attempt = stmt_get_result_compat($stmt)->fetch_assoc();
        $stmt->close();

        if ($attempt && $attempt['status'] === 'completed') {
            echo json_encode([
                'success' => false,
                'message' => 'لقد أكملت هذا الاختبار بالفعل',
                'already_taken' => true
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
    }

    // Get exam details
    $stmt = $conn->prepare("
        SELECT e.*, c.title as course_title, u.full_name as creator_name
        FROM exams e
        JOIN courses c ON e.course_id = c.course_id
        JOIN users u ON e.created_by = u.id
        WHERE e.exam_id = ? AND e.status = 'published'
    ");
    $stmt->bind_param('i', $exam_id);
    $stmt->execute();
    $exam = stmt_get_result_compat($stmt)->fetch_assoc();
    $stmt->close();

    if (!$exam) {
        echo json_encode(['success' => false, 'message' => 'الاختبار غير موجود'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Get questions
    $stmt = $conn->prepare("
        SELECT question_id, question_text, question_type, options, marks, order_index
        FROM exam_questions
        WHERE exam_id = ?
        ORDER BY order_index
    ");
    $stmt->bind_param('i', $exam_id);
    $stmt->execute();
    $result = stmt_get_result_compat($stmt);
    $questions = [];
    
    while ($row = $result->fetch_assoc()) {
        $row['options'] = json_decode($row['options'], true);
        unset($row['correct_answer']); // Don't send correct answer to student
        $questions[] = $row;
    }
    $stmt->close();

    $exam['settings'] = json_decode($exam['settings'], true);
    $exam['questions'] = $questions;

    echo json_encode([
        'success' => true,
        'exam' => $exam
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// START EXAM ATTEMPT (Student)
// ============================================
if ($action === 'start_attempt' && $method === 'POST') {
    if ($user_role !== 'student') {
        echo json_encode(['success' => false, 'message' => 'الطلاب فقط يمكنهم إجراء الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $exam_id = (int)($_POST['exam_id'] ?? 0);
    
    // Create attempt record
    $stmt = $conn->prepare("
        INSERT INTO exam_attempts (exam_id, student_id, start_time, status)
        VALUES (?, ?, NOW(), 'in_progress')
    ");
    $stmt->bind_param('ii', $exam_id, $user_id);
    
    if ($stmt->execute()) {
        $attempt_id = $conn->insert_id;
        
        echo json_encode([
            'success' => true,
            'message' => 'تم بدء الاختبار',
            'attempt_id' => $attempt_id,
            'start_time' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل بدء الاختبار'], JSON_UNESCAPED_UNICODE);
    }
    
    $stmt->close();
    exit;
}

// ============================================
// SUBMIT EXAM (Student)
// ============================================
if ($action === 'submit' && $method === 'POST') {
    if ($user_role !== 'student') {
        echo json_encode(['success' => false, 'message' => 'الطلاب فقط يمكنهم تقديم الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $attempt_id = (int)($data['attempt_id'] ?? 0);
    $answers = $data['answers'] ?? [];
    $anti_cheat_events = $data['anti_cheat_events'] ?? [];

    if ($attempt_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'محاولة غير صحيحة'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->begin_transaction();

    try {
        // Save answers
        $stmt = $conn->prepare("
            INSERT INTO exam_answers (attempt_id, question_id, answer_text)
            VALUES (?, ?, ?)
        ");

        foreach ($answers as $question_id => $answer) {
            $stmt->bind_param('iis', $attempt_id, $question_id, $answer);
            $stmt->execute();
        }
        $stmt->close();

        // Log anti-cheat events
        if (!empty($anti_cheat_events)) {
            $stmt = $conn->prepare("
                INSERT INTO exam_anti_cheat_log (attempt_id, event_type, event_data, timestamp)
                VALUES (?, ?, ?, ?)
            ");

            foreach ($anti_cheat_events as $event) {
                $event_type = $event['type'];
                $event_data = json_encode($event['data']);
                $timestamp = $event['timestamp'];
                
                $stmt->bind_param('isss', $attempt_id, $event_type, $event_data, $timestamp);
                $stmt->execute();
            }
            $stmt->close();
        }

        // Update attempt
        $stmt = $conn->prepare("
            UPDATE exam_attempts 
            SET end_time = NOW(), status = 'completed'
            WHERE attempt_id = ?
        ");
        $stmt->bind_param('i', $attempt_id);
        $stmt->execute();
        $stmt->close();

        // Auto-grade MCQ questions
        $score = autoGradeExam($conn, $attempt_id);

        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'تم تقديم الاختبار بنجاح',
            'score' => $score,
            'cheating_detected' => count($anti_cheat_events) > 0
        ], JSON_UNESCAPED_UNICODE);

    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode([
            'success' => false,
            'message' => 'خطأ في تقديم الاختبار: ' . $e->getMessage()
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// GRADE EXAM (Technical/Manager)
// ============================================
if ($action === 'grade' && $method === 'POST') {
    if (!hasPermission($user_role, 'grade')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بتصحيح الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    
    $attempt_id = (int)($data['attempt_id'] ?? 0);
    $grades = $data['grades'] ?? []; // [question_id => marks_awarded]

    foreach ($grades as $question_id => $marks) {
        $stmt = $conn->prepare("
            UPDATE exam_answers 
            SET marks_awarded = ?, graded_by = ?, graded_at = NOW()
            WHERE attempt_id = ? AND question_id = ?
        ");
        $stmt->bind_param('diii', $marks, $user_id, $attempt_id, $question_id);
        $stmt->execute();
        $stmt->close();
    }

    // Calculate total score
    $total_score = calculateTotalScore($conn, $attempt_id);

    // Update attempt with final score
    $stmt = $conn->prepare("
        UPDATE exam_attempts 
        SET obtained_marks = ?, graded_at = NOW(), status = 'graded'
        WHERE attempt_id = ?
    ");
    $stmt->bind_param('di', $total_score, $attempt_id);
    $stmt->execute();
    $stmt->close();

    // Check if student passed and generate certificate
    $stmt = $conn->prepare("
        SELECT ea.*, e.passing_marks, e.course_id, ea.student_id
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.exam_id
        WHERE ea.attempt_id = ?
    ");
    $stmt->bind_param('i', $attempt_id);
    $stmt->execute();
    $attempt = stmt_get_result_compat($stmt)->fetch_assoc();
    $stmt->close();

    if ($attempt['obtained_marks'] >= $attempt['passing_marks']) {
        // Update enrollment status to completed
        $stmt = $conn->prepare("
            UPDATE enrollments 
            SET status = 'completed', completed_at = NOW()
            WHERE user_id = ? AND course_id = ?
        ");
        $stmt->bind_param('ii', $attempt['student_id'], $attempt['course_id']);
        $stmt->execute();
        $stmt->close();

        // Trigger certificate generation
        generateCertificateAutomatically($conn, $attempt['student_id'], $attempt['course_id']);
    }

    echo json_encode([
        'success' => true,
        'message' => 'تم تصحيح الاختبار بنجاح',
        'total_score' => $total_score,
        'passed' => $attempt['obtained_marks'] >= $attempt['passing_marks'],
        'certificate_generated' => $attempt['obtained_marks'] >= $attempt['passing_marks']
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// GET RESULTS (Student)
// ============================================
if ($action === 'my_results' && $method === 'GET') {
    if ($user_role !== 'student') {
        echo json_encode(['success' => false, 'message' => 'الطلاب فقط'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("
        SELECT 
            ea.attempt_id, ea.exam_id, ea.start_time, ea.end_time,
            ea.obtained_marks, ea.status,
            e.title as exam_title, e.total_marks, e.passing_marks,
            c.title as course_title
        FROM exam_attempts ea
        JOIN exams e ON ea.exam_id = e.exam_id
        JOIN courses c ON e.course_id = c.course_id
        WHERE ea.student_id = ?
        ORDER BY ea.start_time DESC
    ");
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $results = stmt_get_result_compat($stmt)->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode([
        'success' => true,
        'results' => $results
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// LIST EXAMS (Manager/Technical/Trainer)
// ============================================
if ($action === 'list' && $method === 'GET') {
    if (!hasPermission($user_role, 'view_all') && !hasPermission($user_role, 'view_assigned')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بعرض الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $where_clause = "1=1";
    $params = [];
    $types = "";

    if ($user_role === 'trainer') {
        $where_clause .= " AND created_by = ?";
        $params[] = $user_id;
        $types .= "i";
    }

    $stmt = $conn->prepare("
        SELECT e.*, c.title as course_title, u.full_name as creator_name,
               (SELECT COUNT(*) FROM exam_attempts WHERE exam_id = e.exam_id) as attempts_count
        FROM exams e
        LEFT JOIN courses c ON e.course_id = c.course_id
        LEFT JOIN users u ON e.created_by = u.id
        WHERE $where_clause
        ORDER BY e.created_at DESC
    ");

    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }

    $stmt->execute();
    $exams = stmt_get_result_compat($stmt)->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    echo json_encode(['success' => true, 'exams' => $exams], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// DELETE EXAM (Manager)
// ============================================
if ($action === 'delete' && $method === 'POST') {
    if (!hasPermission($user_role, 'delete')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك بحذف الاختبارات'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $exam_id = (int)($_POST['exam_id'] ?? 0);
    
    if ($exam_id <= 0) {
        echo json_encode(['success' => false, 'message' => 'معرف الاختبار غير صحيح'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Check if exam has attempts
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM exam_attempts WHERE exam_id = ?");
    $stmt->bind_param('i', $exam_id);
    $stmt->execute();
    $result = stmt_get_result_compat($stmt)->fetch_assoc();
    $stmt->close();

    if ($result['count'] > 0) {
        echo json_encode(['success' => false, 'message' => 'لا يمكن حذف اختبار تم إجراؤه من قبل الطلاب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $conn->begin_transaction();
    try {
        $stmt = $conn->prepare("DELETE FROM exam_questions WHERE exam_id = ?");
        $stmt->bind_param('i', $exam_id);
        $stmt->execute();
        $stmt->close();

        $stmt = $conn->prepare("DELETE FROM exams WHERE exam_id = ?");
        $stmt->bind_param('i', $exam_id);
        $stmt->execute();
        $stmt->close();

        $conn->commit();
        echo json_encode(['success' => true, 'message' => 'تم حذف الاختبار بنجاح'], JSON_UNESCAPED_UNICODE);
    } catch (Exception $e) {
        $conn->rollback();
        echo json_encode(['success' => false, 'message' => 'خطأ في الحذف'], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// ============================================
// GENERATE AI QUESTIONS (Manager/Technical)
// ============================================
if ($action === 'generate_ai_questions' && $method === 'POST') {
    if (!hasPermission($user_role, 'create')) {
        echo json_encode(['success' => false, 'message' => 'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    $topic = $data['topic'] ?? '';
    $count = (int)($data['count'] ?? 5);
    $difficulty = $data['difficulty'] ?? 'medium';

    if (empty($topic)) {
        echo json_encode(['success' => false, 'message' => 'الموضوع مطلوب'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // Mock AI Generation (Replace with real API call if available)
    // This simulates an AI response based on the topic
    $questions = [];
    $types = ['mcq', 'true_false'];
    
    for ($i = 0; $i < $count; $i++) {
        $type = $types[array_rand($types)];
        $q = [
            'text' => "سؤال تجريبي عن $topic رقم " . ($i + 1),
            'type' => $type,
            'marks' => 1
        ];

        if ($type === 'mcq') {
            $q['options'] = ["خيار 1", "خيار 2", "خيار 3", "خيار 4"];
            $q['correct_answer'] = "خيار 1";
        } else {
            $q['options'] = ["صح", "خطأ"];
            $q['correct_answer'] = "صح";
        }
        
        $questions[] = $q;
    }

    echo json_encode([
        'success' => true, 
        'questions' => $questions,
        'message' => 'تم توليد الأسئلة بنجاح باستخدام الذكاء الاصطناعي (محاكاة)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ============================================
// HELPER FUNCTIONS
// ============================================

function notifyStudents($conn, $exam_id) {
    $stmt = $conn->prepare("
        SELECT DISTINCT e.user_id
        FROM enrollments e
        JOIN exams ex ON e.course_id = ex.course_id
        WHERE ex.exam_id = ? AND e.status = 'active'
    ");
    $stmt->bind_param('i', $exam_id);
    $stmt->execute();
    $students = stmt_get_result_compat($stmt)->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    foreach ($students as $student) {
        $stmt = $conn->prepare("
            INSERT INTO notifications (user_id, title, message, type, link, created_at)
            VALUES (?, 'اختبار جديد', 'تم نشر اختبار جديد في أحد دوراتك', 'exam', '/exams/{$exam_id}', NOW())
        ");
        $stmt->bind_param('i', $student['user_id']);
        $stmt->execute();
        $stmt->close();
    }
}

function autoGradeExam($conn, $attempt_id) {
    // Get all MCQ/True-False questions
    $stmt = $conn->prepare("
        SELECT ea.answer_id, ea.question_id, ea.answer_text, 
               eq.correct_answer, eq.marks
        FROM exam_answers ea
        JOIN exam_questions eq ON ea.question_id = eq.question_id
        WHERE ea.attempt_id = ? AND eq.question_type IN ('mcq', 'true_false')
    ");
    $stmt->bind_param('i', $attempt_id);
    $stmt->execute();
    $answers = stmt_get_result_compat($stmt)->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $total_score = 0;

    foreach ($answers as $answer) {
        $marks_awarded = ($answer['answer_text'] === $answer['correct_answer']) ? $answer['marks'] : 0;
        $total_score += $marks_awarded;

        $stmt = $conn->prepare("
            UPDATE exam_answers 
            SET marks_awarded = ?, graded_at = NOW()
            WHERE answer_id = ?
        ");
        $stmt->bind_param('di', $marks_awarded, $answer['answer_id']);
        $stmt->execute();
        $stmt->close();
    }

    return $total_score;
}

function calculateTotalScore($conn, $attempt_id) {
    $stmt = $conn->prepare("
        SELECT SUM(marks_awarded) as total
        FROM exam_answers
        WHERE attempt_id = ?
    ");
    $stmt->bind_param('i', $attempt_id);
    $stmt->execute();
    $result = stmt_get_result_compat($stmt)->fetch_assoc();
    $stmt->close();

    return $result['total'] ?? 0;
}

function generateCertificateAutomatically($conn, $student_id, $course_id) {
    // Check if certificate already exists
    $stmt = $conn->prepare("
        SELECT certificate_id 
        FROM certificates 
        WHERE user_id = ? AND course_id = ?
    ");
    $stmt->bind_param('ii', $student_id, $course_id);
    $stmt->execute();
    $existing = stmt_get_result_compat($stmt)->fetch_assoc();
    $stmt->close();

    if ($existing) {
        return; // Certificate already exists
    }

    // Trigger certificate generation via internal request
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

    curl_exec($ch);
    curl_close($ch);
}

echo json_encode(['success' => false, 'message' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
?>
