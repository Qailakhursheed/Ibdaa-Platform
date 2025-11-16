<?php
/**
 * Technical Dashboard - Courses Management
 * إدارة الدورات التدريبية
 */

// Get courses from database
$courses = [];
try {
    $stmt = $conn->query("SELECT c.*, 
        u.full_name as trainer_name,
        COUNT(DISTINCT e.id) as enrolled_count
        FROM courses c
        LEFT JOIN users u ON c.trainer_id = u.id
        LEFT JOIN enrollments e ON c.course_id = e.course_id
        GROUP BY c.course_id
        ORDER BY c.created_at DESC");
    $courses = $stmt->fetch_all(MYSQLI_ASSOC);
} catch (Exception $e) {
    error_log("Courses fetch error: " . $e->getMessage());
}
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2">إدارة الدورات التدريبية</h1>
            <p class="text-slate-600">مراجعة وإدارة جميع الدورات في النظام</p>
        </div>
        <button onclick="openAddCourseModal()" class="px-6 py-3 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition-colors font-semibold flex items-center gap-2 shadow-lg">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إضافة دورة جديدة
        </button>
    </div>
</div>

<!-- Filters -->
<div class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">البحث</label>
            <div class="relative">
                <i data-lucide="search" class="w-5 h-5 absolute right-3 top-1/2 -translate-y-1/2 text-slate-400"></i>
                <input type="text" id="searchCourses" placeholder="ابحث عن دورة..." 
                    class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
            <select id="filterStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                <option value="">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="pending">معلق</option>
                <option value="completed">مكتمل</option>
                <option value="cancelled">ملغي</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">المدرب</label>
            <select id="filterTrainer" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                <option value="">جميع المدربين</option>
                <?php
                $trainers = $conn->query("SELECT id, full_name FROM users WHERE role = 'trainer' ORDER BY full_name");
                while ($trainer = $trainers->fetch_assoc()) {
                    echo '<option value="' . $trainer['id'] . '">' . htmlspecialchars($trainer['full_name']) . '</option>';
                }
                ?>
            </select>
        </div>
        <div class="flex items-end">
            <button onclick="applyFilters()" class="w-full px-4 py-2 bg-slate-600 text-white rounded-lg hover:bg-slate-700 transition-colors font-semibold">
                تطبيق الفلاتر
            </button>
        </div>
    </div>
</div>

<!-- Courses Grid -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6" id="coursesGrid">
    <?php if (empty($courses)): ?>
        <div class="col-span-full text-center py-16">
            <i data-lucide="inbox" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد دورات بعد</h3>
            <p class="text-slate-500 mb-6">ابدأ بإضافة دورة تدريبية جديدة</p>
            <button onclick="openAddCourseModal()" class="px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                إضافة دورة الآن
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $course): ?>
            <div class="bg-white rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow course-card" 
                 data-status="<?php echo $course['status']; ?>" 
                 data-trainer="<?php echo $course['trainer_id']; ?>">
                <!-- Course Image -->
                <div class="relative h-48 bg-gradient-to-br from-sky-400 to-blue-600 flex items-center justify-center">
                    <?php if (!empty($course['cover_image'])): ?>
                        <img src="<?php echo htmlspecialchars($course['cover_image']); ?>" 
                             alt="<?php echo htmlspecialchars($course['title']); ?>" 
                             class="w-full h-full object-cover">
                    <?php else: ?>
                        <i data-lucide="book-open" class="w-16 h-16 text-white opacity-50"></i>
                    <?php endif; ?>
                    
                    <!-- Status Badge -->
                    <span class="absolute top-4 right-4 px-3 py-1 rounded-full text-xs font-bold
                        <?php 
                        echo $course['status'] === 'active' ? 'bg-emerald-500 text-white' : 
                             ($course['status'] === 'pending' ? 'bg-amber-500 text-white' : 
                             ($course['status'] === 'completed' ? 'bg-blue-500 text-white' : 'bg-red-500 text-white'));
                        ?>">
                        <?php 
                        echo $course['status'] === 'active' ? 'نشط' : 
                             ($course['status'] === 'pending' ? 'معلق' : 
                             ($course['status'] === 'completed' ? 'مكتمل' : 'ملغي'));
                        ?>
                    </span>
                </div>
                
                <!-- Course Content -->
                <div class="p-6">
                    <h3 class="text-xl font-bold text-slate-800 mb-2 line-clamp-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                    <p class="text-slate-600 text-sm mb-4 line-clamp-3"><?php echo htmlspecialchars($course['short_desc'] ?? $course['description'] ?? 'لا يوجد وصف'); ?></p>
                    
                    <!-- Course Meta -->
                    <div class="flex items-center gap-4 text-sm text-slate-500 mb-4">
                        <span class="flex items-center gap-1">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <?php echo htmlspecialchars($course['trainer_name'] ?? 'لم يحدد'); ?>
                        </span>
                        <span class="flex items-center gap-1">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <?php echo $course['enrolled_count']; ?> طالب
                        </span>
                    </div>
                    
                    <div class="flex items-center gap-4 text-sm text-slate-500 mb-4">
                        <span class="flex items-center gap-1">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <?php echo date('Y/m/d', strtotime($course['start_date'] ?? $course['created_at'])); ?>
                        </span>
                        <span class="flex items-center gap-1 font-semibold text-emerald-600">
                            <i data-lucide="tag" class="w-4 h-4"></i>
                            <?php echo number_format($course['price'] ?? 0); ?> ريال
                        </span>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="flex items-center gap-2 pt-4 border-t border-slate-200">
                        <button onclick="viewCourse(<?php echo $course['course_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100 transition-colors font-semibold text-sm">
                            عرض التفاصيل
                        </button>
                        <button onclick="editCourse(<?php echo $course['course_id']; ?>)" 
                            class="p-2 bg-slate-50 text-slate-600 rounded-lg hover:bg-slate-100 transition-colors">
                            <i data-lucide="edit" class="w-5 h-5"></i>
                        </button>
                        <?php if ($course['status'] === 'pending'): ?>
                        <button onclick="approveCourse(<?php echo $course['course_id']; ?>)" 
                            class="p-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors" title="الموافقة">
                            <i data-lucide="check" class="w-5 h-5"></i>
                        </button>
                        <button onclick="rejectCourse(<?php echo $course['course_id']; ?>)" 
                            class="p-2 bg-red-50 text-red-600 rounded-lg hover:bg-red-100 transition-colors" title="الرفض">
                            <i data-lucide="x" class="w-5 h-5"></i>
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
lucide.createIcons();

