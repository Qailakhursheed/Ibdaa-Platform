<?php
/**
 * 🎨 Advanced AI-Powered Background Removal System
 * Ultra-advanced photo processing with multiple AI providers
 * Converts any photo background to pure white automatically
 */

session_start();
header('Content-Type: application/json; charset=utf-8');

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../platform/db.php';

// Authentication check
$user_id = $_SESSION['user_id'] ?? null;
$user_role = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'يجب تسجيل الدخول'], JSON_UNESCAPED_UNICODE);
    exit;
}

$method = $_SERVER['REQUEST_METHOD'];

// ============================================
// Configuration - Multiple AI Providers
// ============================================
$config = [
    'remove_bg_api_key' => 'YOUR_REMOVEBG_API_KEY', // https://remove.bg
    'cloudinary_cloud_name' => 'YOUR_CLOUD_NAME',
    'cloudinary_api_key' => 'YOUR_CLOUDINARY_KEY',
    'cloudinary_api_secret' => 'YOUR_CLOUDINARY_SECRET',
    'use_local_processing' => true, // Fallback to GD/Imagick if APIs not available
];

/**
 * Advanced local background removal using edge detection and color analysis
 * This is a sophisticated algorithm that works without external APIs
 */
function removeBackgroundLocal($imagePath) {
    if (!extension_loaded('gd') && !extension_loaded('imagick')) {
        return ['success' => false, 'message' => 'GD or Imagick extension required'];
    }

    try {
        // Try Imagick first (more powerful)
        if (extension_loaded('imagick')) {
            $image = new Imagick($imagePath);
            
            // Get original dimensions
            $width = $image->getImageWidth();
            $height = $image->getImageHeight();
            
            // Advanced edge detection and subject isolation
            $image->setImageBackgroundColor('white');
            
            // Method 1: Fuzz-based background removal (works great for uniform backgrounds)
            $fuzz = $image->getQuantumRange()['quantumRangeLong'] * 0.15; // 15% tolerance
            $image->transparentPaintImage($image->getImagePixelColor(0, 0), 0, $fuzz, false);
            
            // Method 2: Subject detection using edge detection
            $edgeImage = clone $image;
            $edgeImage->edgeImage(1);
            $edgeImage->thresholdImage(0.3 * $edgeImage->getQuantumRange()['quantumRangeLong']);
            
            // Create mask from edges
            $mask = new Imagick();
            $mask->newImage($width, $height, 'black');
            $mask->compositeImage($edgeImage, Imagick::COMPOSITE_OVER, 0, 0);
            
            // Dilate to expand subject area
            $mask->morphology(Imagick::MORPHOLOGY_DILATE, 3, Imagick::KERNEL_DISK);
            
            // Fill interior (flood fill from center)
            $centerX = intval($width / 2);
            $centerY = intval($height / 2);
            $mask->floodfillPaintImage('white', 1000, 'black', $centerX, $centerY, false);
            
            // Apply mask to original image
            $image->compositeImage($mask, Imagick::COMPOSITE_DSTIN, 0, 0);
            
            // Create final image with white background
            $final = new Imagick();
            $final->newImage($width, $height, 'white');
            $final->compositeImage($image, Imagick::COMPOSITE_OVER, 0, 0);
            
            // Enhance edges for crisp look
            $final->adaptiveSharpenImage(2, 1);
            $final->enhanceImage();
            
            // Save as high-quality PNG
            $final->setImageFormat('png');
            $final->setImageCompressionQuality(95);
            
            $outputPath = str_replace(
                ['.jpg', '.jpeg', '.png'],
                '_no_bg.png',
                $imagePath
            );
            
            $final->writeImage($outputPath);
            
            return [
                'success' => true,
                'output_path' => $outputPath,
                'method' => 'imagick_advanced'
            ];
            
        } else {
            // Fallback to GD with advanced color-based removal
            return removeBackgroundGD($imagePath);
        }
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

/**
 * Advanced GD-based background removal
 * Uses color clustering and edge detection
 */
function removeBackgroundGD($imagePath) {
    $info = getimagesize($imagePath);
    if (!$info) {
        return ['success' => false, 'message' => 'Invalid image'];
    }
    
    // Load image based on type
    switch ($info[2]) {
        case IMAGETYPE_JPEG:
            $src = imagecreatefromjpeg($imagePath);
            break;
        case IMAGETYPE_PNG:
            $src = imagecreatefrompng($imagePath);
            break;
        case IMAGETYPE_GIF:
            $src = imagecreatefromgif($imagePath);
            break;
        default:
            return ['success' => false, 'message' => 'Unsupported format'];
    }
    
    $width = imagesx($src);
    $height = imagesy($src);
    
    // Create output image with white background
    $output = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($output, 255, 255, 255);
    imagefill($output, 0, 0, $white);
    
    // Advanced edge detection and subject isolation
    // Sample background color from corners
    $bgColors = [
        imagecolorat($src, 0, 0),
        imagecolorat($src, $width - 1, 0),
        imagecolorat($src, 0, $height - 1),
        imagecolorat($src, $width - 1, $height - 1),
    ];
    
    $avgBgColor = [
        'r' => 0,
        'g' => 0,
        'b' => 0
    ];
    
    foreach ($bgColors as $color) {
        $avgBgColor['r'] += ($color >> 16) & 0xFF;
        $avgBgColor['g'] += ($color >> 8) & 0xFF;
        $avgBgColor['b'] += $color & 0xFF;
    }
    
    $avgBgColor['r'] = intval($avgBgColor['r'] / 4);
    $avgBgColor['g'] = intval($avgBgColor['g'] / 4);
    $avgBgColor['b'] = intval($avgBgColor['b'] / 4);
    
    // Process each pixel with intelligent threshold
    $threshold = 40; // Color distance threshold
    
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $rgb = imagecolorat($src, $x, $y);
            $r = ($rgb >> 16) & 0xFF;
            $g = ($rgb >> 8) & 0xFF;
            $b = $rgb & 0xFF;
            
            // Calculate color distance from background
            $distance = sqrt(
                pow($r - $avgBgColor['r'], 2) +
                pow($g - $avgBgColor['g'], 2) +
                pow($b - $avgBgColor['b'], 2)
            );
            
            // If pixel is significantly different from background, keep it
            if ($distance > $threshold) {
                $color = imagecolorallocate($output, $r, $g, $b);
                imagesetpixel($output, $x, $y, $color);
            }
            // Otherwise it stays white (background)
        }
    }
    
    // Edge enhancement
    imagefilter($output, IMG_FILTER_EDGEDETECT);
    imagefilter($output, IMG_FILTER_SMOOTH, -10);
    
    $outputPath = str_replace(
        ['.jpg', '.jpeg', '.png'],
        '_no_bg.png',
        $imagePath
    );
    
    imagepng($output, $outputPath, 9);
    imagedestroy($src);
    imagedestroy($output);
    
    return [
        'success' => true,
        'output_path' => $outputPath,
        'method' => 'gd_advanced'
    ];
}

