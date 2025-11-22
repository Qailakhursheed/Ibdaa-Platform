<?php
/**
 * Manager Dashboard - Chat System
 * نظام المحادثات - بسيط وفعال
 */

// Get chat statistics
$chat_stats = [
    'total_messages' => 0,
    'unread_messages' => 0,
    'active_conversations' => 0
];

// Check if messages table exists
$messages_table_check = $conn->query("SHOW TABLES LIKE 'messages'");
$messages_exists = ($messages_table_check && $messages_table_check->num_rows > 0);

if ($messages_exists) {
    // Total messages
    $result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = {$userId}");
    if ($result) $chat_stats['total_messages'] = (int)$result->fetch_assoc()['count'];
    
    // Unread messages
    $result = $conn->query("SELECT COUNT(*) as count FROM messages WHERE recipient_id = {$userId} AND is_read = 0");
    if ($result) $chat_stats['unread_messages'] = (int)$result->fetch_assoc()['count'];
    
    // Active conversations
    $result = $conn->query("SELECT COUNT(DISTINCT sender_id) as count FROM messages WHERE recipient_id = {$userId}");
    if ($result) $chat_stats['active_conversations'] = (int)$result->fetch_assoc()['count'];
}

// Handle actions
$action = isset($_POST['action']) ? $_POST['action'] : '';
$message_sent = false;
$error_message = '';

if ($action === 'send_message' && isset($_POST['recipient_id']) && isset($_POST['message'])) {
    if ($messages_exists) {
        $recipient_id = (int)$_POST['recipient_id'];
        $message = trim($_POST['message']);
        
        if (!empty($message)) {
            $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->bind_param("iis", $userId, $recipient_id, $message);
            
            if ($stmt->execute()) {
                $message_sent = true;
            } else {
                $error_message = 'فشل إرسال الرسالة';
            }
        }
    }
}

// Get conversations
$conversations = [];
if ($messages_exists) {
    $query = "
        SELECT 
            u.id,
            u.full_name,
            u.role,
            COUNT(CASE WHEN m.is_read = 0 AND m.recipient_id = {$userId} THEN 1 END) as unread_count,
            MAX(m.created_at) as last_message_time
        FROM users u
        INNER JOIN messages m ON (m.sender_id = u.id OR m.recipient_id = u.id)
        WHERE (m.sender_id = {$userId} OR m.recipient_id = {$userId}) AND u.id != {$userId}
        GROUP BY u.id, u.full_name, u.role
        ORDER BY last_message_time DESC
    ";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $conversations[] = $row;
        }
    }
}

// Get selected conversation messages
$selected_user = isset($_GET['user']) ? (int)$_GET['user'] : 0;
$messages = [];
$selected_user_info = null;

if ($selected_user > 0 && $messages_exists) {
    // Get user info
    $result = $conn->query("SELECT id, full_name, role FROM users WHERE id = {$selected_user}");
    if ($result) {
        $selected_user_info = $result->fetch_assoc();
    }
    
    // Get messages
    $query = "
        SELECT m.*, u.full_name as sender_name 
        FROM messages m
        JOIN users u ON m.sender_id = u.id
        WHERE (m.sender_id = {$userId} AND m.recipient_id = {$selected_user})
           OR (m.sender_id = {$selected_user} AND m.recipient_id = {$userId})
        ORDER BY m.created_at ASC
    ";
    
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $messages[] = $row;
        }
    }
    
    // Mark messages as read
    $conn->query("UPDATE messages SET is_read = 1 WHERE sender_id = {$selected_user} AND recipient_id = {$userId} AND is_read = 0");
}

// Get all users for new conversation
$all_users = [];
$result = $conn->query("SELECT id, full_name, role FROM users WHERE id != {$userId} AND verified = 1 ORDER BY full_name");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $all_users[] = $row;
    }
}
?>

<!-- Page Header -->
<div class="bg-gradient-to-br from-purple-500 to-pink-600 rounded-2xl shadow-xl p-8 mb-8 text-white">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold mb-2 flex items-center gap-3">
                <i data-lucide="message-circle" class="w-10 h-10"></i>
                نظام المحادثات
            </h1>
            <p class="text-purple-100 text-lg">التواصل مع المدربين والطلاب</p>
        </div>
    </div>
