# 🎨 AI Image Generation System - Complete Documentation
## نظام توليد الصور بالذكاء الاصطناعي - التوثيق الشامل

**Version:** 1.0.0  
**Last Updated:** 2025  
**Author:** Ibdaa Platform Development Team

---

## 📋 Table of Contents

1. [Overview](#overview)
2. [Features](#features)
3. [Architecture](#architecture)
4. [Installation](#installation)
5. [API Documentation](#api-documentation)
6. [Frontend Components](#frontend-components)
7. [Database Schema](#database-schema)
8. [Usage Examples](#usage-examples)
9. [Configuration](#configuration)
10. [Testing](#testing)
11. [Troubleshooting](#troubleshooting)

---

## 🌟 Overview

The AI Image Generation System is a comprehensive solution for creating professional images using artificial intelligence. It integrates with OpenAI's DALL-E and Stable Diffusion to generate:

- **Course Cover Images** - Professional covers for educational courses
- **Announcement Banners** - Eye-catching promotional materials
- **Certificate Designs** - Elegant certificate templates
- **General Images** - Custom images for various purposes

### Key Highlights

✨ **AI-Powered Generation** - Leverages DALL-E 3 and Stable Diffusion XL  
🎨 **Style Variations** - Realistic, Artistic, Cartoon, Abstract  
📐 **Multiple Sizes** - Square, Landscape, Portrait formats  
🔖 **Template Library** - 13 pre-configured templates  
💧 **Watermark Support** - Automatic watermark application  
📊 **Usage Tracking** - Detailed analytics and statistics  
🔍 **Smart Gallery** - Searchable, filterable image library

---

## 🚀 Features

### Core Features

#### 1. AI Image Generation
- **Multi-Provider Support**
  - DALL-E 3 (OpenAI) - High quality, creative
  - Stable Diffusion XL - Fast, customizable
  - Local Models - Privacy-focused option (planned)
  - Placeholder Mode - Demo/testing without API keys

- **Generation Options**
  - Prompt enhancement based on image type
  - Style modifiers (realistic, artistic, cartoon, abstract)
  - Size options (1024x1024, 1920x1080, 1080x1920)
  - Quality settings (standard/HD for DALL-E)

#### 2. Template System
- **Pre-configured Templates**
  - Course templates (5) - Programming, Design, Business, Marketing, English
  - Announcement templates (4) - General, Events, Sales, Registration
  - Certificate templates (4) - Achievement, Appreciation, Excellence, Participation
  
- **Template Features**
  - One-click prompt selection
  - Type-based filtering
  - Usage tracking
  - Custom template creation (admin)

#### 3. Image Management
- **Gallery Interface**
  - Grid view with hover previews
  - Type-based filtering (course/announcement/certificate/general)
  - Pagination support
  - Real-time statistics

- **Image Operations**
  - View full details
  - Download to device
  - Apply watermark
  - Delete image
  - Track usage

#### 4. Watermark System
- **Features**
  - Custom text watermarks
  - Position options (5 positions)
  - Semi-transparent rendering
  - Maintains image quality

#### 5. Statistics & Analytics
- **Dashboard Stats**
  - Total images generated
  - Images by type (course/announcement/certificate)
  - Generation time averages
  - Provider usage distribution

---

## 🏗️ Architecture

### System Components

```
┌─────────────────────────────────────────────────────┐
│                  Frontend (JS/HTML)                  │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────┐  │
│  │Generator │  │ Gallery  │  │ Stats Dashboard  │  │
│  └──────────┘  └──────────┘  └──────────────────┘  │
└───────────────────────┬─────────────────────────────┘
                        │
                        │ AJAX/Fetch API
                        │
┌───────────────────────▼─────────────────────────────┐
│           Backend API (PHP)                          │
│  ┌──────────────────────────────────────────────┐  │
│  │        ai_image_generator.php                │  │
│  │  ┌──────────┐  ┌──────────┐  ┌───────────┐  │  │
│  │  │Generate  │  │ Gallery  │  │ Watermark │  │  │
│  │  │  Image   │  │   API    │  │    API    │  │  │
│  │  └──────────┘  └──────────┘  └───────────┘  │  │
│  └──────────────────────────────────────────────┘  │
└───────────────────────┬─────────────────────────────┘
                        │
        ┌───────────────┼───────────────┐
        │               │               │
        ▼               ▼               ▼
┌──────────────┐ ┌──────────┐ ┌────────────────┐
│   DALL-E 3   │ │  Stable  │ │ Local Models   │
│   (OpenAI)   │ │Diffusion │ │  (Planned)     │
└──────────────┘ └──────────┘ └────────────────┘
        │               │               │
        └───────────────┼───────────────┘
                        │
                        ▼
            ┌───────────────────────┐
            │  MySQL Database       │
            │  ┌─────────────────┐  │
            │  │ ai_generated_   │  │
            │  │    images       │  │
            │  ├─────────────────┤  │
            │  │ ai_image_       │  │
            │  │   templates     │  │
            │  ├─────────────────┤  │
            │  │ ai_image_usage  │  │
            │  ├─────────────────┤  │
            │  │ ai_image_       │  │
            │  │  favorites      │  │
            │  └─────────────────┘  │
            └───────────────────────┘
```

### Data Flow

1. **Generation Request**
   ```
   User → Frontend → API → AI Provider → Save to DB → Return URL
   ```

2. **Gallery Loading**
   ```
   User → Frontend → API → Database Query → Return JSON → Render Grid
   ```

3. **Watermark Application**
   ```
   User → API → Load Image → Apply Text → Save New → Return URL
   ```

---

## 📦 Installation

### Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- GD Library (for image manipulation)
- cURL extension (for API calls)
- OpenAI API Key (optional, for DALL-E)
- Stability AI API Key (optional, for Stable Diffusion)

### Step 1: Database Setup

Execute the SQL schema:

```bash
mysql -u your_user -p your_database < database/ai_images_system.sql
```

Or via phpMyAdmin:
1. Open phpMyAdmin
2. Select your database
3. Click "Import"
4. Choose `database/ai_images_system.sql`
5. Click "Go"

### Step 2: Configure API Keys

Edit `Manager/api/ai_image_generator.php`:

```php
// Line 13-14
define('OPENAI_API_KEY', 'sk-your-openai-api-key-here');
define('STABILITY_API_KEY', 'sk-your-stability-api-key-here');
```

### Step 3: Set Permissions

```bash
# Create upload directory
mkdir -p uploads/ai_images
chmod 777 uploads/ai_images

# Or on Windows PowerShell:
New-Item -ItemType Directory -Path "uploads\ai_images" -Force
```

### Step 4: Test Demo Mode

Without API keys, the system runs in demo mode with placeholder images:

1. Login to manager dashboard
2. Navigate to "🎨 AI توليد الصور"
3. Try generating an image
4. System will create a gradient placeholder

---

## 📡 API Documentation

### Base URL
```
/Manager/api/ai_image_generator.php
```

### Authentication
All endpoints require active session with `manager` or `technical` role.

---

### 1. Generate Image

**Endpoint:** `?action=generate`  
**Method:** POST  
**Permission:** manager, technical

**Request Body:**
```json
{
  "prompt": "Professional programming course cover with laptop and code",
  "type": "course",
  "style": "realistic",
  "size": "1024x1024",
  "provider": "dalle"
}
```

**Parameters:**
- `prompt` (string, required) - Description of desired image
- `type` (enum, optional) - `course`, `announcement`, `certificate`, `general`
- `style` (enum, optional) - `realistic`, `artistic`, `cartoon`, `abstract`
- `size` (string, optional) - `1024x1024`, `1920x1080`, `1080x1920`
- `provider` (enum, optional) - `dalle`, `stable-diffusion`, `local`

**Response:**
```json
{
  "success": true,
  "message": "تم إنشاء الصورة بنجاح",
  "image_id": 123,
  "filename": "dalle_1704067200_1234.png",
  "url": "uploads/ai_images/dalle_1704067200_1234.png",
  "provider": "dalle"
}
```

**Example cURL:**
```bash
curl -X POST "http://localhost/Manager/api/ai_image_generator.php?action=generate" \
  -H "Content-Type: application/json" \
  -d '{
    "prompt": "Modern digital marketing course cover",
    "type": "course",
    "style": "realistic",
    "provider": "dalle"
  }'
```

---

### 2. List Images

**Endpoint:** `?action=list`  
**Method:** GET  
**Permission:** All authenticated users

**Query Parameters:**
- `page` (int, optional) - Page number (default: 1)
- `limit` (int, optional) - Items per page (default: 20, max: 50)
- `type` (enum, optional) - Filter by type

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 123,
      "user_id": 1,
      "image_type": "course",
      "prompt": "Programming course cover",
      "filename": "dalle_1704067200_1234.png",
      "file_path": "uploads/ai_images/dalle_1704067200_1234.png",
      "provider": "dalle",
      "metadata": {
        "enhanced_prompt": "...",
        "style": "realistic",
        "size": "1024x1024",
        "generation_time": 3.45
      },
      "usage_count": 5,
      "created_at": "2025-01-01 12:00:00",
      "creator_name": "Admin User"
    }
  ],
  "pagination": {
    "page": 1,
    "limit": 20,
    "total": 45,
    "total_pages": 3
  }
}
```

---

### 3. Get Single Image

**Endpoint:** `?action=get&id={id}`  
**Method:** GET  
**Permission:** All authenticated users

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 123,
    "user_id": 1,
    "image_type": "course",
    "prompt": "Programming course cover",
    "enhanced_prompt": "Professional programming course...",
    "filename": "dalle_1704067200_1234.png",
    "file_path": "uploads/ai_images/dalle_1704067200_1234.png",
    "dimensions": "1024x1024",
    "provider": "dalle",
    "generation_time": 3.45,
    "metadata": {...},
    "usage_count": 5,
    "created_at": "2025-01-01 12:00:00",
    "creator_name": "Admin User"
  }
}
```

---

### 4. Delete Image

**Endpoint:** `?action=delete&id={id}`  
**Method:** GET  
**Permission:** manager, technical

**Response:**
```json
{
  "success": true,
  "message": "تم حذف الصورة بنجاح"
}
```

---

### 5. Apply Watermark

**Endpoint:** `?action=apply_watermark`  
**Method:** POST  
**Permission:** manager, technical

**Request Body:**
```json
{
  "image_id": 123,
  "watermark_text": "منصة إبداع - تعز",
  "position": "bottom-right"
}
```

**Parameters:**
- `image_id` (int, required) - ID of image to watermark
- `watermark_text` (string, required) - Text to display
- `position` (enum, optional) - `top-left`, `top-right`, `bottom-left`, `bottom-right`, `center`

**Response:**
```json
{
  "success": true,
  "message": "تم إضافة العلامة المائية بنجاح",
  "filename": "watermarked_1704067200_dalle_1704067200_1234.png",
  "url": "uploads/ai_images/watermarked_1704067200_dalle_1704067200_1234.png"
}
```

---

### 6. Get Templates

**Endpoint:** `?action=get_templates`  
**Method:** GET  
**Permission:** All authenticated users

**Response:**
```json
{
  "success": true,
  "templates": {
    "course": [
      {
        "name": "دورة برمجة",
        "prompt": "A professional programming course cover image with laptop and code"
      },
      {
        "name": "دورة تصميم",
        "prompt": "Creative design course cover with colorful graphics and tools"
      }
    ],
    "announcement": [...],
    "certificate": [...]
  }
}
```

---

### 7. Generate Batch (Planned)

**Endpoint:** `?action=generate_batch`  
**Method:** POST  
**Permission:** manager, technical

**Status:** Under development

---

### 8. Enhance Image (Planned)

**Endpoint:** `?action=enhance`  
**Method:** POST  
**Permission:** manager, technical

**Status:** Under development

---

## 🎨 Frontend Components

### Main Interface

Located in `Manager/dashboard.php` - `renderAIImages()` function

#### Statistics Cards

```javascript
// Four stat cards showing:
- Total images generated
- Course images count
- Announcement images count
- Certificate images count
```

#### Generation Form

Fields:
- **Image Type** - Dropdown (course/announcement/certificate/general)
- **Template** - Dropdown (dynamically loaded based on type)
- **Prompt** - Textarea (description of desired image)
- **Style** - Dropdown (realistic/artistic/cartoon/abstract)
- **Size** - Dropdown (square/landscape/portrait)
- **Provider** - Radio buttons (DALL-E/Stable Diffusion)

#### Preview Panel

Shows:
- Generated image
- Download button
- Watermark button

#### Gallery Grid

Features:
- Responsive grid (2/3/4 columns)
- Hover effects with image details
- Filter by type dropdown
- Pagination controls
- View/Download/Delete actions

### JavaScript Functions

```javascript
// Core functions
renderAIImages()              // Main renderer
loadAIImagesData()           // Load stats and data
loadGallery(page, type)      // Load gallery images
generateAIImage()            // Generate new image
viewAIImage(id)              // View image details modal
downloadAIImage(url)         // Download image
deleteAIImage(id)            // Delete image with confirmation
applyWatermark(id)           // Apply watermark modal
```

---

## 🗄️ Database Schema

### Tables

#### 1. `ai_generated_images`

Primary table for storing generated images.

**Columns:**
- `id` INT PRIMARY KEY AUTO_INCREMENT
- `user_id` INT NOT NULL
- `image_type` ENUM('course', 'announcement', 'certificate', 'general')
- `prompt` TEXT NOT NULL
- `enhanced_prompt` TEXT
- `filename` VARCHAR(255) NOT NULL
- `file_path` VARCHAR(500) NOT NULL
- `file_size` INT
- `dimensions` VARCHAR(20)
- `provider` ENUM('dalle', 'stable-diffusion', 'local', 'placeholder')
- `generation_time` DECIMAL(10,2)
- `metadata` JSON
- `tags` VARCHAR(500)
- `usage_count` INT DEFAULT 0
- `is_public` BOOLEAN DEFAULT FALSE
- `created_at` TIMESTAMP
- `updated_at` TIMESTAMP

**Indexes:**
- `idx_user` ON (user_id)
- `idx_type` ON (image_type)
- `idx_created` ON (created_at)
- FULLTEXT `idx_prompt` ON (prompt, tags)

---

#### 2. `ai_image_templates`

Pre-configured prompt templates.

**Columns:**
- `id` INT PRIMARY KEY
- `template_name` VARCHAR(100)
- `template_type` ENUM('course', 'announcement', 'certificate', 'general')
- `prompt_template` TEXT
- `description` TEXT
- `style` VARCHAR(50)
- `recommended_size` VARCHAR(20)
- `preview_image` VARCHAR(255)
- `usage_count` INT DEFAULT 0
- `is_active` BOOLEAN DEFAULT TRUE
- `created_by` INT
- `created_at` TIMESTAMP
- `updated_at` TIMESTAMP

**Default Templates:** 13 inserted (5 course + 4 announcement + 4 certificate)

---

#### 3. `ai_image_usage`

Tracks where images are used.

**Columns:**
- `id` INT PRIMARY KEY
- `image_id` INT NOT NULL
- `used_in_type` ENUM('course', 'announcement', 'certificate', 'email', 'social_media', 'other')
- `used_in_id` INT
- `used_at` TIMESTAMP

**Trigger:** Increments `usage_count` in `ai_generated_images` on INSERT

---

#### 4. `ai_image_favorites`

User favorites for quick access.

**Columns:**
- `id` INT PRIMARY KEY
- `user_id` INT NOT NULL
- `image_id` INT NOT NULL
- `created_at` TIMESTAMP

**Unique:** (user_id, image_id)

---

### Views

#### 1. `ai_generation_stats`

30-day statistics by date, type, and provider.

```sql
SELECT 
    DATE(created_at) as generation_date,
    image_type,
    provider,
    COUNT(*) as total_generated,
    AVG(generation_time) as avg_generation_time,
    SUM(file_size) as total_size,
    SUM(usage_count) as total_usage
FROM ai_generated_images
WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
GROUP BY DATE(created_at), image_type, provider;
```

#### 2. `popular_ai_templates`

Most used templates with creator info.

---

### Stored Procedures

#### `clean_unused_images(days_old INT)`

Deletes unused images older than specified days.

```sql
CALL clean_unused_images(90);
-- Deletes images with usage_count = 0 and older than 90 days
```

---

### Functions

#### `get_image_url(image_id INT)`

Returns file path for an image ID.

```sql
SELECT get_image_url(123);
-- Returns: uploads/ai_images/dalle_1704067200_1234.png
```

---

## 💡 Usage Examples

### Example 1: Generate Course Cover

```javascript
const response = await fetch('api/ai_image_generator.php?action=generate', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    prompt: 'Professional Python programming course cover with code and laptop',
    type: 'course',
    style: 'realistic',
    size: '1024x1024',
    provider: 'dalle'
  })
});

const result = await response.json();
console.log(result.url); // uploads/ai_images/dalle_...png
```

### Example 2: Load Gallery with Filter

```javascript
const response = await fetch('api/ai_image_generator.php?action=list&type=course&page=1&limit=12');
const result = await response.json();

result.data.forEach(image => {
  console.log(`${image.prompt} - ${image.file_path}`);
});
```

### Example 3: Apply Watermark

```javascript
const response = await fetch('api/ai_image_generator.php?action=apply_watermark', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({
    image_id: 123,
    watermark_text: 'منصة إبداع - تعز',
    position: 'bottom-right'
  })
});

const result = await response.json();
console.log(result.url); // New watermarked image URL
```

---

## ⚙️ Configuration

### API Keys

**OpenAI DALL-E:**
1. Sign up at https://platform.openai.com/
2. Generate API key
3. Update `OPENAI_API_KEY` in `ai_image_generator.php`

**Stability AI:**
1. Sign up at https://platform.stability.ai/
2. Generate API key
3. Update `STABILITY_API_KEY` in `ai_image_generator.php`

### Upload Directory

Default: `uploads/ai_images/`

Change in `ai_image_generator.php`:
```php
define('UPLOAD_DIR', __DIR__ . '/../../uploads/custom_path/');
```

### Max Image Size

Default: 5MB

```php
define('MAX_IMAGE_SIZE', 5242880); // 5MB in bytes
```

---

## 🧪 Testing

### Test 1: Demo Mode Generation

**Without API keys:**
1. Navigate to AI Images page
2. Fill prompt: "Test image"
3. Click "توليد الصورة"
4. Verify placeholder image is created
5. Check image shows in gallery

**Expected:** Gradient placeholder with prompt text

---

### Test 2: DALL-E Generation

**With valid OPENAI_API_KEY:**
1. Select provider: DALL-E
2. Prompt: "Professional course cover"
3. Generate
4. Verify high-quality AI image
5. Check generation time logged

---

### Test 3: Watermark Application

1. Generate or select an image
2. Click "إضافة علامة مائية"
3. Enter text: "Test Watermark"
4. Verify new image created
5. Check watermark visible

---

### Test 4: Gallery Filtering

1. Generate images of different types
2. Use type filter dropdown
3. Verify only matching images show
4. Test pagination

---

### Test 5: Delete Image

1. Click delete on any image
2. Confirm deletion
3. Verify image removed from gallery
4. Check file deleted from `uploads/ai_images/`

---

## 🐛 Troubleshooting

### Issue: "فشل طلب DALL-E"

**Causes:**
- Invalid API key
- Insufficient API credits
- Network timeout

**Solutions:**
1. Verify `OPENAI_API_KEY` is correct
2. Check OpenAI account credits: https://platform.openai.com/usage
3. Test API key with cURL:
   ```bash
   curl https://api.openai.com/v1/models \
     -H "Authorization: Bearer YOUR_API_KEY"
   ```

---

### Issue: "ملف الصورة غير موجود"

**Causes:**
- Upload directory doesn't exist
- Incorrect permissions
- File path mismatch

**Solutions:**
1. Create directory:
   ```bash
   mkdir -p uploads/ai_images
   chmod 777 uploads/ai_images
   ```
2. Check `UPLOAD_DIR` constant matches actual path
3. Verify web server has write permissions

---

### Issue: "خطأ في إنشاء الصورة"

**Causes:**
- API timeout
- Invalid prompt
- Provider error

**Solutions:**
1. Check PHP error log: `tail -f /var/log/php_errors.log`
2. Verify provider API status
3. Test with simpler prompt
4. Switch to demo mode for testing

---

### Issue: Watermark not visible

**Causes:**
- Image format not supported
- GD library missing
- Color contrast too low

**Solutions:**
1. Verify GD installed: `php -m | grep gd`
2. Convert image to PNG before watermarking
3. Adjust watermark color in `applyWatermark()` function

---

### Issue: Gallery not loading

**Causes:**
- Database connection error
- Session expired
- JavaScript error

**Solutions:**
1. Check browser console for errors
2. Verify database connection in `db.php`
3. Clear browser cache and reload
4. Check session is active: `var_dump($_SESSION);`

---

## 📊 Performance Optimization

### Recommendations

1. **Image Caching**
   - Store generated images in CDN
   - Use browser caching headers

2. **Database Optimization**
   - Add indexes on frequently queried columns
   - Archive old unused images

3. **API Rate Limiting**
   - Implement rate limiting for generation endpoint
   - Queue batch generations

4. **Thumbnail Generation**
   - Generate thumbnails for gallery
   - Lazy load full-size images

---

## 🔐 Security Considerations

1. **API Key Protection**
   - Never expose keys in frontend
   - Use environment variables in production

2. **File Upload Validation**
   - Validate file types
   - Check file sizes
   - Sanitize filenames

3. **Access Control**
   - Verify user permissions on all endpoints
   - Limit generation to authenticated users

4. **SQL Injection Prevention**
   - Use prepared statements (already implemented)
   - Validate all input parameters

---

## 🚀 Future Enhancements

### Planned Features

1. **Batch Generation** - Generate multiple images at once
2. **Image Enhancement** - Upscale, denoise, color correction
3. **Advanced Watermarks** - Logo watermarks, custom positioning
4. **Social Media Optimization** - Auto-resize for platforms
5. **Template Editor** - Visual template creator
6. **Image History** - Version control for generated images
7. **AI Model Training** - Fine-tune models on custom data
8. **Integration with Courses** - Auto-generate covers on course creation

---

## 📞 Support

For issues or questions:
- Check troubleshooting section above
- Review API documentation
- Contact development team

---

## 📄 License

Copyright © 2025 Ibdaa Platform. All rights reserved.

---

**End of Documentation**
