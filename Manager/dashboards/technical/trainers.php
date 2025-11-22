<?php
/**
 * Technical Dashboard - Trainers Management (Hybrid PHP System)
 * إدارة المدربين - نظام هجين محدث
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper and $stats are already initialized
 */

// Get trainers data
$trainers = $technicalHelper->getAllTrainers();
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2 flex items-center gap-3">
                <i data-lucide="user-check" class="w-8 h-8 text-emerald-600"></i>
                إدارة المدربين
            </h1>
            <p class="text-slate-600">عرض وتقييم أداء جميع المدربين - <?php echo count($trainers); ?> مدرب</p>
        </div>
        <button onclick="openAddTrainer()" class="px-6 py-3 bg-emerald-600 text-white rounded-xl hover:bg-emerald-700 transition-colors font-semibold flex items-center gap-2 shadow-lg">
            <i data-lucide="user-plus" class="w-5 h-5"></i>
            إضافة مدرب جديد
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="user-check" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo count($trainers); ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي المدربين</p>
    </div>
    
    <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="book-open" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo $stats['total_courses']; ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي الدورات</p>
    </div>
    
    <div class="bg-gradient-to-br from-violet-500 to-purple-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="users" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo $stats['total_students']; ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي الطلاب</p>
    </div>
    
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="star" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                $avgRating = 0;
                $ratingCount = 0;
                foreach ($trainers as $t) {
                    if ($t['avg_rating']) {
                        $avgRating += $t['avg_rating'];
                        $ratingCount++;
                    }
                }
                echo $ratingCount > 0 ? round($avgRating / $ratingCount, 1) : '0';
            ?></span>
        </div>
        <p class="text-sm opacity-90">متوسط التقييم</p>
    </div>
</div>

<!-- Trainers Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($trainers)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-xl shadow-lg">
            <i data-lucide="users" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا يوجد مدربون</h3>
            <p class="text-slate-500 mb-6">لم يتم إضافة أي مدرب بعد</p>
            <button onclick="openAddTrainer()" class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 transition-colors">
                إضافة مدرب الآن
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($trainers as $trainer): ?>
            <div class="bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden">
                <div class="bg-gradient-to-br from-emerald-500 to-teal-600 p-6 text-white">
                    <div class="flex items-center gap-4 mb-4">
                        <img src="<?php echo $trainer['photo'] ?? ($platformBaseUrl . '/photos/default-avatar.png'); ?>" 
                             alt="<?php echo htmlspecialchars($trainer['full_name']); ?>"
                             class="w-16 h-16 rounded-full object-cover border-4 border-white shadow-lg">
                        <div class="flex-1">
                            <h3 class="text-xl font-bold"><?php echo htmlspecialchars($trainer['full_name']); ?></h3>
                            <p class="text-sm opacity-90"><?php echo htmlspecialchars($trainer['email']); ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php 
                        $rating = round($trainer['avg_rating'] ?? 0, 1);
                        for ($i = 1; $i <= 5; $i++): ?>
                            <i data-lucide="star" class="w-5 h-5 <?php echo $i <= $rating ? 'fill-current' : 'opacity-40'; ?>"></i>
                        <?php endfor; ?>
                        <span class="font-bold ml-2"><?php echo $rating; ?></span>
                    </div>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="book-open" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo $trainer['courses_count']; ?> دورة</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo $trainer['students_count'] ?? 0; ?> طالب</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span class="text-sm">منذ <?php echo date('Y', strtotime($trainer['created_at'] ?? 'now')); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="viewTrainer(<?php echo $trainer['user_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors text-sm font-semibold">
                            عرض
                        </button>
                        <button onclick="evaluateTrainer(<?php echo $trainer['user_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-amber-50 text-amber-600 rounded-lg hover:bg-amber-100 transition-colors text-sm font-semibold">
                            تقييم
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function viewTrainer(trainerId) {
    window.location.href = `trainer_details.php?id=${trainerId}`;
}

function evaluateTrainer(trainerId) {
    window.location.href = `evaluations.php?trainer_id=${trainerId}`;
}

function openAddTrainer() {
    alert('سيتم فتح نموذج إضافة مدرب جديد');
}

lucide.createIcons();
</script>
