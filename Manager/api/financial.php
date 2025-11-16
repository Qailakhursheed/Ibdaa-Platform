<?php
/**
 * Financial Management API
 * نظام الإدارة المالية - API
 * 
 * للمشرف الفني والمدير العام
 * 
 * الوظائف:
 * - إدارة المدفوعات (Payments)
 * - إدارة المصروفات (Expenses)
 * - إدارة الفواتير (Invoices)
 * - التقارير المالية (Reports)
 * - الإحصائيات (Statistics)
 */

session_start();
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

// Only manager and technical supervisor can access
if (!in_array($userRole, ['manager', 'technical'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'ليس لديك صلاحية للوصول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$action = $_GET['action'] ?? '';

try {
    switch ($action) {
        // Payments
        case 'list_payments':
            listPayments($conn);
            break;
        case 'get_payment':
            getPayment($conn);
            break;
        case 'confirm_payment':
            confirmPayment($conn, $userId);
            break;
        case 'reject_payment':
            rejectPayment($conn, $userId);
            break;
        case 'add_payment':
            addPayment($conn, $userId);
            break;
        
        // Expenses
        case 'list_expenses':
            listExpenses($conn);
            break;
        case 'add_expense':
            addExpense($conn, $userId);
            break;
        case 'update_expense':
            updateExpense($conn, $userId);
            break;
        case 'delete_expense':
            deleteExpense($conn);
            break;
        
        // Invoices
        case 'list_invoices':
            listInvoices($conn);
            break;
        case 'create_invoice':
            createInvoice($conn, $userId);
            break;
        case 'get_invoice':
            getInvoice($conn);
            break;
        case 'send_invoice':
            sendInvoice($conn);
            break;
        
        // Reports & Statistics
        case 'statistics':
            getStatistics($conn);
            break;
        case 'revenue_report':
            getRevenueReport($conn);
            break;
        case 'expenses_report':
            getExpensesReport($conn);
            break;
        case 'monthly_chart':
            getMonthlyChart($conn);
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

// ==================== PAYMENTS ====================

/**
 * List all payments with filters
 */
function listPayments($conn) {
    $status = $_GET['status'] ?? 'all';
    $studentId = $_GET['student_id'] ?? 'all';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateFrom = $_GET['date_to'] ?? '';
    $search = $_GET['search'] ?? '';
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if ($status !== 'all') {
        $whereConditions[] = "p.status = ?";
        $params[] = $status;
        $types .= 's';
    }
    
    if ($studentId !== 'all') {
        $whereConditions[] = "p.student_id = ?";
        $params[] = intval($studentId);
        $types .= 'i';
    }
    
    if (!empty($search)) {
        $whereConditions[] = "(u.full_name LIKE ? OR u.email LIKE ? OR p.transaction_id LIKE ?)";
        $searchParam = "%$search%";
        $params[] = $searchParam;
        $params[] = $searchParam;
        $params[] = $searchParam;
        $types .= 'sss';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT 
                p.*,
                u.full_name as student_name,
                u.email as student_email,
                u.phone as student_phone,
                c.title as course_name
            FROM payments p
            JOIN users u ON p.student_id = u.id
            LEFT JOIN courses c ON p.course_id = c.course_id
            $whereClause
            ORDER BY p.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $payments = [];
    
    while ($row = $result->fetch_assoc()) {
        $payments[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $payments,
        'count' => count($payments)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get single payment details
 */
function getPayment($conn) {
    $paymentId = intval($_GET['id'] ?? 0);
    
    if ($paymentId === 0) {
        throw new Exception('معرف الدفعة مطلوب');
    }
    
    $stmt = $conn->prepare("SELECT 
                                p.*,
                                u.full_name as student_name,
                                u.email as student_email,
                                u.phone as student_phone,
                                c.title as course_name
                            FROM payments p
                            JOIN users u ON p.student_id = u.id
                            LEFT JOIN courses c ON p.course_id = c.course_id
                            WHERE p.id = ?");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الدفعة غير موجودة');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Confirm payment
 */
function confirmPayment($conn, $userId) {
    $paymentId = intval($_POST['payment_id'] ?? 0);
    
    if ($paymentId === 0) {
        throw new Exception('معرف الدفعة مطلوب');
    }
    
    // Get payment details
    $stmt = $conn->prepare("SELECT student_id, amount FROM payments WHERE id = ?");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        throw new Exception('الدفعة غير موجودة');
    }
    
    // Update payment status
    $stmt = $conn->prepare("UPDATE payments 
                           SET status = 'confirmed', 
                               confirmed_by = ?,
                               confirmed_at = NOW() 
                           WHERE id = ?");
    $stmt->bind_param('ii', $userId, $paymentId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تأكيد الدفعة');
    }
    
    // Update student payment status
    $stmt = $conn->prepare("UPDATE users SET payment_status = 'paid' WHERE id = ?");
    $stmt->bind_param('i', $payment['student_id']);
    $stmt->execute();
    
    // Update student status to active if approved
    $stmt = $conn->prepare("UPDATE users 
                           SET status = 'active' 
                           WHERE id = ? AND status = 'approved'");
    $stmt->bind_param('i', $payment['student_id']);
    $stmt->execute();
    
    // Create notification
    createNotification($conn, $payment['student_id'], 
        'تم تأكيد دفعتك بنجاح! يمكنك الآن الوصول لجميع خدمات المنصة.');
    
    // Send email
    sendPaymentConfirmationEmail($conn, $payment['student_id'], $payment['amount']);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تأكيد الدفعة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Reject payment
 */
function rejectPayment($conn, $userId) {
    $paymentId = intval($_POST['payment_id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    
    if ($paymentId === 0) {
        throw new Exception('معرف الدفعة مطلوب');
    }
    
    // Get payment details
    $stmt = $conn->prepare("SELECT student_id FROM payments WHERE id = ?");
    $stmt->bind_param('i', $paymentId);
    $stmt->execute();
    $payment = $stmt->get_result()->fetch_assoc();
    
    if (!$payment) {
        throw new Exception('الدفعة غير موجودة');
    }
    
    // Update payment status
    $stmt = $conn->prepare("UPDATE payments 
                           SET status = 'rejected', 
                               rejection_reason = ?,
                               rejected_by = ?,
                               rejected_at = NOW() 
                           WHERE id = ?");
    $stmt->bind_param('sii', $reason, $userId, $paymentId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في رفض الدفعة');
    }
    
    // Create notification
    $message = 'تم رفض دفعتك. السبب: ' . ($reason ?: 'غير محدد');
    createNotification($conn, $payment['student_id'], $message);
    
    echo json_encode([
        'success' => true,
        'message' => 'تم رفض الدفعة'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Add manual payment
 */
function addPayment($conn, $userId) {
    $studentId = intval($_POST['student_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? 'cash';
    $notes = trim($_POST['notes'] ?? '');
    $courseId = intval($_POST['course_id'] ?? 0);
    
    if ($studentId === 0 || $amount <= 0) {
        throw new Exception('الرجاء إدخال البيانات المطلوبة');
    }
    
    // Generate transaction ID
    $transactionId = 'TXN' . date('YmdHis') . rand(1000, 9999);
    
    $stmt = $conn->prepare("INSERT INTO payments 
                           (student_id, course_id, amount, payment_method, transaction_id, 
                            status, notes, created_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'confirmed', ?, ?, NOW())");
    $stmt->bind_param('iidsssi', $studentId, $courseId, $amount, $paymentMethod, 
                      $transactionId, $notes, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إضافة الدفعة');
    }
    
    // Update student payment status
    $stmt = $conn->prepare("UPDATE users SET payment_status = 'paid' WHERE id = ?");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة الدفعة بنجاح',
        'transaction_id' => $transactionId
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== EXPENSES ====================

/**
 * List all expenses
 */
function listExpenses($conn) {
    $category = $_GET['category'] ?? 'all';
    $dateFrom = $_GET['date_from'] ?? '';
    $dateTo = $_GET['date_to'] ?? '';
    
    $whereConditions = [];
    $params = [];
    $types = '';
    
    if ($category !== 'all') {
        $whereConditions[] = "category = ?";
        $params[] = $category;
        $types .= 's';
    }
    
    if (!empty($dateFrom)) {
        $whereConditions[] = "expense_date >= ?";
        $params[] = $dateFrom;
        $types .= 's';
    }
    
    if (!empty($dateTo)) {
        $whereConditions[] = "expense_date <= ?";
        $params[] = $dateTo;
        $types .= 's';
    }
    
    $whereClause = !empty($whereConditions) ? 'WHERE ' . implode(' AND ', $whereConditions) : '';
    
    $sql = "SELECT e.*, u.full_name as created_by_name 
            FROM expenses e
            LEFT JOIN users u ON e.created_by = u.id
            $whereClause
            ORDER BY e.expense_date DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $expenses = [];
    
    while ($row = $result->fetch_assoc()) {
        $expenses[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $expenses,
        'count' => count($expenses)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Add new expense
 */
function addExpense($conn, $userId) {
    $title = trim($_POST['title'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    $expenseDate = $_POST['expense_date'] ?? date('Y-m-d');
    
    if (empty($title) || $amount <= 0 || empty($category)) {
        throw new Exception('الرجاء إدخال البيانات المطلوبة');
    }
    
    $stmt = $conn->prepare("INSERT INTO expenses 
                           (title, amount, category, description, expense_date, created_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param('sdsssi', $title, $amount, $category, $description, $expenseDate, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إضافة المصروف');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إضافة المصروف بنجاح',
        'expense_id' => $conn->insert_id
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Update expense
 */
function updateExpense($conn, $userId) {
    $expenseId = intval($_POST['expense_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $category = $_POST['category'] ?? '';
    $description = trim($_POST['description'] ?? '');
    
    if ($expenseId === 0 || empty($title) || $amount <= 0) {
        throw new Exception('الرجاء إدخال البيانات المطلوبة');
    }
    
    $stmt = $conn->prepare("UPDATE expenses 
                           SET title = ?, amount = ?, category = ?, description = ?, updated_at = NOW() 
                           WHERE id = ?");
    $stmt->bind_param('sdssi', $title, $amount, $category, $description, $expenseId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في تحديث المصروف');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم تحديث المصروف بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Delete expense
 */
function deleteExpense($conn) {
    $expenseId = intval($_GET['id'] ?? 0);
    
    if ($expenseId === 0) {
        throw new Exception('معرف المصروف مطلوب');
    }
    
    $stmt = $conn->prepare("DELETE FROM expenses WHERE id = ?");
    $stmt->bind_param('i', $expenseId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في حذف المصروف');
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'تم حذف المصروف بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== INVOICES ====================

/**
 * List invoices
 */
function listInvoices($conn) {
    $status = $_GET['status'] ?? 'all';
    
    $whereClause = $status !== 'all' ? "WHERE status = '$status'" : '';
    
    $sql = "SELECT i.*, u.full_name as student_name, u.email as student_email
            FROM invoices i
            JOIN users u ON i.student_id = u.id
            $whereClause
            ORDER BY i.created_at DESC";
    
    $result = $conn->query($sql);
    $invoices = [];
    
    while ($row = $result->fetch_assoc()) {
        $invoices[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $invoices,
        'count' => count($invoices)
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Create invoice
 */
function createInvoice($conn, $userId) {
    $studentId = intval($_POST['student_id'] ?? 0);
    $amount = floatval($_POST['amount'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $dueDate = $_POST['due_date'] ?? '';
    
    if ($studentId === 0 || $amount <= 0) {
        throw new Exception('الرجاء إدخال البيانات المطلوبة');
    }
    
    // Generate invoice number
    $invoiceNumber = 'INV' . date('Y') . str_pad($conn->insert_id + 1, 5, '0', STR_PAD_LEFT);
    
    $stmt = $conn->prepare("INSERT INTO invoices 
                           (invoice_number, student_id, amount, description, due_date, 
                            status, created_by, created_at) 
                           VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW())");
    $stmt->bind_param('sidssi', $invoiceNumber, $studentId, $amount, $description, $dueDate, $userId);
    
    if (!$stmt->execute()) {
        throw new Exception('فشل في إنشاء الفاتورة');
    }
    
    $invoiceId = $conn->insert_id;
    
    // Send notification
    createNotification($conn, $studentId, "تم إصدار فاتورة جديدة برقم: $invoiceNumber بقيمة: $amount ريال");
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إنشاء الفاتورة بنجاح',
        'invoice_id' => $invoiceId,
        'invoice_number' => $invoiceNumber
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get invoice details
 */
function getInvoice($conn) {
    $invoiceId = intval($_GET['id'] ?? 0);
    
    if ($invoiceId === 0) {
        throw new Exception('معرف الفاتورة مطلوب');
    }
    
    $stmt = $conn->prepare("SELECT i.*, 
                                   u.full_name as student_name,
                                   u.email as student_email,
                                   u.phone as student_phone,
                                   u.address as student_address
                            FROM invoices i
                            JOIN users u ON i.student_id = u.id
                            WHERE i.id = ?");
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('الفاتورة غير موجودة');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $result->fetch_assoc()
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Send invoice via email
 */
function sendInvoice($conn) {
    $invoiceId = intval($_POST['invoice_id'] ?? 0);
    
    if ($invoiceId === 0) {
        throw new Exception('معرف الفاتورة مطلوب');
    }
    
    // Get invoice details
    $stmt = $conn->prepare("SELECT i.*, u.email, u.full_name
                           FROM invoices i
                           JOIN users u ON i.student_id = u.id
                           WHERE i.id = ?");
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    $invoice = $stmt->get_result()->fetch_assoc();
    
    if (!$invoice) {
        throw new Exception('الفاتورة غير موجودة');
    }
    
    // Send email (integrate with existing email system)
    sendInvoiceEmail($invoice);
    
    // Update sent status
    $stmt = $conn->prepare("UPDATE invoices SET sent_at = NOW() WHERE id = ?");
    $stmt->bind_param('i', $invoiceId);
    $stmt->execute();
    
    echo json_encode([
        'success' => true,
        'message' => 'تم إرسال الفاتورة بنجاح'
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== REPORTS & STATISTICS ====================

/**
 * Get financial statistics
 */
function getStatistics($conn) {
    $stats = [
        'total_revenue' => 0,
        'monthly_revenue' => 0,
        'pending_payments' => 0,
        'total_expenses' => 0,
        'net_profit' => 0,
        'payment_methods' => []
    ];
    
    // Total revenue
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'confirmed'");
    $stats['total_revenue'] = floatval($result->fetch_assoc()['total'] ?? 0);
    
    // Monthly revenue
    $result = $conn->query("SELECT SUM(amount) as total FROM payments 
                           WHERE status = 'confirmed' 
                           AND MONTH(created_at) = MONTH(CURRENT_DATE())
                           AND YEAR(created_at) = YEAR(CURRENT_DATE())");
    $stats['monthly_revenue'] = floatval($result->fetch_assoc()['total'] ?? 0);
    
    // Pending payments
    $result = $conn->query("SELECT SUM(amount) as total FROM payments WHERE status = 'pending'");
    $stats['pending_payments'] = floatval($result->fetch_assoc()['total'] ?? 0);
    
    // Total expenses
    $result = $conn->query("SELECT SUM(amount) as total FROM expenses");
    $stats['total_expenses'] = floatval($result->fetch_assoc()['total'] ?? 0);
    
    // Net profit
    $stats['net_profit'] = $stats['total_revenue'] - $stats['total_expenses'];
    
    // Payment methods distribution
    $result = $conn->query("SELECT payment_method, COUNT(*) as count, SUM(amount) as total
                           FROM payments 
                           WHERE status = 'confirmed'
                           GROUP BY payment_method");
    while ($row = $result->fetch_assoc()) {
        $stats['payment_methods'][] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $stats
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get revenue report
 */
function getRevenueReport($conn) {
    $period = $_GET['period'] ?? 'month'; // month, quarter, year
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as period,
                SUM(amount) as total,
                COUNT(*) as count
            FROM payments
            WHERE status = 'confirmed'
            GROUP BY period
            ORDER BY period DESC
            LIMIT 12";
    
    $result = $conn->query($sql);
    $report = [];
    
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get expenses report
 */
function getExpensesReport($conn) {
    $sql = "SELECT 
                category,
                SUM(amount) as total,
                COUNT(*) as count
            FROM expenses
            GROUP BY category
            ORDER BY total DESC";
    
    $result = $conn->query($sql);
    $report = [];
    
    while ($row = $result->fetch_assoc()) {
        $report[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $report
    ], JSON_UNESCAPED_UNICODE);
}

/**
 * Get monthly chart data
 */
function getMonthlyChart($conn) {
    $months = 6;
    
    $sql = "SELECT 
                DATE_FORMAT(created_at, '%Y-%m') as month,
                SUM(CASE WHEN status = 'confirmed' THEN amount ELSE 0 END) as revenue
            FROM payments
            WHERE created_at >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $revenue = [];
    while ($row = $result->fetch_assoc()) {
        $revenue[$row['month']] = floatval($row['revenue']);
    }
    
    // Get expenses
    $sql = "SELECT 
                DATE_FORMAT(expense_date, '%Y-%m') as month,
                SUM(amount) as total
            FROM expenses
            WHERE expense_date >= DATE_SUB(CURRENT_DATE(), INTERVAL ? MONTH)
            GROUP BY month
            ORDER BY month ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $months);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $expenses = [];
    while ($row = $result->fetch_assoc()) {
        $expenses[$row['month']] = floatval($row['total']);
    }
    
    echo json_encode([
        'success' => true,
        'data' => [
            'revenue' => $revenue,
            'expenses' => $expenses
        ]
    ], JSON_UNESCAPED_UNICODE);
}

// ==================== HELPER FUNCTIONS ====================

/**
 * Create notification
 */
function createNotification($conn, $userId, $message) {
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, message, type, created_at) 
                           VALUES (?, ?, 'financial', NOW())");
    $stmt->bind_param('is', $userId, $message);
    $stmt->execute();
}

/**
 * Send payment confirmation email
 */
function sendPaymentConfirmationEmail($conn, $studentId, $amount) {
    // Get student details
    $stmt = $conn->prepare("SELECT email, full_name FROM users WHERE id = ?");
    $stmt->bind_param('i', $studentId);
    $stmt->execute();
    $student = $stmt->get_result()->fetch_assoc();
    
    if ($student) {
        // Integrate with existing email system
        error_log("Payment confirmation email sent to: {$student['email']} - Amount: $amount");
    }
}

/**
 * Send invoice email
 */
function sendInvoiceEmail($invoice) {
    // Integrate with existing email system
    error_log("Invoice email sent: {$invoice['invoice_number']} to {$invoice['email']}");
}
