<?php
/**
 * نظام التكامل الشامل - سكريبت الإعداد
 * Integration System Setup Script
 * A-TEAM @ F.G.M
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

// إعدادات قاعدة البيانات
$db_host = 'localhost';
$db_user = 'root';
$db_pass = '';
$db_name = 'ibdaa_taiz';

try {
    $pdo = new PDO("mysql:host=$db_host", $db_user, $db_pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec("SET NAMES utf8mb4");
    
    echo "<h1>🚀 نظام التكامل الشامل - الإعداد</h1>";
    echo "<hr>";
    
    // التحقق من وجود قاعدة البيانات
    echo "<h2>1️⃣ التحقق من قاعدة البيانات...</h2>";
    $stmt = $pdo->query("SHOW DATABASES LIKE '$db_name'");
    if ($stmt->rowCount() == 0) {
        echo "<p>❌ قاعدة البيانات غير موجودة. يتم إنشاؤها...</p>";
        $pdo->exec("CREATE DATABASE `$db_name` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "<p>✅ تم إنشاء قاعدة البيانات بنجاح</p>";
    } else {
        echo "<p>✅ قاعدة البيانات موجودة</p>";
    }
    
    // الاتصال بقاعدة البيانات
    $pdo->exec("USE `$db_name`");
    
    // إنشاء الجداول المطلوبة إن لم تكن موجودة
    echo "<h2>2️⃣ إنشاء الجداول المطلوبة...</h2>";
    
    // جدول الجداول الدراسية
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `schedules` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `day_of_week` VARCHAR(50) NOT NULL,
            `start_time` TIME NOT NULL,
            `end_time` TIME NOT NULL,
            `room` VARCHAR(100),
            `type` ENUM('محاضرة', 'عملي', 'مختبر') DEFAULT 'محاضرة',
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_course` (`course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الجداول الدراسية (schedules)</p>";
    
    // جدول الحضور والغياب
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `attendance` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `date` DATE NOT NULL,
            `status` ENUM('present', 'absent', 'late', 'excused') NOT NULL,
            `notes` TEXT,
            `recorded_by` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_student_course` (`student_id`, `course_id`),
            INDEX `idx_date` (`date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الحضور والغياب (attendance)</p>";
    
    // جدول الاختبارات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `exams` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `exam_date` DATETIME NOT NULL,
            `duration_minutes` INT NOT NULL,
            `total_marks` DECIMAL(5,2) NOT NULL,
            `passing_marks` DECIMAL(5,2) NOT NULL,
            `type` ENUM('quiz', 'midterm', 'final', 'project') DEFAULT 'quiz',
            `status` ENUM('draft', 'scheduled', 'ongoing', 'completed', 'cancelled') DEFAULT 'draft',
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_course` (`course_id`),
            INDEX `idx_date` (`exam_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الاختبارات (exams)</p>";
    
    // جدول درجات الاختبارات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `exam_grades` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `exam_id` INT NOT NULL,
            `student_id` INT NOT NULL,
            `score` DECIMAL(5,2),
            `feedback` TEXT,
            `graded_by` INT,
            `graded_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_exam_student` (`exam_id`, `student_id`),
            UNIQUE KEY `unique_exam_student` (`exam_id`, `student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول درجات الاختبارات (exam_grades)</p>";
    
    // جدول الواجبات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `assignments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `due_date` DATETIME NOT NULL,
            `max_score` DECIMAL(5,2) NOT NULL,
            `attachment` VARCHAR(255),
            `created_by` INT NOT NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_course` (`course_id`),
            INDEX `idx_due_date` (`due_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الواجبات (assignments)</p>";
    
    // جدول تسليم الواجبات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `assignment_submissions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `assignment_id` INT NOT NULL,
            `student_id` INT NOT NULL,
            `submission_text` TEXT,
            `attachment` VARCHAR(255),
            `submitted_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `score` DECIMAL(5,2),
            `feedback` TEXT,
            `graded_by` INT,
            `graded_at` TIMESTAMP NULL,
            INDEX `idx_assignment_student` (`assignment_id`, `student_id`),
            UNIQUE KEY `unique_assignment_student` (`assignment_id`, `student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول تسليم الواجبات (assignment_submissions)</p>";
    
    // جدول الرسائل والدردشة
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `messages` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `sender_id` INT NOT NULL,
            `receiver_id` INT NOT NULL,
            `subject` VARCHAR(255),
            `message` TEXT NOT NULL,
            `is_read` TINYINT(1) DEFAULT 0,
            `sent_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `read_at` TIMESTAMP NULL,
            INDEX `idx_sender` (`sender_id`),
            INDEX `idx_receiver` (`receiver_id`),
            INDEX `idx_conversation` (`sender_id`, `receiver_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الرسائل (messages)</p>";
    
    // جدول البطائق الطلابية
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `student_cards` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `student_id` INT NOT NULL,
            `card_number` VARCHAR(50) UNIQUE NOT NULL,
            `issue_date` DATE NOT NULL,
            `expiry_date` DATE NOT NULL,
            `status` ENUM('active', 'expired', 'suspended') DEFAULT 'active',
            `photo` VARCHAR(255),
            `barcode` VARCHAR(100),
            `issued_by` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_student` (`student_id`),
            INDEX `idx_card_number` (`card_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول البطائق الطلابية (student_cards)</p>";
    
    // جدول ملاحظات المدربين
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `trainer_notes` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `trainer_id` INT NOT NULL,
            `student_id` INT NOT NULL,
            `course_id` INT,
            `note` TEXT NOT NULL,
            `type` ENUM('positive', 'negative', 'warning', 'improvement') DEFAULT 'positive',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_trainer` (`trainer_id`),
            INDEX `idx_student` (`student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول ملاحظات المدربين (trainer_notes)</p>";
    
    // جدول المحتوى التعليمي
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `course_materials` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `type` ENUM('video', 'pdf', 'document', 'link', 'other') NOT NULL,
            `file_path` VARCHAR(255),
            `order_number` INT DEFAULT 0,
            `is_free` TINYINT(1) DEFAULT 0,
            `uploaded_by` INT NOT NULL,
            `uploaded_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_course` (`course_id`),
            INDEX `idx_order` (`order_number`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول المحتوى التعليمي (course_materials)</p>";
    
    // جدول التقييمات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `course_reviews` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `student_id` INT NOT NULL,
            `rating` INT NOT NULL CHECK (rating >= 1 AND rating <= 5),
            `review` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_course` (`course_id`),
            UNIQUE KEY `unique_course_student` (`course_id`, `student_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول التقييمات (course_reviews)</p>";
    
    // جدول الأنشطة والفعاليات
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `activities` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `type` ENUM('workshop', 'seminar', 'exhibition', 'competition', 'other') NOT NULL,
            `start_date` DATETIME NOT NULL,
            `end_date` DATETIME NOT NULL,
            `location` VARCHAR(255),
            `organizer_id` INT NOT NULL,
            `max_participants` INT,
            `status` ENUM('upcoming', 'ongoing', 'completed', 'cancelled') DEFAULT 'upcoming',
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX `idx_dates` (`start_date`, `end_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول الأنشطة (activities)</p>";
    
    // جدول تذاكر الدعم
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `support_tickets` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `subject` VARCHAR(255) NOT NULL,
            `description` TEXT NOT NULL,
            `priority` ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
            `status` ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
            `category` ENUM('technical', 'academic', 'billing', 'general') DEFAULT 'general',
            `assigned_to` INT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_user` (`user_id`),
            INDEX `idx_status` (`status`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    echo "<p>✅ جدول تذاكر الدعم (support_tickets)</p>";
    
    // التحقق من الجداول الأساسية الأخرى
    echo "<h2>3️⃣ التحقق من الجداول الأساسية...</h2>";
    
    $essential_tables = [
        'users', 'courses', 'enrollments', 'payments', 
        'announcements', 'notifications', 'certificates'
    ];
    
    $missing_tables = [];
    foreach ($essential_tables as $table) {
        $stmt = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() == 0) {
            $missing_tables[] = $table;
        } else {
            echo "<p>✅ $table</p>";
        }
    }
    
    if (!empty($missing_tables)) {
        echo "<p style='color: orange;'>⚠️ الجداول التالية غير موجودة: " . implode(', ', $missing_tables) . "</p>";
        echo "<p style='color: orange;'>يرجى تنفيذ ملف schema.sql أو UNIFIED_DATABASE.sql أولاً</p>";
    }
    
    // استيراد البيانات الافتراضية
    echo "<h2>4️⃣ استيراد البيانات الافتراضية...</h2>";
    
    $sql_file = __DIR__ . '/INTEGRATION_SEED_DATA.sql';
    if (file_exists($sql_file)) {
        echo "<p>📂 قراءة ملف البيانات...</p>";
        $sql_content = file_get_contents($sql_file);
        
        // تقسيم الاستعلامات
        $statements = array_filter(
            array_map('trim', 
                preg_split('/;[\r\n]+/', $sql_content)
            ),
            function($stmt) {
                return !empty($stmt) && 
                       !preg_match('/^--/', $stmt) && 
                       strlen($stmt) > 5;
            }
        );
        
        $success_count = 0;
        $error_count = 0;
        
        foreach ($statements as $statement) {
            try {
                if (stripos($statement, 'INSERT') !== false || 
                    stripos($statement, 'UPDATE') !== false) {
                    $pdo->exec($statement);
                    $success_count++;
                }
            } catch (PDOException $e) {
                // تجاهل أخطاء التكرار
                if (strpos($e->getMessage(), 'Duplicate entry') === false) {
                    $error_count++;
                }
            }
        }
        
        echo "<p>✅ تم تنفيذ $success_count استعلام بنجاح</p>";
        if ($error_count > 0) {
            echo "<p style='color: orange;'>⚠️ $error_count خطأ (معظمها بيانات مكررة)</p>";
        }
    } else {
        echo "<p style='color: red;'>❌ ملف البيانات غير موجود: $sql_file</p>";
    }
    
    // إحصائيات نهائية
    echo "<h2>5️⃣ الإحصائيات النهائية...</h2>";
    
    $stats = [
        'users' => 'المستخدمين',
        'courses' => 'الدورات',
        'enrollments' => 'التسجيلات',
        'schedules' => 'الجداول الدراسية',
        'attendance' => 'سجلات الحضور',
        'exams' => 'الاختبارات',
        'assignments' => 'الواجبات',
        'messages' => 'الرسائل',
        'announcements' => 'الإعلانات',
        'notifications' => 'الإشعارات',
        'payments' => 'المدفوعات'
    ];
    
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%; margin-top: 20px;'>";
    echo "<tr style='background: #4CAF50; color: white;'><th>الجدول</th><th>عدد السجلات</th></tr>";
    
    foreach ($stats as $table => $label) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
            echo "<tr><td>$label ($table)</td><td style='text-align: center;'>$count</td></tr>";
        } catch (PDOException $e) {
            echo "<tr><td>$label ($table)</td><td style='text-align: center; color: red;'>غير موجود</td></tr>";
        }
    }
    
    echo "</table>";
    
    // معلومات الحسابات
    echo "<h2>6️⃣ حسابات الدخول السريع:</h2>";
    echo "<div style='background: #f5f5f5; padding: 20px; border-radius: 5px;'>";
    echo "<table border='1' cellpadding='10' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr style='background: #2196F3; color: white;'><th>الدور</th><th>اسم المستخدم</th><th>كلمة المرور</th></tr>";
    echo "<tr><td>المدير</td><td>manager</td><td>password123</td></tr>";
    echo "<tr><td>المشرف الفني</td><td>supervisor</td><td>password123</td></tr>";
    echo "<tr><td>مدرب 1</td><td>trainer1</td><td>password123</td></tr>";
    echo "<tr><td>مدرب 2</td><td>trainer2</td><td>password123</td></tr>";
    echo "<tr><td>طالب 1</td><td>student1</td><td>password123</td></tr>";
    echo "<tr><td>طالب 2</td><td>student2</td><td>password123</td></tr>";
    echo "</table>";
    echo "</div>";
    
    echo "<hr>";
    echo "<h2 style='color: green;'>✅ اكتمل الإعداد بنجاح!</h2>";
    echo "<p><strong>الخطوة التالية:</strong> قم بتسجيل الدخول إلى أي من اللوحات باستخدام الحسابات أعلاه</p>";
    
    echo "<div style='background: #fff3cd; padding: 20px; margin-top: 20px; border-radius: 5px;'>";
    echo "<h3>📌 روابط سريعة:</h3>";
    echo "<ul>";
    echo "<li><a href='/Ibdaa-Taiz/Manager/login.php' target='_blank'>لوحة تحكم المدير</a></li>";
    echo "<li><a href='/Ibdaa-Taiz/platform/login.php' target='_blank'>لوحة تحكم الطالب</a></li>";
    echo "<li><a href='/Ibdaa-Taiz/Manager/login.php' target='_blank'>لوحة تحكم المدرب</a></li>";
    echo "<li><a href='/Ibdaa-Taiz/Manager/login.php' target='_blank'>لوحة تحكم المشرف</a></li>";
    echo "</ul>";
    echo "</div>";
    
} catch (PDOException $e) {
    echo "<h2 style='color: red;'>❌ خطأ في الاتصال بقاعدة البيانات:</h2>";
    echo "<pre style='background: #ffebee; padding: 20px; border-radius: 5px;'>";
    echo $e->getMessage();
    echo "</pre>";
    echo "<p><strong>تأكد من:</strong></p>";
    echo "<ul>";
    echo "<li>تشغيل خادم MySQL (XAMPP/WAMP)</li>";
    echo "<li>صحة معلومات الاتصال بقاعدة البيانات</li>";
    echo "</ul>";
}
?>

<style>
    body {
        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        max-width: 1200px;
        margin: 0 auto;
        padding: 20px;
        background: #f0f2f5;
        direction: rtl;
    }
    h1 {
        color: #1976d2;
        text-align: center;
        padding: 20px;
        background: white;
        border-radius: 10px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h2 {
        color: #333;
        margin-top: 30px;
        padding: 10px;
        background: white;
        border-right: 5px solid #4CAF50;
    }
    p {
        line-height: 1.6;
        margin: 10px 0;
    }
    table {
        background: white;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    a {
        color: #1976d2;
        text-decoration: none;
        font-weight: bold;
    }
    a:hover {
        text-decoration: underline;
    }
</style>
