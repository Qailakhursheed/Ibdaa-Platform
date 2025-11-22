<?php
/**
 * Student Helper Functions
 * دوال مساعدة للطالب - بديل PHP لـ student-features.js
 * 
 * Hybrid System: Direct PHP queries + Python API for charts
 */

class StudentHelper {
    private $conn;
    private $userId;
    
    public function __construct($connection, $studentId) {
        $this->conn = $connection;
        $this->userId = $studentId;
    }
    
    /**
     * Get all student courses
     */
    public function getMyCourses() {
        try {
            $stmt = $this->conn->prepare("
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
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getMyCourses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get course details
     */
    public function getCourseDetails($courseId) {
        try {
            $stmt = $this->conn->prepare("
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
            $stmt->bind_param("ii", $courseId, $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Student getCourseDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get student grades
     */
    public function getMyGrades($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
                    SELECT 
                        c.course_name,
                        c.course_id,
                        e.midterm_grade,
                        e.final_grade,
                        e.status,
                        COALESCE(
                            (SELECT AVG(grade) FROM assignment_submissions 
                             WHERE student_id = e.user_id 
                             AND assignment_id IN (SELECT assignment_id FROM assignments WHERE course_id = c.course_id)
                             AND graded = 1), 
                            0
                        ) as assignments_grade,
                        0 as quizzes_grade,
                        (
                            COALESCE(
                                (SELECT AVG(grade) FROM assignment_submissions 
                                 WHERE student_id = e.user_id 
                                 AND assignment_id IN (SELECT assignment_id FROM assignments WHERE course_id = c.course_id)
                                 AND graded = 1), 
                                0
                            ) * 0.2 +
                            COALESCE(e.midterm_grade, 0) +
                            COALESCE(e.final_grade, 0)
                        ) as total_grade
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE e.user_id = ? AND c.course_id = ?
                ");
                $stmt->bind_param("ii", $this->userId, $courseId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT 
                        c.course_name,
                        c.course_id,
                        e.midterm_grade,
                        e.final_grade,
                        e.status,
                        COALESCE(
                            (SELECT AVG(grade) FROM assignment_submissions 
                             WHERE student_id = e.user_id 
                             AND assignment_id IN (SELECT assignment_id FROM assignments WHERE course_id = c.course_id)
                             AND graded = 1), 
                            0
                        ) as assignments_grade,
                        0 as quizzes_grade,
                        (
                            COALESCE(
                                (SELECT AVG(grade) FROM assignment_submissions 
                                 WHERE student_id = e.user_id 
                                 AND assignment_id IN (SELECT assignment_id FROM assignments WHERE course_id = c.course_id)
                                 AND graded = 1), 
                                0
                            ) * 0.2 +
                            COALESCE(e.midterm_grade, 0) +
                            COALESCE(e.final_grade, 0)
                        ) as total_grade
                    FROM enrollments e
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE e.user_id = ?
                    ORDER BY e.enrollment_date DESC
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getMyGrades Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate GPA
     */
    public function getGPA() {
        try {
            $stmt = $this->conn->prepare("
                SELECT AVG(final_grade) as gpa, COUNT(*) as courses_count
                FROM enrollments
                WHERE user_id = ? AND final_grade IS NOT NULL
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return [
                'gpa' => round($result['gpa'] ?? 0, 2),
                'courses_count' => $result['courses_count']
            ];
        } catch (Exception $e) {
            error_log("Student getGPA Error: " . $e->getMessage());
            return ['gpa' => 0, 'courses_count' => 0];
        }
    }
    
    /**
     * Get attendance records
     */
    public function getMyAttendance($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
                    SELECT 
                        a.attendance_date,
                        a.status,
                        c.course_name
                    FROM attendance a
                    JOIN courses c ON a.course_id = c.course_id
                    WHERE a.student_id = ? AND a.course_id = ?
                    ORDER BY a.attendance_date DESC
                ");
                $stmt->bind_param("ii", $this->userId, $courseId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT 
                        a.attendance_date,
                        a.status,
                        c.course_name,
                        c.course_id
                    FROM attendance a
                    JOIN courses c ON a.course_id = c.course_id
                    WHERE a.student_id = ?
                    ORDER BY a.attendance_date DESC
                    LIMIT 50
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getMyAttendance Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Calculate attendance rate
     */
    public function getAttendanceRate($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
                    SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(*) as total
                    FROM attendance
                    WHERE student_id = ? AND course_id = ?
                ");
                $stmt->bind_param("ii", $this->userId, $courseId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT 
                        COUNT(CASE WHEN status = 'present' THEN 1 END) as present,
                        COUNT(*) as total
                    FROM attendance
                    WHERE student_id = ?
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            
            $total = $result['total'] ?? 0;
            $present = $result['present'] ?? 0;
            $rate = $total > 0 ? ($present / $total * 100) : 0;
            
            return [
                'present' => $present,
                'total' => $total,
                'rate' => round($rate, 1)
            ];
        } catch (Exception $e) {
            error_log("Student getAttendanceRate Error: " . $e->getMessage());
            return ['present' => 0, 'total' => 0, 'rate' => 0];
        }
    }
    
    /**
     * Get assignments
     */
    public function getMyAssignments($courseId = null, $status = null) {
        try {
            $query = "
                SELECT 
                    a.assignment_id,
                    a.title,
                    a.description,
                    a.due_date,
                    s.submission_date,
                    s.grade,
                    s.status,
                    c.course_name
                FROM assignments a
                JOIN courses c ON a.course_id = c.course_id
                LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id AND s.student_id = ?
                WHERE c.course_id IN (SELECT course_id FROM enrollments WHERE user_id = ?)
            ";
            
            $params = [$this->userId, $this->userId];
            $types = "ii";
            
            if ($courseId) {
                $query .= " AND c.course_id = ?";
                $params[] = $courseId;
                $types .= "i";
            }
            
            if ($status) {
                $query .= " AND s.status = ?";
                $params[] = $status;
                $types .= "s";
            }
            
            $query .= " ORDER BY a.due_date DESC";
            
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getMyAssignments Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get course materials
     */
    public function getCourseMaterials($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    m.material_id,
                    m.title,
                    m.description,
                    m.file_path,
                    m.file_type,
                    m.upload_date,
                    c.course_name
                FROM course_materials m
                JOIN courses c ON m.course_id = c.course_id
                WHERE m.course_id = ?
                ORDER BY m.upload_date DESC
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getCourseMaterials Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get student schedule
     */
    public function getMySchedule() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.course_name,
                    s.day_of_week,
                    s.start_time,
                    s.end_time,
                    s.room
                FROM course_schedule s
                JOIN courses c ON s.course_id = c.course_id
                JOIN enrollments e ON c.course_id = e.course_id
                WHERE e.user_id = ? AND e.status = 'active'
                ORDER BY 
                    FIELD(s.day_of_week, 'Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'),
                    s.start_time
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getMySchedule Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get payment history
     */
    public function getPaymentHistory() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    t.transaction_id,
                    t.amount,
                    t.transaction_date,
                    t.payment_method,
                    t.status,
                    t.description
                FROM transactions t
                WHERE t.user_id = ?
                ORDER BY t.transaction_date DESC
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Student getPaymentHistory Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get account balance
     */
    public function getAccountBalance() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    COALESCE(SUM(CASE WHEN type = 'credit' THEN amount ELSE -amount END), 0) as balance
                FROM transactions
                WHERE user_id = ? AND status = 'completed'
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            return round($result['balance'] ?? 0, 2);
        } catch (Exception $e) {
            error_log("Student getAccountBalance Error: " . $e->getMessage());
            return 0;
        }
    }
}
?>
