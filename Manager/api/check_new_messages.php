<?php
/**
 * check_new_messages - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


header('Content-Type: application/json; charset=utf-8');

// التحقق من تسجيل الدخول
if (!isset($_SESSION['user_id'])) {
	http_response_code(401);
	echo json_encode([
		'success' => false,
		'message' => 'يجب تسجيل الدخول أولاً',
		'new_count' => 0
	]);
	exit;
}

require_once __DIR__ . '/../../database/db.php';

$userId = (int) $_SESSION['user_id'];

try {
	// عد الرسائل غير المقروءة
	$stmt = $conn->prepare("
		SELECT COUNT(*) as unread_count
		FROM messages
		WHERE recipient_id = ? AND is_read = 0
	");
	$stmt->bind_param('i', $userId);
	$stmt->execute();
	$result = $stmt->get_result();
	$row = $result->fetch_assoc();
	$unreadCount = (int) ($row['unread_count'] ?? 0);
	$stmt->close();
	
	// الحصول على آخر رسالة غير مقروءة (اختياري)
	$lastMessage = null;
	if ($unreadCount > 0) {
		$stmtLast = $conn->prepare("
			SELECT 
				m.message_id,
				m.subject,
				m.body,
				m.created_at,
				sender.full_name AS sender_name,
				sender.role AS sender_role
			FROM messages m
			JOIN users sender ON m.sender_id = sender.id
			WHERE m.recipient_id = ? AND m.is_read = 0
			ORDER BY m.created_at DESC
			LIMIT 1
		");
		$stmtLast->bind_param('i', $userId);
		$stmtLast->execute();
		$resultLast = $stmtLast->get_result();
		$lastMessage = $resultLast->fetch_assoc();
		$stmtLast->close();
	}
	
	echo json_encode([
		'success' => true,
		'new_count' => $unreadCount,
		'last_message' => $lastMessage,
		'timestamp' => date('Y-m-d H:i:s')
	]);
	
} catch (Exception $e) {
	http_response_code(500);
	echo json_encode([
		'success' => false,
		'message' => 'خطأ في الخادم',
		'new_count' => 0
	]);
}
