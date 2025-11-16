<?php
/**
 * اختبار شامل لجميع APIs المصلحة
 * Test All Fixed APIs
 * 
 * استخدام: http://localhost/Ibdaa-Taiz/test_apis.php
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html dir="rtl" lang="ar">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>اختبار APIs - Ibdaa Platform</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        
        h1 {
            color: #667eea;
            text-align: center;
            margin-bottom: 30px;
            font-size: 2.5em;
        }
        
        .test-section {
            margin-bottom: 30px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 20px;
            background: #f9f9f9;
        }
        
        .test-section h2 {
            color: #764ba2;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .status {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.9em;
            font-weight: bold;
        }
        
        .status.success {
            background: #4caf50;
            color: white;
        }
        
        .status.error {
            background: #f44336;
            color: white;
        }
        
        .status.warning {
            background: #ff9800;
            color: white;
        }
        
        .status.info {
            background: #2196f3;
            color: white;
        }
        
        .test-item {
            padding: 10px;
            margin: 10px 0;
            background: white;
            border-radius: 5px;
            border-left: 4px solid #667eea;
        }
        
        .test-item strong {
            color: #333;
        }
        
        .code {
            background: #2d2d2d;
            color: #f8f8f2;
            padding: 15px;
            border-radius: 5px;
            font-family: 'Courier New', monospace;
            overflow-x: auto;
            margin: 10px 0;
            direction: ltr;
            text-align: left;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            background: #667eea;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin: 5px;
            transition: all 0.3s;
        }
        
        .btn:hover {
            background: #764ba2;
            transform: translateY(-2px);
        }
        
        .summary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
            margin-bottom: 30px;
        }
        
        .summary h3 {
            margin-bottom: 10px;
        }
        
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 15px;
        }
        
        .card {
            background: rgba(255,255,255,0.1);
            padding: 15px;
            border-radius: 8px;
            backdrop-filter: blur(10px);
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🧪 اختبار APIs - منصة إبداع</h1>
        
        <div class="summary">
            <h3>ملخص الفحص</h3>
            <div class="grid">
                <div class="card">
                    <strong>قاعدة البيانات</strong><br>
                    <?php
                    try {
                        require_once __DIR__ . '/database/db.php';
                        echo '<span class="status success">✅ متصلة</span>';
                        $db_status = 'connected';
                    } catch (Exception $e) {
                        echo '<span class="status error">❌ غير متصلة</span>';
                        $db_status = 'error';
                    }
                    ?>
                </div>
                <div class="card">
                    <strong>ملف PDO</strong><br>
                    <?php
                    if (file_exists(__DIR__ . '/Manager/config/database.php')) {
                        echo '<span class="status success">✅ موجود</span>';
                        $pdo_status = 'exists';
                    } else {
                        echo '<span class="status error">❌ غير موجود</span>';
                        $pdo_status = 'missing';
                    }
                    ?>
                </div>
                <div class="card">
                    <strong>PHP Version</strong><br>
                    <span class="status info"><?php echo phpversion(); ?></span>
                </div>
                <div class="card">
                    <strong>MySQL Extension</strong><br>
                    <?php
                    if (extension_loaded('mysqli')) {
                        echo '<span class="status success">✅ mysqli</span>';
                    }
                    if (extension_loaded('pdo_mysql')) {
                        echo '<span class="status success">✅ PDO</span>';
                    }
                    ?>
                </div>
            </div>
        </div>

        <?php if ($db_status === 'connected'): ?>
        
        <!-- اختبار الجداول -->
        <div class="test-section">
            <h2>📊 فحص الجداول</h2>
            <?php
            $required_tables = [
                'users', 'courses', 'enrollments', 'certificates',
                'exams', 'exam_questions', 'exam_attempts', 'exam_answers',
                'exam_anti_cheat_log', 'student_grades', 'notifications', 'attendance'
            ];
            
            $result = $conn->query("SHOW TABLES");
            $existing_tables = [];
            while ($row = $result->fetch_array()) {
                $existing_tables[] = $row[0];
            }
            
            $missing_tables = array_diff($required_tables, $existing_tables);
            
            if (empty($missing_tables)) {
                echo '<span class="status success">✅ جميع الجداول موجودة (' . count($existing_tables) . ' جدول)</span>';
            } else {
                echo '<span class="status error">❌ جداول ناقصة: ' . implode(', ', $missing_tables) . '</span>';
            }
            ?>
            
            <div class="code">
<?php
echo "Tables Found:\n";
foreach ($existing_tables as $table) {
    echo "  ✓ $table\n";
}
?>
            </div>
        </div>

        <!-- اختبار البيانات -->
        <div class="test-section">
            <h2>📈 إحصائيات البيانات</h2>
            <div class="grid">
                <?php
                $stats = [
                    'المستخدمين' => "SELECT COUNT(*) as count FROM users",
                    'الطلاب' => "SELECT COUNT(*) as count FROM users WHERE role='student'",
                    'الدورات' => "SELECT COUNT(*) as count FROM courses",
                    'التسجيلات' => "SELECT COUNT(*) as count FROM enrollments",
                    'الاختبارات' => "SELECT COUNT(*) as count FROM exams",
                    'الدرجات' => "SELECT COUNT(*) as count FROM student_grades",
                    'الإشعارات' => "SELECT COUNT(*) as count FROM notifications",
                    'الشهادات' => "SELECT COUNT(*) as count FROM certificates"
                ];
                
                foreach ($stats as $name => $query) {
                    try {
                        $result = $conn->query($query);
                        $row = $result->fetch_assoc();
                        $count = $row['count'];
                        echo '<div class="test-item">';
                        echo "<strong>$name:</strong> ";
                        echo '<span class="status info">' . $count . '</span>';
                        echo '</div>';
                    } catch (Exception $e) {
                        echo '<div class="test-item">';
                        echo "<strong>$name:</strong> ";
                        echo '<span class="status error">خطأ</span>';
                        echo '</div>';
                    }
                }
                ?>
            </div>
        </div>

        <?php endif; ?>

        <!-- اختبار الملفات المصلحة -->
        <div class="test-section">
            <h2>🔧 الملفات المصلحة (8 ملفات)</h2>
            <?php
            $fixed_files = [
                'student_assignments.php',
                'student_attendance.php',
                'student_courses.php',
                'student_grades.php',
                'student_id_card.php',
                'student_materials.php',
                'student_payments.php',
                'student_schedule.php'
            ];
            
            foreach ($fixed_files as $file) {
                $path = __DIR__ . '/Manager/api/' . $file;
                echo '<div class="test-item">';
                echo "<strong>$file:</strong> ";
                
                if (file_exists($path)) {
                    // تحقق من المحتوى
                    $content = file_get_contents($path);
                    if (strpos($content, "require_once __DIR__ . '/../config/database.php';") !== false) {
                        echo '<span class="status success">✅ تم الإصلاح</span>';
                    } else {
                        echo '<span class="status warning">⚠️ يحتاج مراجعة</span>';
                    }
                } else {
                    echo '<span class="status error">❌ غير موجود</span>';
                }
                echo '</div>';
            }
            ?>
        </div>

        <!-- روابط الاختبار -->
        <div class="test-section">
            <h2>🔗 روابط الاختبار</h2>
            <p><strong>ملاحظة:</strong> تحتاج تسجيل دخول كطالب أولاً</p>
            
            <div style="margin-top: 15px;">
                <a href="/Ibdaa-Taiz/platform/login.php" class="btn" target="_blank">
                    🔑 تسجيل دخول المنصة
                </a>
                <a href="/Ibdaa-Taiz/Manager/login.php" class="btn" target="_blank">
                    🔐 تسجيل دخول لوحة التحكم
                </a>
            </div>
            
            <div style="margin-top: 15px;">
                <h3 style="color: #764ba2; margin-bottom: 10px;">APIs للاختبار:</h3>
                <?php foreach ($fixed_files as $file): ?>
                <a href="/Ibdaa-Taiz/Manager/api/<?php echo $file; ?>?action=list" 
                   class="btn" target="_blank" style="font-size: 0.9em;">
                    <?php echo str_replace('.php', '', $file); ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- تعليمات الاستيراد -->
        <div class="test-section">
            <h2>📥 تعليمات الاستيراد</h2>
            <div class="test-item">
                <strong>1. افتح phpMyAdmin:</strong>
                <a href="http://localhost/phpmyadmin" target="_blank" class="btn">
                    افتح phpMyAdmin
                </a>
            </div>
            
            <div class="test-item">
                <strong>2. اختر قاعدة البيانات:</strong> ibdaa_taiz
            </div>
            
            <div class="test-item">
                <strong>3. استورد الملف:</strong>
                <div class="code">database/UNIFIED_DATABASE.sql</div>
            </div>
            
            <div class="test-item">
                <strong>4. أو استخدم سطر الأوامر:</strong>
                <div class="code">cd C:\xampp\mysql\bin
.\mysql.exe -u root ibdaa_taiz < C:\xampp\htdocs\Ibdaa-Taiz\database\UNIFIED_DATABASE.sql</div>
            </div>
        </div>

        <!-- الأنظمة المتقدمة -->
        <div class="test-section">
            <h2>⭐ الأنظمة المتقدمة</h2>
            <div class="grid">
                <div class="test-item">
                    <strong>نظام الاختبارات</strong><br>
                    <a href="/Ibdaa-Taiz/Manager/exam_interface.html" class="btn" target="_blank">
                        اختبار
                    </a>
                </div>
                <div class="test-item">
                    <strong>نظام الدرجات</strong><br>
                    <a href="/Ibdaa-Taiz/Manager/grades_entry.html" class="btn" target="_blank">
                        اختبار
                    </a>
                </div>
                <div class="test-item">
                    <strong>إزالة الخلفية AI</strong><br>
                    <a href="/Ibdaa-Taiz/Manager/components/photo_upload_widget.html" class="btn" target="_blank">
                        اختبار
                    </a>
                </div>
                <div class="test-item">
                    <strong>نظام البطاقات</strong><br>
                    <a href="/Ibdaa-Taiz/Manager/api/generate_id_card_v2.php" class="btn" target="_blank">
                        اختبار
                    </a>
                </div>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px; padding: 20px; background: #f0f0f0; border-radius: 10px;">
            <p style="color: #666; font-size: 0.9em;">
                تم إنشاء هذا الاختبار بواسطة AI System Audit<br>
                التاريخ: 2025-11-12
            </p>
        </div>
    </div>
</body>
</html>
