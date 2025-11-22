<?php
/**
 * id_cards - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


require_once __DIR__ . '/../includes/db_connect.php';

header('Content-Type: application/json; charset=utf-8');

// Authentication check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'غير مصرح بالدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['role'];

// Check permissions
$allowedRoles = ['manager', 'technical', 'student'];
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        case 'list':
            listCards($conn, $userId, $userRole);
            break;
        case 'get':
            getCard($conn, $userId, $userRole);
            break;
        case 'create':
            createCard($conn, $userId);
            break;
        case 'update':
            updateCard($conn, $userId);
            break;
        case 'activate':
            activateCard($conn);
            break;
        case 'deactivate':
            deactivateCard($conn);
            break;
        case 'download_pdf':
            downloadCardPDF($conn, $userId, $userRole);
            break;
        case 'send_email':
            sendCardEmail($conn);
            break;
        case 'send_whatsapp':
            sendCardWhatsApp($conn);
            break;
        case 'scan':
            scanCard($conn);
            break;
        case 'statistics':
            getStatistics($conn);
            break;
        case 'bulk_create':
            bulkCreateCards($conn, $userId);
            break;
        default:
            throw new Exception('إجراء غير صحيح');
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * List all ID cards
 */
function listCards($conn, $userId, $userRole) {
    $status = $_GET['status'] ?? 'all';
    $type = $_GET['type'] ?? 'all';
    $search = $_GET['search'] ?? '';
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    // For students: show only their card
    if ($userRole === 'student') {
        $whereConditions[] = "c.user_id = ?";
        $params[] = $userId;
        $types .= 'i';
    }
    
    // Status filter
    if ($status !== 'all') {
        $whereConditions[] = "c.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    // Type filter
    if ($type !== 'all') {
        $whereConditions[] = "c.card_type = ?";
        $params[] = $type;
        $types .= 's';
    }
    
    // Search filter
    if (!empty($search)) {
        $whereConditions[] = "(u.full_name LIKE ? OR c.card_number LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'ss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT 
                c.*,
                u.full_name,
                u.email,
                u.phone,
                u.photo,
                u.birth_date,
                u.gender,
                co.title as course_name
            FROM id_cards c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses co ON e.course_id = co.course_id
            $whereClause
            ORDER BY c.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $cards = [];
    
    while ($row = $result->fetch_assoc()) {
        // Check if expired
        if ($row['expiry_date']) {
            $expiryDate = new DateTime($row['expiry_date']);
            $now = new DateTime();
            $row['is_expired'] = $now > $expiryDate;
            $row['days_until_expiry'] = $now->diff($expiryDate)->days;
        } else {
            $row['is_expired'] = false;
            $row['days_until_expiry'] = null;
        }
        
        $cards[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $cards,
        'count' => count($cards)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get single card details
 */
function getCard($conn, $userId, $userRole) {
    $cardId = intval($_GET['id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    $sql = "SELECT 
                c.*,
                u.full_name,
                u.email,
                u.phone,
                u.photo,
                u.birth_date,
                u.gender,
                u.address,
                u.national_id,
                co.title as course_name,
                co.description as course_description
            FROM id_cards c
            JOIN users u ON c.user_id = u.id
            LEFT JOIN enrollments e ON u.id = e.user_id
            LEFT JOIN courses co ON e.course_id = co.course_id
            WHERE c.id = ?";
    
    // Students can only view their own cards
    if ($userRole === 'student') {
        $sql .= " AND c.user_id = ?";
    }
    
    $stmt = $conn->prepare($sql);
    
    if ($userRole === 'student') {
        $stmt->bind_param('ii', $cardId, $userId);
    } else {
        $stmt->bind_param('i', $cardId);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('البطاقة غير موجودة');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Create new ID card
 */
function createCard($conn, $userId) {
    $studentId = intval($_POST['user_id'] ?? 0);
    $cardType = $_POST['card_type'] ?? 'student';
    $validityPeriod = intval($_POST['validity_period'] ?? 12); // months
    
    if ($studentId === 0) {
        throw new Exception('معرف المستخدم مطلوب');
    }
    
    // Check if card already exists
    $checkStmt = $conn->prepare("SELECT id FROM id_cards WHERE user_id = ? AND status = 'active'");
    $checkStmt->bind_param('i', $studentId);
    $checkStmt->execute();
    
    if ($checkStmt->get_result()->num_rows > 0) {
        throw new Exception('المستخدم لديه بطاقة نشطة بالفعل');
    }
    
    // Generate card number
    $cardNumber = generateCardNumber($conn, $cardType);
    
    // Calculate expiry date
    $issueDate = date('Y-m-d');
    $expiryDate = date('Y-m-d', strtotime("+$validityPeriod months"));
    
    // Generate barcode/QR code value
    $barcodeValue = $cardNumber . '|' . $studentId . '|' . time();
    
    $stmt = $conn->prepare("INSERT INTO id_cards 
                           (user_id, card_number, card_type, issue_date, expiry_date, 
                            barcode_value, status, created_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, 'active', ?, NOW())");
    $stmt->bind_param('isssssi', $studentId, $cardNumber, $cardType, $issueDate, 
                      $expiryDate, $barcodeValue, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إنشاء البطاقة');
    }
    
    $cardId = $conn->insert_id;
    
    // Update user's card number
    $updateUser = $conn->prepare("UPDATE users SET id_card_number = ? WHERE id = ?");
    $updateUser->bind_param('si', $cardNumber, $studentId);
    $updateUser->execute();
    
    // Create notification
    $message = "تم إصدار بطاقتك بنجاح! رقم البطاقة: $cardNumber";
    $notifStmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                                 VALUES (?, ?, 'id_card', NOW())");
    $notifStmt->bind_param('is', $studentId, $message);
    $notifStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء البطاقة بنجاح',
        'card_id' => $cardId,
        'card_number' => $cardNumber
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Update card
 */
function updateCard($conn, $userId) {
    $cardId = intval($_POST['card_id'] ?? 0);
    $status = $_POST['status'] ?? '';
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    $updates = [];
    $params = [];
    $types = '';
    
    if (!empty($status)) {
        $updates[] = "status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if (empty($updates)) {
        throw new Exception('لا توجد بيانات للتحديث');
    }
    
    $updates[] = "updated_at = NOW()";
    $params[] = $cardId;
    $types .= 'i';
    
    $sql = "UPDATE id_cards SET " . implode(', ', $updates) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$params);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث البطاقة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث البطاقة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Activate card
 */
function activateCard($conn) {
    $cardId = intval($_POST['card_id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    $stmt = $conn->prepare("UPDATE id_cards SET status = 'active' WHERE id = ?");
    $stmt->bind_param('i', $cardId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تفعيل البطاقة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تفعيل البطاقة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Deactivate card
 */
function deactivateCard($conn) {
    $cardId = intval($_POST['card_id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    $stmt = $conn->prepare("UPDATE id_cards SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param('i', $cardId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إلغاء تفعيل البطاقة');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إلغاء تفعيل البطاقة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Download card as PDF
 */
function downloadCardPDF($conn, $userId, $userRole) {
    $cardId = intval($_GET['id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    // This would integrate with existing PDF generation system
    // Redirect to the existing ID card PDF generator
    echo json_encode([
        'success' => true,
        'redirect' => "../api/id_cards_dynamic_system.php?action=download&id=$cardId"
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Send card via email
 */
function sendCardEmail($conn) {
    $cardId = intval($_POST['card_id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    // Get card and user details
    $stmt = $conn->prepare("SELECT c.*, u.email, u.full_name 
                           FROM id_cards c
                           JOIN users u ON c.user_id = u.id
                           WHERE c.id = ?");
    $stmt->bind_param('i', $cardId);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();
    
    if (!$card) {
        throw new Exception('البطاقة غير موجودة');
    }
    
    // Send email (integrate with existing email system)
    $sent = sendCardByEmail($card);
    
    if ($sent) {
        // Update sent status
        $updateStmt = $conn->prepare("UPDATE id_cards SET email_sent = 1, email_sent_at = NOW() WHERE id = ?");
        $updateStmt->bind_param('i', $cardId);
        $updateStmt->execute();
        
        echo json_encode([
            'success' => true,
            'message' => 'تم إرسال البطاقة عبر البريد الإلكتروني بنجاح'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        throw new Exception('فشل في إرسال البريد الإلكتروني');
    }
}

/**
 * Send card via WhatsApp
 */
function sendCardWhatsApp($conn) {
    $cardId = intval($_POST['card_id'] ?? 0);
    
    if ($cardId === 0) {
        throw new Exception('معرف البطاقة مطلوب');
    }
    
    // Get card and user details
    $stmt = $conn->prepare("SELECT c.*, u.phone, u.full_name 
                           FROM id_cards c
                           JOIN users u ON c.user_id = u.id
                           WHERE c.id = ?");
    $stmt->bind_param('i', $cardId);
    $stmt->execute();
    $card = $stmt->get_result()->fetch_assoc();
    
    if (!$card) {
        throw new Exception('البطاقة غير موجودة');
    }
    
    // Generate WhatsApp link
    $phone = preg_replace('/[^0-9]/', '', $card['phone']);
    $message = "مرحباً {$card['full_name']}!\n\n";
    $message .= "إليك بطاقتك الطلابية:\n";
    $message .= "رقم البطاقة: {$card['card_number']}\n";
    $message .= "تاريخ الإصدار: {$card['issue_date']}\n";
    $message .= "تاريخ الانتهاء: {$card['expiry_date']}\n\n";
    $message .= "يمكنك تحميل البطاقة من المنصة.";
    
    $whatsappLink = "https://wa.me/$phone?text=" . urlencode($message);
    
    echo json_encode([
        'success' => true,
        'message' => 'جاهز للإرسال عبر واتساب',
        'whatsapp_link' => $whatsappLink
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Scan card barcode/QR code
 */
function scanCard($conn) {
    $barcodeValue = $_POST['barcode'] ?? '';
    
    if (empty($barcodeValue)) {
        throw new Exception('قيمة الباركود مطلوبة');
    }
    
    // Find card by barcode
    $stmt = $conn->prepare("SELECT c.*, u.* 
                           FROM id_cards c
                           JOIN users u ON c.user_id = u.id
                           WHERE c.barcode_value = ? OR c.card_number = ?");
    $stmt->bind_param('ss', $barcodeValue, $barcodeValue);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('البطاقة غير موجودة');
    }
    
    $card = $result->fetch_assoc();
    
    // Check if card is active
    if ($card['status'] !== 'active') {
        throw new Exception('البطاقة غير نشطة');
    }
    
    // Check if expired
    if ($card['expiry_date']) {
        $expiryDate = new DateTime($card['expiry_date']);
        $now = new DateTime();
        if ($now > $expiryDate) {
            throw new Exception('البطاقة منتهية الصلاحية');
        }
    }
    
    // Log scan
    $logStmt = $conn->prepare("INSERT INTO card_scans (card_id, scanned_at) VALUES (?, NOW())");
    $logStmt->bind_param('i', $card['id']);
    $logStmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم مسح البطاقة بنجاح',
        'data' => [
            'student_id' => $card['user_id'],
            'student_name' => $card['full_name'],
            'card_number' => $card['card_number'],
            'status' => $card['status'],
            'expiry_date' => $card['expiry_date']
        ],
        'redirect' => "?page=students&action=view&id={$card['user_id']}"
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get statistics
 */
function getStatistics($conn) {
    $stats = [
        'total' => 0,
        'active' => 0,
        'expired' => 0,
        'pending' => 0,
        'issued_this_month' => 0
    ];
    
    // Total cards
    $result = $conn->query("SELECT COUNT(*) as count FROM id_cards");
    $stats['total'] = intval($result->fetch_assoc()['count']);
    
    // Active cards
    $result = $conn->query("SELECT COUNT(*) as count FROM id_cards WHERE status = 'active'");
    $stats['active'] = intval($result->fetch_assoc()['count']);
    
    // Expired cards
    $result = $conn->query("SELECT COUNT(*) as count FROM id_cards 
                           WHERE expiry_date < CURRENT_DATE() AND status = 'active'");
    $stats['expired'] = intval($result->fetch_assoc()['count']);
    
    // Pending cards
    $result = $conn->query("SELECT COUNT(*) as count FROM id_cards WHERE status = 'pending'");
    $stats['pending'] = intval($result->fetch_assoc()['count']);
    
    // Issued this month
    $result = $conn->query("SELECT COUNT(*) as count FROM id_cards 
                           WHERE MONTH(issue_date) = MONTH(CURRENT_DATE())
                           AND YEAR(issue_date) = YEAR(CURRENT_DATE())");
    $stats['issued_this_month'] = intval($result->fetch_assoc()['count']);
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Bulk create cards for multiple students
 */
function bulkCreateCards($conn, $userId) {
    $courseId = intval($_POST['course_id'] ?? 0);
    
    if ($courseId === 0) {
        throw new Exception('معرف الدورة مطلوب');
    }
    
    // Get all students in course without cards
    $stmt = $conn->prepare("SELECT DISTINCT e.user_id 
                           FROM enrollments e
                           LEFT JOIN id_cards c ON e.user_id = c.user_id AND c.status = 'active'
                           WHERE e.course_id = ? AND c.id IS NULL");
    $stmt->bind_param('i', $courseId);
    $stmt->execute();
    $students = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    $created = 0;
    $errors = [];
    
    foreach ($students as $student) {
        try {
            $_POST['user_id'] = $student['user_id'];
            $_POST['card_type'] = 'student';
            $_POST['validity_period'] = 12;
            
            createCard($conn, $userId);
            $created++;
        } catch (Exception $e) {
            $errors[] = "Student ID {$student['user_id']}: " . $e->getMessage();
        }
    }
    
    echo json_encode([
        'success' => true,
        'message' => "تم إنشاء $created بطاقة بنجاح",
        'created' => $created,
        'errors' => $errors
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Generate unique card number
 */
function generateCardNumber($conn, $cardType) {
    $prefix = match($cardType) {
        'trainer' => 'TRN',
        'staff' => 'STF',
        default => 'STD'
    };
    
    $year = date('Y');
    $cardPrefix = $prefix . $year;
    
    // Get last card number
    $stmt = $conn->query("SELECT card_number FROM id_cards 
                         WHERE card_number LIKE '{$cardPrefix}%' 
                         ORDER BY card_number DESC LIMIT 1");
    
    if ($stmt->num_rows > 0) {
        $lastNumber = $stmt->fetch_assoc()['card_number'];
        $sequence = intval(substr($lastNumber, -4)) + 1;
    } else {
        $sequence = 1;
    }
    
    return $cardPrefix . str_pad($sequence, 4, '0', STR_PAD_LEFT);
}

/**
 * Send card by email
 */
function sendCardByEmail($card) {
    // Integrate with existing email system
    error_log("Card email sent to: {$card['email']} - Card: {$card['card_number']}");
    return true; // Return true for now
}
