<?php
// 🎨 AI Image Generation System
// Advanced AI-Powered Image Generation for Courses, Announcements, Certificates & More

session_start();
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../database/db.php';

// ===== CONFIGURATION =====
define('OPENAI_API_KEY', 'YOUR_OPENAI_API_KEY'); // Update with actual key
define('STABILITY_API_KEY', 'YOUR_STABILITY_API_KEY'); // For Stable Diffusion
define('UPLOAD_DIR', __DIR__ . '/../../uploads/ai_images/');
define('MAX_IMAGE_SIZE', 5242880); // 5MB

// Create upload directory if not exists
if (!file_exists(UPLOAD_DIR)) {
    mkdir(UPLOAD_DIR, 0777, true);
}

// ===== HELPER FUNCTIONS =====

function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function checkAuth() {
    $userId = $_SESSION['user_id'] ?? null;
    $userRole = $_SESSION['user_role'] ?? ($_SESSION['role'] ?? null);
    
    if (!$userId) {
        respond(['success' => false, 'message' => 'غير مصرح - يرجى تسجيل الدخول'], 401);
    }
    
    return ['user_id' => $userId, 'role' => $userRole];
}

function checkPermission($userRole, $requiredRoles) {
    if (!in_array($userRole, $requiredRoles, true)) {
        respond(['success' => false, 'message' => 'ليس لديك صلاحية لهذا الإجراء'], 403);
    }
}

