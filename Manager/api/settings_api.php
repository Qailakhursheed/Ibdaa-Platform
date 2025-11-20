<?php
/**
 * ⚙️ Unified Settings API
 * Handles both Platform Settings (Manager only) and User Profile Settings (All Roles)
 */

session_start();
require_once '../../database/db.php';
header('Content-Type: application/json; charset=utf-8');

// 1. Auth Check
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$userId = $_SESSION['user_id'];
$userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? 'student');
$action = $_REQUEST['action'] ?? '';

// Helper: Respond JSON
function respond($success, $message, $data = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $data), JSON_UNESCAPED_UNICODE);
    exit;
}

// Helper: Get Platform Setting
function getPlatformSetting($conn, $key, $default = '') {
    $stmt = $conn->prepare("SELECT setting_value FROM platform_settings WHERE setting_key = ?");
    $stmt->bind_param("s", $key);
    $stmt->execute();
    $res = stmt_get_result_compat($stmt);
    if ($row = $res->fetch_assoc()) {
        return $row['setting_value'];
    }
    return $default;
}

// Helper: Set Platform Setting
function setPlatformSetting($conn, $key, $value, $type, $userId) {
    $stmt = $conn->prepare("
        INSERT INTO platform_settings (setting_key, setting_value, setting_type, updated_by) 
        VALUES (?, ?, ?, ?) 
        ON DUPLICATE KEY UPDATE 
        setting_value = VALUES(setting_value), 
        setting_type = VALUES(setting_type),
        updated_by = VALUES(updated_by),
        updated_at = NOW()
    ");
    $stmt->bind_param("sssi", $key, $value, $type, $userId);
    return $stmt->execute();
}

// =================================================================
// 🟢 PUBLIC ACTIONS (All Authenticated Users)
// =================================================================

// --- Get User Profile ---
if ($action === 'get_profile') {
    $stmt = $conn->prepare("SELECT id, full_name, email, phone, bio, profile_picture, role, created_at FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $user = stmt_get_result_compat($stmt)->fetch_assoc();
    
    if ($user) {
        respond(true, 'Profile fetched', ['profile' => $user]);
    } else {
        respond(false, 'User not found');
    }
}

// --- Update Profile Info ---
if ($action === 'update_profile') {
    $fullName = trim($_POST['full_name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $bio = trim($_POST['bio'] ?? '');
    $email = trim($_POST['email'] ?? '');

    if (empty($fullName) || empty($email)) {
        respond(false, 'Name and Email are required');
    }

    // Check email uniqueness if changed
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
    $stmt->bind_param("si", $email, $userId);
    $stmt->execute();
    if (stmt_get_result_compat($stmt)->num_rows > 0) {
        respond(false, 'Email already in use');
    }

    $stmt = $conn->prepare("UPDATE users SET full_name = ?, phone = ?, bio = ?, email = ? WHERE id = ?");
    $stmt->bind_param("ssssi", $fullName, $phone, $bio, $email, $userId);
    
    if ($stmt->execute()) {
        $_SESSION['full_name'] = $fullName; // Update session
        respond(true, 'Profile updated successfully');
    } else {
        respond(false, 'Failed to update profile');
    }
}

// --- Change Password ---
if ($action === 'change_password') {
    $currentPass = $_POST['current_password'] ?? '';
    $newPass = $_POST['new_password'] ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (empty($currentPass) || empty($newPass)) {
        respond(false, 'All fields are required');
    }

    if ($newPass !== $confirmPass) {
        respond(false, 'New passwords do not match');
    }

    // Verify current password
    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $res = stmt_get_result_compat($stmt)->fetch_assoc();

    if (!$res || !password_verify($currentPass, $res['password'])) {
        respond(false, 'Current password is incorrect');
    }

    // Update password
    $hashed = password_hash($newPass, PASSWORD_DEFAULT);
    $stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
    $stmt->bind_param("si", $hashed, $userId);
    
    if ($stmt->execute()) {
        respond(true, 'Password changed successfully');
    } else {
        respond(false, 'Failed to change password');
    }
}

// --- Upload Profile Picture ---
if ($action === 'upload_avatar') {
    if (!isset($_FILES['avatar']) || $_FILES['avatar']['error'] !== UPLOAD_ERR_OK) {
        respond(false, 'No file uploaded or upload error');
    }

    $file = $_FILES['avatar'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        respond(false, 'Invalid file type. Allowed: ' . implode(', ', $allowed));
    }

    if ($file['size'] > 5 * 1024 * 1024) { // 5MB
        respond(false, 'File too large (Max 5MB)');
    }

    // Create uploads directory if not exists
    $uploadDir = '../../uploads/avatars/';
    if (!file_exists($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileName = 'user_' . $userId . '_' . time() . '.' . $ext;
    $targetPath = $uploadDir . $fileName;
    $dbPath = 'uploads/avatars/' . $fileName;

    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        $stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
        $stmt->bind_param("si", $dbPath, $userId);
        $stmt->execute();
        
        respond(true, 'Avatar updated', ['url' => $dbPath]);
    } else {
        respond(false, 'Failed to save file');
    }
}

// =================================================================
// 🔴 MANAGER ONLY ACTIONS (Platform Settings)
// =================================================================

if ($action === 'get_platform_settings') {
    if ($userRole !== 'manager') respond(false, 'Access Denied');

    $stmt = $conn->prepare("SELECT setting_key, setting_value FROM platform_settings");
    $stmt->execute();
    $res = stmt_get_result_compat($stmt);
    
    $settings = [];
    while ($row = $res->fetch_assoc()) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }

    // Defaults
    $defaults = [
        'site_name' => 'منصة إبداع',
        'site_desc' => 'منصة تعليمية رائدة',
        'maintenance_mode' => '0',
        'allow_registration' => '1',
        'contact_email' => 'info@ibdaa.com',
        'contact_phone' => '',
        'smtp_host' => '',
        'smtp_user' => '',
        'smtp_pass' => '', // Should probably not send this back or mask it
        'smtp_port' => '587'
    ];

    respond(true, 'Settings fetched', ['settings' => array_merge($defaults, $settings)]);
}

if ($action === 'update_platform_settings') {
    if ($userRole !== 'manager') respond(false, 'Access Denied');

    $allowed = [
        'site_name' => 'general',
        'site_desc' => 'general',
        'maintenance_mode' => 'general',
        'allow_registration' => 'general',
        'contact_email' => 'general',
        'contact_phone' => 'general',
        'smtp_host' => 'email',
        'smtp_user' => 'email',
        'smtp_pass' => 'email',
        'smtp_port' => 'email'
    ];

    $conn->begin_transaction();
    try {
        foreach ($allowed as $key => $type) {
            if (isset($_POST[$key])) {
                setPlatformSetting($conn, $key, $_POST[$key], $type, $userId);
            }
        }
        $conn->commit();
        respond(true, 'Settings updated successfully');
    } catch (Exception $e) {
        $conn->rollback();
        respond(false, 'Error updating settings: ' . $e->getMessage());
    }
}

respond(false, 'Invalid Action');
?>