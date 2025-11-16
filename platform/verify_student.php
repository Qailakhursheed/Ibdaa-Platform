<?php
// 🔐 AI-Powered Student Verification System
// Advanced QR Code Verification with Complete Data Display

require_once __DIR__ . '/../database/db.php';

$student_id = intval($_GET['id'] ?? 0);
$is_json = isset($_GET['json']);

if (!$student_id) {
    if ($is_json) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => false, 'message' => 'رقم الطالب مطلوب']);
        exit;
    }
    die('رقم الطالب غير صحيح');
}

// Fetch complete student data
$stmt = $conn->prepare("
    SELECT 
        u.id, u.full_name, u.email, u.phone, u.governorate, u.district, 
        u.created_at, u.updated_at,
        c.title as course_title, c.course_id,
        e.enrollment_date, e.status as enrollment_status,
        e.payment_status, e.payment_amount,
        (SELECT COUNT(*) FROM course_progress cp WHERE cp.user_id = u.id) as completed_lessons,
        (SELECT AVG(grade) FROM grades g WHERE g.student_id = u.id) as average_grade
    FROM users u
    LEFT JOIN enrollments e ON u.id = e.user_id
    LEFT JOIN courses c ON e.course_id = c.course_id
    WHERE u.id = ? AND u.role = 'student'
    LIMIT 1
");
$stmt->bind_param('i', $student_id);
$stmt->execute();
$result = $stmt->get_result();

$student = $result->fetch_assoc();
$verified = $student !== null;

// JSON Response (for API calls)
if ($is_json) {
    header('Content-Type: application/json; charset=utf-8');
    if ($verified) {
        echo json_encode([
            'success' => true,
            'student' => [
                'id' => $student['id'],
                'full_name' => $student['full_name'],
                'email' => $student['email'],
                'phone' => $student['phone'],
                'governorate' => $student['governorate'],
                'district' => $student['district'],
                'course_title' => $student['course_title'],
                'enrollment_status' => $student['enrollment_status'],
                'payment_status' => $student['payment_status'],
                'created_at' => $student['created_at'],
                'completed_lessons' => intval($student['completed_lessons']),
                'average_grade' => round(floatval($student['average_grade']), 2)
            ]
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'الطالب غير موجود']);
    }
    exit;
}

// HTML Response (for QR scanning)
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $verified ? '✅ تم التحقق من الطالب' : '❌ بطاقة غير صالحة' ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap');
        body { font-family: 'Cairo', Tahoma, Arial, sans-serif; }
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card-shadow { box-shadow: 0 20px 60px rgba(0,0,0,0.15); }
        .info-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1rem; }
        .badge { display: inline-block; padding: 0.25rem 0.75rem; border-radius: 9999px; font-size: 0.75rem; font-weight: 600; }
        .badge-success { background: #d1fae5; color: #065f46; }
        .badge-warning { background: #fef3c7; color: #92400e; }
        .pulse { animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
    </style>
</head>
<body class="min-h-screen gradient-bg p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        
        <?php if($verified): ?>
            <!-- Verified Card -->
            <div class="bg-white rounded-3xl overflow-hidden card-shadow">
                
                <!-- Header -->
                <div class="gradient-bg text-white p-8 text-center relative overflow-hidden">
                    <div class="absolute top-0 right-0 w-64 h-64 bg-white opacity-5 rounded-full -mr-32 -mt-32"></div>
                    <div class="absolute bottom-0 left-0 w-48 h-48 bg-white opacity-5 rounded-full -ml-24 -mb-24"></div>
                    <div class="relative z-10">
                        <div class="text-6xl mb-4 pulse">✅</div>
                        <h1 class="text-3xl font-bold mb-2">تم التحقق من البطاقة بنجاح</h1>
                        <p class="text-indigo-100 text-lg">AI-Powered Verification System</p>
                    </div>
                </div>

                <!-- Student Info -->
                <div class="p-8">
                    
                    <!-- Name & ID -->
                    <div class="text-center mb-8 pb-8 border-b-2 border-gray-100">
                        <div class="inline-flex items-center justify-center w-24 h-24 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-full text-white text-4xl font-bold mb-4 shadow-lg">
                            <?= mb_substr($student['full_name'], 0, 1) ?>
                        </div>
                        <h2 class="text-3xl font-bold text-gray-800 mb-2"><?= htmlspecialchars($student['full_name']) ?></h2>
                        <p class="text-xl text-indigo-600 font-semibold">رقم الطالب: #<?= str_pad($student['id'], 6, '0', STR_PAD_LEFT) ?></p>
                    </div>

                    <!-- Info Grid -->
                    <div class="info-grid mb-6">
                        
                        <!-- Course Info -->
                        <?php if(!empty($student['course_title'])): ?>
                        <div class="bg-gradient-to-br from-blue-50 to-indigo-50 rounded-2xl p-6 border border-indigo-100">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-indigo-500 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">📚</div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1 font-semibold">الدورة التدريبية</p>
                                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($student['course_title']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Email -->
                        <?php if(!empty($student['email'])): ?>
                        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6 border border-purple-100">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-purple-500 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">📧</div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1 font-semibold">البريد الإلكتروني</p>
                                    <p class="text-sm font-bold text-gray-800 break-all"><?= htmlspecialchars($student['email']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Phone -->
                        <?php if(!empty($student['phone'])): ?>
                        <div class="bg-gradient-to-br from-green-50 to-emerald-50 rounded-2xl p-6 border border-green-100">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">📱</div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1 font-semibold">رقم الهاتف</p>
                                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($student['phone']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Location -->
                        <?php if(!empty($student['district']) || !empty($student['governorate'])): ?>
                        <div class="bg-gradient-to-br from-orange-50 to-amber-50 rounded-2xl p-6 border border-orange-100">
                            <div class="flex items-start gap-3">
                                <div class="w-10 h-10 bg-orange-500 rounded-lg flex items-center justify-center text-white text-xl flex-shrink-0">📍</div>
                                <div class="flex-1">
                                    <p class="text-xs text-gray-500 mb-1 font-semibold">الموقع</p>
                                    <p class="text-sm font-bold text-gray-800"><?= htmlspecialchars($student['district'] . ' - ' . $student['governorate']) ?></p>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                    </div>

                    <!-- Status Badges -->
                    <div class="flex flex-wrap gap-3 mb-6">
                        <?php if($student['enrollment_status'] === 'active'): ?>
                            <span class="badge badge-success">✅ طالب نشط</span>
                        <?php endif; ?>
                        
                        <?php if($student['payment_status'] === 'completed'): ?>
                            <span class="badge badge-success">💳 مكتمل الدفع</span>
                        <?php elseif($student['payment_status'] === 'pending'): ?>
                            <span class="badge badge-warning">⏳ دفع معلق</span>
                        <?php endif; ?>
                        
                        <?php if(intval($student['completed_lessons']) > 0): ?>
                            <span class="badge badge-success">📖 <?= $student['completed_lessons'] ?> درس مكتمل</span>
                        <?php endif; ?>
                        
                        <?php if(floatval($student['average_grade']) > 0): ?>
                            <span class="badge badge-success">📊 المعدل: <?= round($student['average_grade'], 2) ?>%</span>
                        <?php endif; ?>
                    </div>

                    <!-- Timeline -->
                    <div class="bg-gray-50 rounded-2xl p-6 border border-gray-200">
                        <h3 class="font-bold text-gray-800 mb-4 flex items-center gap-2">
                            <span class="text-xl">⏱️</span> الجدول الزمني
                        </h3>
                        <div class="space-y-3">
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">تاريخ التسجيل:</span>
                                <span class="text-sm font-bold text-gray-800"><?= date('Y/m/d', strtotime($student['created_at'])) ?></span>
                            </div>
                            <?php if(!empty($student['enrollment_date'])): ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">تاريخ الالتحاق:</span>
                                <span class="text-sm font-bold text-gray-800"><?= date('Y/m/d', strtotime($student['enrollment_date'])) ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="flex justify-between items-center">
                                <span class="text-sm text-gray-600">آخر تحديث:</span>
                                <span class="text-sm font-bold text-gray-800"><?= date('Y/m/d H:i', strtotime($student['updated_at'])) ?></span>
                            </div>
                        </div>
                    </div>

                </div>

                <!-- Footer -->
                <div class="bg-gradient-to-r from-gray-800 to-gray-900 text-center py-6 px-4">
                    <p class="text-gray-300 text-sm mb-2 font-semibold">منصة إبداع للتدريب والتأهيل</p>
                    <p class="text-gray-500 text-xs">AI-Powered Smart ID Card System v2.0 • Verified at <?= date('Y/m/d H:i') ?></p>
                </div>
            </div>

        <?php else: ?>
            <!-- Not Verified Card -->
            <div class="bg-white rounded-3xl overflow-hidden card-shadow">
                <div class="bg-gradient-to-br from-red-500 to-pink-600 text-white p-8 text-center">
                    <div class="text-6xl mb-4">❌</div>
                    <h1 class="text-3xl font-bold mb-2">بطاقة غير صالحة</h1>
                    <p class="text-red-100">Verification Failed</p>
                </div>
                <div class="p-8 text-center">
                    <p class="text-gray-600 text-lg mb-6">لم يتم العثور على طالب بهذا الرقم التعريفي</p>
                    <p class="text-sm text-gray-500">يرجى التحقق من صحة رمز QR والمحاولة مرة أخرى</p>
                </div>
                <div class="bg-gray-800 text-center py-6">
                    <p class="text-gray-400 text-xs">منصة إبداع للتدريب والتأهيل • <?= date('Y') ?></p>
                </div>
            </div>
        <?php endif; ?>

    </div>
</body>
</html>
