<?php
// application.php
$course = isset($_GET['course']) ? htmlspecialchars($_GET['course'], ENT_QUOTES, 'UTF-8') : '';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>طلب التسجيل في الدورة - منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; background: url('photos/bg.png') center/cover no-repeat fixed; }
    .overlay { position: fixed; inset:0; background: rgba(0,0,0,0.55); backdrop-filter: blur(3px); z-index: 0; }
  </style>
</head>
<body class="text-white min-h-screen">
  <div class="overlay"></div>

  <!-- Header -->
  <header class="relative z-10 bg-white/10 backdrop-blur-md border-b border-white/20">
    <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
      <a href="index.html" class="flex items-center space-x-3 rtl:space-x-reverse font-bold text-2xl text-indigo-200 hover:text-white transition">
        <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-10 w-10 rounded-full">
        <span>منصة إبداع</span>
      </a>
      <a href="index.html#home" class="bg-indigo-600 text-white px-4 py-2 rounded-lg shadow hover:bg-indigo-700 transition">
        العودة للرئيسية
      </a>
    </nav>
  </header>

  <!-- Form Card -->
  <main class="relative z-10 container mx-auto px-6 py-10">
    <div class="max-w-3xl mx-auto bg-white/10 border border-white/20 rounded-2xl p-8 backdrop-blur-md shadow-2xl">
      <div class="text-center mb-8">
        <h1 class="text-3xl font-bold">طلب التسجيل في الدورة</h1>
        <p class="text-indigo-200 mt-2">يرجى تعبئة البيانات التالية بدقة لإتمام طلب التسجيل.</p>
      </div>

      <form action="apply.php" method="POST" enctype="multipart/form-data" class="space-y-6">
        <!-- Course -->
        <div>
          <label class="block mb-1 text-indigo-100">الدورة</label>
          <select name="course" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900">
            <option value="">اختر الدورة</option>
            <option <?= $course==='ICDL'?'selected':''; ?>>ICDL</option>
            <option <?= $course==='دبلوم الحاسوب المتكامل'?'selected':''; ?>>دبلوم الحاسوب المتكامل</option>
            <option <?= $course==='علوم الحاسوب وتطبيقاته'?'selected':''; ?>>علوم الحاسوب وتطبيقاته</option>
            <option <?= $course==='تصميم الأنظمة التعليمية والإدارية'?'selected':''; ?>>تصميم الأنظمة التعليمية والإدارية</option>
            <option <?= $course==='إكسل المتقدم وتحليل البيانات'?'selected':''; ?>>إكسل المتقدم وتحليل البيانات</option>
            <option <?= $course==='اللغة الإنجليزية'?'selected':''; ?>>اللغة الإنجليزية</option>
            <option <?= $course==='تنمية المهارات الشخصية والمهنية'?'selected':''; ?>>تنمية المهارات الشخصية والمهنية</option>
          </select>
        </div>

        <!-- Name / Email / Phone -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 text-indigo-100">الاسم الكامل</label>
            <input type="text" name="full_name" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900" placeholder="أدخل اسمك الكامل">
          </div>
          <div>
            <label class="block mb-1 text-indigo-100">رقم الهاتف</label>
            <input type="tel" name="phone" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900" placeholder="مثال: 00967xxxxxxxxx">
          </div>
        </div>
        <div>
          <label class="block mb-1 text-indigo-100">البريد الإلكتروني</label>
          <input type="email" name="email" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900" placeholder="example@email.com">
        </div>

        <!-- Gov / District -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label class="block mb-1 text-indigo-100">المحافظة</label>
            <select id="governorate" name="governorate" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900">
              <option value="">اختر المحافظة</option>
            </select>
          </div>
          <div>
            <label class="block mb-1 text-indigo-100">المديرية</label>
            <select id="district" name="district" required class="w-full p-3 rounded-lg bg-white/80 text-gray-900">
              <option value="">اختر المديرية</option>
            </select>
            <input id="district_other" name="district_other" class="hidden w-full mt-2 p-3 rounded-lg bg-white/80 text-gray-900" placeholder="اكتب اسم المديرية">
          </div>
        </div>

        <!-- ID Upload -->
        <div>
          <label class="block mb-1 text-indigo-100">رفع الهوية (صورة أو PDF)</label>
          <input type="file" name="id_file" accept="image/*,.pdf" required class="w-full text-indigo-100 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-indigo-600 file:text-white hover:file:bg-indigo-700">
          <p class="text-xs text-indigo-200 mt-1">الحد الأقصى 5MB. الصيغ المسموحة: JPG, PNG, PDF.</p>
        </div>

        <!-- Commitment -->
        <div class="bg-indigo-900/30 border border-indigo-300/30 rounded-lg p-4">
          <label class="inline-flex items-start space-x-2 rtl:space-x-reverse">
            <input type="checkbox" name="commit" value="yes" required class="mt-1">
            <span>
              أتعهد بسداد رسوم الدورة عند الحضور، وأقر بصحة البيانات المرفقة.
            </span>
          </label>
        </div>

        <!-- Notes -->
        <div>
          <label class="block mb-1 text-indigo-100">ملاحظات إضافية (اختياري)</label>
          <textarea name="notes" rows="3" class="w-full p-3 rounded-lg bg-white/80 text-gray-900" placeholder="اكتب أي تفاصيل مهمة..."></textarea>
        </div>

        <!-- Submit -->
        <div class="text-center">
          <button type="submit" class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white px-8 py-3 rounded-lg shadow-lg hover:opacity-90 transition font-semibold">
            إرسال طلب التسجيل
          </button>
        </div>
      </form>
    </div>
  </main>

  <!-- Footer -->
  <footer class="relative z-10 bg-gray-900 text-gray-400 py-10 mt-10">
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
          <li><a href="index.html#about" class="hover:text-white">عن المنصة</a></li>
          <li><a href="courses.html" class="hover:text-white">الدورات</a></li>
          <li><a href="staff.html" class="hover:text-white">الكادر</a></li>
          <li><a href="index.html#gallery" class="hover:text-white">المعرض</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">تواصل معنا</h4>
        <p><i data-lucide="phone-call" class="inline w-5 h-5 text-indigo-400"></i> 00967 734 847 037</p>
        <p><i data-lucide="message-circle" class="inline w-5 h-5 text-green-400"></i> واتساب 1: 00967 713 567 677</p>
        <p><i data-lucide="message-circle" class="inline w-5 h-5 text-green-400"></i> واتساب 2: 00967 717 781 053</p>
      </div>
    </div>
    <div class="text-center text-sm border-t border-gray-700 mt-10 pt-5">
      &copy; 2025 منصة إبداع للتدريب والتأهيل — جميع الحقوق محفوظة.
    </div>
  </footer>

  <script>
    lucide.createIcons();

    // تعبئة ديناميكية للمحافظات والمديريات عبر API جديد manage_locations.php
    const govSelect = document.getElementById('governorate');
    const distSelect = document.getElementById('district');
    const distOther = document.getElementById('district_other');

    async function loadGovernorates() {
      govSelect.innerHTML = '<option value="">اختر المحافظة</option>';
      try {
        const resp = await fetch('../Manager/api/manage_locations.php?target=regions');
        const json = await resp.json();
        (json.data || []).forEach(r => {
          const opt = document.createElement('option');
          opt.value = r.id; opt.textContent = r.name; govSelect.appendChild(opt);
        });
      } catch (e) { console.error('failed regions', e); }
    }

    async function loadDistricts(regionId) {
      distSelect.innerHTML = '<option value="">اختر المديرية</option>';
      if (!regionId) return;
      try {
        const resp = await fetch(`../Manager/api/manage_locations.php?target=districts&region_id=${regionId}`);
        const json = await resp.json();
        (json.data || []).forEach(d => {
          const opt = document.createElement('option'); opt.value = d.name; opt.textContent = d.name; distSelect.appendChild(opt);
        });
        // خيار أخرى
        const otherOpt = document.createElement('option'); otherOpt.value = 'أخرى'; otherOpt.textContent = 'أخرى'; distSelect.appendChild(otherOpt);
      } catch (e) { console.error('failed districts', e); }
    }

    govSelect.addEventListener('change', e => loadDistricts(e.target.value));
    distSelect.addEventListener('change', e => {
      if (e.target.value === 'أخرى') { distOther.classList.remove('hidden'); distOther.required = true; }
      else { distOther.classList.add('hidden'); distOther.required = false; distOther.value = ''; }
    });

    loadGovernorates();
  </script>
</body>
</html>
