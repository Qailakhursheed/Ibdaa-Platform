<?php
/**
 * Certificate Designer - Design professional certificates
 * مصمم الشهادات الاحترافي
 */

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'save_template':
            $templateName = $_POST['template_name'] ?? '';
            $templateData = $_POST['template_data'] ?? '';
            
            echo json_encode(saveTemplate($conn, $userId, $templateName, $templateData));
            exit;
            
        case 'load_templates':
            echo json_encode(loadTemplates($conn));
            exit;
            
        case 'delete_template':
            $templateId = intval($_POST['template_id']);
            echo json_encode(deleteTemplate($conn, $templateId, $userId));
            exit;
            
        case 'generate_certificate':
            $studentId = intval($_POST['student_id']);
            $courseId = intval($_POST['course_id']);
            $templateId = intval($_POST['template_id']);
            
            echo json_encode(generateCertificate($conn, $studentId, $courseId, $templateId));
            exit;
    }
}

/**
 * Save certificate template
 */
function saveTemplate($conn, $userId, $templateName, $templateData) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO certificate_templates 
            (name, template_data, created_by, created_at, status)
            VALUES (?, ?, ?, NOW(), 'active')
        ");
        $stmt->bind_param("ssi", $templateName, $templateData, $userId);
        $stmt->execute();
        
        return [
            'success' => true,
            'template_id' => $conn->insert_id,
            'message' => 'تم حفظ القالب بنجاح'
        ];
    } catch (Exception $e) {
        error_log("Save Template Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Load all templates
 */
function loadTemplates($conn) {
    try {
        $result = $conn->query("
            SELECT * FROM certificate_templates 
            WHERE status = 'active' 
            ORDER BY created_at DESC
        ");
        
        $templates = [];
        if ($result) {
            while ($row = $result->fetch_assoc()) {
                $templates[] = $row;
            }
        }
        
        return ['success' => true, 'templates' => $templates];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete template
 */
function deleteTemplate($conn, $templateId, $userId) {
    try {
        $stmt = $conn->prepare("DELETE FROM certificate_templates WHERE id = ? AND created_by = ?");
        $stmt->bind_param("ii", $templateId, $userId);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'تم حذف القالب'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Generate certificate for student
 */
function generateCertificate($conn, $studentId, $courseId, $templateId) {
    try {
        // Get student and course details
        $stmt = $conn->prepare("
            SELECT 
                u.full_name as student_name,
                c.course_name,
                c.duration,
                e.final_grade,
                e.completion_date
            FROM enrollments e
            JOIN users u ON e.user_id = u.user_id
            JOIN courses c ON e.course_id = c.course_id
            WHERE e.user_id = ? AND e.course_id = ?
        ");
        $stmt->bind_param("ii", $studentId, $courseId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 0) {
            return ['success' => false, 'message' => 'لم يتم العثور على التسجيل'];
        }
        
        $data = $result->fetch_assoc();
        
        // Generate certificate number
        $certNumber = 'CERT-' . date('Y') . '-' . str_pad($studentId, 5, '0', STR_PAD_LEFT) . '-' . str_pad($courseId, 3, '0', STR_PAD_LEFT);
        
        return [
            'success' => true,
            'certificate_number' => $certNumber,
            'student_name' => $data['student_name'],
            'course_name' => $data['course_name'],
            'duration' => $data['duration'],
            'grade' => $data['final_grade'],
            'completion_date' => $data['completion_date'],
            'issue_date' => date('Y-m-d')
        ];
        
    } catch (Exception $e) {
        error_log("Generate Certificate Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

// Get students for certificate generation
$students = [];
try {
    $result = $conn->query("
        SELECT DISTINCT
            u.user_id, u.full_name, u.email,
            COUNT(DISTINCT e.course_id) as completed_courses
        FROM users u
        JOIN enrollments e ON u.user_id = e.user_id
        WHERE u.role = 'student' AND e.status = 'completed'
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
<div class="mb-8">
    <h2 class="text-3xl font-bold text-slate-800 mb-2">مصمم الشهادات الاحترافي</h2>
    <p class="text-slate-600">تصميم وإصدار شهادات احترافية للمتدربين</p>
</div>

<!-- Designer Interface -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Panel: Tools -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
            <h3 class="text-xl font-bold text-slate-800 mb-6">أدوات التصميم</h3>
            
            <!-- Template Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="layout-template" class="w-4 h-4 inline"></i>
                    قالب الشهادة
                </label>
                <select id="templateSelect" onchange="loadTemplate(this.value)" 
                        class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="classic">كلاسيكي (Classic)</option>
                    <option value="modern">عصري (Modern)</option>
                    <option value="elegant">أنيق (Elegant)</option>
                    <option value="minimal">بسيط (Minimal)</option>
                </select>
            </div>
            
            <!-- Text Elements -->
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">عناصر النص</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">اسم الطالب</label>
                        <input type="text" id="studentNameInput" value="عبد الله محمد" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">اسم الدورة</label>
                        <input type="text" id="courseNameInput" value="البرمجة المتقدمة" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">المدة (ساعات)</label>
                        <input type="number" id="durationInput" value="40" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">التاريخ</label>
                        <input type="date" id="dateInput" value="<?php echo date('Y-m-d'); ?>" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">رقم الشهادة</label>
                        <input type="text" id="certNumberInput" value="CERT-2024-00001" 
                               class="w-full px-3 py-2 border border-slate-300 rounded-lg text-sm">
                    </div>
                </div>
            </div>
            
            <!-- Style Options -->
            <div class="mb-6">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">خيارات التنسيق</h4>
                <div class="space-y-3">
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">لون الحدود</label>
                        <input type="color" id="borderColor" value="#0ea5e9" 
                               class="w-full h-10 border border-slate-300 rounded-lg">
                    </div>
                    <div>
                        <label class="text-xs text-slate-600 block mb-1">حجم الخط الرئيسي</label>
                        <input type="range" id="mainFontSize" min="24" max="48" value="36" 
                               class="w-full">
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="showLogo" checked class="rounded">
                        <label class="text-sm text-slate-700">إظهار الشعار</label>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" id="showQR" checked class="rounded">
                        <label class="text-sm text-slate-700">إظهار QR Code</label>
                    </div>
                </div>
            </div>
            
            <!-- Action Buttons -->
            <div class="space-y-3">
                <button onclick="updatePreview()" 
                        class="w-full px-6 py-3 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition font-bold">
                    <i data-lucide="refresh-cw" class="w-5 h-5 inline mr-2"></i>
                    تحديث المعاينة
                </button>
                <button onclick="saveTemplate()" 
                        class="w-full px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition font-bold">
                    <i data-lucide="save" class="w-5 h-5 inline mr-2"></i>
                    حفظ القالب
                </button>
                <button onclick="downloadCertificate()" 
                        class="w-full px-6 py-3 bg-purple-600 text-white rounded-xl hover:bg-purple-700 transition font-bold">
                    <i data-lucide="download" class="w-5 h-5 inline mr-2"></i>
                    تحميل PDF
                </button>
            </div>
        </div>
    </div>
    
    <!-- Right Panel: Certificate Preview -->
    <div class="lg:col-span-2">
        <!-- Preview Canvas -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-8">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">معاينة الشهادة</h3>
                <div class="flex gap-2">
                    <button onclick="zoomIn()" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                        <i data-lucide="zoom-in" class="w-4 h-4"></i>
                    </button>
                    <button onclick="zoomOut()" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                        <i data-lucide="zoom-out" class="w-4 h-4"></i>
                    </button>
                    <button onclick="resetZoom()" class="p-2 rounded-lg bg-slate-100 hover:bg-slate-200 transition">
                        <i data-lucide="maximize-2" class="w-4 h-4"></i>
                    </button>
                </div>
            </div>
            
            <!-- Certificate Canvas -->
            <div id="certificatePreview" class="border-4 border-slate-200 rounded-xl p-12 bg-gradient-to-br from-sky-50 to-white" style="min-height: 600px;">
                <!-- Classic Template -->
                <div id="classicTemplate" class="certificate-template">
                    <div class="border-8 border-sky-600 rounded-2xl p-12 relative h-full">
                        <!-- Logo -->
                        <div class="text-center mb-6">
                            <img src="../../platform/photos/Sh.jpg" alt="Logo" class="w-24 h-24 rounded-full mx-auto border-4 border-sky-600 shadow-lg">
                        </div>
                        
                        <!-- Title -->
                        <div class="text-center mb-8">
                            <h1 class="text-5xl font-bold text-slate-800 mb-2" style="font-family: 'Arial Black', sans-serif;">شهادة إتمام</h1>
                            <p class="text-xl text-slate-600">Certificate of Completion</p>
                        </div>
                        
                        <!-- Content -->
                        <div class="text-center mb-8">
                            <p class="text-lg text-slate-700 mb-4">تشهد منصة إبداع للتدريب بأن</p>
                            <h2 id="certStudentName" class="text-4xl font-bold text-sky-700 mb-4">عبد الله محمد</h2>
                            <p class="text-lg text-slate-700 mb-2">قد أتم بنجاح دورة</p>
                            <h3 id="certCourseName" class="text-3xl font-bold text-slate-800 mb-4">البرمجة المتقدمة</h3>
                            <p class="text-slate-600">بمدة <span id="certDuration">40</span> ساعة تدريبية</p>
                        </div>
                        
                        <!-- Footer -->
                        <div class="flex items-end justify-between mt-12">
                            <div class="text-center flex-1">
                                <div class="border-t-2 border-slate-800 inline-block px-8 pt-2">
                                    <p class="font-bold text-slate-800">أ. عبد الباسط اليوسفي</p>
                                    <p class="text-sm text-slate-600">المدير العام</p>
                                </div>
                            </div>
                            <div class="text-center flex-1">
                                <div id="certQR" class="w-24 h-24 bg-slate-200 mx-auto rounded-lg flex items-center justify-center">
                                    <i data-lucide="qr-code" class="w-16 h-16 text-slate-400"></i>
                                </div>
                                <p class="text-xs text-slate-500 mt-2">Verify Certificate</p>
                            </div>
                            <div class="text-center flex-1">
                                <p class="text-sm text-slate-600">التاريخ</p>
                                <p id="certDate" class="font-bold text-slate-800"><?php echo date('Y/m/d'); ?></p>
                                <p class="text-xs text-slate-500 mt-2">رقم الشهادة: <span id="certNumber">CERT-2024-00001</span></p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Quick Generate Section -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <h3 class="text-xl font-bold text-slate-800 mb-6">إصدار شهادة سريع</h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <?php foreach (array_slice($students, 0, 6) as $student): ?>
                <div class="flex items-center justify-between p-4 border border-slate-200 rounded-xl hover:border-sky-500 hover:bg-sky-50 transition">
                    <div>
                        <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($student['full_name']); ?></p>
                        <p class="text-sm text-slate-600"><?php echo $student['completed_courses']; ?> دورات مكتملة</p>
                    </div>
                    <button onclick="quickGenerate(<?php echo $student['user_id']; ?>)" 
                            class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition text-sm font-semibold">
                        إصدار
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
let currentZoom = 1;

function loadTemplate(templateType) {
    // Template loading logic
    updatePreview();
}

function updatePreview() {
    // Get values
    const studentName = document.getElementById('studentNameInput').value;
    const courseName = document.getElementById('courseNameInput').value;
    const duration = document.getElementById('durationInput').value;
    const date = document.getElementById('dateInput').value;
    const certNumber = document.getElementById('certNumberInput').value;
    const borderColor = document.getElementById('borderColor').value;
    
    // Update preview
    document.getElementById('certStudentName').textContent = studentName;
    document.getElementById('certCourseName').textContent = courseName;
    document.getElementById('certDuration').textContent = duration;
    document.getElementById('certDate').textContent = new Date(date).toLocaleDateString('ar-EG');
    document.getElementById('certNumber').textContent = certNumber;
    
    // Update styles
    const template = document.querySelector('.certificate-template');
    template.querySelector('.border-8').style.borderColor = borderColor;
    
    lucide.createIcons();
}

function saveTemplate() {
    const templateName = prompt('اسم القالب:');
    if (!templateName) return;
    
    const templateData = JSON.stringify({
        studentName: document.getElementById('studentNameInput').value,
        courseName: document.getElementById('courseNameInput').value,
        duration: document.getElementById('durationInput').value,
        borderColor: document.getElementById('borderColor').value
    });
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=save_template&template_name=${encodeURIComponent(templateName)}&template_data=${encodeURIComponent(templateData)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ تم حفظ القالب بنجاح');
        } else {
            alert('❌ خطأ: ' + data.message);
        }
    });
}

function downloadCertificate() {
    // In production, use html2pdf or similar library
    alert('✅ سيتم تحميل الشهادة بصيغة PDF قريباً');
}

function quickGenerate(studentId) {
    alert(`سيتم إصدار شهادة للطالب رقم ${studentId}`);
}

function zoomIn() {
    currentZoom += 0.1;
    document.getElementById('certificatePreview').style.transform = `scale(${currentZoom})`;
}

function zoomOut() {
    if (currentZoom > 0.5) {
        currentZoom -= 0.1;
        document.getElementById('certificatePreview').style.transform = `scale(${currentZoom})`;
    }
}

function resetZoom() {
    currentZoom = 1;
    document.getElementById('certificatePreview').style.transform = 'scale(1)';
}

// Initialize
document.addEventListener('DOMContentLoaded', () => {
    updatePreview();
    lucide.createIcons();
    
    // Auto-update preview on input change
    ['studentNameInput', 'courseNameInput', 'durationInput', 'dateInput', 'certNumberInput', 'borderColor'].forEach(id => {
        document.getElementById(id).addEventListener('input', updatePreview);
    });
});
</script>
