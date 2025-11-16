<?php
require_once __DIR__ . '/../includes/session_security.php';
require_once 'db.php';

// بدء جلسة آمنة والتحقق من تسجيل الدخول
SessionSecurity::startSecureSession();
SessionSecurity::requireLogin('login.php');

// جلب بيانات المستخدم
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// معالجة تسجيل الخروج
if (isset($_GET['logout'])) {
    SessionSecurity::logout();
    header("Location: login.php");
    exit;
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>لوحة التحكم - منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body {
      font-family: 'Cairo', sans-serif;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      min-height: 100vh;
    }
  </style>
</head>

<body class="bg-gray-50">
  <!-- Header -->
  <nav class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg">
    <div class="container mx-auto px-6 py-4 flex items-center justify-between">
      <div class="flex items-center gap-4">
        <img src="photos/Sh.jpg" class="w-10 h-10 rounded-full border-2 border-white">
        <h1 class="text-2xl font-bold">منصة إبداع تعز</h1>
      </div>
      <a href="?logout=1" class="bg-red-500 hover:bg-red-600 px-4 py-2 rounded-lg transition">
        تسجيل الخروج
      </a>
    </div>
  </nav>

  <!-- Main Content -->
  <div class="container mx-auto px-6 py-8">
    <!-- Welcome Card -->
    <div class="bg-white rounded-2xl shadow-xl p-8 mb-8">
      <div class="flex items-center gap-6">
        <img src="<?php echo htmlspecialchars($user['photo_path']); ?>" 
             class="w-24 h-24 rounded-full border-4 border-indigo-500 object-cover">
        <div>
          <h2 class="text-3xl font-bold text-gray-800">
            مرحباً، <?php echo htmlspecialchars($user['full_name']); ?> 👋
          </h2>
          <p class="text-gray-600 mt-2">
            📧 <?php echo htmlspecialchars($user['email']); ?>
          </p>
          <p class="text-gray-600">
            📍 <?php echo htmlspecialchars($user['governorate']); ?> - <?php echo htmlspecialchars($user['district']); ?>
          </p>
          <p class="text-gray-600">
            🎂 تاريخ الميلاد: <?php echo htmlspecialchars($user['birth_date']); ?>
          </p>
        </div>
      </div>
    </div>

    <!-- Stats Grid -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
      <div class="bg-gradient-to-br from-blue-500 to-blue-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-blue-100 text-sm">الدورات المسجلة</p>
            <p class="text-3xl font-bold mt-2">0</p>
          </div>
          <div class="text-5xl opacity-30">📚</div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-green-500 to-green-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-green-100 text-sm">الدورات المكتملة</p>
            <p class="text-3xl font-bold mt-2">0</p>
          </div>
          <div class="text-5xl opacity-30">✅</div>
        </div>
      </div>

      <div class="bg-gradient-to-br from-purple-500 to-purple-600 text-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between">
          <div>
            <p class="text-purple-100 text-sm">الشهادات الحاصل عليها</p>
            <p class="text-3xl font-bold mt-2">0</p>
          </div>
          <div class="text-5xl opacity-30">🏆</div>
        </div>
      </div>
    </div>

    <!-- Available Courses -->
    <div class="bg-white rounded-2xl shadow-xl p-8">
      <h3 class="text-2xl font-bold text-gray-800 mb-6">الدورات المتاحة</h3>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        <!-- Course Card Example -->
        <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition">
          <div class="bg-gradient-to-r from-indigo-500 to-purple-500 h-40 flex items-center justify-center">
            <span class="text-6xl">💻</span>
          </div>
          <div class="p-5">
            <h4 class="font-bold text-lg text-gray-800 mb-2">تطوير المواقع الإلكترونية</h4>
            <p class="text-gray-600 text-sm mb-4">تعلم HTML, CSS, JavaScript والمزيد</p>
            <button class="w-full bg-indigo-600 hover:bg-indigo-700 text-white py-2 rounded-lg transition">
              التسجيل في الدورة
            </button>
          </div>
        </div>

        <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition">
          <div class="bg-gradient-to-r from-green-500 to-teal-500 h-40 flex items-center justify-center">
            <span class="text-6xl">🎨</span>
          </div>
          <div class="p-5">
            <h4 class="font-bold text-lg text-gray-800 mb-2">التصميم الجرافيكي</h4>
            <p class="text-gray-600 text-sm mb-4">تعلم Photoshop وIllustrator</p>
            <button class="w-full bg-green-600 hover:bg-green-700 text-white py-2 rounded-lg transition">
              التسجيل في الدورة
            </button>
          </div>
        </div>

        <div class="border border-gray-200 rounded-xl overflow-hidden hover:shadow-lg transition">
          <div class="bg-gradient-to-r from-orange-500 to-red-500 h-40 flex items-center justify-center">
            <span class="text-6xl">📱</span>
          </div>
          <div class="p-5">
            <h4 class="font-bold text-lg text-gray-800 mb-2">تطوير تطبيقات الجوال</h4>
            <p class="text-gray-600 text-sm mb-4">تعلم Flutter وReact Native</p>
            <button class="w-full bg-orange-600 hover:bg-orange-700 text-white py-2 rounded-lg transition">
              التسجيل في الدورة
            </button>
          </div>
        </div>
      </div>

      <div class="mt-8 text-center">
        <p class="text-gray-500">المزيد من الدورات قريباً... 🚀</p>
      </div>
    </div>
  </div>

  <!-- Footer -->
  <footer class="bg-gray-800 text-white text-center py-6 mt-12">
    <p>© 2025 منصة إبداع تعز - جميع الحقوق محفوظة</p>
    <p class="text-sm text-gray-400 mt-2">نحو مستقبل تقني مشرق 💡</p>
  </footer>
</body>
</html>
