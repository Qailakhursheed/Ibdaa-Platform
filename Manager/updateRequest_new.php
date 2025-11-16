<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../Mailer/sendMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $action = $_POST['action'] ?? '';
    $status = 'قيد المراجعة';

    if ($action === 'approve') $status = 'مقبول';
    if ($action === 'reject') $status = 'مرفوض';
    if ($action === 'paid') $status = 'تم الدفع';

    $stmt = $conn->prepare("UPDATE course_requests SET status=? WHERE id=?");
    $stmt->bind_param("si", $status, $id);
    $stmt->execute();

    // إرسال إشعار بالبريد للحالات المقبولة والمدفوعة
    if ($action === 'approve' || $action === 'paid') {
        $info = $conn->query("SELECT full_name, email, course FROM course_requests WHERE id=$id")->fetch_assoc();
        if ($info) {
            sendStatusMail($info['email'], $info['full_name'], $info['course'], $status);
        }
    }

    header('Location: requests_new.php');
    exit;
}
?>
