# 📘 دليل المطور - النظام الهجين
# Developer Guide - Hybrid System

## 🎯 نظرة سريعة

تم تحويل المشروع من **JavaScript معقد** إلى **نظام هجين**:
- **PHP** → المعالجة والبيانات
- **Python API** → الرسوم البيانية التفاعلية
- **JavaScript البسيط** → تحميل الرسوم فقط

---

## 🚀 البدء السريع

### 1. تشغيل Python API (للرسوم التفاعلية)

```powershell
# طريقة 1: استخدام الملف الجاهز
cd Manager\dashboards\api
.\start_server.bat

# طريقة 2: يدوياً
cd Manager\dashboards\api
pip install -r requirements.txt
python charts_api.py
```

سيعمل على: `http://localhost:5000`

### 2. استخدام Helper Classes

#### للطلاب:
```php
<?php
require_once __DIR__ . '/../includes/student_helper.php';

// إنشاء كائن المساعد
$student = new StudentHelper($conn, $userId);

// الحصول على الدورات
$courses = $student->getMyCourses();

// حساب المعدل
$gpaData = $student->getGPA();
echo "المعدل: " . $gpaData['gpa'];

// معدل الحضور
$attendance = $student->getAttendanceRate();
echo "نسبة الحضور: " . $attendance['rate'] . "%";
?>
```

#### للمدربين:
```php
<?php
require_once __DIR__ . '/../includes/trainer_helper.php';

// إنشاء كائن المساعد
$trainer = new TrainerHelper($conn, $userId);

// الحصول على دورات المدرب
$courses = $trainer->getMyCourses();

// الحصول على طلاب دورة معينة
$students = $trainer->getMyStudents($courseId);

// تحديث درجة طالب
$trainer->updateGrade($courseId, $studentId, 'final', 85);

// تسجيل الحضور
$trainer->recordAttendance($courseId, $studentId, 'present');
?>
```

### 3. تحميل الرسوم البيانية

```html
<!-- تضمين المكتبات المطلوبة -->
<script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
<script src="/Manager/assets/js/chart-loader.js"></script>

<!-- حاوية الرسم -->
<div id="myChart" class="h-80"></div>

<script>
// للطلاب
ChartLoader.loadStudentCoursesProgress('myChart', <?php echo $userId; ?>);
ChartLoader.loadStudentGradesOverview('myChart', <?php echo $userId; ?>);

// للمدربين
ChartLoader.loadTrainerStudentsPerformance('myChart', <?php echo $trainerId; ?>, <?php echo $courseId; ?>);
ChartLoader.loadTrainerCourseAttendance('myChart', <?php echo $courseId; ?>);

// للمدير
ChartLoader.loadStudentsStatus('myChart');
ChartLoader.loadRevenueMonthly('myChart');
</script>
```

---

## 📚 دوال StudentHelper

### إدارة الدورات
```php
// جميع دورات الطالب
$courses = $student->getMyCourses();
// Returns: array of courses with enrollment details

// تفاصيل دورة محددة
$course = $student->getCourseDetails($courseId);
// Returns: course data + enrollment info
```

### الدرجات والمعدل
```php
// جميع الدرجات
$grades = $student->getMyGrades();
// Returns: array of all grades

// درجات دورة محددة
$grades = $student->getMyGrades($courseId);
// Returns: grades for specific course

// حساب المعدل
$gpa = $student->getGPA();
// Returns: ['gpa' => 85.5, 'courses_count' => 5]
```

### الحضور
```php
// جميع سجلات الحضور
$attendance = $student->getMyAttendance();
// Returns: array of attendance records

// حضور دورة محددة
$attendance = $student->getMyAttendance($courseId);

// حساب معدل الحضور
$rate = $student->getAttendanceRate($courseId);
// Returns: ['present' => 25, 'total' => 30, 'rate' => 83.3]
```

