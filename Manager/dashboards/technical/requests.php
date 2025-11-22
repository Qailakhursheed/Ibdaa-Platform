<?php
/**
 * Technical Dashboard - Requests Management System
 * نظام إدارة واستقبال الطلبات للمشرف الفني
 */

// Get requests statistics
$requestsStats = [
    'pending' => 0,
    'approved' => 0,
    'rejected' => 0,
    'total' => 0
];

try {
    // Pending Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM registration_requests WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $requestsStats['pending'] = $result['total'] ?? 0;
    
    // Approved Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM registration_requests WHERE status = 'approved'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $requestsStats['approved'] = $result['total'] ?? 0;
    
    // Rejected Requests
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM registration_requests WHERE status = 'rejected'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $requestsStats['rejected'] = $result['total'] ?? 0;
    
    // Total Requests
    $requestsStats['total'] = $requestsStats['pending'] + $requestsStats['approved'] + $requestsStats['rejected'];
    
} catch(Exception $e) {
    error_log("Requests Stats Error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-purple-500 to-indigo-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="inbox" class="w-10 h-10"></i>
                إدارة الطلبات
            </h1>
            <p class="text-purple-100 text-lg">استقبال ومراجعة طلبات التسجيل في الدورات</p>
        </div>
        <div class="flex gap-3">
            <button onclick="refreshRequests()" class="bg-white text-purple-600 px-6 py-3 rounded-xl font-bold hover:bg-purple-50 transition-all flex items-center gap-2 shadow-lg">
                <i data-lucide="refresh-cw" class="w-5 h-5"></i>
                تحديث
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Pending Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 hover:shadow-xl transition-shadow cursor-pointer" onclick="filterByStatus('pending')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">معلقة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $requestsStats['pending']; ?></h3>
        <p class="text-slate-500 text-sm">طلبات تحتاج مراجعة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-amber-600 font-semibold">انقر للعرض</p>
        </div>
    </div>

    <!-- Approved Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500 hover:shadow-xl transition-shadow cursor-pointer" onclick="filterByStatus('approved')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">موافقة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $requestsStats['approved']; ?></h3>
        <p class="text-slate-500 text-sm">طلبات مقبولة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-emerald-600 font-semibold">انقر للعرض</p>
        </div>
    </div>

    <!-- Rejected Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-red-500 hover:shadow-xl transition-shadow cursor-pointer" onclick="filterByStatus('rejected')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-red-100 flex items-center justify-center">
                <i data-lucide="x-circle" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-sm font-semibold text-red-600 bg-red-50 px-3 py-1 rounded-full">رفض</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $requestsStats['rejected']; ?></h3>
        <p class="text-slate-500 text-sm">طلبات مرفوضة</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-red-600 font-semibold">انقر للعرض</p>
        </div>
    </div>

    <!-- Total Requests -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500 hover:shadow-xl transition-shadow cursor-pointer" onclick="filterByStatus('all')">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="list" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">الكل</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $requestsStats['total']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي الطلبات</p>
        <div class="mt-4 pt-4 border-t border-slate-100">
            <p class="text-xs text-blue-600 font-semibold">انقر للعرض</p>
        </div>
    </div>
</div>

<!-- Requests Chart -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i data-lucide="trending-up" class="w-5 h-5 text-purple-600"></i>
        إحصائيات الطلبات الشهرية
    </h3>
    <div style="height: 300px; position: relative;">
        <canvas id="requestsChart"></canvas>
    </div>
</div>

<!-- Requests Table -->
<div class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex items-center justify-between mb-6">
        <h3 class="text-xl font-bold text-slate-800">جميع الطلبات</h3>
        <div class="flex gap-3">
            <input type="text" id="searchRequests" placeholder="بحث باسم الطالب أو الدورة..." class="border border-slate-300 rounded-lg px-4 py-2 w-80" oninput="searchRequests()">
            <select id="filterStatus" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterByStatus(this.value)">
                <option value="all">كل الحالات</option>
                <option value="pending">معلقة</option>
                <option value="approved">موافقة</option>
                <option value="rejected">مرفوضة</option>
            </select>
            <select id="filterCourse" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterByCourse(this.value)">
                <option value="all">كل الدورات</option>
                <!-- Loaded dynamically -->
            </select>
        </div>
    </div>
    
    <div id="requestsTableContainer" class="overflow-x-auto">
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 mx-auto mb-3 animate-spin text-purple-600"></i>
            <p class="text-slate-500 text-lg">جاري تحميل الطلبات...</p>
        </div>
    </div>
    
    <!-- Pagination -->
    <div id="requestsPagination" class="flex justify-center gap-2 mt-6"></div>
</div>

<!-- View Request Details Modal -->
<div id="viewRequestModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="file-text" class="w-7 h-7"></i>
                    تفاصيل الطلب
                </h3>
                <button onclick="closeViewRequestModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <div id="requestDetailsContent" class="p-6">
            <!-- Loaded dynamically -->
        </div>
    </div>
</div>

<!-- Approve Request Modal -->
<div id="approveRequestModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-emerald-500 to-teal-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="check-circle" class="w-7 h-7"></i>
                    موافقة على الطلب
                </h3>
                <button onclick="closeApproveRequestModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="approveRequestForm" class="p-6 space-y-4">
            <input type="hidden" name="request_id" id="approve_request_id">
            
            <div class="bg-emerald-50 border border-emerald-200 rounded-lg p-4 mb-4">
                <p class="text-emerald-700 text-center">
                    <i data-lucide="info" class="w-5 h-5 inline-block ml-2"></i>
                    هل أنت متأكد من الموافقة على هذا الطلب؟
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">تعيين المدرب (اختياري)</label>
                <select name="trainer_id" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent">
                    <option value="">اختر المدرب</option>
                    <!-- Options loaded dynamically -->
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">ملاحظات</label>
                <textarea name="notes" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-emerald-500 focus:border-transparent" placeholder="أي ملاحظات للطالب..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="check" class="w-5 h-5"></i>
                    تأكيد الموافقة
                </button>
                <button type="button" onclick="closeApproveRequestModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Reject Request Modal -->
<div id="rejectRequestModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-red-500 to-rose-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="x-circle" class="w-7 h-7"></i>
                    رفض الطلب
                </h3>
                <button onclick="closeRejectRequestModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="rejectRequestForm" class="p-6 space-y-4">
            <input type="hidden" name="request_id" id="reject_request_id">
            
            <div class="bg-red-50 border border-red-200 rounded-lg p-4 mb-4">
                <p class="text-red-700 text-center">
                    <i data-lucide="alert-triangle" class="w-5 h-5 inline-block ml-2"></i>
                    هل أنت متأكد من رفض هذا الطلب؟
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">سبب الرفض *</label>
                <select name="rejection_reason" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent">
                    <option value="">اختر السبب</option>
                    <option value="incomplete_documents">مستندات غير مكتملة</option>
                    <option value="course_full">الدورة مكتملة العدد</option>
                    <option value="unqualified">لا يستوفي الشروط</option>
                    <option value="payment_issue">مشكلة في الدفع</option>
                    <option value="duplicate">طلب مكرر</option>
                    <option value="other">سبب آخر</option>
                </select>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">تفاصيل إضافية *</label>
                <textarea name="rejection_details" required rows="4" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-red-500 focus:border-transparent" placeholder="اشرح السبب بالتفصيل للطالب..."></textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="x" class="w-5 h-5"></i>
                    تأكيد الرفض
                </button>
                <button type="button" onclick="closeRejectRequestModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Initialize chart
    initializeRequestsChart();
    
    // Load requests
    loadRequests();
    
    // Load courses for filter
    loadCoursesFilter();
    
    // Load trainers for approve modal
    loadTrainersForApprove();
});

