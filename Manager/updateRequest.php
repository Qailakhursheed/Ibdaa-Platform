<?php
$dataFile = __DIR__ . '/../database/requests.json';
$requests = file_exists($dataFile) ? json_decode(file_get_contents($dataFile), true) : [];
if (!is_array($requests)) $requests = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? '';
    $action = $_POST['action'] ?? '';

    foreach ($requests as &$req) {
        if ($req['id'] === $id) {
            if ($action === 'approve') $req['status'] = 'مقبول';
            if ($action === 'reject') $req['status'] = 'مرفوض';
            if ($action === 'paid') $req['status'] = 'تم الدفع';

            // إرسال إشعار للمتقدم
            if ($action === 'approve' || $action === 'paid') {
                // محاولة إرسال البريد
                try {
                    require_once __DIR__ . '/../Mailer/sendMail.php';
                    sendStatusMail($req['email'], $req['full_name'], $req['course'], $req['status']);
                } catch (Exception $e) {
                    // تجاهل الأخطاء إذا لم يعمل البريد
                    error_log('Mail Error: ' . $e->getMessage());
                }
            }
            break;
        }
    }

    file_put_contents($dataFile, json_encode($requests, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    header('Location: requests.php');
    exit;
}

// إذا لم يكن طلب POST، ارجع للصفحة الرئيسية
header('Location: requests.php');
exit;
?>
