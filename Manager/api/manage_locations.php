<?php
session_start();

ini_set('display_errors', 1);
error_reporting(E_ALL);

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

require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

$method = $_SERVER['REQUEST_METHOD'];

function sanitize_name($name) { return trim($name); }

try {
    if ($method === 'GET') {
        $target = $_GET['target'] ?? '';
        if ($target === 'regions') {
            $res = $conn->query("SELECT id, name FROM regions ORDER BY name ASC");
            $rows = [];
            while ($r = $res->fetch_assoc()) { $rows[] = $r; }
            echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
        }
        if ($target === 'districts') {
            $region_id = intval($_GET['region_id'] ?? 0);
            if ($region_id <= 0) { echo json_encode(['success'=>false,'message'=>'region_id مطلوب'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare("SELECT id, region_id, name FROM districts WHERE region_id=? ORDER BY name ASC");
            $stmt->bind_param('i',$region_id);
            $stmt->execute(); $res = $stmt->get_result();
            $rows = []; while ($r = $res->fetch_assoc()) { $rows[] = $r; }
            echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
        }
        if ($target === 'sub_districts') {
            $district_id = intval($_GET['district_id'] ?? 0);
            if ($district_id <= 0) { echo json_encode(['success'=>false,'message'=>'district_id مطلوب'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare("SELECT id, district_id, name FROM sub_districts WHERE district_id=? ORDER BY name ASC");
            $stmt->bind_param('i',$district_id);
            $stmt->execute(); $res = $stmt->get_result();
            $rows = []; while ($r = $res->fetch_assoc()) { $rows[] = $r; }
            echo json_encode(['success'=>true,'data'=>$rows], JSON_UNESCAPED_UNICODE); exit;
        }
        echo json_encode(['success'=>false,'message'=>'target غير معروف'], JSON_UNESCAPED_UNICODE); exit;
    }

    if ($method === 'POST') {
        // تأمين عمليات التعديل للمدير أو المشرف الفني فقط
        if (!$user_id || !in_array($user_role, ['manager','technical'])) {
            echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $input_raw = file_get_contents('php://input');
        $input = json_decode($input_raw, true);
        if (!is_array($input)) { // دعم x-www-form-urlencoded
            $input = $_POST;
        }
        $action = $input['action'] ?? '';
        $target = $input['target'] ?? '';

        // CREATE operations
        if ($action === 'create' && $target === 'region') {
            $name = sanitize_name($input['name'] ?? '');
            if ($name === '') { echo json_encode(['success'=>false,'message'=>'اسم المحافظة مطلوب'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('INSERT INTO regions (name) VALUES (?)');
            $stmt->bind_param('s',$name);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم إضافة المحافظة','id'=>$conn->insert_id], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل الإضافة: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'create' && $target === 'district') {
            $region_id = intval($input['region_id'] ?? 0);
            $name = sanitize_name($input['name'] ?? '');
            if ($region_id<=0 || $name==='') { echo json_encode(['success'=>false,'message'=>'region_id و name مطلوبان'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('INSERT INTO districts (region_id,name) VALUES (?,?)');
            $stmt->bind_param('is',$region_id,$name);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم إضافة المديرية','id'=>$conn->insert_id], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل الإضافة: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'create' && $target === 'sub_district') {
            $district_id = intval($input['district_id'] ?? 0);
            $name = sanitize_name($input['name'] ?? '');
            if ($district_id<=0 || $name==='') { echo json_encode(['success'=>false,'message'=>'district_id و name مطلوبان'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('INSERT INTO sub_districts (district_id,name) VALUES (?,?)');
            $stmt->bind_param('is',$district_id,$name);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم إضافة العزلة','id'=>$conn->insert_id], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل الإضافة: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // UPDATE operations
        if ($action === 'update' && $target === 'region') {
            $id = intval($input['id'] ?? 0); $name = sanitize_name($input['name'] ?? '');
            if ($id<=0 || $name==='') { echo json_encode(['success'=>false,'message'=>'id و name مطلوبان'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('UPDATE regions SET name=? WHERE id=?');
            $stmt->bind_param('si',$name,$id);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم تحديث المحافظة'], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل التحديث: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'update' && $target === 'district') {
            $id = intval($input['id'] ?? 0); $name = sanitize_name($input['name'] ?? '');
            if ($id<=0 || $name==='') { echo json_encode(['success'=>false,'message'=>'id و name مطلوبان'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('UPDATE districts SET name=? WHERE id=?');
            $stmt->bind_param('si',$name,$id);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم تحديث المديرية'], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل التحديث: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }
        if ($action === 'update' && $target === 'sub_district') {
            $id = intval($input['id'] ?? 0); $name = sanitize_name($input['name'] ?? '');
            if ($id<=0 || $name==='') { echo json_encode(['success'=>false,'message'=>'id و name مطلوبان'], JSON_UNESCAPED_UNICODE); exit; }
            $stmt = $conn->prepare('UPDATE sub_districts SET name=? WHERE id=?');
            $stmt->bind_param('si',$name,$id);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم تحديث العزلة'], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل التحديث: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }

        // DELETE operations
        if ($action === 'delete') {
            $id = intval($input['id'] ?? 0);
            if ($id<=0) { echo json_encode(['success'=>false,'message'=>'id مطلوب'], JSON_UNESCAPED_UNICODE); exit; }
            if ($target === 'region') {
                $stmt = $conn->prepare('DELETE FROM regions WHERE id=?');
            } elseif ($target === 'district') {
                $stmt = $conn->prepare('DELETE FROM districts WHERE id=?');
            } elseif ($target === 'sub_district') {
                $stmt = $conn->prepare('DELETE FROM sub_districts WHERE id=?');
            } else {
                echo json_encode(['success'=>false,'message'=>'target غير صالح للحذف'], JSON_UNESCAPED_UNICODE); exit;
            }
            $stmt->bind_param('i',$id);
            if ($stmt->execute()) echo json_encode(['success'=>true,'message'=>'تم الحذف بنجاح'], JSON_UNESCAPED_UNICODE); else echo json_encode(['success'=>false,'message'=>'فشل الحذف: '.$stmt->error], JSON_UNESCAPED_UNICODE);
            exit;
        }

        echo json_encode(['success'=>false,'message'=>'إجراء غير معروف'], JSON_UNESCAPED_UNICODE); exit;
    }

    echo json_encode(['success'=>false,'message'=>'طريقة غير مدعومة'], JSON_UNESCAPED_UNICODE);
} catch (Exception $e) {
    echo json_encode(['success'=>false,'message'=>'خطأ في الخادم: '.$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
