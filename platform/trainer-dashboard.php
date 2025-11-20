<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'trainer') {
    header('Location: login.php');
    exit;
}
$trainer_name = $_SESSION['user_name'] ?? 'مدرب';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة تحكم المدرب - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="css/trainer-dashboard.css">
</head>
<body class="bg-gray-100">

    <div class="flex h-screen">
        <!-- Sidebar -->
        <aside class="w-64 bg-gray-800 text-white flex flex-col">
            <div class="p-6 text-center border-b border-gray-700">
                <h1 class="text-2xl font-bold">منصة إبداع</h1>
                <p class="text-sm text-gray-400">مرحباً، <?= htmlspecialchars($trainer_name) ?></p>
            </div>
            <nav class="flex-grow">
                <a href="#dashboard" class="nav-link active"><i data-lucide="layout-dashboard"></i><span>الرئيسية</span></a>
                <a href="#courses" class="nav-link"><i data-lucide="book-open"></i><span>دوراتي</span></a>
                <a href="#students" class="nav-link"><i data-lucide="users"></i><span>طلابي</span></a>
                <a href="#attendance" class="nav-link"><i data-lucide="clipboard-check"></i><span>الحضور</span></a>
                <a href="#grades" class="nav-link"><i data-lucide="award"></i><span>الدرجات</span></a>
                <a href="#profile" class="nav-link"><i data-lucide="user"></i><span>ملفي الشخصي</span></a>
            </nav>
            <div class="p-4 border-t border-gray-700">
                <a href="logout.php" class="w-full text-center bg-red-600 hover:bg-red-700 text-white py-2 rounded-lg">تسجيل الخروج</a>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="flex-1 p-8 overflow-y-auto">
            <header class="mb-8">
                <h2 id="pageTitle" class="text-3xl font-bold text-gray-800"></h2>
                <p id="pageSubtitle" class="text-gray-600"></p>
            </header>
            <div id="pageBody">
                <!-- Content will be loaded here by JavaScript -->
            </div>
        </main>
    </div>

    <script src="js/trainer-features.js"></script>
    <script>
        lucide.createIcons();
    </script>
</body>
</html>
