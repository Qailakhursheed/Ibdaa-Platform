# دليل تكامل نظام المراسلة
# Chat System Integration Guide

## ✅ تم إنشاء الملفات التالية:

### Backend APIs (6 ملفات):
1. ✅ `Manager/api/send_message.php` - إرسال رسائل (فردية + جماعية)
2. ✅ `Manager/api/get_conversations.php` - جلب قائمة المحادثات
3. ✅ `Manager/api/get_messages.php` - جلب رسائل محادثة محددة
4. ✅ `Manager/api/mark_messages_read.php` - تحديد رسائل كمقروءة
5. ✅ `Manager/api/delete_message.php` - حذف رسالة أو محادثة
6. ✅ `Manager/api/group_chat.php` - إدارة المحادثات الجماعية

### Frontend Components (3 ملفات):
7. ✅ `Manager/Components/chat_sidebar.php` - قائمة المحادثات
8. ✅ `Manager/Components/conversation_view.php` - عرض الرسائل
9. ✅ `Manager/JS/chat.js` - JavaScript الرئيسي

---

## 📋 خطوات التكامل مع أي Dashboard

### الخطوة 1: إضافة CSS

```php
<!-- في الـ <head> -->
<style>
/* Chat Icon Badge */
.chat-icon-wrapper {
    position: relative;
}

#chat-unread-badge {
    position: absolute;
    top: -8px;
    right: -8px;
    background-color: #ef4444;
    color: white;
    font-size: 11px;
    font-weight: bold;
    padding: 2px 6px;
    border-radius: 10px;
    min-width: 18px;
    text-align: center;
}
</style>
```

### الخطوة 2: إضافة Chat Icon في الـ Header/Topbar

#### مثال لـ Manager Dashboard:

```php
<!-- ابحث عن الـ header/topbar وأضف هذا الكود -->
<header id="topbar" class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between">
    <div class="flex items-center gap-3">
        <!-- المحتوى الموجود -->
    </div>
    
    <div class="flex items-center gap-4">
        
        <!-- 🆕 Chat Icon - أضف هذا -->
        <div class="chat-icon-wrapper">
            <button id="chat-toggle" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50 transition" 
                    aria-label="Messages" title="الرسائل">
                <i data-lucide="message-circle" class="w-5 h-5"></i>
                <span id="chat-unread-badge" class="hidden">0</span>
            </button>
        </div>
        
        <!-- Notifications Bell (الموجود) -->
        <button id="notificationsBell" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50">
            <i data-lucide="bell" class="w-5 h-5"></i>
        </button>
        
    </div>
</header>
```

### الخطوة 3: تضمين Components قبل </body>

```php
<!-- قبل نهاية الـ <body> مباشرة -->

<!-- Chat Components -->
<?php include 'Components/chat_sidebar.php'; ?>
<?php include 'Components/conversation_view.php'; ?>

<!-- Chat JavaScript -->
<script src="JS/chat.js"></script>

<!-- Initialize Lucide Icons -->
<script>
    if (typeof lucide !== 'undefined') {
        lucide.createIcons();
    }
</script>

</body>
</html>
```

### الخطوة 4: التحقق من Session Variables

تأكد من وجود هذه المتغيرات في الـ session:
```php
$_SESSION['user_id']        // معرف المستخدم
$_SESSION['user_role']      // دور المستخدم
$_SESSION['user_name']      // اسم المستخدم (اختياري)
$_SESSION['full_name']      // الاسم الكامل (اختياري)
```

---

## 🎯 التكامل التفصيلي لكل Dashboard

### 1️⃣ Manager Dashboard (Manager/dashboard.php)

**الموقع:** سطر 170 (بعد notifications bell)

```php
<!-- أضف بعد notification bell -->
<div class="chat-icon-wrapper">
    <button id="chat-toggle" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50 transition">
        <i data-lucide="message-circle" class="w-5 h-5"></i>
        <span id="chat-unread-badge" class="hidden">0</span>
    </button>
</div>
```

**قبل </body> (سطر ~8040):**
```php
<?php include 'Components/chat_sidebar.php'; ?>
<?php include 'Components/conversation_view.php'; ?>
<script src="JS/chat.js"></script>
```

---

### 2️⃣ Technical Dashboard (Technical/Portal.php)

**نفس الخطوات السابقة، لكن استخدم المسارات النسبية:**

