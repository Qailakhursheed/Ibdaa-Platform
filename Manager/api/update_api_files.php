<?php
/**
 * Auto-Update API Files Script
 * سكريبت تلقائي لتحديث ملفات API لاستخدام نظام الحماية المركزي
 * 
 * الاستخدام: php update_api_files.php
 */

$apiDir = __DIR__;
$files = glob($apiDir . '/*.php');
$updated = 0;
$skipped = 0;
$errors = 0;

$excludeFiles = ['api_auth.php', 'API_UPDATE_EXAMPLE.php', 'update_api_files.php'];

echo "\n=== Starting API Files Update ===\n\n";

foreach ($files as $file) {
    $filename = basename($file);
    
    // تخطي الملفات المستثناة
    if (in_array($filename, $excludeFiles)) {
        continue;
    }
    
    $content = file_get_contents($file);
    
    // التحقق إذا كان الملف محدث بالفعل
    if (strpos($content, "require_once __DIR__ . '/api_auth.php'") !== false) {
        echo "✓ SKIP: $filename (already updated)\n";
        $skipped++;
        continue;
    }
    
    // التحقق إذا كان الملف يحتوي على session_start
    if (strpos($content, 'session_start()') === false) {
        echo "⚠ SKIP: $filename (no session_start found)\n";
        $skipped++;
        continue;
    }
    
    try {
        // النمط 1: استبدال session_start() مع shutdown handler
        $pattern1 = '/^<\?php\s*\n(?:\/\*\*[\s\S]*?\*\/\s*)?\nsession_start\(\);\s*\n+(?:ini_set[^;]+;\s*\n)?(?:error_reporting[^;]+;\s*\n+)?(?:register_shutdown_function\([^}]+}\);\s*\n+)?/m';
        
        $replacement1 = <<<'PHP'
<?php
/**
 * $FILENAME - Protected with Central Security System
 * محمي بنظام الحماية المركزي
 */

require_once __DIR__ . '/api_auth.php';

PHP;
        
        $replacement1 = str_replace('$FILENAME', pathinfo($filename, PATHINFO_FILENAME), $replacement1);
        
        $newContent = preg_replace($pattern1, $replacement1, $content, 1, $count);
        
        if ($count > 0) {
            // إضافة التحقق من الصلاحيات بعد require
            $authLine = "\n// Verify authentication\n\$user = APIAuth::requireAuth();\nAPIAuth::rateLimit(120, 60);\n\n";
            $newContent = preg_replace('/(require_once __DIR__ \. \'\/api_auth\.php\';)/', "$1$authLine", $newContent, 1);
            
            // حفظ الملف
            if (file_put_contents($file, $newContent)) {
                echo "✅ UPDATED: $filename\n";
                $updated++;
            } else {
                echo "❌ ERROR: Failed to write $filename\n";
                $errors++;
            }
        } else {
            echo "⚠ SKIP: $filename (pattern not matched)\n";
            $skipped++;
        }
        
    } catch (Exception $e) {
        echo "❌ ERROR: $filename - " . $e->getMessage() . "\n";
        $errors++;
    }
}

echo "\n=== Update Summary ===\n";
echo "✅ Updated: $updated files\n";
echo "⚠ Skipped: $skipped files\n";
echo "❌ Errors: $errors files\n";
echo "\nTotal files processed: " . ($updated + $skipped + $errors) . "\n\n";

if ($updated > 0) {
    echo "🎉 Success! $updated files have been updated with central security system.\n";
    echo "⚠ Remember to test the updated files!\n\n";
}
