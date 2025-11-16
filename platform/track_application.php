<?php
/**
 * صفحة تتبع الطلب
 * Track Application Status
 * 
 * يسمح للطلاب بتتبع حالة طلبهم
 */

require_once __DIR__ . '/../platform/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/anti_detection.php';

AntiDetection::hideServerHeaders();

$application = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $application_id = (int)($_POST['application_id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    
    if ($application_id > 0 && !empty($email)) {
        $stmt = $conn->prepare("
            SELECT 
                a.*,
                c.name as course_name,
                c.price as course_price,
                u_reviewer.full_name as reviewer_name
            FROM applications a
            JOIN courses c ON c.id = a.course_id
            LEFT JOIN users u_reviewer ON u_reviewer.id = a.reviewed_by
            WHERE a.id = ? AND a.email = ?
        ");
        $stmt->bind_param('is', $application_id, $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $application = $result->fetch_assoc();
        } else {
            $error = 'الطلب غير موجود أو البريد الإلكتروني غير صحيح';
        }
        
        $stmt->close();
    } else {
        $error = 'يرجى إدخال رقم الطلب والبريد الإلكتروني';
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع طلب الانضمام - منصة إبداع</title>
    <?php echo CSRF::getMetaTag(); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            right: 19px;
            top: 40px;
            height: calc(100% - 40px);
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child::before {
            display: none;
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white rounded-full shadow-lg mb-4">
                <img src="photos/Sh.jpg" alt="منصة إبداع" class="w-20 h-20 rounded-full">
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">تتبع طلب الانضمام 🔍</h1>
            <p class="text-white/90">تحقق من حالة طلبك في الوقت الفعلي</p>
        </div>

        <!-- Search Form -->
        <?php if (!$application): ?>
            <div class="bg-white rounded-2xl shadow-2xl p-8 mb-8">
                <h2 class="text-2xl font-bold text-gray-800 mb-6">أدخل بيانات طلبك</h2>
                
                <?php if ($error): ?>
                    <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                        <p class="text-red-700"><?php echo htmlspecialchars($error); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="POST" class="space-y-6">
                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            رقم الطلب <span class="text-red-500">*</span>
                        </label>
                        <input type="number" name="application_id" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                               placeholder="مثال: 12345">
                        <p class="text-xs text-gray-500 mt-1">تم إرسال رقم الطلب على بريدك الإلكتروني بعد التقديم</p>
                    </div>

                    <div>
                        <label class="block text-sm font-bold text-gray-700 mb-2">
                            البريد الإلكتروني <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" required 
                               class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                               placeholder="example@email.com">
                    </div>

                    <button type="submit" 
                            class="w-full py-3 bg-gradient-to-r from-indigo-600 to-purple-600 text-white rounded-lg hover:from-indigo-700 hover:to-purple-700 transition font-bold shadow-lg">
                        🔍 تتبع الطلب
                    </button>
                </form>
            </div>
        <?php else: ?>
            <!-- Application Status -->
            <div class="bg-white rounded-2xl shadow-2xl overflow-hidden mb-8">
                <!-- Header -->
                <div class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white p-6">
                    <div class="flex justify-between items-center">
                        <div>
                            <h2 class="text-2xl font-bold">طلب رقم #<?php echo $application['id']; ?></h2>
                            <p class="text-white/80 mt-1"><?php echo htmlspecialchars($application['full_name']); ?></p>
                        </div>
                        <div class="text-right">
                            <?php
                            $status_badge = '';
                            switch($application['status']) {
                                case 'pending':
                                    $status_badge = '<span class="px-4 py-2 bg-yellow-500 rounded-full text-sm font-bold">⏳ قيد المراجعة</span>';
                                    break;
                                case 'approved':
                                    if ($application['payment_status'] === 'completed') {
                                        $status_badge = '<span class="px-4 py-2 bg-green-500 rounded-full text-sm font-bold">✅ مكتمل</span>';
                                    } else {
                                        $status_badge = '<span class="px-4 py-2 bg-blue-500 rounded-full text-sm font-bold">💰 بانتظار الدفع</span>';
                                    }
                                    break;
                                case 'rejected':
                                    $status_badge = '<span class="px-4 py-2 bg-red-500 rounded-full text-sm font-bold">❌ مرفوض</span>';
                                    break;
                            }
                            echo $status_badge;
                            ?>
                        </div>
                    </div>
                </div>

                <!-- Details -->
                <div class="p-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                        <div>
                            <h3 class="font-bold text-gray-700 mb-3">معلومات الطلب</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>الدورة:</strong> <?php echo htmlspecialchars($application['course_name']); ?></p>
                                <p><strong>الرسوم:</strong> <span class="text-green-600 font-bold"><?php echo number_format($application['course_price'], 0); ?> ريال</span></p>
                                <p><strong>تاريخ التقديم:</strong> <?php echo date('Y-m-d H:i', strtotime($application['created_at'])); ?></p>
                            </div>
                        </div>
                        <div>
                            <h3 class="font-bold text-gray-700 mb-3">معلومات الاتصال</h3>
                            <div class="space-y-2 text-sm">
                                <p><strong>البريد:</strong> <?php echo htmlspecialchars($application['email']); ?></p>
                                <p><strong>الهاتف:</strong> <?php echo htmlspecialchars($application['phone']); ?></p>
                                <p><strong>العنوان:</strong> <?php echo htmlspecialchars($application['governorate']); ?> - <?php echo htmlspecialchars($application['district']); ?></p>
                            </div>
                        </div>
                    </div>

                    <!-- Timeline -->
                    <h3 class="font-bold text-gray-800 text-xl mb-6">مسار الطلب</h3>
                    <div class="relative">
                        <!-- Step 1: تقديم الطلب -->
                        <div class="timeline-item relative flex gap-4 pb-8">
                            <div class="flex-shrink-0">
                                <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                    ✓
                                </div>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800">تقديم الطلب</h4>
                                <p class="text-sm text-gray-600">تم استلام طلبك بنجاح</p>
                                <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($application['created_at'])); ?></p>
                            </div>
                        </div>

                        <!-- Step 2: المراجعة -->
                        <div class="timeline-item relative flex gap-4 pb-8">
                            <div class="flex-shrink-0">
                                <?php if ($application['status'] === 'pending'): ?>
                                    <div class="w-10 h-10 bg-yellow-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg animate-pulse">
                                        ⏳
                                    </div>
                                <?php elseif ($application['status'] === 'approved' || $application['status'] === 'rejected'): ?>
                                    <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                        ✓
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="flex-1">
                                <h4 class="font-bold text-gray-800">مراجعة المشرف الفني</h4>
                                <?php if ($application['status'] === 'pending'): ?>
                                    <p class="text-sm text-gray-600">جاري مراجعة طلبك... يرجى الانتظار</p>
                                <?php elseif ($application['status'] === 'approved'): ?>
                                    <p class="text-sm text-green-600 font-bold">✅ تم قبول طلبك!</p>
                                    <?php if ($application['reviewer_name']): ?>
                                        <p class="text-xs text-gray-500">راجعه: <?php echo htmlspecialchars($application['reviewer_name']); ?></p>
                                    <?php endif; ?>
                                    <p class="text-xs text-gray-500 mt-1"><?php echo date('Y-m-d H:i', strtotime($application['reviewed_at'])); ?></p>
                                <?php elseif ($application['status'] === 'rejected'): ?>
                                    <p class="text-sm text-red-600 font-bold">❌ تم رفض الطلب</p>
                                    <?php if ($application['review_notes']): ?>
                                        <p class="text-sm text-gray-700 mt-2 bg-red-50 p-2 rounded"><strong>السبب:</strong> <?php echo htmlspecialchars($application['review_notes']); ?></p>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <?php if ($application['status'] === 'approved'): ?>
                            <!-- Step 3: الدفع -->
                            <div class="timeline-item relative flex gap-4 pb-8">
                                <div class="flex-shrink-0">
                                    <?php if ($application['payment_status'] === 'completed'): ?>
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                            ✓
                                        </div>
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-blue-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg animate-pulse">
                                            💰
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800">دفع الرسوم</h4>
                                    <?php if ($application['payment_status'] === 'completed'): ?>
                                        <p class="text-sm text-green-600 font-bold">✅ تم التحقق من الدفع</p>
                                    <?php else: ?>
                                        <p class="text-sm text-blue-600 font-bold">⏳ في انتظار دفع الرسوم</p>
                                        <div class="bg-blue-50 p-3 rounded-lg mt-2">
                                            <p class="text-sm font-bold">المبلغ المطلوب: <?php echo number_format($application['course_price'], 0); ?> ريال</p>
                                            <p class="text-xs text-gray-600 mt-1">يرجى التواصل معنا لإتمام الدفع:</p>
                                            <p class="text-xs text-gray-600">📞 هاتف: 00967-XXX-XXX-XXX</p>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Step 4: تفعيل الحساب -->
                            <div class="timeline-item relative flex gap-4">
                                <div class="flex-shrink-0">
                                    <?php if ($application['payment_status'] === 'completed'): ?>
                                        <div class="w-10 h-10 bg-green-500 rounded-full flex items-center justify-center text-white font-bold shadow-lg">
                                            ✓
                                        </div>
                                    <?php else: ?>
                                        <div class="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center text-gray-600 font-bold">
                                            🔒
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div class="flex-1">
                                    <h4 class="font-bold text-gray-800">تفعيل الحساب</h4>
                                    <?php if ($application['payment_status'] === 'completed'): ?>
                                        <p class="text-sm text-green-600 font-bold">🎉 تم تفعيل حسابك!</p>
                                        <p class="text-sm text-gray-600 mt-2">تم إرسال بيانات الدخول على بريدك الإلكتروني</p>
                                        <a href="login.php" class="inline-block mt-3 px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">
                                            تسجيل الدخول →
                                        </a>
                                    <?php else: ?>
                                        <p class="text-sm text-gray-600">سيتم تفعيل حسابك تلقائياً بعد تأكيد الدفع</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="text-center">
                <a href="track_application.php" class="inline-block px-8 py-3 bg-white text-indigo-600 rounded-lg hover:bg-gray-100 transition font-bold shadow-lg">
                    ← تتبع طلب آخر
                </a>
            </div>
        <?php endif; ?>

        <!-- Footer -->
        <div class="text-center mt-8 text-white">
            <p class="text-sm">تحتاج مساعدة؟ 
                <a href="mailto:info@ibdaa-taiz.com" class="font-bold underline hover:text-white/80">تواصل معنا</a>
            </p>
            <p class="text-xs mt-2 opacity-75">© 2025 منصة إبداع - تعز. جميع الحقوق محفوظة</p>
        </div>
    </div>
</body>
</html>
