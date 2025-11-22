<?php
/**
 * Manager Helper Functions
 * دوال مساعدة للمدير - بديل PHP للمدير
 * 
 * Hybrid System: Direct PHP queries + Python API for charts
 */

class ManagerHelper {
    private $conn;
    private $userId;
    
    public function __construct($connection, $userId) {
        $this->conn = $connection;
        $this->userId = $userId;
    }
    
    /**
     * Get all courses with full details
     */
    public function getAllCourses() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    c.*,
                    u.full_name as trainer_name,
                    COUNT(DISTINCT e.user_id) as students_count,
                    AVG(e.final_grade) as avg_grade,
                    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_count
                FROM courses c
                LEFT JOIN users u ON c.trainer_id = u.user_id
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                GROUP BY c.course_id
                ORDER BY c.start_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getAllCourses Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all students with enrollments
     */
    public function getAllStudents() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    COUNT(DISTINCT e.course_id) as enrolled_courses,
                    AVG(e.final_grade) as gpa,
                    SUM(CASE WHEN e.status = 'completed' THEN 1 ELSE 0 END) as completed_courses
                FROM users u
                LEFT JOIN enrollments e ON u.user_id = e.user_id
                WHERE u.role = 'student'
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getAllStudents Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all trainers with statistics
     */
    public function getAllTrainers() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    COUNT(DISTINCT c.course_id) as total_courses,
                    COUNT(DISTINCT CASE WHEN c.status = 'active' THEN c.course_id END) as active_courses,
                    COUNT(DISTINCT e.user_id) as total_students,
                    AVG(e.final_grade) as avg_student_grade
                FROM users u
                LEFT JOIN courses c ON u.user_id = c.trainer_id
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                WHERE u.role = 'trainer'
                GROUP BY u.user_id
                ORDER BY u.full_name
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getAllTrainers Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get financial summary
     */
    public function getFinancialSummary() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    SUM(amount) as total_revenue,
                    SUM(CASE WHEN status = 'completed' THEN amount ELSE 0 END) as confirmed_revenue,
                    SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as pending_revenue,
                    COUNT(*) as total_transactions,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_transactions
                FROM payments
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_assoc();
        } catch (Exception $e) {
            error_log("Manager getFinancialSummary Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get all graduates
     */
    public function getGraduates() {
        try {
            $stmt = $this->conn->prepare("
                SELECT 
                    u.*,
                    e.course_id,
                    c.course_name,
                    e.final_grade,
                    e.completion_date,
                    e.certificate_issued
                FROM users u
                JOIN enrollments e ON u.user_id = e.user_id
                JOIN courses c ON e.course_id = c.course_id
                WHERE e.status = 'completed' AND e.final_grade >= 60
                ORDER BY e.completion_date DESC
            ");
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getGraduates Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get manager statistics
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
            
            // Total students
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'student'");
            $stmt->execute();
            $stats['total_students'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Active enrollments
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM enrollments WHERE status = 'active'");
            $stmt->execute();
            $stats['active_enrollments'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Total trainers
            $stmt = $this->conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'trainer'");
            $stmt->execute();
            $stats['total_trainers'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Total revenue
            $stmt = $this->conn->prepare("SELECT SUM(amount) as total FROM payments WHERE status = 'completed'");
            $stmt->execute();
            $stats['total_revenue'] = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
            
            // Graduates
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) as count 
                FROM enrollments 
                WHERE status = 'completed' AND final_grade >= 60
            ");
            $stmt->execute();
            $stats['total_graduates'] = $stmt->get_result()->fetch_assoc()['count'];
            
            // Average grade
            $stmt = $this->conn->prepare("
                SELECT AVG(final_grade) as avg 
                FROM enrollments 
                WHERE final_grade IS NOT NULL
            ");
            $stmt->execute();
            $stats['avg_grade'] = round($stmt->get_result()->fetch_assoc()['avg'] ?? 0, 2);
            
            return $stats;
        } catch (Exception $e) {
            error_log("Manager getStatistics Error: " . $e->getMessage());
            return [];
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
            error_log("Manager getAllAnnouncements Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Create course
     */
    public function createCourse($data) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO courses (course_name, description, trainer_id, start_date, end_date, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->bind_param("ssisssi", 
                $data['course_name'],
                $data['description'],
                $data['trainer_id'],
                $data['start_date'],
                $data['end_date'],
                $data['status'],
                $this->userId
            );
            return $stmt->execute();
        } catch (Exception $e) {
            error_log("Manager createCourse Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all users by role
     */
    public function getAllUsers($role = null) {
        try {
            if ($role) {
                $stmt = $this->conn->prepare("
                    SELECT * FROM users 
                    WHERE role = ? 
                    ORDER BY created_at DESC
                ");
                $stmt->bind_param("s", $role);
            } else {
                $stmt = $this->conn->prepare("
                    SELECT * FROM users 
                    ORDER BY created_at DESC
                ");
            }
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getAllUsers Error: " . $e->getMessage());
            return [];
        }
    }
    
    // Duplicate getAllTrainers() removed - using existing method at line 72
    
    /**
     * Get comprehensive reports
     */
    public function getReports($type, $startDate = null, $endDate = null) {
        try {
            $startDate = $startDate ?? date('Y-m-01');
            $endDate = $endDate ?? date('Y-m-t');
            
            switch ($type) {
                case 'students':
                    $stmt = $this->conn->prepare("
                        SELECT 
                            DATE(created_at) as date,
                            COUNT(*) as count
                        FROM users
                        WHERE role = 'student'
                        AND DATE(created_at) BETWEEN ? AND ?
                        GROUP BY DATE(created_at)
                        ORDER BY date
                    ");
                    break;
                    
                case 'courses':
                    $stmt = $this->conn->prepare("
                        SELECT 
                            c.course_name,
                            COUNT(DISTINCT e.user_id) as students,
                            AVG(e.final_grade) as avg_grade,
                            c.status
                        FROM courses c
                        LEFT JOIN enrollments e ON c.course_id = e.course_id
                        WHERE DATE(c.created_at) BETWEEN ? AND ?
                        GROUP BY c.course_id
                        ORDER BY students DESC
                    ");
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
                    break;
                    
                default:
                    return [];
            }
            
            $stmt->bind_param("ss", $startDate, $endDate);
            $stmt->execute();
            return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getReports Error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Process Smart Import (AI-Powered)
     */
    public function processSmartImport($file, $options) {
        $result = [
            'success' => false,
            'stats' => ['imported' => 0, 'updated' => 0, 'failed' => 0, 'skipped' => 0],
            'errors' => []
        ];
        
        try {
            // Validate file
            if (!isset($file['tmp_name']) || !file_exists($file['tmp_name'])) {
                $result['errors'][] = 'لم يتم رفع الملف بشكل صحيح';
                return $result;
            }
            
            $fileType = pathinfo($file['name'], PATHINFO_EXTENSION);
            
            // For now, return a placeholder success
            // Full implementation would require PhpSpreadsheet library
            $result['success'] = true;
            $result['stats']['imported'] = 0;
            $result['errors'][] = 'وظيفة الاستيراد قيد التطوير - يتطلب مكتبة PhpSpreadsheet';
            
            return $result;
        } catch (Exception $e) {
            $result['errors'][] = $e->getMessage();
            return $result;
        }
    }
    
    /**
     * Get dashboard analytics
     */
    public function getDashboardAnalytics() {
        try {
            $analytics = [];
            
            // Students analytics (use verified instead of status)
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN DATE(created_at) >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 ELSE 0 END) as new_this_month
                FROM users WHERE role = 'student'
            ");
            $stmt->execute();
            $analytics['students'] = $stmt->get_result()->fetch_assoc();
            
            // Courses analytics
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM courses
            ");
            $stmt->execute();
            $analytics['courses'] = $stmt->get_result()->fetch_assoc();
            
            // Trainers analytics (use verified instead of status)
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN verified = 1 THEN 1 ELSE 0 END) as active
                FROM users WHERE role = 'trainer'
            ");
            $stmt->execute();
            $analytics['trainers'] = $stmt->get_result()->fetch_assoc();
            
            // Enrollments analytics
            $stmt = $this->conn->prepare("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed
                FROM enrollments
            ");
            $stmt->execute();
            $analytics['enrollments'] = $stmt->get_result()->fetch_assoc();
            
            return $analytics;
        } catch (Exception $e) {
            error_log("Manager getDashboardAnalytics Error: " . $e->getMessage());
            // Return default structure on error
            return [
                'students' => ['total' => 0, 'active' => 0, 'new_this_month' => 0],
                'courses' => ['total' => 0, 'active' => 0, 'completed' => 0],
                'trainers' => ['total' => 0, 'active' => 0],
                'enrollments' => ['total' => 0, 'active' => 0, 'completed' => 0]
            ];
        }
    }

    /**
     * Get all registration requests
     */
    public function getAllRequests() {
        try {
            $query = "SELECT r.*, c.course_name 
                     FROM registration_requests r
                     LEFT JOIN courses c ON r.course_id = c.course_id
                     ORDER BY r.created_at DESC";
            $stmt = $this->conn->prepare($query);
            $stmt->execute();
            $result = $stmt->get_result();
            return $result->fetch_all(MYSQLI_ASSOC);
        } catch (Exception $e) {
            error_log("Manager getAllRequests Error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Approve a registration request
     */
    public function approveRequest($requestId) {
        $this->conn->begin_transaction();
        
        try {
            // Get request details
            $query = "SELECT * FROM registration_requests WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $request = $stmt->get_result()->fetch_assoc();
            
            if (!$request) {
                throw new Exception("Request not found");
            }
            
            // Create user account
            $password = bin2hex(random_bytes(4)); // Generate 8-char password
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $query = "INSERT INTO users (full_name, email, password, role, status, created_at) 
                     VALUES (?, ?, ?, 'student', 'active', NOW())";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("sss", $request['full_name'], $request['email'], $hashedPassword);
            $stmt->execute();
            $userId = $this->conn->insert_id;
            
            // Create enrollment if course specified
            if (!empty($request['course_id'])) {
                $query = "INSERT INTO enrollments (user_id, course_id, enrollment_date, status) 
                         VALUES (?, ?, NOW(), 'active')";
                $stmt = $this->conn->prepare($query);
                $stmt->bind_param("ii", $userId, $request['course_id']);
                $stmt->execute();
            }
            
            // Update request status
            $query = "UPDATE registration_requests SET status = 'approved' WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            
            $this->conn->commit();
            
            // TODO: Send welcome email with credentials
            // mail($request['email'], "تم قبول طلبك", "كلمة المرور: $password");
            
            return [
                'success' => true,
                'message' => "تمت الموافقة على الطلب بنجاح وتم إنشاء حساب للطالب",
                'user_id' => $userId,
                'password' => $password
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log("Manager approveRequest Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "فشلت عملية الموافقة: " . $e->getMessage()
            ];
        }
    }

    /**
     * Reject a registration request
     */
    public function rejectRequest($requestId, $reason) {
        try {
            // Get request email for notification
            $query = "SELECT email FROM registration_requests WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $requestId);
            $stmt->execute();
            $email = $stmt->get_result()->fetch_assoc()['email'];
            
            // Update request status and reason
            $query = "UPDATE registration_requests 
                     SET status = 'rejected', rejection_reason = ? 
                     WHERE request_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("si", $reason, $requestId);
            $stmt->execute();
            
            // TODO: Send rejection email
            // mail($email, "بخصوص طلب التسجيل", "نأسف لإبلاغك برفض طلبك: $reason");
            
            return [
                'success' => true,
                'message' => "تم رفض الطلب وحفظ السبب"
            ];
        } catch (Exception $e) {
            error_log("Manager rejectRequest Error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => "فشلت عملية الرفض: " . $e->getMessage()
            ];
        }
    }

    /**
     * Bulk approve multiple requests
     */
    public function bulkApproveRequests($requestIds) {
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        
        foreach ($requestIds as $requestId) {
            if (empty($requestId)) continue;
            
            $result = $this->approveRequest($requestId);
            if ($result['success']) {
                $successCount++;
            } else {
                $failCount++;
                $errors[] = "Request ID $requestId: " . $result['message'];
            }
        }
        
        return [
            'success' => $successCount > 0,
            'message' => "تمت الموافقة على $successCount طلب بنجاح" . 
                        ($failCount > 0 ? " وفشل $failCount طلب" : ""),
            'stats' => [
                'success' => $successCount,
                'failed' => $failCount
            ],
            'errors' => $errors
        ];
    }
}
?>
