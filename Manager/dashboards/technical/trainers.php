<?php
/**
 * Technical Dashboard - Trainers Management
 * إدارة المدربين
 */

// Get trainers
$trainers = [];
try {
    $stmt = $conn->query("SELECT u.*, 
        COUNT(DISTINCT c.course_id) as courses_count,
        AVG(e.rating) as avg_rating
        FROM users u
        LEFT JOIN courses c ON u.id = c.trainer_id
        LEFT JOIN trainer_evaluations e ON u.id = e.trainer_id
        WHERE u.role = 'trainer'
        GROUP BY u.id
        ORDER BY u.full_name");
    $trainers = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Trainers fetch error: " . $e->getMessage());
}
?>

<div class="mb-8">
    <h1 class="text-3xl font-bold text-slate-800 mb-2">إدارة المدربين</h1>
    <p class="text-slate-600">عرض وتقييم أداء جميع المدربين</p>
</div>

<!-- Trainers Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($trainers)): ?>
        <div class="col-span-full text-center py-16">
            <i data-lucide="users" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا يوجد مدربون</h3>
            <p class="text-slate-500">لم يتم إضافة أي مدرب بعد</p>
        </div>
    <?php else: ?>
        <?php foreach ($trainers as $trainer): ?>
            <div class="bg-white rounded-xl shadow-lg p-6 hover:shadow-xl transition-shadow">
                <div class="flex items-start gap-4 mb-4">
                    <img src="<?php echo $trainer['photo'] ?? '../platform/photos/default-avatar.png'; ?>" 
                         alt="<?php echo htmlspecialchars($trainer['full_name']); ?>"
                         class="w-16 h-16 rounded-full object-cover border-4 border-emerald-100">
                    <div class="flex-1">
                        <h3 class="text-xl font-bold text-slate-800"><?php echo htmlspecialchars($trainer['full_name']); ?></h3>
                        <p class="text-sm text-slate-500"><?php echo htmlspecialchars($trainer['email']); ?></p>
                    </div>
                </div>

                <!-- Stats -->
                <div class="grid grid-cols-2 gap-4 mb-4">
                    <div class="text-center p-3 bg-sky-50 rounded-lg">
                        <p class="text-2xl font-bold text-sky-600"><?php echo $trainer['courses_count']; ?></p>
                        <p class="text-xs text-slate-600">دورة</p>
                    </div>
                    <div class="text-center p-3 bg-amber-50 rounded-lg">
                        <p class="text-2xl font-bold text-amber-600">
                            <?php echo $trainer['avg_rating'] ? number_format($trainer['avg_rating'], 1) : '-'; ?>
                        </p>
                        <p class="text-xs text-slate-600">التقييم</p>
                    </div>
                </div>

                <!-- Actions -->
                <div class="flex gap-2">
                    <button onclick="viewTrainerProfile(<?php echo $trainer['id']; ?>)"
                        class="flex-1 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors font-semibold text-sm">
                        عرض الملف
                    </button>
                    <button onclick="evaluateTrainer(<?php echo $trainer['id']; ?>)"
                        class="px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors">
                        <i data-lucide="star" class="w-5 h-5"></i>
                    </button>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
lucide.createIcons();

function viewTrainerProfile(trainerId) {
    window.location.href = `?page=trainers&id=${trainerId}`;
}

function evaluateTrainer(trainerId) {
    TechnicalFeatures.ui.showEvaluationForm(trainerId);
}
</script>