function saveImageRecord($conn, $userId, $type, $prompt, $filename, $provider, $metadata = []) {
    $stmt = $conn->prepare("
        INSERT INTO ai_generated_images 
        (user_id, image_type, prompt, filename, file_path, provider, metadata, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");
    
    $filePath = 'uploads/ai_images/' . $filename;
    $metadataJson = json_encode($metadata, JSON_UNESCAPED_UNICODE);
    
    $stmt->bind_param('issssss', $userId, $type, $prompt, $filename, $filePath, $provider, $metadataJson);
    
    if (!$stmt->execute()) {
        return false;
    }
    
    return $conn->insert_id;
}

// ===== MAIN ROUTER =====

$auth = checkAuth();
$action = $_GET['action'] ?? '';

switch ($action) {
    case 'generate':
        checkPermission($auth['role'], ['manager', 'technical', 'trainer']);
        generateImage($conn, $auth);
        break;
    
    case 'list':
        listImagesWithStats($conn, $auth);
        break;
    
    case 'get':
        getImage($conn, $auth);
        break;
    
    case 'delete':
        checkPermission($auth['role'], ['manager', 'technical']);
        deleteImage($conn, $auth);
        break;
    
    case 'apply_watermark':
        checkPermission($auth['role'], ['manager', 'technical']);
        applyWatermark($conn, $auth);
        break;
    
    case 'enhance':
        checkPermission($auth['role'], ['manager', 'technical']);
        enhanceImage($conn, $auth);
        break;
    
    case 'get_templates':
        getTemplates($conn, $auth);
        break;
    
    case 'generate_batch':
        checkPermission($auth['role'], ['manager', 'technical']);
        generateBatch($conn, $auth);
        break;
    
    default:
        respond(['success' => false, 'message' => 'إجراء غير صالح'], 400);
}

// ===== AI IMAGE GENERATION =====

/**
 * Generate image using AI (DALL-E, Stable Diffusion, or Midjourney)
 */
function generateImage($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    $prompt = trim($input['prompt'] ?? '');
    $type = $input['type'] ?? 'course'; // course, announcement, certificate, general
    $style = $input['style'] ?? 'realistic'; // realistic, artistic, cartoon, abstract
    $size = $input['size'] ?? '1024x1024';
    $provider = $input['provider'] ?? 'dalle'; // dalle, stable-diffusion, local
    
    if (empty($prompt)) {
        respond(['success' => false, 'message' => 'النص التوضيحي مطلوب'], 400);
    }
    
    // Enhance prompt based on type
    $enhancedPrompt = enhancePromptForType($prompt, $type, $style);
    
    try {
        switch ($provider) {
            case 'dalle':
                $result = generateWithDALLE($enhancedPrompt, $size);
                break;
            case 'stable-diffusion':
                $result = generateWithStableDiffusion($enhancedPrompt, $size);
                break;
            case 'local':
                $result = generateWithLocalModel($enhancedPrompt, $type);
                break;
            default:
                respond(['success' => false, 'message' => 'مزود غير مدعوم'], 400);
        }
        
        if (!$result['success']) {
            respond($result, 500);
        }
        
        // Save to database
        $imageId = saveImageRecord(
            $conn,
            $auth['user_id'],
            $type,
            $prompt,
            $result['filename'],
            $provider,
            [
                'enhanced_prompt' => $enhancedPrompt,
                'style' => $style,
                'size' => $size,
                'generation_time' => $result['generation_time'] ?? null
            ]
        );
        
        respond([
            'success' => true,
            'message' => 'تم إنشاء الصورة بنجاح',
            'image_id' => $imageId,
            'filename' => $result['filename'],
            'url' => 'uploads/ai_images/' . $result['filename'],
            'provider' => $provider
        ]);
        
    } catch (Exception $e) {
        respond(['success' => false, 'message' => 'خطأ في الإنشاء: ' . $e->getMessage()], 500);
    }
}

/**
 * Generate image with DALL-E (OpenAI)
 */
function generateWithDALLE($prompt, $size) {
    if (OPENAI_API_KEY === 'YOUR_OPENAI_API_KEY') {
        // Fallback to placeholder for demo
        return generatePlaceholder($prompt, $size);
    }
    
    $startTime = microtime(true);
    
    $ch = curl_init('https://api.openai.com/v1/images/generations');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . OPENAI_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'model' => 'dall-e-3',
            'prompt' => $prompt,
            'n' => 1,
            'size' => $size,
            'quality' => 'standard'
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'فشل طلب DALL-E'];
    }
    
    $data = json_decode($response, true);
    $imageUrl = $data['data'][0]['url'] ?? null;
    
    if (!$imageUrl) {
        return ['success' => false, 'message' => 'لم يتم إرجاع صورة'];
    }
    
    // Download image
    $imageData = file_get_contents($imageUrl);
    $filename = 'dalle_' . time() . '_' . rand(1000, 9999) . '.png';
    file_put_contents(UPLOAD_DIR . $filename, $imageData);
    
    $generationTime = round(microtime(true) - $startTime, 2);
    
    return [
        'success' => true,
        'filename' => $filename,
        'generation_time' => $generationTime
    ];
}

/**
 * Generate image with Stable Diffusion
 */
function generateWithStableDiffusion($prompt, $size) {
    if (STABILITY_API_KEY === 'YOUR_STABILITY_API_KEY') {
        return generatePlaceholder($prompt, $size);
    }
    
    $startTime = microtime(true);
    
    // Parse size
    list($width, $height) = explode('x', $size);
    
    $ch = curl_init('https://api.stability.ai/v1/generation/stable-diffusion-xl-1024-v1-0/text-to-image');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . STABILITY_API_KEY,
            'Content-Type: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'text_prompts' => [
                ['text' => $prompt, 'weight' => 1]
            ],
            'cfg_scale' => 7,
            'height' => intval($height),
            'width' => intval($width),
            'samples' => 1,
            'steps' => 30
        ])
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpCode !== 200) {
        return ['success' => false, 'message' => 'فشل طلب Stable Diffusion'];
    }
    
    $data = json_decode($response, true);
    $base64Image = $data['artifacts'][0]['base64'] ?? null;
    
    if (!$base64Image) {
        return ['success' => false, 'message' => 'لم يتم إرجاع صورة'];
    }
    
    // Save image
    $imageData = base64_decode($base64Image);
    $filename = 'sd_' . time() . '_' . rand(1000, 9999) . '.png';
    file_put_contents(UPLOAD_DIR . $filename, $imageData);
    
    $generationTime = round(microtime(true) - $startTime, 2);
    
    return [
        'success' => true,
        'filename' => $filename,
        'generation_time' => $generationTime
    ];
}

