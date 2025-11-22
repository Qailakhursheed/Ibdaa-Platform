<?php
/**
 * Manager - Trainers Management Page
 * صفحة إدارة المدربين
 */

// معالجة الإجراءات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_trainer') {
        $name = $conn->real_escape_string($_POST['name']);
        $email = $conn->real_escape_string($_POST['email']);
        $phone = $conn->real_escape_string($_POST['phone']);
        $specialty = $conn->real_escape_string($_POST['specialty']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
        
        $insert = $conn->query("INSERT INTO users (name, email, phone, password, role, status) VALUES ('$name', '$email', '$phone', '$password', 'trainer', 'active')");
        if ($insert) {
            header('Location: ?page=trainers&msg=added');
            exit;
        }
    }
    
    if ($action === 'update_status') {
        $id = (int)$_POST['trainer_id'];
        $status = $conn->real_escape_string($_POST['status']);
        $conn->query("UPDATE users SET status = '$status' WHERE id = $id AND role = 'trainer'");
        header('Location: ?page=trainers&msg=updated');
        exit;
    }
}

// جلب قائمة المدربين
$trainers = [];
$query = "SELECT u.id, u.name, u.email, u.phone, u.created_at, u.status,
        COUNT(DISTINCT c.id) as courses_count,
        COUNT(DISTINCT e.user_id) as students_count
        FROM users u
        LEFT JOIN courses c ON u.id = c.trainer_id
        LEFT JOIN enrollments e ON c.id = e.course_id
        WHERE u.role = 'trainer'
        GROUP BY u.id
        ORDER BY u.created_at DESC";

try {
    $result = $conn->query($query);
    if ($result) {
        while ($row = $result->fetch_assoc()) {
            $trainers[] = $row;
        }
    }
} catch (Exception $e) {
    error_log("Trainers query error: " . $e->getMessage());
}

// إحصائيات
$stats = [
    'total' => count($trainers),
    'active' => 0,
    'total_courses' => 0,
    'total_students' => 0
];

foreach ($trainers as $trainer) {
    if ($trainer['status'] === 'active') {
        $stats['active']++;
    }
    $stats['total_courses'] += (int)$trainer['courses_count'];
    $stats['total_students'] += (int)$trainer['students_count'];
}
?>

<!-- Success Message -->
<?php if (isset($_GET['msg'])): ?>
<div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg mb-4">
    <?php
    $messages = [
        'added' => 'تم إضافة المدرب بنجاح',
        'updated' => 'تم تحديث البيانات بنجاح',
        'deleted' => 'تم حذف المدرب بنجاح'
    ];
    echo $messages[$_GET['msg']] ?? 'تمت العملية بنجاح';
    ?>
</div>
<?php endif; ?>

<!-- Stats Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-6">
    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-blue-50 text-blue-600">
                <i data-lucide="users" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي المدربين</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['total']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i data-lucide="user-check" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">المدربون النشطون</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['active']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-sky-50 text-sky-600">
                <i data-lucide="book" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي الدورات</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['total_courses']; ?></p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm p-6 border border-slate-100">
        <div class="flex items-center justify-between mb-2">
            <div class="p-3 rounded-xl bg-purple-50 text-purple-600">
                <i data-lucide="graduation-cap" class="w-6 h-6"></i>
            </div>
        </div>
        <p class="text-sm text-slate-500 mb-1">إجمالي الطلاب</p>
        <p class="text-3xl font-bold text-slate-800"><?php echo $stats['total_students']; ?></p>
    </div>
</div>

