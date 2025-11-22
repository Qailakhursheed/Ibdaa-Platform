<?php
/**
 * مثال: كيفية تحديث ملف API قديم لاستخدام نظام الحماية الجديد
 * Example: How to Update Old API File to Use New Security System
 */

// ============= الطريقة القديمة (قبل التحديث) =============
/*
<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../database/db.php';

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager','technical'])) {
    echo json_encode(['success'=>false,'message'=>'غير مصرح لك'], JSON_UNESCAPED_UNICODE);
    exit;
}

// باقي الكود...
?>
*/

// ============= الطريقة الجديدة (بعد التحديث) =============
<?php
// 1. تضمين نظام الحماية المركزي
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// 2. التحقق من المصادقة والصلاحيات (سطر واحد بدلاً من 8 أسطر!)
$user = APIAuth::requireAuth(['manager', 'technical']);

// 3. تطبيق Rate Limiting (اختياري ولكن موصى به)
APIAuth::rateLimit(60, 60); // 60 طلب خلال 60 ثانية

// 4. التحقق من طريقة الطلب (اختياري)
// APIAuth::requireMethod('POST');

// 5. الآن يمكنك استخدام معلومات المستخدم بأمان
$userId = $user['user_id'];
$userRole = $user['role'];
$userName = $user['name'];

// 6. باقي الكود كما هو...
try {
    // مثال: جلب البيانات
    $stmt = $conn->prepare("SELECT * FROM courses WHERE trainer_id = ?");
    $stmt->bind_param('i', $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $courses = $result->fetch_all(MYSQLI_ASSOC);
    
    // 7. إرسال الاستجابة (باستخدام الطريقة الموحدة)
    APIAuth::sendSuccess([
        'courses' => $courses,
        'total' => count($courses)
    ]);
    
} catch (Exception $e) {
    // 8. إرسال الخطأ (باستخدام الطريقة الموحدة)
    error_log("API Error: " . $e->getMessage());
    APIAuth::sendError('حدث خطأ في معالجة الطلب', 500);
}
?>

// ============= مثال آخر: API للعمليات الحساسة =============
<?php
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من الصلاحيات (مدير فقط)
$user = APIAuth::requireAuth(['manager']);

// التحقق من طريقة الطلب
APIAuth::requireMethod('POST');

// التحقق من CSRF Token
if (!APIAuth::validateCSRF($_POST['csrf_token'] ?? '')) {
    APIAuth::sendError('رمز الحماية غير صحيح', 403);
}

// تطبيق Rate Limiting صارم
APIAuth::rateLimit(10, 60); // 10 طلبات فقط خلال دقيقة

// تنظيف المدخلات
$courseName = APIAuth::sanitize($_POST['course_name'] ?? '');
$courseDesc = APIAuth::sanitize($_POST['course_desc'] ?? '');

try {
    // العملية الحساسة
    $stmt = $conn->prepare("INSERT INTO courses (name, description, created_by) VALUES (?, ?, ?)");
    $stmt->bind_param('ssi', $courseName, $courseDesc, $user['user_id']);
    $stmt->execute();
    
    // تسجيل النشاط
    APIAuth::logActivity('course_created', [
        'course_id' => $conn->insert_id,
        'course_name' => $courseName
    ]);
    
    APIAuth::sendSuccess([
        'message' => 'تم إنشاء الدورة بنجاح',
        'course_id' => $conn->insert_id
    ]);
    
} catch (Exception $e) {
    error_log("Course Creation Error: " . $e->getMessage());
    APIAuth::sendError('فشل إنشاء الدورة', 500);
}
?>

// ============= مثال: API عام لجميع المستخدمين =============
<?php
require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من تسجيل الدخول فقط (بدون تحديد صلاحيات)
$user = APIAuth::requireAuth();

// تطبيق Rate Limiting عادي
APIAuth::rateLimit(120, 60);

try {
    // جلب البيانات العامة
    $stmt = $conn->prepare("SELECT * FROM announcements WHERE active = 1 ORDER BY created_at DESC");
    $stmt->execute();
    $announcements = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    APIAuth::sendSuccess([
        'announcements' => $announcements
    ]);
    
} catch (Exception $e) {
    APIAuth::sendError('فشل جلب الإعلانات', 500);
}
?>

// ============= نصائح مهمة =============

/**
 * 1. دائماً استخدم APIAuth::requireAuth() في بداية كل ملف API
 * 
 * 2. حدد الصلاحيات المطلوبة بوضوح:
 *    - للمدير فقط: ['manager']
 *    - للمدير والمشرف: ['manager', 'technical']
 *    - للجميع: لا تمرر معامل
 * 
 * 3. استخدم Rate Limiting للحماية من الهجمات:
 *    - عمليات عادية: APIAuth::rateLimit(60, 60)
 *    - عمليات حساسة: APIAuth::rateLimit(10, 60)
 *    - عمليات عامة: APIAuth::rateLimit(120, 60)
 * 
 * 4. استخدم CSRF Token للعمليات التي تغير البيانات:
 *    - INSERT, UPDATE, DELETE
 * 
 * 5. استخدم تنظيف المدخلات دائماً:
 *    APIAuth::sanitize($input)
 * 
 * 6. استخدم الطرق الموحدة للردود:
 *    - APIAuth::sendSuccess($data)
 *    - APIAuth::sendError($message, $code)
 * 
 * 7. سجل النشاطات المهمة:
 *    APIAuth::logActivity('action_name', $details)
 * 
 * 8. تعامل مع الأخطاء بشكل صحيح:
 *    - استخدم try-catch
 *    - سجل الأخطاء في error_log
 *    - لا تكشف تفاصيل الأخطاء للمستخدم
 */

// ============= قائمة التحقق السريعة =============

/**
 * عند تحديث ملف API قديم، تحقق من:
 * 
 * ✅ تضمين api_auth.php في البداية
 * ✅ استخدام APIAuth::requireAuth() بدلاً من التحقق اليدوي
 * ✅ تطبيق Rate Limiting
 * ✅ التحقق من CSRF Token للعمليات الحساسة
 * ✅ تنظيف جميع المدخلات
 * ✅ استخدام الردود الموحدة
 * ✅ تسجيل النشاطات المهمة
 * ✅ معالجة الأخطاء بشكل آمن
 * ✅ اختبار الملف بعد التحديث
 */
