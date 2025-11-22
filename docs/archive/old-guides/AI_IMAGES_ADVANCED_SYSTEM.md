# 🎨 نظام توليد الصور بالذكاء الاصطناعي المتقدم
## Advanced AI Image Generation System

**التاريخ:** 11 نوفمبر 2025  
**الحالة:** ✅ مكتمل ومتاح في جميع لوحات التحكم  
**الإصدار:** v2.0 - Advanced Edition

---

## 📋 نظرة عامة

نظام متقدم ومتكامل لتوليد الصور باستخدام تقنيات الذكاء الاصطناعي الحديثة. يدعم محركات متعددة ويوفر واجهة احترافية سهلة الاستخدام.

---

## ✨ الميزات الرئيسية

### 1. 🤖 محركات ذكاء اصطناعي متعددة
- **DALL-E 3 (OpenAI)** - أحدث تقنيات OpenAI
- **Stable Diffusion XL** - مفتوح المصدر وقوي
- **نموذج محلي** - للاستخدام دون الاتصال بالإنترنت

### 2. 🎨 أنواع صور متعددة
- ✅ **صور الدورات التدريبية** - تصاميم احترافية تعليمية
- ✅ **صور الإعلانات** - تصاميم جذابة للعين
- ✅ **صور الشهادات** - تصاميم أنيقة وراقية
- ✅ **بانرات إعلانية** - تصاميم عرضية حديثة
- ✅ **شعارات** - تصاميم مبتكرة للهوية
- ✅ **صور عامة** - لأي غرض آخر

### 3. 🖌️ أساليب فنية متنوعة
- **واقعي (Realistic)** - صور فوتوغرافية واقعية
- **فني (Artistic)** - لمسة فنية إبداعية
- **كرتوني (Cartoon)** - رسوم توضيحية ممتعة
- **تجريدي (Abstract)** - تصاميم حديثة مجردة
- **بسيط (Minimalist)** - تصاميم نظيفة بسيطة
- **احترافي (Professional)** - تصاميم رسمية للأعمال

### 4. 🛠️ خيارات متقدمة
- ✅ **تحسين الوصف بالذكاء الاصطناعي** - يحسن جودة الـ Prompt تلقائياً
- ✅ **اختيار الحجم** - مربع، عرضي، عمودي
- ✅ **التحكم بالجودة** - من 1% إلى 100%
- ✅ **إضافة علامة مائية** - حماية الصور
- ✅ **تحسين تلقائي** - معالجة ذكية للصورة

### 5. 📊 إحصائيات شاملة
- عدد الصور الإجمالي
- صور الشهر الحالي
- إحصائيات لكل محرك AI
- تصنيف حسب النوع

### 6. 🖼️ معرض صور احترافي
- عرض شبكي متجاوب
- بحث متقدم
- تصفية حسب النوع
- معاينة فورية
- تحميل ونسخ الروابط

### 7. 📝 قوالب جاهزة
- قوالب للدورات التدريبية
- قوالب للإعلانات
- قوالب للشهادات
- قوالب للبانرات
- قوالب للشعارات

---

## 📁 هيكل الملفات

```
Manager/
├── api/
│   └── ai_image_generator.php          # API الرئيسي (660 سطر)
├── dashboards/
│   ├── manager-dashboard.php           # لوحة المدير (محدثة)
│   ├── manager-features.js             # الوظائف (محدثة - +800 سطر)
│   └── shared-header.php               # الهيدر المشترك
├── assets/
│   └── css/
│       └── ai-images.css               # أنماط مخصصة (350 سطر)
└── uploads/
    └── ai_images/                      # مجلد الصور المولدة
```

---

## 🗄️ قاعدة البيانات

### جدول `ai_generated_images`

