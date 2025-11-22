<?php
/**
 * Trainer Helper Functions
 * دوال مساعدة للمدرب - بديل PHP لـ trainer-features.js
 * 
 * Hybrid System: Direct PHP queries + Python API for charts
 */

class TrainerHelper {
    private $conn;
    private $userId;
    
    public function __construct($connection, $trainerId) {
        $this->conn = $connection;
        $this->userId = $trainerId;
    }
    
    /**
     * Get all trainer courses
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
                    c.status,
                    COUNT(DISTINCT e.user_id) as students_count,
                    AVG(e.progress) as avg_progress
                FROM courses c
                LEFT JOIN enrollments e ON c.course_id = e.course_id AND e.status = 'active'
                WHERE c.trainer_id = ?
                GROUP BY c.course_id
                ORDER BY c.start_date DESC
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getMyCourses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get course details with statistics
     */
    public function getCourseDetails($courseId) {
        try {
            $stmt = $this->conn->prepare("
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
            $stmt->bind_param("ii", $courseId, $this->userId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Trainer getCourseDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get students in a course or all students
     */
    public function getMyStudents($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
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
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE c.course_id = ? AND c.trainer_id = ?
                    ORDER BY u.full_name
                ");
                $stmt->bind_param("ii", $courseId, $this->userId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT DISTINCT
                        u.user_id,
                        u.full_name,
                        u.email,
                        u.photo,
                        COUNT(e.course_id) as courses_count,
                        AVG(e.final_grade) as avg_grade
                    FROM enrollments e
                    JOIN users u ON e.user_id = u.user_id
                    JOIN courses c ON e.course_id = c.course_id
                    WHERE c.trainer_id = ?
                    GROUP BY u.user_id
                    ORDER BY u.full_name
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getMyStudents Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get student profile with all details
     */
    public function getStudentProfile($studentId) {
        try {
            $stmt = $this->conn->prepare("
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
            $stmt->bind_param("iii", $this->userId, $this->userId, $studentId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Trainer getStudentProfile Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Get attendance statistics for a course
     */
    public function getCourseAttendanceStats($courseId, $date = null) {
        try {
            if ($date) {
                $stmt = $this->conn->prepare("
                    SELECT 
                        u.user_id,
                        u.full_name,
                        a.status,
                        a.attendance_date
                    FROM users u
                    JOIN enrollments e ON u.user_id = e.user_id
                    LEFT JOIN attendance a ON u.user_id = a.student_id 
                        AND a.course_id = ? 
                        AND DATE(a.attendance_date) = ?
                    WHERE e.course_id = ? AND e.status = 'active'
                    ORDER BY u.full_name
                ");
                $stmt->bind_param("isi", $courseId, $date, $courseId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT 
                        DATE(a.attendance_date) as date,
                        COUNT(CASE WHEN a.status = 'present' THEN 1 END) as present,
                        COUNT(CASE WHEN a.status = 'absent' THEN 1 END) as absent,
                        COUNT(*) as total
                    FROM attendance a
                    WHERE a.course_id = ?
                    GROUP BY DATE(a.attendance_date)
                    ORDER BY a.attendance_date DESC
                    LIMIT 30
                ");
                $stmt->bind_param("i", $courseId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseAttendanceStats Error: " . $e->getMessage());
            return [];
        }
    }
    
    
    /**
     * Get grades for a course
     */
    public function getCourseGrades($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.user_id,
                    u.full_name,
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
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseGrades Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update student grade
     */
    public function updateGrade($courseId, $studentId, $gradeType, $grade) {
        try {
            $column = $gradeType === 'midterm' ? 'midterm_grade' : 'final_grade';
            
            $stmt = $this->conn->prepare("
                UPDATE enrollments 
                SET $column = ? 
                WHERE course_id = ? AND user_id = ?
            ");
            $stmt->bind_param("dii", $grade, $courseId, $studentId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Trainer updateGrade Error: " . $e->getMessage());
            return false;
        }
    }
    
    
    
    /**
     * Get course materials
     */
    public function getCourseMaterials($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT * FROM course_materials 
                WHERE course_id = ? 
                ORDER BY upload_date DESC
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseMaterials Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Upload course material
     */
    public function uploadMaterial($courseId, $title, $description, $filePath, $fileType) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO course_materials (course_id, title, description, file_path, file_type, uploaded_by, upload_date)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issssi", $courseId, $title, $description, $filePath, $fileType, $this->userId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Trainer uploadMaterial Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get attendance for course and date
     */
    public function getCourseAttendance($courseId, $date = null) {
        try {
            $date = $date ?? date('Y-m-d');
            $stmt = $this->conn->prepare("
                SELECT 
                    u.user_id,
                    u.full_name,
                    u.photo,
                    a.status,
                    a.notes,
                    a.recorded_at
                FROM enrollments e
                JOIN users u ON e.user_id = u.user_id
                LEFT JOIN attendance a ON e.user_id = a.student_id 
                    AND e.course_id = a.course_id 
                    AND DATE(a.attendance_date) = ?
                WHERE e.course_id = ? AND e.status = 'active'
                ORDER BY u.full_name
            ");
            $stmt->bind_param("si", $date, $courseId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseAttendance Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Record attendance
     */
    public function recordAttendance($courseId, $studentId, $date, $status, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO attendance (course_id, student_id, attendance_date, status, notes, recorded_by)
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE status = ?, notes = ?
            ");
            $stmt->bind_param("iisssiss", $courseId, $studentId, $date, $status, $notes, $this->userId, $status, $notes);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Trainer recordAttendance Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get course assignments
     */
    public function getCourseAssignments($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
                    SELECT 
                        a.*,
                        c.course_name,
                        COUNT(s.submission_id) as total_submissions,
                        COUNT(CASE WHEN s.graded = 1 THEN 1 END) as graded_submissions
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.course_id
                    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
                    WHERE a.course_id = ? AND c.trainer_id = ?
                    GROUP BY a.assignment_id
                    ORDER BY a.due_date DESC
                ");
                $stmt->bind_param("ii", $courseId, $this->userId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT 
                        a.*,
                        c.course_name,
                        COUNT(s.submission_id) as total_submissions,
                        COUNT(CASE WHEN s.graded = 1 THEN 1 END) as graded_submissions
                    FROM assignments a
                    JOIN courses c ON a.course_id = c.course_id
                    LEFT JOIN assignment_submissions s ON a.assignment_id = s.assignment_id
                    WHERE c.trainer_id = ?
                    GROUP BY a.assignment_id
                    ORDER BY a.due_date DESC
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseAssignments Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get assignment submissions
     */
    public function getAssignmentSubmissions($assignmentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    s.*,
                    u.full_name,
                    u.photo,
                    a.title as assignment_title,
                    a.due_date
                FROM assignment_submissions s
                JOIN users u ON s.student_id = u.user_id
                JOIN assignments a ON s.assignment_id = a.assignment_id
                WHERE s.assignment_id = ?
                ORDER BY s.submitted_at DESC
            ");
            $stmt->bind_param("i", $assignmentId);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getAssignmentSubmissions Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Grade assignment submission
     */
    public function gradeSubmission($submissionId, $grade, $feedback) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE assignment_submissions 
                SET grade = ?, feedback = ?, graded = 1, graded_at = NOW(), graded_by = ?
                WHERE submission_id = ?
            ");
            $stmt->bind_param("dsii", $grade, $feedback, $this->userId, $submissionId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Trainer gradeSubmission Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get course announcements
     */
    public function getCourseAnnouncements($courseId = null) {
        try {
            if ($courseId) {
                $stmt = $this->conn->prepare("
                    SELECT a.*, c.course_name
                    FROM announcements a
                    JOIN courses c ON a.course_id = c.course_id
                    WHERE a.course_id = ? AND c.trainer_id = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->bind_param("ii", $courseId, $this->userId);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT a.*, c.course_name
                    FROM announcements a
                    JOIN courses c ON a.course_id = c.course_id
                    WHERE c.trainer_id = ?
                    ORDER BY a.created_at DESC
                ");
                $stmt->bind_param("i", $this->userId);
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Trainer getCourseAnnouncements Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create announcement
     */
    public function createAnnouncement($courseId, $title, $content, $priority = 'normal') {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO announcements (course_id, title, content, priority, created_by, created_at)
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("isssi", $courseId, $title, $content, $priority, $this->userId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Trainer createAnnouncement Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get trainer statistics
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total courses
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM courses WHERE trainer_id = ?");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['total_courses'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Active students
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT e.user_id) as count 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.course_id 
                WHERE c.trainer_id = ? AND e.status = 'active'
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['active_students'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Average grade
            $stmt = $this->conn->prepare("
                SELECT AVG(e.final_grade) as avg 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.course_id 
                WHERE c.trainer_id = ? AND e.final_grade IS NOT NULL
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['avg_grade'] = round($stmt->get_result()->fetch_assoc()['avg'] ?? 0, 2);
            
            // Pending grades
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM enrollments e 
                JOIN courses c ON e.course_id = c.course_id 
                WHERE c.trainer_id = ? AND e.final_grade IS NULL AND e.status = 'active'
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['pending_grades'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Total materials
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.course_id
                WHERE c.trainer_id = ?
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['total_materials'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Pending assignments
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT s.submission_id) as count 
                FROM assignment_submissions s
                JOIN assignments a ON s.assignment_id = a.assignment_id
                JOIN courses c ON a.course_id = c.course_id
                WHERE c.trainer_id = ? AND s.graded = 0
            ");
            $stmt->bind_param("i", $this->userId);
            $stmt->execute();
            $stats['pending_assignments'] = $stmt->get_result()->fetch_assoc()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Trainer getStatistics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get comprehensive course report
     */
    public function getCourseReport($courseId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT c.course_name, c.course_id,
                       COUNT(DISTINCT e.user_id) as total_students,
                       AVG(e.final_grade) as avg_grade,
                       AVG(CASE WHEN a.status = 'present' THEN 100 ELSE 0 END) as avg_attendance
                FROM courses c
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                LEFT JOIN attendance a ON c.course_id = a.course_id
                WHERE c.course_id = ? AND c.trainer_id = ?
                GROUP BY c.course_id
            ");
            $stmt->bind_param("ii", $courseId, $this->userId);
            $stmt->execute();
            $report = $stmt->get_result()->fetch_assoc();
            
            if (!$report) return null;
            
            // Get top 5 students
            $stmt = $this->conn->prepare("
                SELECT u.full_name, e.final_grade
                FROM enrollments e
                JOIN users u ON e.user_id = u.user_id
                WHERE e.course_id = ? AND e.final_grade IS NOT NULL
                ORDER BY e.final_grade DESC
                LIMIT 5
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $report['top_students'] = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            
            // Get grade distribution
            $stmt = $this->conn->prepare("
                SELECT 
                    SUM(CASE WHEN final_grade >= 90 THEN 1 ELSE 0 END) as excellent,
                    SUM(CASE WHEN final_grade >= 80 AND final_grade < 90 THEN 1 ELSE 0 END) as very_good,
                    SUM(CASE WHEN final_grade >= 70 AND final_grade < 80 THEN 1 ELSE 0 END) as good,
                    SUM(CASE WHEN final_grade >= 60 AND final_grade < 70 THEN 1 ELSE 0 END) as pass,
                    SUM(CASE WHEN final_grade < 60 THEN 1 ELSE 0 END) as fail
                FROM enrollments
                WHERE course_id = ? AND final_grade IS NOT NULL
            ");
            $stmt->bind_param("i", $courseId);
            $stmt->execute();
            $distribution = $stmt->get_result()->fetch_assoc();
            $report['grade_distribution'] = [
                $distribution['excellent'] ?? 0,
                $distribution['very_good'] ?? 0,
                $distribution['good'] ?? 0,
                $distribution['pass'] ?? 0,
                $distribution['fail'] ?? 0
            ];
            
            return $report;
        } catch (Exception $e) {
            error_log("Trainer getCourseReport Error: " . $e->getMessage());
            return null;
        }
    }
}
?>
