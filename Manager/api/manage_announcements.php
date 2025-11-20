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
const UPLOAD_DIR = __DIR__ . '/../../uploads/announcements/';
const BASE_URL = '/uploads/announcements/';

try {
    if ($method === 'GET') {
        $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
        $limit = isset($_GET['limit']) ? max(1, min(100, (int) $_GET['limit'])) : 50;

        if ($id > 0) {
            $stmt = $conn->prepare('SELECT id, title, content, media_url, created_at, updated_at FROM notifications WHERE id = ? LIMIT 1');
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

        $stmt = $conn->prepare('SELECT id, title, content, media_url, created_at, updated_at FROM notifications ORDER BY created_at DESC LIMIT ?');
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

    // For POST, we use $_POST and $_FILES instead of php://input
    $action = $_POST['action'] ?? '';

    if ($action === 'create') {
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $media_url = null;

        if ($title === '' || $content === '') {
            respond(false, 'يرجى تعبئة عنوان ومحتوى الإعلان');
        }

        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['media'];
            $fileName = uniqid() . '-' . basename($file['name']);
            $targetPath = UPLOAD_DIR . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $media_url = BASE_URL . $fileName;
            } else {
                respond(false, 'فشل رفع الملف');
            }
        }

        $stmt = $conn->prepare('INSERT INTO notifications (title, content, media_url, user_id) VALUES (?, ?, ?, ?)');
        $stmt->bind_param('sssi', $title, $content, $media_url, $userId);
        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, 'فشل إنشاء الإعلان: ' . $stmt->error);
        }
        $newId = $stmt->insert_id;
        $stmt->close();

        $stmt = $conn->prepare('SELECT id, title, content, media_url, created_at, updated_at FROM notifications WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $newId);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond(true, 'تم إنشاء الإعلان بنجاح', ['data' => $announcement]);
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
        $title = trim($_POST['title'] ?? '');
        $content = trim($_POST['content'] ?? '');
        $media_url = $_POST['existing_media_url'] ?? null;

        if ($id <= 0 || $title === '' || $content === '') {
            respond(false, 'بيانات التحديث غير مكتملة');
        }

        if (isset($_FILES['media']) && $_FILES['media']['error'] === UPLOAD_ERR_OK) {
            // Delete old file if it exists
            if ($media_url) {
                $oldFilePath = UPLOAD_DIR . basename($media_url);
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
            }

            $file = $_FILES['media'];
            $fileName = uniqid() . '-' . basename($file['name']);
            $targetPath = UPLOAD_DIR . $fileName;

            if (move_uploaded_file($file['tmp_name'], $targetPath)) {
                $media_url = BASE_URL . $fileName;
            } else {
                respond(false, 'فشل رفع الملف الجديد');
            }
        }

        $stmt = $conn->prepare('UPDATE notifications SET title = ?, content = ?, media_url = ?, updated_at = NOW() WHERE id = ?');
        $stmt->bind_param('sssi', $title, $content, $media_url, $id);
        if (!$stmt->execute()) {
            $stmt->close();
            respond(false, 'فشل تحديث الإعلان: ' . $stmt->error);
        }
        $stmt->close();

        $stmt = $conn->prepare('SELECT id, title, content, media_url, created_at, updated_at FROM notifications WHERE id = ? LIMIT 1');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $announcement = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        respond(true, 'تم تحديث الإعلان بنجاح', ['data' => $announcement]);
    }

    if ($action === 'delete') {
        $payload = json_decode(file_get_contents('php://input'), true) ?? [];
        $id = isset($payload['id']) ? (int) $payload['id'] : 0;
        if ($id <= 0) {
            respond(false, 'معرّف الإعلان مطلوب للحذف');
        }

        // Get media_url before deleting
        $stmt = $conn->prepare('SELECT media_url FROM notifications WHERE id = ?');
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $announcement = $result->fetch_assoc();
        $stmt->close();

        if ($announcement && $announcement['media_url']) {
            $filePath = UPLOAD_DIR . basename($announcement['media_url']);
            if (file_exists($filePath)) {
                unlink($filePath);
            }
        }

        $stmt = $conn->prepare('DELETE FROM notifications WHERE id = ?');
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
