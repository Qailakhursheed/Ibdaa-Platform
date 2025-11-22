<?php
/**
 * Users Management API
 * إدارة المستخدمين - محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
require_once __DIR__ . '/../../database/db.php';

// التحقق من الصلاحيات (مدير أو مشرف فني فقط)
$user = APIAuth::requireAuth(['manager', 'technical']);

// تطبيق Rate Limiting
APIAuth::rateLimit(120, 60);

header('Content-Type: application/json; charset=utf-8');

function respond(array $payload, int $status = 200): void {
    http_response_code($status);
    $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE);
    if ($encoded === false) {
        echo '{"success": false, "message": "JSON encoding failed."}';
    } else {
        echo $encoded;
    }
    exit;
}

function sanitize_role(?string $role): ?string {
    if ($role === null) {
        return null;
    }
    $allowed = ['manager', 'technical', 'trainer', 'student'];
    return in_array($role, $allowed, true) ? $role : null;
}

function sanitize_gender(?string $gender): ?string {
    if ($gender === null) {
        return null;
    }
    $allowed = ['male', 'female'];
    return in_array($gender, $allowed, true) ? $gender : null;
}

// التحقق من الجلسة
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id || !in_array($user_role, ['manager','technical'], true)) {
    respond(['success' => false, 'message' => 'غير مصرح لك'], 403);
}

$method = $_SERVER['REQUEST_METHOD'];

try {
    if ($method === 'GET') {
        $action = $_GET['action'] ?? '';

        if ($action === 'get_single') {
            $targetId = (int) ($_GET['id'] ?? 0);
            if ($targetId <= 0) {
                respond(['success' => false, 'message' => 'معرّف المستخدم مطلوب'], 400);
            }

            $stmt = $conn->prepare(
                "SELECT id, full_name, full_name_en, email, phone, role, dob, governorate, district, address, locations, profile_picture, verified, created_at, updated_at
                 FROM users WHERE id = ? LIMIT 1"
            );
            $stmt->bind_param('i', $targetId);
            $stmt->execute();
            $result = stmt_get_result_compat($stmt);
            $user = $result->fetch_assoc();
            $stmt->close();

            if (!$user) {
                respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }

            respond(['success' => true, 'user' => $user]);
        }

        $roleFilter = $_GET['role'] ?? 'all';
        $baseQuery = "SELECT id, full_name, full_name_en, email, phone, role, dob, governorate, district, address, created_at, verified FROM users";

        if ($roleFilter !== 'all') {
            $roleFilter = sanitize_role($roleFilter);
            if ($roleFilter === null) {
                respond(['success' => false, 'message' => 'دور غير معروف'], 400);
            }
            $stmt = $conn->prepare($baseQuery . " WHERE role = ? ORDER BY id DESC");
            $stmt->bind_param('s', $roleFilter);
        } else {
            $stmt = $conn->prepare($baseQuery . " ORDER BY id DESC");
        }

    $stmt->execute();
    $result = stmt_get_result_compat($stmt);
    $users = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
        $stmt->close();

        respond(['success' => true, 'data' => $users, 'count' => count($users)]);
    }

    if ($method === 'POST') {
        $rawBody = file_get_contents('php://input');
        $data = json_decode($rawBody, true);
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }

        $action = $data['action'] ?? ($_GET['action'] ?? '');

        if ($action === 'create') {
            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = (string) ($data['password'] ?? '');
            $role = sanitize_role($data['role'] ?? 'student');

            if ($fullName === '' || $email === '' || $password === '') {
                respond(['success' => false, 'message' => 'الرجاء ملء جميع الحقول المطلوبة'], 400);
            }

            if ($role === null) {
                respond(['success' => false, 'message' => 'دور غير صالح'], 400);
            }

            $check = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $check->bind_param('s', $email);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                respond(['success' => false, 'message' => 'البريد الإلكتروني مسجل مسبقاً'], 409);
            }
            $check->close();

            $passwordHash = password_hash($password, PASSWORD_DEFAULT);

            $fullNameEn = $data['full_name_en'] ?? null;
            $phone = $data['phone'] ?? null;
            $dob = $data['dob'] ?? null;
            $genderValue = $data['gender'] ?? null;
            $gender = null;
            if ($genderValue !== null) {
                $gender = sanitize_gender(is_string($genderValue) ? $genderValue : null);
                if ($gender === null) {
                    respond(['success' => false, 'message' => 'قيمة النوع غير صالحة'], 400);
                }
            }
            $governorate = $data['governorate'] ?? null;
            $district = $data['district'] ?? null;
            $subDistrict = $data['sub_district'] ?? null;
            $address = $data['address'] ?? null;
            $locations = $data['locations'] ?? null;
            if (is_array($locations)) {
                $locations = json_encode($locations, JSON_UNESCAPED_UNICODE);
                if ($locations === false) {
                    respond(['success' => false, 'message' => 'فشل ترميز بيانات المواقع'], 400);
                }
            }
            $profilePicture = $data['profile_picture'] ?? null;
            $verified = isset($data['verified']) ? (int) (bool) $data['verified'] : 1;

            $stmt = $conn->prepare(
                'INSERT INTO users (full_name, full_name_en, email, phone, password_hash, role, dob, governorate, district, address, locations, profile_picture, verified)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->bind_param(
                'ssssssssssssi',
                $fullName,
                $fullNameEn,
                $email,
                $phone,
                $passwordHash,
                $role,
                $dob,
                $governorate,
                $district,
                $address,
                $locations,
                $profilePicture,
                $verified
            );

            if (!$stmt->execute()) {
                $errorMessage = $stmt->error;
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إضافة المستخدم: ' . $errorMessage], 500);
            }

            $newId = $stmt->insert_id ?: $conn->insert_id;
            $stmt->close();

            respond(['success' => true, 'message' => 'تم إضافة المستخدم بنجاح', 'user_id' => $newId], 201);
        }

        if ($action === 'update') {
            $targetId = (int) ($data['user_id'] ?? 0);
            if ($targetId <= 0) {
                respond(['success' => false, 'message' => 'معرّف المستخدم مطلوب'], 400);
            }

            $fullName = trim($data['full_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $role = sanitize_role($data['role'] ?? null);

            if ($fullName === '' || $email === '' || $role === null) {
                respond(['success' => false, 'message' => 'بيانات غير كاملة أو دور غير صالح'], 400);
            }

            $check = $conn->prepare('SELECT id FROM users WHERE email = ? AND id != ? LIMIT 1');
            $check->bind_param('si', $email, $targetId);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $check->close();
                respond(['success' => false, 'message' => 'البريد الإلكتروني مستخدم من قبل حساب آخر'], 409);
            }
            $check->close();

            $fullNameEn = $data['full_name_en'] ?? null;
            $phone = $data['phone'] ?? null;
            $dob = $data['dob'] ?? null;
            $genderValue = $data['gender'] ?? null;
            $gender = null;
            if ($genderValue !== null) {
                $gender = sanitize_gender(is_string($genderValue) ? $genderValue : null);
                if ($gender === null) {
                    respond(['success' => false, 'message' => 'قيمة النوع غير صالحة'], 400);
                }
            }
            $governorate = $data['governorate'] ?? null;
            $district = $data['district'] ?? null;
            $subDistrict = $data['sub_district'] ?? null;
            $address = $data['address'] ?? null;
            $locations = $data['locations'] ?? null;
            if (is_array($locations)) {
                $locations = json_encode($locations, JSON_UNESCAPED_UNICODE);
                if ($locations === false) {
                    respond(['success' => false, 'message' => 'فشل ترميز بيانات المواقع'], 400);
                }
            }
            $profilePicture = $data['profile_picture'] ?? null;
            $verified = isset($data['verified']) ? (int) (bool) $data['verified'] : 1;

            $fields = [
                'full_name = ?',
                'full_name_en = ?',
                'email = ?',
                'phone = ?',
                'role = ?',
                'dob = ?',
                'governorate = ?',
                'district = ?',
                'address = ?',
                'locations = ?',
                'profile_picture = ?',
                'verified = ?'
            ];
            $types = 'sssssssssssi';
            $params = [
                $fullName,
                $fullNameEn,
                $email,
                $phone,
                $role,
                $dob,
                $governorate,
                $district,
                $address,
                $locations,
                $profilePicture,
                $verified
            ];

            $password = $data['password'] ?? null;
            if (is_string($password) && $password !== '') {
                $fields[] = 'password_hash = ?';
                $types .= 's';
                $params[] = password_hash($password, PASSWORD_DEFAULT);
            }

            $sql = 'UPDATE users SET ' . implode(', ', $fields) . ', updated_at = CURRENT_TIMESTAMP WHERE id = ? LIMIT 1';
            $types .= 'i';
            $params[] = $targetId;

            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$params);

            if (!$stmt->execute()) {
                $errorMessage = $stmt->error;
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل تحديث المستخدم: ' . $errorMessage], 500);
            }

            $stmt->close();
            respond(['success' => true, 'message' => 'تم تحديث بيانات المستخدم']);
        }

        if ($action === 'delete') {
            $targetId = (int) ($data['user_id'] ?? 0);
            if ($targetId <= 0) {
                respond(['success' => false, 'message' => 'معرّف المستخدم مطلوب'], 400);
            }

            $stmt = $conn->prepare('DELETE FROM users WHERE id = ? LIMIT 1');
            $stmt->bind_param('i', $targetId);

            if (!$stmt->execute()) {
                $errorMessage = $stmt->error;
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل حذف المستخدم: ' . $errorMessage], 500);
            }

            $affected = $stmt->affected_rows;
            $stmt->close();

            if ($affected === 0) {
                respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }

            respond(['success' => true, 'message' => 'تم حذف المستخدم']);
        }

        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }

    respond(['success' => false, 'message' => 'طريقة غير مدعومة'], 405);
} catch (Throwable $throwable) {
    respond(['success' => false, 'message' => 'حدث خطأ داخلي: ' . $throwable->getMessage()], 500);
}