/**
 * Generate with local model (placeholder for now)
 */
function generateWithLocalModel($prompt, $type) {
    return generatePlaceholder($prompt, '1024x1024');
}

/**
 * Generate placeholder image for demo/testing
 */
function generatePlaceholder($prompt, $size) {
    list($width, $height) = explode('x', $size);
    
    // Create image
    $image = imagecreatetruecolor($width, $height);
    
    // Random gradient colors
    $colors = [
        [99, 102, 241], // Indigo
        [139, 92, 246], // Purple
        [236, 72, 153], // Pink
        [251, 146, 60], // Orange
    ];
    
    $colorSet = $colors[array_rand($colors)];
    
    // Create gradient
    for ($y = 0; $y < $height; $y++) {
        $ratio = $y / $height;
        $r = $colorSet[0] + ($ratio * 50);
        $g = $colorSet[1] + ($ratio * 50);
        $b = $colorSet[2] + ($ratio * 50);
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $y, $width, $y, $color);
    }
    
    // Add text
    $white = imagecolorallocate($image, 255, 255, 255);
    $black = imagecolorallocate($image, 0, 0, 0);
    
    // Wrap text
    $wrappedText = wordwrap($prompt, 40, "\n", true);
    $lines = explode("\n", $wrappedText);
    
    $fontSize = 5;
    $lineHeight = 20;
    $startY = ($height / 2) - (count($lines) * $lineHeight / 2);
    
    foreach ($lines as $index => $line) {
        $textWidth = imagefontwidth($fontSize) * strlen($line);
        $x = ($width - $textWidth) / 2;
        $y = $startY + ($index * $lineHeight);
        
        // Shadow
        imagestring($image, $fontSize, $x + 2, $y + 2, $line, $black);
        // Text
        imagestring($image, $fontSize, $x, $y, $line, $white);
    }
    
    // Add "AI Generated" watermark
    $watermark = "AI Generated - Demo Mode";
    $watermarkWidth = imagefontwidth(2) * strlen($watermark);
    imagestring($image, 2, ($width - $watermarkWidth) / 2, $height - 30, $watermark, $white);
    
    // Save
    $filename = 'placeholder_' . time() . '_' . rand(1000, 9999) . '.png';
    imagepng($image, UPLOAD_DIR . $filename);
    imagedestroy($image);
    
    return [
        'success' => true,
        'filename' => $filename,
        'generation_time' => 0.5
    ];
}

/**
 * Enhance prompt based on type and style
 */
function enhancePromptForType($prompt, $type, $style) {
    $styleModifiers = [
        'realistic' => 'photorealistic, high quality, detailed, 4k',
        'artistic' => 'artistic, creative, expressive, vibrant colors',
        'cartoon' => 'cartoon style, illustration, colorful, friendly',
        'abstract' => 'abstract, modern, geometric, artistic'
    ];
    
    $typeEnhancements = [
        'course' => 'educational, professional, clean background',
        'announcement' => 'eye-catching, informative, modern design',
        'certificate' => 'elegant, formal, certificate design, border',
        'general' => 'high quality, professional'
    ];
    
    $enhanced = $prompt;
    
    if (isset($styleModifiers[$style])) {
        $enhanced .= ', ' . $styleModifiers[$style];
    }
    
    if (isset($typeEnhancements[$type])) {
        $enhanced .= ', ' . $typeEnhancements[$type];
    }
    
    return $enhanced;
}

/**
 * List generated images with statistics
 */
