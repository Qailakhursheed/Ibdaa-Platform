<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

/**
 * نظام التحليلات والرسوم البيانية الديناميكية
 * Dynamic Analytics & Charts System
 * 
 * يوفر بيانات حقيقية ومحدثة للرسوم البيانية
 */

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من الصلاحيات
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager', 'technical'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    if ($method === 'GET') {
        
        // ==============================================
        // إحصائيات لوحة التحكم الرئيسية
        // ==============================================
        if ($action === 'dashboard_stats') {
            // عدد الطلاب
            $students_stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student'");
            $total_students = $students_stmt->fetch_assoc()['total'];
            
            // الطلاب النشطون
            $active_students_stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'student' AND account_status = 'active'");
            $active_students = $active_students_stmt->fetch_assoc()['total'];
            
            // عدد المدربين
            $trainers_stmt = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'trainer'");
            $total_trainers = $trainers_stmt->fetch_assoc()['total'];
            
            // عدد الدورات
            $courses_stmt = $conn->query("SELECT COUNT(*) as total FROM courses WHERE status = 'active'");
            $total_courses = $courses_stmt->fetch_assoc()['total'];
            
            // الإيرادات المكتملة
            $revenue_stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'completed'");
            $total_revenue = $revenue_stmt->fetch_assoc()['total'];
            
            // الدفعات المعلقة
            $pending_stmt = $conn->query("SELECT COALESCE(SUM(amount), 0) as total FROM payments WHERE status = 'pending'");
            $pending_amount = $pending_stmt->fetch_assoc()['total'];
            
            // طلبات التسجيل المعلقة
            $requests_stmt = $conn->query("SELECT COUNT(*) as total FROM registration_requests WHERE status = 'pending'");
            $pending_requests = $requests_stmt->fetch_assoc()['total'];
            
            // البطاقات الصادرة
            $cards_stmt = $conn->query("SELECT COUNT(*) as total FROM id_cards WHERE status = 'active'");
            $issued_cards = $cards_stmt->fetch_assoc()['total'];
            
            respond([
                'success' => true,
                'statistics' => [
                    'total_students' => (int)$total_students,
                    'active_students' => (int)$active_students,
                    'total_trainers' => (int)$total_trainers,
                    'total_courses' => (int)$total_courses,
                    'total_revenue' => round((float)$total_revenue, 2),
                    'pending_amount' => round((float)$pending_amount, 2),
                    'pending_requests' => (int)$pending_requests,
                    'issued_cards' => (int)$issued_cards
                ]
            ]);
        }
        
        // ==============================================
        // بيانات الرسوم البيانية - الطلاب حسب الحالة
        // ==============================================
        if ($action === 'students_by_status') {
            $stmt = $conn->query("
                SELECT 
                    account_status,
                    COUNT(*) as count
                FROM users
                WHERE role = 'student'
                GROUP BY account_status
            ");
            
            $data = [];
            $labels = [];
            $values = [];
            $colors = [
                'pending' => '#f59e0b',
                'active' => '#10b981',
                'suspended' => '#ef4444',
                'completed' => '#3b82f6'
            ];
            
            $status_names = [
                'pending' => 'معلق',
                'active' => 'نشط',
                'suspended' => 'موقوف',
                'completed' => 'مكتمل'
            ];
            
            while ($row = $stmt->fetch_assoc()) {
                $status = $row['account_status'];
                $labels[] = $status_names[$status] ?? $status;
                $values[] = (int)$row['count'];
                $data[] = [
                    'status' => $status,
                    'status_name' => $status_names[$status] ?? $status,
                    'count' => (int)$row['count'],
                    'color' => $colors[$status] ?? '#6b7280'
                ];
            }
            
            respond([
                'success' => true,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'data' => $values,
                        'backgroundColor' => array_values($colors)
                    ]]
                ],
                'raw_data' => $data
            ]);
        }
        
        // ==============================================
        // الإيرادات الشهرية
        // ==============================================
        if ($action === 'monthly_revenue') {
            $year = (int)($_GET['year'] ?? date('Y'));
            
            $stmt = $conn->prepare("
                SELECT 
                    MONTH(payment_date) as month,
                    COALESCE(SUM(amount), 0) as total,
                    COUNT(*) as count
                FROM payments
                WHERE YEAR(payment_date) = ? AND status = 'completed'
                GROUP BY MONTH(payment_date)
                ORDER BY month
            ");
            $stmt->bind_param('i', $year);
            $stmt->execute();
            $result = $stmt->get_result();
            
            $months_ar = [
                1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
                5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
                9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
            ];
            
            $data = array_fill(1, 12, 0);
            $counts = array_fill(1, 12, 0);
            
            while ($row = $result->fetch_assoc()) {
                $month = (int)$row['month'];
                $data[$month] = round((float)$row['total'], 2);
                $counts[$month] = (int)$row['count'];
            }
            
            $labels = array_values($months_ar);
            $values = array_values($data);
            
            respond([
                'success' => true,
                'year' => $year,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'الإيرادات الشهرية',
                        'data' => $values,
                        'borderColor' => '#10b981',
                        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
                        'fill' => true
                    ]]
                ],
                'monthly_details' => array_map(function($m) use ($months_ar, $data, $counts) {
                    return [
                        'month' => $m,
                        'month_name' => $months_ar[$m],
                        'revenue' => $data[$m],
                        'count' => $counts[$m]
                    ];
                }, range(1, 12))
            ]);
        }
        
        // ==============================================
        // الطلاب حسب الدورة
        // ==============================================
        if ($action === 'students_per_course') {
            $stmt = $conn->query("
                SELECT 
                    c.title,
                    c.region,
                    COUNT(DISTINCT e.user_id) as student_count,
                    c.max_students,
                    COALESCE(SUM(CASE WHEN p.status = 'completed' THEN p.amount END), 0) as revenue
                FROM courses c
                LEFT JOIN enrollments e ON c.course_id = e.course_id
                LEFT JOIN payments p ON e.user_id = p.user_id AND e.course_id = p.course_id
                WHERE c.status = 'active'
                GROUP BY c.course_id, c.title, c.region, c.max_students
                ORDER BY student_count DESC
                LIMIT 10
            ");
            
            $labels = [];
            $values = [];
            $data = [];
            
            while ($row = $stmt->fetch_assoc()) {
                $label = $row['title'] . ($row['region'] ? ' - ' . $row['region'] : '');
                $labels[] = $label;
                $values[] = (int)$row['student_count'];
                $data[] = [
                    'course' => $row['title'],
                    'region' => $row['region'],
                    'students' => (int)$row['student_count'],
                    'max_students' => (int)$row['max_students'],
                    'revenue' => round((float)$row['revenue'], 2),
                    'percentage' => $row['max_students'] > 0 
                        ? round(($row['student_count'] / $row['max_students']) * 100, 1)
                        : 0
                ];
            }
            
            respond([
                'success' => true,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'عدد الطلاب',
                        'data' => $values,
                        'backgroundColor' => '#3b82f6'
                    ]]
                ],
                'courses_details' => $data
            ]);
        }
        
        // ==============================================
        // الطلاب حسب المنطقة
        // ==============================================
        if ($action === 'students_by_region') {
            $stmt = $conn->query("
                SELECT 
                    COALESCE(governorate, 'غير محدد') as region,
                    COUNT(*) as count
                FROM users
                WHERE role = 'student'
                GROUP BY governorate
                ORDER BY count DESC
                LIMIT 10
            ");
            
            $labels = [];
            $values = [];
            
            while ($row = $stmt->fetch_assoc()) {
                $labels[] = $row['region'];
                $values[] = (int)$row['count'];
            }
            
            respond([
                'success' => true,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'عدد الطلاب',
                        'data' => $values,
                        'backgroundColor' => [
                            '#ef4444', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6',
                            '#ec4899', '#14b8a6', '#f97316', '#06b6d4', '#84cc16'
                        ]
                    ]]
                ]
            ]);
        }
        
        // ==============================================
        // حالة الدفع للطلاب
        // ==============================================
        if ($action === 'payment_status_distribution') {
            $stmt = $conn->query("
                SELECT 
                    CASE 
                        WHEN u.payment_complete = 1 THEN 'مكتمل'
                        WHEN EXISTS (SELECT 1 FROM payments p WHERE p.user_id = u.id AND p.status = 'completed') THEN 'جزئي'
                        ELSE 'لم يدفع'
                    END as payment_status,
                    COUNT(*) as count
                FROM users u
                WHERE role = 'student'
                GROUP BY payment_status
            ");
            
            $labels = [];
            $values = [];
            $colors = [
                'مكتمل' => '#10b981',
                'جزئي' => '#f59e0b',
                'لم يدفع' => '#ef4444'
            ];
            
            $backgroundColor = [];
            
            while ($row = $stmt->fetch_assoc()) {
                $status = $row['payment_status'];
                $labels[] = $status;
                $values[] = (int)$row['count'];
                $backgroundColor[] = $colors[$status] ?? '#6b7280';
            }
            
            respond([
                'success' => true,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'data' => $values,
                        'backgroundColor' => $backgroundColor
                    ]]
                ]
            ]);
        }
        
        // ==============================================
        // نشاط النظام (آخر 7 أيام)
        // ==============================================
        if ($action === 'system_activity') {
            $stmt = $conn->query("
                SELECT 
                    DATE(created_at) as date,
                    COUNT(*) as count
                FROM activity_logs
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY DATE(created_at)
                ORDER BY date
            ");
            
            $labels = [];
            $values = [];
            
            // ملء آخر 7 أيام
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m-d', strtotime("-$i days"));
                $labels[] = date('d/m', strtotime($date));
                $values[] = 0;
            }
            
            while ($row = $stmt->fetch_assoc()) {
                $date = $row['date'];
                $index = array_search(date('d/m', strtotime($date)), $labels);
                if ($index !== false) {
                    $values[$index] = (int)$row['count'];
                }
            }
            
            respond([
                'success' => true,
                'chart_data' => [
                    'labels' => $labels,
                    'datasets' => [[
                        'label' => 'النشاط اليومي',
                        'data' => $values,
                        'borderColor' => '#8b5cf6',
                        'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
                        'fill' => true
                    ]]
                ]
            ]);
        }
        
        // ==============================================
        // تحليل شامل
        // ==============================================
        if ($action === 'comprehensive_analytics') {
            // جمع كل الإحصائيات في طلب واحد
            $analytics = [];
            
            // الإحصائيات الأساسية
            $stats = $conn->query("
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE role = 'student') as total_students,
                    (SELECT COUNT(*) FROM users WHERE role = 'trainer') as total_trainers,
                    (SELECT COUNT(*) FROM courses WHERE status = 'active') as active_courses,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed') as total_revenue,
                    (SELECT COUNT(*) FROM id_cards WHERE status = 'active') as issued_cards,
                    (SELECT COUNT(*) FROM registration_requests WHERE status = 'pending') as pending_requests
            ")->fetch_assoc();
            
            $analytics['summary'] = [
                'students' => (int)$stats['total_students'],
                'trainers' => (int)$stats['total_trainers'],
                'courses' => (int)$stats['active_courses'],
                'revenue' => round((float)$stats['total_revenue'], 2),
                'cards' => (int)$stats['issued_cards'],
                'requests' => (int)$stats['pending_requests']
            ];
            
            // معدل النمو (مقارنة بالشهر السابق)
            $growth = $conn->query("
                SELECT 
                    (SELECT COUNT(*) FROM users WHERE role = 'student' AND MONTH(created_at) = MONTH(NOW())) as new_students_this_month,
                    (SELECT COUNT(*) FROM users WHERE role = 'student' AND MONTH(created_at) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))) as new_students_last_month,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(NOW())) as revenue_this_month,
                    (SELECT COALESCE(SUM(amount), 0) FROM payments WHERE status = 'completed' AND MONTH(payment_date) = MONTH(DATE_SUB(NOW(), INTERVAL 1 MONTH))) as revenue_last_month
            ")->fetch_assoc();
            
            $students_growth = $growth['new_students_last_month'] > 0
                ? round((($growth['new_students_this_month'] - $growth['new_students_last_month']) / $growth['new_students_last_month']) * 100, 1)
                : 100;
                
            $revenue_growth = $growth['revenue_last_month'] > 0
                ? round((($growth['revenue_this_month'] - $growth['revenue_last_month']) / $growth['revenue_last_month']) * 100, 1)
                : 100;
            
            $analytics['growth'] = [
                'students' => $students_growth,
                'revenue' => $revenue_growth
            ];
            
            respond([
                'success' => true,
                'analytics' => $analytics,
                'generated_at' => date('Y-m-d H:i:s')
            ]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
    
} catch (Throwable $e) {
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
