<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>حول المنصة - منصة إبداع للتدريب والتأهيل</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style> body { font-family: 'Cairo', sans-serif; } </style>
</head>
<body class="bg-gradient-to-br from-blue-50 to-sky-100">
    <?php include '_header.php'; ?>

    <!-- Hero Section -->
    <section class="py-16">
        <div class="container mx-auto px-4 text-center">
            <h2 class="text-5xl font-bold text-gray-800 mb-4">من نحن؟</h2>
            <p class="text-xl text-gray-600 max-w-3xl mx-auto">منصة إبداع للتدريب والتأهيل - رائدة في مجال التدريب التقني والمهني في اليمن</p>
        </div>
    </section>

    <!-- Vision & Mission -->
    <section class="py-12 bg-white">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-2 gap-8 max-w-5xl mx-auto">
                <div class="bg-gradient-to-br from-sky-500 to-blue-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="flex items-center gap-3 mb-4"><i data-lucide="eye" class="w-10 h-10"></i><h3 class="text-3xl font-bold">رؤيتنا</h3></div>
                    <p class="text-lg leading-relaxed">أن نكون المنصة الرائدة في التدريب التقني والمهني في اليمن، ونساهم في بناء جيل مؤهل قادر على مواكبة التطورات التكنولوجية العالمية.</p>
                </div>
                <div class="bg-gradient-to-br from-cyan-500 to-teal-600 text-white p-8 rounded-2xl shadow-xl">
                    <div class="flex items-center gap-3 mb-4"><i data-lucide="target" class="w-10 h-10"></i><h3 class="text-3xl font-bold">رسالتنا</h3></div>
                    <p class="text-lg leading-relaxed">تقديم برامج تدريبية متميزة تلبي احتياجات سوق العمل، وتمكين الشباب اليمني من اكتساب المهارات اللازمة لبناء مستقبل مهني ناجح.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="container mx-auto px-4">
            <div class="grid md:grid-cols-3 gap-8 mb-8">
                <div><h4 class="text-xl font-bold mb-4">منصة إبداع</h4><p class="text-gray-400">رائدة في مجال التدريب التقني والمهني في اليمن</p></div>
                <div>
                    <h4 class="text-xl font-bold mb-4">روابط سريعة</h4>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="index.php" class="hover:text-white">الرئيسية</a></li>
                        <li><a href="courses.php" class="hover:text-white">الدورات</a></li>
                        <li><a href="staff.php" class="hover:text-white">فريق العمل</a></li>
                        <li><a href="about.php" class="hover:text-white">من نحن</a></li>
                    </ul>
                </div>
                <div><h4 class="text-xl font-bold mb-4">تواصل معنا</h4><ul class="space-y-2 text-gray-400"><li>📍 تعز - اليمن</li><li>📧 ha717781053@gmail.com</li></ul></div>
            </div>
            <div class="border-t border-gray-800 pt-8 pt-8 text-center text-gray-400"><p>© 2025 منصة إبداع للتدريب والتأهيل - جميع الحقوق محفوظة</p></div>
        </div>
    </footer>

    <script>lucide.createIcons();</script>
</body>
</html>
