<?php
/**
 * لوحة المشرف الفني - مراجعة الطلبات
 * Technical Supervisor - Review Applications
 */

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../platform/db.php';

// التحقق من تسجيل الدخول والصلاحية
SessionSecurity::requireLogin();
SessionSecurity::requireRole(['technical', 'manager']);

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['full_name'];
$user_role = $_SESSION['role'];

// جلب الطلبات المعلقة
$pending_query = "SELECT * FROM pending_applications ORDER BY days_pending DESC";
$pending_result = $conn->query($pending_query);

// إحصائيات
$stats_query = "
    SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
        SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved_count,
        SUM(CASE WHEN status = 'rejected' THEN 1 ELSE 0 END) as rejected_count,
        SUM(CASE WHEN status = 'approved' AND payment_status = 'completed' THEN 1 ELSE 0 END) as completed_count
    FROM applications
    WHERE DATE(created_at) >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
";
$stats = $conn->query($stats_query)->fetch_assoc();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>مراجعة الطلبات - المشرف الفني</title>
    <?php echo CSRF::getMetaTag(); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Cairo', sans-serif; }
        .card { transition: transform 0.2s; }
        .card:hover { transform: translateY(-2px); }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Header -->
    <nav class="bg-gradient-to-r from-indigo-600 to-purple-600 text-white shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <h1 class="text-xl font-bold">لوحة المشرف الفني</h1>
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
        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-5 gap-6 mb-8">
            <div class="card bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">إجمالي الطلبات</p>
                        <p class="text-3xl font-bold text-gray-800"><?php echo $stats['total']; ?></p>
                    </div>
                    <div class="bg-blue-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">معلقة</p>
                        <p class="text-3xl font-bold text-yellow-600"><?php echo $stats['pending_count']; ?></p>
                    </div>
                    <div class="bg-yellow-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">مقبولة</p>
                        <p class="text-3xl font-bold text-green-600"><?php echo $stats['approved_count']; ?></p>
                    </div>
                    <div class="bg-green-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">مرفوضة</p>
                        <p class="text-3xl font-bold text-red-600"><?php echo $stats['rejected_count']; ?></p>
                    </div>
                    <div class="bg-red-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                </div>
            </div>

            <div class="card bg-white rounded-lg shadow-md p-6">
                <div class="flex items-center justify-between">
                    <div>
                        <p class="text-gray-500 text-sm">مكتملة</p>
                        <p class="text-3xl font-bold text-purple-600"><?php echo $stats['completed_count']; ?></p>
                    </div>
                    <div class="bg-purple-100 p-3 rounded-full">
                        <svg class="w-8 h-8 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pending Applications Table -->
        <div class="bg-white rounded-lg shadow-md overflow-hidden">
            <div class="px-6 py-4 bg-gradient-to-r from-indigo-600 to-purple-600 text-white">
                <h2 class="text-xl font-bold">الطلبات المعلقة - بانتظار المراجعة</h2>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">رقم الطلب</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الاسم</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">البريد/الهاتف</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">العنوان</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الدورة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">السعر</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الحالة</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php if ($pending_result->num_rows > 0): ?>
                            <?php while ($app = $pending_result->fetch_assoc()): ?>
                                <tr class="hover:bg-gray-50">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-indigo-600">#<?php echo $app['application_id']; ?></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($app['full_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($app['email']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($app['phone']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm text-gray-900"><?php echo htmlspecialchars($app['governorate']); ?></div>
                                        <div class="text-sm text-gray-500"><?php echo htmlspecialchars($app['district']); ?></div>
                                    </td>
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($app['course_name']); ?></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="text-sm font-bold text-green-600"><?php echo number_format($app['course_price'], 0); ?> ريال</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="px-2 py-1 inline-flex text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800">
                                            معلق منذ <?php echo $app['days_pending']; ?> يوم
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                        <button onclick="viewApplication(<?php echo $app['application_id']; ?>)" 
                                                class="text-indigo-600 hover:text-indigo-900 mr-3">
                                            عرض
                                        </button>
                                        <button onclick="approveApplication(<?php echo $app['application_id']; ?>)" 
                                                class="text-green-600 hover:text-green-900 mr-3">
                                            قبول
                                        </button>
                                        <button onclick="rejectApplication(<?php echo $app['application_id']; ?>)" 
                                                class="text-red-600 hover:text-red-900">
                                            رفض
                                        </button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="px-6 py-12 text-center text-gray-500">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
                                    </svg>
                                    <p class="mt-2 text-lg font-medium">لا توجد طلبات معلقة</p>
                                    <p class="text-sm">جميع الطلبات تمت مراجعتها ✅</p>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Modal: View Application -->
    <div id="viewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-4xl w-full mx-4 max-h-[90vh] overflow-y-auto">
            <div class="p-6 border-b">
                <h3 class="text-2xl font-bold">تفاصيل الطلب</h3>
            </div>
            <div id="modalContent" class="p-6"></div>
            <div class="p-6 border-t flex justify-end gap-4">
                <button onclick="closeModal()" class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">إغلاق</button>
            </div>
        </div>
    </div>

    <!-- Modal: Approve -->
    <div id="approveModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full mx-4">
            <div class="p-6 border-b bg-green-50">
                <h3 class="text-xl font-bold text-green-800">قبول الطلب</h3>
            </div>
            <form id="approveForm">
                <?php echo CSRF::getTokenField(); ?>
                <input type="hidden" name="application_id" id="approve_app_id">
                <div class="p-6">
                    <p class="mb-4">هل أنت متأكد من قبول هذا الطلب؟</p>
                    <label class="block mb-2 font-bold">ملاحظات (اختياري):</label>
                    <textarea name="review_notes" rows="3" class="w-full border rounded-lg p-2"></textarea>
                </div>
                <div class="p-6 border-t flex justify-end gap-4">
                    <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">إلغاء</button>
                    <button type="submit" class="px-6 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700">تأكيد القبول</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Reject -->
    <div id="rejectModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg max-w-md w-full mx-4">
            <div class="p-6 border-b bg-red-50">
                <h3 class="text-xl font-bold text-red-800">رفض الطلب</h3>
            </div>
            <form id="rejectForm">
                <?php echo CSRF::getTokenField(); ?>
                <input type="hidden" name="application_id" id="reject_app_id">
                <div class="p-6">
                    <label class="block mb-2 font-bold">سبب الرفض <span class="text-red-500">*</span>:</label>
                    <textarea name="rejection_reason" rows="4" required class="w-full border rounded-lg p-2" placeholder="يرجى كتابة سبب الرفض..."></textarea>
                </div>
                <div class="p-6 border-t flex justify-end gap-4">
                    <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-300 rounded-lg hover:bg-gray-400">إلغاء</button>
                    <button type="submit" class="px-6 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700">تأكيد الرفض</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function viewApplication(id) {
            fetch(`api/get_application.php?id=${id}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const app = data.application;
                        document.getElementById('modalContent').innerHTML = `
                            <div class="grid grid-cols-2 gap-6">
                                <div>
                                    <h4 class="font-bold text-lg mb-4">المعلومات الشخصية</h4>
                                    <p><strong>الاسم:</strong> ${app.full_name}</p>
                                    <p><strong>البريد:</strong> ${app.email}</p>
                                    <p><strong>الهاتف:</strong> ${app.phone}</p>
                                    <p><strong>تاريخ الميلاد:</strong> ${app.birth_date || 'غير محدد'}</p>
                                    <p><strong>المحافظة:</strong> ${app.governorate}</p>
                                    <p><strong>المديرية:</strong> ${app.district}</p>
                                </div>
                                <div>
                                    <h4 class="font-bold text-lg mb-4">معلومات الدورة</h4>
                                    <p><strong>الدورة:</strong> ${app.course_name}</p>
                                    <p><strong>تاريخ التقديم:</strong> ${app.created_at}</p>
                                    ${app.notes ? `<p><strong>ملاحظات:</strong><br>${app.notes}</p>` : ''}
                                </div>
                                <div class="col-span-2">
                                    <h4 class="font-bold text-lg mb-4">المستندات</h4>
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="mb-2"><strong>صورة الهوية:</strong></p>
                                            <img src="../platform/${app.id_file_path}" class="w-full border rounded-lg" alt="الهوية">
                                        </div>
                                        <div>
                                            <p class="mb-2"><strong>الصورة الشخصية:</strong></p>
                                            <img src="../platform/${app.photo_path}" class="w-full border rounded-lg" alt="الصورة">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        `;
                        document.getElementById('viewModal').classList.remove('hidden');
                    }
                });
        }

        function approveApplication(id) {
            document.getElementById('approve_app_id').value = id;
            document.getElementById('approveModal').classList.remove('hidden');
        }

        function rejectApplication(id) {
            document.getElementById('reject_app_id').value = id;
            document.getElementById('rejectModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('viewModal').classList.add('hidden');
            document.getElementById('approveModal').classList.add('hidden');
            document.getElementById('rejectModal').classList.add('hidden');
        }

        // Approve Form Submit
        document.getElementById('approveForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/approve_application.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('تم قبول الطلب بنجاح ✅');
                    location.reload();
                } else {
                    alert('خطأ: ' + data.message);
                }
            });
        });

        // Reject Form Submit
        document.getElementById('rejectForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('api/reject_application.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    alert('تم رفض الطلب');
                    location.reload();
                } else {
                    alert('خطأ: ' + data.message);
                }
            });
        });
    </script>
</body>
</html>