</div>

<?php if (!$messages_exists): ?>
<div class="bg-yellow-50 border border-yellow-200 rounded-xl p-6 mb-8">
    <div class="flex items-center gap-3">
        <i data-lucide="alert-triangle" class="w-6 h-6 text-yellow-600"></i>
        <div>
            <h3 class="font-bold text-yellow-800">جدول الرسائل غير موجود</h3>
            <p class="text-yellow-700 text-sm">يرجى إنشاء جدول messages في قاعدة البيانات</p>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Chat Statistics -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-blue-100 rounded-xl flex items-center justify-center">
                <i data-lucide="message-square" class="w-6 h-6 text-blue-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($chat_stats['total_messages']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">إجمالي الرسائل</h3>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-red-100 rounded-xl flex items-center justify-center">
                <i data-lucide="bell" class="w-6 h-6 text-red-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($chat_stats['unread_messages']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">رسائل غير مقروءة</h3>
    </div>
    
    <div class="bg-white rounded-xl shadow-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <div class="w-12 h-12 bg-green-100 rounded-xl flex items-center justify-center">
                <i data-lucide="users" class="w-6 h-6 text-green-600"></i>
            </div>
            <span class="text-3xl font-bold text-slate-800"><?php echo number_format($chat_stats['active_conversations']); ?></span>
        </div>
        <h3 class="text-slate-600 font-semibold">محادثات نشطة</h3>
    </div>
</div>

<?php if ($message_sent): ?>
<div class="bg-green-50 border border-green-200 rounded-xl p-4 mb-6">
    <p class="text-green-800 font-semibold"><i data-lucide="check-circle" class="w-5 h-5 inline"></i> تم إرسال الرسالة بنجاح</p>
</div>
<?php endif; ?>

<?php if ($error_message): ?>
<div class="bg-red-50 border border-red-200 rounded-xl p-4 mb-6">
    <p class="text-red-800 font-semibold"><i data-lucide="x-circle" class="w-5 h-5 inline"></i> <?php echo $error_message; ?></p>
</div>
<?php endif; ?>