```sql
CREATE TABLE IF NOT EXISTS ai_generated_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    image_type ENUM('course', 'announcement', 'certificate', 'banner', 'logo', 'general') NOT NULL,
    prompt TEXT NOT NULL,
    filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(512) NOT NULL,
    provider ENUM('dalle', 'stable-diffusion', 'local') NOT NULL,
    metadata JSON DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_type (image_type),
    INDEX idx_provider (provider),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### حقول الـ Metadata (JSON):
```json
{
    "enhanced_prompt": "النص المحسن",
    "style": "professional",
    "size": "1024x1024",
    "quality": 85,
    "generation_time": 3.5,
    "auto_enhanced": true,
    "watermark": false
}
```

---

## 🔌 API Documentation

### Base URL
```
/Manager/api/ai_image_generator.php
```

### 1. توليد صورة جديدة

**Endpoint:** `?action=generate`  
**Method:** `POST`  
**الصلاحيات:** `manager`, `technical`, `trainer`

**Request Body:**
```json
{
    "prompt": "professional course cover image",
    "type": "course",
    "style": "professional",
    "provider": "dalle",
    "size": "1024x1024",
    "quality": 85,
    "addWatermark": false,
    "autoEnhance": true
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم إنشاء الصورة بنجاح",
    "image_id": 42,
    "filename": "dalle_1699728000_1234.png",
    "url": "uploads/ai_images/dalle_1699728000_1234.png",
    "provider": "dalle"
}
```

---

### 2. قائمة الصور مع الإحصائيات

**Endpoint:** `?action=list&type={type}&page={page}&limit={limit}`  
**Method:** `GET`  
**الصلاحيات:** جميع المستخدمين المسجلين

**Parameters:**
- `type` (optional): `all`, `course`, `announcement`, `certificate`, `banner`, `logo`, `general`
- `page` (optional): رقم الصفحة (default: 1)
- `limit` (optional): عدد العناصر (default: 20, max: 50)

**Response:**
```json
{
    "success": true,
    "data": {
        "images": [
            {
                "id": 1,
                "user_id": 2,
                "image_type": "course",
                "prompt": "professional programming course",
                "filename": "dalle_1699728000_1234.png",
                "file_path": "uploads/ai_images/dalle_1699728000_1234.png",
                "provider": "dalle",
                "metadata": {
                    "style": "professional",
                    "size": "1024x1024"
                },
                "created_at": "2025-11-11 10:30:00",
                "creator_name": "أحمد محمد"
            }
        ],
        "stats": {
            "total": 150,
            "month": 42,
            "dalle": 80,
            "stable_diffusion": 70
        },
        "total_pages": 8,
        "current_page": 1
    }
}
```

---

### 3. تفاصيل صورة

**Endpoint:** `?action=get&id={id}`  
**Method:** `GET`

**Response:**
```json
{
    "success": true,
    "data": {
        "id": 1,
        "prompt": "professional course image",
        "filename": "dalle_1699728000_1234.png",
        "file_path": "uploads/ai_images/dalle_1699728000_1234.png",
        "provider": "dalle",
        "metadata": {...},
        "created_at": "2025-11-11 10:30:00"
    }
}
```

---

### 4. حذف صورة

**Endpoint:** `?action=delete`  
**Method:** `POST`  
**الصلاحيات:** `manager`, `technical`

**Request Body:**
```json
{
    "image_id": 42
}
```

**Response:**
```json
{
    "success": true,
    "message": "تم حذف الصورة بنجاح"
}
```

---

### 5. إضافة علامة مائية

**Endpoint:** `?action=apply_watermark`  
**Method:** `POST`  
**الصلاحيات:** `manager`, `technical`

**Request Body:**
```json
{
    "image_id": 42,
    "watermark_text": "منصة إبداع",
    "position": "bottom-right"
}
```

**Positions:** `top-left`, `top-right`, `bottom-left`, `bottom-right`, `center`

**Response:**
```json
{
    "success": true,
    "message": "تم إضافة العلامة المائية بنجاح",
    "filename": "watermarked_1699728000_dalle_1699728000_1234.png",
    "url": "uploads/ai_images/watermarked_1699728000_dalle_1699728000_1234.png"
}
```

---

### 6. قوالب جاهزة

**Endpoint:** `?action=get_templates`  
**Method:** `GET`

**Response:**
```json
{
    "success": true,
    "templates": {
        "course": [
            {
                "name": "دورة برمجة",
                "prompt": "A professional programming course cover..."
            }
        ],
        "announcement": [...],
        "certificate": [...]
    }
}
```

---

## 💻 واجهة المستخدم

### الوصول إلى النظام

#### في لوحة المدير:
1. افتح **لوحة التحكم**
2. من القائمة الجانبية، اذهب إلى **التقارير والأدوات**
3. اضغط على **توليد الصور AI**

أو مباشرة:
```javascript
navigateTo('ai-images');
```

### المكونات الرئيسية:

#### 1. بطاقات الإحصائيات (Stats Cards)
```
┌─────────────┬─────────────┬─────────────┬─────────────┐
│ إجمالي      │ هذا الشهر   │ DALL-E      │ Stable      │
│ الصور       │             │             │ Diffusion   │
│   150       │     42      │     80      │     70      │
└─────────────┴─────────────┴─────────────┴─────────────┘
```

#### 2. لوحة الإنشاء (Generation Panel)
- نوع الصورة (6 خيارات)
- حقل الوصف (Prompt) مع زر تحسين AI
- اختيار الأسلوب (6 أساليب)
- محرك AI (3 محركات)
- الحجم (3 أحجام)
- خيارات متقدمة (قابلة للطي)
- زر التوليد

#### 3. معرض الصور (Gallery)
- شبكة متجاوبة 3 أعمدة
- بحث مباشر
- تصفية حسب النوع
- معاينة عند التمرير
- ترقيم الصفحات

#### 4. القوالب الجاهزة
- 6 قوالب أساسية
- تطبيق فوري
- قابلة للتخصيص

---

## 🎯 حالات الاستخدام

### 1. إنشاء صورة دورة تدريبية

```javascript
// التنقل للصفحة
navigateTo('ai-images');

