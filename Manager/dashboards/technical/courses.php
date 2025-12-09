<?php
/**
 * Technical Dashboard - Courses Management (Hybrid PHP System)
 * إدارة الدورات التدريبية - نظام هجين محدث
 * 
 * This file is included in technical-dashboard.php
 * $technicalHelper is already initialized
 */

// Get courses data
$courses = $technicalHelper->getAllCourses();
$stats = $technicalHelper->getStatistics();

// Handle course actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'create':
                $data = [
                    'course_name' => $_POST['course_name'],
                    'description' => $_POST['description'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'trainer_id' => $_POST['trainer_id'],
                    'status' => $_POST['status'] ?? 'active'
                ];
                if ($technicalHelper->createCourse($data)) {
                    $_SESSION['success_message'] = 'تم إنشاء الدورة بنجاح';
                    header('Location: courses.php');
                    exit;
                }
                break;
                
            case 'update':
                $courseId = $_POST['course_id'];
                $data = [
                    'course_name' => $_POST['course_name'],
                    'description' => $_POST['description'],
                    'start_date' => $_POST['start_date'],
                    'end_date' => $_POST['end_date'],
                    'trainer_id' => $_POST['trainer_id'],
                    'status' => $_POST['status']
                ];
                if ($technicalHelper->updateCourse($courseId, $data)) {
                    $_SESSION['success_message'] = 'تم تحديث الدورة بنجاح';
                    header('Location: courses.php');
                    exit;
                }
                break;
        }
    }
}

// Get all trainers for dropdown
$trainers = $technicalHelper->getAllTrainers();
?>

<div class="mb-8">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-3xl font-bold text-slate-800 mb-2 flex items-center gap-3">
                <i data-lucide="book-open" class="w-8 h-8 text-sky-600"></i>
                إدارة الدورات التدريبية
            </h1>
            <p class="text-slate-600">مراجعة وإدارة جميع الدورات في النظام - <?php echo count($courses); ?> دورة</p>
        </div>
        <button onclick="openAddCourseModal()" class="px-6 py-3 bg-sky-600 text-white rounded-xl hover:bg-sky-700 transition-colors font-semibold flex items-center gap-2 shadow-lg">
            <i data-lucide="plus" class="w-5 h-5"></i>
            إضافة دورة جديدة
        </button>
    </div>
</div>

<!-- Statistics Cards -->
<div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
    <div class="bg-gradient-to-br from-sky-500 to-blue-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="book-open" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo $stats['total_courses']; ?></span>
        </div>
        <p class="text-sm opacity-90">إجمالي الدورات</p>
    </div>
    
    <div class="bg-gradient-to-br from-emerald-500 to-teal-600 rounded-xl shadow-lg p-6 text-white">
        <div class="flex items-center justify-between mb-4">
            <i data-lucide="play-circle" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo $stats['active_courses']; ?></span>
        </div>
        <p class="text-sm opacity-90">دورات نشطة</p>
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
            <i data-lucide="user-check" class="w-10 h-10 opacity-80"></i>
            <span class="text-4xl font-bold"><?php echo $stats['total_trainers']; ?></span>
        </div>
        <p class="text-sm opacity-90">المدربون</p>
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
                    class="w-full pr-10 pl-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"
                    onkeyup="filterCourses()">
            </div>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
            <select id="filterStatus" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterCourses()">
                <option value="">جميع الحالات</option>
                <option value="active">نشط</option>
                <option value="completed">مكتمل</option>
                <option value="upcoming">قادم</option>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">المدرب</label>
            <select id="filterTrainer" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterCourses()">
                <option value="">جميع المدربين</option>
                <?php foreach ($trainers as $trainer): ?>
                    <option value="<?php echo $trainer['user_id']; ?>"><?php echo htmlspecialchars($trainer['full_name']); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-sm font-semibold text-slate-700 mb-2">الترتيب</label>
            <select id="sortBy" class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent" onchange="filterCourses()">
                <option value="date_desc">الأحدث أولاً</option>
                <option value="date_asc">الأقدم أولاً</option>
                <option value="name_asc">الاسم (أ-ي)</option>
                <option value="students_desc">عدد الطلاب</option>
            </select>
        </div>
    </div>
</div>