function listImagesWithStats($conn, $auth) {
    $page = max(1, intval($_GET['page'] ?? 1));
    $limit = min(50, max(1, intval($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;
    $typeFilter = $_GET['type'] ?? null;
    
    $whereClause = ($typeFilter && $typeFilter !== 'all') ? "WHERE image_type = ?" : "";
    
    // Get statistics
    $statsQuery = "
        SELECT 
            COUNT(*) as total,
            COUNT(CASE WHEN DATE_FORMAT(created_at, '%Y-%m') = DATE_FORMAT(NOW(), '%Y-%m') THEN 1 END) as month_total,
            COUNT(CASE WHEN provider = 'dalle' THEN 1 END) as dalle_count,
            COUNT(CASE WHEN provider = 'stable-diffusion' THEN 1 END) as sd_count
        FROM ai_generated_images
    ";
    
    $statsResult = $conn->query($statsQuery);
    $stats = $statsResult->fetch_assoc();
    
    // Count filtered
    $countQuery = "SELECT COUNT(*) as total FROM ai_generated_images $whereClause";
    $countStmt = $conn->prepare($countQuery);
    if ($typeFilter && $typeFilter !== 'all') {
        $countStmt->bind_param('s', $typeFilter);
    }
    $countStmt->execute();
    $filteredTotal = $countStmt->get_result()->fetch_assoc()['total'];
    
    // Fetch images
    $query = "
        SELECT 
            i.*,
            u.full_name as creator_name
        FROM ai_generated_images i
        LEFT JOIN users u ON i.user_id = u.id
        $whereClause
        ORDER BY i.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $stmt = $conn->prepare($query);
    if ($typeFilter && $typeFilter !== 'all') {
        $stmt->bind_param('sii', $typeFilter, $limit, $offset);
    } else {
        $stmt->bind_param('ii', $limit, $offset);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    $images = [];
    while ($row = $result->fetch_assoc()) {
        $row['metadata'] = $row['metadata'] ? json_decode($row['metadata'], true) : null;
        $images[] = $row;
    }
    
    respond([
        'success' => true,
        'data' => [
            'images' => $images,
            'stats' => [
                'total' => (int)$stats['total'],
                'month' => (int)$stats['month_total'],
                'dalle' => (int)$stats['dalle_count'],
                'stable_diffusion' => (int)$stats['sd_count']
            ],
            'total_pages' => ceil($filteredTotal / $limit),
            'current_page' => $page
        ]
    ]);
}

/**
 * Get single image details
 */
function getImage($conn, $auth) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'معرف الصورة مطلوب'], 400);
    }
    
    $stmt = $conn->prepare("
        SELECT 
            i.*,
            u.full_name as creator_name
        FROM ai_generated_images i
        LEFT JOIN users u ON i.user_id = u.id
        WHERE i.id = ?
    ");
    
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'الصورة غير موجودة'], 404);
    }
    
    $image = $result->fetch_assoc();
    $image['metadata'] = $image['metadata'] ? json_decode($image['metadata'], true) : null;
    
    respond(['success' => true, 'data' => $image]);
}

/**
 * Delete image
 */
function deleteImage($conn, $auth) {
    $id = intval($_GET['id'] ?? 0);
    
    if (!$id) {
        respond(['success' => false, 'message' => 'معرف الصورة مطلوب'], 400);
    }
    
    // Get file path
    $stmt = $conn->prepare("SELECT filename FROM ai_generated_images WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'الصورة غير موجودة'], 404);
    }
    
    $filename = $result->fetch_assoc()['filename'];
    
    // Delete file
    $filePath = UPLOAD_DIR . $filename;
    if (file_exists($filePath)) {
        unlink($filePath);
    }
    
    // Delete record
    $deleteStmt = $conn->prepare("DELETE FROM ai_generated_images WHERE id = ?");
    $deleteStmt->bind_param('i', $id);
    $deleteStmt->execute();
    
    respond(['success' => true, 'message' => 'تم حذف الصورة بنجاح']);
}