// اختيار النوع
document.getElementById('imageType').value = 'course';

// كتابة الوصف
document.getElementById('imagePrompt').value = 
    'دورة برمجة Python احترافية';

// تحسين الوصف (اختياري)
await enhancePrompt();

// اختيار الأسلوب
selectStyle('professional');

// توليد الصورة
document.getElementById('aiImageForm').submit();
```

### 2. استخدام قالب جاهز

```javascript
// تطبيق قالب
applyTemplate(0); // قالب دورة تقنية

// تخصيص إضافي (اختياري)
document.getElementById('imagePrompt').value += 
    ', شعار منصة إبداع';

// توليد
document.getElementById('aiImageForm').submit();
```

### 3. إضافة علامة مائية لصورة

```javascript
// فتح معاينة الصورة
openImagePreview(image);

// في المودال، سيكون زر العلامة المائية متاحاً
// أو استخدام API مباشرة:
await fetch(API_ENDPOINTS.aiImages + '?action=apply_watermark', {
    method: 'POST',
    body: JSON.stringify({
        image_id: 42,
        watermark_text: 'منصة إبداع',
        position: 'bottom-right'
    })
});
```

---

## 🚀 البدء السريع

### الخطوة 1: إعداد مفاتيح API (اختياري)

في `ai_image_generator.php`:

```php
// إذا كنت تريد استخدام OpenAI DALL-E
define('OPENAI_API_KEY', 'sk-...your-key...');

