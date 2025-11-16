<?php
/**
 * Announcements API - يربط بين الموقع الخارجي ولوحة التحكم
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type');

require_once '../db.php';

if (!isset($conn)) {
    die(json_encode(['success' => false, 'error' => 'فشل الاتصال بقاعدة البيانات']));
}

$action = $_GET['action'] ?? $_POST['action'] ?? 'list';
$response = ['success' => false, 'data' => null, 'error' => null];

try {
    switch ($action) {
        // قائمة الإعلانات (للموقع الخارجي)
        case 'list':
        case 'public':
            $category = $_GET['category'] ?? 'all';
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 50;
            
            $sql = "
                SELECT 
                    id,
                    title_ar as title,
                    content_ar as content,
                    category,
                    priority,
                    image_url,
                    link_url,
                    start_date,
                    end_date,
                    is_active,
                    created_at
                FROM announcements 
                WHERE is_active = 1 
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
            ";
            
            if ($category !== 'all') {
                $sql .= " AND category = ?";
            }
            
            $sql .= " ORDER BY priority DESC, created_at DESC LIMIT ?";
            
            $stmt = $conn->prepare($sql);
            
            if ($category !== 'all') {
                $stmt->bind_param("si", $category, $limit);
            } else {
                $stmt->bind_param("i", $limit);
            }
            
            $stmt->execute();
            $result = $stmt->get_result();
            
            $announcements = [];
            // Prepare watermark manager from platform
            $watermarkAvailable = false;
            $watermarkManager = null;
            $wmInclude = __DIR__ . '/../../platform/watermark_system.php';
            if (file_exists($wmInclude)) {
                require_once $wmInclude;
                if (class_exists('WatermarkManager')) {
                    $watermarkAvailable = true;
                    $watermarkManager = new WatermarkManager();
                }
            }

            while ($row = $result->fetch_assoc()) {
                // إضافة علامة مائية على الصور إن أمكن
                if (!empty($row['image_url'])) {
                    $imgRel = ltrim($row['image_url'], '/');
                    $platformRoot = realpath(__DIR__ . '/../../platform');
                    $srcPath = $platformRoot . '/' . $imgRel;

                    // Default to original URL
                    $row['image_url_watermarked'] = $row['image_url'];

                    if ($watermarkAvailable && file_exists($srcPath)) {
                        $destDirRel = 'uploads/announcements/watermarked';
                        $destDir = $platformRoot . '/' . $destDirRel;
                        if (!is_dir($destDir)) {
                            mkdir($destDir, 0755, true);
                        }

                        $base = pathinfo($imgRel, PATHINFO_FILENAME);
                        $ext = pathinfo($imgRel, PATHINFO_EXTENSION);
                        $watermarkedName = 'watermarked_' . $base . '_' . crc32($imgRel) . '.' . $ext;
                        $destPath = $destDir . '/' . $watermarkedName;
                        $destRel = $destDirRel . '/' . $watermarkedName;

                        // If not exists, generate
                        if (!file_exists($destPath)) {
                            $res = $watermarkManager->addWatermark($srcPath, $destPath, [
                                'opacity' => 30,
                                'position' => 'bottom-right',
                                'size' => 15
                            ]);
                            if ($res['success']) {
                                $row['image_url_watermarked'] = $destRel;
                            } else {
                                // fallback to original
                                $row['image_url_watermarked'] = $row['image_url'];
                            }
                        } else {
                            $row['image_url_watermarked'] = $destRel;
                        }
                        // make URLs web-friendly (ensure leading slash)
                        if ($row['image_url_watermarked'] && strpos($row['image_url_watermarked'], '/') !== 0) {
                            $row['image_url_watermarked'] = '/' . $row['image_url_watermarked'];
                        }
                    }
                }
                $announcements[] = $row;
            }
            
            $response = ['success' => true, 'data' => $announcements];
            $stmt->close();
            break;

        // إعلان واحد
        case 'get':
            $id = $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('معرف الإعلان مطلوب');
            }
            
            $stmt = $conn->prepare("
                SELECT * FROM announcements 
                WHERE id = ? AND is_active = 1
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows === 0) {
                throw new Exception('الإعلان غير موجود');
            }
            
            $announcement = $result->fetch_assoc();
            $response = ['success' => true, 'data' => $announcement];
            $stmt->close();
            break;

        // إنشاء إعلان جديد (من لوحة التحكم)
        case 'create':
            $required = ['title_ar', 'content_ar', 'category'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("الحقل $field مطلوب");
                }
            }
            
            $title_ar = $_POST['title_ar'];
            $title_en = $_POST['title_en'] ?? $title_ar;
            $content_ar = $_POST['content_ar'];
            $content_en = $_POST['content_en'] ?? $content_ar;
            $category = $_POST['category'];
            $priority = isset($_POST['priority']) ? intval($_POST['priority']) : 1;
            $image_url = $_POST['image_url'] ?? null;
            $link_url = $_POST['link_url'] ?? null;
            $start_date = $_POST['start_date'] ?? null;
            $end_date = $_POST['end_date'] ?? null;
            $is_active = isset($_POST['is_active']) ? intval($_POST['is_active']) : 1;
            
            $stmt = $conn->prepare("
                INSERT INTO announcements 
                (title_ar, title_en, content_ar, content_en, category, priority, 
                 image_url, link_url, start_date, end_date, is_active, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");
            
            $stmt->bind_param(
                "sssssissssi",
                $title_ar, $title_en, $content_ar, $content_en, $category,
                $priority, $image_url, $link_url, $start_date, $end_date, $is_active
            );
            
            $stmt->execute();
            $new_id = $conn->insert_id;
            
            $response = [
                'success' => true,
                'message' => 'تم إنشاء الإعلان بنجاح',
                'data' => ['id' => $new_id]
            ];
            $stmt->close();
            break;

        // تحديث إعلان
        case 'update':
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('معرف الإعلان مطلوب');
            }
            
            $fields = [];
            $types = "";
            $values = [];
            
            $allowed = [
                'title_ar' => 's', 'title_en' => 's',
                'content_ar' => 's', 'content_en' => 's',
                'category' => 's', 'priority' => 'i',
                'image_url' => 's', 'link_url' => 's',
                'start_date' => 's', 'end_date' => 's',
                'is_active' => 'i'
            ];
            
            foreach ($allowed as $field => $type) {
                if (isset($_POST[$field])) {
                    $fields[] = "$field = ?";
                    $types .= $type;
                    $values[] = $_POST[$field];
                }
            }
            
            if (empty($fields)) {
                throw new Exception('لا توجد حقول للتحديث');
            }
            
            $sql = "UPDATE announcements SET " . implode(', ', $fields) . " WHERE id = ?";
            $types .= "i";
            $values[] = $id;
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param($types, ...$values);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'تم تحديث الإعلان بنجاح'];
            $stmt->close();
            break;

        // حذف إعلان
        case 'delete':
            $id = $_POST['id'] ?? $_GET['id'] ?? null;
            
            if (!$id) {
                throw new Exception('معرف الإعلان مطلوب');
            }
            
            $stmt = $conn->prepare("DELETE FROM announcements WHERE id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'تم حذف الإعلان بنجاح'];
            $stmt->close();
            break;

        // تفعيل/تعطيل إعلان
        case 'toggle':
            $id = $_POST['id'] ?? null;
            
            if (!$id) {
                throw new Exception('معرف الإعلان مطلوب');
            }
            
            $stmt = $conn->prepare("
                UPDATE announcements 
                SET is_active = NOT is_active 
                WHERE id = ?
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            
            $response = ['success' => true, 'message' => 'تم تغيير حالة الإعلان'];
            $stmt->close();
            break;

        // إحصائيات الإعلانات
        case 'stats':
            $stmt = $conn->query("
                SELECT 
                    COUNT(*) as total,
                    SUM(CASE WHEN is_active = 1 THEN 1 ELSE 0 END) as active,
                    SUM(CASE WHEN is_active = 0 THEN 1 ELSE 0 END) as inactive,
                    SUM(CASE WHEN category = 'important' THEN 1 ELSE 0 END) as important,
                    SUM(CASE WHEN category = 'events' THEN 1 ELSE 0 END) as events,
                    SUM(CASE WHEN category = 'courses' THEN 1 ELSE 0 END) as courses,
                    SUM(CASE WHEN category = 'news' THEN 1 ELSE 0 END) as news,
                    SUM(CASE WHEN category = 'offers' THEN 1 ELSE 0 END) as offers
                FROM announcements
            ");
            
            $stats = $stmt->fetch_assoc();
            $response = ['success' => true, 'data' => $stats];
            break;

        // الفئات المتاحة
        case 'categories':
            $categories = [
                ['id' => 'important', 'name' => 'إعلانات هامة', 'icon' => 'bell', 'color' => 'red'],
                ['id' => 'events', 'name' => 'الفعاليات', 'icon' => 'calendar', 'color' => 'blue'],
                ['id' => 'courses', 'name' => 'الدورات', 'icon' => 'book-open', 'color' => 'green'],
                ['id' => 'news', 'name' => 'الأخبار', 'icon' => 'newspaper', 'color' => 'purple'],
                ['id' => 'offers', 'name' => 'العروض', 'icon' => 'gift', 'color' => 'yellow']
            ];
            
            $response = ['success' => true, 'data' => $categories];
            break;

        default:
            throw new Exception('إجراء غير معروف');
    }

} catch (Exception $e) {
    $response = [
        'success' => false,
        'error' => $e->getMessage()
    ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
?>
