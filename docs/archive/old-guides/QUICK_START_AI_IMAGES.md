# 🎨 Quick Start Guide - AI Image Generation
## دليل التشغيل السريع - توليد الصور بالذكاء الاصطناعي

---

## ⚡ 5-Minute Setup

### Step 1: Database Setup (1 min)

```bash
# Option A: MySQL Command Line
mysql -u root -p ibdaa_platform < database/ai_images_system.sql

# Option B: PowerShell (Windows with XAMPP)
Get-Content database\ai_images_system.sql | mysql -u root ibdaa_platform
```

Or via **phpMyAdmin**:
1. Open http://localhost/phpmyadmin
2. Select database `ibdaa_platform`
3. Click **Import** tab
4. Choose `database/ai_images_system.sql`
5. Click **Go** ✅

---

### Step 2: Create Upload Directory (30 sec)

```powershell
# Windows PowerShell
New-Item -ItemType Directory -Path "uploads\ai_images" -Force
```

Or manually:
- Navigate to `C:\xampp\htdocs\Ibdaa-Taiz\`
- Create folder `uploads\ai_images\`

---

### Step 3: Test Demo Mode (2 min)

1. **Login** to manager dashboard:
   - URL: http://localhost/Ibdaa-Taiz/Manager/
   - User: Your manager account

2. **Navigate** to AI Images:
   - Click **🎨 AI توليد الصور** in sidebar

3. **Generate Test Image**:
   - Keep default provider (DALL-E)
   - Type prompt: `صورة دورة برمجة`
   - Click **توليد الصورة** 🚀

4. **Verify**:
   - ✅ Image appears in preview panel
   - ✅ Stats update (Total Images: 1)
   - ✅ Gallery shows new image

**Expected Result:** Colorful gradient placeholder with your prompt text

---

### Step 4: (Optional) Add Real AI (1 min)

For production-quality AI images:

1. **Get OpenAI API Key**:
   - Visit: https://platform.openai.com/api-keys
   - Create new secret key
   - Copy key (starts with `sk-...`)

2. **Update Config**:
   - Open: `Manager/api/ai_image_generator.php`
   - Line 13: Replace `YOUR_OPENAI_API_KEY` with your key
   ```php
   define('OPENAI_API_KEY', 'sk-your-actual-key-here');
   ```

3. **Test Real Generation**:
   - Generate another image
   - Wait 5-10 seconds
   - ✨ High-quality AI image appears!

---

## 🎯 Quick Usage Examples

### Generate Course Cover

1. **Image Type:** `صورة دورة تدريبية`
2. **Template:** Select `دورة برمجة` (optional)
3. **Prompt:**
   ```
   Professional programming course cover with laptop, code snippets, 
   modern workspace, blue and purple colors
   ```
4. **Style:** `واقعي` (Realistic)
5. **Size:** `مربع (1024x1024)`
6. Click **توليد الصورة** ✅

---

### Generate Announcement Banner

1. **Image Type:** `إعلان`
2. **Template:** Select `حدث قادم`
3. **Prompt:**
   ```
   Eye-catching event announcement poster with calendar, 
   celebration elements, vibrant colors, modern design
   ```
4. **Style:** `فني` (Artistic)
5. **Size:** `عريض (1920x1080)`
6. Click **توليد الصورة** ✅

---

### Add Watermark

1. **Find image** in gallery
2. Click **عرض** button
3. Or in preview panel, click **إضافة علامة مائية**
4. Enter text: `منصة إبداع - تعز`
5. Confirm ✅
6. New watermarked image created!

---

## 📊 Features Overview

### ✅ What's Working Now

| Feature | Status | Description |
|---------|--------|-------------|
| **Demo Mode** | ✅ Ready | Gradient placeholders without API keys |
| **DALL-E Integration** | ✅ Ready | High-quality AI images (requires API key) |
| **Stable Diffusion** | ✅ Ready | Fast generation (requires API key) |
| **Templates** | ✅ Ready | 13 pre-configured prompts |
| **Gallery** | ✅ Ready | Grid view with filtering |
| **Watermarks** | ✅ Ready | Custom text watermarks |
| **Statistics** | ✅ Ready | Real-time dashboard stats |
| **Download** | ✅ Ready | Save images to device |
| **Delete** | ✅ Ready | Remove unwanted images |

### 🚧 Coming Soon

- **Batch Generation** - Create multiple images at once
- **Image Enhancement** - Upscale and improve quality
- **Social Media Sizes** - Auto-resize for platforms
- **Template Editor** - Create custom templates

---

## 🐛 Common Issues & Solutions

### ❌ "فشل طلب DALL-E"

**Problem:** API key invalid or no credits

**Solution:**
1. Check API key is correct (line 13 in `ai_image_generator.php`)
2. Verify OpenAI account has credits: https://platform.openai.com/usage
3. Use demo mode if testing without credits

---

### ❌ Images not showing in gallery

**Problem:** Database not connected or tables missing

**Solution:**
1. Re-run SQL import:
   ```bash
   mysql -u root ibdaa_platform < database/ai_images_system.sql
   ```
2. Check browser console for errors (F12)
3. Verify session is active (refresh page and re-login)

---

### ❌ Upload directory error

**Problem:** Folder doesn't exist or no permissions

**Solution:**
```powershell
# Windows
New-Item -ItemType Directory -Path "uploads\ai_images" -Force