// إذا كنت تريد استخدام Stable Diffusion
define('STABILITY_API_KEY', 'sk-...your-key...');
```

> **ملاحظة:** إذا لم تضف المفاتيح، سيعمل النظام بوضع Demo مع صور تجريبية.

### الخطوة 2: إنشاء جدول قاعدة البيانات

قم بتشغيل سكريبت SQL (موجود في القسم السابق).

### الخطوة 3: الوصول إلى النظام

1. سجل دخول كـ **مدير** أو **مشرف فني**
2. اذهب إلى **التقارير والأدوات** → **توليد الصور AI**
3. ابدأ في إنشاء صور رائعة! 🎨

---

## 🎨 أمثلة Prompts فعّالة

### دورات تقنية:
```
Modern computer programming course banner, 
laptop with code on screen, 
professional tech workspace, 
blue and purple gradient background, 
clean minimalist design, 
high quality 4k
```

### دورات لغات:
```
English language learning course poster,
books and globe illustration,
friendly and welcoming atmosphere,
bright colors, educational design,
professional quality
```

### إعلانات:
```
Exciting announcement poster for new course,
vibrant colors, eye-catching design,
modern Arabic typography,
professional layout, 
attention-grabbing
```

### شهادات:
```
Elegant certificate of achievement,
formal design with gold accents,
ornate border, professional layout,
premium quality, sophisticated
```

---

## 🔧 خيارات التخصيص

### 1. إضافة محرك AI جديد

في `ai_image_generator.php`:

```php
function generateWithNewAI($prompt, $size) {
    // كود المحرك الجديد
    
    return [
        'success' => true,
        'filename' => $filename,
        'generation_time' => $time
    ];
}

// في switch case
case 'new-ai':
    $result = generateWithNewAI($enhancedPrompt, $size);
    break;
```

### 2. إضافة نوع صورة جديد

في `manager-features.js`:

```javascript
// إضافة في select
<option value="social-media">صورة سوشيال ميديا</option>

// إضافة تحسين خاص
if (imageType === 'social-media') {
    enhancedPrompt += ', optimized for social media, 1080x1080';
}
```

### 3. إضافة أسلوب فني جديد

```javascript
<button type="button" 
        onclick="selectStyle('vintage')" 
        data-style="vintage" 
        class="style-btn ...">
    كلاسيكي
</button>

// في enhancePrompt
if (style === 'vintage') {
    enhancedPrompt += ', vintage style, retro colors, classic design';
}
```

---

## 📊 التحليلات والإحصائيات

### معلومات متاحة:
- إجمالي الصور المولدة
- الصور في الشهر الحالي
- توزيع حسب المحرك (DALL-E vs Stable Diffusion)
- توزيع حسب النوع (دورات، إعلانات، شهادات، إلخ)
- متوسط وقت التوليد
- الصور الأكثر استخداماً

### تصدير البيانات:
```javascript
// تصدير إحصائيات إلى CSV
function exportAIStats() {
    const stats = await fetch(API_ENDPOINTS.aiImages + '?action=stats');
    // تحويل إلى CSV
    // تحميل الملف
}
```

---

## ⚡ الأداء والتحسين

### نصائح للأداء الأمثل:

1. **استخدم Caching:**
```php
// في ai_image_generator.php
if (file_exists(CACHE_DIR . $cacheKey)) {
    return getCachedImage($cacheKey);
}
```

2. **ضغط الصور:**
```php
function compressImage($filename) {
    $image = imagecreatefrompng(UPLOAD_DIR . $filename);
    imagepng($image, UPLOAD_DIR . $filename, 7); // 0-9
}
```

3. **تحميل كسول (Lazy Loading):**
```html
<img src="..." loading="lazy" alt="...">
```

4. **WebP Format:**
```php
imagewebp($image, UPLOAD_DIR . $filename . '.webp', 80);
```

---

## 🔒 الأمان

### إجراءات الأمان المطبقة:

1. ✅ **التحقق من الجلسة**
2. ✅ **فحص الصلاحيات**
3. ✅ **تنظيف المدخلات**
4. ✅ **حماية من SQL Injection**
5. ✅ **تحديد حجم الملفات**
6. ✅ **التحقق من نوع الملفات**

### توصيات إضافية:

```php
// تحديد معدل الطلبات (Rate Limiting)
$maxRequestsPerHour = 50;

