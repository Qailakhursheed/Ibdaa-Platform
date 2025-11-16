<?php
// Shared header include for platform pages
?>
<header class="bg-white shadow-md sticky top-0 z-50">
  <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
    <a href="index.php#home" class="flex items-center space-x-3 rtl:space-x-reverse font-bold text-2xl text-indigo-700 hover:text-indigo-900 transition">
      <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-10 w-10 rounded-full">
      <span>منصة إبداع</span>
    </a>

    <!-- روابط -->
    <div class="hidden md:flex items-center space-x-6 rtl:space-x-reverse">
      <a href="index.php#home" class="hover:text-indigo-600 transition">الرئيسية</a>
      <a href="index.php#about" class="hover:text-indigo-600 transition">عن المنصة</a>
      <a href="index.php#courses" class="hover:text-indigo-600 transition">الدورات</a>
      <a href="staff.php" class="hover:text-indigo-600 transition">الكادر</a>
      <a href="index.php#gallery" class="hover:text-indigo-600 transition">المعرض</a>
    </div>

    <!-- أزرار اللغة والدخول -->
    <div class="hidden md:flex items-center space-x-4 rtl:space-x-reverse">
      <!-- أزرار اللغة -->
      <div class="flex items-center space-x-2 rtl:space-x-reverse border-s ps-3">
        <button id="lang-ar" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">AR</button>
        <button id="lang-en" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">EN</button>
        <button id="lang-ch" class="lang-btn px-3 py-1 rounded-md text-sm text-gray-700 hover:bg-indigo-50">CH</button>
      </div>
      <!-- أزرار الدخول (رابط لصفحات تسجيل/دخول) -->
      <a href="login.php" class="text-indigo-600 border border-indigo-600 px-5 py-2 rounded-lg hover:bg-indigo-50 transition">تسجيل الدخول</a>
      <a href="signup.php" class="bg-indigo-600 text-white px-5 py-2 rounded-lg shadow-lg hover:bg-indigo-700 transition btn-glow">إنشاء حساب</a>
    </div>

    <!-- زر الموبايل -->
    <button id="mobileMenuBtn" class="md:hidden text-gray-700" type="button" aria-label="فتح القائمة" title="القائمة" aria-controls="mobileMenu" aria-expanded="false"><i data-lucide="menu" aria-hidden="true"></i></button>
  </nav>

  <!-- قائمة الموبايل -->
  <div id="mobileMenu" class="hidden bg-white border-t border-gray-200 md:hidden">
    <a href="index.php#home" class="block px-6 py-3 hover:bg-indigo-50">الرئيسية</a>
    <a href="index.php#about" class="block px-6 py-3 hover:bg-indigo-50">عن المنصة</a>
    <a href="index.php#courses" class="block px-6 py-3 hover:bg-indigo-50">الدورات</a>
    <a href="staff.php" class="block px-6 py-3 hover:bg-indigo-50">الكادر</a>
    <a href="index.php#gallery" class="block px-6 py-3 hover:bg-indigo-50">المعرض</a>
    <div class="border-t my-2"></div>
    <div class="px-6 mt-3 space-y-3">
      <a href="login.php" class="w-full block text-center text-indigo-600 border border-indigo-600 py-3 rounded-lg hover:bg-indigo-50">تسجيل الدخول</a>
      <a href="signup.php" class="w-full block text-center bg-indigo-600 text-white py-3 rounded-lg shadow-lg hover:bg-indigo-700 btn-glow">إنشاء حساب</a>
    </div>
  </div>
</header>
<!-- Chatbot widget (global) -->
<link rel="stylesheet" href="/platform/css/chatbot.css">
<script defer src="/platform/js/chatbot.js"></script>
