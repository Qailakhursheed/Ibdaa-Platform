<?php
session_start();
if (empty($_SESSION['user_id']) || ($_SESSION['user_role'] ?? '') === '') {
  header('Location: login.php');
  exit;
}

require_once __DIR__ . '/../platform/db.php';

$user_id = $_SESSION['user_id'];
$active_courses_count = 0;
$announcements = [];
// check enrollments table and count active enrollments
if ($conn->query("SHOW TABLES LIKE 'enrollments'")->num_rows > 0) {
  $stmt = $conn->prepare("SELECT COUNT(*) AS c FROM enrollments WHERE user_id = ? AND status = 'active'");
  $stmt->bind_param('i', $user_id);
  $stmt->execute();
  $res = $stmt->get_result();
  if ($res && ($row = $res->fetch_assoc())) {
    $active_courses_count = intval($row['c']);
  } else {
    $active_courses_count = 0;
  }
}

// fetch announcements if table exists
if ($conn->query("SHOW TABLES LIKE 'announcements'")->num_rows > 0) {
  $res = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 50");
  if ($res) while ($r = $res->fetch_assoc()) $announcements[] = $r;
}
?>
<!doctype html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>لوحة المتدرب</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="p-8 bg-gray-50">
  <div class="max-w-3xl mx-auto">
    <div class="flex items-center justify-between mb-6">
      <h1 class="text-2xl font-bold">لوحة المتدرب</h1>
      <div>
        <span class="ml-4">مرحبا، <?php echo htmlspecialchars($_SESSION['user_name']); ?></span>
        <a href="logout.php" class="ml-4 text-sm text-red-600">تسجيل خروج</a>
      </div>
    </div>

    <div class="space-y-4">
      <?php if ($active_courses_count > 0): ?>
        <div class="bg-white p-6 rounded shadow">
          <h2 class="text-lg font-bold">دوراتي المسجل بها</h2>
          <p class="text-sm text-gray-600">لديك <?php echo $active_courses_count; ?> دورة/دورات نشطة.</p>
          <a href="../platform/courses.php" class="text-sky-600 underline">استعراض الدورات</a>
        </div>
      <?php else: ?>
        <div class="bg-white p-6 rounded shadow">
          <h2 class="text-lg font-bold">أهلاً بك في منصة إبداع</h2>
          <p class="text-sm text-gray-600">أنت غير مسجل في أي دورة حالياً. تصفح دوراتنا المتاحة وتابع آخر إعلاناتنا.</p>
          <a href="../platform/courses.php" class="text-sky-600 underline">تصفح جميع الدورات</a>

          <h3 class="mt-4 font-semibold">آخر الإعلانات</h3>
          <?php if (!empty($announcements)): ?>
            <?php foreach ($announcements as $ann): ?>
              <div class="mt-3 p-3 border rounded bg-gray-50">
                <h4 class="font-bold"><?php echo htmlspecialchars($ann['title']); ?></h4>
                <p class="text-sm text-gray-700"><?php echo nl2br(htmlspecialchars($ann['content'])); ?></p>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <p class="text-sm text-gray-500 mt-2">لا توجد إعلانات حالياً.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>