// Initialize Requests Chart
function initializeRequestsChart() {
    const ctx = document.getElementById('requestsChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [
                    {
                        label: 'طلبات جديدة',
                        data: [25, 35, 42, 38, 50, 45, 52, 48, 55, 60, 58, 65],
                        borderColor: '#8b5cf6',
                        backgroundColor: 'rgba(139, 92, 246, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'طلبات موافقة',
                        data: [20, 28, 35, 30, 42, 38, 45, 40, 48, 52, 50, 58],
                        borderColor: '#10b981',
                        backgroundColor: 'rgba(16, 185, 129, 0.1)',
                        tension: 0.4,
                        fill: true
                    },
                    {
                        label: 'طلبات مرفوضة',
                        data: [5, 7, 7, 8, 8, 7, 7, 8, 7, 8, 8, 7],
                        borderColor: '#ef4444',
                        backgroundColor: 'rgba(239, 68, 68, 0.1)',
                        tension: 0.4,
                        fill: true
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        position: 'top',
                        labels: { font: { family: 'Cairo', size: 12 } }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { font: { family: 'Cairo' } }
                    },
                    x: {
                        ticks: { font: { family: 'Cairo' } }
                    }
                }
            }
        });
    }
}

// Load Requests
async function loadRequests(status = 'all', courseId = 'all', searchTerm = '') {
    try {
        let url = '<?php echo $managerBaseUrl; ?>/api/requests.php?action=get_all';
        if (status !== 'all') url += `&status=${status}`;
        if (courseId !== 'all') url += `&course_id=${courseId}`;
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        const container = document.getElementById('requestsTableContainer');
        if (data.success && data.data && data.data.length > 0) {
            container.innerHTML = `
                <table class="w-full">
                    <thead class="bg-slate-50 border-b-2 border-slate-200">
                        <tr>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الطالب</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">البريد الإلكتروني</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الدورة</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">تاريخ الطلب</th>
                            <th class="text-right py-4 px-6 font-bold text-slate-700">الحالة</th>
                            <th class="text-center py-4 px-6 font-bold text-slate-700">إجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${data.data.map(request => `
                            <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-slate-800">${request.full_name}</div>
                                    <div class="text-sm text-slate-500">${request.phone}</div>
                                </td>
                                <td class="py-4 px-6 text-slate-600">${request.email}</td>
                                <td class="py-4 px-6">
                                    <div class="font-semibold text-slate-800">${request.course_title}</div>
                                    <div class="text-sm text-slate-500">${request.category_name || 'غير محدد'}</div>
                                </td>
                                <td class="py-4 px-6 text-slate-600">${formatDate(request.created_at)}</td>
                                <td class="py-4 px-6">
                                    <span class="px-3 py-1 rounded-full text-xs font-bold ${getStatusClass(request.status)}">
                                        ${getStatusLabel(request.status)}
                                    </span>
                                </td>
                                <td class="py-4 px-6">
                                    <div class="flex items-center justify-center gap-2">
                                        <button onclick="viewRequestDetails(${request.request_id})" class="text-blue-600 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-lg transition-all" title="عرض التفاصيل">
                                            <i data-lucide="eye" class="w-5 h-5"></i>
                                        </button>
                                        ${request.status === 'pending' ? `
                                            <button onclick="openApproveModal(${request.request_id})" class="text-emerald-600 hover:text-emerald-700 p-2 hover:bg-emerald-50 rounded-lg transition-all" title="موافقة">
                                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                            </button>
                                            <button onclick="openRejectModal(${request.request_id})" class="text-red-600 hover:text-red-700 p-2 hover:bg-red-50 rounded-lg transition-all" title="رفض">
                                                <i data-lucide="x-circle" class="w-5 h-5"></i>
                                            </button>
                                        ` : ''}
                                        <button onclick="sendEmailToStudent(${request.request_id})" class="text-purple-600 hover:text-purple-700 p-2 hover:bg-purple-50 rounded-lg transition-all" title="إرسال بريد">
                                            <i data-lucide="mail" class="w-5 h-5"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `).join('')}
                    </tbody>
                </table>
            `;
        } else {
            container.innerHTML = `
                <div class="text-center py-12">
                    <i data-lucide="inbox" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                    <p class="text-slate-500 text-lg">لا توجد طلبات</p>
                </div>
            `;
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading requests:', error);
        document.getElementById('requestsTableContainer').innerHTML = '<p class="text-center text-red-500 py-8">فشل التحميل</p>';
    }
}

// Filter Functions
function filterByStatus(status) {
    document.getElementById('filterStatus').value = status;
    const courseId = document.getElementById('filterCourse').value;
    const searchTerm = document.getElementById('searchRequests').value;
    loadRequests(status, courseId, searchTerm);
}

function filterByCourse(courseId) {
    const status = document.getElementById('filterStatus').value;
    const searchTerm = document.getElementById('searchRequests').value;
    loadRequests(status, courseId, searchTerm);
}

function searchRequests() {
    const status = document.getElementById('filterStatus').value;
    const courseId = document.getElementById('filterCourse').value;
    const searchTerm = document.getElementById('searchRequests').value;
    loadRequests(status, courseId, searchTerm);
}

function refreshRequests() {
    document.getElementById('filterStatus').value = 'all';
    document.getElementById('filterCourse').value = 'all';
    document.getElementById('searchRequests').value = '';
    loadRequests();
}

// View Request Details
async function viewRequestDetails(requestId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/requests.php?action=get_details&request_id=${requestId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const request = data.data;
            document.getElementById('requestDetailsContent').innerHTML = `
                <div class="space-y-6">
                    <!-- Student Information -->
                    <div class="bg-slate-50 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="user" class="w-5 h-5 text-purple-600"></i>
                            معلومات الطالب
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500 mb-1">الاسم الكامل</p>
                                <p class="font-semibold text-slate-800">${request.full_name}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">البريد الإلكتروني</p>
                                <p class="font-semibold text-slate-800">${request.email}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">رقم الهاتف</p>
                                <p class="font-semibold text-slate-800">${request.phone}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">المؤهل العلمي</p>
                                <p class="font-semibold text-slate-800">${request.qualification || 'غير محدد'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course Information -->
                    <div class="bg-slate-50 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="book-open" class="w-5 h-5 text-blue-600"></i>
                            معلومات الدورة
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500 mb-1">اسم الدورة</p>
                                <p class="font-semibold text-slate-800">${request.course_title}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">الفئة</p>
                                <p class="font-semibold text-slate-800">${request.category_name || 'غير محدد'}</p>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Request Information -->
                    <div class="bg-slate-50 rounded-xl p-6">
                        <h4 class="text-lg font-bold text-slate-800 mb-4 flex items-center gap-2">
                            <i data-lucide="file-text" class="w-5 h-5 text-emerald-600"></i>
                            معلومات الطلب
                        </h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-sm text-slate-500 mb-1">تاريخ التقديم</p>
                                <p class="font-semibold text-slate-800">${formatDateTime(request.created_at)}</p>
                            </div>
                            <div>
                                <p class="text-sm text-slate-500 mb-1">الحالة</p>
                                <span class="inline-block px-3 py-1 rounded-full text-sm font-bold ${getStatusClass(request.status)}">
                                    ${getStatusLabel(request.status)}
                                </span>
                            </div>
                        </div>
                        ${request.notes ? `
                            <div class="mt-4">
                                <p class="text-sm text-slate-500 mb-1">ملاحظات</p>
                                <p class="text-slate-800 bg-white rounded-lg p-3">${request.notes}</p>
                            </div>
                        ` : ''}
                    </div>
                    
                    <!-- Actions -->
                    ${request.status === 'pending' ? `
                        <div class="flex gap-3">
                            <button onclick="closeViewRequestModal(); openApproveModal(${request.request_id});" class="flex-1 bg-emerald-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="check-circle" class="w-5 h-5"></i>
                                موافقة على الطلب
                            </button>
                            <button onclick="closeViewRequestModal(); openRejectModal(${request.request_id});" class="flex-1 bg-red-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-red-700 transition-all flex items-center justify-center gap-2">
                                <i data-lucide="x-circle" class="w-5 h-5"></i>
                                رفض الطلب
                            </button>
                        </div>
                    ` : ''}
                </div>
            `;
            document.getElementById('viewRequestModal').classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error loading request details:', error);
        alert('فشل تحميل تفاصيل الطلب');
    }
}

// Modal Functions
function closeViewRequestModal() {
    document.getElementById('viewRequestModal').classList.add('hidden');
}

function openApproveModal(requestId) {
    document.getElementById('approve_request_id').value = requestId;
    document.getElementById('approveRequestModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeApproveRequestModal() {
    document.getElementById('approveRequestModal').classList.add('hidden');
    document.getElementById('approveRequestForm').reset();
}

function openRejectModal(requestId) {
    document.getElementById('reject_request_id').value = requestId;
    document.getElementById('rejectRequestModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeRejectRequestModal() {
    document.getElementById('rejectRequestModal').classList.add('hidden');
    document.getElementById('rejectRequestForm').reset();
}

// Form Submissions
document.getElementById('approveRequestForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'approve_request');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/requests.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تمت الموافقة على الطلب بنجاح');
            closeApproveRequestModal();
            loadRequests();
            location.reload();
        } else {
            alert('فشلت الموافقة: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء الموافقة على الطلب');
    }
});

