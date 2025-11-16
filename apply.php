<?php
// apply.php - receive application form and save to requests table
// usage: POST from application.php with file input named 'id_file'
require_once __DIR__ . '/platform/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: application.php');
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');
$course_name = trim($_POST['course'] ?? '');
$governorate = trim($_POST['governorate'] ?? '');
$district = trim($_POST['district'] ?? '');
$notes = trim($_POST['notes'] ?? '');

// basic validation
if (empty($full_name) || empty($phone) || empty($email) || empty($course_name)) {
    die('الرجاء ملء الحقول المطلوبة');
}

// handle file upload
$uploadDir = __DIR__ . '/uploads/ids';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

$id_file_path = '';
if (!empty($_FILES['id_file']['name'])) {
    $orig = $_FILES['id_file']['name'];
    $ext = pathinfo($orig, PATHINFO_EXTENSION);
    $safe = preg_replace('/[^a-zA-Z0-9-_\.]/', '_', pathinfo($orig, PATHINFO_BASENAME));
    $newName = time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
    $dest = $uploadDir . '/' . $newName;
    if (move_uploaded_file($_FILES['id_file']['tmp_name'], $dest)) {
        $id_file_path = 'uploads/ids/' . $newName;
    } else {
        die('فشل رفع ملف الهوية');
    }
} else {
    die('مطلوب ملف الهوية');
}

// insert into requests
$stmt = $conn->prepare("INSERT INTO requests (full_name, phone, email, course_name, governorate, district, id_file_path, notes) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
if (!$stmt) die('خطأ في التحضير: ' . $conn->error);
$stmt->bind_param('ssssssss', $full_name, $phone, $email, $course_name, $governorate, $district, $id_file_path, $notes);
if (!$stmt->execute()) {
    die('خطأ في إدخال الطلب: ' . $stmt->error);
}

// redirect to thank-you page
header('Location: application_thanks.html');
exit;
