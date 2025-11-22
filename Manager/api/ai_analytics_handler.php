<?php
header('Content-Type: application/json; charset=utf-8');

require_once '../../includes/config.php';
require_once '../../includes/session_security.php';

// --- Response Helper ---
function json_response($success, $data = []) {
    $response = ['success' => (bool)$success];
    if (is_string($data)) {
        $response['message'] = $data;
    } else {
        $response = array_merge($response, $data);
    }
    echo json_encode($response);
    exit;
}

// --- Security Check ---
if ($userRole !== 'manager') {
    json_response(false, 'غير مصرح لك بالقيام بهذه العملية.');
}

$action = $_GET['action'] ?? null;

try {
    switch ($action) {
        case 'enrollment_over_time':
            $sql = "SELECT DATE_FORMAT(created_at, '%Y-%m') as month, COUNT(*) as count 
                    FROM enrollments 
                    GROUP BY month 
                    ORDER BY month ASC 
                    LIMIT 12";
            $result = $conn->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, ['data' => $data]);
            break;

        case 'course_popularity':
            $sql = "SELECT c.title, COUNT(e.id) as student_count 
                    FROM courses c
                    JOIN enrollments e ON c.course_id = e.course_id
                    GROUP BY c.course_id 
                    ORDER BY student_count DESC
                    LIMIT 10";
            $result = $conn->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, ['data' => $data]);
            break;

        case 'student_performance':
            $sql = "SELECT 
                        CASE 
                            WHEN grade_value >= 90 THEN 'A (90-100)'
                            WHEN grade_value >= 80 THEN 'B (80-89)'
                            WHEN grade_value >= 70 THEN 'C (70-79)'
                            WHEN grade_value >= 60 THEN 'D (60-69)'
                            ELSE 'F (<60)'
                        END as grade_range,
                        COUNT(*) as count
                    FROM grades
                    GROUP BY grade_range
                    ORDER BY grade_range ASC";
            $result = $conn->query($sql);
            $data = $result->fetch_all(MYSQLI_ASSOC);
            json_response(true, ['data' => $data]);
            break;
        
        case 'ai_dropout_analysis':
            // This is a simulated AI analysis.
            // It identifies students with an average grade below a certain threshold.
            $sql = "SELECT u.full_name_ar, u.email, AVG(g.grade_value) as avg_grade
                    FROM users u
                    JOIN grades g ON u.user_id = g.user_id
                    WHERE u.role = 'student'
                    GROUP BY u.user_id
                    HAVING avg_grade < 70
                    ORDER BY avg_grade ASC";
            
            $result = $conn->query($sql);
            $at_risk_students = $result->fetch_all(MYSQLI_ASSOC);
            
            $count = count($at_risk_students);
            $insight = "تم تحديد **{$count}** طالبًا معرضين لخطر التسرب بناءً على متوسط درجاتهم المنخفض (أقل من 70%).\n\n";
            
            if ($count > 0) {
                $insight .= "**توصية:** التواصل مع هؤلاء الطلاب لتقديم الدعم الأكاديمي اللازم. الطلاب هم:\n";
                foreach ($at_risk_students as $student) {
                    $name = htmlspecialchars($student['full_name_ar']);
                    $grade = number_format($student['avg_grade'], 1);
                    $insight .= "- **{$name}** (متوسط الدرجات: {$grade}%)\n";
                }
            } else {
                $insight .= "لم يتم العثور على طلاب معرضين للخطر حاليًا. هذا مؤشر جيد على الأداء العام!";
            }

            json_response(true, [
                'report_title' => 'تحليل الذكاء الاصطناعي لأداء الطلاب',
                'insight' => $insight,
                'at_risk_count' => $count
            ]);
            break;

        default:
            json_response(false, 'الإجراء المطلوب غير معروف.');
            break;
    }
} catch (Exception $e) {
    error_log("AI Analytics Error: " . $e->getMessage());
    json_response(false, 'حدث خطأ في الخادم: ' . $e->getMessage());
}
?>
