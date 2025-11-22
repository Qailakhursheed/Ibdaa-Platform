<?php
/**
 * Technical Helper Functions
 * دوال مساعدة للإدارة الفنية - بديل PHP لـ technical-features.js
 * 
 * Hybrid System: Direct PHP queries + Python API for charts
 */

class TechnicalHelper {
    private $conn;
    private $userId;
    
    public function __construct($connection, $userId) {
        $this->conn = $connection;
        $this->userId = $userId;
    }
    
    /**
     * Get all courses
     */
    public function getAllCourses() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.*,
                    u.full_name as trainer_name,
                    COUNT(DISTINCT e.user_id) as students_count
                FROM courses c
                LEFT JOIN users u ON c.trainer_id = u.user_id
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                GROUP BY c.course_id
                ORDER BY c.start_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getAllCourses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all students
     */
    public function getAllStudents() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    COUNT(DISTINCT e.course_id) as enrolled_courses,
                    AVG(e.final_grade) as avg_grade
                FROM users u
                LEFT JOIN enrollments e ON u.user_id = e.user_id
                WHERE u.role = 'student'
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getAllStudents Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all trainers
     */
    public function getAllTrainers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    COUNT(DISTINCT c.course_id) as courses_count,
                    COUNT(DISTINCT e.user_id) as students_count,
                    AVG(ev.rating) as avg_rating
                FROM users u
                LEFT JOIN courses c ON u.user_id = c.trainer_id
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                LEFT JOIN trainer_evaluations ev ON u.user_id = ev.trainer_id
                WHERE u.role = 'trainer'
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getAllTrainers Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all materials
     */
    public function getAllMaterials() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    cm.*,
                    c.course_name,
                    u.full_name as uploader_name
                FROM course_materials cm
                JOIN courses c ON cm.course_id = c.course_id
                LEFT JOIN users u ON cm.uploaded_by = u.user_id
                ORDER BY cm.upload_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getAllMaterials Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all ID card requests
     */
    public function getIdCardRequests() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.user_id,
                    u.full_name,
                    u.email,
                    u.photo,
                    u.id_number,
                    u.created_at,
                    COUNT(DISTINCT e.course_id) as enrolled_courses
                FROM users u
                LEFT JOIN enrollments e ON u.user_id = e.user_id
                WHERE u.role = 'student' AND u.id_card_status = 'pending'
                GROUP BY u.user_id
                ORDER BY u.created_at DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getIdCardRequests Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get technical statistics
     */
    public function getStatistics() {
        try {
            $stats = [];
            
            // Total courses
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM courses");
            $stmt->execute();
            $stats['total_courses'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Active courses
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
            $stmt->execute();
            $stats['active_courses'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Pending courses
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM courses WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_courses'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Total students
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
            $stmt->execute();
            $stats['total_students'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Active students
            $stmt = $this->conn->prepare("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM enrollments 
                WHERE status = 'active'
            ");
            $stmt->execute();
            $stats['active_students'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Total trainers
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
            $stmt->execute();
            $stats['total_trainers'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Pending requests (ID card approvals)
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE status = 'pending'");
            $stmt->execute();
            $stats['pending_requests'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Support tickets (pending)
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM support_tickets 
                WHERE status IN ('open', 'pending')
            ");
            $stmt->execute();
            $stats['support_tickets'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
            
            // Pending reviews/evaluations
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM trainer_evaluations 
                WHERE reviewed = 0 OR reviewed IS NULL
            ");
            $stmt->execute();
            $stats['pending_reviews'] = $stmt->get_result()->fetch_assoc()['count'] ?? 0;
            
            // Total materials
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM course_materials");
            $stmt->execute();
            $stats['total_materials'] = $stmt->get_result()->fetch_assoc()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("Technical getStatistics Error: " . $e->getMessage());
            return [
                'total_courses' => 0,
                'active_courses' => 0,
                'pending_courses' => 0,
                'total_students' => 0,
                'active_students' => 0,
                'total_trainers' => 0,
                'pending_requests' => 0,
                'support_tickets' => 0,
                'pending_reviews' => 0,
                'total_materials' => 0
            ];
        }
    }
    
    /**
     * Get evaluations
     */
    public function getAllEvaluations() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    e.*,
                    u.full_name as student_name,
                    c.course_name,
                    t.full_name as trainer_name
                FROM evaluations e
                JOIN users u ON e.student_id = u.user_id
                JOIN courses c ON e.course_id = c.course_id
                JOIN users t ON c.trainer_id = t.user_id
                ORDER BY e.submitted_at DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Technical getAllEvaluations Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update ID card status
     */
    public function updateIdCardStatus($userId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET status = ? 
                WHERE user_id = ?
            ");
            $stmt->bind_param("si", $status, $userId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper updateIdCardStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all announcements
     */
    public function getAllAnnouncements() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    a.*,
                    c.course_name,
                    u.full_name as created_by_name
                FROM announcements a
                LEFT JOIN courses c ON a.course_id = c.course_id
                LEFT JOIN users u ON a.created_by = u.user_id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getAllAnnouncements Error: " . $e->getMessage());
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
            error_log("TechnicalHelper createAnnouncement Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all certificates
     */
    public function getAllCertificates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.*,
                    c.issued_date as issue_date,
                    u.full_name as student_name,
                    u.email,
                    u.photo,
                    co.course_name,
                    e.final_grade as grade
                FROM certificates c
                JOIN users u ON c.user_id = u.user_id
                JOIN courses co ON c.course_id = co.course_id
                LEFT JOIN enrollments e ON c.user_id = e.user_id AND c.course_id = e.course_id
                ORDER BY c.issued_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getAllCertificates Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Issue certificate
     */
    public function issueCertificate($userId, $courseId, $certificateNumber) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO certificates (user_id, course_id, certificate_number, issued_date, issued_by)
                VALUES (?, ?, ?, NOW(), ?)
            ");
            $stmt->bind_param("iisi", $userId, $courseId, $certificateNumber, $this->userId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper issueCertificate Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all support tickets
     */
    public function getAllSupportTickets() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    t.*,
                    u.full_name as user_name,
                    u.email,
                    u.photo,
                    u.role,
                    (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = t.ticket_id) as replies_count
                FROM support_tickets t
                JOIN users u ON t.user_id = u.user_id
                ORDER BY 
                    CASE WHEN t.status = 'open' THEN 1 ELSE 2 END,
                    t.priority = 'urgent' DESC,
                    t.created_at DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getAllSupportTickets Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update support ticket status
     */
    public function updateTicketStatus($ticketId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE support_tickets 
                SET status = ?, updated_at = NOW() 
                WHERE ticket_id = ?
            ");
            $stmt->bind_param("si", $status, $ticketId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper updateTicketStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all pending requests
     */
    public function getAllRequests() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    r.*,
                    u.full_name as user_name,
                    u.email,
                    u.photo,
                    u.role
                FROM requests r
                JOIN users u ON r.user_id = u.user_id
                ORDER BY 
                    CASE WHEN r.status = 'pending' THEN 1 ELSE 2 END,
                    r.created_at DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getAllRequests Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update request status
     */
    public function updateRequestStatus($requestId, $status, $notes = '') {
        try {
            $stmt = $this->conn->prepare("
                UPDATE requests 
                SET status = ?, notes = ?, handled_by = ?, handled_at = NOW() 
                WHERE request_id = ?
            ");
            $stmt->bind_param("ssii", $status, $notes, $this->userId, $requestId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper updateRequestStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get finance statistics
     */
    public function getFinanceStatistics() {
        try {
            $stats = [];
            
            // Total revenue
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total 
                FROM payments 
                WHERE status = 'completed'
            ");
            $stmt->execute();
            $stats['total_revenue'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
            
            // Pending payments
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total 
                FROM payments 
                WHERE status = 'pending'
            ");
            $stmt->execute();
            $stats['pending_payments'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
            
            // This month revenue
            $stmt = $this->conn->prepare("
                SELECT SUM(amount) as total 
                FROM payments 
                WHERE status = 'completed' 
                AND MONTH(payment_date) = MONTH(CURRENT_DATE()) 
                AND YEAR(payment_date) = YEAR(CURRENT_DATE())
            ");
            $stmt->execute();
            $stats['month_revenue'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
            
            // Transactions count
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM payments");
            $stmt->execute();
            $stats['transactions_count'] = $stmt->get_result()->fetch_assoc()['count'];
            
            return $stats;
        } catch (Exception $e) {
            error_log("TechnicalHelper getFinanceStatistics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all payments
     */
    public function getAllPayments() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    p.*,
                    u.full_name as student_name,
                    u.email,
                    c.course_name
                FROM payments p
                JOIN users u ON p.user_id = u.user_id
                LEFT JOIN courses c ON p.course_id = c.course_id
                ORDER BY p.payment_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getAllPayments Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get quality metrics
     */
    public function getQualityMetrics() {
        try {
            $metrics = [];
            
            // Average trainer rating
            $stmt = $this->conn->prepare("
                SELECT AVG(rating) as avg_rating 
                FROM trainer_evaluations
            ");
            $stmt->execute();
            $metrics['avg_trainer_rating'] = round($stmt->get_result()->fetch_assoc()['avg_rating'] ?? 0, 2);
            
            // Average student grade
            $stmt = $this->conn->prepare("
                SELECT AVG(final_grade) as avg_grade 
                FROM enrollments 
                WHERE final_grade IS NOT NULL
            ");
            $stmt->execute();
            $metrics['avg_student_grade'] = round($stmt->get_result()->fetch_assoc()['avg_grade'] ?? 0, 2);
            
            // Completion rate
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed
                FROM enrollments
            ");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $total = $result['total'] ?? 1;
            $completed = $result['completed'] ?? 0;
            $metrics['completion_rate'] = round(($completed / $total) * 100, 2);
            
            // Satisfaction rate
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    COUNT(CASE WHEN rating >= 4 THEN 1 END) as satisfied
                FROM trainer_evaluations
            ");
            $stmt->execute();
            $result = $stmt->get_result()->fetch_assoc();
            $total = $result['total'] ?? 1;
            $satisfied = $result['satisfied'] ?? 0;
            $metrics['satisfaction_rate'] = round(($satisfied / $total) * 100, 2);
            
            return $metrics;
        } catch (Exception $e) {
            error_log("TechnicalHelper getQualityMetrics Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Generate comprehensive reports
     */
    public function generateReport($type, $startDate = null, $endDate = null) {
        try {
            $report = [];
            $startDate = $startDate ?? date('Y-m-01');
            $endDate = $endDate ?? date('Y-m-t');
            
            switch ($type) {
                case 'students':
                    $stmt = $this->conn->prepare("
                        SELECT 
                            DATE(u.created_at) as date,
                            COUNT(*) as count
                        FROM users u
                        WHERE u.role = 'student'
                        AND DATE(u.created_at) BETWEEN ? AND ?
                        GROUP BY DATE(u.created_at)
                        ORDER BY date
                    ");
                    $stmt->bind_param("ss", $startDate, $endDate);
                    break;
                    
                case 'courses':
                    $stmt = $this->conn->prepare("
                        SELECT 
                            c.course_name,
                            COUNT(DISTINCT e.user_id) as students,
                            AVG(e.final_grade) as avg_grade
                        FROM courses c
                        LEFT JOIN enrollments e ON c.course_id = e.course_id
                        WHERE DATE(c.created_at) BETWEEN ? AND ?
                        GROUP BY c.course_id
                        ORDER BY students DESC
                    ");
                    $stmt->bind_param("ss", $startDate, $endDate);
                    break;
                    
                case 'finance':
                    $stmt = $this->conn->prepare("
                        SELECT 
                            DATE(payment_date) as date,
                            SUM(amount) as revenue,
                            COUNT(*) as transactions
                        FROM payments
                        WHERE status = 'completed'
                        AND DATE(payment_date) BETWEEN ? AND ?
                        GROUP BY DATE(payment_date)
                        ORDER BY date
                    ");
                    $stmt->bind_param("ss", $startDate, $endDate);
                    break;
            }
            
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper generateReport Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Update course
     */
    public function updateCourse($courseId, $data) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE courses 
                SET course_name = ?, description = ?, start_date = ?, end_date = ?, 
                    trainer_id = ?, status = ?
                WHERE course_id = ?
            ");
            $stmt->bind_param("ssssssi", 
                $data['course_name'], 
                $data['description'], 
                $data['start_date'], 
                $data['end_date'],
                $data['trainer_id'],
                $data['status'],
                $courseId
            );
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper updateCourse Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create course
     */
    public function createCourse($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO courses (course_name, description, start_date, end_date, trainer_id, status, created_at)
                VALUES (?, ?, ?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("ssssss", 
                $data['course_name'], 
                $data['description'], 
                $data['start_date'], 
                $data['end_date'],
                $data['trainer_id'],
                $data['status']
            );
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper createCourse Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get student details
     */
    public function getStudentDetails($studentId) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    COUNT(DISTINCT e.course_id) as enrolled_courses,
                    AVG(e.final_grade) as gpa,
                    (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.user_id AND a.status = 'present') as attendance_present,
                    (SELECT COUNT(*) FROM attendance a WHERE a.student_id = u.user_id) as attendance_total
                FROM users u
                LEFT JOIN enrollments e ON u.user_id = e.user_id
                WHERE u.user_id = ? AND u.role = 'student'
                GROUP BY u.user_id
            ");
            $stmt->bind_param("i", $studentId);
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("TechnicalHelper getStudentDetails Error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update student status
     */
    public function updateStudentStatus($studentId, $status) {
        try {
            $stmt = $this->conn->prepare("
                UPDATE users 
                SET status = ?, updated_at = NOW() 
                WHERE user_id = ? AND role = 'student'
            ");
            $stmt->bind_param("si", $status, $studentId);
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("TechnicalHelper updateStudentStatus Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending courses for review
     */
    public function getPendingCourses($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.course_id,
                    c.course_name,
                    c.description,
                    c.status,
                    u.full_name as trainer_name
                FROM courses c
                LEFT JOIN users u ON c.trainer_id = u.user_id
                WHERE c.status = 'pending'
                ORDER BY c.created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getPendingCourses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get recent support tickets
     */
    public function getRecentSupportTickets($limit = 10) {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    st.ticket_id,
                    st.subject,
                    st.priority,
                    st.status,
                    st.created_at,
                    u.full_name as user_name
                FROM support_tickets st
                LEFT JOIN users u ON st.user_id = u.user_id
                WHERE st.status IN ('open', 'pending')
                ORDER BY st.created_at DESC
                LIMIT ?
            ");
            $stmt->bind_param("i", $limit);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("TechnicalHelper getRecentSupportTickets Error: " . $e->getMessage());
            return [];
        }
    }
}
?>
