<?php
session_start();

echo "<!DOCTYPE html>";
echo "<html lang='ar' dir='rtl'>";
echo "<head><meta charset='utf-8'><title>فحص الجلسة</title>";
echo "<script src='https://cdn.tailwindcss.com'></script>";
echo "<link href='https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap' rel='stylesheet'>";
echo "<style>body { font-family: 'Cairo', sans-serif; }</style>";
echo "</head>";
echo "<body class='min-h-screen bg-slate-50 p-8'>";

echo "<div class='max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-6'>";
echo "<h1 class='text-2xl font-bold text-slate-800 mb-6'>🔍 فحص حالة الجلسة (Session)</h1>";

if (isset($_SESSION['user_id'])) {
    echo "<div class='bg-green-50 border border-green-200 rounded-lg p-4 mb-6'>";
    echo "<h2 class='text-lg font-bold text-green-800 mb-3'>✅ الجلسة نشطة!</h2>";
    echo "<div class='space-y-2 text-sm'>";
    echo "<div><span class='font-semibold text-green-700'>User ID:</span> <code class='bg-white px-2 py-1 rounded'>" . htmlspecialchars($_SESSION['user_id']) . "</code></div>";
    echo "<div><span class='font-semibold text-green-700'>User Name:</span> <code class='bg-white px-2 py-1 rounded'>" . htmlspecialchars($_SESSION['user_name'] ?? 'غير محدد') . "</code></div>";
    echo "<div><span class='font-semibold text-green-700'>User Email:</span> <code class='bg-white px-2 py-1 rounded'>" . htmlspecialchars($_SESSION['user_email'] ?? 'غير محدد') . "</code></div>";
    echo "<div><span class='font-semibold text-green-700'>User Role:</span> <code class='bg-white px-2 py-1 rounded'>" . htmlspecialchars($_SESSION['user_role'] ?? 'غير محدد') . "</code></div>";
    echo "<div><span class='font-semibold text-green-700'>Role (fallback):</span> <code class='bg-white px-2 py-1 rounded'>" . htmlspecialchars($_SESSION['role'] ?? 'غير محدد') . "</code></div>";
    echo "</div>";
    echo "</div>";
    
    echo "<div class='flex gap-3'>";
    echo "<a href='dashboard_router.php' class='bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded-lg font-semibold transition'>الذهاب إلى Dashboard</a>";
    echo "<a href='logout.php' class='bg-red-500 hover:bg-red-600 text-white px-6 py-2 rounded-lg font-semibold transition'>تسجيل الخروج</a>";
    echo "</div>";
    
} else {
    echo "<div class='bg-red-50 border border-red-200 rounded-lg p-4 mb-6'>";
    echo "<h2 class='text-lg font-bold text-red-800 mb-2'>❌ لا توجد جلسة نشطة!</h2>";
    echo "<p class='text-sm text-red-600'>لم يتم تسجيل الدخول أو انتهت صلاحية الجلسة.</p>";
    echo "</div>";
    
    echo "<a href='login.php' class='inline-block bg-sky-500 hover:bg-sky-600 text-white px-6 py-2 rounded-lg font-semibold transition'>تسجيل الدخول</a>";
}

echo "<div class='mt-6 p-4 bg-slate-50 rounded-lg border border-slate-200'>";
echo "<h3 class='text-sm font-bold text-slate-700 mb-2'>🔬 معلومات الجلسة الكاملة:</h3>";
echo "<pre class='text-xs overflow-auto bg-white p-3 rounded border border-slate-300'>" . print_r($_SESSION, true) . "</pre>";
echo "</div>";

echo "</div>";
echo "</body>";
echo "</html>";
?>
