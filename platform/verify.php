<?php
session_start();
require_once 'db.php';

// التحقق من وجود التوكن
if (!isset($_GET['token'])) {
    header("Location: login.php?error=" . urlencode("رابط التفعيل غير صحيح"));
    exit;
}

$token = $_GET['token'];

// البحث عن المستخدم بواسطة التوكن
$stmt = $conn->prepare("SELECT id, email, verified FROM users WHERE verification_token = ?");
$stmt->bind_param("s", $token);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $stmt->close();
    header("Location: login.php?error=" . urlencode("رابط التفعيل غير صحيح أو منتهي الصلاحية"));
    exit;
}

$user = $result->fetch_assoc();
$stmt->close();

// التحقق إذا كان الحساب مفعل مسبقاً
if ($user['verified'] == 1) {
    header("Location: login.php?success=" . urlencode("حسابك مفعل مسبقاً. يمكنك تسجيل الدخول الآن."));
    exit;
}

// تفعيل الحساب
$stmt = $conn->prepare("UPDATE users SET verified = 1, verification_token = NULL WHERE id = ?");
$stmt->bind_param("i", $user['id']);

if ($stmt->execute()) {
    $stmt->close();
    header("Location: login.php?success=" . urlencode("تم تفعيل حسابك بنجاح! يمكنك تسجيل الدخول الآن."));
    exit;
} else {
    $stmt->close();
    header("Location: login.php?error=" . urlencode("حدث خطأ أثناء التفعيل. حاول مرة أخرى."));
    exit;
}

$conn->close();
?>
