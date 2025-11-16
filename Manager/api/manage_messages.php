<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR], true)) {
        http_response_code(500);
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
        }
        $payload = [
            'success' => false,
            'message' => 'CRASH (Fatal Error): ' . $error['message'] . ' in ' . $error['file'] . ' on line ' . $error['line']
        ];
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
        echo $encoded === false
            ? '{"success":false,"message":"Fatal Error & JSON encoding failed."}'
            : $encoded;
    }
});

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../platform/db.php';

function respond(array $payload, int $status = 200): void
{
    http_response_code($status);
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        echo '{"success":false,"message":"JSON encoding failed."}';
    } else {
        echo $encoded;
    }
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$userId || !in_array($userRole, ['manager', 'technical', 'trainer', 'student'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'GET') {
    $mode = $_GET['mode'] ?? '';

    if (isset($_GET['message_id'])) {
        $messageId = (int) $_GET['message_id'];
        if ($messageId <= 0) {
            respond(['success' => false, 'message' => 'معرّف الرسالة غير صالح'], 400);
        }

        $stmt = $conn->prepare(
            'SELECT m.message_id, m.sender_id, m.recipient_id, m.subject, m.body, m.is_read, m.created_at, m.read_at,
                    sender.full_name AS sender_name, sender.role AS sender_role,
                    recipient.full_name AS recipient_name, recipient.role AS recipient_role
             FROM messages m
             JOIN users sender ON sender.id = m.sender_id
             JOIN users recipient ON recipient.id = m.recipient_id
             WHERE m.message_id = ? AND (m.sender_id = ? OR m.recipient_id = ?)
             LIMIT 1'
        );
        $stmt->bind_param('iii', $messageId, $userId, $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        $message = $result->fetch_assoc();
        $stmt->close();

        if (!$message) {
            respond(['success' => false, 'message' => 'الرسالة غير موجودة أو لا تملك صلاحية الوصول إليها'], 404);
        }

        respond(['success' => true, 'message' => $message]);
    }

    if ($mode === 'recipients') {
        $search = trim($_GET['q'] ?? '');
        if ($search !== '') {
            $like = '%' . $search . '%';
            $stmt = $conn->prepare(
                'SELECT id, full_name, role, email
                 FROM users
                 WHERE id != ? AND (full_name LIKE ? OR email LIKE ?)
                 ORDER BY full_name ASC
                 LIMIT 50'
            );
            $stmt->bind_param('iss', $userId, $like, $like);
        } else {
            $stmt = $conn->prepare(
                'SELECT id, full_name, role, email
                 FROM users
                 WHERE id != ?
                 ORDER BY full_name ASC
                 LIMIT 100'
            );
            $stmt->bind_param('i', $userId);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        $recipients = $result->fetch_all(MYSQLI_ASSOC);
        $stmt->close();

        respond(['success' => true, 'recipients' => $recipients]);
    }

    $box = strtolower($_GET['box'] ?? 'inbox');
    $box = in_array($box, ['inbox', 'sent'], true) ? $box : 'inbox';
    $withUser = isset($_GET['with']) ? (int) $_GET['with'] : 0;
    $limit = (int) ($_GET['limit'] ?? 50);
    if ($limit < 5) {
        $limit = 5;
    }
    if ($limit > 200) {
        $limit = 200;
    }

    if ($withUser > 0) {
        $stmt = $conn->prepare(
            'SELECT m.message_id, m.sender_id, m.recipient_id, m.subject, m.body, m.is_read, m.created_at, m.read_at,
                    sender.full_name AS sender_name, sender.role AS sender_role,
                    recipient.full_name AS recipient_name, recipient.role AS recipient_role
             FROM messages m
             JOIN users sender ON sender.id = m.sender_id
             JOIN users recipient ON recipient.id = m.recipient_id
             WHERE ((m.sender_id = ? AND m.recipient_id = ?) OR (m.sender_id = ? AND m.recipient_id = ?))
             ORDER BY m.created_at DESC
             LIMIT ?'
        );
        $stmt->bind_param('iiiii', $userId, $withUser, $withUser, $userId, $limit);
    } elseif ($box === 'sent') {
        $stmt = $conn->prepare(
            'SELECT m.message_id, m.sender_id, m.recipient_id, m.subject, m.body, m.is_read, m.created_at, m.read_at,
                    sender.full_name AS sender_name, sender.role AS sender_role,
                    recipient.full_name AS recipient_name, recipient.role AS recipient_role
             FROM messages m
             JOIN users sender ON sender.id = m.sender_id
             JOIN users recipient ON recipient.id = m.recipient_id
             WHERE m.sender_id = ?
             ORDER BY m.created_at DESC
             LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
    } else {
        $stmt = $conn->prepare(
            'SELECT m.message_id, m.sender_id, m.recipient_id, m.subject, m.body, m.is_read, m.created_at, m.read_at,
                    sender.full_name AS sender_name, sender.role AS sender_role,
                    recipient.full_name AS recipient_name, recipient.role AS recipient_role
             FROM messages m
             JOIN users sender ON sender.id = m.sender_id
             JOIN users recipient ON recipient.id = m.recipient_id
             WHERE m.recipient_id = ?
             ORDER BY m.created_at DESC
             LIMIT ?'
        );
        $stmt->bind_param('ii', $userId, $limit);
    }

    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    respond([
        'success' => true,
        'box' => $box,
        'messages' => $messages,
        'with' => $withUser > 0 ? $withUser : null
    ]);
}

if ($method === 'POST') {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    if (!is_array($data)) {
        $data = $_POST;
    }
    if (!is_array($data)) {
        respond(['success' => false, 'message' => 'بيانات غير صالحة'], 400);
    }

    $action = $data['action'] ?? 'send';

    if ($action === 'send') {
        $recipientId = isset($data['recipient_id']) ? (int) $data['recipient_id'] : 0;
        $subject = trim((string) ($data['subject'] ?? ''));
        $body = trim((string) ($data['body'] ?? ''));

        if ($recipientId <= 0 || $body === '') {
            respond(['success' => false, 'message' => 'الرجاء تحديد المستقبل وكتابة محتوى الرسالة'], 400);
        }
        if ($recipientId === (int) $userId) {
            respond(['success' => false, 'message' => 'لا يمكنك إرسال رسالة إلى نفسك'], 400);
        }

        $userStmt = $conn->prepare('SELECT id FROM users WHERE id = ? LIMIT 1');
        $userStmt->bind_param('i', $recipientId);
        $userStmt->execute();
        $exists = $userStmt->get_result()->fetch_assoc();
        $userStmt->close();
        if (!$exists) {
            respond(['success' => false, 'message' => 'المستخدم المستقبل غير موجود'], 404);
        }

        if ($subject === '') {
            $subject = 'بدون عنوان';
        }

        $stmt = $conn->prepare(
            'INSERT INTO messages (sender_id, recipient_id, subject, body, is_read, created_at)
             VALUES (?, ?, ?, ?, 0, NOW())'
        );
        $stmt->bind_param('iiss', $userId, $recipientId, $subject, $body);

        if (!$stmt->execute()) {
            $error = $stmt->error;
            $stmt->close();
            respond(['success' => false, 'message' => 'فشل إرسال الرسالة: ' . $error], 500);
        }
        $messageId = $stmt->insert_id;
        $stmt->close();

        respond(['success' => true, 'message' => 'تم إرسال الرسالة', 'message_id' => $messageId]);
    }

    if ($action === 'mark_read') {
        $messageId = isset($data['message_id']) ? (int) $data['message_id'] : 0;
        if ($messageId <= 0) {
            respond(['success' => false, 'message' => 'معرّف الرسالة مطلوب'], 400);
        }

        $stmt = $conn->prepare('UPDATE messages SET is_read = 1, read_at = NOW() WHERE message_id = ? AND recipient_id = ?');
        $stmt->bind_param('ii', $messageId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            respond(['success' => false, 'message' => 'لا يمكن تحديث هذه الرسالة'], 404);
        }

        respond(['success' => true, 'message' => 'تم تحديث حالة الرسالة']);
    }

    if ($action === 'delete') {
        $messageId = isset($data['message_id']) ? (int) $data['message_id'] : 0;
        if ($messageId <= 0) {
            respond(['success' => false, 'message' => 'معرّف الرسالة مطلوب'], 400);
        }

        $stmt = $conn->prepare('DELETE FROM messages WHERE message_id = ? AND (sender_id = ? OR recipient_id = ?)');
        $stmt->bind_param('iii', $messageId, $userId, $userId);
        $stmt->execute();
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            respond(['success' => false, 'message' => 'لم يتم العثور على الرسالة أو لا تملك صلاحية حذفها'], 404);
        }

        respond(['success' => true, 'message' => 'تم حذف الرسالة']);
    }

    if ($action === 'mark_all_read') {
        $stmt = $conn->prepare('UPDATE messages SET is_read = 1, read_at = NOW() WHERE recipient_id = ? AND is_read = 0');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $updated = $stmt->affected_rows;
        $stmt->close();

        respond(['success' => true, 'message' => 'تم تحديد كل الرسائل كمقروءة', 'updated' => $updated]);
    }

    respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
}

respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
