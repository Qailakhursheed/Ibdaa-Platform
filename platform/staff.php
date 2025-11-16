<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>الكادر التعليمي - منصة إبداع</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script src="https://unpkg.com/lucide@latest"></script>

  <style>
    @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
    body { font-family: 'Cairo', sans-serif; background: #f9fafb; }
    .member-card { transition: all 0.3s ease; cursor: pointer; }
    .member-card:hover { transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.1); }
    .member-active { border: 3px solid #4f46e5; transform: scale(1.02); }
    .member-info { animation: fadeIn 0.4s ease; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  </style>
</head>

<body class="text-gray-900">
  <?php include '_header.php'; ?>

  <!-- قسم الكادر -->
  <section class="py-20 bg-white">
    <div class="container mx-auto px-6">
      <div class="text-center mb-12">
        <h1 class="text-4xl font-bold text-gray-900 mb-4">الكادر التعليمي والإداري</h1>
        <p class="text-gray-600 text-lg max-w-3xl mx-auto">تضم منصة <strong>إبداع للتدريب والتأهيل</strong> نخبة من المؤسسين والمدربين المعتمدين ذوي الخبرة العالية في التدريب والتعليم.</p>
      </div>

      <!-- شبكة الأعضاء -->
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8 mb-12 text-center">
        <div class="member-card bg-gray-50 rounded-xl p-6 border" onclick="showMember('baset')" id="card-baset">
          <img src="photos/Sir1.jpg" alt="عبد الباسط يوسف" class="w-28 h-28 mx-auto rounded-full border-4 border-indigo-400 mb-3 object-cover">
          <h3 class="text-xl font-bold text-gray-800">أ / عبد الباسط يوسف عوض سعيد</h3>
          <p class="text-indigo-600 font-medium">مدير عام المنصة</p>
        </div>
        <div class="member-card bg-gray-50 rounded-xl p-6 border" onclick="showMember('elaa')" id="card-elaa">
          <img src="https://placehold.co/150x150/818cf8/ffffff?text=EL" alt="عبد الإله هزاع" class="w-28 h-28 mx-auto rounded-full border-4 border-indigo-400 mb-3 object-cover">
          <h3 class="text-xl font-bold text-gray-800">أ / عبد الإله هزاع الحريبي</h3>
          <p class="text-indigo-600 font-medium">نائب المدير ومدرب ICDL</p>
        </div>
        <div class="member-card bg-gray-50 rounded-xl p-6 border" onclick="showMember('osama')" id="card-osama">
          <img src="https://placehold.co/150x150/6366f1/ffffff?text=OS" alt="أسامة عبد الباسط" class="w-28 h-28 mx-auto rounded-full border-4 border-indigo-400 mb-3 object-cover">
          <h3 class="text-xl font-bold text-gray-800">أسامة عبد الباسط يوسف</h3>
          <p class="text-indigo-600 font-medium">الشؤون المالية والإدارية</p>
        </div>
        <div class="member-card bg-gray-50 rounded-xl p-6 border" onclick="showMember('rashdi')" id="card-rashdi">
          <img src="https://placehold.co/150x150/a5b4fc/312e81?text=RH" alt="رشدي بسام" class="w-28 h-28 mx-auto rounded-full border-4 border-indigo-400 mb-3 object-cover">
          <h3 class="text-xl font-bold text-gray-800">رشدي بسام الحريبي</h3>
          <p class="text-indigo-600 font-medium">السكرتارية والفني</p>
        </div>
      </div>

      <!-- محتوى العرض -->
      <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <div id="member-info" class="member-info bg-gray-100 rounded-2xl p-8 hidden lg:col-span-2 md:flex md:items-center md:space-x-8 rtl:space-x-reverse">
          <img id="info-photo" src="" alt="" title="" class="w-40 h-40 rounded-2xl border-4 border-indigo-400 object-cover">
          <div>
            <h2 id="info-name" class="text-2xl font-bold text-gray-900 mb-2"></h2>
            <h4 id="info-role" class="text-indigo-600 font-semibold mb-4"></h4>
            <p id="info-bio" class="text-gray-700 leading-relaxed"></p>
          </div>
        </div>

        <div class="trainer-slider bg-gray-50 rounded-2xl p-4 border hidden lg:block">
          <h3 class="text-xl font-semibold text-gray-800 mb-4 text-center">مدربون آخرون</h3>
          <div id="trainer-slider-list" class="space-y-4"></div>
        </div>
      </div>
    </div>

    <div class="text-center mt-16">
      <h3 class="text-xl text-gray-700 font-semibold">تحتوي المنصة على نخبة من المدربين المميزين، وسيتم إضافة تفاصيلهم قريبًا 🌟</h3>
    </div>
  </section>

  <!-- الفوتر -->
  <footer class="bg-gray-900 text-gray-400 py-10">
    <div class="container mx-auto px-6 grid grid-cols-1 md:grid-cols-3 gap-10 text-center md:text-start">
      <div>
        <div class="flex items-center justify-center md:justify-start space-x-3 rtl:space-x-reverse mb-3">
          <img src="photos/Sh.jpg" alt="شعار منصة إبداع" title="شعار منصة إبداع" class="h-10 w-10 rounded-full">
          <h3 class="text-xl font-bold text-white">منصة إبداع</h3>
        </div>
        <p>تعز، اليمن</p>
        <p class="text-sm mt-2">المصمم: RoboStack-Yemen</p>
      </div>
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">روابط سريعة</h4>
        <ul class="space-y-2">
          <li><a href="index.php#about" class="hover:text-white">عن المنصة</a></li>
          <li><a href="index.php#courses" class="hover:text-white">الدورات</a></li>
          <li><a href="staff.php" class="hover:text-white">الكادر</a></li>
          <li><a href="index.php#gallery" class="hover:text-white">المعرض</a></li>
        </ul>
      </div>
      <div>
        <h4 class="text-lg font-semibold text-white mb-4">تواصل معنا</h4>
        <p><i data-lucide="phone-call" class="inline w-5 h-5 text-indigo-400"></i> 00967 734 847 037</p>
      </div>
    </div>
    <div class="text-center text-sm border-t border-gray-700 mt-10 pt-5">&copy; 2025 منصة إبداع للتدريب والتأهيل — جميع الحقوق محفوظة.</div>
  </footer>

  <script>
    const members = {
      baset: { name: "الأستاذ عبد الباسط يوسف اليوسفي", role: "مدير عام المنصة", photo: "photos/Sir1.jpg", bio: "مدرب معتمد وخبير في مجال التدريب والتطوير..." },
      elaa: { name: "أ / عبد الإله هزاع الحريبي", role: "نائب المدير ومدرب ICDL", photo: "https://placehold.co/300x300/818cf8/ffffff?text=E.H", bio: "مدرب معتمد وعضو إداري نشط..." },
      osama: { name: "أسامة عبد الباسط يوسف", role: "الشؤون المالية والإدارية", photo: "https://placehold.co/300x300/6366f1/ffffff?text=O.A", bio: "المسؤول المالي والإداري..." },
      rashdi: { name: "رشدي بسام الحريبي", role: "السكرتارية والفني", photo: "https://placehold.co/300x300/a5b4fc/312e81?text=R.H", bio: "المهام الفنية والإدارية اليومية..." }
    };

    function showMember(key) {
      const m = members[key]; if (!m) return;
      document.querySelectorAll('.member-card').forEach(c => c.classList.remove('member-active'));
      document.getElementById('card-' + key)?.classList.add('member-active');
      const info = document.getElementById('member-info'); info.classList.remove('hidden');
      document.getElementById('info-photo').src = m.photo; document.getElementById('info-photo').alt = m.name; document.getElementById('info-name').textContent = m.name; document.getElementById('info-role').textContent = m.role; document.getElementById('info-bio').textContent = m.bio;
      const infoM = document.getElementById('member-info-mobile'); if (infoM) infoM.classList.remove('hidden');
    }

    function fillTrainerSlider() {
      const sidebar = document.getElementById('trainer-slider-list'); if (!sidebar) return; sidebar.innerHTML = '';
      for (const [key, m] of Object.entries(members)) {
        const card = document.createElement('div');
        card.className = "cursor-pointer flex items-center space-x-3 rtl:space-x-reverse bg-white p-3 rounded-lg shadow hover:shadow-md transition";
        card.innerHTML = `<img src="${m.photo}" alt="${m.name}" title="${m.name}" class="w-12 h-12 rounded-full border-2 border-indigo-400 object-cover"><div><p class="font-bold text-gray-800 text-sm">${m.name}</p><p class="text-indigo-600 text-xs">${m.role}</p></div>`;
        card.onclick = () => showMember(key); sidebar.appendChild(card);
      }
    }

    document.addEventListener("DOMContentLoaded", () => { lucide.createIcons(); fillTrainerSlider(); showMember('baset'); });
  </script>
</body>
</html>
