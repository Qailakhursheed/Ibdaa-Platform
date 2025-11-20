$announcements = [];
try {
    $stmt = $conn->prepare("
        SELECT id, title, content, media_url, created_at 
        FROM notifications 
        WHERE type = 'info'
        ORDER BY created_at DESC 
        LIMIT 3
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $announcements[] = $row;
    }
    $stmt->close();
} catch (Exception $e) {
    // في حالة حدوث خطأ، سيبقى المصفوفة فارغة
    error_log("Error fetching announcements: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>منصة إبداع للتدريب والتأهيل</title>

  <!-- Tailwind CSS -->
  <script src="https://cdn.tailwindcss.com"></script>
  <!-- Lucide Icons -->
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; background-color: #f9fafb; }
    .card-hover { transition: all 0.3s ease; }
    .card-hover:hover { transform: translateY(-6px); box-shadow: 0 10px 20px rgba(0,0,0,0.08); }
    .hero-video { position: absolute; inset: 0; width: 100%; height: 100%; object-fit: cover; z-index: -1; }
    .hero-overlay { position: absolute; inset: 0; background: linear-gradient(to bottom, rgba(0,0,0,0.5), rgba(0,0,0,0.8)); z-index: 0; }
    .btn-glow { position: relative; overflow: hidden; }
    .btn-glow::after { content: ""; position: absolute; top: 0; left: -100%; width: 100%; height: 100%; background: linear-gradient(120deg, transparent, rgba(255,255,255,0.4), transparent); transition: all 0.75s; }
    .btn-glow:hover::after { left: 100%; }
    .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
    .line-clamp-4 { display: -webkit-box; -webkit-line-clamp: 4; -webkit-box-orient: vertical; overflow: hidden; }
  </style>
  <link rel="stylesheet" href="css/ask-abdullah.css">
</head>

<body class="text-gray-900">

  <!-- الهيدر -->
  <header class="bg-white shadow-md sticky top-0 z-50">
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="#home" class="flex items-center space-x-3 rtl:space-x-reverse font-bold text-2xl text-indigo-700 hover:text-indigo-900 transition">
        <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-10 w-10 rounded-full">
        <span>منصة إبداع</span>
      </a>

      <!-- روابط -->
      <div class="hidden md:flex items-center space-x-6 rtl:space-x-reverse">
        <a href="#home" class="hover:text-indigo-600 transition">الرئيسية</a>
        <a href="#about" class="hover:text-indigo-600 transition">عن المنصة</a>
        <a href="#courses" class="hover:text-indigo-600 transition">الدورات</a>
        <a href="staff.php" class="hover:text-indigo-600 transition">الكادر</a>
        <?php if (!empty($announcements)): ?>
        <a href="#announcements" class="hover:text-indigo-600 transition">الإعلانات</a>
        <?php endif; ?>
        <a href="#gallery" class="hover:text-indigo-600 transition">المعرض</a>
      </div>

        <!-- أزرار اللغة والدخول -->
      <div class="hidden md:flex items-center space-x-4 rtl:space-x-reverse">
        <!-- أزرار اللغة -->
        <div class="flex items-center space-x-2 rtl:space-x-reverse border-s ps-3">
          <button id="lang-ar" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">AR</button>
          <button id="lang-en" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">EN</button>
          <button id="lang-ch" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">CH</button>
        </div>
        <!-- أزرار الدخول (روابط لصفحات منفصلة) -->
        <a href="login.php" class="text-indigo-600 border border-indigo-600 px-5 py-2 rounded-lg hover:bg-indigo-50 transition">تسجيل الدخول</a>
        <a href="signup.php" class="bg-indigo-600 text-white px-5 py-2 rounded-lg shadow-lg hover:bg-indigo-700 transition btn-glow">إنشاء حساب</a>
      </div>

      <!-- زر الموبايل -->
      <button id="mobileMenuBtn" class="md:hidden text-gray-700" type="button" aria-label="فتح القائمة" title="القائمة" aria-controls="mobileMenu" aria-expanded="false"><i data-lucide="menu" aria-hidden="true"></i></button>
    </nav>

    <!-- قائمة الموبايل -->
      <div id="mobileMenu" class="hidden bg-white border-t border-gray-200 md:hidden">
      <a href="#home" class="block px-6 py-3 hover:bg-indigo-50">الرئيسية</a>
      <a href="#about" class="block px-6 py-3 hover:bg-indigo-50">عن المنصة</a>
      <a href="#courses" class="block px-6 py-3 hover:bg-indigo-50">الدورات</a>
      <a href="staff.php" class="block px-6 py-3 hover:bg-indigo-50">الكادر</a>
      <?php if (!empty($announcements)): ?>
      <a href="#announcements" class="block px-6 py-3 hover:bg-indigo-50">الإعلانات</a>
      <?php endif; ?>
      <a href="#gallery" class="block px-6 py-3 hover:bg-indigo-50">المعرض</a>
      <div class="border-t my-2"></div>
      <div class="px-6 mt-3 space-y-3">
        <a href="login.php" class="w-full block text-center text-indigo-600 border border-indigo-600 py-3 rounded-lg hover:bg-indigo-50">تسجيل الدخول</a>
        <a href="signup.php" class="w-full block text-center bg-indigo-600 text-white py-3 rounded-lg shadow-lg hover:bg-indigo-700 btn-glow">إنشاء حساب</a>
      </div>
    </div>
  </header>

  <!-- الهيرو -->
  <section id="home" class="relative h-screen flex flex-col justify-center items-center text-center text-white overflow-hidden">
    <video autoplay muted loop playsinline class="hero-video">
      <source src="Videos/front.mp4" type="video/mp4">
    </video>
    <div class="hero-overlay"></div>

    <div class="relative z-10 fade-in">
      <h1 class="text-4xl md:text-6xl font-bold mb-4">منصة إبداع للتدريب والتأهيل</h1>
      <p class="text-lg md:text-2xl text-gray-200 mb-8 max-w-3xl mx-auto">بوابتك نحو الاحتراف الرقمي وتنمية المهارات التقنية، من الأساسيات إلى المستويات المتقدمة.</p>
      <a href="#courses" class="btn-glow bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-10 py-4 rounded-lg shadow-lg hover:scale-105 transition-transform font-semibold">اكتشف دوراتنا الآن</a>
    </div>
  </section>

  <!-- عن المنصة -->
  <section id="about" class="py-20 bg-white text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold mb-4 text-gray-800">لماذا تختار منصة إبداع؟</h2>
      <p class="text-gray-600 max-w-3xl mx-auto mb-10">نهدف إلى تمكين الجميع من التعلم التقني الحديث عبر برامج معتمدة وتدريب احترافي في بيئة مرنة وممتعة.</p>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
        <div class="card-hover bg-gray-50 p-8 rounded-xl border">
          <i data-lucide="award" class="text-indigo-600 w-10 h-10 mx-auto mb-3"></i>
          <h3 class="text-xl font-bold mb-2">برامج معتمدة</h3>
          <p class="text-gray-600">نقدم برامج ICDL ودبلومات معتمدة بتركيز عملي.</p>
        </div>
        <div class="card-hover bg-gray-50 p-8 rounded-xl border">
          <i data-lucide="bar-chart-3" class="text-indigo-600 w-10 h-10 mx-auto mb-3"></i>
          <h3 class="text-xl font-bold mb-2">مجالات متقدمة</h3>
          <p class="text-gray-600">دورات متخصصة في تحليل البيانات وتصميم الأنظمة.</p>
        </div>
        <div class="card-hover bg-gray-50 p-8 rounded-xl border">
          <i data-lucide="globe" class="text-indigo-600 w-10 h-10 mx-auto mb-3"></i>
          <h3 class="text-xl font-bold mb-2">مهارات شاملة</h3>
          <p class="text-gray-600">دورات في اللغة الإنجليزية ومهارات سوق العمل.</p>
        </div>
      </div>
    </div>
  </section>

  <!-- الدورات -->
  <section id="courses" class="py-20 bg-gray-100 text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-gray-800 mb-4">دوراتنا التدريبية</h2>
      <p class="text-gray-600 max-w-2xl mx-auto mb-8">مجموعة متنوعة من الدورات التقنية والرقمية بإشراف مدربين متخصصين.</p>
      <a href="courses.php" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition btn-glow">استعراض كل الدورات</a>
    </div>
  </section>

  <!-- الكادر -->
  <section id="staff" class="py-20 bg-white text-center">
    <div class="container mx-auto px-6">
      <h2 class="text-3xl font-bold text-gray-800 mb-4">الكادر المؤسس والإداري</h2>
      <p class="text-gray-600 max-w-2xl mx-auto mb-8">يقود المنصة نخبة من المدربين والخبراء أصحاب الخبرة الطويلة في التعليم التقني.</p>
      <a href="staff.php" class="inline-block border border-indigo-600 text-indigo-600 px-8 py-3 rounded-lg hover:bg-indigo-50 transition btn-glow">تعرف على الكادر</a>
    </div>
  </section>

  <!-- الإعلانات -->
  <?php if (!empty($announcements)): ?>
  <section id="announcements" class="py-20 bg-gradient-to-br from-indigo-50 to-blue-50">
    <div class="container mx-auto px-6">
      <div class="text-center mb-12">
        <div class="inline-flex items-center justify-center bg-white rounded-full px-6 py-2 shadow-md mb-4">
          <i data-lucide="megaphone" class="w-5 h-5 text-indigo-600 ml-2"></i>
          <span class="text-indigo-600 font-bold">الإعلانات والأخبار</span>
        </div>
        <h2 class="text-3xl font-bold text-gray-800 mb-3">آخر الأخبار والتحديثات</h2>
        <p class="text-gray-600 max-w-2xl mx-auto">تابع أحدث الإعلانات والفعاليات والتحديثات من منصة إبداع</p>
      </div>

      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8 max-w-6xl mx-auto">
        <?php foreach ($announcements as $announcement): ?>
        <div class="bg-white rounded-xl shadow-lg overflow-hidden card-hover border border-indigo-100 flex flex-col">
          <?php if (!empty($announcement['media_url'])): ?>
            <div class="w-full h-48 bg-slate-200">
              <?php
                $media_ext = strtolower(pathinfo($announcement['media_url'], PATHINFO_EXTENSION));
                $video_exts = ['mp4', 'webm', 'ogg'];
              ?>
              <?php if (in_array($media_ext, $video_exts)): ?>
                <video src="../<?= htmlspecialchars($announcement['media_url']) ?>" class="w-full h-full object-cover" controls></video>
              <?php else: ?>
                <img src="../<?= htmlspecialchars($announcement['title']) ?>" alt="<?= htmlspecialchars($announcement['title']) ?>" class="w-full h-full object-cover">
              <?php endif; ?>
            </div>
          <?php endif; ?>
          <div class="p-6 flex-grow flex flex-col">
            <h3 class="text-xl font-bold text-gray-800 mb-2 line-clamp-2"><?= htmlspecialchars($announcement['title']) ?></h3>
            <p class="text-gray-700 leading-relaxed mb-4 line-clamp-4 flex-grow"><?= nl2br(htmlspecialchars($announcement['content'])) ?></p>
            <div class="flex items-center justify-between text-sm text-gray-500 pt-4 border-t mt-auto">
              <div class="flex items-center">
                <i data-lucide="calendar" class="w-4 h-4 ml-1"></i>
                <span><?= date('Y/m/d', strtotime($announcement['created_at'])) ?></span>
              </div>
              <div class="flex items-center">
                <i data-lucide="clock" class="w-4 h-4 ml-1"></i>
                <span><?= date('h:i A', strtotime($announcement['created_at'])) ?></span>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>
    </div>
  </section>
  <?php endif; ?>

  <!-- المعرض -->
  <section id="gallery" class="py-20 bg-gray-100">
    <div class="container mx-auto px-6 text-center">
      <h2 class="text-3xl font-bold mb-6 text-gray-800">معرض الأنشطة والفعاليات</h2>
      <div class="scroll-container flex space-x-4 rtl:space-x-reverse overflow-x-auto pb-4 px-2">
        <img src="photos/1t.jpg" alt="معرض 1" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-90 h-56 object-cover">
        <img src="photos/1v.jpg" alt="معرض 2" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-80 h-56 object-cover">
        <img src="photos/2t.jpg" alt="معرض 3" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-95 h-56 object-cover">
        <img src="photos/2v.jpg" alt="معرض 4" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-80 h-56 object-cover">
        <img src="photos/3t.jpg" alt="معرض 5" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-100 h-56 object-cover">
        <img src="photos/3v.jpg" alt="معرض 6" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-80 h-56 object-cover">
        <img src="photos/4v.jpg" alt="معرض 7" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-80 h-56 object-cover">
        <img src="photos/5v.jpg" alt="معرض 8" class="scroll-item rounded-lg shadow-lg flex-shrink-0 w-80 h-56 object-cover">
      </div>
    </div>
  </section>

  <!-- الفوتر -->
  <footer class="bg-gray-900 text-gray-400 py-10">
    <div class="container mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10 text-center md:text-start">
      <div>
        <div class="flex items-center justify-center md:justify-start space-x-3 rtl:space-x-reverse mb-3">
          <img src="photos/Sh.jpg" class="h-10 w-10 rounded-full" alt="شعار منصة إبداع">
          <h3 class="text-xl font-bold text-white">منصة إبداع</h3>
        </div>
        <p>تعز، اليمن</p>
        <p class="text-sm mt-2">المصمم: RoboStack-Yemen</p>
      </div>

      <div>
        <h4 class="text-lg font-semibold text-white mb-4">روابط سريعة</h4>
        <ul class="space-y-2">
          <li><a href="#about" class="hover:text-white">عن المنصة</a></li>
          <li><a href="#courses" class="hover:text-white">الدورات</a></li>
          <li><a href="staff.php" class="hover:text-white">الكادر</a></li>
          <li><a href="#gallery" class="hover:text-white">المعرض</a></li>
        </ul>
      </div>

      <div>
        <h4 class="text-lg font-semibold text-white mb-4">تواصل معنا</h4>
        <p><i data-lucide="phone-call" class="inline w-5 h-5 text-indigo-400"></i> 00967 734 847 037</p>
        <p><i data-lucide="message-circle" class="inline w-5 h-5 text-green-400"></i> واتساب 1: 00967 713 567 677</p>
        <p><i data-lucide="message-circle" class="inline w-5 h-5 text-green-400"></i> واتساب 2: 00967 717 781 053</p>
      </div>
    </div>
    <div class="text-center text-sm border-t border-gray-700 mt-10 pt-5">&copy; 2025 منصة إبداع للتدريب والتأهيل — جميع الحقوق محفوظة.</div>
  </footer>

  <!-- "Ask Abdullah" AI Chat Widget -->
  <div id="ai-chat-button" role="button" aria-expanded="false" aria-label="افتح المحادثة مع عبدالله">
      <div class="icon-open">
          <i data-lucide="message-circle" class="w-8 h-8 text-white"></i>
      </div>
      <div class="icon-close">
          <i data-lucide="x" class="w-8 h-8 text-white"></i>
      </div>
  </div>

  <div id="ai-chat-widget" class="hidden" role="dialog" aria-modal="true" aria-labelledby="chat-header-title">
      <div class="chat-header">
          <h3 id="chat-header-title">اسأل عبدالله</h3>
          <button id="chat-close-btn" aria-label="إغلاق المحادثة">
              <i data-lucide="x" class="w-5 h-5"></i>
          </button>
      </div>
      <div id="chat-body" class="chat-body">
          <!-- Messages will be dynamically inserted here -->
      </div>
      <div class="chat-footer">
          <form id="chat-input-form">
              <input type="text" id="chat-input" placeholder="اكتب رسالتك هنا..." autocomplete="off" required>
              <button type="submit" id="chat-send-btn" disabled aria-label="إرسال الرسالة">
                  <i data-lucide="send" class="w-5 h-5"></i>
              </button>
          </form>
      </div>
  </div>
  <!-- End of AI Chat Widget -->

  <script>
    document.addEventListener("DOMContentLoaded", () => {
      lucide.createIcons();
      const mobileBtn = document.getElementById("mobileMenuBtn");
      const mobileMenu = document.getElementById("mobileMenu");
      if (mobileBtn && mobileMenu) {
        mobileBtn.addEventListener("click", () => {
          const isHidden = mobileMenu.classList.toggle("hidden");
          mobileBtn.setAttribute("aria-expanded", (!isHidden).toString());
        });
      }

      // اللغة النشطة
      const buttons = document.querySelectorAll('.lang-btn');
      buttons.forEach(btn => {
        btn.addEventListener('click', () => {
          buttons.forEach(b => b.classList.remove('active'));
          btn.classList.add('active');
        });
      }
    });
  </script>
  <script src="js/ask-abdullah.js"></script>
</body>
</html>

