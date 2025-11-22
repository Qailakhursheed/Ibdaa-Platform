<?php
/**
 * group_chat - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';
// Verify authentication
$user = APIAuth::requireAuth();
APIAuth::rateLimit(120, 60);


require_once __DIR__ . '/../../database/db.php';

header('Content-Type: application/json; charset=utf-8');

/**
 * إرسال استجابة JSON
 */
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// التحقق من تسجيل الدخول
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    respond(['success' => false, 'message' => 'يجب تسجيل الدخول أولاً'], 401);
}

$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

try {
    // ==============================================
    // POST: إنشاء مجموعة أو إضافة أعضاء
    // ==============================================
    if ($method === 'POST') {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        
        if (!is_array($data)) {
            respond(['success' => false, 'message' => 'بيانات JSON غير صالحة'], 400);
        }
        
        // ======================================
        // إنشاء مجموعة جديدة
        // ======================================
        if ($action === 'create') {
            $name = trim($data['name'] ?? '');
            $description = trim($data['description'] ?? '');
            $members = $data['members'] ?? [];
            
            if ($name === '') {
                respond(['success' => false, 'message' => 'اسم المجموعة مطلوب'], 400);
            }
            
            if (!is_array($members) || empty($members)) {
                respond(['success' => false, 'message' => 'يجب إضافة أعضاء للمجموعة'], 400);
            }
            
            // إنشاء المجموعة
            $stmt = $conn->prepare(
                "INSERT INTO group_chats (name, description, created_by, created_at) 
                 VALUES (?, ?, ?, NOW())"
            );
            $stmt->bind_param('ssi', $name, $description, $user_id);
            
            if (!$stmt->execute()) {
                $stmt->close();
                respond(['success' => false, 'message' => 'فشل إنشاء المجموعة'], 500);
            }
            
            $group_id = $stmt->insert_id;
            $stmt->close();
            
            // إضافة المنشئ كعضو
            $member_stmt = $conn->prepare(
                "INSERT INTO group_chat_members (group_id, user_id, joined_at) 
                 VALUES (?, ?, NOW())"
            );
            $member_stmt->bind_param('ii', $group_id, $user_id);
            $member_stmt->execute();
            
            // إضافة باقي الأعضاء
            $added_members = 0;
            foreach ($members as $member_id) {
                $member_id = (int)$member_id;
                if ($member_id <= 0 || $member_id === $user_id) continue;
                
                // التحقق من وجود المستخدم
                $check = $conn->prepare("SELECT id FROM users WHERE id = ?");
                $check->bind_param('i', $member_id);
                $check->execute();
                $check_result = $check->get_result();
                
                if ($check_result->num_rows > 0) {
                    $member_stmt->bind_param('ii', $group_id, $member_id);
                    if ($member_stmt->execute()) {
                        $added_members++;
                        
                        // إنشاء إشعار للعضو الجديد
                        $notif = $conn->prepare(
                            "INSERT INTO notifications (user_id, title, message, type, link) 
                             VALUES (?, 'تمت إضافتك لمجموعة', ?, 'info', '/Manager/messages.php')"
                        );
                        $notif_msg = "تمت إضافتك إلى مجموعة: {$name}";
                        $notif->bind_param('is', $member_id, $notif_msg);
                        $notif->execute();
                        $notif->close();
                    }
                }
                $check->close();
            }
            
            $member_stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم إنشاء المجموعة بنجاح',
                'group_id' => $group_id,
                'group_name' => $name,
                'members_added' => $added_members + 1 // +1 للمنشئ
            ], 201);
        }
        
        // ======================================
        // إضافة عضو للمجموعة
        // ======================================
        if ($action === 'add_member') {
            $group_id = (int)($data['group_id'] ?? 0);
            $new_member_id = (int)($data['user_id'] ?? 0);
            
            if ($group_id <= 0 || $new_member_id <= 0) {
                respond(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
            }
            
            // التحقق من أن المستخدم الحالي عضو في المجموعة
            $check_member = $conn->prepare(
                "SELECT id FROM group_chat_members WHERE group_id = ? AND user_id = ?"
            );
            $check_member->bind_param('ii', $group_id, $user_id);
            $check_member->execute();
            $member_result = $check_member->get_result();
            
            if ($member_result->num_rows === 0) {
                $check_member->close();
                respond(['success' => false, 'message' => 'أنت لست عضواً في هذه المجموعة'], 403);
            }
            $check_member->close();
            
            // التحقق من وجود العضو الجديد
            $check_user = $conn->prepare("SELECT id, full_name FROM users WHERE id = ?");
            $check_user->bind_param('i', $new_member_id);
            $check_user->execute();
            $user_result = $check_user->get_result();
            
            if ($user_result->num_rows === 0) {
                $check_user->close();
                respond(['success' => false, 'message' => 'المستخدم غير موجود'], 404);
            }
            
            $new_user = $user_result->fetch_assoc();
            $check_user->close();
            
            // إضافة العضو
            $add_stmt = $conn->prepare(
                "INSERT IGNORE INTO group_chat_members (group_id, user_id, joined_at) 
                 VALUES (?, ?, NOW())"
            );
            $add_stmt->bind_param('ii', $group_id, $new_member_id);
            $add_stmt->execute();
            
            $affected = $add_stmt->affected_rows;
            $add_stmt->close();
            
            if ($affected === 0) {
                respond(['success' => false, 'message' => 'العضو موجود مسبقاً'], 400);
            }
            
            // جلب اسم المجموعة
            $group_stmt = $conn->prepare("SELECT name FROM group_chats WHERE id = ?");
            $group_stmt->bind_param('i', $group_id);
            $group_stmt->execute();
            $group_result = $group_stmt->get_result();
            $group = $group_result->fetch_assoc();
            $group_stmt->close();
            
            // إنشاء إشعار
            $notif = $conn->prepare(
                "INSERT INTO notifications (user_id, title, message, type, link) 
                 VALUES (?, 'تمت إضافتك لمجموعة', ?, 'info', '/Manager/messages.php')"
            );
            $notif_msg = "تمت إضافتك إلى مجموعة: {$group['name']}";
            $notif->bind_param('is', $new_member_id, $notif_msg);
            $notif->execute();
            $notif->close();
            
            respond([
                'success' => true,
                'message' => 'تمت إضافة العضو بنجاح',
                'member_name' => $new_user['full_name']
            ]);
        }
        
        // ======================================
        // مغادرة المجموعة
        // ======================================
        if ($action === 'leave') {
            $group_id = (int)($data['group_id'] ?? 0);
            
            if ($group_id <= 0) {
                respond(['success' => false, 'message' => 'معرف المجموعة مطلوب'], 400);
            }
            
            // حذف العضوية
            $delete_stmt = $conn->prepare(
                "DELETE FROM group_chat_members WHERE group_id = ? AND user_id = ?"
            );
            $delete_stmt->bind_param('ii', $group_id, $user_id);
            $delete_stmt->execute();
            
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            
            if ($affected === 0) {
                respond(['success' => false, 'message' => 'أنت لست عضواً في هذه المجموعة'], 404);
            }
            
            respond([
                'success' => true,
                'message' => 'تم مغادرة المجموعة بنجاح'
            ]);
        }
        
        // ======================================
        // إزالة عضو من المجموعة
        // ======================================
        if ($action === 'remove_member') {
            $group_id = (int)($data['group_id'] ?? 0);
            $member_to_remove = (int)($data['user_id'] ?? 0);
            
            if ($group_id <= 0 || $member_to_remove <= 0) {
                respond(['success' => false, 'message' => 'بيانات غير كاملة'], 400);
            }
            
            // التحقق من أن المستخدم هو منشئ المجموعة
            $check_creator = $conn->prepare(
                "SELECT created_by FROM group_chats WHERE id = ?"
            );
            $check_creator->bind_param('i', $group_id);
            $check_creator->execute();
            $creator_result = $check_creator->get_result();
            
            if ($creator_result->num_rows === 0) {
                $check_creator->close();
                respond(['success' => false, 'message' => 'المجموعة غير موجودة'], 404);
            }
            
            $group = $creator_result->fetch_assoc();
            $check_creator->close();
            
            if ((int)$group['created_by'] !== $user_id) {
                respond(['success' => false, 'message' => 'فقط منشئ المجموعة يمكنه إزالة الأعضاء'], 403);
            }
            
            // إزالة العضو
            $delete_stmt = $conn->prepare(
                "DELETE FROM group_chat_members WHERE group_id = ? AND user_id = ?"
            );
            $delete_stmt->bind_param('ii', $group_id, $member_to_remove);
            $delete_stmt->execute();
            
            $affected = $delete_stmt->affected_rows;
            $delete_stmt->close();
            
            respond([
                'success' => true,
                'message' => 'تم إزالة العضو بنجاح',
                'removed' => $affected
            ]);
        }
        
        respond(['success' => false, 'message' => 'إجراء غير معروف'], 400);
    }
    
    // ==============================================
    // GET: جلب معلومات المجموعة
    // ==============================================
    if ($method === 'GET' && $action === 'info') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        
        if ($group_id <= 0) {
            respond(['success' => false, 'message' => 'معرف المجموعة مطلوب'], 400);
        }
        
        // جلب معلومات المجموعة
        $group_stmt = $conn->prepare(
            "SELECT gc.*, u.full_name AS creator_name
             FROM group_chats gc
             INNER JOIN users u ON gc.created_by = u.id
             WHERE gc.id = ?"
        );
        $group_stmt->bind_param('i', $group_id);
        $group_stmt->execute();
        $group_result = $group_stmt->get_result();
        
        if ($group_result->num_rows === 0) {
            $group_stmt->close();
            respond(['success' => false, 'message' => 'المجموعة غير موجودة'], 404);
        }
        
        $group = $group_result->fetch_assoc();
        $group_stmt->close();
        
        // جلب الأعضاء
        $members_stmt = $conn->prepare(
            "SELECT u.id, u.full_name, u.role, gcm.joined_at
             FROM group_chat_members gcm
             INNER JOIN users u ON gcm.user_id = u.id
             WHERE gcm.group_id = ?"
        );
        $members_stmt->bind_param('i', $group_id);
        $members_stmt->execute();
        $members_result = $members_stmt->get_result();
        
        $members = [];
        while ($member = $members_result->fetch_assoc()) {
            $members[] = [
                'id' => (int)$member['id'],
                'name' => $member['full_name'],
                'role' => $member['role'],
                'joined_at' => $member['joined_at']
            ];
        }
        $members_stmt->close();
        
        respond([
            'success' => true,
            'group' => [
                'id' => (int)$group['id'],
                'name' => $group['name'],
                'description' => $group['description'],
                'created_by' => (int)$group['created_by'],
                'creator_name' => $group['creator_name'],
                'created_at' => $group['created_at'],
                'members' => $members,
                'members_count' => count($members)
            ]
        ]);
    }
    
    // ==============================================
    // DELETE: حذف المجموعة
    // ==============================================
    if ($method === 'DELETE') {
        $group_id = (int)($_GET['group_id'] ?? 0);
        
        if ($group_id <= 0) {
            respond(['success' => false, 'message' => 'معرف المجموعة مطلوب'], 400);
        }
        
        // التحقق من أن المستخدم هو منشئ المجموعة
        $check_creator = $conn->prepare(
            "SELECT created_by FROM group_chats WHERE id = ?"
        );
        $check_creator->bind_param('i', $group_id);
        $check_creator->execute();
        $creator_result = $check_creator->get_result();
        
        if ($creator_result->num_rows === 0) {
            $check_creator->close();
            respond(['success' => false, 'message' => 'المجموعة غير موجودة'], 404);
        }
        
        $group = $creator_result->fetch_assoc();
        $check_creator->close();
        
        if ((int)$group['created_by'] !== $user_id) {
            respond(['success' => false, 'message' => 'فقط منشئ المجموعة يمكنه حذفها'], 403);
        }
        
        // حذف المجموعة (سيتم حذف الأعضاء والرسائل تلقائياً بسبب CASCADE)
        $delete_stmt = $conn->prepare("DELETE FROM group_chats WHERE id = ?");
        $delete_stmt->bind_param('i', $group_id);
        $delete_stmt->execute();
        
        $affected = $delete_stmt->affected_rows;
        $delete_stmt->close();
        
        respond([
            'success' => true,
            'message' => 'تم حذف المجموعة بنجاح'
        ]);
    }
    
    respond(['success' => false, 'message' => 'طريقة أو إجراء غير مدعوم'], 405);
    
} catch (Throwable $e) {
    error_log("Group Chat Error: " . $e->getMessage());
    respond(['success' => false, 'message' => 'خطأ داخلي: ' . $e->getMessage()], 500);
}
