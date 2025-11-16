<?php
/**
 * Courses API for Public Platform
 * Provides course listings with filters, search, and categories
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

// Handle preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            getCourses($conn);
            break;
        
        case 'details':
            getCourseDetails($conn);
            break;
        
        case 'categories':
            getCategories($conn);
            break;
        
        case 'featured':
            getFeaturedCourses($conn);
            break;
        
        case 'search':
            searchCourses($conn);
            break;
        
        case 'stats':
            getCourseStats($conn);
            break;
        
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

function getCourses($conn) {
    $category = $_GET['category'] ?? null;
    $status = $_GET['status'] ?? 'active';
    $limit = (int)($_GET['limit'] ?? 20);
    $offset = (int)($_GET['offset'] ?? 0);
    $orderBy = $_GET['order'] ?? 'created_at';
    $orderDir = $_GET['dir'] ?? 'DESC';
    
    // Build query
    $where = ["c.status = ?"];
    $params = [$status];
    $types = "s";
    
    if ($category) {
        $where[] = "c.category = ?";
        $params[] = $category;
        $types .= "s";
    }
    
    $whereClause = implode(" AND ", $where);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as total FROM courses c WHERE $whereClause";
    $stmtCount = $conn->prepare($countSql);
    $stmtCount->bind_param($types, ...$params);
    $stmtCount->execute();
    $total = $stmtCount->get_result()->fetch_assoc()['total'];
    
    // Get courses with trainer info
    $sql = "SELECT 
                c.course_id,
                c.title,
                c.description,
                c.price,
                c.duration,
                c.start_date,
                c.end_date,
                c.category,
                c.status,
                c.max_students,
                c.image_url,
                c.created_at,
                u.full_name as trainer_name,
                u.email as trainer_email,
                l.name as location_name,
                l.address as location_address,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count,
                (SELECT AVG(rating) FROM course_reviews WHERE course_id = c.course_id) as avg_rating,
                (SELECT COUNT(*) FROM course_reviews WHERE course_id = c.course_id) as review_count
            FROM courses c
            LEFT JOIN users u ON c.trainer_id = u.user_id
            LEFT JOIN locations l ON c.location_id = l.location_id
            WHERE $whereClause
            ORDER BY c.$orderBy $orderDir
            LIMIT ? OFFSET ?";
    
    $stmt = $conn->prepare($sql);
    $params[] = $limit;
    $params[] = $offset;
    $types .= "ii";
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $row['avg_rating'] = round($row['avg_rating'], 1);
        $row['is_full'] = $row['enrolled_count'] >= $row['max_students'];
        $row['available_seats'] = max(0, $row['max_students'] - $row['enrolled_count']);
        $courses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $courses,
        'pagination' => [
            'total' => (int)$total,
            'limit' => $limit,
            'offset' => $offset,
            'pages' => ceil($total / $limit)
        ]
    ], JSON_UNESCAPED_UNICODE);
}

function getCourseDetails($conn) {
    $courseId = $_GET['id'] ?? null;
    
    if (!$courseId) {
        throw new Exception('Course ID is required');
    }
    
    $sql = "SELECT 
                c.*,
                u.full_name as trainer_name,
                u.email as trainer_email,
                u.phone as trainer_phone,
                l.name as location_name,
                l.address as location_address,
                l.phone as location_phone,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count,
                (SELECT AVG(rating) FROM course_reviews WHERE course_id = c.course_id) as avg_rating,
                (SELECT COUNT(*) FROM course_reviews WHERE course_id = c.course_id) as review_count
            FROM courses c
            LEFT JOIN users u ON c.trainer_id = u.user_id
            LEFT JOIN locations l ON c.location_id = l.location_id
            WHERE c.course_id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $courseId);
    $stmt->execute();
    $course = $stmt->get_result()->fetch_assoc();
    
    if (!$course) {
        throw new Exception('Course not found');
    }
    
    // Get reviews
    $reviewsSql = "SELECT 
                        cr.*,
                        u.full_name as student_name
                   FROM course_reviews cr
                   JOIN users u ON cr.student_id = u.user_id
                   WHERE cr.course_id = ?
                   ORDER BY cr.created_at DESC
                   LIMIT 10";
    $stmtReviews = $conn->prepare($reviewsSql);
    $stmtReviews->bind_param("i", $courseId);
    $stmtReviews->execute();
    $reviews = $stmtReviews->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $course['reviews'] = $reviews;
    $course['avg_rating'] = round($course['avg_rating'], 1);
    $course['is_full'] = $course['enrolled_count'] >= $course['max_students'];
    $course['available_seats'] = max(0, $course['max_students'] - $course['enrolled_count']);
    
    echo json_encode([
        'success' => true,
        'data' => $course
    ], JSON_UNESCAPED_UNICODE);
}

function getCategories($conn) {
    $sql = "SELECT 
                category,
                COUNT(*) as course_count
            FROM courses
            WHERE status = 'active'
            GROUP BY category
            ORDER BY course_count DESC";
    
    $result = $conn->query($sql);
    $categories = $result->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $categories
    ], JSON_UNESCAPED_UNICODE);
}

function getFeaturedCourses($conn) {
    $limit = (int)($_GET['limit'] ?? 6);
    
    // Get featured courses (most enrolled or highest rated)
    $sql = "SELECT 
                c.course_id,
                c.title,
                c.description,
                c.price,
                c.duration,
                c.start_date,
                c.category,
                c.image_url,
                u.full_name as trainer_name,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count,
                (SELECT AVG(rating) FROM course_reviews WHERE course_id = c.course_id) as avg_rating,
                (SELECT COUNT(*) FROM course_reviews WHERE course_id = c.course_id) as review_count
            FROM courses c
            LEFT JOIN users u ON c.trainer_id = u.user_id
            WHERE c.status = 'active'
            ORDER BY enrolled_count DESC, avg_rating DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $row['avg_rating'] = round($row['avg_rating'], 1);
        $courses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $courses
    ], JSON_UNESCAPED_UNICODE);
}

function searchCourses($conn) {
    $query = $_GET['q'] ?? '';
    $limit = (int)($_GET['limit'] ?? 20);
    
    if (strlen($query) < 2) {
        throw new Exception('Search query must be at least 2 characters');
    }
    
    $searchTerm = "%$query%";
    
    $sql = "SELECT 
                c.course_id,
                c.title,
                c.description,
                c.price,
                c.duration,
                c.start_date,
                c.category,
                c.image_url,
                u.full_name as trainer_name,
                (SELECT COUNT(*) FROM enrollments WHERE course_id = c.course_id) as enrolled_count,
                (SELECT AVG(rating) FROM course_reviews WHERE course_id = c.course_id) as avg_rating
            FROM courses c
            LEFT JOIN users u ON c.trainer_id = u.user_id
            WHERE c.status = 'active' 
                AND (c.title LIKE ? OR c.description LIKE ? OR c.category LIKE ?)
            ORDER BY 
                CASE 
                    WHEN c.title LIKE ? THEN 1
                    WHEN c.category LIKE ? THEN 2
                    ELSE 3
                END,
                enrolled_count DESC
            LIMIT ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sssssi", $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $courses = [];
    while ($row = $result->fetch_assoc()) {
        $row['avg_rating'] = round($row['avg_rating'], 1);
        $courses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $courses,
        'count' => count($courses)
    ], JSON_UNESCAPED_UNICODE);
}

function getCourseStats($conn) {
    $stats = [];
    
    // Total active courses
    $result = $conn->query("SELECT COUNT(*) as count FROM courses WHERE status = 'active'");
    $stats['active_courses'] = (int)$result->fetch_assoc()['count'];
    
    // Total students enrolled
    $result = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM enrollments");
    $stats['total_students'] = (int)$result->fetch_assoc()['count'];
    
    // Total graduates
    $result = $conn->query("SELECT COUNT(DISTINCT student_id) as count FROM certificates");
    $stats['total_graduates'] = (int)$result->fetch_assoc()['count'];
    
    // Average course rating
    $result = $conn->query("SELECT AVG(rating) as avg FROM course_reviews");
    $stats['avg_rating'] = round($result->fetch_assoc()['avg'], 1);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}
?>