/**
 * Apply watermark to image
 */
function applyWatermark($conn, $auth) {
    $input = json_decode(file_get_contents('php://input'), true);
    $imageId = intval($input['image_id'] ?? 0);
    $watermarkText = $input['watermark_text'] ?? 'منصة إبداع';
    $position = $input['position'] ?? 'bottom-right'; // top-left, top-right, bottom-left, bottom-right, center
    
    if (!$imageId) {
        respond(['success' => false, 'message' => 'معرف الصورة مطلوب'], 400);
    }
    
    // Get image
    $stmt = $conn->prepare("SELECT filename FROM ai_generated_images WHERE id = ?");
    $stmt->bind_param('i', $imageId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        respond(['success' => false, 'message' => 'الصورة غير موجودة'], 404);
    }
    
    $filename = $result->fetch_assoc()['filename'];
    $filePath = UPLOAD_DIR . $filename;
    
    if (!file_exists($filePath)) {
        respond(['success' => false, 'message' => 'ملف الصورة غير موجود'], 404);
    }
    
    // Load image
    $image = imagecreatefrompng($filePath);
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Create watermark
    $white = imagecolorallocatealpha($image, 255, 255, 255, 50);
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
    $textHeight = imagefontheight($fontSize);
    
    // Calculate position
    $padding = 10;
    switch ($position) {
        case 'top-left':
            $x = $padding;
            $y = $padding;
            break;
        case 'top-right':
            $x = $width - $textWidth - $padding;
            $y = $padding;
            break;
        case 'bottom-left':
            $x = $padding;
            $y = $height - $textHeight - $padding;
            break;
        case 'bottom-right':
            $x = $width - $textWidth - $padding;
            $y = $height - $textHeight - $padding;
            break;
        case 'center':
            $x = ($width - $textWidth) / 2;
            $y = ($height - $textHeight) / 2;
            break;
        default:
            $x = $width - $textWidth - $padding;
            $y = $height - $textHeight - $padding;
    }
    
    // Add watermark
    imagestring($image, $fontSize, $x, $y, $watermarkText, $white);
    
    // Save
    $newFilename = 'watermarked_' . time() . '_' . $filename;
    imagepng($image, UPLOAD_DIR . $newFilename);
    imagedestroy($image);
    
    respond([
        'success' => true,
        'message' => 'تم إضافة العلامة المائية بنجاح',
        'filename' => $newFilename,
        'url' => 'uploads/ai_images/' . $newFilename
    ]);
}

/**
 * Enhance image (upscale, denoise, etc.)
 */
function enhanceImage($conn, $auth) {
    respond(['success' => true, 'message' => 'قيد التطوير - Image Enhancement']);
}

/**
 * Get prompt templates
 */
function getTemplates($conn, $auth) {
    $templates = [
        'course' => [
            ['name' => 'دورة برمجة', 'prompt' => 'A professional programming course cover image with laptop and code'],
            ['name' => 'دورة تصميم', 'prompt' => 'Creative design course cover with colorful graphics and tools'],
            ['name' => 'دورة إدارة', 'prompt' => 'Business management course cover with professional setting'],
        ],
        'announcement' => [
            ['name' => 'إعلان عام', 'prompt' => 'Eye-catching announcement poster with modern design'],
            ['name' => 'حدث قادم', 'prompt' => 'Event announcement with calendar and celebration elements'],
        ],
        'certificate' => [
            ['name' => 'شهادة إنجاز', 'prompt' => 'Elegant certificate design with golden border and emblem'],
            ['name' => 'شهادة تقدير', 'prompt' => 'Professional appreciation certificate with laurel wreath'],
        ]
    ];
    
    respond(['success' => true, 'templates' => $templates]);
}

/**
 * Generate batch of images
 */
function generateBatch($conn, $auth) {
    respond(['success' => true, 'message' => 'قيد التطوير - Batch Generation']);
}
