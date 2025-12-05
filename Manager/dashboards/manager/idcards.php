<?php
/**
 * ID Cards Generator - Student ID Cards System
 * نظام إنشاء البطاقات الطلابية بتصميم احترافي
 */

// Handle AJAX requests for ID card generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'generate_single':
            $studentId = intval($_POST['student_id']);
            echo json_encode(generateIDCard($conn, $studentId));
            exit;
            
        case 'generate_batch':
            $studentIds = json_decode($_POST['student_ids'], true);
            $results = [];
            foreach ($studentIds as $id) {
                $results[] = generateIDCard($conn, intval($id));
            }
            echo json_encode(['success' => true, 'cards' => $results]);
            exit;
            
        case 'download_pdf':
            $cardId = intval($_POST['card_id']);
            generateIDCardPDF($conn, $cardId);
            exit;
    }
}

/**
 * Generate ID Card for a student
 */
function generateIDCard($conn, $studentId) {
    try {
        // Get student details
        $stmt = $conn->prepare("
            SELECT 
                u.user_id, u.full_name, u.email, u.phone, u.photo,
                u.national_id, u.date_of_birth, u.address,
                COUNT(DISTINCT e.course_id) as courses_count,
                GROUP_CONCAT(DISTINCT c.course_name SEPARATOR ', ') as courses
            FROM users u
            LEFT JOIN enrollments e ON u.user_id = e.user_id
            LEFT JOIN courses c ON e.course_id = c.course_id
            WHERE u.user_id = ? AND u.role = 'student'
            GROUP BY u.user_id
        ");
        $stmt->bind_param("i", $studentId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'الطالب غير موجود'];
        }
        
        $student = $result->fetch_assoc();
        
        // Generate unique card number
        $cardNumber = 'IDB-' . date('Y') . '-' . str_pad($studentId, 5, '0', STR_PAD_LEFT);
        
        // Generate QR code data
        $qrData = json_encode([
            'type' => 'student_id',
            'card_number' => $cardNumber,
            'student_id' => $studentId,
            'name' => $student['full_name'],
            'issued_date' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+1 year'))
        ]);
        
        // Store card in database
        $stmt = $conn->prepare("
            INSERT INTO student_id_cards 
            (student_id, card_number, qr_code_data, issue_date, expiry_date, status)
            VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 1 YEAR), 'active')
            ON DUPLICATE KEY UPDATE 
                card_number = VALUES(card_number),
                qr_code_data = VALUES(qr_code_data),
                issue_date = NOW(),
                expiry_date = DATE_ADD(NOW(), INTERVAL 1 YEAR)
        ");
        $stmt->bind_param("iss", $studentId, $cardNumber, $qrData);
        $stmt->execute();
        
        return [
            'success' => true,
            'card_number' => $cardNumber,
            'student' => $student,
            'qr_data' => $qrData
        ];
        
    } catch (Exception $e) {
        error_log("ID Card Generation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get all students for ID card generation
$students = [];
try {
    $result = $conn->query("
        SELECT 
            u.user_id, u.full_name, u.email, u.phone, u.photo, u.status,
            COUNT(DISTINCT e.course_id) as enrolled_courses,
            CASE 
                WHEN idc.card_id IS NOT NULL THEN 'has_card'
                ELSE 'no_card'
            END as card_status,
            idc.card_number, idc.issue_date, idc.expiry_date
        FROM users u
        LEFT JOIN enrollments e ON u.user_id = e.user_id
        LEFT JOIN student_id_cards idc ON u.user_id = idc.student_id AND idc.status = 'active'
        WHERE u.role = 'student'
        GROUP BY u.user_id
        ORDER BY u.full_name
    ");
    
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $students[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Students fetch error: " . $e->getMessage());
}
?>

<!-- Page Header -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-slate-800 mb-2">البطاقات الطلابية</h2>
        <p class="text-slate-600">إنشاء وإدارة البطاقات الطلابية بتصميم احترافي</p>
    </div>
    <div class="flex gap-3">
        <button onclick="generateBatchCards()" class="px-6 py-3 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition font-semibold shadow-sm">
            <i data-lucide="layers" class="w-5 h-5 inline mr-2"></i>
            إنشاء بطاقات جماعية
        </button>
        <button onclick="printAllCards()" class="px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition font-semibold shadow-sm">
            <i data-lucide="printer" class="w-5 h-5 inline mr-2"></i>
            طباعة الكل
        </button>
    </div>
</div>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-3">
            <div class="p-3 rounded-lg bg-sky-50">
                <i data-lucide="credit-card" class="w-6 h-6 text-sky-600"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-800"><?php echo count(array_filter($students, fn($s) => $s['card_status'] === 'has_card')); ?></h3>
        <p class="text-sm text-slate-600 mt-1">بطاقات صادرة</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-3">
            <div class="p-3 rounded-lg bg-amber-50">
                <i data-lucide="alert-circle" class="w-6 h-6 text-amber-600"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-800"><?php echo count(array_filter($students, fn($s) => $s['card_status'] === 'no_card')); ?></h3>
        <p class="text-sm text-slate-600 mt-1">طلاب بدون بطاقة</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-3">
            <div class="p-3 rounded-lg bg-emerald-50">
                <i data-lucide="check-circle" class="w-6 h-6 text-emerald-600"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-800"><?php echo count(array_filter($students, fn($s) => $s['status'] === 'active')); ?></h3>
        <p class="text-sm text-slate-600 mt-1">طلاب نشطون</p>
    </div>
    
    <div class="bg-white rounded-xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-3">
            <div class="p-3 rounded-lg bg-purple-50">
                <i data-lucide="users" class="w-6 h-6 text-purple-600"></i>
            </div>
        </div>
        <h3 class="text-2xl font-bold text-slate-800"><?php echo count($students); ?></h3>
        <p class="text-sm text-slate-600 mt-1">إجمالي الطلاب</p>
    </div>
</div>

<!-- Students Table with ID Card Actions -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
    <div class="p-6 border-b border-slate-200">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-slate-800">قائمة الطلاب</h3>
            <input type="text" id="searchInput" placeholder="بحث بالاسم أو البريد..." 
                   class="px-4 py-2 border border-slate-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-sky-500 w-72">
        </div>
    </div>
    
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-slate-50 border-b border-slate-200">
                <tr>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">
                        <input type="checkbox" id="selectAll" class="rounded">
                    </th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">الطالب</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">الدورات</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">رقم البطاقة</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">الحالة</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-slate-600 uppercase">الإجراءات</th>
                </tr>
            </thead>
            <tbody id="studentsTableBody" class="divide-y divide-slate-100">
                <?php foreach ($students as $student): ?>
                <tr class="hover:bg-slate-50 transition student-row" data-name="<?php echo htmlspecialchars($student['full_name']); ?>" data-email="<?php echo htmlspecialchars($student['email']); ?>">
                    <td class="px-6 py-4">
                        <input type="checkbox" class="student-checkbox rounded" value="<?php echo $student['user_id']; ?>">
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-3">
                            <img src="<?php echo $student['photo'] ?: '../../platform/photos/default-avatar.png'; ?>" 
                                 alt="Photo" class="w-10 h-10 rounded-full object-cover border-2 border-slate-200">
                            <div>
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></p>
                                <p class="text-sm text-slate-500"><?php echo htmlspecialchars($student['email']); ?></p>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-3 py-1 rounded-full text-sm font-semibold bg-sky-100 text-sky-700">
                            <?php echo $student['enrolled_courses']; ?> دورة
                        </span>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($student['card_status'] === 'has_card'): ?>
                            <span class="font-mono text-sm text-slate-700"><?php echo htmlspecialchars($student['card_number']); ?></span>
                        <?php else: ?>
                            <span class="text-slate-400 text-sm">لا توجد بطاقة</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <?php if ($student['card_status'] === 'has_card'): ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                <i data-lucide="check" class="w-3 h-3 inline"></i> صادرة
                            </span>
                        <?php else: ?>
                            <span class="px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                <i data-lucide="clock" class="w-3 h-3 inline"></i> معلقة
                            </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <div class="flex items-center gap-2">
                            <button onclick="generateCard(<?php echo $student['user_id']; ?>)" 
                                    class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 transition" title="إنشاء بطاقة">
                                <i data-lucide="credit-card" class="w-4 h-4"></i>
                            </button>
                            <button onclick="previewCard(<?php echo $student['user_id']; ?>)" 
                                    class="p-2 rounded-lg bg-purple-50 text-purple-600 hover:bg-purple-100 transition" title="معاينة">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="downloadCard(<?php echo $student['user_id']; ?>)" 
                                    class="p-2 rounded-lg bg-emerald-50 text-emerald-600 hover:bg-emerald-100 transition" title="تحميل PDF">
                                <i data-lucide="download" class="w-4 h-4"></i>
                            </button>
                            <button onclick="printCard(<?php echo $student['user_id']; ?>)" 
                                    class="p-2 rounded-lg bg-slate-50 text-slate-600 hover:bg-slate-100 transition" title="طباعة">
                                <i data-lucide="printer" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- ID Card Preview Modal -->
<div id="cardPreviewModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full p-8">
        <div class="flex items-center justify-between mb-6">
            <h3 class="text-2xl font-bold text-slate-800">معاينة البطاقة الطلابية</h3>
            <button onclick="closePreview()" class="p-2 rounded-lg hover:bg-slate-100">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        <div id="cardPreviewContent" class="border-2 border-slate-200 rounded-xl p-8">
            <!-- Card design will be injected here -->
        </div>
    </div>
</div>

<script>
// Search functionality
document.getElementById('searchInput').addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    document.querySelectorAll('.student-row').forEach(row => {
        const name = row.dataset.name.toLowerCase();
        const email = row.dataset.email.toLowerCase();
        if (name.includes(searchTerm) || email.includes(searchTerm)) {
            row.classList.remove('hidden');
        } else {
            row.classList.add('hidden');
        }
    });
});

// Select all checkbox
document.getElementById('selectAll').addEventListener('change', function(e) {
    document.querySelectorAll('.student-checkbox').forEach(cb => {
        cb.checked = e.target.checked;
    });
});

// Generate single ID card
function generateCard(studentId) {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=generate_single&student_id=${studentId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ تم إنشاء البطاقة بنجاح!\nرقم البطاقة: ' + data.card_number);
            location.reload();
        } else {
            alert('❌ خطأ: ' + data.message);
        }
    });
}

