<?php
/**
 * سكريبت نقل البيانات من JSON إلى MySQL
 * يجب تشغيله مرة واحدة فقط بعد إنشاء قاعدة البيانات
 */

require_once __DIR__ . '/database/db.php';

$jsonFile = __DIR__ . '/database/requests.json';
$migrated = 0;
$errors = 0;

echo "<!DOCTYPE html>
<html lang='ar' dir='rtl'>
<head>
    <meta charset='UTF-8'>
    <title>نقل البيانات - منصة إبداع</title>
    <script src='https://cdn.tailwindcss.com'></script>
    <link href='https://fonts.googleapis.com/css2?family=Cairo:wght@600;700&display=swap' rel='stylesheet'>
    <style>body { font-family: 'Cairo', sans-serif; }</style>
</head>
<body class='bg-gray-50 p-10'>
<div class='max-w-4xl mx-auto bg-white rounded-2xl shadow-xl p-8'>";

echo "<h1 class='text-3xl font-bold text-sky-700 mb-6'>🔄 نقل البيانات من JSON إلى MySQL</h1>";

// التحقق من وجود الملف
if (!file_exists($jsonFile)) {
    echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-800 p-4 rounded-lg'>
            <p class='font-bold'>⚠️ ملف JSON غير موجود</p>
            <p>المسار: $jsonFile</p>
            <p class='mt-2'>لا توجد بيانات للنقل. قد يكون هذا أمراً طبيعياً إذا لم تكن هناك طلبات سابقة.</p>
          </div>";
} else {
    $jsonContent = file_get_contents($jsonFile);
    $requests = json_decode($jsonContent, true);
    
    if (empty($requests)) {
        echo "<div class='bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg'>
                <p class='font-bold'>📭 لا توجد طلبات في ملف JSON</p>
                <p>الملف موجود لكنه فارغ</p>
              </div>";
    } else {
        echo "<div class='bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mb-6'>
                <p>تم العثور على <strong>" . count($requests) . "</strong> طلب في ملف JSON</p>
              </div>";
        
        echo "<div class='space-y-2 mb-6'>";
        
        foreach ($requests as $req) {
            try {
                $fullName = $req['full_name'] ?? '';
                $email = $req['email'] ?? '';
                $phone = $req['phone'] ?? '';
                $course = $req['course'] ?? '';
                $governorate = $req['governorate'] ?? '';
                $district = $req['district'] ?? '';
                $idCard = $req['id_card'] ?? '';
                $status = $req['status'] ?? 'قيد المراجعة';
                $notes = $req['notes'] ?? '';
                $createdAt = $req['date'] ?? date('Y-m-d H:i:s');
                
                // التحقق من عدم وجود نفس البريد والدورة مسبقاً
                $checkStmt = $conn->prepare("SELECT id FROM course_requests WHERE email = ? AND course = ? LIMIT 1");
                $checkStmt->bind_param("ss", $email, $course);
                $checkStmt->execute();
                $checkResult = $checkStmt->get_result();
                
                if ($checkResult->num_rows > 0) {
                    echo "<div class='text-sm text-gray-600 p-2 bg-gray-50 rounded'>
                            ⏭️ تم تخطي: $fullName - $course (موجود مسبقاً)
                          </div>";
                    continue;
                }
                
                $stmt = $conn->prepare("INSERT INTO course_requests (full_name, email, phone, course, governorate, district, id_card, status, note, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssssss", $fullName, $email, $phone, $course, $governorate, $district, $idCard, $status, $notes, $createdAt);
                
                if ($stmt->execute()) {
                    $migrated++;
                    echo "<div class='text-sm text-green-700 p-2 bg-green-50 rounded'>
                            ✅ تم نقل: $fullName - $course
                          </div>";
                } else {
                    $errors++;
                    echo "<div class='text-sm text-red-700 p-2 bg-red-50 rounded'>
                            ❌ خطأ: $fullName - " . $stmt->error . "
                          </div>";
                }
                
                $stmt->close();
                
            } catch (Exception $e) {
                $errors++;
                echo "<div class='text-sm text-red-700 p-2 bg-red-50 rounded'>
                        ❌ خطأ: " . $e->getMessage() . "
                      </div>";
            }
        }
        
        echo "</div>";
        
        // النتيجة النهائية
        if ($migrated > 0) {
            echo "<div class='bg-green-50 border border-green-200 text-green-800 p-6 rounded-lg mt-6'>
                    <h3 class='text-xl font-bold mb-2'>✅ تم النقل بنجاح!</h3>
                    <p>تم نقل <strong>$migrated</strong> طلب إلى قاعدة البيانات</p>";
            
            if ($errors > 0) {
                echo "<p class='mt-2 text-yellow-700'>⚠️ حدثت $errors أخطاء أثناء النقل</p>";
            }
            
            echo "</div>";
            
            // إنشاء نسخة احتياطية
            $backupFile = __DIR__ . '/database/requests_backup_' . date('Ymd_His') . '.json';
            if (copy($jsonFile, $backupFile)) {
                echo "<div class='bg-blue-50 border border-blue-200 text-blue-800 p-4 rounded-lg mt-4'>
                        <p>💾 تم إنشاء نسخة احتياطية:</p>
                        <p class='text-sm font-mono mt-1'>$backupFile</p>
                        <p class='text-sm mt-2'>يمكنك حذف ملف JSON الأصلي الآن بأمان</p>
                      </div>";
            }
            
        } else {
            echo "<div class='bg-yellow-50 border border-yellow-200 text-yellow-800 p-6 rounded-lg mt-6'>
                    <p class='font-bold'>⚠️ لم يتم نقل أي طلبات</p>
                    <p>جميع الطلبات إما موجودة مسبقاً أو حدثت أخطاء</p>
                  </div>";
        }
    }
}

echo "<div class='mt-8 flex gap-4 justify-center'>
        <a href='Manager/requests.php' class='bg-sky-600 hover:bg-sky-700 text-white px-6 py-3 rounded-lg font-bold transition'>
            📋 عرض الطلبات
        </a>
        <a href='platform/index.html' class='bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-lg font-bold transition'>
            🏠 الصفحة الرئيسية
        </a>
      </div>";

echo "</div></body></html>";

$conn->close();
?>