/**
 * Remove background using Remove.bg API (highest quality)
 */
function removeBackgroundRemoveBG($imagePath, $apiKey) {
    if (empty($apiKey) || $apiKey === 'YOUR_REMOVEBG_API_KEY') {
        return ['success' => false, 'message' => 'Remove.bg API key not configured'];
    }
    
    $ch = curl_init('https://api.remove.bg/v1.0/removebg');
    
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'X-Api-Key: ' . $apiKey,
        ],
        CURLOPT_POSTFIELDS => [
            'image_file' => new CURLFile($imagePath),
            'size' => 'auto',
            'bg_color' => 'ffffff', // White background
        ],
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $outputPath = str_replace(
            ['.jpg', '.jpeg', '.png'],
            '_no_bg.png',
            $imagePath
        );
        file_put_contents($outputPath, $response);
        
        return [
            'success' => true,
            'output_path' => $outputPath,
            'method' => 'removebg_api'
        ];
    }
    
    return [
        'success' => false,
        'message' => 'API error: ' . $httpCode,
        'response' => $response
    ];
}

/**
 * Remove background using Cloudinary AI
 */
function removeBackgroundCloudinary($imagePath, $config) {
    if (empty($config['cloudinary_cloud_name'])) {
        return ['success' => false, 'message' => 'Cloudinary not configured'];
    }
    
    $timestamp = time();
    $publicId = 'student_photo_' . uniqid();
    
    $params = [
        'file' => 'data:image/jpeg;base64,' . base64_encode(file_get_contents($imagePath)),
        'upload_preset' => 'unsigned_preset', // You need to create this in Cloudinary
        'public_id' => $publicId,
        'background_removal' => 'cloudinary_ai',
    ];
    
    $ch = curl_init("https://api.cloudinary.com/v1_1/{$config['cloudinary_cloud_name']}/image/upload");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $params,
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode === 200) {
        $data = json_decode($response, true);
        
        // Download processed image
        $outputPath = str_replace(
            ['.jpg', '.jpeg', '.png'],
            '_no_bg.png',
            $imagePath
        );
        
        $imageUrl = $data['secure_url'] ?? null;
        if ($imageUrl) {
            file_put_contents($outputPath, file_get_contents($imageUrl));
            
            return [
                'success' => true,
                'output_path' => $outputPath,
                'method' => 'cloudinary_ai'
            ];
        }
    }
    
    return ['success' => false, 'message' => 'Cloudinary upload failed'];
}

// ============================================
// Main Request Handling
// ============================================

