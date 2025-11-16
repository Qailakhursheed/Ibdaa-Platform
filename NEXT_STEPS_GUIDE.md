# 🚀 دليل الخطوات السريعة لإكمال الأنظمة المتبقية

## 📋 جدول المحتويات
1. [إضافة Chart.js](#1-إضافة-chartjs)
2. [إكمال نظام المدربين](#2-نظام-المدربين)
3. [نظام الإعلانات الذكي](#3-نظام-الإعلانات-الذكي)
4. [نظام الإشعارات](#4-نظام-الإشعارات)
5. [التحقق من Sidebar](#5-التحقق-من-sidebar)

---

## 1. إضافة Chart.js

### الخطوة 1: إضافة CDN
افتح ملف `Manager/dashboard.php` وابحث عن `</head>` ثم أضف قبلها مباشرة:

```html
<!-- Chart.js for Financial Charts -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
```

### الخطوة 2: اختبار الرسوم البيانية
```bash
1. افتح لوحة التحكم
2. اذهب إلى "الشؤون المالية"
3. تحقق من ظهور الرسم البياني لاتجاه الإيرادات
4. تحقق من الرسم الدائري لطرق الدفع
```

---

## 2. نظام المدربين

### الخطوات المطلوبة:

#### أ) إضافة AI Performance Score

```javascript
// في dashboard.php، ابحث عن renderTrainers() وأضف:

function calculateAIPerformanceScore(trainer) {
    // Metrics
    const attendanceRate = trainer.attendance_rate || 0; // 0-100
    const studentRating = trainer.avg_student_rating || 0; // 0-5
    const completionRate = trainer.course_completion_rate || 0; // 0-100
    const contentQuality = trainer.content_quality_score || 0; // 0-100
    
    // Weighted calculation
    const score = (
        (attendanceRate * 0.25) +
        (studentRating * 20 * 0.30) + // Convert 0-5 to 0-100
        (completionRate * 0.25) +
        (contentQuality * 0.20)
    );
    
    return Math.round(score);
}

function getAIRecommendations(score) {
    if (score >= 90) return '🌟 أداء ممتاز! استمر في التميز';
    if (score >= 75) return '💪 أداء جيد جداً! حاول رفع معدل الحضور';
    if (score >= 60) return '📚 أداء مقبول، ننصح بحضور دورات تطوير المدربين';
    return '⚠️ يحتاج تحسين فوري، نوصي بمراجعة المشرف الفني';
}
```

#### ب) إضافة نظام المكافآت

```javascript
function getBadges(trainer) {
    const badges = [];
    
    if (trainer.courses_count >= 10) badges.push('🏆 مدرب محترف');
    if (trainer.avg_student_rating >= 4.5) badges.push('⭐ الأعلى تقييماً');
    if (trainer.attendance_rate >= 95) badges.push('💯 الحضور المثالي');
    if (trainer.years_experience >= 5) badges.push('🎓 خبير متمرس');
    
    return badges;
}

function getRewardPoints(score) {
    if (score >= 90) return 100;
    if (score >= 75) return 75;
    if (score >= 60) return 50;
    return 25;
}
```

#### ج) إضافة Leaderboard

```javascript
function renderTrainersLeaderboard(trainers) {
    const sorted = trainers
        .map(t => ({ ...t, aiScore: calculateAIPerformanceScore(t) }))
        .sort((a, b) => b.aiScore - a.aiScore)
        .slice(0, 10);
    
    return `
        <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6">
            <h3 class="font-bold text-xl mb-4">🏆 قائمة الشرف - أفضل المدربين</h3>
            <div class="space-y-3">
                ${sorted.map((trainer, index) => `
                    <div class="bg-white rounded-xl p-4 flex items-center gap-4">
                        <div class="w-12 h-12 rounded-full bg-gradient-to-br from-purple-500 to-pink-600 flex items-center justify-center text-white font-bold text-xl">
                            ${index + 1}
                        </div>
                        <div class="flex-1">
                            <p class="font-bold">${trainer.full_name}</p>
                            <p class="text-sm text-gray-600">${trainer.aiScore} نقطة</p>
                        </div>
                        <div class="flex gap-1">
                            ${getBadges(trainer).map(b => `<span class="text-2xl">${b}</span>`).join('')}
                        </div>
                    </div>
                `).join('')}
            </div>
        </div>
    `;
}
```

---

## 3. نظام الإعلانات الذكي

### الملفات المطلوبة:

#### أ) Backend API
أنشئ ملف `Manager/api/manage_announcements_ai.php`:

```php
<?php
require_once '../../database/db.php';
header('Content-Type: application/json');
session_start();

// Check permissions
if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['manager', 'technical'])) {
    echo json_encode(['success' => false, 'message' => 'غير مصرح']);
    exit;
}

$action = $_POST['action'] ?? $_GET['action'] ?? 'list';

try {
    switch ($action) {
        case 'list':
            $stmt = $conn->query("
                SELECT a.*, 
                    COUNT(DISTINCT ar.user_id) as views_count,
                    COUNT(DISTINCT e.user_id) as enrollments_count
                FROM announcements a
                LEFT JOIN announcement_reads ar ON a.id = ar.announcement_id
                LEFT JOIN enrollments e ON a.course_id = e.course_id 
                    AND e.created_at >= a.created_at
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;
            
        case 'create':
            $data = json_decode(file_get_contents('php://input'), true);
            
            $stmt = $conn->prepare("
                INSERT INTO announcements 
                (title, description, course_id, target_audience, scheduled_at, status, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['title'],
                $data['description'],
                $data['course_id'],
                $data['target_audience'] ?? 'all',
                $data['scheduled_at'] ?? date('Y-m-d H:i:s'),
                $data['status'] ?? 'published',
                $_SESSION['user_id']
            ]);
            
            $announcementId = $conn->lastInsertId();
            
            // Send notifications to students
            if ($data['send_notifications']) {
                sendAnnouncementNotifications($conn, $announcementId, $data);
            }
            
            // Publish to external website
            if ($data['publish_to_website']) {
                publishToExternalWebsite($data);
            }
            
            echo json_encode(['success' => true, 'message' => 'تم نشر الإعلان بنجاح']);
            break;
            
        case 'ai_suggest_audience':
            // AI-powered audience targeting
            $courseId = $_GET['course_id'];
            
            // Get students with similar interests/past enrollments
            $stmt = $conn->prepare("
                SELECT DISTINCT u.id, u.full_name, u.email
                FROM users u
                INNER JOIN enrollments e ON u.id = e.user_id
                WHERE e.course_id IN (
                    SELECT DISTINCT course_id FROM enrollments 
                    WHERE user_id IN (
                        SELECT user_id FROM enrollments WHERE course_id = ?
                    )
                )
                AND u.role = 'student'
                LIMIT 100
            ");
            $stmt->execute([$courseId]);
            
            echo json_encode([
                'success' => true, 
                'suggested_students' => $stmt->fetchAll(PDO::FETCH_ASSOC),
                'ai_confidence' => 85 // Simulated AI confidence score
            ]);
            break;
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function sendAnnouncementNotifications($conn, $announcementId, $data) {
    // Get target students
    $stmt = $conn->prepare("SELECT id, email, full_name FROM users WHERE role = 'student'");
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($students as $student) {
        // Send email
        $emailData = [
            'to' => $student['email'],
            'subject' => '📢 إعلان جديد: ' . $data['title'],
            'message' => generateAnnouncementEmail($student, $data)
        ];
        
        // Call sendMail.php
        file_get_contents('../../Mailer/sendMail.php?' . http_build_query($emailData));
        
        // Insert notification record
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, type, title, message, link) VALUES (?, 'announcement', ?, ?, ?)");
        $stmt->execute([
            $student['id'],
            $data['title'],
            $data['description'],
            '/platform/announcements.php?id=' . $announcementId
        ]);
    }
}

function publishToExternalWebsite($data) {
    // API call to external website (example)
    $ch = curl_init('https://your-external-website.com/api/courses');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);
    
    return json_decode($response, true);
}

function generateAnnouncementEmail($student, $data) {
    return "
        <div style='font-family: Arial; direction: rtl; padding: 20px;'>
            <h2>مرحباً {$student['full_name']}</h2>
            <h3>📢 {$data['title']}</h3>
            <p>{$data['description']}</p>
            <a href='" . ($_SERVER['HTTP_HOST'] ?? 'localhost') . "/platform/courses.php?id={$data['course_id']}' 
               style='display: inline-block; background: #4F46E5; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;'>
                🎓 سجل الآن
            </a>
        </div>
    ";
}
?>
```

#### ب) Frontend JavaScript

في `dashboard.php`، أضف:

```javascript
async function renderAnnouncements() {
    setPageHeader('🤖 نظام الإعلانات الذكي', 'إنشاء وإدارة الإعلانات بالذكاء الاصطناعي');
    clearPageBody();
    const body = document.getElementById('pageBody');
    
    body.innerHTML = `
        <div class="space-y-6">
            <!-- AI Insights -->
            <div class="bg-gradient-to-br from-purple-50 to-pink-50 rounded-2xl p-6">
                <h3 class="font-bold text-xl mb-4">🤖 رؤى ذكية</h3>
                <div class="grid grid-cols-3 gap-4">
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-sm text-gray-600">معدل الفتح</p>
                        <p class="text-3xl font-bold text-purple-600" id="openRate">0%</p>
                    </div>
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-sm text-gray-600">معدل التحويل</p>
                        <p class="text-3xl font-bold text-emerald-600" id="conversionRate">0%</p>
                    </div>
                    <div class="bg-white rounded-xl p-4">
                        <p class="text-sm text-gray-600">أفضل وقت للنشر</p>
                        <p class="text-xl font-bold text-indigo-600" id="bestTime">10:00 ص</p>
                    </div>
                </div>
            </div>
            
            <!-- Announcements List -->
            <div class="bg-white rounded-2xl p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="font-bold text-xl">📢 الإعلانات</h3>
                    <button onclick="openAnnouncementModal()" class="px-4 py-2 bg-purple-600 text-white rounded-lg">
                        إنشاء إعلان جديد
                    </button>
                </div>
                <div id="announcementsList">جاري التحميل...</div>
            </div>
        </div>
    `;
    
    loadAnnouncements();
}

async function loadAnnouncements() {
    try {
        const data = await fetchJson('api/manage_announcements_ai.php');
        const announcements = data.data || [];
        
        const html = announcements.map(a => `
            <div class="border rounded-xl p-4 mb-3">
                <h4 class="font-bold">${a.title}</h4>
                <p class="text-sm text-gray-600">${a.description}</p>
                <div class="flex gap-4 mt-2 text-sm">
                    <span>👁️ ${a.views_count} مشاهدة</span>
                    <span>🎓 ${a.enrollments_count} تسجيل</span>
                </div>
            </div>
        `).join('');
        
        document.getElementById('announcementsList').innerHTML = html || 'لا توجد إعلانات';
    } catch (error) {
        showToast('خطأ في تحميل الإعلانات', 'error');
    }
}
```

---

## 4. نظام الإشعارات

### قاعدة البيانات

أنشئ جدول الإشعارات:

```sql
CREATE TABLE IF NOT EXISTS notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    type ENUM('info', 'warning', 'success', 'announcement', 'payment', 'grade') DEFAULT 'info',
    title VARCHAR(255) NOT NULL,
    message TEXT,
    link VARCHAR(500),
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    read_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_user_read (user_id, is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### Real-time Notifications (Polling)

```javascript
// Add to dashboard.php

let notificationInterval;

function startNotificationPolling() {
    // Initial load
    loadNotifications();
    
    // Poll every 30 seconds
    notificationInterval = setInterval(loadNotifications, 30000);
}

async function loadNotifications() {
    try {
        const response = await fetch('api/get_notifications.php');
        const data = await response.json();
        
        updateNotificationBadge(data.unread_count);
        updateNotificationDropdown(data.notifications);
    } catch (error) {
        console.error('Failed to load notifications:', error);
    }
}

function updateNotificationBadge(count) {
    const badge = document.getElementById('notificationBadge');
    if (badge) {
        badge.textContent = count;
        badge.style.display = count > 0 ? 'block' : 'none';
    }
}

// Start polling when page loads
document.addEventListener('DOMContentLoaded', () => {
    startNotificationPolling();
});

// Stop polling when page unloads
window.addEventListener('beforeunload', () => {
    if (notificationInterval) {
        clearInterval(notificationInterval);
    }
});
```

---

## 5. التحقق من Sidebar

### التحقق السريع

```javascript
// في dashboard.php، تأكد من وجود:

1. هيكل HTML للـ Sidebar موجود في كل صفحة
2. Sidebar يحتوي على جميع الروابط
3. Active state يعمل بشكل صحيح
4. Responsive design (يخفي/يظهر في الموبايل)
```

### إضافة Breadcrumbs

```javascript
function updateBreadcrumbs(pageName) {
    const breadcrumbs = document.getElementById('breadcrumbs');
    if (!breadcrumbs) return;
    
    const paths = {
        'home': ['لوحة التحكم'],
        'trainees': ['لوحة التحكم', 'المتدربين'],
        'trainers': ['لوحة التحكم', 'المدربين'],
        'courses': ['لوحة التحكم', 'الدورات'],
        'finance': ['لوحة التحكم', 'الشؤون المالية'],
        'announcements': ['لوحة التحكم', 'الإعلانات'],
        'idcards': ['لوحة التحكم', 'البطاقات الذكية']
    };
    
    const path = paths[pageName] || ['لوحة التحكم'];
    
    breadcrumbs.innerHTML = path.map((item, index) => `
        <span class="${index === path.length - 1 ? 'text-indigo-600 font-semibold' : 'text-gray-500'}">
            ${item}
        </span>
        ${index < path.length - 1 ? '<span class="text-gray-400 mx-2">/</span>' : ''}
    `).join('');
}
```

---

## ✅ قائمة التحقق النهائية

### قبل النشر:
- [x] إضافة Chart.js CDN ✅
- [x] نظام المدربين بالذكاء الاصطناعي ✅
- [ ] اختبار النظام المالي
- [ ] اختبار نظام الطلاب
- [ ] اختبار نظام المدربين الذكي
- [ ] اختبار إرسال البريد
- [ ] تأمين الـ APIs
- [ ] إنشاء Backup للقاعدة
- [ ] اختبار الصلاحيات
- [ ] اختبار على أجهزة متعددة
- [ ] مراجعة الأمان
- [ ] توثيق التغييرات

---

## 🎉 ملخص التحديثات الأخيرة

### ✅ تم إكمال نظام المدربين بالذكاء الاصطناعي (2025-11-09)

**المكونات المضافة:**
1. **AI Performance Score**: نظام تقييم ذكي يعتمد على 4 معايير (الحضور، تقييم الطلاب، معدل الإكمال، جودة المحتوى)
2. **نظام الشارات الذكي**: 4 شارات (مدرب محترف، الأعلى تقييماً، الحضور المثالي، خبير متمرس)
3. **نظام المكافآت**: نقاط مكافأة من 25-100 حسب الأداء
4. **Leaderboard**: قائمة الشرف لأفضل 10 مدربين مع ترتيب ديناميكي
5. **بطاقات المدربين المحسّنة**: عرض AI Score، توصيات ذكية، شارات، نقاط المكافأة
6. **توصيات AI**: 4 مستويات من التوصيات الذكية حسب الأداء

**الوظائف المضافة:**
- `calculateAIPerformanceScore(trainer)` - حساب درجة الأداء
- `getAIRecommendations(score)` - توصيات ذكية
- `getBadges(trainer)` - استخراج الشارات
- `getRewardPoints(score)` - حساب نقاط المكافأة
- `renderTrainersLeaderboard(trainers)` - عرض قائمة الشرف

**التحسينات على الواجهة:**
- تدرجات لونية حديثة
- أيقونات Lucide
- تصميم responsive
- Hover effects
- Color-coded performance indicators

---

**✨ دليل سريع - v1.1**  
**📅 آخر تحديث:** 2025-11-09  
**🔥 نسبة الإنجاز:** 50% (4 من 8 أنظمة مكتملة)
