<?php
/**
 * نظام التسجيل الموحد
 * Unified Registration System
 * 
 * يجمع بين:
 * 1. طلب الانضمام للمنصة
 * 2. التسجيل في دورة محددة
 * 3. رفع المستندات المطلوبة
 */

require_once __DIR__ . '/../includes/session_security.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/anti_detection.php';
require_once 'db.php';

// إخفاء معلومات السيرفر
AntiDetection::hideServerHeaders();

// بدء جلسة آمنة
SessionSecurity::startSecureSession();

// كشف البوتات
if (AntiDetection::detectBot()) {
    AntiDetection::logSuspiciousActivity('registration_bot_detected');
    AntiDetection::sendDecoyResponse();
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$course_name = '';

// جلب معلومات الدورة إذا تم تحديدها
if ($course_id > 0) {
    $stmt = $conn->prepare("SELECT id, name, price, duration_weeks, description FROM courses WHERE id = ? AND status = 'active'");
    $stmt->bind_param('i', $course_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $course_name = $row['name'];
        $course_price = $row['price'];
        $course_duration = $row['duration_weeks'];
    } else {
        $course_id = 0; // دورة غير موجودة
    }
    $stmt->close();
}

// جلب جميع الدورات النشطة
$courses = [];
$result = $conn->query("SELECT id, name, price, duration_weeks FROM courses WHERE status = 'active' ORDER BY name");
while ($row = $result->fetch_assoc()) {
    $courses[] = $row;
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>التسجيل في منصة إبداع - تعز</title>
    <?php echo CSRF::getMetaTag(); ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        body { 
            font-family: 'Cairo', sans-serif; 
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .card-glass {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
        }
        .step {
            transition: all 0.3s ease;
        }
        .step.active {
            transform: scale(1.05);
        }
    </style>
</head>
<body class="p-4 md:p-8">
    <div class="max-w-4xl mx-auto">
        <!-- Header -->
        <div class="text-center mb-8">
            <div class="inline-block p-4 bg-white rounded-full shadow-lg mb-4">
                <img src="photos/Sh.jpg" alt="منصة إبداع" class="w-20 h-20 rounded-full">
            </div>
            <h1 class="text-4xl font-bold text-white mb-2">مرحباً بك في منصة إبداع 🎓</h1>
            <p class="text-white/90 text-lg">سجل الآن للانضمام إلى دوراتنا التدريبية</p>
        </div>

        <!-- Progress Steps -->
        <div class="grid grid-cols-3 gap-4 mb-8">
            <div class="step active text-center">
                <div class="w-12 h-12 bg-white text-indigo-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-2 shadow-lg">
                    1
                </div>
                <p class="text-white text-sm font-semibold">البيانات الشخصية</p>
            </div>
            <div class="step text-center opacity-50">
                <div class="w-12 h-12 bg-white/50 text-gray-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-2">
                    2
                </div>
                <p class="text-white text-sm">اختيار الدورة</p>
            </div>
            <div class="step text-center opacity-50">
                <div class="w-12 h-12 bg-white/50 text-gray-600 rounded-full flex items-center justify-center font-bold text-xl mx-auto mb-2">
                    3
                </div>
                <p class="text-white text-sm">المستندات</p>
            </div>
        </div>

        <!-- Main Form -->
        <div class="card-glass rounded-2xl shadow-2xl p-8">
            <?php if (isset($_GET['success'])): ?>
                <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-green-700 font-semibold">
                                <?php echo htmlspecialchars($_GET['success']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['error'])): ?>
                <div class="bg-red-50 border-l-4 border-red-500 p-4 mb-6 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/>
                            </svg>
                        </div>
                        <div class="mr-3">
                            <p class="text-sm text-red-700 font-semibold">
                                <?php echo htmlspecialchars($_GET['error']); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <form action="process_registration.php" method="POST" enctype="multipart/form-data" class="space-y-6" id="registrationForm">
                <?php echo CSRF::getTokenField(); ?>
                <?php echo AntiDetection::getProtectedFormFields(); ?>

                <!-- Step 1: Personal Information -->
                <div id="step1" class="space-y-6">
                    <h2 class="text-2xl font-bold text-gray-800 border-b pb-3">البيانات الشخصية</h2>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">الاسم الكامل <span class="text-red-500">*</span></label>
                            <input type="text" name="full_name" required minlength="3" 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                   placeholder="الاسم الثلاثي أو الرباعي">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">البريد الإلكتروني <span class="text-red-500">*</span></label>
                            <input type="email" name="email" required 
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                   placeholder="example@email.com">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">رقم الهاتف <span class="text-red-500">*</span></label>
                            <input type="tel" name="phone" required pattern="[0-9]{9,15}"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                   placeholder="00967xxxxxxxxx">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">تاريخ الميلاد <span class="text-red-500">*</span></label>
                            <input type="date" name="birth_date" required max="<?php echo date('Y-m-d', strtotime('-15 years')); ?>"
                                   class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition">
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">المحافظة <span class="text-red-500">*</span></label>
                            <select name="governorate" id="governorate" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition">
                                <option value="">اختر المحافظة</option>
                                <!-- populated by JS -->
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">المديرية <span class="text-red-500">*</span></label>
                            <select name="district" id="district" required
                                    class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition">
                                <option value="">اختر المديرية</option>
                                <!-- populated by JS -->
                            </select>
                            <input type="text" name="district_other" id="district_other" style="display:none;" placeholder="اكتب المديرية إذا لم تكن في القائمة"
                                   class="w-full mt-2 px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition">
                        </div>
                    </div>

                    <div class="flex justify-end pt-4">
                        <button type="button" onclick="nextStep(2)" 
                                class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-bold shadow-lg">
                            التالي ←
                        </button>
                    </div>
                </div>

                <!-- Step 2: Course Selection -->
                <div id="step2" class="space-y-6 hidden">
                    <h2 class="text-2xl font-bold text-gray-800 border-b pb-3">اختيار الدورة التدريبية</h2>
                    
                    <div class="grid grid-cols-1 gap-4">
                        <?php foreach ($courses as $course): ?>
                            <label class="flex items-center p-6 border-2 border-gray-200 rounded-xl hover:border-indigo-500 hover:bg-indigo-50 transition cursor-pointer">
                                <input type="radio" name="course_id" value="<?php echo $course['id']; ?>" 
                                       <?php echo ($course_id == $course['id']) ? 'checked' : ''; ?>
                                       required class="w-5 h-5 text-indigo-600">
                                <div class="mr-4 flex-1">
                                    <div class="font-bold text-lg text-gray-800"><?php echo htmlspecialchars($course['name']); ?></div>
                                    <div class="text-sm text-gray-600 mt-1">
                                        المدة: <?php echo $course['duration_weeks']; ?> أسابيع | 
                                        الرسوم: <?php echo number_format($course['price'], 0); ?> ريال
                                    </div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(1)" 
                                class="px-8 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-bold">
                            → السابق
                        </button>
                        <button type="button" onclick="nextStep(3)" 
                                class="px-8 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition font-bold shadow-lg">
                            التالي ←
                        </button>
                    </div>
                </div>

                <!-- Step 3: Documents -->
                <div id="step3" class="space-y-6 hidden">
                    <h2 class="text-2xl font-bold text-gray-800 border-b pb-3">رفع المستندات المطلوبة</h2>
                    
                    <div class="space-y-6">
                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">صورة الهوية (بطاقة شخصية أو جواز سفر) <span class="text-red-500">*</span></label>
                            <input type="file" name="id_file" accept="image/*,.pdf" required
                                   class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 transition">
                            <p class="text-xs text-gray-500 mt-1">الحد الأقصى: 5MB | الصيغ المسموحة: JPG, PNG, PDF</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">الصورة الشخصية <span class="text-red-500">*</span></label>
                            <input type="file" name="photo" accept="image/*" required
                                   class="w-full px-4 py-3 border-2 border-dashed border-gray-300 rounded-lg hover:border-indigo-500 transition">
                            <p class="text-xs text-gray-500 mt-1">صورة واضحة بخلفية بيضاء أو ملونة</p>
                        </div>

                        <div>
                            <label class="block text-sm font-bold text-gray-700 mb-2">ملاحظات إضافية (اختياري)</label>
                            <textarea name="notes" rows="3" 
                                      class="w-full px-4 py-3 border-2 border-gray-300 rounded-lg focus:border-indigo-500 focus:ring focus:ring-indigo-200 transition"
                                      placeholder="أي معلومات إضافية تود مشاركتها"></textarea>
                        </div>
                    </div>

                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-4 rounded">
                        <div class="flex">
                            <div class="flex-shrink-0">
                                <svg class="h-5 w-5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                                </svg>
                            </div>
                            <div class="mr-3">
                                <h3 class="text-sm font-bold text-yellow-800">تنبيه هام</h3>
                                <p class="text-sm text-yellow-700 mt-1">
                                    • سيتم مراجعة طلبك خلال 24-48 ساعة<br>
                                    • سيتم إرسال إشعار على بريدك الإلكتروني بعد المراجعة<br>
                                    • لن يتم تفعيل حسابك إلا بعد دفع الرسوم المطلوبة
                                </p>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-between pt-4">
                        <button type="button" onclick="prevStep(2)" 
                                class="px-8 py-3 bg-gray-300 text-gray-700 rounded-lg hover:bg-gray-400 transition font-bold">
                            → السابق
                        </button>
                        <button type="submit" id="submitBtn"
                                class="px-8 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition font-bold shadow-lg">
                            إرسال الطلب ✓
                        </button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Footer -->
        <div class="text-center mt-8 text-white">
            <p class="text-sm">هل لديك حساب بالفعل؟ 
                <a href="login.php" class="font-bold underline hover:text-white/80">تسجيل الدخول</a>
            </p>
            <p class="text-xs mt-2 opacity-75">© 2025 منصة إبداع - تعز. جميع الحقوق محفوظة</p>
        </div>
    </div>

    <script>
        let currentStep = 1;

        function nextStep(step) {
            // Validate current step
            const currentForm = document.getElementById(`step${currentStep}`);
            const inputs = currentForm.querySelectorAll('[required]');
            let valid = true;

            inputs.forEach(input => {
                if (!input.value) {
                    input.classList.add('border-red-500');
                    valid = false;
                } else {
                    input.classList.remove('border-red-500');
                }
            });

            if (!valid) {
                alert('يرجى ملء جميع الحقول المطلوبة');
                return;
            }

            // Hide current step
            document.getElementById(`step${currentStep}`).classList.add('hidden');
            
            // Show next step
            document.getElementById(`step${step}`).classList.remove('hidden');
            
            // Update progress
            updateProgress(step);
            
            currentStep = step;
            
            // Scroll to top
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function prevStep(step) {
            document.getElementById(`step${currentStep}`).classList.add('hidden');
            document.getElementById(`step${step}`).classList.remove('hidden');
            updateProgress(step);
            currentStep = step;
            window.scrollTo({ top: 0, behavior: 'smooth' });
        }

        function updateProgress(step) {
            const steps = document.querySelectorAll('.step');
            steps.forEach((s, index) => {
                if (index < step) {
                    s.classList.add('active');
                    s.classList.remove('opacity-50');
                } else if (index === step - 1) {
                    s.classList.add('active');
                    s.classList.remove('opacity-50');
                } else {
                    s.classList.remove('active');
                    s.classList.add('opacity-50');
                }
            });
        }

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const submitBtn = document.getElementById('submitBtn');
            submitBtn.disabled = true;
            submitBtn.innerHTML = 'جاري الإرسال... ⏳';
        });
    </script>
    <script src="/platform/js/yemen_locations.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            if (window.YemenLocations) YemenLocations.init('governorate','district','district_other');
        });
    </script>

    <!-- Chatbot widget styles & script -->
    <link rel="stylesheet" href="/platform/css/chatbot.css">
    <script src="/platform/js/chatbot.js"></script>
</body>
</html>
