<?php
/**
 * لوحة المدير المالي - تأكيد الدفعات
 * Finance Manager - Payment Verification
 */

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../platform/db.php';

SessionSecurity::requireLogin();
SessionSecurity::requireRole(['manager']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];

// جلب الطلبات المقبولة بانتظار الدفع
$pending_payment_query = "SELECT * FROM approved_pending_payment ORDER BY days_since_approval DESC";
$pending_payment_result = $conn->query($pending_payment_query);

// إحصائيات مالية
$finance_stats_query = "
    SELECT 
        COUNT(*) as pending_payments,
        SUM(c.price) as expected_revenue
    FROM applications a
    JOIN courses c ON c.id = a.course_id
    WHERE a.status = 'approved' AND a.payment_status != 'completed'
";
$finance_stats = $conn->query($finance_stats_query)->fetch_assoc();

$completed_query = "
    SELECT COUNT(*) as completed_count, SUM(c.price) as total_revenue
    FROM applications a
    JOIN courses c ON c.id = a.course_id
    WHERE a.status = 'approved' AND a.payment_status = 'completed'
    AND DATE(a.updated_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$completed_stats = $conn->query($completed_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المدفوعات - المدير المالي</title>
    <?php echo CSRF::getMetaTag(); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <nav class="bg-gradient-to-r from-green-600 to-teal-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold">💰 لوحة المدير المالي</h1>
                </div>
                <div class="flex items-center gap-4">
                    <span>مرحباً، <?php echo htmlspecialchars($user_name); ?></span>
                    <a href="../platform/logout.php" class="bg-white/20 hover:bg-white/30 px-4 py-2 rounded-lg transition">
                        تسجيل الخروج
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Finance Statistics -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">بانتظار الدفع</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $finance_stats['pending_payments']; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">الإيرادات المتوقعة</p>
                        <p class="text-2xl font-bold text-blue-600"><?php echo number_format($finance_stats['expected_revenue'] ?? 0, 0); ?> ريال</p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">المحصل (آخر 30 يوم)</p>
                        <p class="text-2xl font-bold text-green-600"><?php echo number_format($completed_stats['total_revenue'] ?? 0, 0); ?> ريال</p>
                        <p class="text-xs text-gray-500 mt-1"><?php echo $completed_stats['completed_count']; ?> دفعة</p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Payments Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-green-600 to-teal-600 text-white">
                <h2 class="text-xl font-bold">الطلبات المقبولة - بانتظار تأكيد الدفع</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم الطلب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الطالب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاتصال</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الدورة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">المبلغ المطلوب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">حالة الدفع</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">تمت الموافقة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($pending_payment_result->num_rows > 0): ?>
                            <?php while ($payment = $pending_payment_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-indigo-600">#<?php echo $payment['application_id']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['full_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900">📧 <?php echo htmlspecialchars($payment['email']); ?></div>
                                        <div class="text-sm text-gray-500">📱 <?php echo htmlspecialchars($payment['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($payment['course_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-lg font-bold text-green-600"><?php echo number_format($payment['course_price'], 0); ?> ريال</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <?php if ($payment['payment_status'] == 'pending'): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                                لم يتم الدفع
                                            </span>
                                        <?php elseif ($payment['payment_status'] == 'partial'): ?>
                                            <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                                دفع جزئي
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                        منذ <?php echo $payment['days_since_approval']; ?> يوم
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="confirmPayment(<?php echo $payment['application_id']; ?>, '<?php echo htmlspecialchars($payment['full_name']); ?>', <?php echo $payment['course_price']; ?>)" 
                                                class="bg-green-600 text-white px-4 py-2 rounded-lg hover:bg-green-700">
                                            تأكيد الدفع
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                    </svg>
                                    <p class="mt-2 text-lg font-medium">لا توجد دفعات معلقة</p>
                                    <p class="text-sm">جميع الدفعات تم تأكيدها ✅</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: Confirm Payment -->
    <div id="paymentModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full mx-4">
            <div class="p-6 border-b bg-green-50">
                <h3 class="text-xl font-bold text-green-800">تأكيد استلام الدفع</h3>
            </div>
            <form id="paymentForm">
                <?php echo CSRF::getTokenField(); ?>
                <input type="hidden" name="application_id" id="payment_app_id">
                <div class="p-6 space-y-4">
                    <div>
                        <p class="mb-2"><strong>الطالب:</strong> <span id="student_name"></span></p>
                        <p><strong>المبلغ:</strong> <span id="payment_amount" class="text-green-600 font-bold"></span> ريال</p>
                    </div>
                    
                    <div>
                        <label class="block mb-2 font-bold">رقم الإيصال <span class="text-red-500">*</span>:</label>
                        <input type="text" name="receipt_number" required class="w-full border rounded-lg p-2" placeholder="مثال: REC-001">
                    </div>
                    
                    <div>
                        <label class="block mb-2 font-bold">طريقة الدفع <span class="text-red-500">*</span>:</label>
                        <select name="payment_method" required class="w-full border rounded-lg p-2">
                            <option value="cash">نقداً</option>
                            <option value="bank_transfer">تحويل بنكي</option>
                            <option value="mobile_money">محفظة إلكترونية</option>
                            <option value="other">أخرى</option>
                        </select>
                    </div>
                    
                    <div>
                        <label class="block mb-2 font-bold">تاريخ الدفع <span class="text-red-500">*</span>:</label>
                        <input type="date" name="payment_date" required value="<?php echo date('Y-m-d'); ?>" class="w-full border rounded-lg p-2">
                    </div>
                    
                    <div>
                        <label class="block mb-2 font-bold">ملاحظات (اختياري):</label>
                        <textarea name="notes" rows="2" class="w-full border rounded-lg p-2"></textarea>
                    </div>
                </div>
                <div class="p-6 border-t flex justify-end gap-4">
                    <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">إلغاء</button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">✓ تأكيد الدفع</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function confirmPayment(appId, studentName, amount) {
            document.getElementById('payment_app_id').value = appId;
            document.getElementById('student_name').textContent = studentName;
            document.getElementById('payment_amount').textContent = amount.toLocaleString();
            document.getElementById('paymentModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('paymentModal').classList.add('hidden');
        }

        document.getElementById('paymentForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/confirm_payment.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('✅ تم تأكيد الدفع بنجاح!\n\nسيتم:\n• تفعيل حساب الطالب تلقائياً\n• إرسال بيانات الدخول بالبريد\n• إنشاء سجل انضمام للدورة');
                    location.reload();
                } else {
                    alert('خطأ: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