<!-- Courses Grid -->
<div id="coursesContainer" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (empty($courses)): ?>
        <div class="col-span-full text-center py-16 bg-white rounded-xl shadow-lg">
            <i data-lucide="book-open" class="w-16 h-16 mx-auto text-slate-300 mb-4"></i>
            <h3 class="text-xl font-bold text-slate-800 mb-2">لا توجد دورات</h3>
            <p class="text-slate-500 mb-6">ابدأ بإضافة دورة جديدة</p>
            <button onclick="openAddCourseModal()" class="px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors">
                إضافة دورة الآن
            </button>
        </div>
    <?php else: ?>
        <?php foreach ($courses as $course): 
            $statusColors = [
                'active' => 'bg-emerald-100 text-emerald-700',
                'completed' => 'bg-slate-100 text-slate-700',
                'upcoming' => 'bg-amber-100 text-amber-700'
            ];
            $statusNames = [
                'active' => 'نشط',
                'completed' => 'مكتمل',
                'upcoming' => 'قادم'
            ];
        ?>
            <div class="course-card bg-white rounded-xl shadow-lg hover:shadow-xl transition-all duration-300 overflow-hidden" 
                 data-course-name="<?php echo strtolower($course['course_name']); ?>"
                 data-status="<?php echo $course['status']; ?>"
                 data-trainer="<?php echo $course['trainer_id']; ?>">
                <div class="bg-gradient-to-br from-sky-500 to-blue-600 p-6 text-white">
                    <div class="flex items-start justify-between mb-4">
                        <span class="px-3 py-1 <?php echo $statusColors[$course['status']] ?? 'bg-slate-100 text-slate-700'; ?> rounded-full text-xs font-semibold">
                            <?php echo $statusNames[$course['status']] ?? $course['status']; ?>
                        </span>
                        <i data-lucide="book-open" class="w-8 h-8 opacity-80"></i>
                    </div>
                    <h3 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h3>
                    <p class="text-sm opacity-90 line-clamp-2"><?php echo htmlspecialchars($course['description'] ?? ''); ?></p>
                </div>
                
                <div class="p-6">
                    <div class="space-y-3 mb-6">
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="user" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo htmlspecialchars($course['trainer_name']); ?></span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="users" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo $course['students_count']; ?> طالب</span>
                        </div>
                        <div class="flex items-center gap-2 text-slate-600">
                            <i data-lucide="calendar" class="w-4 h-4"></i>
                            <span class="text-sm"><?php echo date('Y/m/d', strtotime($course['start_date'])); ?></span>
                        </div>
                    </div>
                    
                    <div class="flex gap-2">
                        <button onclick="viewCourse(<?php echo $course['course_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-sky-50 text-sky-600 rounded-lg hover:bg-sky-100 transition-colors text-sm font-semibold">
                            عرض
                        </button>
                        <button onclick="editCourse(<?php echo $course['course_id']; ?>)" 
                            class="flex-1 px-4 py-2 bg-emerald-50 text-emerald-600 rounded-lg hover:bg-emerald-100 transition-colors text-sm font-semibold">
                            تعديل
                        </button>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Add Course Modal -->
<div id="addCourseModal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl max-w-2xl w-full max-h-[90vh] overflow-y-auto">
        <div class="bg-gradient-to-r from-sky-600 to-blue-600 text-white p-6 rounded-t-2xl">
            <h2 class="text-2xl font-bold">إضافة دورة جديدة</h2>
        </div>
        
        <form method="POST" class="p-6">
            <input type="hidden" name="action" value="create">
            
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">اسم الدورة *</label>
                    <input type="text" name="course_name" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">الوصف</label>
                    <textarea name="description" rows="3"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent"></textarea>
                </div>
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">تاريخ البدء *</label>
                        <input type="date" name="start_date" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">تاريخ الانتهاء *</label>
                        <input type="date" name="end_date" required
                            class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                    </div>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">المدرب *</label>
                    <select name="trainer_id" required
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="">اختر المدرب</option>
                        <?php foreach ($trainers as $trainer): ?>
                            <option value="<?php echo $trainer['user_id']; ?>"><?php echo htmlspecialchars($trainer['full_name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-semibold text-slate-700 mb-2">الحالة</label>
                    <select name="status"
                        class="w-full px-4 py-2 border border-slate-300 rounded-lg focus:ring-2 focus:ring-sky-500 focus:border-transparent">
                        <option value="upcoming">قادم</option>
                        <option value="active">نشط</option>
                        <option value="completed">مكتمل</option>
                    </select>
                </div>
            </div>
            
            <div class="flex gap-3 mt-6">
                <button type="submit" class="flex-1 px-6 py-3 bg-sky-600 text-white rounded-lg hover:bg-sky-700 transition-colors font-semibold">
                    إضافة الدورة
                </button>
                <button type="button" onclick="closeAddCourseModal()" class="px-6 py-3 bg-slate-200 text-slate-700 rounded-lg hover:bg-slate-300 transition-colors font-semibold">
                    إلغاء
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddCourseModal() {
    document.getElementById('addCourseModal').classList.remove('hidden');
}

function closeAddCourseModal() {
    document.getElementById('addCourseModal').classList.add('hidden');
}

function filterCourses() {
    const search = document.getElementById('searchCourses').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;
    const trainer = document.getElementById('filterTrainer').value;
    const sortBy = document.getElementById('sortBy').value;
    
    const cards = Array.from(document.querySelectorAll('.course-card'));
    
    // Filter
    cards.forEach(card => {
        const name = card.dataset.courseName;
        const cardStatus = card.dataset.status;
        const cardTrainer = card.dataset.trainer;
        
        const matchSearch = !search || name.includes(search);
        const matchStatus = !status || cardStatus === status;
        const matchTrainer = !trainer || cardTrainer === trainer;
        
        if (matchSearch && matchStatus && matchTrainer) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
    
    // Sort
    const container = document.getElementById('coursesContainer');
    cards.sort((a, b) => {
        switch(sortBy) {
            case 'name_asc':
                return a.dataset.courseName.localeCompare(b.dataset.courseName);
            default:
                return 0;
        }
    });
    
    cards.forEach(card => container.appendChild(card));
}

function viewCourse(courseId) {
    window.location.href = `?page=courses&id=${courseId}`;
}

function editCourse(courseId) {
    // Will implement edit modal
    alert('تحرير الدورة #' + courseId);
}

lucide.createIcons();

<?php if (isset($_SESSION['success_message'])): ?>
    alert('<?php echo $_SESSION['success_message']; ?>');
    <?php unset($_SESSION['success_message']); ?>
<?php endif; ?>
</script>
