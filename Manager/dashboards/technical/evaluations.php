<?php
/**
 * Technical Dashboard - Trainer Evaluations (Hybrid PHP System)
 * تقييمات المدربين - نظام هجين محدث
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper is already initialized
 */

// Get evaluations data
$evaluations = $technicalHelper->getAllEvaluations();
$trainers = $technicalHelper->getAllTrainers();
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2 flex items-center gap-3">
                <i data-lucide="star" class="w-8 h-8 text-amber-600"></i>
                تقييم المدربين
            </h1>
            <p class="text-slate-600">مراجعة وتقييم أداء المدربين - <?php echo count($evaluations); ?> تقييم</p>
        </div>
    </div>
</div>

<!-- Statistics -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-amber-500 to-orange-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="star" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo count($evaluations); ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي التقييمات</p>
    </div>
    
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="trending-up" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                if (!empty($evaluations)) {
                    echo round(array_sum(array_column($evaluations, 'rating')) / count($evaluations), 1);
                } else {
                    echo '0';
                }
            ?></span>
        </div>
        <p class="text-sm opacity-90">متوسط التقييم</p>
    </div>
    
    <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="user-check" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo count($trainers); ?></span>
        </div>
        <p class="text-sm opacity-90">المدربون</p>
    </div>
    
    <div class="bg-gradient-to-br from-rose-500 to-pink-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="award" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php 
                echo count(array_filter($evaluations, function($e) { return $e['rating'] >= 4; }));
            ?></span>
        </div>
        <p class="text-sm opacity-90">تقييمات ممتازة</p>
    </div>
</div>

<!-- Evaluations List -->
<div class="bg-white rounded-xl shadow-lg">
    <div class="p-6 border-b border-slate-200">
        <h3 class="text-xl font-bold text-slate-800 flex items-center gap-2">
            <i data-lucide="list" class="w-6 h-6 text-amber-600"></i>
            تقييمات حديثة
        </h3>
    </div>
    
    <div class="divide-y divide-slate-200">
        <?php if (empty($evaluations)): ?>
            <div class="p-16 text-center">
                <i data-lucide="star" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
                <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد تقييمات</h3>
                <p class="text-slate-500">لم يتم إضافة أي تقييمات بعد</p>
            </div>
        <?php else: ?>
            <?php foreach ($evaluations as $evaluation): ?>
                <div class="p-6 hover:bg-slate-50 transition-colors">
                    <div class="flex items-start gap-4">
                        <img src="<?php echo $evaluation['trainer_photo'] ?? ($platformBaseUrl . '/photos/default-avatar.png'); ?>" 
                             alt="<?php echo htmlspecialchars($evaluation['trainer_name']); ?>"
                             class="w-14 h-14 rounded-full object-cover border-2 border-amber-200">
                        
                        <div class="flex-1">
                            <div class="flex items-center justify-between mb-2">
                                <h4 class="text-lg font-bold text-slate-800"><?php echo htmlspecialchars($evaluation['trainer_name']); ?></h4>
                                <span class="text-sm text-slate-500"><?php echo date('Y/m/d', strtotime($evaluation['evaluation_date'])); ?></span>
                            </div>
                            
                            <div class="flex items-center gap-2 mb-3">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <i data-lucide="star" class="w-5 h-5 <?php echo $i <= $evaluation['rating'] ? 'fill-current text-amber-500' : 'text-slate-300'; ?>"></i>
                                <?php endfor; ?>
                                <span class="text-sm font-bold text-amber-600 ml-2"><?php echo $evaluation['rating']; ?>/5</span>
                            </div>
                            
                            <p class="text-slate-600 mb-3"><?php echo htmlspecialchars($evaluation['comments'] ?? 'لا يوجد تعليق'); ?></p>
                            
                            <div class="flex items-center gap-4 text-sm">
                                <div class="flex items-center gap-2 text-slate-500">
                                    <i data-lucide="user" class="w-4 h-4"></i>
                                    <span><?php echo htmlspecialchars($evaluation['evaluator_name']); ?></span>
                                </div>
                                <div class="flex items-center gap-2 text-slate-500">
                                    <i data-lucide="book-open" class="w-4 h-4"></i>
                                    <span><?php echo htmlspecialchars($evaluation['course_name'] ?? 'عام'); ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
lucide.createIcons();
</script>