### الواجبات
```php
// جميع الواجبات
$assignments = $student->getMyAssignments();

// واجبات دورة محددة
$assignments = $student->getMyAssignments($courseId);

// واجبات بحالة معينة
$pending = $student->getMyAssignments($courseId, 'pending');
$graded = $student->getMyAssignments($courseId, 'graded');
```

### المواد الدراسية
```php
// مواد دورة
$materials = $student->getCourseMaterials($courseId);
// Returns: array of course materials
```

### الجدول والمدفوعات
```php
// جدول الدراسة
$schedule = $student->getMySchedule();

// سجل المدفوعات
$payments = $student->getPaymentHistory();

// الرصيد الحالي
$balance = $student->getAccountBalance();
```

---

## 📚 دوال TrainerHelper

### إدارة الدورات
```php
// جميع دورات المدرب
$courses = $trainer->getMyCourses();

// تفاصيل دورة مع الإحصائيات
$course = $trainer->getCourseDetails($courseId);
// Returns: course + total_students, active_students, avg_grade
```

### إدارة الطلاب
```php
// جميع طلاب المدرب
$students = $trainer->getMyStudents();

// طلاب دورة محددة
$students = $trainer->getMyStudents($courseId);

// ملف طالب كامل
$profile = $trainer->getStudentProfile($studentId);
```

### الحضور والغياب
```php
// تقرير حضور الدورة
$report = $trainer->getCourseAttendance($courseId);

// حضور يوم محدد
$daily = $trainer->getCourseAttendance($courseId, '2025-11-21');

// تسجيل حضور
$success = $trainer->recordAttendance($courseId, $studentId, 'present');
$success = $trainer->recordAttendance($courseId, $studentId, 'absent');
```

### الدرجات
```php
// جميع درجات الدورة
$grades = $trainer->getCourseGrades($courseId);

// تحديث درجة نصفي
$trainer->updateGrade($courseId, $studentId, 'midterm', 42);

// تحديث درجة نهائي
$trainer->updateGrade($courseId, $studentId, 'final', 85);
```

### الواجبات
```php
// واجبات الدورة
$assignments = $trainer->getCourseAssignments($courseId);

// تسليمات واجب
$submissions = $trainer->getAssignmentSubmissions($assignmentId);

// تصحيح تسليم
$trainer->gradeSubmission($submissionId, 90, 'عمل ممتاز!');
```

### المواد الدراسية
```php
// مواد الدورة
$materials = $trainer->getCourseMaterials($courseId);

// رفع مادة جديدة
$trainer->uploadMaterial(
    $courseId, 
    'عنوان المادة',
    'وصف المادة',
    '/uploads/file.pdf',
    'pdf'
);
```

### الإحصائيات
```php
// إحصائيات المدرب
$stats = $trainer->getStatistics();
// Returns: 
// - total_courses
// - active_students
// - avg_grade
// - pending_grades
```

---

## 🎨 Python API Endpoints

### Manager Dashboard
```
GET /api/charts/students-status
GET /api/charts/courses-status
GET /api/charts/revenue-monthly
GET /api/charts/attendance-rate
GET /api/charts/performance-overview
GET /api/charts/grades-distribution
```

### Student Dashboard
```
GET /api/student/courses-progress?student_id=123
GET /api/student/attendance-rate?student_id=123
GET /api/student/grades-overview?student_id=123
```

### Trainer Dashboard
```
GET /api/trainer/students-performance?trainer_id=45&course_id=10
GET /api/trainer/course-attendance?course_id=10
GET /api/trainer/grades-distribution?course_id=10
```

### Analytics
```
GET /api/analytics/dashboard-stats
GET /api/analytics/monthly-revenue
```

---

## 🔧 مثال كامل: صفحة دورات الطالب

