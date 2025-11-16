بوابة المدير — تعليمات الوصول والاختبار

ملخص سريع:
- صفحة الدخول للمدير/المشرف/المدرب/المتدرب موجودة في:
  - http://localhost/Ibdaa-Taiz/Manager/login.php

لوحات الأدوار (بعد تسجيل الدخول):
- المدير العام: http://localhost/Ibdaa-Taiz/Manager/manager-dashboard.php
- المشرف الفني: http://localhost/Ibdaa-Taiz/Manager/technical-dashboard.php
- المدربون: http://localhost/Ibdaa-Taiz/Manager/trainer-dashboard.php
- المتدربون: http://localhost/Ibdaa-Taiz/Manager/student-dashboard.php

إنشاء حسابات اختبار:
- يمكنك إنشاء حسابات عبر صفحة التسجيل العامة (platform/signup.php)
- أو إنشاء حسابات مباشرة عبر سكربت PHP صغير يولد hash لك ويُدخِل السَجِلّ في قاعدة البيانات.

مثال سريع لإنشاء مستخدم مدير (شغّل هذا كملف PHP مرة واحدة داخل XAMPP):

<?php
// save_as create_admin.php and open http://localhost/Ibdaa-Taiz/Manager/create_admin.php
require_once __DIR__ . '/../platform/db.php';
$name = 'Admin Test';
$email = 'admin@local.test';
$password = password_hash('Password123!', PASSWORD_BCRYPT);
$role = 'manager';
$stmt = $conn->prepare("INSERT INTO users (full_name, email, password_hash, role, verified) VALUES (?, ?, ?, ?, 1)");
$stmt->bind_param('ssss', $name, $email, $password, $role);
if ($stmt->execute()) echo 'Admin created: ' . $email; else echo 'Error: ' . $conn->error;
?>

ملاحظات مهمة:
- تأكد Apache مفعّل (XAMPP Control Panel) وادخل العناوين عبر http://localhost/... وليس فتح الملفات محليًا (file://).
- إذا كانت قاعدة البيانات لديك تستخدم أعمدة قديمة (password, photo) فالصفحات تدعم كلا الشكلين.
- أعد تشغيل Apache إذا قمت بتعديل ملفات PHP أو الإعدادات.

إذا تريد، أنشئ الآن حسابات اختبار دقيقة وسأتحقق من تسجيل الدخول وأعدّل الروابط لخدمات معينة (مثل إدارة الطلبات أو تقارير المدفوعات).