// التحقق من الوصف (تصفية كلمات غير مناسبة)
function filterPrompt($prompt) {
    $bannedWords = ['...'];
    // فلترة
    return $cleanPrompt;
}
```

---

## 🐛 استكشاف الأخطاء

### مشكلة: "فشل طلب DALL-E"
**الحل:**
1. تحقق من صحة API Key
2. تأكد من وجود رصيد في حساب OpenAI
3. افحص اتصال الإنترنت

### مشكلة: "لم يتم إرجاع صورة"
**الحل:**
1. راجع الـ Prompt (قد يكون مخالفاً للسياسات)
2. جرب محرك AI آخر
3. افحص سجل الأخطاء (error logs)

### مشكلة: "الصور لا تظهر في المعرض"
**الحل:**
1. تحقق من صلاحيات المجلد `uploads/ai_images/`
2. افحص console.log للأخطاء
3. تأكد من وجود البيانات في قاعدة البيانات

---

## 📱 التوافق مع الأجهزة

### متصفحات مدعومة:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### الأجهزة:
- ✅ Desktop (1920x1080+)
- ✅ Laptop (1366x768+)
- ✅ Tablet (768x1024+)
- ✅ Mobile (375x667+)

---

## 🎓 أمثلة متقدمة

### مثال 1: توليد دفعة من الصور

```javascript
async function generateBatch(prompts) {
    const results = [];
    
    for (const prompt of prompts) {
        const result = await fetch(API_ENDPOINTS.aiImages + '?action=generate', {
            method: 'POST',
            body: JSON.stringify({
                prompt: prompt,
                type: 'course',
                style: 'professional',
                provider: 'dalle',
                size: '1024x1024'
            })
        });
        
        results.push(await result.json());
        
        // انتظار 2 ثانية بين كل صورة
        await new Promise(resolve => setTimeout(resolve, 2000));
    }
    
    return results;
}

// الاستخدام
const courses = ['Python', 'JavaScript', 'React', 'Node.js'];
const prompts = courses.map(c => 
    `Professional ${c} programming course banner`
);
await generateBatch(prompts);
```

### مثال 2: تحليل أداء المحركات

```javascript
async function compareAIEngines() {
    const prompt = 'Professional course banner';
    const engines = ['dalle', 'stable-diffusion'];
    const results = {};
    
    for (const engine of engines) {
        const startTime = Date.now();
        
        const response = await fetch(API_ENDPOINTS.aiImages + '?action=generate', {
            method: 'POST',
            body: JSON.stringify({
                prompt: prompt,
                provider: engine
            })
        });
        
        const endTime = Date.now();
        const result = await response.json();
        
        results[engine] = {
            success: result.success,
            time: endTime - startTime,
            filename: result.filename
        };
    }
    
    console.table(results);
    return results;
}
```

---

## 📚 المراجع والموارد

### وثائق APIs:
- [OpenAI DALL-E 3](https://platform.openai.com/docs/guides/images)
- [Stability AI](https://platform.stability.ai/docs/getting-started)

### مصادر تعليمية:
- [Prompt Engineering Guide](https://www.promptingguide.ai/)
- [Best Practices for AI Images](https://example.com)

### أدوات مفيدة:
- [Prompt Generator](https://prompthero.com/)
- [Image Analyzer](https://example.com)

---

## 🎉 الخلاصة

نظام توليد الصور بالذكاء الاصطناعي المتقدم يوفر:

✅ **سهولة الاستخدام** - واجهة بديهية وسلسة  
✅ **قوة ومرونة** - محركات متعددة وخيارات واسعة  
✅ **احترافية** - جودة عالية ونتائج مذهلة  
✅ **تكامل كامل** - يعمل مع جميع أنظمة المنصة  
✅ **قابلية التوسع** - سهل التطوير والتخصيص  

**جاهز للاستخدام في الإنتاج!** 🚀

---

**تم التطوير بواسطة:** فريق منصة إبداع تعز  
**التاريخ:** 11 نوفمبر 2025  
**الإصدار:** v2.0 Advanced