```php
<!-- في الـ Header -->
<div class="chat-icon-wrapper">
    <button id="chat-toggle" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50 transition">
        <i data-lucide="message-circle" class="w-5 h-5"></i>
        <span id="chat-unread-badge" class="hidden">0</span>
    </button>
</div>

<!-- قبل </body> -->
<?php include '../Manager/Components/chat_sidebar.php'; ?>
<?php include '../Manager/Components/conversation_view.php'; ?>
<script src="../Manager/JS/chat.js"></script>
```

**تحديث API Paths في chat.js:**
```javascript
// إذا كنت في مجلد Technical، عدّل المسارات:
const response = await fetch('../Manager/api/get_conversations.php?limit=50');
```

---

### 3️⃣ Student Dashboard (platform/student-dashboard.php)

**في الـ Header:**
```php
<div class="flex items-center gap-3">
    <!-- Chat Icon -->
    <button id="chat-toggle" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50">
        <i data-lucide="message-circle" class="w-5 h-5"></i>
        <span id="chat-unread-badge" class="hidden">0</span>
    </button>
    
    <!-- Notifications (الموجود) -->
    <button id="studentNotificationsBtn" class="relative rounded-full border border-slate-200 p-2 hover:bg-slate-50">
        <i data-lucide="bell" class="w-5 h-5"></i>
    </button>
</div>

<!-- قبل </body> -->
<?php include '../Manager/Components/chat_sidebar.php'; ?>
<?php include '../Manager/Components/conversation_view.php'; ?>
<script src="../Manager/JS/chat.js"></script>
```

---

## 🔧 Customization - التخصيص

### تغيير فترة Polling (التحديث التلقائي):

**في JS/chat.js - سطر ~450:**
```javascript
startPolling() {
    // غيّر 5000 (5 ثواني) إلى أي قيمة تريدها بالميلي ثانية
    this.pollInterval = setInterval(() => {
        // ...
    }, 5000); // ⬅️ هنا
}
```

### تغيير الألوان:

**في chat_sidebar.php و conversation_view.php:**
```css
/* Chat Header */
.bg-gradient-to-r.from-blue-600.to-blue-700 {
    /* غيّر blue إلى أي لون آخر: green, purple, red, etc */
}

/* Unread Badge */
.bg-blue-600 {
    /* غيّر إلى bg-red-600 مثلاً */
}
```

### تخصيص الرسائل:

**في chat.js - دالة renderMessages():**
```javascript
// تخصيص شكل الرسائل المرسلة (Mine)
const mineTemplate = document.getElementById('message-mine-template');
// يمكنك تعديل الـ template في conversation_view.php
```

---

## 📱 Responsive Design

النظام responsive تلقائياً:
- **Desktop (> 768px):** Sidebar + Conversation جنباً إلى جنب
- **Mobile (< 768px):** Full-screen conversation، الـ sidebar يخفى تلقائياً

لا حاجة لتعديلات إضافية!

---

## ⚙️ API Endpoints - نقاط النهاية

### 1. إرسال رسالة
```javascript
POST /Manager/api/send_message.php
Body: {
    "receiver_id": 123,     // للرسائل الفردية
    "group_id": 45,         // للرسائل الجماعية
    "message_text": "مرحباً"
}
```

### 2. جلب المحادثات
```javascript
GET /Manager/api/get_conversations.php?limit=50&search=
```

### 3. جلب الرسائل
```javascript
GET /Manager/api/get_messages.php?contact_id=123&limit=50
GET /Manager/api/get_messages.php?group_id=45&limit=50
```

### 4. تحديد كمقروء
```javascript
POST /Manager/api/mark_messages_read.php
Body: {
    "message_ids": [1, 2, 3]
}
```

### 5. حذف محادثة
```javascript
DELETE /Manager/api/delete_message.php?contact_id=123
```

### 6. إدارة المجموعات
```javascript
// إنشاء مجموعة
POST /Manager/api/group_chat.php?action=create
Body: {
    "name": "فريق المشروع",
    "description": "...",
    "members": [123, 456]
}

// إضافة عضو
POST /Manager/api/group_chat.php?action=add_member
Body: {
    "group_id": 45,
    "user_id": 789
}

// مغادرة المجموعة
POST /Manager/api/group_chat.php?action=leave
Body: {
    "group_id": 45
}
```

---

## 🧪 الاختبار

### 1. اختبار أساسي:
1. افتح Manager Dashboard
2. انقر على أيقونة الرسائل
3. يجب أن يفتح Chat Sidebar
4. إذا لم تكن هناك محادثات، سترى "لا توجد محادثات"

### 2. اختبار إرسال رسالة:
1. افتح SQL Editor في phpMyAdmin
2. نفّذ:
```sql
-- احصل على user_id آخر
SELECT id, full_name FROM users WHERE id != 1 LIMIT 1;

-- سجّل دخول بحساب المستخدم الأول
-- اذهب لـ Chat، انقر "محادثة جديدة"
-- اختر المستخدم الثاني
-- أرسل رسالة
```

