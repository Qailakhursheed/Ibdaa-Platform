<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التحقق من بيانات الطالب - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 to-sky-50 min-h-screen flex items-center justify-center p-6">
    
    <div class="max-w-2xl w-full bg-white rounded-2xl shadow-2xl p-8">
        <!-- شعار المنصة -->
        <div class="text-center mb-8">
            <img src="platform/photos/Sh.jpg" alt="شعار منصة إبداع" class="h-24 w-24 mx-auto rounded-full border-4 border-indigo-600 shadow-lg mb-4">
            <h1 class="text-3xl font-bold text-gray-900 mb-2">منصة إبداع للتدريب والتأهيل</h1>
            <p class="text-gray-600">التحقق من بيانات الطالب</p>
        </div>

        <?php
        require_once __DIR__ . '/platform/db.php';

        $uid = $_GET['uid'] ?? '';
        
        if (empty($uid)) {
            echo '<div class="bg-red-50 border border-red-200 rounded-lg p-6 text-center">';
            echo '<svg class="w-16 h-16 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>';
            echo '<h2 class="text-xl font-bold text-red-700 mb-2">رابط غير صالح</h2>';
            echo '<p class="text-red-600">الرجاء التأكد من الرابط والمحاولة مرة أخرى.</p>';
            echo '</div>';
            exit;
        }

        // جلب بيانات الطالب
        $stmt = $conn->prepare("
            SELECT u.id, u.full_name, u.email, u.phone, u.governorate, u.district, u.created_at,
                   GROUP_CONCAT(DISTINCT c.title SEPARATOR ', ') as courses,
                   COUNT(DISTINCT e.enrollment_id) as enrollment_count
            FROM users u
            LEFT JOIN enrollments e ON u.id = e.student_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.id = ? AND u.role = 'student'
            GROUP BY u.id
        ");
        $stmt->bind_param('i', $uid);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            echo '<div class="bg-yellow-50 border border-yellow-200 rounded-lg p-6 text-center">';
            echo '<svg class="w-16 h-16 text-yellow-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>';
            echo '<h2 class="text-xl font-bold text-yellow-700 mb-2">لم يتم العثور على الطالب</h2>';
            echo '<p class="text-yellow-600">لا توجد بيانات مطابقة لهذا المعرف.</p>';
            echo '</div>';
            exit;
        }

        $student = $result->fetch_assoc();
        ?>

        <!-- بطاقة التحقق الناجحة -->
        <div class="bg-green-50 border-2 border-green-500 rounded-lg p-6 mb-6">
            <div class="flex items-center mb-4">
                <svg class="w-12 h-12 text-green-500 ml-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
                <div>
                    <h2 class="text-2xl font-bold text-green-700">✓ بيانات موثقة</h2>
                    <p class="text-green-600">هذا الطالب مسجل رسمياً لدى منصة إبداع</p>
                </div>
            </div>
        </div>

        <!-- بيانات الطالب -->
        <div class="space-y-4">
            <div class="bg-gray-50 rounded-lg p-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <span class="text-sm text-gray-500">الاسم الكامل</span>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($student['full_name']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">رقم الهاتف</span>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($student['phone'] ?? 'غير متوفر'); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">البريد الإلكتروني</span>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($student['email']); ?></p>
                    </div>
                    <div>
                        <span class="text-sm text-gray-500">المحافظة</span>
                        <p class="text-lg font-bold text-gray-900"><?php echo htmlspecialchars($student['governorate'] ?? 'غير محدد'); ?></p>
                    </div>
                </div>
            </div>

            <?php if ($student['enrollment_count'] > 0 && !empty($student['courses'])): ?>
            <div class="bg-indigo-50 rounded-lg p-4">
                <span class="text-sm text-indigo-600 font-semibold">الدورات المسجلة</span>
                <p class="text-lg font-bold text-indigo-900 mt-2"><?php echo htmlspecialchars($student['courses']); ?></p>
                <p class="text-sm text-indigo-600 mt-1">عدد الدورات: <?php echo $student['enrollment_count']; ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-gray-50 rounded-lg p-4">
                <span class="text-sm text-gray-500">تاريخ التسجيل</span>
                <p class="text-lg font-bold text-gray-900"><?php echo date('Y/m/d', strtotime($student['created_at'])); ?></p>
            </div>
        </div>

        <!-- معلومات إضافية -->
        <div class="mt-8 pt-6 border-t border-gray-200 text-center text-sm text-gray-600">
            <p class="mb-2">
                <strong>نؤكد أن</strong> <span class="text-indigo-600 font-bold"><?php echo htmlspecialchars($student['full_name']); ?></span>
            </p>
            <p>
                هو طالب مسجل رسمياً لدى <strong>منصة إبداع للتدريب والتأهيل</strong>
            </p>
            <p class="mt-4 text-xs text-gray-500">
                للاستفسارات: 00967 734 847 037
            </p>
        </div>

        <!-- زر العودة -->
        <div class="mt-6 text-center">
            <a href="platform/index.php" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg font-semibold hover:bg-indigo-700 transition">
                العودة للصفحة الرئيسية
            </a>
        </div>
    </div>

</body>
</html>