// Search functionality
document.getElementById('searchCourses')?.addEventListener('input', function(e) {
    const searchTerm = e.target.value.toLowerCase();
    const cards = document.querySelectorAll('.course-card');
    
    cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('p').textContent.toLowerCase();
        
        if (title.includes(searchTerm) || description.includes(searchTerm)) {
            card.style.display = 'block';
        } else {
            card.style.display = 'none';
        }
    });
});

// Filter functionality
function applyFilters() {
    const status = document.getElementById('filterStatus').value;
    const trainer = document.getElementById('filterTrainer').value;
    const cards = document.querySelectorAll('.course-card');
    
    cards.forEach(card => {
        const cardStatus = card.dataset.status;
        const cardTrainer = card.dataset.trainer;
        
        const statusMatch = !status || cardStatus === status;
        const trainerMatch = !trainer || cardTrainer === trainer;
        
        card.style.display = statusMatch && trainerMatch ? 'block' : 'none';
    });
}

// Course actions
function viewCourse(courseId) {
    window.location.href = `?page=courses&view=${courseId}`;
}

function editCourse(courseId) {
    window.location.href = `?page=courses&edit=${courseId}`;
}

async function approveCourse(courseId) {
    if (!confirm('هل أنت متأكد من الموافقة على هذه الدورة؟')) return;
    
    try {
        const response = await fetch('../api/courses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'approve', course_id: courseId })
        });
        
        const data = await response.json();
        if (data.success) {
            DashboardIntegration.ui.showToast('تمت الموافقة على الدورة بنجاح', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        DashboardIntegration.ui.showToast('فشلت الموافقة على الدورة', 'error');
    }
}

async function rejectCourse(courseId) {
    const reason = prompt('يرجى إدخال سبب الرفض:');
    if (!reason) return;
    
    try {
        const response = await fetch('../api/courses.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reject', course_id: courseId, reason })
        });
        
        const data = await response.json();
        if (data.success) {
            DashboardIntegration.ui.showToast('تم رفض الدورة', 'success');
            setTimeout(() => location.reload(), 1500);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        DashboardIntegration.ui.showToast('فشل رفض الدورة', 'error');
    }
}

function openAddCourseModal() {
    DashboardIntegration.ui.showToast('سيتم فتح نموذج إضافة دورة قريباً', 'info');
}
</script>
