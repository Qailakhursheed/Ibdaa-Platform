<?php
require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/anti_detection.php';

// إخفاء معلومات السيرفر
AntiDetection::hideServerHeaders();

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

// كشف البوتات
if (AntiDetection::detectBot()) {
    AntiDetection::logSuspiciousActivity('signup_bot_detected');
    AntiDetection::addRandomDelay(1000, 3000);
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>إنشاء حساب - منصة إبداع</title>
  <?php echo CSRF::getMetaTag(); ?>
  <script src="https://cdn.tailwindcss.com"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body {
      font-family: 'Cairo', sans-serif;
      background: url('photos/bg.png') center center/cover no-repeat fixed;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
    }
    .overlay {
      position: absolute;
      inset: 0;
      background: rgba(0, 0, 0, 0.55);
      backdrop-filter: blur(3px);
      z-index: 0;
    }
  </style>
</head>

<body class="relative text-white">
  <div class="overlay"></div>

  <div class="relative z-10 bg-white/10 p-10 rounded-2xl shadow-2xl w-[90%] max-w-lg backdrop-blur-md border border-white/20">
    <div class="text-center mb-6">
      <img src="photos/Sh.jpg" class="mx-auto w-16 h-16 rounded-full border-2 border-indigo-400 shadow-md mb-3">
      <h1 class="text-3xl font-bold text-white">إنشاء حساب جديد</h1>
      <p class="text-gray-200 text-sm mt-2">ابدأ رحلتك معنا نحو التميز التقني 🚀</p>
    </div>

    <?php if(isset($_GET['error'])): ?>
      <div class="bg-red-500/20 border border-red-500 text-white px-4 py-3 rounded-lg mb-4">
        <?php echo htmlspecialchars($_GET['error']); ?>
      </div>
    <?php endif; ?>

    <form class="space-y-5" action="register.php" method="POST" enctype="multipart/form-data">
      <?php echo CSRF::getTokenField(); ?>
      <?php echo AntiDetection::getProtectedFormFields(); ?>
      <div>
        <label class="block text-gray-200 mb-1">الاسم الكامل</label>
        <input type="text" name="full_name" required placeholder="أدخل اسمك الكامل" class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
      </div>

      <div>
        <label class="block text-gray-200 mb-1">البريد الإلكتروني</label>
        <input type="email" name="email" required placeholder="example@email.com" class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-200 mb-1">كلمة المرور</label>
          <input type="password" name="password" required placeholder="8 أحرف على الأقل" minlength="8" class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
          <small class="text-gray-300 text-xs block mt-1">يجب أن تحتوي على: حرف كبير، حرف صغير، رقم</small>
        </div>
        <div>
          <label class="block text-gray-200 mb-1">تأكيد كلمة المرور</label>
          <input type="password" name="confirm_password" required placeholder="••••••••" minlength="8" class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400">
        </div>
      </div>

      <div>
        <label class="block text-gray-200 mb-1">تاريخ الميلاد</label>
        <input type="date" name="birth_date" required class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-white focus:ring-2 focus:ring-indigo-400">
      </div>

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block text-gray-200 mb-1">المحافظة</label>
          <select id="governorate" name="governorate" required class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-black focus:ring-2 focus:ring-indigo-400">
            <option value="">اختر المحافظة</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-200 mb-1">المديرية</label>
          <select id="district" name="district" required class="w-full p-3 rounded-lg bg-white/20 border border-white/30 text-black focus:ring-2 focus:ring-indigo-400">
            <option value="">اختر المديرية</option>
          </select>
          <!-- يظهر فقط عند اختيار "أخرى" -->
          <input id="district_other" name="district_other" class="hidden w-full mt-2 p-3 rounded-lg bg-white/20 border border-white/30 text-white placeholder-gray-200 focus:ring-2 focus:ring-indigo-400" placeholder="اكتب اسم المديرية">
        </div>
      </div>

      <div>
        <label class="block text-gray-200 mb-1">الصورة الشخصية</label>
        <input type="file" name="photo" accept="image/*" required class="w-full text-white file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
      </div>

      <button type="submit" class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 py-3 rounded-lg shadow-lg hover:opacity-90 transition font-semibold">إنشاء الحساب</button>
    </form>

    <p class="text-center text-gray-300 mt-6">لديك حساب بالفعل؟  
      <a href="login.php" class="text-indigo-300 hover:text-indigo-200 font-semibold">تسجيل الدخول</a>
    </p>
  </div>

  <script src="/platform/js/yemen_locations.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function(){
      if (window.YemenLocations) YemenLocations.init('governorate','district','district_other');
    });
  </script>
  <!-- Chatbot widget styles & script -->
  <link rel="stylesheet" href="/platform/css/chatbot.css">
  <script src="/platform/js/chatbot.js"></script>
  <script src="js/watermark.js"></script>
</body>
</html>