# Then verify in File Explorer:
C:\xampp\htdocs\Ibdaa-Taiz\uploads\ai_images\
```

---

### ❌ Watermark not visible

**Problem:** GD library missing or image format issue

**Solution:**
1. Check PHP has GD:
   ```powershell
   php -m | Select-String gd
   ```
2. If missing, enable in `php.ini`:
   - Open: `C:\xampp\php\php.ini`
   - Find: `;extension=gd`
   - Remove `;` to uncomment
   - Restart Apache

---

## 🎨 Template Quick Reference

### Course Templates (دورات)
1. **دورة برمجة** - Programming with laptop and code
2. **دورة تصميم جرافيك** - Graphic design with tools
3. **دورة إدارة أعمال** - Business with office setting
4. **دورة تسويق رقمي** - Digital marketing with analytics
5. **دورة لغة إنجليزية** - English with books and flags

### Announcement Templates (إعلانات)
1. **إعلان عام** - General announcement banner
2. **حدث قادم** - Event with calendar
3. **تخفيضات وعروض** - Sales with gift boxes
4. **فتح التسجيل** - Registration open with forms

### Certificate Templates (شهادات)
1. **شهادة إنجاز** - Completion with golden border
2. **شهادة تقدير** - Appreciation with laurel wreath
3. **شهادة تفوق** - Excellence with stars
4. **شهادة مشاركة** - Participation with modern design

---

## 📈 Statistics Dashboard

The main page shows 4 key metrics:

1. **إجمالي الصور** - Total images generated
2. **صور الدورات** - Course images count
3. **الإعلانات** - Announcement images count
4. **الشهادات** - Certificate images count

Updates automatically after each generation!

---

## 🔧 Advanced Configuration

### Change Upload Path

Edit `Manager/api/ai_image_generator.php` line 15:

```php
// Default:
define('UPLOAD_DIR', __DIR__ . '/../../uploads/ai_images/');

// Custom:
define('UPLOAD_DIR', __DIR__ . '/../../custom/path/images/');
```

---

### Change Max Image Size

Edit line 16:

```php
// Default: 5MB
define('MAX_IMAGE_SIZE', 5242880);

// Increase to 10MB:
define('MAX_IMAGE_SIZE', 10485760);
```

---

### Add Custom Template

Database insert:

```sql
INSERT INTO ai_image_templates 
(template_name, template_type, prompt_template, style, recommended_size) 
VALUES 
('دورة فوتوشوب', 'course', 
 'Adobe Photoshop course cover with creative design tools and colorful elements', 
 'artistic', '1024x1024');
```

---

## 🚀 Performance Tips

1. **Use Demo Mode** for testing (faster, free)
2. **Stable Diffusion** is faster than DALL-E (but requires API key)
3. **Smaller sizes** generate faster (1024x1024 vs 1920x1080)
4. **Cache templates** - Select from dropdown instead of typing
5. **Delete unused** images to keep gallery fast

---

## 📱 Mobile Access

The interface is fully responsive!

- ✅ Works on tablets (iPad, Android)
- ✅ Works on phones (iPhone, Android)
- ✅ Touch-friendly buttons
- ✅ Optimized gallery grid

---

## 🎓 Best Practices

### Writing Good Prompts

**✅ Good:**
```
Professional programming course cover with modern laptop, 
Python code snippets, dark blue background, clean minimalist design
```

**❌ Avoid:**
```
course
```

**Tips:**
- Be specific about colors, style, objects
- Include adjectives (professional, modern, colorful)
- Mention background and composition
- Use templates as starting point

---

### Organizing Images

1. **Use consistent naming** in prompts
2. **Tag images** with keywords (future feature)
3. **Delete duplicates** regularly
4. **Archive old** unused images (>90 days)

---

## 🔗 Quick Links

- **Main Dashboard:** http://localhost/Ibdaa-Taiz/Manager/
- **AI Images Page:** Dashboard → 🎨 AI توليد الصور
- **phpMyAdmin:** http://localhost/phpmyadmin
- **Upload Folder:** `C:\xampp\htdocs\Ibdaa-Taiz\uploads\ai_images\`

---

## 📞 Need Help?

1. Check **Troubleshooting** section above
2. Review full docs: `AI_IMAGES_SYSTEM_COMPLETE.md`
3. Check browser console (F12) for errors
4. Verify database tables exist:
   ```sql
   SHOW TABLES LIKE 'ai_%';
   ```

---

## ✅ Quick Checklist

Before going live:

- [ ] Database imported successfully
- [ ] Upload directory created with permissions
- [ ] Demo mode tested (placeholder images work)
- [ ] OpenAI API key added (for production)
- [ ] Template dropdown populates correctly
- [ ] Gallery loads and filters work
- [ ] Watermark feature tested
- [ ] Download feature works
- [ ] Stats update after generation

---

**🎉 You're Ready! Start generating amazing AI images!**

For detailed documentation, see: `AI_IMAGES_SYSTEM_COMPLETE.md`