```php
<?php
// courses.php
require_once __DIR__ . '/../includes/student_helper.php';

$student = new StudentHelper($conn, $userId);
$courses = $student->getMyCourses();
$gpa = $student->getGPA();
?>

<div class="p-6">
    <h1 class="text-2xl font-bold mb-6">دوراتي</h1>
    
    <!-- إحصائيات -->
    <div class="grid grid-cols-3 gap-4 mb-6">
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-slate-600">الدورات المسجلة</p>
            <p class="text-3xl font-bold"><?php echo count($courses); ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-slate-600">المعدل التراكمي</p>
            <p class="text-3xl font-bold"><?php echo $gpa['gpa']; ?></p>
        </div>
        <div class="bg-white p-4 rounded-lg shadow">
            <p class="text-slate-600">الدورات المكتملة</p>
            <p class="text-3xl font-bold"><?php echo $gpa['courses_count']; ?></p>
        </div>
    </div>
    
    <!-- رسم بياني تفاعلي -->
    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <h2 class="text-xl font-bold mb-4">تقدم الدورات</h2>
        <div id="progressChart" class="h-80"></div>
    </div>
    
    <!-- قائمة الدورات -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($courses as $course): ?>
        <div class="bg-white rounded-lg shadow p-4">
            <h3 class="font-bold text-lg mb-2"><?php echo htmlspecialchars($course['course_name']); ?></h3>
            <p class="text-slate-600 text-sm mb-3"><?php echo htmlspecialchars($course['trainer_name']); ?></p>
            
            <!-- Progress Bar -->
            <div class="w-full bg-slate-200 rounded-full h-2 mb-2">
                <div class="bg-blue-600 h-2 rounded-full" style="width: <?php echo $course['progress']; ?>%"></div>
            </div>
            <p class="text-xs text-slate-500">التقدم: <?php echo $course['progress']; ?>%</p>
            
            <a href="?page=course-details&id=<?php echo $course['course_id']; ?>" 
               class="mt-4 block text-center bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700">
                عرض التفاصيل
            </a>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script src="https://cdn.plot.ly/plotly-2.27.0.min.js"></script>
<script src="/Manager/assets/js/chart-loader.js"></script>
<script>
ChartLoader.loadStudentCoursesProgress('progressChart', <?php echo $userId; ?>);
</script>
```

---

## 🐛 معالجة الأخطاء

### في PHP:
```php
try {
    $student = new StudentHelper($conn, $userId);
    $courses = $student->getMyCourses();
    
    if (empty($courses)) {
        echo '<p class="text-slate-500">لا توجد دورات مسجلة</p>';
    }
} catch (Exception $e) {
    error_log("Error: " . $e->getMessage());
    echo '<p class="text-red-500">حدث خطأ، يرجى المحاولة لاحقاً</p>';
}
```

### في JavaScript:
```javascript
ChartLoader.loadStudentCoursesProgress('chart', studentId)
    .then(data => {
        if (data && data.success) {
            console.log('Chart loaded successfully');
        }
    })
    .catch(error => {
        console.error('Chart error:', error);
    });
```

---

## 🔒 الأمان

### 1. Prepared Statements (دائماً)
```php
// ✅ صحيح
$stmt = $conn->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->bind_param("i", $userId);

// ❌ خطأ - لا تستخدم أبداً
$query = "SELECT * FROM users WHERE user_id = $userId";
```

### 2. تحقق من الصلاحيات
```php
// في بداية كل صفحة
if ($userRole !== 'student') {
    header('Location: /login.php?error=access_denied');
    exit;
}
```

### 3. تنظيف المخرجات
```php
// دائماً استخدم htmlspecialchars
echo htmlspecialchars($userName);
echo htmlspecialchars($courseDescription);
```

---

## 📊 أمثلة الاستخدام الكامل

