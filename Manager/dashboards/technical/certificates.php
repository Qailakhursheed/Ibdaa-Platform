<?php
/**
 * Technical Dashboard - Certificates Management (Pure PHP System)
 * نظام إدارة الشهادات - PHP محدث بدون AJAX
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper is already initialized
 */

// Get certificates data
$certificates = $technicalHelper->getAllCertificates();
$courses = $technicalHelper->getAllCourses();

// Get statistics
$certsStats = [
    'total_certs' => count($certificates),
    'issued_certs' => count(array_filter($certificates, fn($c) => $c['status'] === 'issued')),
    'pending_certs' => count(array_filter($certificates, fn($c) => $c['status'] === 'pending')),
    'this_month' => count(array_filter($certificates, function($c) {
        return date('Y-m', strtotime($c['issue_date'])) === date('Y-m');
    }))
];
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="award" class="w-10 h-10"></i>
                إدارة الشهادات
            </h1>
            <p class="text-amber-100 text-lg">إصدار وإدارة شهادات إتمام الدورات التدريبية - <?php echo count($certificates); ?> شهادة</p>
        </div>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-amber-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center">
                <i data-lucide="award" class="w-6 h-6 text-amber-600"></i>
            </div>
            <span class="text-sm font-semibold text-amber-600 bg-amber-50 px-3 py-1 rounded-full">الإجمالي</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['total_certs']; ?></h3>
        <p class="text-slate-500 text-sm">إجمالي الشهادات</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-emerald-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
            <span class="text-sm font-semibold text-emerald-600 bg-emerald-50 px-3 py-1 rounded-full">مُصدرة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['issued_certs']; ?></h3>
        <p class="text-slate-500 text-sm">شهادات مُصدرة</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-blue-500">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 rounded-lg bg-blue-100 flex items-center justify-center">
                <i data-lucide="clock" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-sm font-semibold text-blue-600 bg-blue-50 px-3 py-1 rounded-full">معلقة</span>
        </div>
        <h3 class="text-4xl font-bold text-slate-800 mb-1"><?php echo $certsStats['pending_certs']; ?></h3>
        <p class="text-slate-500 text-sm">شهادات معلقة</p>
    </div>

    <div class="bg-white rounded-xl shadow-lg p-6 border-r-4 border-purple-500">
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

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex items-center justify-between flex-wrap gap-4">
        <div class="flex gap-3 flex-wrap flex-1">
            <input type="text" id="searchCerts" placeholder="بحث بالاسم أو رقم الشهادة..." 
                class="border border-slate-300 rounded-lg px-4 py-2 flex-1 min-w-[250px]" 
                onkeyup="filterCertificates()">
            <select id="filterCourse" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCertificates()">
                <option value="">جميع الدورات</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?php echo $course['course_id']; ?>"><?php echo htmlspecialchars($course['course_name']); ?></option>
                <?php endforeach; ?>
            </select>
            <select id="filterStatus" class="border border-slate-300 rounded-lg px-4 py-2" onchange="filterCertificates()">
                <option value="">جميع الحالات</option>
                <option value="issued">مُصدرة</option>
                <option value="pending">معلقة</option>
                <option value="revoked">ملغاة</option>
            </select>
        </div>
    </div>
</div>

<!-- Certificates Grid -->
<div id="certificatesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($certificates)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-xl shadow-lg">
            <i data-lucide="award" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد شهادات</h3>
            <p class="text-slate-500">لم يتم إصدار أي شهادات بعد</p>
        </div>
    <?php else: ?>
        <?php foreach ($certificates as $cert): 
            $statusColors = [
                'issued' => 'from-emerald-500 to-teal-600',
                'pending' => 'from-amber-500 to-orange-600',
                'revoked' => 'from-red-500 to-rose-600'
            ];
            $statusLabels = [
                'issued' => 'مُصدرة',
                'pending' => 'معلقة',
                'revoked' => 'ملغاة'
            ];
        ?>
            <div class="cert-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden"
                 data-name="<?php echo strtolower($cert['student_name'] . ' ' . $cert['certificate_number']); ?>"
                 data-course="<?php echo $cert['course_id']; ?>"
                 data-status="<?php echo $cert['status']; ?>">
                <div class="bg-gradient-to-br <?php echo $statusColors[$cert['status']] ?? 'from-slate-500 to-slate-600'; ?> p-6 text-white">
                    <div class="flex items-center justify-between mb-4">
                        <i data-lucide="award" class="w-10 h-10 opacity-80"></i>
                        <span class="px-3 py-1 bg-white bg-opacity-20 rounded-full text-xs font-semibold">
                            <?php echo $statusLabels[$cert['status']] ?? $cert['status']; ?>
                        </span>
                    </div>
                    <h3 class="text-lg font-bold mb-2"><?php echo htmlspecialchars($cert['student_name']); ?></h3>
                    <p class="text-sm opacity-90"><?php echo htmlspecialchars($cert['certificate_number']); ?></p>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($cert['course_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo date('Y/m/d', strtotime($cert['issue_date'])); ?></span>
                        </div>
                        <?php if (!empty($cert['grade'])): ?>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="star" class="w-4 h-4"></i>
                            <span class="text-sm font-semibold"><?php echo htmlspecialchars($cert['grade']); ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="viewCertificate(<?php echo $cert['certificate_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors text-sm font-semibold flex items-center justify-center gap-2">
                            <i data-lucide="eye" class="w-4 h-4"></i>
                            عرض
                        </button>
                        <button onclick="downloadCertificate(<?php echo $cert['certificate_id']; ?>)" 
                            class="px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors text-sm font-semibold">
                            <i data-lucide="download" class="w-4 h-4"></i>
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function filterCertificates() {
    const search = document.getElementById('searchCerts').value.toLowerCase();
    const course = document.getElementById('filterCourse').value;
    const status = document.getElementById('filterStatus').value;
    
    const cards = document.querySelectorAll('.cert-card');
    
    cards.forEach(card => {
        const name = card.dataset.name;
        const cardCourse = card.dataset.course;
        const cardStatus = card.dataset.status;
        
        const matchSearch = !search || name.includes(search);
        const matchCourse = !course || cardCourse === course;
        const matchStatus = !status || cardStatus === status;
        
        if (matchSearch && matchCourse && matchStatus) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    lucide.createIcons();
}

function viewCertificate(certId) {
    window.open(`certificate_view.php?id=${certId}`, '_blank');
}

function downloadCertificate(certId) {
    window.open(`certificate_download.php?id=${certId}`, '_blank');
}

lucide.createIcons();
</script>
