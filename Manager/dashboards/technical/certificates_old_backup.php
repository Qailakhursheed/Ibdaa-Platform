<?php
/**
 * Technical Dashboard - Certificates Management System
 * نظام إدارة الشهادات الكامل
 * Features: Issue, Display, Download, Email, WhatsApp
 */

// Get Certificates statistics
$certsStats = [
    'total_certs' => 0,
    'issued_certs' => 0,
    'pending_certs' => 0,
    'this_month' => 0
];

try {
    // Total Certificates
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $certsStats['total_certs'] = $result['total'] ?? 0;
    
    // Issued Certificates
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE status = 'issued'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $certsStats['issued_certs'] = $result['total'] ?? 0;
    
    // Pending Certificates
    $stmt = $conn->prepare("SELECT COUNT(*) as total FROM certificates WHERE status = 'pending'");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $certsStats['pending_certs'] = $result['total'] ?? 0;
    
    // This Month
    $stmt = $conn->prepare("
        SELECT COUNT(*) as total FROM certificates 
        WHERE MONTH(issue_date) = MONTH(CURRENT_DATE()) 
        AND YEAR(issue_date) = YEAR(CURRENT_DATE())
    ");
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $certsStats['this_month'] = $result['total'] ?? 0;
    
} catch(Exception $e) {
    error_log("Certificates Stats Error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="award" class="w-10 h-10"></i>
                إدارة الشهادات
            </h1>
            <p class="text-amber-100 text-lg">إصدار وإدارة شهادات إتمام الدورات التدريبية</p>
        </div>
        <div class="flex gap-3">
            <button onclick="openIssueCertModal()" class="bg-white text-amber-600 px-6 py-3 rounded-xl font-bold hover:bg-amber-50 transition-all flex items-center gap-2 shadow-lg">
                <i data-lucide="plus-circle" class="w-5 h-5"></i>
                إصدار شهادة جديدة
            </button>
            <button onclick="openBulkIssueModal()" class="bg-amber-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-amber-700 transition-all flex items-center gap-2 shadow-lg border-2 border-white">
                <i data-lucide="layers" class="w-5 h-5"></i>
                إصدار جماعي
            </button>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <!-- Total Certificates -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="award" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">الإجمالي</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['total_certs']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي الشهادات</p>
    </div>

    <!-- Issued Certificates -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">مُصدرة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['issued_certs']; ?></h3>
        <p class="text-slate-500 text-sm">شهادات مُصدرة</p>
    </div>

    <!-- Pending Certificates -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">معلقة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['pending_certs']; ?></h3>
        <p class="text-slate-500 text-sm">شهادات معلقة</p>
    </div>

    <!-- This Month -->
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500 hover:shadow-xl transition-shadow">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-purple-100 flex items-center justify-center">
                <i data-lucide="calendar" class="w-6 h-6 text-purple-600"></i>
            </div>
            <span class="text-sm font-semibold text-purple-600 bg-purple-50 px-3 py-1 rounded-full">هذا الشهر</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['this_month']; ?></h3>
        <p class="text-slate-500 text-sm">شهادات الشهر الحالي</p>
    </div>
</div>

<!-- Certificates Chart -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h3 class="text-xl font-bold text-slate-800 mb-4 flex items-center gap-2">
        <i data-lucide="bar-chart-2" class="w-5 h-5 text-amber-600"></i>
        إحصائيات إصدار الشهادات
    </h3>
    <div style="height: 300px; position: relative;">
        <canvas id="certificatesChart"></canvas>
    </div>
</div>

<!-- View Toggle and Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex gap-3">
            <button onclick="switchCertView('grid')" id="gridCertViewBtn" class="px-4 py-2 rounded-lg font-bold transition-all bg-amber-100 text-amber-700 flex items-center gap-2">
                <i data-lucide="grid" class="w-5 h-5"></i>
                شبكة
            </button>
            <button onclick="switchCertView('list')" id="listCertViewBtn" class="px-4 py-2 rounded-lg font-bold transition-all text-slate-600 hover:bg-slate-100 flex items-center gap-2">
                <i data-lucide="list" class="w-5 h-5"></i>
                قائمة
            </button>
        </div>
        
        <div class="flex gap-3 flex-wrap">
            <input type="text" id="searchCerts" placeholder="بحث بالاسم أو رقم الشهادة..." class="border border-slate-300 rounded-lg px-4 py-2 w-80" oninput="searchCerts()">
            <select id="filterCourse" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCerts()">
                <option value="all">كل الدورات</option>
                <!-- Loaded dynamically -->
            </select>
            <select id="filterCertStatus" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCerts()">
                <option value="all">كل الحالات</option>
                <option value="issued">مُصدرة</option>
                <option value="pending">معلقة</option>
                <option value="revoked">ملغاة</option>
            </select>
        </div>
    </div>
</div>

<!-- Certificates Grid View -->
<div id="certsGridView" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 mb-8">
    <div class="col-span-full text-center py-12">
        <i data-lucide="loader" class="w-12 h-12 mx-auto mb-3 animate-spin text-amber-600"></i>
        <p class="text-slate-500 text-lg">جاري تحميل الشهادات...</p>
    </div>
</div>

<!-- Certificates List View -->
<div id="certsListView" class="bg-white rounded-xl shadow-lg p-6 hidden">
    <div id="certsTableContainer" class="overflow-x-auto">
        <div class="text-center py-12">
            <i data-lucide="loader" class="w-12 h-12 mx-auto mb-3 animate-spin text-amber-600"></i>
            <p class="text-slate-500 text-lg">جاري تحميل الشهادات...</p>
        </div>
    </div>
</div>

<!-- Issue Certificate Modal -->
<div id="issueCertModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-3xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="plus-circle" class="w-7 h-7"></i>
                    إصدار شهادة جديدة
                </h3>
                <button onclick="closeIssueCertModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="issueCertForm" class="p-6 space-y-4">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الطالب *</label>
                    <select name="student_id" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent" onchange="loadStudentCourses(this.value)">
                        <option value="">اختر الطالب</option>
                        <!-- Loaded dynamically -->
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الدورة *</label>
                    <select name="course_id" id="courseSelect" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">اختر الدورة</option>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">رقم الشهادة *</label>
                    <input type="text" name="certificate_number" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="مثال: CERT-2025-001">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">تاريخ الإصدار *</label>
                    <input type="date" name="issue_date" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">الدرجة</label>
                    <input type="text" name="grade" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="مثال: ممتاز، A+">
                </div>
                
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">التقدير</label>
                    <select name="honors" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent">
                        <option value="">بدون تقدير</option>
                        <option value="excellence">امتياز</option>
                        <option value="honors">مرتبة الشرف</option>
                        <option value="distinction">تفوق</option>
                    </select>
                </div>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">المهارات المكتسبة</label>
                <textarea name="skills" rows="3" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="قائمة المهارات التي اكتسبها المتدرب..."></textarea>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">ملاحظات إضافية</label>
                <textarea name="notes" rows="2" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-amber-500 focus:border-transparent" placeholder="أي ملاحظات إضافية..."></textarea>
            </div>
            
            <div class="bg-amber-50 border border-amber-200 rounded-lg p-4">
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="checkbox" name="send_email" class="w-5 h-5 text-amber-600 rounded focus:ring-2 focus:ring-amber-500">
                    <span class="text-sm font-semibold text-amber-800">إرسال الشهادة بالبريد الإلكتروني تلقائياً بعد الإصدار</span>
                </label>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-amber-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-amber-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="award" class="w-5 h-5"></i>
                    إصدار الشهادة
                </button>
                <button type="button" onclick="closeIssueCertModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- View Certificate Modal -->
<div id="viewCertModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-5xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-amber-500 to-orange-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="eye" class="w-7 h-7"></i>
                    عرض الشهادة
                </h3>
                <button onclick="closeViewCertModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <div id="certPreviewContent" class="p-6">
            <!-- Loaded dynamically -->
        </div>
    </div>
</div>

<!-- Send Email Modal (Same as ID Cards) -->
<div id="sendCertEmailModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-purple-500 to-indigo-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="mail" class="w-7 h-7"></i>
                    إرسال بالبريد
                </h3>
                <button onclick="closeSendCertEmailModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="sendCertEmailForm" class="p-6 space-y-4">
            <input type="hidden" name="certificate_id" id="email_cert_id">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">البريد الإلكتروني *</label>
                <input type="email" name="email" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="example@email.com">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الموضوع *</label>
                <input type="text" name="subject" required value="شهادة إتمام الدورة من مركز إبداع تعز" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الرسالة</label>
                <textarea name="message" rows="4" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500 focus:border-transparent">تهانينا!

تجد مرفقاً شهادة إتمام الدورة التدريبية من مركز إبداع تعز.

نتمنى لك المزيد من التقدم والنجاح.</textarea>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-purple-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-purple-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    إرسال
                </button>
                <button type="button" onclick="closeSendCertEmailModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Send WhatsApp Modal -->
<div id="sendCertWhatsAppModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-md w-full">
        <div class="bg-gradient-to-r from-green-500 to-emerald-600 text-white p-6 rounded-t-2xl">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="message-circle" class="w-7 h-7"></i>
                    إرسال عبر واتساب
                </h3>
                <button onclick="closeSendCertWhatsAppModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <form id="sendCertWhatsAppForm" class="p-6 space-y-4">
            <input type="hidden" name="certificate_id" id="whatsapp_cert_id">
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">رقم الواتساب *</label>
                <input type="tel" name="phone" required class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent" placeholder="967xxxxxxxxx" pattern="[0-9]+">
                <p class="text-xs text-slate-500 mt-1">أدخل الرقم بصيغة دولية (مثال: 967777123456)</p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">الرسالة</label>
                <textarea name="message" rows="4" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-green-500 focus:border-transparent">مبروك!

تجد مرفقاً شهادة إتمام الدورة التدريبية من مركز إبداع تعز.

نفخر بإنجازك ونتمنى لك مزيداً من التميز.</textarea>
            </div>
            
            <div class="bg-green-50 border border-green-200 rounded-lg p-4">
                <p class="text-green-700 text-sm">
                    <i data-lucide="info" class="w-4 h-4 inline-block ml-2"></i>
                    سيتم فتح واتساب مع الرسالة والملف جاهزين للإرسال
                </p>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 bg-green-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                    <i data-lucide="send" class="w-5 h-5"></i>
                    فتح واتساب
                </button>
                <button type="button" onclick="closeSendCertWhatsAppModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Bulk Issue Modal -->
<div id="bulkIssueModal" class="fixed inset-0 bg-black bg-opacity-50 z-50 hidden flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-indigo-500 to-purple-600 text-white p-6 rounded-t-2xl sticky top-0 z-10">
            <div class="flex items-center justify-between">
                <h3 class="text-2xl font-bold flex items-center gap-2">
                    <i data-lucide="layers" class="w-7 h-7"></i>
                    إصدار شهادات جماعي
                </h3>
                <button onclick="closeBulkIssueModal()" class="text-white hover:bg-white/20 rounded-lg p-2 transition-all">
                    <i data-lucide="x" class="w-6 h-6"></i>
                </button>
            </div>
        </div>
        
        <div class="p-6 space-y-4">
            <div class="bg-indigo-50 border border-indigo-200 rounded-lg p-4">
                <p class="text-indigo-800 text-sm flex items-start gap-2">
                    <i data-lucide="info" class="w-5 h-5 mt-0.5 flex-shrink-0"></i>
                    <span>سيتم إصدار شهادات لجميع الطلاب المكملين للدورة المختارة.</span>
                </p>
            </div>
            
            <div>
                <label class="block text-sm font-bold text-slate-700 mb-2">اختر الدورة *</label>
                <select id="bulkCourseSelect" class="w-full border border-slate-300 rounded-lg px-4 py-3 focus:ring-2 focus:ring-indigo-500 focus:border-transparent" onchange="loadCompletedStudents(this.value)">
                    <option value="">اختر الدورة</option>
                    <!-- Loaded dynamically -->
                </select>
            </div>
            
            <div id="completedStudentsContainer" class="hidden">
                <label class="block text-sm font-bold text-slate-700 mb-3">الطلاب المكملين للدورة</label>
                <div id="completedStudentsList" class="border border-slate-300 rounded-lg p-4 max-h-64 overflow-y-auto space-y-2">
                    <!-- Loaded dynamically -->
                </div>
            </div>
            
            <div class="flex gap-3 pt-4">
                <button onclick="bulkIssueCertificates()" id="bulkIssueBtn" class="flex-1 bg-indigo-600 text-white px-6 py-3 rounded-xl font-bold hover:bg-indigo-700 transition-all flex items-center justify-center gap-2" disabled>
                    <i data-lucide="check-circle" class="w-5 h-5"></i>
                    إصدار الشهادات
                </button>
                <button type="button" onclick="closeBulkIssueModal()" class="px-6 py-3 border-2 border-slate-300 rounded-xl font-bold hover:bg-slate-100 transition-all">
                    إلغاء
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let currentCertView = 'grid';

document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Initialize chart
    initializeCertificatesChart();
    
    // Load certificates
    loadCertificates();
    
    // Load students for issue form
    loadStudentsForCert();
    
    // Load courses for filter and bulk issue
    loadCoursesForFilter();
    loadCoursesForBulkIssue();
    
    // Set default date
    const today = new Date().toISOString().split('T')[0];
    document.querySelector('#issueCertForm [name="issue_date"]').value = today;
});

// Initialize Certificates Chart
function initializeCertificatesChart() {
    const ctx = document.getElementById('certificatesChart');
    if (ctx) {
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['يناير', 'فبراير', 'مارس', 'أبريل', 'مايو', 'يونيو', 'يوليو', 'أغسطس', 'سبتمبر', 'أكتوبر', 'نوفمبر', 'ديسمبر'],
                datasets: [{
                    label: 'عدد الشهادات المُصدرة',
                    data: [45, 52, 48, 65, 72, 68, 75, 82, 78, 85, 90, 95],
                    backgroundColor: '#f59e0b',
                    borderRadius: 8,
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: {
                        display: false
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

// Load Certificates
async function loadCertificates(courseId = 'all', status = 'all', searchTerm = '') {
    try {
        let url = '<?php echo $managerBaseUrl; ?>/api/certificates.php?action=get_all';
        if (courseId !== 'all') url += `&course_id=${courseId}`;
        if (status !== 'all') url += `&status=${status}`;
        if (searchTerm) url += `&search=${encodeURIComponent(searchTerm)}`;
        
        const response = await fetch(url);
        const data = await response.json();
        
        if (data.success && data.data && data.data.length > 0) {
            if (currentCertView === 'grid') {
                displayCertsGrid(data.data);
            } else {
                displayCertsList(data.data);
            }
        } else {
            const emptyMessage = `
                <div class="col-span-full text-center py-12">
                    <i data-lucide="award" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                    <p class="text-slate-500 text-lg">لا توجد شهادات</p>
                </div>
            `;
            if (currentCertView === 'grid') {
                document.getElementById('certsGridView').innerHTML = emptyMessage;
            } else {
                document.getElementById('certsTableContainer').innerHTML = emptyMessage;
            }
        }
        lucide.createIcons();
    } catch (error) {
        console.error('Error loading certificates:', error);
    }
}

// Display Certificates as Grid
function displayCertsGrid(certs) {
    const container = document.getElementById('certsGridView');
    container.innerHTML = certs.map(cert => `
        <div class="bg-white rounded-xl shadow-lg hover:shadow-2xl transition-all overflow-hidden border-2 ${getCertBorderColor(cert.status)}">
            <!-- Certificate Preview -->
            <div class="relative bg-gradient-to-br from-amber-500 to-orange-600 p-6 text-white">
                <div class="absolute top-4 right-4 opacity-10">
                    <i data-lucide="award" class="w-32 h-32"></i>
                </div>
                <div class="relative">
                    <p class="text-xs opacity-75 mb-2">مركز إبداع تعز للتدريب</p>
                    <h4 class="text-xl font-bold mb-1">${cert.student_name}</h4>
                    <p class="text-sm opacity-90 mb-3">${cert.course_title}</p>
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-mono">${cert.certificate_number}</span>
                        <span>${formatDate(cert.issue_date)}</span>
                    </div>
                </div>
            </div>
            
            <!-- Certificate Actions -->
            <div class="p-4 bg-slate-50">
                <div class="grid grid-cols-2 gap-2">
                    <button onclick="viewCertificate(${cert.certificate_id})" class="px-3 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="eye" class="w-4 h-4"></i>
                        عرض
                    </button>
                    <button onclick="downloadCertificate(${cert.certificate_id})" class="px-3 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="download" class="w-4 h-4"></i>
                        تحميل
                    </button>
                    <button onclick="openSendCertEmailModal(${cert.certificate_id}, '${cert.email}')" class="px-3 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="mail" class="w-4 h-4"></i>
                        بريد
                    </button>
                    <button onclick="openSendCertWhatsAppModal(${cert.certificate_id}, '${cert.phone}')" class="px-3 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all text-sm font-semibold flex items-center justify-center gap-2">
                        <i data-lucide="message-circle" class="w-4 h-4"></i>
                        واتساب
                    </button>
                </div>
            </div>
        </div>
    `).join('');
}

// Display Certificates as List
function displayCertsList(certs) {
    const container = document.getElementById('certsTableContainer');
    container.innerHTML = `
        <table class="w-full">
            <thead class="bg-slate-50 border-b-2 border-slate-200">
                <tr>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">الطالب</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">الدورة</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">رقم الشهادة</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">تاريخ الإصدار</th>
                    <th class="text-right py-4 px-6 font-bold text-slate-700">الحالة</th>
                    <th class="text-center py-4 px-6 font-bold text-slate-700">إجراءات</th>
                </tr>
            </thead>
            <tbody>
                ${certs.map(cert => `
                    <tr class="border-b border-slate-100 hover:bg-slate-50 transition-colors">
                        <td class="py-4 px-6">
                            <div class="font-semibold text-slate-800">${cert.student_name}</div>
                            <div class="text-sm text-slate-500">${cert.email}</div>
                        </td>
                        <td class="py-4 px-6 text-slate-600">${cert.course_title}</td>
                        <td class="py-4 px-6 font-mono text-amber-600 font-semibold">${cert.certificate_number}</td>
                        <td class="py-4 px-6 text-slate-600">${formatDate(cert.issue_date)}</td>
                        <td class="py-4 px-6">
                            <span class="px-3 py-1 rounded-full text-xs font-bold ${getCertStatusClass(cert.status)}">
                                ${getCertStatusLabel(cert.status)}
                            </span>
                        </td>
                        <td class="py-4 px-6">
                            <div class="flex items-center justify-center gap-2">
                                <button onclick="viewCertificate(${cert.certificate_id})" class="text-blue-600 hover:text-blue-700 p-2 hover:bg-blue-50 rounded-lg transition-all">
                                    <i data-lucide="eye" class="w-5 h-5"></i>
                                </button>
                                <button onclick="downloadCertificate(${cert.certificate_id})" class="text-emerald-600 hover:text-emerald-700 p-2 hover:bg-emerald-50 rounded-lg transition-all">
                                    <i data-lucide="download" class="w-5 h-5"></i>
                                </button>
                                <button onclick="openSendCertEmailModal(${cert.certificate_id}, '${cert.email}')" class="text-purple-600 hover:text-purple-700 p-2 hover:bg-purple-50 rounded-lg transition-all">
                                    <i data-lucide="mail" class="w-5 h-5"></i>
                                </button>
                                <button onclick="openSendCertWhatsAppModal(${cert.certificate_id}, '${cert.phone}')" class="text-green-600 hover:text-green-700 p-2 hover:bg-green-50 rounded-lg transition-all">
                                    <i data-lucide="message-circle" class="w-5 h-5"></i>
                                </button>
                                <button onclick="printCertificate(${cert.certificate_id})" class="text-slate-600 hover:text-slate-700 p-2 hover:bg-slate-50 rounded-lg transition-all">
                                    <i data-lucide="printer" class="w-5 h-5"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `).join('')}
            </tbody>
        </table>
    `;
}

// Switch Certificate View
function switchCertView(view) {
    currentCertView = view;
    
    if (view === 'grid') {
        document.getElementById('certsGridView').classList.remove('hidden');
        document.getElementById('certsListView').classList.add('hidden');
        document.getElementById('gridCertViewBtn').classList.add('bg-amber-100', 'text-amber-700');
        document.getElementById('gridCertViewBtn').classList.remove('text-slate-600');
        document.getElementById('listCertViewBtn').classList.remove('bg-amber-100', 'text-amber-700');
        document.getElementById('listCertViewBtn').classList.add('text-slate-600');
    } else {
        document.getElementById('certsGridView').classList.add('hidden');
        document.getElementById('certsListView').classList.remove('hidden');
        document.getElementById('listCertViewBtn').classList.add('bg-amber-100', 'text-amber-700');
        document.getElementById('listCertViewBtn').classList.remove('text-slate-600');
        document.getElementById('gridCertViewBtn').classList.remove('bg-amber-100', 'text-amber-700');
        document.getElementById('gridCertViewBtn').classList.add('text-slate-600');
    }
    
    loadCertificates();
    lucide.createIcons();
}

// Filter and Search Functions
function filterCerts() {
    const courseId = document.getElementById('filterCourse').value;
    const status = document.getElementById('filterCertStatus').value;
    const searchTerm = document.getElementById('searchCerts').value;
    loadCertificates(courseId, status, searchTerm);
}

function searchCerts() {
    filterCerts();
}

// Modal Functions
function openIssueCertModal() {
    document.getElementById('issueCertModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeIssueCertModal() {
    document.getElementById('issueCertModal').classList.add('hidden');
    document.getElementById('issueCertForm').reset();
}

function openBulkIssueModal() {
    document.getElementById('bulkIssueModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeBulkIssueModal() {
    document.getElementById('bulkIssueModal').classList.add('hidden');
    document.getElementById('bulkCourseSelect').value = '';
    document.getElementById('completedStudentsContainer').classList.add('hidden');
}

async function viewCertificate(certId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/certificates.php?action=get_details&certificate_id=${certId}`);
        const data = await response.json();
        
        if (data.success && data.data) {
            const cert = data.data;
            document.getElementById('certPreviewContent').innerHTML = `
                <div class="space-y-6">
                    <!-- Certificate Preview -->
                    <div class="relative bg-gradient-to-br from-amber-100 to-orange-100 rounded-2xl p-12 border-8 border-double border-amber-600">
                        <div class="absolute top-0 left-0 w-full h-full opacity-5">
                            <i data-lucide="award" class="w-full h-full"></i>
                        </div>
                        <div class="relative text-center space-y-6">
                            <div>
                                <p class="text-amber-800 text-lg font-bold">مركز إبداع تعز للتدريب</p>
                                <h2 class="text-4xl font-bold text-amber-900 mt-2">شهادة إتمام</h2>
                            </div>
                            
                            <div class="py-6">
                                <p class="text-amber-800 mb-2">يُشهد بأن</p>
                                <h3 class="text-5xl font-bold text-amber-900">${cert.student_name}</h3>
                            </div>
                            
                            <div>
                                <p class="text-amber-800">قد أتم بنجاح دورة</p>
                                <h4 class="text-3xl font-bold text-amber-900 mt-2">${cert.course_title}</h4>
                            </div>
                            
                            ${cert.grade ? `<p class="text-xl text-amber-800">بتقدير: <span class="font-bold">${cert.grade}</span></p>` : ''}
                            
                            <div class="flex items-center justify-around pt-6 border-t-2 border-amber-300">
                                <div>
                                    <p class="text-sm text-amber-700">رقم الشهادة</p>
                                    <p class="font-mono font-bold text-amber-900">${cert.certificate_number}</p>
                                </div>
                                <div>
                                    <p class="text-sm text-amber-700">تاريخ الإصدار</p>
                                    <p class="font-bold text-amber-900">${formatDate(cert.issue_date)}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Actions -->
                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3">
                        <button onclick="downloadCertificate(${cert.certificate_id})" class="px-6 py-3 bg-emerald-600 text-white rounded-xl font-bold hover:bg-emerald-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="download" class="w-5 h-5"></i>
                            تحميل
                        </button>
                        <button onclick="printCertificate(${cert.certificate_id})" class="px-6 py-3 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="printer" class="w-5 h-5"></i>
                            طباعة
                        </button>
                        <button onclick="closeViewCertModal(); openSendCertEmailModal(${cert.certificate_id}, '${cert.email}');" class="px-6 py-3 bg-purple-600 text-white rounded-xl font-bold hover:bg-purple-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="mail" class="w-5 h-5"></i>
                            بريد
                        </button>
                        <button onclick="closeViewCertModal(); openSendCertWhatsAppModal(${cert.certificate_id}, '${cert.phone}');" class="px-6 py-3 bg-green-600 text-white rounded-xl font-bold hover:bg-green-700 transition-all flex items-center justify-center gap-2">
                            <i data-lucide="message-circle" class="w-5 h-5"></i>
                            واتساب
                        </button>
                    </div>
                </div>
            `;
            document.getElementById('viewCertModal').classList.remove('hidden');
            lucide.createIcons();
        }
    } catch (error) {
        console.error('Error:', error);
        alert('فشل تحميل تفاصيل الشهادة');
    }
}

function closeViewCertModal() {
    document.getElementById('viewCertModal').classList.add('hidden');
}

function openSendCertEmailModal(certId, email) {
    document.getElementById('email_cert_id').value = certId;
    document.querySelector('#sendCertEmailForm [name="email"]').value = email || '';
    document.getElementById('sendCertEmailModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeSendCertEmailModal() {
    document.getElementById('sendCertEmailModal').classList.add('hidden');
}

function openSendCertWhatsAppModal(certId, phone) {
    document.getElementById('whatsapp_cert_id').value = certId;
    document.querySelector('#sendCertWhatsAppForm [name="phone"]').value = phone || '';
    document.getElementById('sendCertWhatsAppModal').classList.remove('hidden');
    lucide.createIcons();
}

function closeSendCertWhatsAppModal() {
    document.getElementById('sendCertWhatsAppModal').classList.add('hidden');
}

// Download and Print
function downloadCertificate(certId) {
    window.open(`<?php echo $managerBaseUrl; ?>/api/certificates.php?action=download&certificate_id=${certId}`, '_blank');
}

function printCertificate(certId) {
    window.open(`<?php echo $managerBaseUrl; ?>/api/certificates.php?action=print&certificate_id=${certId}`, '_blank');
}

// Load Students
async function loadStudentsForCert() {
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/students.php?action=get_all');
        const data = await response.json();
        
        const select = document.querySelector('#issueCertForm [name="student_id"]');
        if (data.success && data.data) {
            data.data.forEach(student => {
                const option = document.createElement('option');
                option.value = student.student_id;
                option.textContent = student.full_name;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load Student Courses
async function loadStudentCourses(studentId) {
    try {
        const response = await fetch(`<?php echo $managerBaseUrl; ?>/api/students.php?action=get_courses&student_id=${studentId}`);
        const data = await response.json();
        
        const select = document.getElementById('courseSelect');
        select.innerHTML = '<option value="">اختر الدورة</option>';
        
        if (data.success && data.data) {
            data.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Load Courses for Filter
async function loadCoursesForFilter() {
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
        console.error('Error:', error);
    }
}

// Load Courses for Bulk Issue
async function loadCoursesForBulkIssue() {
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/courses.php?action=get_all');
        const data = await response.json();
        
        const select = document.getElementById('bulkCourseSelect');
        if (data.success && data.data) {
            data.data.forEach(course => {
                const option = document.createElement('option');
                option.value = course.course_id;
                option.textContent = course.title;
                select.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Form Submissions
document.getElementById('issueCertForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'issue_certificate');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/certificates.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم إصدار الشهادة بنجاح');
            closeIssueCertModal();
            loadCertificates();
            location.reload();
        } else {
            alert('فشل الإصدار: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إصدار الشهادة');
    }
});

document.getElementById('sendCertEmailForm')?.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const formData = new FormData(this);
    formData.append('action', 'send_certificate_email');
    
    try {
        const response = await fetch('<?php echo $managerBaseUrl; ?>/api/certificates.php', {
            method: 'POST',
            body: formData
        });
        const data = await response.json();
        
        if (data.success) {
            alert('تم إرسال الشهادة بالبريد الإلكتروني بنجاح');
            closeSendCertEmailModal();
        } else {
            alert('فشل الإرسال: ' + (data.message || 'خطأ غير معروف'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('حدث خطأ أثناء إرسال البريد');
    }
});

document.getElementById('sendCertWhatsAppForm')?.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const certId = document.getElementById('whatsapp_cert_id').value;
    const phone = this.querySelector('[name="phone"]').value;
    const message = this.querySelector('[name="message"]').value;
    
    const downloadUrl = `${window.location.origin}/Manager/api/certificates.php?action=download&certificate_id=${certId}`;
    const whatsappMessage = encodeURIComponent(`${message}\n\nرابط تحميل الشهادة:\n${downloadUrl}`);
    const whatsappUrl = `https://wa.me/${phone}?text=${whatsappMessage}`;
    
    window.open(whatsappUrl, '_blank');
    closeSendCertWhatsAppModal();
});

// Helper Functions
function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('ar-EG');
}

function getCertStatusLabel(status) {
    const statuses = {
        'issued': 'مُصدرة',
        'pending': 'معلقة',
        'revoked': 'ملغاة'
    };
    return statuses[status] || status;
}

function getCertStatusClass(status) {
    const classes = {
        'issued': 'bg-emerald-100 text-emerald-700',
        'pending': 'bg-blue-100 text-blue-700',
        'revoked': 'bg-red-100 text-red-700'
    };
    return classes[status] || '';
}

function getCertBorderColor(status) {
    const colors = {
        'issued': 'border-emerald-500',
        'pending': 'border-blue-500',
        'revoked': 'border-red-500'
    };
    return colors[status] || 'border-slate-300';
}
</script>