document.getElementById('rejectRequestForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'reject_request');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/requests.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم رفض الطلب بنجاح');
            closeRejectRequestModal();
            loadRequests();
            location.reload();
        } else {
            alert('فشل الرفض: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء رفض الطلب');
    }
});

// Load Courses for Filter
async function loadCoursesFilter() {
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/courses.php?action=get_all');
        const data = await response.json();
        
        const select = document.getElementById('filterCourse');
        if (data.success && data.data) {
            data.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading courses:', error);
    }
}

// Load Trainers for Approve Modal
async function loadTrainersForApprove() {
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/trainers.php?action=get_all');
        const data = await response.json();
        
        const select = document.querySelector('#approveRequestForm select[name="trainer_id"]');
        if (data.success && data.data) {
            data.data.forEach(trainer => {
                const option = document.createElement('option');
                option.value = trainer.trainer_id;
                option.textContent = trainer.full_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading trainers:', error);
    }
}

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG');
}

function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG') + ' ' + date.toLocaleTimeString('ar-EG', {hour: '2-digit', minute: '2-digit'});
}

function getStatusLabel(status) {
    const statuses = {
        'pending': 'معلقة',
        'approved': 'موافقة',
        'rejected': 'مرفوضة'
    };
    return statuses[status] || status;
}

function getStatusClass(status) {
    const classes = {
        'pending': 'bg-amber-100 text-amber-700',
        'approved': 'bg-emerald-100 text-emerald-700',
        'rejected': 'bg-red-100 text-red-700'
    };
    return classes[status] || '';
}

// Send Email to Student
async function sendEmailToStudent(requestId) {
    if (confirm('هل تريد إرسال بريد إلكتروني للطالب؟')) {
        try {
            const response = await fetch('<?php echo $managerBaseUrl; ?>/api/requests.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=send_email&request_id=${requestId}`
            });
            const data = await response.json();
            
            if (data.success) {
                alert('تم إرسال البريد الإلكتروني بنجاح');
            } else {
                alert('فشل إرسال البريد: ' + (data.message || 'خطأ غير معروف'));
            }
        } catch (error) {
            console.error('Error:', error);
            alert('حدث خطأ أثناء إرسال البريد');
        }
    }
}
</script>
