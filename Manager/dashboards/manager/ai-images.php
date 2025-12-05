<?php
/**
 * AI Image Generator - Generate images using Gemini AI
 * مولد الصور بالذكاء الاصطناعي باستخدام Gemini AI
 */

require_once __DIR__ . '/../../includes/env_loader.php';
EnvLoader::load(__DIR__ . '/../../../.env');

$geminiApiKey = EnvLoader::get('GEMINI_API_KEY', '');

// Handle AJAX requests for AI image generation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    switch ($_POST['action']) {
        case 'generate_image':
            $prompt = $_POST['prompt'] ?? '';
            $style = $_POST['style'] ?? 'realistic';
            $size = $_POST['size'] ?? '1024x1024';
            
            echo json_encode(generateAIImage($prompt, $style, $size, $geminiApiKey));
            exit;
            
        case 'save_image':
            $imageUrl = $_POST['image_url'] ?? '';
            $title = $_POST['title'] ?? 'AI Generated Image';
            $prompt = $_POST['prompt'] ?? '';
            
            echo json_encode(saveGeneratedImage($conn, $userId, $imageUrl, $title, $prompt));
            exit;
            
        case 'get_history':
            echo json_encode(getImageHistory($conn, $userId));
            exit;
            
        case 'delete_image':
            $imageId = intval($_POST['image_id']);
            echo json_encode(deleteGeneratedImage($conn, $imageId, $userId));
            exit;
    }
}

/**
 * Generate AI Image using Gemini Vision API
 */