<!-- Trainers List -->
<div class="bg-white rounded-2xl shadow-sm border border-slate-100 overflow-hidden">
    <div class="p-6 border-b border-slate-200 flex justify-between items-center">
        <h3 class="text-xl font-bold text-slate-800">قائمة المدربين</h3>
        <button onclick="document.getElementById('addTrainerModal').classList.remove('hidden')" class="px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
            <i data-lucide="user-plus" class="w-4 h-4 inline"></i>
            إضافة مدرب
        </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6 p-6">
        <?php if (!empty($trainers)): ?>
            <?php foreach ($trainers as $trainer): ?>
                <div class="bg-white border border-slate-200 rounded-xl p-6 hover:shadow-md transition">
                    <div class="flex items-start justify-between mb-4">
                        <div class="w-16 h-16 rounded-full bg-gradient-to-br from-blue-100 to-blue-50 flex items-center justify-center">
                            <i data-lucide="user" class="w-8 h-8 text-blue-600"></i>
                        </div>
                        <span class="px-3 py-1 text-xs rounded-full <?php echo $trainer['status'] === 'active' ? 'bg-emerald-100 text-emerald-700' : 'bg-slate-100 text-slate-700'; ?>">
                            <?php echo $trainer['status'] === 'active' ? 'نشط' : 'غير نشط'; ?>
                        </span>
                    </div>
                    
                    <h4 class="text-lg font-bold text-slate-800 mb-2"><?php echo htmlspecialchars($trainer['name']); ?></h4>
                    
                    <div class="space-y-2 mb-4">
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i data-lucide="mail" class="w-4 h-4"></i>
                            <?php echo htmlspecialchars($trainer['email']); ?>
                        </p>
                        <p class="text-sm text-slate-600 flex items-center gap-2">
                            <i data-lucide="phone" class="w-4 h-4"></i>
                            <?php echo htmlspecialchars($trainer['phone']); ?>
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-4 pt-4 border-t border-slate-200">
                        <div class="text-center">
                            <p class="text-xs text-slate-500">الدورات</p>
                            <p class="text-xl font-bold text-slate-800"><?php echo $trainer['courses_count']; ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-xs text-slate-500">الطلاب</p>
                            <p class="text-xl font-bold text-slate-800"><?php echo $trainer['students_count']; ?></p>
                        </div>
                    </div>

                    <div class="mt-4 flex gap-2">
                        <a href="?page=chat&user=<?php echo $trainer['id']; ?>" class="flex-1 px-3 py-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100 transition text-center text-sm">
                            <i data-lucide="message-circle" class="w-4 h-4 inline"></i>
                            مراسلة
                        </a>
                        <form method="POST" class="flex-1" onsubmit="return confirm('هل أنت متأكد؟')">
                            <input type="hidden" name="action" value="update_status">
                            <input type="hidden" name="trainer_id" value="<?php echo $trainer['id']; ?>">
                            <input type="hidden" name="status" value="<?php echo $trainer['status'] === 'active' ? 'inactive' : 'active'; ?>">
                            <button type="submit" class="w-full px-3 py-2 <?php echo $trainer['status'] === 'active' ? 'bg-amber-50 text-amber-600 hover:bg-amber-100' : 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100'; ?> rounded-lg transition text-sm">
                                <?php echo $trainer['status'] === 'active' ? 'تعطيل' : 'تفعيل'; ?>
                            </button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-span-3 text-center py-12">
                <i data-lucide="inbox" class="w-16 h-16 text-slate-300 mx-auto mb-4"></i>
                <p class="text-slate-500">لا يوجد مدربون</p>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Add Trainer Modal -->
<div id="addTrainerModal" class="hidden fixed inset-0 bg-slate-900/60 backdrop-blur-sm flex items-center justify-center px-4 z-50">
    <div class="bg-white w-full max-w-md rounded-2xl shadow-2xl overflow-hidden">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 bg-slate-50">
            <h3 class="text-xl font-bold text-slate-800">إضافة مدرب جديد</h3>
            <button onclick="document.getElementById('addTrainerModal').classList.add('hidden')" class="p-2 rounded-lg hover:bg-slate-200 transition">
                <i data-lucide="x" class="w-5 h-5"></i>
            </button>
        </div>
        <form method="POST" class="p-6 space-y-4">
            <input type="hidden" name="action" value="add_trainer">
            
            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">الاسم الكامل</label>
                <input type="text" name="name" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">البريد الإلكتروني</label>
                <input type="email" name="email" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">رقم الهاتف</label>
                <input type="tel" name="phone" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">التخصص</label>
                <input type="text" name="specialty" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>

            <div>
                <label class="block text-sm font-medium text-slate-700 mb-2">كلمة المرور</label>
                <input type="password" name="password" required class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>

            <div class="flex gap-3 pt-4">
                <button type="submit" class="flex-1 px-4 py-2 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition">
                    <i data-lucide="check" class="w-4 h-4 inline"></i>
                    إضافة
                </button>
                <button type="button" onclick="document.getElementById('addTrainerModal').classList.add('hidden')" class="flex-1 px-4 py-2 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
if (typeof lucide !== 'undefined') {
    lucide.createIcons();
}
</script>
