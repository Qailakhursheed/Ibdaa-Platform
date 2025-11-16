<?php
// apply.php
mb_internal_encoding("UTF-8");
date_default_timezone_set('Asia/Aden');

// إعدادات
$uploadDir  = __DIR__ . "/uploads/ids/";
$dataFile   = __DIR__ . "/../database/requests.json";
if (!is_dir($uploadDir)) { @mkdir($uploadDir, 0777, true); }
if (!is_dir(dirname($dataFile))) { @mkdir(dirname($dataFile), 0777, true); }

function safe($k){ return isset($_POST[$k]) ? trim($_POST[$k]) : ''; }

$course      = safe('course');
$full_name   = safe('full_name');
$phone       = safe('phone');
$email       = safe('email');
$governorate = safe('governorate');
$district    = safe('district');
$districtAlt = safe('district_other');
$notes       = safe('notes');
$commit      = isset($_POST['commit']) && $_POST['commit'] === 'yes';

if ($district === 'أخرى' && !empty($districtAlt)) {
  $district = $districtAlt;
}

$errors = [];
if (!$course)        $errors[] = "الرجاء اختيار الدورة.";
if (!$full_name)     $errors[] = "الرجاء إدخال الاسم الكامل.";
if (!$phone)         $errors[] = "الرجاء إدخال رقم الهاتف.";
if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "البريد الإلكتروني غير صالح.";
if (!$governorate)   $errors[] = "الرجاء اختيار المحافظة.";
if (!$district)      $errors[] = "الرجاء اختيار/إدخال المديرية.";
if (!$commit)        $errors[] = "يجب الموافقة على التعهد.";

if (!isset($_FILES['id_file']) || $_FILES['id_file']['error'] !== UPLOAD_ERR_OK) {
  $errors[] = "الرجاء إرفاق الهوية.";
}

// تحقق من الملف
$allowedExt = ['jpg','jpeg','png','pdf'];
$maxSize = 5 * 1024 * 1024; // 5MB
$uploadedPath = '';
$uploadedFileName = '';
if (empty($errors)) {
  $fname = $_FILES['id_file']['name'];
  $tmp   = $_FILES['id_file']['tmp_name'];
  $size  = $_FILES['id_file']['size'];
  $ext   = strtolower(pathinfo($fname, PATHINFO_EXTENSION));
  if (!in_array($ext, $allowedExt)) $errors[] = "صيغة الملف غير مسموحة.";
  if ($size > $maxSize)             $errors[] = "حجم الملف يتجاوز 5MB.";
  if (empty($errors)) {
    $newName = 'ID_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $uploadedPath = $uploadDir . $newName;
    $uploadedFileName = $newName;
    if (!move_uploaded_file($tmp, $uploadedPath)) {
      $errors[] = "تعذّر حفظ الملف.";
    }
  }
}

if (!empty($errors)) {
  echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'><title>خطأ</title>
  <script src='https://cdn.tailwindcss.com'></script></head><body class='bg-gray-50'>
  <div class='container mx-auto px-6 py-10 max-w-2xl'>
  <div class='bg-red-50 border border-red-200 text-red-700 p-6 rounded-xl shadow'>
  <h1 class='text-2xl font-bold mb-3'>يرجى تصحيح الأخطاء التالية:</h1><ul class='list-disc ms-6'>";
  foreach($errors as $e){ echo "<li>$e</li>"; }
  echo "</ul><a href='javascript:history.back()' class='inline-block mt-4 text-white bg-red-600 px-4 py-2 rounded'>العودة</a>
  </div></div></body></html>";
  exit;
}

// الاتصال بقاعدة البيانات والحفظ
require_once __DIR__ . '/../database/db.php';

$status = 'قيد المراجعة';
$fees = 0;

$stmt = $conn->prepare("INSERT INTO course_requests (full_name, email, phone, course, governorate, district, id_card, status, fees, note) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmt->bind_param("ssssssssds", $full_name, $email, $phone, $course, $governorate, $district, $uploadedFileName, $status, $fees, $notes);

if (!$stmt->execute()) {
    die("حدث خطأ أثناء حفظ الطلب: " . $conn->error);
}

$requestId = $stmt->insert_id;
$stmt->close();

// صفحة تأكيد
echo "<!DOCTYPE html><html lang='ar' dir='rtl'><head><meta charset='UTF-8'>
<title>تم الإرسال بنجاح</title>
<script src='https://cdn.tailwindcss.com'></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
  body { font-family: 'Cairo', sans-serif; }
</style>
</head><body class='bg-gray-50'>
<div class='container mx-auto px-6 py-10 max-w-2xl'>";

echo "<div class='bg-green-50 border border-green-200 text-green-700 p-6 rounded-xl shadow'>
  <h1 class='text-2xl font-bold mb-3'>تم إرسال طلبك بنجاح ✅</h1>
  <p class='mb-4'>سيتم مراجعة طلبك من قبل الإدارة وسيتم التواصل معك قريبًا عبر البريد أو الهاتف.</p>
  <div class='bg-white p-4 rounded border'>
    <p><strong>رقم الطلب:</strong> #$requestId</p>
    <p><strong>الدورة:</strong> $course</p>
    <p><strong>الاسم:</strong> $full_name</p>
    <p><strong>الهاتف:</strong> $phone</p>
    <p><strong>البريد:</strong> $email</p>
    <p><strong>المحافظة/المديرية:</strong> $governorate - $district</p>
  </div>
  <a href='courses.html' class='inline-block mt-4 bg-indigo-600 text-white px-4 py-2 rounded hover:bg-indigo-700'>العودة لصفحة الدورات</a>
  </div>";

echo "</div></body></html>";
?>