<!-- Chat Interface -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- Conversations List -->
    <div class="bg-white rounded-xl shadow-lg overflow-hidden">
        <div class="p-4 border-b border-slate-200 flex items-center justify-between">
            <h3 class="font-bold text-slate-800">المحادثات</h3>
            <button onclick="document.getElementById('newChatModal').classList.remove('hidden')" 
                class="text-sm bg-purple-600 text-white px-3 py-1 rounded-lg hover:bg-purple-700">
                <i data-lucide="plus" class="w-4 h-4 inline"></i> جديد
            </button>
        </div>
        
        <div class="overflow-y-auto" style="max-height: 600px;">
            <?php if (empty($conversations)): ?>
                <div class="text-center py-8 text-slate-500">
                    <i data-lucide="inbox" class="w-12 h-12 mx-auto mb-3 text-slate-300"></i>
                    <p>لا توجد محادثات</p>
                </div>
            <?php else: ?>
                <?php foreach ($conversations as $conv): ?>
                    <a href="?page=chat&user=<?php echo $conv['id']; ?>" 
                       class="block p-4 border-b border-slate-200 hover:bg-slate-50 transition <?php echo $selected_user == $conv['id'] ? 'bg-purple-50' : ''; ?>">
                        <div class="flex items-center gap-3">
                            <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                                <i data-lucide="user" class="w-6 h-6 text-purple-600"></i>
                            </div>
                            <div class="flex-1">
                                <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($conv['full_name']); ?></p>
                                <p class="text-xs text-slate-500"><?php echo $conv['role'] === 'trainer' ? 'مدرب' : ($conv['role'] === 'student' ? 'طالب' : 'مستخدم'); ?></p>
                            </div>
                            <?php if ($conv['unread_count'] > 0): ?>
                                <span class="bg-red-500 text-white text-xs font-bold px-2 py-1 rounded-full">
                                    <?php echo $conv['unread_count']; ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Chat Messages -->
    <div class="lg:col-span-2 bg-white rounded-xl shadow-lg flex flex-col" style="height: 700px;">
        <?php if ($selected_user_info): ?>
            <!-- Chat Header -->
            <div class="p-4 border-b border-slate-200">
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 bg-purple-100 rounded-full flex items-center justify-center">
                        <i data-lucide="user" class="w-6 h-6 text-purple-600"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($selected_user_info['full_name']); ?></h3>
                        <p class="text-xs text-slate-500"><?php echo $selected_user_info['role'] === 'trainer' ? 'مدرب' : 'طالب'; ?></p>
                    </div>
                </div>
            </div>
            
            <!-- Messages Container -->
            <div class="flex-1 p-4 overflow-y-auto" style="max-height: 500px;">
                <?php if (empty($messages)): ?>
                    <div class="text-center py-16 text-slate-500">
                        <i data-lucide="message-circle" class="w-16 h-16 mx-auto mb-4 text-slate-300"></i>
                        <p>لا توجد رسائل - ابدأ المحادثة</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($messages as $msg): ?>
                        <div class="mb-4 flex <?php echo $msg['sender_id'] == $userId ? 'justify-end' : 'justify-start'; ?>">
                            <div class="max-w-[70%] <?php echo $msg['sender_id'] == $userId ? 'bg-purple-100' : 'bg-slate-100'; ?> rounded-lg p-3">
                                <p class="text-slate-800"><?php echo htmlspecialchars($msg['message']); ?></p>
                                <p class="text-xs text-slate-500 mt-1">
                                    <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                                </p>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            
            <!-- Message Input -->
            <div class="p-4 border-t border-slate-200">
                <form method="POST" class="flex gap-2">
                    <input type="hidden" name="action" value="send_message">
                    <input type="hidden" name="recipient_id" value="<?php echo $selected_user; ?>">
                    <input type="text" name="message" placeholder="اكتب رسالتك..." 
                        class="flex-1 px-4 py-2 border border-slate-300 rounded-lg" required>
                    <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">
                        <i data-lucide="send" class="w-5 h-5"></i>
                    </button>
                </form>
            </div>
        <?php else: ?>
            <div class="flex-1 flex items-center justify-center text-slate-500">
                <div class="text-center">
                    <i data-lucide="message-circle" class="w-24 h-24 mx-auto mb-4 text-slate-300"></i>
                    <p class="text-lg">اختر محادثة لبدء الدردشة</p>
                </div>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- New Chat Modal -->
<div id="newChatModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
    <div class="bg-white rounded-xl shadow-xl p-6 w-full max-w-md">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xl font-bold text-slate-800">محادثة جديدة</h3>
            <button onclick="document.getElementById('newChatModal').classList.add('hidden')" class="text-slate-400 hover:text-slate-600">
                <i data-lucide="x" class="w-6 h-6"></i>
            </button>
        </div>
        
        <div class="space-y-2 max-h-96 overflow-y-auto">
            <?php foreach ($all_users as $user): ?>
                <a href="?page=chat&user=<?php echo $user['id']; ?>" 
                   class="block p-3 bg-slate-50 rounded-lg hover:bg-slate-100 transition">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 bg-purple-100 rounded-full flex items-center justify-center">
                            <i data-lucide="user" class="w-5 h-5 text-purple-600"></i>
                        </div>
                        <div>
                            <p class="font-semibold text-slate-800"><?php echo htmlspecialchars($user['full_name']); ?></p>
                            <p class="text-xs text-slate-500"><?php echo $user['role'] === 'trainer' ? 'مدرب' : 'طالب'; ?></p>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    lucide.createIcons();
    
    // Auto-scroll to bottom of messages
    const messagesContainer = document.querySelector('.overflow-y-auto[style*="max-height: 500px"]');
    if (messagesContainer) {
        messagesContainer.scrollTop = messagesContainer.scrollHeight;
    }
});
</script>
