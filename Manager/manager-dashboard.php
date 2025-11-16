<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'manager') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة المدير العام</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-50">
  <div class="max-w-6xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">لوحة المدير العام</h1>
      <div>
        <span class="ml-4">مرحبا، <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="ml-4 text-sm text-red-600">تسجيل خروج</a>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <a href="requests.php" class="p-6 bg-white rounded shadow">إدارة الطلبات</a>
      <a href="../platform/courses.php" class="p-6 bg-white rounded shadow">الدورات</a>
      <a href="users.php" class="p-6 bg-white rounded shadow">إدارة المستخدمين</a>
    </div>
  </div>
</body>
</html>
