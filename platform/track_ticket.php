<?php
/**
 * صفحة تتبع تذاكر الدعم
 */
require_once 'db.php';

$ticket = null;
$responses = [];
$error = '';

if (isset($_GET['id'])) {
    $ticket_id = trim($_GET['id']);
    
    $stmt = $conn->prepare("SELECT * FROM support_tickets WHERE ticket_id = ?");
    $stmt->bind_param("s", $ticket_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $ticket = $result->fetch_assoc();
        
        // جلب الردود
        $stmt2 = $conn->prepare("SELECT * FROM support_responses WHERE ticket_id = ? ORDER BY created_at ASC");
        $stmt2->bind_param("s", $ticket_id);
        $stmt2->execute();
        $responses = $stmt2->get_result()->fetch_all(MYSQLI_ASSOC);
        $stmt2->close();
    } else {
        $error = 'لم يتم العثور على التذكرة';
    }
    
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تتبع التذكرة - منصة إبداع</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .timeline-item::before {
            content: '';
            position: absolute;
            right: 19px;
            top: 30px;
            bottom: -30px;
            width: 2px;
            background: #e5e7eb;
        }
        .timeline-item:last-child::before {
            display: none;
        }
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
            <a href="support.php" class="bg-indigo-600 text-white px-5 py-2 rounded-lg shadow hover:bg-indigo-700 transition">
                تذكرة جديدة
            </a>
        </nav>
    </header>

    <main class="container mx-auto px-6 py-12">
        
        <?php if (!isset($_GET['id'])): ?>
            <!-- نموذج البحث -->
            <div class="max-w-2xl mx-auto">
                <div class="text-center mb-10">
                    <div class="bg-white w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-4 shadow-lg">
                        <i data-lucide="search" class="w-10 h-10 text-indigo-600"></i>
                    </div>
                    <h1 class="text-4xl font-bold text-gray-800 mb-3">تتبع التذكرة 🔍</h1>
                    <p class="text-lg text-gray-600">أدخل رقم التذكرة لمتابعة حالة طلبك</p>
                </div>

                <div class="bg-white rounded-3xl shadow-2xl p-8">
                    <form method="GET" action="">
                        <div class="mb-6">
                            <label class="block text-gray-700 font-semibold mb-3 text-lg">
                                <i data-lucide="ticket" class="inline w-5 h-5 text-indigo-600"></i>
                                رقم التذكرة
                            </label>
                            <input type="text" name="id" required 
                                placeholder="TKT-20250113-XXXXXX"
                                class="w-full px-6 py-4 border-2 border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition text-lg">
                            <p class="text-sm text-gray-500 mt-2">
                                <i data-lucide="info" class="inline w-4 h-4"></i>
                                يمكنك إيجاد رقم التذكرة في البريد الإلكتروني المرسل إليك
                            </p>
                        </div>
                        <button type="submit" 
                            class="w-full bg-gradient-to-r from-indigo-600 to-blue-600 text-white py-4 rounded-xl hover:from-indigo-700 hover:to-blue-700 transition transform hover:scale-[1.02] shadow-xl font-bold text-lg">
                            <i data-lucide="search" class="inline w-5 h-5"></i>
                            بحث عن التذكرة
                        </button>
                    </form>
                </div>
            </div>
            
        <?php elseif ($error): ?>
            <!-- خطأ -->
            <div class="max-w-2xl mx-auto text-center">
                <div class="bg-white rounded-3xl shadow-2xl p-12">
                    <div class="bg-red-100 w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-6">
                        <i data-lucide="x-circle" class="w-12 h-12 text-red-600"></i>
                    </div>
                    <h2 class="text-3xl font-bold text-gray-800 mb-4">التذكرة غير موجودة</h2>
                    <p class="text-gray-600 mb-6">لم نتمكن من إيجاد التذكرة المطلوبة. تحقق من الرقم وحاول مرة أخرى.</p>
                    <a href="track_ticket.php" class="inline-block bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition">
                        بحث مرة أخرى
                    </a>
                </div>
            </div>
            
        <?php else: ?>
            <!-- عرض التذكرة -->
            <div class="max-w-5xl mx-auto">
                
                <!-- معلومات التذكرة -->
                <div class="bg-white rounded-3xl shadow-2xl overflow-hidden mb-8">
                    <!-- الهيدر -->
                    <div class="bg-gradient-to-r from-indigo-600 to-blue-600 text-white p-8">
                        <div class="flex items-center justify-between mb-4">
                            <div>
                                <p class="text-indigo-100 text-sm mb-2">رقم التذكرة</p>
                                <h1 class="text-3xl font-bold"><?php echo htmlspecialchars($ticket['ticket_id']); ?></h1>
                            </div>
                            <div class="text-left">
                                <?php
                                $status_colors = [
                                    'pending' => 'bg-yellow-400 text-yellow-900',
                                    'in-progress' => 'bg-blue-400 text-blue-900',
                                    'resolved' => 'bg-green-400 text-green-900',
                                    'closed' => 'bg-gray-400 text-gray-900'
                                ];
                                $status_text = [
                                    'pending' => '⏳ معلقة',
                                    'in-progress' => '🔄 قيد المعالجة',
                                    'resolved' => '✅ محلولة',
                                    'closed' => '🔒 مغلقة'
                                ];
                                $status_class = $status_colors[$ticket['status']] ?? 'bg-gray-400 text-gray-900';
                                ?>
                                <span class="<?php echo $status_class; ?> px-6 py-3 rounded-full font-bold text-lg inline-block">
                                    <?php echo $status_text[$ticket['status']] ?? $ticket['status']; ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mt-6">
                            <div>
                                <p class="text-indigo-100 text-sm">الأولوية</p>
                                <?php
                                $priority_colors = [
                                    'low' => '🟢 منخفضة',
                                    'medium' => '🟡 متوسطة',
                                    'high' => '🔴 عالية'
                                ];
                                ?>
                                <p class="font-semibold text-lg"><?php echo $priority_colors[$ticket['priority']] ?? $ticket['priority']; ?></p>
                            </div>
                            <div>
                                <p class="text-indigo-100 text-sm">تاريخ الإنشاء</p>
                                <p class="font-semibold text-lg"><?php echo date('Y-m-d H:i', strtotime($ticket['created_at'])); ?></p>
                            </div>
                            <div>
                                <p class="text-indigo-100 text-sm">آخر تحديث</p>
                                <p class="font-semibold text-lg"><?php echo date('Y-m-d H:i', strtotime($ticket['updated_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- التفاصيل -->
                    <div class="p-8">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                            <div>
                                <p class="text-gray-600 text-sm mb-1">الاسم</p>
                                <p class="font-semibold text-lg"><?php echo htmlspecialchars($ticket['name']); ?></p>
                            </div>
                            <div>
                                <p class="text-gray-600 text-sm mb-1">البريد الإلكتروني</p>
                                <p class="font-semibold text-lg"><?php echo htmlspecialchars($ticket['email']); ?></p>
                            </div>
                            <?php if ($ticket['phone']): ?>
                            <div>
                                <p class="text-gray-600 text-sm mb-1">رقم الهاتف</p>
                                <p class="font-semibold text-lg"><?php echo htmlspecialchars($ticket['phone']); ?></p>
                            </div>
                            <?php endif; ?>
                            <div>
                                <p class="text-gray-600 text-sm mb-1">الفئة</p>
                                <p class="font-semibold text-lg"><?php echo htmlspecialchars($ticket['category']); ?></p>
                            </div>
                        </div>
                        
                        <div class="mb-6">
                            <p class="text-gray-600 text-sm mb-2">الموضوع</p>
                            <p class="font-bold text-xl text-gray-800"><?php echo htmlspecialchars($ticket['subject']); ?></p>
                        </div>
                        
                        <div class="bg-gray-50 rounded-xl p-6">
                            <p class="text-gray-600 text-sm mb-2 font-semibold">الرسالة:</p>
                            <p class="text-gray-800 leading-relaxed whitespace-pre-wrap"><?php echo htmlspecialchars($ticket['message']); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- الردود والمحادثات -->
                <?php if (count($responses) > 0): ?>
                <div class="bg-white rounded-3xl shadow-2xl p-8">
                    <h2 class="text-2xl font-bold text-gray-800 mb-6 flex items-center gap-3">
                        <i data-lucide="message-circle" class="w-7 h-7 text-indigo-600"></i>
                        المحادثات والردود
                    </h2>
                    
                    <div class="space-y-6">
                        <?php foreach ($responses as $index => $response): ?>
                        <div class="relative timeline-item">
                            <div class="flex gap-4">
                                <!-- الأيقونة -->
                                <div class="flex-shrink-0">
                                    <?php if ($response['user_type'] === 'customer'): ?>
                                    <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="user" class="w-5 h-5 text-blue-600"></i>
                                    </div>
                                    <?php else: ?>
                                    <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center">
                                        <i data-lucide="headphones" class="w-5 h-5 text-indigo-600"></i>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <!-- المحتوى -->
                                <div class="flex-1 <?php echo $response['user_type'] === 'customer' ? 'bg-blue-50' : 'bg-indigo-50'; ?> rounded-xl p-5">
                                    <div class="flex items-center justify-between mb-2">
                                        <p class="font-bold text-gray-800">
                                            <?php echo htmlspecialchars($response['user_name']); ?>
                                            <span class="text-sm font-normal text-gray-500 mr-2">
                                                (<?php echo $response['user_type'] === 'customer' ? 'العميل' : 'الدعم الفني'; ?>)
                                            </span>
                                        </p>
                                        <p class="text-sm text-gray-500">
                                            <?php echo date('Y-m-d H:i', strtotime($response['created_at'])); ?>
                                        </p>
                                    </div>
                                    <p class="text-gray-700 leading-relaxed whitespace-pre-wrap">
                                        <?php echo htmlspecialchars($response['message']); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- إجراءات -->
                <div class="mt-8 flex gap-4 justify-center">
                    <a href="support.php" class="bg-indigo-600 text-white px-8 py-3 rounded-lg hover:bg-indigo-700 transition shadow-lg">
                        تذكرة جديدة
                    </a>
                    <a href="track_ticket.php" class="bg-gray-200 text-gray-800 px-8 py-3 rounded-lg hover:bg-gray-300 transition">
                        تتبع تذكرة أخرى
                    </a>
                </div>
            </div>
        <?php endif; ?>

    </main>

    <!-- الفوتر -->
    <footer class="bg-gray-900 text-white py-8 mt-20">
        <div class="container mx-auto px-6 text-center">
            <img src="photos/Sh.jpg" alt="شعار إبداع" class="h-16 w-16 rounded-full mx-auto mb-4 border-2 border-white">
            <p class="text-lg font-semibold mb-2">منصة إبداع للتدريب والتأهيل</p>
            <p class="text-gray-400">📧 support@ibdaa-platform.com | 📱 +967 123 456 789</p>
            <p class="text-gray-500 text-sm mt-4">© 2025 جميع الحقوق محفوظة</p>
        </div>
    </footer>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
