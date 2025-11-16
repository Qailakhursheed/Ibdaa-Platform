<?php
/**
 * نموذج الدعم الفني المتقدم
 * يرسل ويحفظ الرسائل في البريد الإلكتروني وقاعدة البيانات
 */
session_start();
require_once 'db.php';

// معلومات البريد الإلكتروني
define('SUPPORT_EMAIL', 'support@ibdaa-platform.com'); // غير هذا البريد
define('ADMIN_EMAIL', 'admin@ibdaa-platform.com');
define('SITE_NAME', 'منصة إبداع للتدريب والتأهيل');

$success = false;
$error = '';
$ticket_id = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
    $category = $_POST['category'] ?? 'general';
    $priority = $_POST['priority'] ?? 'medium';
    $message = trim($_POST['message'] ?? '');
    
    // التحقق من البيانات
    if (empty($name) || empty($email) || empty($subject) || empty($message)) {
        $error = 'جميع الحقول المطلوبة يجب ملؤها';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'البريد الإلكتروني غير صحيح';
    } else {
        // إنشاء رقم التذكرة
        $ticket_id = 'TKT-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
        
        // حفظ في قاعدة البيانات
        $stmt = $conn->prepare("INSERT INTO support_tickets 
            (ticket_id, name, email, phone, subject, category, priority, message, status, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())");
        $stmt->bind_param("ssssssss", $ticket_id, $name, $email, $phone, $subject, $category, $priority, $message);
        
        if ($stmt->execute()) {
            // إرسال بريد إلكتروني للمستخدم
            $user_subject = "تأكيد استلام تذكرة الدعم - $ticket_id";
            $user_message = "
            <html dir='rtl'>
            <head>
                <meta charset='UTF-8'>
                <style>
                    body { font-family: 'Cairo', Arial, sans-serif; background: #f3f4f6; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background: white; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
                    .header { background: linear-gradient(135deg, #6366f1 0%, #3b82f6 100%); color: white; padding: 30px; text-align: center; }
                    .logo { width: 80px; height: 80px; border-radius: 50%; margin-bottom: 15px; border: 3px solid white; }
                    .content { padding: 30px; }
                    .ticket-box { background: #f3f4f6; padding: 20px; border-radius: 8px; margin: 20px 0; border-right: 4px solid #6366f1; }
                    .ticket-id { font-size: 24px; font-weight: bold; color: #6366f1; margin-bottom: 10px; }
                    .info-row { display: flex; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid #e5e7eb; }
                    .label { font-weight: 600; color: #64748b; }
                    .value { color: #1e293b; }
                    .message-box { background: #fef3c7; padding: 15px; border-radius: 8px; margin: 15px 0; }
                    .footer { background: #1e293b; color: white; padding: 20px; text-align: center; font-size: 14px; }
                    .btn { display: inline-block; background: #6366f1; color: white; padding: 12px 30px; text-decoration: none; border-radius: 8px; margin: 15px 0; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <img src='https://yourdomain.com/photos/Sh.jpg' alt='شعار إبداع' class='logo'>
                        <h1>✅ تم استلام تذكرة الدعم بنجاح</h1>
                        <p>شكراً لتواصلك معنا!</p>
                    </div>
                    
                    <div class='content'>
                        <p style='font-size: 18px; color: #1e293b;'>مرحباً <strong>$name</strong>،</p>
                        
                        <p>تم استلام تذكرة الدعم الفني الخاصة بك وسيتم الرد عليك في أقرب وقت ممكن.</p>
                        
                        <div class='ticket-box'>
                            <div class='ticket-id'>$ticket_id</div>
                            <div class='info-row'>
                                <span class='label'>الموضوع:</span>
                                <span class='value'>$subject</span>
                            </div>
                            <div class='info-row'>
                                <span class='label'>الأولوية:</span>
                                <span class='value'>" . ($priority === 'high' ? '🔴 عالية' : ($priority === 'medium' ? '🟡 متوسطة' : '🟢 منخفضة')) . "</span>
                            </div>
                            <div class='info-row'>
                                <span class='label'>التاريخ:</span>
                                <span class='value'>" . date('Y-m-d H:i') . "</span>
                            </div>
                        </div>
                        
                        <div class='message-box'>
                            <strong>رسالتك:</strong>
                            <p style='margin-top: 10px;'>$message</p>
                        </div>
                        
                        <div style='background: #eff6ff; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                            <strong>⏱️ وقت الاستجابة المتوقع:</strong>
                            <ul style='margin: 10px 0; padding-right: 20px;'>
                                <li>الأولوية العالية: خلال 2-4 ساعات</li>
                                <li>الأولوية المتوسطة: خلال 12-24 ساعة</li>
                                <li>الأولوية المنخفضة: خلال 1-3 أيام</li>
                            </ul>
                        </div>
                        
                        <center>
                            <a href='https://yourdomain.com/platform/track_ticket.php?id=$ticket_id' class='btn'>
                                تتبع التذكرة
                            </a>
                        </center>
                    </div>
                    
                    <div class='footer'>
                        <p><strong>" . SITE_NAME . "</strong></p>
                        <p>📧 " . SUPPORT_EMAIL . " | 📱 +967 123 456 789</p>
                        <p style='margin-top: 10px; font-size: 12px; color: #94a3b8;'>
                            هذه رسالة تلقائية، يرجى عدم الرد عليها مباشرة.
                        </p>
                    </div>
                </div>
            </body>
            </html>
            ";
            
            // رؤوس البريد الإلكتروني
            $headers = "MIME-Version: 1.0\r\n";
            $headers .= "Content-type: text/html; charset=UTF-8\r\n";
            $headers .= "From: " . SITE_NAME . " <" . SUPPORT_EMAIL . ">\r\n";
            $headers .= "Reply-To: " . SUPPORT_EMAIL . "\r\n";
            
            // إرسال بريد للمستخدم
            mail($email, $user_subject, $user_message, $headers);
            
            // إرسال بريد للإدارة
            $admin_subject = "تذكرة دعم جديدة - $ticket_id";
            $admin_message = "
            <html dir='rtl'>
            <head><meta charset='UTF-8'></head>
            <body style='font-family: Arial, sans-serif;'>
                <h2 style='color: #6366f1;'>🎫 تذكرة دعم جديدة</h2>
                <div style='background: #f3f4f6; padding: 20px; border-radius: 8px;'>
                    <p><strong>رقم التذكرة:</strong> $ticket_id</p>
                    <p><strong>الأولوية:</strong> $priority</p>
                    <p><strong>الفئة:</strong> $category</p>
                    <hr>
                    <p><strong>الاسم:</strong> $name</p>
                    <p><strong>البريد:</strong> $email</p>
                    <p><strong>الهاتف:</strong> $phone</p>
                    <p><strong>الموضوع:</strong> $subject</p>
                    <hr>
                    <p><strong>الرسالة:</strong></p>
                    <div style='background: white; padding: 15px; border-radius: 4px;'>$message</div>
                    <hr>
                    <a href='http://localhost/Ibdaa-Taiz/Manager/dashboards/technical/support.php' 
                       style='display: inline-block; background: #6366f1; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; margin-top: 15px;'>
                        عرض في لوحة التحكم
                    </a>
                </div>
            </body>
            </html>
            ";
            
            mail(ADMIN_EMAIL, $admin_subject, $admin_message, $headers);
            
            $success = true;
        } else {
            $error = 'حدث خطأ أثناء حفظ التذكرة';
        }
        
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الدعم الفني - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .animate-fade-in { animation: fadeIn 0.5s ease-in; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        .success-animation { animation: scaleIn 0.6s cubic-bezier(0.68, -0.55, 0.265, 1.55); }
        @keyframes scaleIn { from { transform: scale(0); } to { transform: scale(1); } }
    </style>
</head>
<body class="bg-gradient-to-br from-indigo-50 via-blue-50 to-purple-50 min-h-screen">

    <!-- الهيدر -->
    <header class="bg-white shadow-md sticky top-0 z-50">
        <nav class="container mx-auto px-6 py-4 flex justify-between items-center">
            <a href="index.html" class="flex items-center gap-3 font-bold text-2xl text-indigo-700 hover:text-indigo-900 transition">
                <img src="photos/Sh.jpg" alt="شعار منصة إبداع" class="h-12 w-12 rounded-full shadow-md">
                <span>منصة إبداع</span>
            </a>
            <a href="index.html" class="bg-indigo-600 text-white px-5 py-2 rounded-lg shadow hover:bg-indigo-700 transition">
                <i data-lucide="home" class="inline w-4 h-4"></i>
                الرئيسية
            </a>
        </nav>
    </header>

    <main class="container mx-auto px-6 py-12">
        
        <?php if ($success): ?>
            <!-- رسالة النجاح -->
            <div class="max-w-2xl mx-auto text-center animate-fade-in">
                <div class="bg-white rounded-3xl shadow-2xl p-12">
                    <div class="success-animation bg-green-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="check-circle" class="w-12 h-12 text-green-600"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">تم إرسال تذكرتك بنجاح! ✅</h2>
                    <div class="bg-indigo-50 rounded-xl p-6 mb-6">
                        <p class="text-lg text-gray-700 mb-2">رقم التذكرة:</p>
                        <p class="text-3xl font-bold text-indigo-600"><?php echo htmlspecialchars($ticket_id); ?></p>
                    </div>
                    <p class="text-gray-600 mb-6">
                        تم إرسال تأكيد إلى بريدك الإلكتروني مع تفاصيل التذكرة.<br>
                        سيتم الرد عليك في أقرب وقت ممكن.
                    </p>
                    <div class="flex gap-4 justify-center">
                        <a href="track_ticket.php?id=<?php echo urlencode($ticket_id); ?>" 
                           class="bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition shadow-lg">
                            <i data-lucide="search" class="inline w-5 h-5"></i>
                            تتبع التذكرة
                        </a>
                        <a href="support.php" 
                           class="bg-gray-200 text-gray-800 px-8 py-3 rounded-lg hover:bg-gray-300 transition">
                            تذكرة جديدة
                        </a>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- نموذج الدعم -->
            <div class="max-w-4xl mx-auto">
                <div class="text-center mb-10 animate-fade-in">
                    <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i data-lucide="headphones" class="w-10 h-10 text-indigo-600"></i>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-800 mb-3">الدعم الفني 🎧</h1>
                    <p class="text-lg text-gray-600">نحن هنا لمساعدتك! أرسل استفسارك وسنرد عليك في أقرب وقت</p>
                </div>

                <?php if ($error): ?>
                    <div class="bg-red-100 border-r-4 border-red-500 text-red-700 p-4 rounded-lg mb-6 animate-fade-in">
                        <div class="flex items-center gap-3">
                            <i data-lucide="alert-circle" class="w-5 h-5"></i>
                            <span><?php echo htmlspecialchars($error); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden">
                    <!-- معلومات إضافية -->
                    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white p-8">
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                            <div class="text-center">
                                <i data-lucide="clock" class="w-8 h-8 mx-auto mb-2"></i>
                                <p class="font-semibold">وقت الاستجابة</p>
                                <p class="text-sm text-indigo-100">2-24 ساعة</p>
                            </div>
                            <div class="text-center">
                                <i data-lucide="mail" class="w-8 h-8 mx-auto mb-2"></i>
                                <p class="font-semibold">تأكيد فوري</p>
                                <p class="text-sm text-indigo-100">عبر البريد الإلكتروني</p>
                            </div>
                            <div class="text-center">
                                <i data-lucide="shield-check" class="w-8 h-8 mx-auto mb-2"></i>
                                <p class="font-semibold">دعم احترافي</p>
                                <p class="text-sm text-indigo-100">فريق متخصص</p>
                            </div>
                        </div>
                    </div>

                    <!-- النموذج -->
                    <form method="POST" action="" class="p-8 space-y-6">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- الاسم -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="user" class="inline w-4 h-4 text-indigo-600"></i>
                                    الاسم الكامل *
                                </label>
                                <input type="text" name="name" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="أدخل اسمك الكامل">
                            </div>

                            <!-- البريد الإلكتروني -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="mail" class="inline w-4 h-4 text-indigo-600"></i>
                                    البريد الإلكتروني *
                                </label>
                                <input type="email" name="email" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="example@email.com">
                            </div>

                            <!-- رقم الهاتف -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="phone" class="inline w-4 h-4 text-indigo-600"></i>
                                    رقم الهاتف
                                </label>
                                <input type="tel" name="phone" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="+967 XXX XXX XXX">
                            </div>

                            <!-- الفئة -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="folder" class="inline w-4 h-4 text-indigo-600"></i>
                                    الفئة
                                </label>
                                <select name="category" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="general">استفسار عام</option>
                                    <option value="technical">مشكلة تقنية</option>
                                    <option value="courses">الدورات التدريبية</option>
                                    <option value="registration">التسجيل</option>
                                    <option value="payment">الدفع والفواتير</option>
                                    <option value="certificate">الشهادات</option>
                                    <option value="other">أخرى</option>
                                </select>
                            </div>

                            <!-- الأولوية -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="alert-triangle" class="inline w-4 h-4 text-indigo-600"></i>
                                    الأولوية
                                </label>
                                <select name="priority" 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition">
                                    <option value="low">منخفضة (1-3 أيام)</option>
                                    <option value="medium" selected>متوسطة (12-24 ساعة)</option>
                                    <option value="high">عالية (2-4 ساعات)</option>
                                </select>
                            </div>

                            <!-- الموضوع -->
                            <div>
                                <label class="block text-gray-700 font-semibold mb-2">
                                    <i data-lucide="file-text" class="inline w-4 h-4 text-indigo-600"></i>
                                    الموضوع *
                                </label>
                                <input type="text" name="subject" required 
                                    class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition"
                                    placeholder="عنوان موجز للمشكلة">
                            </div>
                        </div>

                        <!-- الرسالة -->
                        <div>
                            <label class="block text-gray-700 font-semibold mb-2">
                                <i data-lucide="message-square" class="inline w-4 h-4 text-indigo-600"></i>
                                وصف المشكلة أو الاستفسار *
                            </label>
                            <textarea name="message" required rows="6"
                                class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition resize-none"
                                placeholder="اشرح مشكلتك أو استفسارك بالتفصيل..."></textarea>
                        </div>

                        <!-- ملاحظة -->
                        <div class="bg-blue-50 border-r-4 border-blue-500 p-4 rounded-lg">
                            <p class="text-sm text-blue-800">
                                <i data-lucide="info" class="inline w-4 h-4"></i>
                                <strong>ملاحظة:</strong> سيتم إرسال رقم التذكرة وتفاصيل الطلب إلى بريدك الإلكتروني.
                            </p>
                        </div>

                        <!-- زر الإرسال -->
                        <button type="submit" name="submit_ticket"
                            class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 text-white py-4 rounded-lg hover:from-indigo-700 hover:to-blue-700 transition transform hover:scale-[1.02] shadow-xl font-bold text-lg">
                            <i data-lucide="send" class="inline w-5 h-5"></i>
                            إرسال التذكرة
                        </button>
                    </form>
                </div>

                <!-- قسم المساعدة السريعة -->
                <div class="mt-12 grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div class="bg-white rounded-xl p-6 shadow-lg text-center hover:shadow-2xl transition">
                        <i data-lucide="book-open" class="w-12 h-12 text-indigo-600 mx-auto mb-3"></i>
                        <h3 class="font-bold text-lg mb-2">قاعدة المعرفة</h3>
                        <p class="text-sm text-gray-600 mb-4">ابحث عن إجابات فورية</p>
                        <a href="#" class="text-indigo-600 hover:underline">تصفح المقالات</a>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-lg text-center hover:shadow-2xl transition">
                        <i data-lucide="message-circle" class="w-12 h-12 text-indigo-600 mx-auto mb-3"></i>
                        <h3 class="font-bold text-lg mb-2">الدردشة المباشرة</h3>
                        <p class="text-sm text-gray-600 mb-4">تحدث مع عبدالله</p>
                        <a href="index.html" class="text-indigo-600 hover:underline">ابدأ الدردشة</a>
                    </div>
                    <div class="bg-white rounded-xl p-6 shadow-lg text-center hover:shadow-2xl transition">
                        <i data-lucide="search" class="w-12 h-12 text-indigo-600 mx-auto mb-3"></i>
                        <h3 class="font-bold text-lg mb-2">تتبع التذكرة</h3>
                        <p class="text-sm text-gray-600 mb-4">تابع حالة طلبك</p>
                        <a href="track_ticket.php" class="text-indigo-600 hover:underline">تتبع الآن</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- الفوتر -->
    <footer class="bg-gray-900 text-white py-8 mt-20">
        <div class="container mx-auto px-6 text-center">
            <img src="photos/Sh.jpg" alt="شعار إبداع" class="h-16 w-16 rounded-full mx-auto mb-4 border-2 border-white">
            <p class="text-lg font-semibold mb-2">منصة إبداع للتدريب والتأهيل</p>
            <p class="text-gray-400">📧 <?php echo SUPPORT_EMAIL; ?> | 📱 +967 123 456 789</p>
            <p class="text-gray-500 text-sm mt-4">© 2025 جميع الحقوق محفوظة</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