### مثال 1: صفحة الدرجات
```php
<?php
$student = new StudentHelper($conn, $userId);
$grades = $student->getMyGrades();
$gpa = $student->getGPA();
?>

<div class="p-6">
    <h1>درجاتي - المعدل: <?php echo $gpa['gpa']; ?></h1>
    
    <div id="gradesChart" class="h-96 mb-6"></div>
    
    <table class="w-full">
        <thead>
            <tr>
                <th>الدورة</th>
                <th>النصفي</th>
                <th>النهائي</th>
                <th>الحالة</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($grades as $grade): ?>
            <tr>
                <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                <td><?php echo $grade['midterm_grade'] ?? '-'; ?></td>
                <td><?php echo $grade['final_grade'] ?? '-'; ?></td>
                <td><?php echo $grade['status']; ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script>
ChartLoader.loadStudentGradesOverview('gradesChart', <?php echo $userId; ?>);
</script>
```

### مثال 2: تسجيل الحضور (مدرب)
```php
<?php
$trainer = new TrainerHelper($conn, $userId);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $courseId = $_POST['course_id'];
    $studentId = $_POST['student_id'];
    $status = $_POST['status']; // 'present' or 'absent'
    
    $success = $trainer->recordAttendance($courseId, $studentId, $status);
    
    if ($success) {
        echo json_encode(['success' => true, 'message' => 'تم تسجيل الحضور']);
    } else {
        echo json_encode(['success' => false, 'message' => 'فشل التسجيل']);
    }
    exit;
}

$students = $trainer->getMyStudents($courseId);
?>

<div class="p-6">
    <h1>الحضور والغياب</h1>
    
    <form id="attendanceForm">
        <?php foreach ($students as $student): ?>
        <div class="flex items-center justify-between p-4 border-b">
            <span><?php echo htmlspecialchars($student['full_name']); ?></span>
            <div class="space-x-2">
                <button type="button" onclick="markAttendance(<?php echo $student['user_id']; ?>, 'present')"
                        class="bg-green-500 text-white px-4 py-2 rounded">
                    حاضر
                </button>
                <button type="button" onclick="markAttendance(<?php echo $student['user_id']; ?>, 'absent')"
                        class="bg-red-500 text-white px-4 py-2 rounded">
                    غائب
                </button>
            </div>
        </div>
        <?php endforeach; ?>
    </form>
    
    <div id="attendanceChart" class="h-96 mt-6"></div>
</div>

<script>
function markAttendance(studentId, status) {
    fetch('?page=attendance', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `course_id=<?php echo $courseId; ?>&student_id=${studentId}&status=${status}`
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('تم التسجيل بنجاح');
            location.reload();
        }
    });
}

ChartLoader.loadTrainerCourseAttendance('attendanceChart', <?php echo $courseId; ?>);
</script>
```

---

## 💡 نصائح وأفضل الممارسات

### 1. استخدم Helper Classes دائماً
```php
// ✅ صحيح
$student = new StudentHelper($conn, $userId);
$courses = $student->getMyCourses();

// ❌ تجنب الاستعلامات المباشرة المكررة
$stmt = $conn->prepare("SELECT...");
```

### 2. حمّل الرسوم عند الحاجة فقط
```javascript
// ✅ صحيح - عند عرض الصفحة
if (document.getElementById('myChart')) {
    ChartLoader.loadStudentCoursesProgress('myChart', studentId);
}

// ❌ تجنب - تحميل غير ضروري
ChartLoader.loadStudentCoursesProgress('hiddenChart', studentId);
```

### 3. معالجة الأخطاء دائماً
```php
$courses = $student->getMyCourses();
if (empty($courses)) {
    // عرض رسالة واضحة
    echo '<div class="alert">لا توجد دورات</div>';
}
```

---

## 📞 الدعم والمساعدة

- 📖 الوثائق الكاملة: `COMPLETE_JS_CONVERSION_REPORT.md`
- 🐛 الأخطاء الشائعة: راجع error logs في PHP
- 🔧 Python API لا يعمل؟ تأكد من: `pip install -r requirements.txt`

---

**آخر تحديث:** 21 نوفمبر 2025  
**الإصدار:** 2.0
