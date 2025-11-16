<?php
require_once __DIR__ . '/../database/db.php';
require_once __DIR__ . '/../Mailer/sendMail.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $action = $_POST['action'] ?? '';

    $status = "قيد المراجعة";
    $fees = $_POST['fees'] ?? 0;
    $note = $_POST['note'] ?? null;
    $course_title = $_POST['course_title'] ?? null;

    // تحديد الحالة بناءً على الإجراء
    if ($action === 'approve') {
        $status = 'مقبول';
    } elseif ($action === 'reject') {
        $status = 'مرفوض';
    } elseif ($action === 'paid') {
        $status = 'تم الدفع';
    }

    // تحديث قاعدة البيانات
    if ($action === 'approve' && $course_title) {
        // عند الموافقة: تحديث الحالة + الرسوم + الملاحظات + اسم الدورة
        $stmt = $conn->prepare("UPDATE course_requests SET status=?, fees=?, note=?, course=? WHERE id=?");
        $stmt->bind_param("sdssi", $status, $fees, $note, $course_title, $id);
    } else {
        // للإجراءات الأخرى: تحديث الحالة فقط
        $stmt = $conn->prepare("UPDATE course_requests SET status=? WHERE id=?");
        $stmt->bind_param("si", $status, $id);
    }

    if ($stmt->execute()) {
        // الحصول على بيانات الطلب لإرسال البريد
        $infoQuery = $conn->prepare("SELECT full_name, email, course, fees FROM course_requests WHERE id=?");
        $infoQuery->bind_param("i", $id);
        $infoQuery->execute();
        $info = $infoQuery->get_result()->fetch_assoc();
        
        if ($info) {
            // إرسال بريد إلكتروني للطالب
            if ($action === 'approve' || $action === 'paid') {
                $emailStatus = $status;
                if ($action === 'approve' && $info['fees'] > 0) {
                    $emailStatus .= " - الرسوم: " . number_format($info['fees'], 0) . " ريال يمني";
                }
                sendStatusMail($info['email'], $info['full_name'], $info['course'], $emailStatus);
            }
        }
        
        $stmt->close();
        $infoQuery->close();
        
        // الرجوع إلى صفحة اللوحة
        header('Location: Portal.php?success=1');
        exit;
    } else {
        // في حالة الخطأ
        header('Location: Portal.php?error=1');
        exit;
    }
}

// إذا لم يكن POST request
header('Location: Portal.php');
exit;
?>
