# 🚀 AI SYSTEMS - QUICK START GUIDE
# دليل البدء السريع لأنظمة الذكاء الاصطناعي

**Last Updated:** 2024  
**Estimated Setup Time:** 30-60 minutes

---

## 📋 TABLE OF CONTENTS

1. [Prerequisites](#prerequisites)
2. [Installation Steps](#installation)
3. [Configuration](#configuration)
4. [Testing](#testing)
5. [Usage Examples](#usage)
6. [Troubleshooting](#troubleshooting)

---

## ✅ PREREQUISITES

### Required Software

| Software | Minimum Version | Purpose | Status |
|----------|----------------|---------|--------|
| **PHP** | 7.4+ | Backend processing | ⬜ |
| **MySQL** | 5.7+ | Database | ⬜ |
| **Composer** | 2.0+ | PHP dependencies | ⬜ |
| **Apache/Nginx** | Latest | Web server | ⬜ |
| **Tesseract OCR** | 4.0+ | Image text extraction | ⬜ (Optional) |
| **Redis** | 6.0+ | Caching | ⬜ (Optional) |

### Check Current Installation

```powershell
# Check PHP version
php -v

# Check Composer
composer --version

# Check MySQL
mysql --version

# Check Tesseract (optional)
tesseract --version

# Check Redis (optional)
redis-cli ping
```

---

## 📦 INSTALLATION

### Step 1: Install PHP Dependencies

```powershell
# Navigate to project root
cd c:\xampp\htdocs\Ibdaa-Taiz

# Install Composer dependencies
composer require php-ai/php-ml:^0.10
composer require phpoffice/phpspreadsheet:^1.29
composer require smalot/pdfparser:^2.5
composer require predis/predis:^2.2
```

**Expected Output:**
```
✅ php-ai/php-ml installed
✅ phpoffice/phpspreadsheet installed
✅ smalot/pdfparser installed
✅ predis/predis installed
```

---

### Step 2: Install Tesseract OCR (Optional but Recommended)

#### Windows:

```powershell
# Download installer
# Visit: https://github.com/UB-Mannheim/tesseract/wiki

# Install to default location:
# C:\Program Files\Tesseract-OCR

# Add to PATH
setx PATH "%PATH%;C:\Program Files\Tesseract-OCR"

# Verify installation
tesseract --version

# Install Arabic language data
# Download ara.traineddata from:
# https://github.com/tesseract-ocr/tessdata/raw/main/ara.traineddata
# Place in: C:\Program Files\Tesseract-OCR\tessdata\
```

#### Linux:

```bash
sudo apt-get update
sudo apt-get install tesseract-ocr
sudo apt-get install tesseract-ocr-ara
tesseract --version
```

#### macOS:

```bash
brew install tesseract
brew install tesseract-lang
tesseract --version
```

---

### Step 3: Install Redis (Optional - for Performance)

#### Windows:

```powershell
# Download Redis for Windows
# Visit: https://github.com/microsoftarchive/redis/releases

# Extract and run redis-server.exe
cd C:\Redis
.\redis-server.exe

# Test connection
redis-cli ping
# Expected: PONG
```

#### Linux:

```bash
sudo apt-get install redis-server
sudo systemctl start redis
sudo systemctl enable redis
redis-cli ping
```

#### macOS:

```bash
brew install redis
brew services start redis
redis-cli ping
```

---

### Step 4: Create Required Directories

```powershell
# Create model directories
New-Item -ItemType Directory -Force -Path "Manager\models\notification-priority"
New-Item -ItemType Directory -Force -Path "Manager\models\data-validation"

# Create upload directory (if not exists)
New-Item -ItemType Directory -Force -Path "uploads"

# Set permissions (Linux/macOS)
# chmod -R 755 Manager/JS/
# chmod -R 755 Manager/api/
# chmod -R 777 uploads/
```

---

### Step 5: Database Setup

```sql
-- No additional tables needed
-- AI features use existing notifications table
-- Import system uses existing student/trainer/course tables

-- Verify notifications table exists
SHOW TABLES LIKE 'notifications';

-- Add index for performance (optional)
ALTER TABLE notifications ADD INDEX idx_user_created (user_id, created_at);
ALTER TABLE notifications ADD INDEX idx_is_read (is_read);
```

---

### Step 6: Configure Settings

#### Edit: `Manager/includes/ai-libraries.php`

```javascript
// Update WebSocket URL (if using WebSocket server)
window.AIConfig = {
    websocket: {
        url: 'ws://your-server.com:8080/notifications',  // Update this
        reconnectInterval: 5000,
        maxReconnectAttempts: 10
    },
    
    // Enable debug mode for testing
    debug: true,  // Set to false in production
    
    // Other settings are auto-configured
};
```

#### Edit: `Manager/api/ai_notifications.php`

```php
// Configure Redis (if installed)
private function initRedis() {
    try {
        if (class_exists('Redis')) {
            $this->redis = new Redis();
            $this->redis->connect('127.0.0.1', 6379);  // Update host/port if needed
            $this->redis->setOption(Redis::OPT_SERIALIZER, Redis::SERIALIZER_JSON);
        }
    } catch (Exception $e) {
        error_log('Redis connection failed: ' . $e->getMessage());
        // System will work without Redis
    }
}
```

---

### Step 7: Include AI Libraries in Your Pages

#### Update: `Manager/notifications_panel.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title>الإشعارات - مدعومة بالذكاء الاصطناعي</title>
    
    <!-- Include AI Libraries -->
    <?php include 'includes/ai-libraries.php'; ?>
    
    <!-- Your existing CSS -->
    <link rel="stylesheet" href="css/notifications.css">
</head>
<body>
    <!-- Your existing HTML -->
    
    <!-- Replace old notifications.js with AI version -->
    <script src="JS/ai_notifications.js"></script>
    
    <script>
        // Initialize when AI libraries are ready
        window.addEventListener('ai-libraries-ready', async () => {
            const notificationSystem = new AdvancedAINotificationsSystem();
            await notificationSystem.init();
        });
    </script>
</body>
</html>
```

#### Update: `Manager/import_panel.php`

```php
<!DOCTYPE html>
<html>
<head>
    <title>الاستيراد - مدعوم بالذكاء الاصطناعي</title>
    
    <!-- Include AI Libraries -->
    <?php include 'includes/ai-libraries.php'; ?>
    
    <!-- Your existing CSS -->
    <link rel="stylesheet" href="css/import.css">
</head>
<body>
    <!-- Your existing HTML -->
    
    <!-- Replace old import.js with AI version -->
    <script src="JS/ai_import.js"></script>
    
    <script>
        // Initialize when AI libraries are ready
        window.addEventListener('ai-libraries-ready', async () => {
            const importSystem = new AdvancedAIImportSystem();
            await importSystem.init();
        });
    </script>
</body>
</html>
```

---

## 🧪 TESTING

### Test 1: Verify AI Libraries Loading

```javascript
// Open browser console (F12)
// Navigate to notifications page

// Check library status
console.log(window.AIConfig.getStatus());

// Expected output:
{
    tensorflow: true,
    compromise: true,
    sentiment: true,
    tesseract: true,
    papaParse: true,
    brainjs: true,
    ml5: true,
    cocoSsd: true,
    pdfjs: true,
    xlsx: true,
    fuse: true,
    chart: true
}
```

### Test 2: Test AI Notifications

```javascript
// In browser console on notifications page

// Check if system initialized
console.log(notificationSystem);

// Load notifications
await notificationSystem.loadNotifications();

// Check AI processing
console.log(notificationSystem.notifications[0]);

// Expected to see:
{
    notification_id: 1,
    title: "...",
    message: "...",
    sentiment: { score: 0.5, type: "positive", confidence: 0.85 },
    ai_priority: { score: 0.75, level: "high", confidence: 0.88 },
    ai_category: "payment",
    time_sensitive: true,
    suggested_actions: [...],
    related_ids: [2, 5, 7]
}
```

### Test 3: Test AI Import

```javascript
// Upload a test Excel/CSV file
// Check browser console

// You should see:
[Tesseract] Initializing worker...
[AI Import] File analysis complete
[AI Import] Detected: 5 columns, 100 rows
[AI Import] Quality score: 95.5%
[AI Import] Suggested type: students
[AI Import] Column matching confidence: 88%
```

### Test 4: Test OCR (if Tesseract installed)

```javascript
// Upload an image with text
// Check console

// Expected:
[Tesseract] Loading language data...
[Tesseract] Initializing API...
[Tesseract] Processing image...
[Tesseract] Recognized text: "..."
[AI Import] Extracted 10 rows from image
```

### Test 5: Test Backend APIs

```powershell
# Test AI Notifications API
curl http://localhost/Ibdaa-Taiz/Manager/api/ai_notifications.php?action=all

# Expected JSON response with AI fields

# Test AI Import Analysis
curl -X POST http://localhost/Ibdaa-Taiz/Manager/api/ai_import_stream.php?action=analyze `
    -d "file_path=/path/to/test.xlsx&file_type=xlsx"

# Expected analysis response
```

---

## 📖 USAGE EXAMPLES

### Example 1: Get AI-Enhanced Notifications

```javascript
// Initialize system
const notificationSystem = new AdvancedAINotificationsSystem();
await notificationSystem.init();

// Load all notifications
await notificationSystem.loadNotifications();

// Get high-priority notifications
const highPriority = notificationSystem.notifications.filter(
    n => n.ai_priority.level === 'high'
);

console.log(`Found ${highPriority.length} high-priority notifications`);

// Group similar notifications
notificationSystem.createSmartGroups();

console.log(`Created ${notificationSystem.groups.length} smart groups`);
```

### Example 2: Voice Notifications

```javascript
// Enable voice synthesis
notificationSystem.voiceEnabled = true;

// Speak a notification
notificationSystem.speakNotification(notificationSystem.notifications[0]);

// Voice will read notification in Arabic
```

### Example 3: Import with AI

```javascript
// Initialize import system
const importSystem = new AdvancedAIImportSystem();
await importSystem.init();

// Handle file selection
document.getElementById('file-input').addEventListener('change', async (e) => {
    const file = e.target.files[0];
    
    // AI will automatically:
    // 1. Detect file type
    // 2. Extract data (even from images/PDFs)
    // 3. Analyze quality
    // 4. Suggest import type
    // 5. Match columns with fuzzy matching
    
    await importSystem.handleFileSelect(file);
    
    console.log('Analysis:', importSystem.fileAnalysis);
    console.log('Suggested mapping:', importSystem.columnMapping);
});
```

### Example 4: Custom ML Model

```javascript
// Train a custom priority prediction model
const trainingData = [];

// Collect data from user interactions
notificationSystem.notifications.forEach(n => {
    trainingData.push({
        features: notificationSystem.extractFeatures(n),
        priority: n.user_marked_priority // User feedback
    });
});

// Train model
await notificationSystem.aiEngine.train(trainingData);

// Save model
await notificationSystem.aiEngine.save('models/custom-priority');
```

---

## 🔧 CONFIGURATION OPTIONS

### Performance Tuning

```javascript
// Adjust polling interval
window.AIConfig.performance.pollingInterval = 10000; // 10 seconds

// Adjust batch size
window.AIConfig.performance.batchSize = 50;

// Adjust chunk size for import
window.AIConfig.performance.chunkSize = 500;

// Number of parallel workers
window.AIConfig.workers.poolSize = 8; // Use more CPUs
```

### Enable/Disable Features

```javascript
// Disable ML prediction (use rule-based only)
window.AIConfig.features.mlPrediction = false;

// Disable voice synthesis
window.AIConfig.features.voiceSynthesis = false;

// Disable OCR (if Tesseract not available)
window.AIConfig.features.ocrSupport = false;

// Disable WebSocket (use polling only)
window.AIConfig.features.realtimeUpdates = false;
```

### Backend Configuration

```php
// In ai_notifications.php

// Adjust chunk size
private $chunk_size = 100; // Notifications per request

// Cache duration (seconds)
$this->redis->setex($cache_key, 120, $response); // 2 minutes

// In ai_import_stream.php

// Adjust processing chunk size
private $chunk_size = 200; // Rows per chunk

// Timeout for large imports
set_time_limit(600); // 10 minutes
```

---

## 🐛 TROUBLESHOOTING

### Issue 1: Libraries Not Loading

**Symptoms:**
```
Error: tf is not defined
Error: nlp is not defined
```

**Solution:**
```javascript
// Check if libraries are loaded
console.log('TensorFlow:', typeof tf !== 'undefined');
console.log('Compromise:', typeof nlp !== 'undefined');

// If false, check network tab in DevTools
// Ensure CDN URLs are accessible
// Try using local copies if CDN is blocked
```

### Issue 2: OCR Not Working

**Symptoms:**
```
Error: Tesseract OCR not installed
```

**Solution:**
```powershell
# Install Tesseract (see Step 2)
# Verify installation
tesseract --version

# Check language data
tesseract --list-langs
# Should include: ara, eng

# Update path in PHP if needed
$tesseract_path = 'C:\Program Files\Tesseract-OCR\tesseract.exe';
```

### Issue 3: Redis Connection Failed

**Symptoms:**
```
Warning: Redis connection refused
```

**Solution:**
```powershell
# Start Redis server
redis-server

# Or disable Redis in code
# System will work without Redis (slower, no caching)
$this->redis = null;
```

### Issue 4: Import Timeout

**Symptoms:**
```
Error: Maximum execution time exceeded
```

**Solution:**
```php
// In ai_import_stream.php
set_time_limit(0); // No timeout (use carefully)

// Or increase PHP timeout
// Edit php.ini:
max_execution_time = 600
```

### Issue 5: WebSocket Not Connecting

**Symptoms:**
```
Error: WebSocket connection closed
```

**Solution:**
```javascript
// System automatically falls back to polling
// To disable WebSocket entirely:
window.AIConfig.features.realtimeUpdates = false;

// Or set up WebSocket server (advanced)
// See WebSocket server setup guide
```

---

## 📊 VERIFICATION CHECKLIST

After installation, verify:

- [ ] ✅ All PHP dependencies installed (`composer.json`)
- [ ] ✅ Tesseract OCR installed (optional)
- [ ] ✅ Redis running (optional)
- [ ] ✅ Model directories created
- [ ] ✅ AI libraries loading in browser
- [ ] ✅ Notifications API responding
- [ ] ✅ Import API responding
- [ ] ✅ TensorFlow.js backend ready
- [ ] ✅ OCR processing working (if installed)
- [ ] ✅ No console errors in browser
- [ ] ✅ File upload working
- [ ] ✅ Database connections working

---

## 🚀 NEXT STEPS

After successful setup:

1. **Test with Real Data**
   - Upload actual Excel/CSV files
   - Create test notifications
   - Verify AI predictions

2. **Train Custom Models**
   - Collect user interaction data
   - Train priority prediction model
   - Improve accuracy over time

3. **Optimize Performance**
   - Monitor Redis hit rate
   - Adjust polling intervals
   - Tune worker pool size

4. **Enable Production Mode**
   ```javascript
   window.AIConfig.debug = false;
   ```

5. **Monitor & Maintain**
   - Check error logs
   - Monitor API response times
   - Update libraries regularly

---

## 📞 SUPPORT

If you encounter issues:

1. **Check Console Errors**
   ```javascript
   // Enable debug mode
   window.AIConfig.debug = true;
   
   // Check status
   console.log(window.AIConfig.getStatus());
   ```

2. **Check PHP Errors**
   ```php
   // In php.ini
   display_errors = On
   error_reporting = E_ALL
   
   // Check error log
   tail -f /var/log/apache2/error.log  // Linux
   ```

3. **Verify Dependencies**
   ```powershell
   composer show
   composer diagnose
   ```

4. **Test APIs Directly**
   ```powershell
   curl http://localhost/Ibdaa-Taiz/Manager/api/ai_notifications.php?action=all
   ```

---

## ✅ QUICK START SUMMARY

```bash
# 1. Install PHP dependencies
composer install

# 2. Install Tesseract (optional)
# Download from: https://github.com/UB-Mannheim/tesseract/wiki

# 3. Install Redis (optional)
# Download from: https://github.com/microsoftarchive/redis/releases

# 4. Create directories
mkdir -p Manager/models/{notification-priority,data-validation}

# 5. Include AI libraries in your HTML
<?php include 'includes/ai-libraries.php'; ?>

# 6. Use AI systems
<script src="JS/ai_notifications.js"></script>
<script src="JS/ai_import.js"></script>

# 7. Test in browser
# Open notifications page
# Check console: window.AIConfig.getStatus()

# 8. Done! 🎉
```

---

**Setup Time:** ~30-60 minutes  
**Difficulty:** Intermediate  
**Support:** Check AI_SYSTEMS_COMPLETION_REPORT.md for full documentation

---

**Happy AI Coding! 🤖✨**
