<?php
/**
 * Setup Messages Table
 * إعداد جدول الرسائل
 */

require_once '../config/database.php';

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>إعداد جدول الرسائل</title>
    <script src='https://cdn.tailwindcss.com'></script>
</head>
<body class='bg-gray-100 p-8'>
    <div class='max-w-4xl mx-auto bg-white rounded-lg shadow-lg p-8'>
        <h1 class='text-3xl font-bold text-center mb-8 text-blue-600'>إعداد جدول الرسائل</h1>
        <div class='space-y-4'>";

$success = true;
$messages = [];

// 1. Create messages table
echo "<div class='border-l-4 border-blue-500 pl-4 py-2'>";
echo "<h2 class='font-bold text-lg mb-2'>1. إنشاء جدول messages...</h2>";

$sql = "CREATE TABLE IF NOT EXISTS `messages` (
  `message_id` int(11) NOT NULL AUTO_INCREMENT,
  `sender_id` int(11) NOT NULL,
  `recipient_id` int(11) NOT NULL,
  `message` text NOT NULL,
  `is_read` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `read_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`message_id`),
  KEY `idx_sender` (`sender_id`),
  KEY `idx_recipient` (`recipient_id`),
  KEY `idx_is_read` (`is_read`),
  KEY `idx_created_at` (`created_at`),
  KEY `idx_conversation` (`sender_id`, `recipient_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

if ($conn->query($sql) === TRUE) {
    echo "<p class='text-green-600'>✅ تم إنشاء جدول messages بنجاح</p>";
} else {
    echo "<p class='text-red-600'>❌ خطأ في إنشاء الجدول: " . $conn->error . "</p>";
    $success = false;
}
echo "</div>";

// 2. Add Foreign Keys
echo "<div class='border-l-4 border-green-500 pl-4 py-2 mt-4'>";
echo "<h2 class='font-bold text-lg mb-2'>2. إضافة Foreign Keys...</h2>";

// Check if foreign keys already exist
$fk_check = $conn->query("
    SELECT CONSTRAINT_NAME 
    FROM information_schema.TABLE_CONSTRAINTS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'messages' 
    AND CONSTRAINT_NAME IN ('messages_sender_fk', 'messages_recipient_fk')
");

if ($fk_check && $fk_check->num_rows == 0) {
    $fk_sql = "ALTER TABLE `messages`
      ADD CONSTRAINT `messages_sender_fk` FOREIGN KEY (`sender_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
      ADD CONSTRAINT `messages_recipient_fk` FOREIGN KEY (`recipient_id`) REFERENCES `users` (`id`) ON DELETE CASCADE";
    
    if ($conn->query($fk_sql) === TRUE) {
        echo "<p class='text-green-600'>✅ تم إضافة Foreign Keys بنجاح</p>";
    } else {
        echo "<p class='text-yellow-600'>⚠️ تحذير: " . $conn->error . "</p>";
    }
} else {
    echo "<p class='text-blue-600'>ℹ️ Foreign Keys موجودة مسبقاً</p>";
}
echo "</div>";

// 3. Create view for latest conversations
echo "<div class='border-l-4 border-purple-500 pl-4 py-2 mt-4'>";
echo "<h2 class='font-bold text-lg mb-2'>3. إنشاء View للمحادثات الأخيرة...</h2>";

$view_sql = "CREATE OR REPLACE VIEW latest_conversations AS
SELECT 
    m.message_id,
    m.sender_id,
    m.recipient_id,
    m.message,
    m.is_read,
    m.created_at,
    sender.full_name as sender_name,
    recipient.full_name as recipient_name,
    sender.photo as sender_photo,
    recipient.photo as recipient_photo
FROM messages m
INNER JOIN users sender ON m.sender_id = sender.id
INNER JOIN users recipient ON m.recipient_id = recipient.id
WHERE m.message_id IN (
    SELECT MAX(message_id) 
    FROM messages 
    GROUP BY 
        LEAST(sender_id, recipient_id),
        GREATEST(sender_id, recipient_id)
)
ORDER BY m.created_at DESC";

if ($conn->query($view_sql) === TRUE) {
    echo "<p class='text-green-600'>✅ تم إنشاء View بنجاح</p>";
} else {
    echo "<p class='text-yellow-600'>⚠️ تحذير: " . $conn->error . "</p>";
}
echo "</div>";

// 4. Insert sample data
echo "<div class='border-l-4 border-amber-500 pl-4 py-2 mt-4'>";
echo "<h2 class='font-bold text-lg mb-2'>4. إضافة بيانات تجريبية...</h2>";

// Check if there are any users
$user_check = $conn->query("SELECT id FROM users LIMIT 2");
if ($user_check && $user_check->num_rows >= 2) {
    $users = $user_check->fetch_all(MYSQLI_ASSOC);
    
    // Check if messages already exist
    $msg_check = $conn->query("SELECT COUNT(*) as count FROM messages");
    $msg_count = $msg_check->fetch_assoc()['count'];
    
    if ($msg_count == 0) {
        $sample_messages = [
            ['sender' => $users[0]['id'], 'recipient' => $users[1]['id'], 'message' => 'مرحباً! كيف يمكنني مساعدتك؟'],
            ['sender' => $users[1]['id'], 'recipient' => $users[0]['id'], 'message' => 'شكراً، أحتاج استفسار عن الدورة'],
            ['sender' => $users[0]['id'], 'recipient' => $users[1]['id'], 'message' => 'بالتأكيد، تفضل بالسؤال']
        ];
        
        $stmt = $conn->prepare("INSERT INTO messages (sender_id, recipient_id, message, created_at) VALUES (?, ?, ?, NOW())");
        
        foreach ($sample_messages as $msg) {
            $stmt->bind_param("iis", $msg['sender'], $msg['recipient'], $msg['message']);
            $stmt->execute();
        }
        
        echo "<p class='text-green-600'>✅ تم إضافة " . count($sample_messages) . " رسائل تجريبية</p>";
    } else {
        echo "<p class='text-blue-600'>ℹ️ يوجد " . $msg_count . " رسالة في النظام</p>";
    }
} else {
    echo "<p class='text-yellow-600'>⚠️ لا يوجد مستخدمين كافيين لإضافة بيانات تجريبية</p>";
}
echo "</div>";

// Final summary
echo "<div class='mt-8 p-6 rounded-lg " . ($success ? 'bg-green-50 border-2 border-green-500' : 'bg-red-50 border-2 border-red-500') . "'>";
if ($success) {
    echo "<h2 class='text-2xl font-bold text-green-700 mb-4'>✅ تم الإعداد بنجاح!</h2>";
    echo "<p class='text-green-600 mb-4'>جدول الرسائل جاهز للاستخدام.</p>";
    echo "<a href='../Manager/dashboards/manager-dashboard.php?page=chat' class='inline-block px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 transition'>
        انتقل إلى نظام المحادثات
    </a>";
} else {
    echo "<h2 class='text-2xl font-bold text-red-700 mb-4'>❌ حدثت بعض الأخطاء</h2>";
    echo "<p class='text-red-600'>يرجى مراجعة الأخطاء أعلاه وإعادة المحاولة.</p>";
}
echo "</div>";

echo "    </div>
    </div>
</body>
</html>";

$conn->close();
?>
