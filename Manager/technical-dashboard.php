<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'technical') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة المشرف الفني</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-50">
  <div class="max-w-5xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">لوحة المشرف الفني</h1>
      <div>
        <span class="ml-4">مرحبا، <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="ml-4 text-sm text-red-600">تسجيل خروج</a>
      </div>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <a href="requests.php" class="p-6 bg-white rounded shadow">مراجعة الطلبات</a>
      <a href="../platform/student-dashboard.php" class="p-6 bg-white rounded shadow">إدارة المتدربين</a>
    </div>
  </div>
</body>
</html>