// Generate batch cards
function generateBatchCards() {
    const selected = Array.from(document.querySelectorAll('.student-checkbox:checked')).map(cb => cb.value);
    if (selected.length === 0) {
        alert('⚠️ الرجاء اختيار طالب واحد على الأقل');
        return;
    }
    
    if (!confirm(`هل تريد إنشاء ${selected.length} بطاقة؟`)) return;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=generate_batch&student_ids=${JSON.stringify(selected)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert(`✅ تم إنشاء ${data.cards.length} بطاقة بنجاح!`);
            location.reload();
        } else {
            alert('❌ حدث خطأ');
        }
    });
}

// Preview card
function previewCard(studentId) {
    document.getElementById('cardPreviewModal').classList.remove('hidden');
    document.getElementById('cardPreviewContent').innerHTML = `
        <div class="text-center py-8">
            <div class="animate-spin w-8 h-8 border-4 border-sky-600 border-t-transparent rounded-full mx-auto"></div>
            <p class="mt-4 text-slate-600">جاري تحميل المعاينة...</p>
        </div>
    `;
    
    // Generate and show preview
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=generate_single&student_id=${studentId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            const student = data.student;
            document.getElementById('cardPreviewContent').innerHTML = `
                <div class="id-card-design bg-gradient-to-br from-sky-600 to-sky-800 text-white p-8 rounded-2xl shadow-2xl">
                    <div class="flex items-center justify-between mb-6">
                        <img src="../../platform/photos/Sh.jpg" alt="Logo" class="w-16 h-16 rounded-full border-4 border-white">
                        <div class="text-right">
                            <h4 class="text-xl font-bold">منصة إبداع</h4>
                            <p class="text-sm text-sky-100">بطاقة طالب</p>
                        </div>
                    </div>
                    <div class="text-center mb-6">
                        <img src="${student.photo || '../../platform/photos/default-avatar.png'}" 
                             alt="Photo" class="w-32 h-32 rounded-full mx-auto border-4 border-white object-cover shadow-lg">
                    </div>
                    <div class="text-center mb-4">
                        <h3 class="text-2xl font-bold mb-2">${student.full_name}</h3>
                        <p class="text-sky-100 text-sm mb-1">${student.email}</p>
                        <p class="text-sky-100 text-sm">${student.phone}</p>
                    </div>
                    <div class="bg-white bg-opacity-20 rounded-xl p-4 mb-4">
                        <p class="text-center font-mono text-lg tracking-wider">${data.card_number}</p>
                    </div>
                    <div class="text-center text-sm text-sky-100">
                        <p>تاريخ الإصدار: ${new Date().toLocaleDateString('ar-EG')}</p>
                        <p>صالحة حتى: ${new Date(new Date().setFullYear(new Date().getFullYear() + 1)).toLocaleDateString('ar-EG')}</p>
                    </div>
                </div>
            `;
            lucide.createIcons();
        }
    });
}

function closePreview() {
    document.getElementById('cardPreviewModal').classList.add('hidden');
}

function downloadCard(studentId) {
    window.open(`?action=download_pdf&student_id=${studentId}`, '_blank');
}

function printCard(studentId) {
    previewCard(studentId);
    setTimeout(() => window.print(), 500);
}

function printAllCards() {
    window.print();
}

// Initialize icons
lucide.createIcons();
</script>

<style>
@media print {
    body * {
        visibility: hidden;
    }
    #cardPreviewModal, #cardPreviewModal * {
        visibility: visible;
    }
    #cardPreviewModal {
        position: fixed;
        left: 0;
        top: 0;
    }
}
</style>
