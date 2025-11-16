<?php
/**
 * ================================================
 * API: إدارة الإعدادات (Settings CRUD)
 * ================================================
 * الهدف: السماح للمدير بتعديل إعدادات النظام
 * الصلاحية: manager فقط
 * ================================================
 */

session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR])) {
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

header('Content-Type: application/json; charset=utf-8');

// --- قراءة بيانات JSON القادمة من الواجهة ودمجها في $_POST لتوحيد المعالجة ---
// ملاحظة: بعض المتصفحات ترسل header مثل: application/json; charset=UTF-8
// لذا نتحقق ببداية السلسلة بدلاً من المطابقة التامة
$contentType = trim($_SERVER['CONTENT_TYPE'] ?? '');
if ($contentType && stripos($contentType, 'application/json') === 0) {
    $content = trim(file_get_contents('php://input'));
    $decoded = json_decode($content, true);
    if (is_array($decoded)) {
        $_POST = array_merge($_POST, $decoded);
    }
}

// التحقق من الصلاحية مبكراً
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'manager') {
    echo json_encode(['success' => false, 'message' => 'غير مصرح. هذه الصفحة للمدير العام فقط.']);
    exit;
}

require_once __DIR__ . '/../../database/db.php'; // يوفر $conn (mysqli)

$action = $_GET['action'] ?? $_POST['action'] ?? null;

try {
    switch ($action) {
        case 'get':
            $settings = [];
            $result = $conn->query("SELECT setting_key, setting_value FROM settings");
            while ($row = $result->fetch_assoc()) {
                $settings[$row['setting_key']] = $row['setting_value'];
            }
            $result->free();
            echo json_encode(['success' => true, 'settings' => $settings]);
            break;

        case 'update':
        case 'update_batch':
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data || !isset($data['settings']) || !is_array($data['settings'])) {
                echo json_encode(['success' => false, 'message' => 'بيانات غير صحيحة.']);
                break;
            }
            $conn->begin_transaction();
            try {
                $stmt = $conn->prepare(
                    "INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) 
                     ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)"
                );
                foreach ($data['settings'] as $key => $value) {
                    $k = (string)$key;
                    $v = (string)$value;
                    $stmt->bind_param('ss', $k, $v);
                    $stmt->execute();
                }
                $stmt->close();
                $conn->commit();
                echo json_encode(['success' => true, 'message' => '✅ تم حفظ الإعدادات بنجاح.']);
            } catch (Throwable $tx) {
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'فشل الحفظ: ' . $tx->getMessage()]);
            }
            break;

        default:
            echo json_encode([
                'success' => false,
                'message' => "إجراء غير معروف: '$action'. الإجراءات المدعومة: get, update, update_batch"
            ]);
            break;
    }
} catch (Throwable $e) {
    echo json_encode([
        'success' => false,
        'message' => '❌ انهيار في الخادم: ' . $e->getMessage()
    ]);
}
