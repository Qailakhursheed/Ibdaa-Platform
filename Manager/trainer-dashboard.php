<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') !== 'trainer') {
    header('Location: login.php');
    exit;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة المدرب</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-50">
  <div class="max-w-4xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">لوحة المدرب</h1>
      <div>
        <span class="ml-4">مرحبا، <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="ml-4 text-sm text-red-600">تسجيل خروج</a>
      </div>
    </div>

    <div class="space-y-4">
      <a href="../platform/courses.php" class="block p-6 bg-white rounded shadow">قائمة دوراتي</a>
      <a href="requests.php" class="block p-6 bg-white rounded shadow">قائمة طلبات المتدربين</a>
    </div>
  </div>
</body>
</html>
