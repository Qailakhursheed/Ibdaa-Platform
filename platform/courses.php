<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>الدورات التدريبية - منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; background-color: #f9fafb; }
    .course-card { transition: all 0.3s ease; cursor: pointer; }
    .course-card:hover { transform: translateY(-6px); box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
    /* hero/background tweaks: lighter overlay so background appears clearer */
    .overlay { background: rgba(0,0,0,0.25); -webkit-backdrop-filter: blur(3px); backdrop-filter: blur(3px); }
    .hero-bg { filter: contrast(1.06) saturate(1.06) brightness(1.04); }
  </style>
</head>

<body class="text-gray-900">
  <?php include '_header.php'; ?>

  <!-- الهيرو -->
  <section class="relative h-[400px] flex items-center justify-center text-center text-white overflow-hidden">
    <!-- background (slightly enhanced for clarity) -->
  <img src="photos/bg.png" class="absolute inset-0 w-full h-full object-cover hero-bg" alt="background" />
    <!-- lighter overlay so background is clearer but text/logo remain readable -->
  <div class="overlay absolute inset-0"></div>

    <!-- centered logo inside the hero -->
    <div class="relative z-10 flex items-center justify-center">
      <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-28 w-28 rounded-full border-4 border-white shadow-lg" />
    </div>
  </section>
    <!-- المحتوى الرئيسي -->
    <main class="py-20 bg-white">
      <div class="container mx-auto px-6">
        <!-- مقدمة -->
        <div class="text-center mb-16">
          <p class="text-lg text-gray-700 max-w-3xl mx-auto leading-relaxed">
            تُعد <strong>منصة إبداع</strong> مركزًا متخصصًا في برامج الرخصة الدولية لقيادة الحاسوب (ICDL) ودبلوم الحاسوب المتكامل، 
            إلى جانب برامج أخرى في مجالات:
          </p>
        </div>

        <!-- أقسام الدورات (ديناميكية من DB مع fallback ثابت) -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-10">
          <?php
          // تعطيل عرض الأخطاء للمستخدم النهائي
          error_reporting(E_ALL & ~E_WARNING & ~E_NOTICE);
          
          require_once __DIR__ . '/db.php';

          // inline SVG placeholder (used as background-image inline)
          $svgPlaceholder = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800"><rect width="100%" height="100%" fill="%23e5e7eb"/><circle cx="600" cy="400" r="60" fill="%239ca3af"/></svg>';

          // try to read courses from `courses` table if it exists
          $courses = [];
          $haveCoursesTable = false;
          
          try {
            $check = $conn->query("SHOW TABLES LIKE 'courses'");
            if ($check && $check->num_rows > 0) {
              $haveCoursesTable = true;
              $res = $conn->query("SELECT course_id, title, short_desc, image_url FROM courses WHERE status = 'active' ORDER BY course_id ASC");
              if ($res && $res->num_rows > 0) {
                while ($row = $res->fetch_assoc()) {
                  $courses[] = [
                    'title' => $row['title'],
                    'id' => $row['course_id'],
                    'desc' => $row['short_desc'] ?: '',
                    'image' => $row['image_url'] ?: ''
                  ];
                }
              }
            }
          } catch (Exception $e) {
            // في حالة وجود خطأ، سنستخدم القائمة الثابتة
          }

          // fallback static list (from the previous HTML page)
          if (empty($courses)) {
            $courses = [
              [
                'title' => 'الرخصة الدولية ICDL',
                'id' => 'ICDL',
                'desc' => 'دورة احترافية تمنحك المهارات العملية لاستخدام الحاسوب والتطبيقات المكتبية بكفاءة عالمية مع شهادة معترف بها دوليًا.',
                'image' => 'https://images.unsplash.com/photo-1581090700227-1e37b190418e?auto=format&fit=crop&w=1200&q=80'
              ],
              [
                'title' => 'دبلوم الحاسوب المتكامل',
                'id' => 'computer-diploma',
                'desc' => 'برنامج شامل يجمع بين الأساسيات النظرية والتطبيق العملي لتأهيلك كخبير في استخدام الحاسوب وتطبيقاته في بيئة العمل الحديثة.',
                'image' => 'https://images.unsplash.com/photo-1603570418855-0e035b1d8a86?auto=format&fit=crop&w=1200&q=80'
              ],
              [
                'title' => 'تطوير الويب',
                'id' => 'web-development',
                'desc' => 'منصات الويب وتطوير الواجهات الأمامية والخلفية باستخدام HTML, CSS, JavaScript, و PHP.',
                'image' => 'https://images.unsplash.com/photo-1498050108023-c5249f4df085?auto=format&fit=crop&w=1200&q=80'
              ],
              [
                'title' => 'التصميم الجرافيكي',
                'id' => 'graphic-design',
                'desc' => 'أساسيات التصميم، استخدام Adobe Photoshop و Illustrator لإنتاج مواد رقمية ومرئية احترافية.',
                'image' => 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?auto=format&fit=crop&w=1200&q=80'
              ],
              [
                'title' => 'تحليل البيانات',
                'id' => 'data-analysis',
                'desc' => 'مبادئ تحليل البيانات، Excel المتقدم، مقدمة في قواعد البيانات وأدوات التحليل.',
                'image' => 'https://images.unsplash.com/photo-1556157382-97eda2d62296?auto=format&fit=crop&w=1200&q=80'
              ],
              [
                'title' => 'اللغة الإنجليزية والمهارات',
                'id' => 'english-skills',
                'desc' => 'دورات تطوير اللغة الإنجليزية ومهارات سوق العمل الأساسية لإعدادك لسوق العمل المحلي والدولي.',
                'image' => 'https://images.unsplash.com/photo-1529070538774-1843cb3265df?auto=format&fit=crop&w=1200&q=80'
              ],
            ];
          }

          // render cards
          foreach ($courses as $c) {
            $title = htmlspecialchars($c['title'], ENT_QUOTES, 'UTF-8');
            $courseId = urlencode($c['id'] ?? $c['title']);
            $desc = htmlspecialchars($c['desc'] ?? '', ENT_QUOTES, 'UTF-8');
            $img = htmlspecialchars($c['image'] ?? '', ENT_QUOTES, 'UTF-8');
            
            // إذا لم يكن هناك صورة، استخدم الشعار
            if (empty($img)) {
              $img = 'photos/Sh.jpg';
            }
            
            echo '<div class="course-card bg-gray-50 rounded-2xl overflow-hidden border">';
            echo "<img loading=\"lazy\" src=\"$img\" onerror=\"this.style.display='none';this.nextElementSibling.style.display='flex'\" class=\"w-full h-48 object-cover\" alt=\"$title\" />";
            echo "<div style=\"display:none;\" class=\"w-full h-48 bg-gradient-to-br from-indigo-100 to-sky-100 items-center justify-center\">";
            echo "<img src=\"photos/Sh.jpg\" class=\"h-20 w-20 rounded-full\" alt=\"$title\" />";
            echo "</div>";
            echo '<div class="p-6">';
            echo "<h3 class=\"text-2xl font-bold text-indigo-700 mb-3\">$title</h3>";
            echo "<p class=\"text-gray-700 mb-4\">$desc</p>";
            echo "<a href=\"application.php?course=$courseId\" class=\"inline-block mt-4 bg-indigo-600 text-white px-5 py-2 rounded-lg shadow hover:bg-indigo-700 transition\">التسجيل في هذه الدورة</a>";
            echo '</div></div>';
          }
          ?>
        </div>
      </div>

      <!-- قسم الفيديو: زيارة مدير المديرية -->
      <div class="mt-20 container mx-auto px-6">
        <div class="bg-gradient-to-br from-indigo-50 to-sky-50 rounded-3xl overflow-hidden shadow-xl">
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-0">
            <!-- النص الوصفي -->
            <div class="p-8 lg:p-12 flex flex-col justify-center">
              <div class="inline-flex items-center bg-indigo-100 text-indigo-700 px-4 py-2 rounded-full text-sm font-semibold mb-4 w-fit">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-4 h-4 ml-2"><polygon points="23 7 16 12 23 17 23 7"></polygon><rect x="1" y="5" width="15" height="14" rx="2" ry="2"></rect></svg>
                <span>زيارة ميدانية</span>
              </div>
              <h2 class="text-3xl lg:text-4xl font-bold text-gray-900 mb-4">
                زيارة مدير مديرية المخا
              </h2>
              <p class="text-lg text-gray-700 leading-relaxed mb-6">
                شاهد لحظات من زيارة مدير مديرية المخا لأحد مراكز منصة إبداع للتدريب والتأهيل في منطقة يختل بالمخا. 
                تأتي هذه الزيارة ضمن الجهود المستمرة لتعزيز التعاون وتطوير البرامج التدريبية لخدمة أبناء المنطقة.
              </p>
              <div class="flex items-center gap-4 text-sm text-gray-600">
                <div class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 ml-2 text-indigo-600"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path><circle cx="12" cy="10" r="3"></circle></svg>
                  <span>يختل - المخا</span>
                </div>
                <div class="flex items-center">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="w-5 h-5 ml-2 text-indigo-600"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                  <span>2025</span>
                </div>
              </div>
            </div>
            
            <!-- مشغل الفيديو -->
            <div class="relative bg-gray-900 flex items-center justify-center min-h-[300px] lg:min-h-[400px]">
              <video 
                controls 
                class="w-full h-full object-cover"
                poster="photos/Sh.jpg"
                preload="metadata"
              >
                <source src="Videos/ad1.mp4" type="video/mp4">
                متصفحك لا يدعم تشغيل الفيديو. يرجى تحديث المتصفح.
              </video>
            </div>
          </div>
        </div>
      </div>

      <!-- الأهداف والمخرجات -->
      <div class="mt-20 text-center max-w-5xl mx-auto">
        <h2 class="text-3xl font-bold text-gray-900 mb-6">🎯 أهداف البرامج التدريبية</h2>
        <p class="text-gray-700 text-lg leading-relaxed mb-8">تهدف منصة <strong>إبداع للتدريب والتأهيل</strong> إلى تمكين المتدربين من امتلاك المهارات الرقمية الحديثة، وربط المعرفة النظرية بالتطبيق العملي.</p>
      </div>
  </main>

  <!-- الفوتر -->
  <footer class="bg-gray-900 text-gray-400 py-10">
    <div class="container mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10 text-center md:text-start">
      <div>
        <div class="flex items-center justify-center md:justify-start space-x-3 rtl:space-x-reverse mb-3">
          <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-10 w-10 rounded-full">
          <h3 class="text-xl font-bold text-white">منصة إبداع</h3>
        </div>
        <p>تعز، اليمن</p>
        <p class="text-sm mt-2">المصمم: RoboStack-Yemen</p>
      </div>
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">روابط سريعة</h4>
        <ul class="space-y-2">
          <li><a href="index.php#about" class="hover:text-white">عن المنصة</a></li>
          <li><a href="courses.php" class="hover:text-white">الدورات</a></li>
          <li><a href="staff.php" class="hover:text-white">الكادر</a></li>
          <li><a href="index.php#gallery" class="hover:text-white">المعرض</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">تواصل معنا</h4>
        <p><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="inline w-5 h-5 text-indigo-400"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"></path></svg> 00967 734 847 037</p>
      </div>
    </div>
    <div class="text-center text-sm border-t border-gray-700 mt-10 pt-5">&copy; 2025 منصة إبداع للتدريب والتأهيل — جميع الحقوق محفوظة.</div>
  </footer>

</body>
</html>