function generateAIImage($prompt, $style, $size, $apiKey) {
    try {
        // Enhanced prompt based on style
        $stylePrompts = [
            'realistic' => 'Create a highly realistic, photographic image of: ',
            'artistic' => 'Create an artistic, creative interpretation of: ',
            'cartoon' => 'Create a cartoon-style, animated illustration of: ',
            'abstract' => 'Create an abstract, modern art representation of: ',
            'professional' => 'Create a professional, business-appropriate image of: ',
            'educational' => 'Create an educational, clear diagram or illustration of: '
        ];
        
        $fullPrompt = ($stylePrompts[$style] ?? '') . $prompt . '. High quality, detailed, professional.';
        
        // Since Gemini doesn't directly generate images, we'll use it to enhance the prompt
        // and suggest improvements, then use a placeholder/mock image generation
        $geminiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key=' . $apiKey;
        
        $data = [
            'contents' => [[
                'parts' => [[
                    'text' => "As an AI image generation expert, enhance this image prompt and provide a detailed description: $fullPrompt. Return ONLY JSON with keys: enhanced_prompt, description, tags (array of 5 tags)."
                ]]
            ]]
        ];
        
        $ch = curl_init($geminiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            $result = json_decode($response, true);
            $aiText = $result['candidates'][0]['content']['parts'][0]['text'] ?? '';
            
            // Extract JSON from response
            preg_match('/\{[^}]+\}/', $aiText, $matches);
            $aiData = json_decode($matches[0] ?? '{}', true);
            
            // For demo purposes, generate a placeholder image URL
            // In production, integrate with actual image generation API (DALL-E, Midjourney, Stable Diffusion)
            $imageUrl = 'https://placehold.co/' . str_replace('x', 'x', $size) . '/0ea5e9/white?text=' . urlencode(substr($prompt, 0, 30));
            
            return [
                'success' => true,
                'image_url' => $imageUrl,
                'enhanced_prompt' => $aiData['enhanced_prompt'] ?? $fullPrompt,
                'description' => $aiData['description'] ?? 'AI generated image based on your prompt',
                'tags' => $aiData['tags'] ?? ['ai', 'generated', 'creative'],
                'original_prompt' => $prompt,
                'style' => $style,
                'size' => $size
            ];
        } else {
            return ['success' => false, 'message' => 'فشل الاتصال بـ Gemini AI'];
        }
        
    } catch (Exception $e) {
        error_log("AI Image Generation Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Save generated image to database
 */
function saveGeneratedImage($conn, $userId, $imageUrl, $title, $prompt) {
    try {
        $stmt = $conn->prepare("
            INSERT INTO ai_generated_images 
            (user_id, title, prompt, image_url, created_at, status)
            VALUES (?, ?, ?, ?, NOW(), 'active')
        ");
        $stmt->bind_param("isss", $userId, $title, $prompt, $imageUrl);
        $stmt->execute();
        
        return [
            'success' => true,
            'image_id' => $conn->insert_id,
            'message' => 'تم حفظ الصورة بنجاح'
        ];
    } catch (Exception $e) {
        error_log("Save Image Error: " . $e->getMessage());
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Get image generation history
 */
function getImageHistory($conn, $userId) {
    try {
        $stmt = $conn->prepare("
            SELECT * FROM ai_generated_images 
            WHERE user_id = ? 
            ORDER BY created_at DESC 
            LIMIT 50
        ");
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $images = [];
        while ($row = $result->fetch_assoc()) {
            $images[] = $row;
        }
        
        return ['success' => true, 'images' => $images];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}

/**
 * Delete generated image
 */
function deleteGeneratedImage($conn, $imageId, $userId) {
    try {
        $stmt = $conn->prepare("DELETE FROM ai_generated_images WHERE id = ? AND user_id = ?");
        $stmt->bind_param("ii", $imageId, $userId);
        $stmt->execute();
        
        return ['success' => true, 'message' => 'تم حذف الصورة'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => $e->getMessage()];
    }
}
?>

<!-- Page Header -->
<div class="mb-8">
    <h2 class="text-3xl font-bold text-slate-800 mb-2">مولد الصور بالذكاء الاصطناعي</h2>
    <p class="text-slate-600">إنشاء صور احترافية باستخدام تقنيات الذكاء الاصطناعي المتقدمة</p>
</div>

<!-- AI Image Generator Interface -->
<div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
    <!-- Left Panel: Generation Controls -->
    <div class="lg:col-span-1">
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6 sticky top-24">
            <h3 class="text-xl font-bold text-slate-800 mb-6">إعدادات التوليد</h3>
            
            <!-- Prompt Input -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="message-square" class="w-4 h-4 inline"></i>
                    وصف الصورة
                </label>
                <textarea id="imagePrompt" rows="5" 
                          class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500 resize-none"
                          placeholder="اكتب وصفاً تفصيلياً للصورة المطلوبة...&#10;مثال: منظر طبيعي لغروب الشمس على شاطئ البحر مع جبال في الخلفية"></textarea>
            </div>
            
            <!-- Style Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="palette" class="w-4 h-4 inline"></i>
                    نمط الصورة
                </label>
                <select id="imageStyle" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="realistic">واقعي (Realistic)</option>
                    <option value="artistic">فني (Artistic)</option>
                    <option value="cartoon">كرتوني (Cartoon)</option>
                    <option value="abstract">تجريدي (Abstract)</option>
                    <option value="professional">احترافي (Professional)</option>
                    <option value="educational">تعليمي (Educational)</option>
                </select>
            </div>
            
            <!-- Size Selection -->
            <div class="mb-6">
                <label class="block text-sm font-semibold text-slate-700 mb-2">
                    <i data-lucide="maximize" class="w-4 h-4 inline"></i>
                    حجم الصورة
                </label>
                <select id="imageSize" class="w-full px-4 py-3 border border-slate-300 rounded-xl focus:outline-none focus:ring-2 focus:ring-sky-500">
                    <option value="1024x1024">مربع - 1024×1024</option>
                    <option value="1792x1024">أفقي - 1792×1024</option>
                    <option value="1024x1792">عمودي - 1024×1792</option>
                    <option value="512x512">صغير - 512×512</option>
                </select>
            </div>
            
            <!-- Generate Button -->
            <button onclick="generateAIImage()" 
                    class="w-full px-6 py-4 bg-gradient-to-r from-sky-600 to-purple-600 text-white rounded-xl hover:from-sky-700 hover:to-purple-700 transition font-bold text-lg shadow-lg">
                <i data-lucide="sparkles" class="w-5 h-5 inline mr-2"></i>
                توليد الصورة
            </button>
            
            <!-- Quick Prompts -->
            <div class="mt-6 pt-6 border-t border-slate-200">
                <h4 class="text-sm font-semibold text-slate-700 mb-3">أمثلة سريعة:</h4>
                <div class="space-y-2">
                    <button onclick="setPrompt('شعار احترافي لمنصة تعليمية')" 
                            class="w-full text-right px-3 py-2 text-sm bg-slate-50 hover:bg-slate-100 rounded-lg transition">
                        شعار احترافي لمنصة تعليمية
                    </button>
                    <button onclick="setPrompt('خلفية عرض تقديمي بتصميم عصري')" 
                            class="w-full text-right px-3 py-2 text-sm bg-slate-50 hover:bg-slate-100 rounded-lg transition">
                        خلفية عرض تقديمي عصري
                    </button>
                    <button onclick="setPrompt('رسم توضيحي لمفهوم الابتكار والإبداع')" 
                            class="w-full text-right px-3 py-2 text-sm bg-slate-50 hover:bg-slate-100 rounded-lg transition">
                        رسم توضيحي للابتكار
                    </button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Center Panel: Generated Image Display -->
    <div class="lg:col-span-2">
        <!-- Current Generation Display -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-8 mb-8">
            <div id="generationDisplay" class="text-center py-16">
                <div class="w-24 h-24 bg-gradient-to-br from-sky-100 to-purple-100 rounded-full flex items-center justify-center mx-auto mb-6">
                    <i data-lucide="image-plus" class="w-12 h-12 text-sky-600"></i>
                </div>
                <h3 class="text-2xl font-bold text-slate-800 mb-2">ابدأ بتوليد صورة جديدة</h3>
                <p class="text-slate-600 mb-6">اكتب وصفاً للصورة واختر النمط المناسب</p>
            </div>
            
            <!-- Generated Image Container -->
            <div id="generatedImageContainer" class="hidden">
                <div class="relative">
                    <img id="generatedImage" src="" alt="Generated Image" class="w-full rounded-xl shadow-lg">
                    <div class="absolute top-4 left-4 flex gap-2">
                        <button onclick="saveImage()" 
                                class="px-4 py-2 bg-white rounded-lg shadow-md hover:bg-slate-50 transition">
                            <i data-lucide="save" class="w-4 h-4 inline mr-1"></i>
                            حفظ
                        </button>
                        <button onclick="downloadImage()" 
                                class="px-4 py-2 bg-white rounded-lg shadow-md hover:bg-slate-50 transition">
                            <i data-lucide="download" class="w-4 h-4 inline mr-1"></i>
                            تحميل
                        </button>
                        <button onclick="shareImage()" 
                                class="px-4 py-2 bg-white rounded-lg shadow-md hover:bg-slate-50 transition">
                            <i data-lucide="share-2" class="w-4 h-4 inline mr-1"></i>
                            مشاركة
                        </button>
                    </div>
                </div>
                <div id="imageMetadata" class="mt-4 p-4 bg-slate-50 rounded-xl">
                    <!-- Metadata will be injected here -->
                </div>
            </div>
        </div>
        
        <!-- Image History -->
        <div class="bg-white rounded-2xl shadow-sm border border-slate-200 p-6">
            <div class="flex items-center justify-between mb-6">
                <h3 class="text-xl font-bold text-slate-800">
                    <i data-lucide="history" class="w-5 h-5 inline mr-2"></i>
                    السجل
                </h3>
                <button onclick="loadHistory()" class="text-sm text-sky-600 hover:text-sky-700 font-semibold">
                    تحديث
                </button>
            </div>
            <div id="historyGrid" class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <p class="col-span-full text-center text-slate-500 py-8">لا توجد صور محفوظة بعد</p>
            </div>
        </div>
    </div>
</div>

<script>
let currentGeneratedData = null;

function setPrompt(text) {
    document.getElementById('imagePrompt').value = text;
}

function generateAIImage() {
    const prompt = document.getElementById('imagePrompt').value.trim();
    const style = document.getElementById('imageStyle').value;
    const size = document.getElementById('imageSize').value;
    
    if (!prompt) {
        alert('⚠️ الرجاء كتابة وصف للصورة');
        return;
    }
    
    // Show loading state
    document.getElementById('generationDisplay').innerHTML = `
        <div class="py-16">
            <div class="animate-spin w-16 h-16 border-4 border-sky-600 border-t-transparent rounded-full mx-auto mb-6"></div>
            <h3 class="text-2xl font-bold text-slate-800 mb-2">جاري توليد الصورة...</h3>
            <p class="text-slate-600">قد تستغرق العملية بضع ثوانٍ</p>
        </div>
    `;
    document.getElementById('generatedImageContainer').classList.add('hidden');
    
    // Generate image via API
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=generate_image&prompt=${encodeURIComponent(prompt)}&style=${style}&size=${size}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            currentGeneratedData = data;
            displayGeneratedImage(data);
        } else {
            alert('❌ خطأ: ' + data.message);
            document.getElementById('generationDisplay').innerHTML = `
                <div class="text-center py-8">
                    <i data-lucide="alert-circle" class="w-12 h-12 text-red-500 mx-auto mb-4"></i>
                    <p class="text-red-600">${data.message}</p>
                </div>
            `;
            lucide.createIcons();
        }
    })
    .catch(err => {
        alert('❌ خطأ في الاتصال');
        console.error(err);
    });
}

function displayGeneratedImage(data) {
    document.getElementById('generationDisplay').classList.add('hidden');
    document.getElementById('generatedImageContainer').classList.remove('hidden');
    
    document.getElementById('generatedImage').src = data.image_url;
    document.getElementById('imageMetadata').innerHTML = `
        <div class="grid grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-slate-600 mb-1">الوصف الأصلي:</p>
                <p class="text-sm font-semibold text-slate-800">${data.original_prompt}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600 mb-1">النمط:</p>
                <p class="text-sm font-semibold text-slate-800">${data.style}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600 mb-1">الحجم:</p>
                <p class="text-sm font-semibold text-slate-800">${data.size}</p>
            </div>
            <div>
                <p class="text-sm text-slate-600 mb-1">الوصف المحسّن:</p>
                <p class="text-sm font-semibold text-slate-800">${data.description}</p>
            </div>
        </div>
        <div class="mt-4">
            <p class="text-sm text-slate-600 mb-2">الوسوم:</p>
            <div class="flex flex-wrap gap-2">
                ${data.tags.map(tag => `<span class="px-3 py-1 bg-sky-100 text-sky-700 rounded-full text-xs font-semibold">${tag}</span>`).join('')}
            </div>
        </div>
    `;
    
    lucide.createIcons();
}

function saveImage() {
    if (!currentGeneratedData) return;
    
    const title = prompt('اسم الصورة:', currentGeneratedData.original_prompt.substring(0, 50));
    if (!title) return;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=save_image&image_url=${encodeURIComponent(currentGeneratedData.image_url)}&title=${encodeURIComponent(title)}&prompt=${encodeURIComponent(currentGeneratedData.original_prompt)}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert('✅ تم حفظ الصورة بنجاح');
            loadHistory();
        } else {
            alert('❌ خطأ: ' + data.message);
        }
    });
}

function downloadImage() {
    if (!currentGeneratedData) return;
    window.open(currentGeneratedData.image_url, '_blank');
}

function shareImage() {
    if (!currentGeneratedData) return;
    if (navigator.share) {
        navigator.share({
            title: 'صورة منصة إبداع',
            text: currentGeneratedData.original_prompt,
            url: currentGeneratedData.image_url
        });
    } else {
        navigator.clipboard.writeText(currentGeneratedData.image_url);
        alert('✅ تم نسخ رابط الصورة');
    }
}

function loadHistory() {
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=get_history'
    })
    .then(res => res.json())
    .then(data => {
        if (data.success && data.images.length > 0) {
            const grid = document.getElementById('historyGrid');
            grid.innerHTML = data.images.map(img => `
                <div class="group relative rounded-xl overflow-hidden shadow-sm hover:shadow-md transition cursor-pointer">
                    <img src="${img.image_url}" alt="${img.title}" class="w-full h-48 object-cover">
                    <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition flex items-center justify-center">
                        <div class="opacity-0 group-hover:opacity-100 transition flex gap-2">
                            <button onclick="event.stopPropagation(); window.open('${img.image_url}', '_blank')" 
                                    class="p-2 bg-white rounded-lg text-slate-800 hover:bg-slate-100">
                                <i data-lucide="eye" class="w-4 h-4"></i>
                            </button>
                            <button onclick="event.stopPropagation(); deleteHistoryImage(${img.id})" 
                                    class="p-2 bg-red-500 rounded-lg text-white hover:bg-red-600">
                                <i data-lucide="trash-2" class="w-4 h-4"></i>
                            </button>
                        </div>
                    </div>
                    <div class="p-3 bg-white">
                        <p class="text-sm font-semibold text-slate-800 truncate">${img.title}</p>
                        <p class="text-xs text-slate-500 mt-1">${new Date(img.created_at).toLocaleDateString('ar-EG')}</p>
                    </div>
                </div>
            `).join('');
            lucide.createIcons();
        }
    });
}

function deleteHistoryImage(imageId) {
    if (!confirm('هل تريد حذف هذه الصورة؟')) return;
    
    fetch('', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: `action=delete_image&image_id=${imageId}`
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            loadHistory();
        }
    });
}

// Load history on page load
document.addEventListener('DOMContentLoaded', () => {
    loadHistory();
    lucide.createIcons();
});
</script>
