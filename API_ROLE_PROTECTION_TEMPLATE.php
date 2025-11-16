<?php
/**
 * 🔐 API Role Protection Template
 * استخدم هذا القالب في بداية كل ملف API لضمان حماية الصلاحيات
 * 
 * Quick Copy-Paste Template for All API Files
 */

// ========================================
// TEMPLATE 1: Manager & Technical Only
// ========================================
// Use for: Finance, Users Management, Settings, Analytics, Graduates
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Role Check: Manager & Technical Only
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$allowedRoles = ['manager', 'technical'];

if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك. الصلاحيات المطلوبة: ' . implode('، ', $allowedRoles)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// TEMPLATE 2: Manager, Technical, Trainer
// ========================================
// Use for: Courses, Announcements, Grades, Messages
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Role Check: Manager, Technical, Trainer
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$allowedRoles = ['manager', 'technical', 'trainer'];

if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك. الصلاحيات المطلوبة: ' . implode('، ', $allowedRoles)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// TEMPLATE 3: Manager Only
// ========================================
// Use for: Settings, Delete Users, Analytics Full Access
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Role Check: Manager Only
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');

if ($userRole !== 'manager') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك. هذه الصلاحية محصورة بالمدير فقط'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// TEMPLATE 4: Technical Only
// ========================================
// Use for: ID Cards Generation
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Role Check: Technical Only
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');

if ($userRole !== 'technical') {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك. هذه الصلاحية محصورة بالمشرف الفني فقط'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// TEMPLATE 5: All Roles (Including Students)
// ========================================
// Use for: Notifications, Public Announcements
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// ✅ Role Check: All Authenticated Users
$userId = $_SESSION['user_id'] ?? null;

if (!$userId) {
    http_response_code(401);
    echo json_encode([
        'success' => false,
        'message' => 'يجب تسجيل الدخول أولاً'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// TEMPLATE 6: Dynamic Action-Based Permissions
// ========================================
// Use when different actions need different roles
/*
session_start();
require_once __DIR__ . '/../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// ✅ Dynamic Role Checking
switch ($action) {
    case 'view':
        // All staff can view
        $allowedRoles = ['manager', 'technical', 'trainer'];
        break;
    
    case 'create':
    case 'update':
        // Manager & Technical can modify
        $allowedRoles = ['manager', 'technical'];
        break;
    
    case 'delete':
        // Only Manager can delete
        $allowedRoles = ['manager'];
        break;
    
    default:
        // Deny unknown actions
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => 'عملية غير معروفة'
        ], JSON_UNESCAPED_UNICODE);
        exit;
}

// Check if user has permission
if (!in_array($userRole, $allowedRoles)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'غير مصرح لك. الصلاحيات المطلوبة: ' . implode('، ', $allowedRoles)
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Continue with API logic...
*/


// ========================================
// ✅ FILES THAT NEED PROTECTION
// ========================================
/*

Priority 1 - CRITICAL (Sensitive Data):
✅ Manager/api/manage_finance.php → Template 1 (Manager, Technical)
✅ Manager/api/manage_users.php → Template 6 (Dynamic: view=all, delete=manager)
✅ Manager/api/get_analytics_data.php → Template 3 (Manager Only)
✅ Manager/api/ai_image_generator.php → Template 1 (Manager, Technical)

Priority 2 - IMPORTANT (Educational Content):
✅ Manager/api/manage_courses.php → Template 2 (Manager, Technical, Trainer)
✅ Manager/api/manage_grades.php → ✅ Already protected (Manager, Technical, Trainer)
✅ Manager/api/manage_announcements.php → Template 2 (Manager, Technical, Trainer)

Priority 3 - MEDIUM (General Content):
✅ Manager/api/manage_messages.php → Template 2 (Manager, Technical, Trainer)
✅ Manager/api/manage_locations.php → Template 1 (Manager, Technical)
✅ Manager/api/smart_import_api.php → Template 1 (Manager, Technical)

Priority 4 - LOW (Public/Student Access):
✅ Manager/api/notifications_api.php → Template 5 (All Authenticated)
✅ platform/api/announcements.php → Template 5 (All Authenticated)

*/


// ========================================
// 🔍 TESTING CHECKLIST
// ========================================
/*

After adding role checks to each API file:

1. Test as Manager:
   - Should access all APIs ✅
   - No 403 errors

2. Test as Technical:
   - Cannot access: Analytics, Settings, Graduates
   - Can access: Finance, Users (view), ID Cards
   - Should see 403 on forbidden APIs

3. Test as Trainer:
   - Cannot access: Finance, Users, Settings, Analytics
   - Can access: Courses, Grades, Announcements, Messages
   - Should see 403 on forbidden APIs

4. Test as Student:
   - Cannot access ANY manager APIs
   - Can access: Notifications, Public Announcements
   - Should see 403 on all manager APIs

5. Test Direct API Access (without login):
   - All APIs should return 401 Unauthorized
   - No data leakage

*/

?>
