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
        if ($encoded === false) {
            echo '{"success": false, "message": "Fatal Error & JSON encoding failed."}';
        } else {
            echo $encoded;
        }
    }
});

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

$userId = $_SESSION['user_id'] ?? null;
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

function respond($success, $message = '', array $extra = []) {
    $payload = array_merge(['success' => $success], $extra);
    if ($message !== '') {
        $payload['message'] = $message;
    }
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

function requireManagerAccess($userId, $userRole) {
    if (!$userId || !in_array($userRole, ['manager', 'technical'], true)) {
        respond(false, 'Unauthorized access');
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

        if ($id > 0) {
            $stmt = $conn->prepare('SELECT id, title, content, created_at, updated_at FROM announcements WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $result = $stmt->get_result();
            $announcement = $result->fetch_assoc();
            $stmt->close();

            if (!$announcement) {
                respond(false, 'الإعلان غير موجود');
            }

            respond(true, '', ['data' => $announcement]);
        }

        $stmt = $conn->prepare('SELECT id, title, content, created_at, updated_at FROM announcements ORDER BY created_at DESC LIMIT ?');
        $stmt->bind_param('i', $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        $announcements = [];
        while ($row = $result->fetch_assoc()) {
            $announcements[] = $row;
        }
        $stmt->close();

        respond(true, '', ['data' => $announcements]);
    }

    // كل العمليات الأخرى تتطلب صلاحيات المدير/الفني
    requireManagerAccess($userId, $userRole);

    $payload = json_decode(file_get_contents('php://input'), true) ?? [];
    $action = $payload['action'] ?? '';

    if ($action === 'create') {
        $title = trim($payload['title'] ?? '');
        $content = trim($payload['content'] ?? '');

        if ($title === '' || $content === '') {
            respond(false, 'يرجى تعبئة عنوان ومحتوى الإعلان');
        }

        $stmt = $conn->prepare('INSERT INTO announcements (title, content) VALUES (?, ?)');
        $stmt->bind_param('ss', $title, $content);
        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, 'فشل إنشاء الإعلان: ' . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('SELECT id, title, content, created_at, updated_at FROM announcements WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond(true, 'تم إنشاء الإعلان بنجاح', ['data' => $announcement]);
    }

    if ($action === 'update') {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        $title = trim($payload['title'] ?? '');
        $content = trim($payload['content'] ?? '');

        if ($id <= 0 || $title === '' || $content === '') {
            respond(false, 'بيانات التحديث غير مكتملة');
        }

        $stmt = $conn->prepare('UPDATE announcements SET title = ?, content = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('ssi', $title, $content, $id);
        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, 'فشل تحديث الإعلان: ' . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare('SELECT id, title, content, created_at, updated_at FROM announcements WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond(true, 'تم تحديث الإعلان بنجاح', ['data' => $announcement]);
    }

    if ($action === 'delete') {
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($id <= 0) {
            respond(false, 'معرّف الإعلان مطلوب للحذف');
        }

        $stmt = $conn->prepare('DELETE FROM announcements WHERE id = ?');
        $stmt->bind_param('i', $id);
        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, 'فشل حذف الإعلان: ' . $stmt->error);
        }
        $affected = $stmt->affected_rows;
        $stmt->close();

        if ($affected === 0) {
            respond(false, 'الإعلان المطلوب حذفه غير موجود');
        }

        respond(true, 'تم حذف الإعلان بنجاح');
    }

    respond(false, 'Action not supported');
} catch (Throwable $e) {
    respond(false, $e->getMessage());
}