### 3. اختبار المحادثات الجماعية:
```sql
-- أنشئ مجموعة عبر API:
POST /Manager/api/group_chat.php?action=create
{
    "name": "مجموعة اختبار",
    "description": "للاختبار",
    "members": [2, 3, 4]
}
```

---

## 🐛 Troubleshooting - حل المشاكل

### المشكلة 1: Chat Icon لا يظهر
**الحل:**
- تأكد من تضمين Lucide icons في الـ <head>
- تأكد من استدعاء `lucide.createIcons()`

### المشكلة 2: لا يفتح Chat Sidebar
**الحل:**
- افتح Console (F12)
- ابحث عن أخطاء JavaScript
- تأكد من تضمين `chat.js`

### المشكلة 3: "يجب تسجيل الدخول أولاً"
**الحل:**
```php
// تأكد من بداية جميع API files:
session_start();
$user_id = $_SESSION['user_id'] ?? null;
```

### المشكلة 4: الرسائل لا تُرسل
**الحل:**
- افتح Network tab في Chrome DevTools
- انقر "Send"
- ابحث عن الطلب `send_message.php`
- انظر للـ Response
- إذا كانت `success: false`، اقرأ الـ `message`

### المشكلة 5: قاعدة البيانات
**الحل:**
```sql
-- تأكد من وجود الجداول:
SHOW TABLES LIKE 'messages';
SHOW TABLES LIKE 'group_chats';
SHOW TABLES LIKE 'group_chat_members';
SHOW TABLES LIKE 'group_messages';
SHOW TABLES LIKE 'group_message_reads';

-- إذا لم تكن موجودة، نفّذ:
SOURCE database/messages.sql;
```

---

## 📊 الأداء

### معدل الاستهلاك:
- **Polling:** 1 طلب كل 5 ثواني
- **Opening conversation:** 1 طلب لجلب الرسائل
- **Sending message:** 1 طلب POST

### التحسينات الممكنة:
1. ✅ استخدام WebSocket بدلاً من Polling (Real-time)
2. ✅ Caching للمحادثات في LocalStorage
3. ✅ Lazy loading للرسائل القديمة
4. ✅ Image compression للمرفقات

---

## 🔐 الأمان

### تم تطبيقه:
- ✅ Session Authentication
- ✅ Prepared Statements (SQL Injection protection)
- ✅ Input validation (طول الرسالة، معرفات صحيحة)
- ✅ Authorization checks (المرسل فقط يمكنه الحذف)

### يُنصح بإضافته:
- ⚠️ Rate Limiting (منع Spam)
- ⚠️ CSRF Tokens
- ⚠️ XSS Protection في عرض الرسائل
- ⚠️ File upload validation (إذا أضفت مرفقات)

---

## 📈 الإحصائيات

### تم إنشاء:
- ✅ **6 ملفات API** (PHP)
- ✅ **2 مكونات UI** (HTML/CSS)
- ✅ **1 ملف JavaScript** (~500 سطر)
- ✅ **قاعدة بيانات كاملة** (5 جداول)

### الميزات:
- ✅ محادثات فردية (1-to-1)
- ✅ محادثات جماعية (Groups)
- ✅ Real-time updates (Polling)
- ✅ Unread badges
- ✅ تحديد كمقروء تلقائياً
- ✅ حذف رسائل/محادثات
- ✅ بحث في المحادثات
- ✅ إشعارات تلقائية
- ✅ Responsive design
- ✅ دعم عربي كامل

---

## ✅ Checklist - قائمة المراجعة

### للمطورين:
- [ ] قاعدة البيانات: تنفيذ `database/messages.sql`
- [ ] Manager Dashboard: إضافة Chat Icon + Components
- [ ] Technical Dashboard: إضافة Chat Icon + Components  
- [ ] Student Dashboard: إضافة Chat Icon + Components
- [ ] اختبار إرسال رسالة
- [ ] اختبار المحادثات الجماعية
- [ ] اختبار على Mobile
- [ ] اختبار Real-time updates

### للمستخدمين:
- [ ] يمكنني فتح Chat Sidebar
- [ ] أرى قائمة المحادثات
- [ ] يمكنني إرسال رسالة
- [ ] أرى الرسائل الجديدة تلقائياً
- [ ] يمكنني حذف محادثة
- [ ] يعمل على الهاتف

---

**🎉 نظام المراسلة جاهز للاستخدام!**