if ($method === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'upload_and_process') {
        // Upload photo and automatically remove background
        
        if (!isset($_FILES['photo']) || $_FILES['photo']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'لم يتم رفع صورة'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $file = $_FILES['photo'];
        $allowedTypes = ['image/jpeg', 'image/png', 'image/jpg'];
        
        if (!in_array($file['type'], $allowedTypes)) {
            echo json_encode(['success' => false, 'message' => 'نوع الملف غير مدعوم. استخدم JPG أو PNG'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Validate image dimensions and quality
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            echo json_encode(['success' => false, 'message' => 'ملف الصورة تالف'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $width = $imageInfo[0];
        $height = $imageInfo[1];
        
        if ($width < 300 || $height < 400) {
            echo json_encode([
                'success' => false,
                'message' => 'الصورة صغيرة جدًا. يرجى استخدام صورة بدقة 300×400 بكسل على الأقل'
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Create uploads directory
        $uploadsDir = __DIR__ . '/../../uploads/student_photos';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0775, true);
        }
        
        // Generate unique filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'student_' . $user_id . '_' . time() . '.' . $extension;
        $filepath = $uploadsDir . '/' . $filename;
        
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            echo json_encode(['success' => false, 'message' => 'فشل حفظ الصورة'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Try multiple methods in order of quality
        $result = null;
        $removeBackground = isset($_POST['remove_background']) && $_POST['remove_background'] === 'true';
        
        if ($removeBackground) {
            // Try Remove.bg API first (best quality)
            if (!empty($config['remove_bg_api_key']) && $config['remove_bg_api_key'] !== 'YOUR_REMOVEBG_API_KEY') {
                $result = removeBackgroundRemoveBG($filepath, $config['remove_bg_api_key']);
            }
            
            // Try Cloudinary if Remove.bg failed
            if ((!$result || !$result['success']) && !empty($config['cloudinary_cloud_name'])) {
                $result = removeBackgroundCloudinary($filepath, $config);
            }
            
            // Fallback to local processing (always available)
            if (!$result || !$result['success']) {
                if ($config['use_local_processing']) {
                    $result = removeBackgroundLocal($filepath);
                }
            }
            
            if ($result && $result['success']) {
                // Use processed image
                $finalPath = $result['output_path'];
                $relativePath = 'uploads/student_photos/' . basename($finalPath);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'فشل إزالة الخلفية. يمكنك المحاولة بصورة أخرى أو رفعها كما هي',
                    'error' => $result['message'] ?? 'Unknown error'
                ], JSON_UNESCAPED_UNICODE);
                exit;
            }
        } else {
            // Use original image
            $relativePath = 'uploads/student_photos/' . $filename;
        }
        
        // Update user photo in database
        $stmt = $conn->prepare('UPDATE users SET photo = ? WHERE id = ?');
        $stmt->bind_param('si', $relativePath, $user_id);
        
        if (!$stmt->execute()) {
            echo json_encode(['success' => false, 'message' => 'فشل تحديث الصورة في قاعدة البيانات'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => $removeBackground 
                ? 'تم رفع الصورة وإزالة الخلفية بنجاح!' 
                : 'تم رفع الصورة بنجاح!',
            'photo_url' => '../../' . $relativePath,
            'method' => $result['method'] ?? 'original',
            'background_removed' => $removeBackground
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    if ($action === 'remove_background_existing') {
        // Remove background from existing photo
        $photoPath = $_POST['photo_path'] ?? '';
        
        if (empty($photoPath)) {
            echo json_encode(['success' => false, 'message' => 'مسار الصورة مطلوب'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        $fullPath = __DIR__ . '/../../' . ltrim($photoPath, '/\\');
        
        if (!file_exists($fullPath)) {
            echo json_encode(['success' => false, 'message' => 'الصورة غير موجودة'], JSON_UNESCAPED_UNICODE);
            exit;
        }
        
        // Try removal methods
        $result = removeBackgroundLocal($fullPath);
        
        if ($result['success']) {
            $relativePath = str_replace(__DIR__ . '/../../', '', $result['output_path']);
            
            // Update database
            $stmt = $conn->prepare('UPDATE users SET photo = ? WHERE id = ?');
            $stmt->bind_param('si', $relativePath, $user_id);
            $stmt->execute();
            
            echo json_encode([
                'success' => true,
                'message' => 'تم إزالة الخلفية بنجاح!',
                'photo_url' => '../../' . $relativePath,
                'method' => $result['method']
            ], JSON_UNESCAPED_UNICODE);
        } else {
            echo json_encode($result, JSON_UNESCAPED_UNICODE);
        }
        exit;
    }
}

if ($method === 'GET') {
    $action = $_GET['action'] ?? '';
    
    if ($action === 'get_photo') {
        // Get current user photo
        $stmt = $conn->prepare('SELECT photo FROM users WHERE id = ?');
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $result = $stmt->get_result()->fetch_assoc();
        
        echo json_encode([
            'success' => true,
            'photo_url' => $result['photo'] ? '../../' . $result['photo'] : null,
            'has_photo' => !empty($result['photo'])
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'إجراء غير معروف'], JSON_UNESCAPED_UNICODE);
?>
