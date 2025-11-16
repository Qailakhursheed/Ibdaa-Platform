# 🔗 دليل التكامل بين اللوحات
## Dashboard Integration Guide

**التاريخ:** 10 نوفمبر 2025  
**الحالة:** ✅ جاهز ومكتمل

---

## 📋 نظرة عامة

تم إنشاء نظام تكامل شامل يربط جميع لوحات التحكم (المدير، المشرف الفني، المدرب، الطالب) مع الأنظمة الجديدة.

---

## 🎯 الملفات المُنشأة

### 1. **dashboard-integration.js**
**المسار:** `Manager/js/dashboard-integration.js`

**الوظائف:**
- ✅ التنقل بين اللوحات المختلفة
- ✅ واجهات API موحدة لجميع الأنظمة
- ✅ وظائف مشتركة (رسائل، إشعارات، بطاقات)
- ✅ واجهة مستخدم موحدة (Toast, Modal, Confirm)
- ✅ اختصارات لوحة المفاتيح

---

## 🚀 كيفية الاستخدام

### التنقل بين اللوحات

```javascript
// الانتقال إلى لوحة المدير
DashboardIntegration.navigation.toManager();

// الانتقال إلى لوحة المشرف الفني
DashboardIntegration.navigation.toTechnical();

// الانتقال إلى لوحة المدرب
DashboardIntegration.navigation.toTrainer(trainerId);

// الانتقال إلى لوحة الطالب
DashboardIntegration.navigation.toStudent(studentId);
```

---

### استخدام API الدردشة

```javascript
// جلب المحادثات
DashboardIntegration.api.chat.getConversations()
    .then(data => {
        console.log('Conversations:', data.conversations);
    });

// جلب الرسائل
DashboardIntegration.api.chat.getMessages(contactId)
    .then(data => {
        console.log('Messages:', data.messages);
    });

// إرسال رسالة
DashboardIntegration.api.chat.sendMessage(receiverId, 'مرحباً!')
    .then(data => {
        if (data.success) {
            console.log('Message sent!');
        }
    });
```

---

### استخدام API الإشعارات

```javascript
// جلب جميع الإشعارات
DashboardIntegration.api.notifications.getAll(1, 10)
    .then(data => {
        console.log('Notifications:', data.notifications);
    });

// إنشاء إشعار
DashboardIntegration.api.notifications.create(
    'عنوان الإشعار',
    'محتوى الإشعار',
    'info',
    '/link/to/page'
).then(data => {
    console.log('Notification created:', data);
});

// إرسال إشعار جماعي
DashboardIntegration.api.notifications.broadcast(
    'إعلان هام',
    'محتوى الإعلان',
    ['student', 'trainer']
).then(data => {
    console.log('Broadcast sent:', data);
});
```

---

### استخدام API البطاقات

```javascript
// إصدار بطاقة
DashboardIntegration.api.idCards.generate(userId)
    .then(data => {
        if (data.success) {
            console.log('Card generated:', data.card);
        }
    });

// جلب بطاقة
DashboardIntegration.api.idCards.getCard(userId)
    .then(data => {
        if (data.success && data.card) {
            console.log('Card:', data.card);
        }
    });

// التحقق من QR Code
DashboardIntegration.api.idCards.scanVerify(qrData)
    .then(data => {
        if (data.success) {
            console.log('Valid card:', data.card);
        }
    });
```

---

### استخدام API الاستيراد

```javascript
// رفع ملف
const fileInput = document.getElementById('fileInput');
const file = fileInput.files[0];

DashboardIntegration.api.import.uploadFile(file, 'students')
    .then(data => {
        if (data.success) {
            console.log('Import successful:', data);
        }
    });

// جلب سجل الاستيراد
DashboardIntegration.api.import.getHistory()
    .then(data => {
        console.log('Import history:', data.logs);
    });
```

---

### استخدام API التحليلات

```javascript
// جلب إحصائيات اللوحة
DashboardIntegration.api.analytics.getDashboardStats()
    .then(data => {
        console.log('Dashboard stats:', data.stats);
    });

// جلب الطلاب حسب الحالة
DashboardIntegration.api.analytics.getStudentsByStatus()
    .then(data => {
        console.log('Students by status:', data.data);
    });

// جلب الإيرادات الشهرية
DashboardIntegration.api.analytics.getMonthlyRevenue(2025)
    .then(data => {
        console.log('Monthly revenue:', data.data);
    });

// جلب تحليلات شاملة
DashboardIntegration.api.analytics.getComprehensive()
    .then(data => {
        console.log('Comprehensive analytics:', data.analytics);
    });
```

---

### الوظائف المشتركة

```javascript
// إرسال رسالة
DashboardIntegration.shared.sendMessage(receiverId, 'مرحباً!');

// إرسال إشعار
DashboardIntegration.shared.sendNotification(
    userId,
    'عنوان الإشعار',
    'محتوى الإشعار',
    'info'
);

// تنزيل البطاقة
DashboardIntegration.shared.downloadIDCard(userId);

// مسح QR Code
DashboardIntegration.shared.scanQRCode(qrData)
    .then(card => {
        if (card) {
            console.log('Valid card:', card);
        }
    });
```

---

### واجهة المستخدم

```javascript
// عرض رسالة Toast
DashboardIntegration.ui.showToast('تم بنجاح!', 'success');
DashboardIntegration.ui.showToast('حدث خطأ!', 'error');
DashboardIntegration.ui.showToast('تحذير!', 'warning');
DashboardIntegration.ui.showToast('معلومة', 'info');

// عرض مودال
DashboardIntegration.ui.showModal(
    'عنوان المودال',
    '<p>محتوى المودال</p>',
    [
        {
            text: 'إلغاء',
            class: 'bg-slate-200 text-slate-700',
            onclick: 'console.log("Cancelled")'
        },
        {
            text: 'حفظ',
            class: 'bg-sky-600 text-white',
            onclick: 'console.log("Saved")'
        }
    ]
);

// مربع تأكيد
DashboardIntegration.ui.confirmDialog(
    'هل أنت متأكد من الحذف؟',
    'deleteItem',  // وظيفة التأكيد
    'cancelDelete' // وظيفة الإلغاء (اختياري)
);
```

---

## ⌨️ اختصارات لوحة المفاتيح

| الاختصار | الوظيفة |
|----------|---------|
| `Ctrl + Shift + M` | الانتقال إلى لوحة المدير |
| `Ctrl + Shift + T` | الانتقال إلى لوحة المشرف الفني |
| `Ctrl + K` | البحث السريع |
| `Ctrl + N` | إضافة طالب جديد |
| `Esc` | إغلاق المودال |

---

## 📝 أمثلة عملية

### مثال 1: إرسال رسالة مع إشعار

```javascript
function sendMessageWithNotification(receiverId, message) {
    // إرسال الرسالة
    DashboardIntegration.shared.sendMessage(receiverId, message)
        .then(data => {
            if (data.success) {
                // إرسال إشعار
                return DashboardIntegration.shared.sendNotification(
                    receiverId,
                    'رسالة جديدة',
                    'لديك رسالة جديدة من ' + DashboardIntegration.currentUser.name,
                    'message',
                    '/Manager/dashboards/messages.php'
                );
            }
        })
        .then(data => {
            DashboardIntegration.ui.showToast('✓ تم الإرسال بنجاح', 'success');
        })
        .catch(error => {
            DashboardIntegration.ui.showToast('خطأ في الإرسال', 'error');
        });
}
```

---

### مثال 2: فتح لوحة طالب من لوحة المدير

```javascript
function viewStudentDetails(studentId) {
    // عرض مودال سريع
    DashboardIntegration.ui.showModal(
        'تفاصيل الطالب',
        `
            <div class="text-center py-4">
                <p>جاري التحميل...</p>
            </div>
        `,
        [
            {
                text: 'عرض اللوحة الكاملة',
                class: 'bg-sky-600 text-white hover:bg-sky-700',
                onclick: `DashboardIntegration.navigation.toStudent(${studentId})`
            },
            {
                text: 'إرسال رسالة',
                class: 'bg-emerald-600 text-white hover:bg-emerald-700',
                onclick: `openChatWithStudent(${studentId})`
            }
        ]
    );
    
    // جلب بيانات الطالب
    fetch(`/Manager/api/manage_users.php?action=get&id=${studentId}`)
        .then(response => response.json())
        .then(data => {
            // تحديث محتوى المودال
        });
}
```

---

### مثال 3: إصدار بطاقة وإرسال إشعار

```javascript
function issueCardAndNotify(studentId) {
    // إصدار البطاقة
    DashboardIntegration.api.idCards.generate(studentId)
        .then(data => {
            if (data.success) {
                // إرسال إشعار للطالب
                return DashboardIntegration.shared.sendNotification(
                    studentId,
                    'تم إصدار بطاقتك الطلابية',
                    'يمكنك الآن تنزيل بطاقتك من لوحة التحكم',
                    'success',
                    '/Manager/dashboards/student-dashboard.php'
                );
            }
        })
        .then(() => {
            DashboardIntegration.ui.showToast('✓ تم إصدار البطاقة بنجاح', 'success');
        })
        .catch(error => {
            DashboardIntegration.ui.showToast('خطأ في الإصدار', 'error');
        });
}
```

---

## 🔄 التكامل مع اللوحات

### في لوحة المدير

```javascript
// تم التكامل في manager-dashboard.php
// الأنظمة المُفعّلة:
// ✅ نظام الإشعارات
// ✅ نظام الدردشة
// ✅ الرسوم البيانية الحية
// ✅ النماذج المتقدمة
// ✅ جميع API Endpoints
```

---

### في لوحة الطالب

```javascript
// إضافة في student-dashboard.php

// تنزيل البطاقة
function downloadMyCard() {
    DashboardIntegration.shared.downloadIDCard();
}

// عرض الحالة المالية
function loadFinancialStatus() {
    fetch('/Manager/api/manage_finance.php?action=get_student_payments')
        .then(response => response.json())
        .then(data => {
            // عرض البيانات
        });
}

// تحميل الإشعارات
function loadMyNotifications() {
    DashboardIntegration.api.notifications.getAll(1, 10)
        .then(data => {
            // عرض الإشعارات
        });
}
```

---

### في لوحة المدرب

```javascript
// إضافة في trainer-dashboard.php

// تسجيل الحضور
function recordAttendance(courseId, date, attendance) {
    fetch('/Manager/api/manage_attendance.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'record',
            course_id: courseId,
            date: date,
            attendance: attendance
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            DashboardIntegration.ui.showToast('✓ تم حفظ الحضور', 'success');
        }
    });
}

// إرسال رسالة لطالب
function messageStudent(studentId) {
    const message = prompt('أدخل الرسالة:');
    if (message) {
        DashboardIntegration.shared.sendMessage(studentId, message);
    }
}
```

---

### في لوحة المشرف الفني

```javascript
// إضافة في technical-dashboard.php

// الموافقة على دورة
function approveCourse(courseId, qualityScore, notes) {
    fetch('/Manager/api/manage_courses.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({
            action: 'approve',
            course_id: courseId,
            quality_score: qualityScore,
            notes: notes
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            DashboardIntegration.ui.showToast('✓ تمت الموافقة', 'success');
            
            // إرسال إشعار للمدرب
            DashboardIntegration.shared.sendNotification(
                data.trainer_id,
                'تمت الموافقة على دورتك',
                'تمت الموافقة على الدورة: ' + data.course_title,
                'success'
            );
        }
    });
}
```

---

## 📊 مخطط التكامل

```
┌─────────────────────────────────────────────────────────────┐
│                   Dashboard Integration                      │
│                  (dashboard-integration.js)                  │
└──────────┬──────────────────────────────────────────────────┘
           │
           ├──► Navigation: التنقل بين اللوحات
           │    ├─► toManager()
           │    ├─► toTechnical()
           │    ├─► toTrainer(id)
           │    └─► toStudent(id)
           │
           ├──► API: واجهات موحدة
           │    ├─► chat: نظام الدردشة
           │    ├─► notifications: نظام الإشعارات
           │    ├─► idCards: نظام البطاقات
           │    ├─► import: نظام الاستيراد
           │    ├─► analytics: نظام التحليلات
           │    └─► registrationRequests: طلبات التسجيل
           │
           ├──► Shared: وظائف مشتركة
           │    ├─► sendMessage()
           │    ├─► sendNotification()
           │    ├─► downloadIDCard()
           │    └─► scanQRCode()
           │
           └──► UI: واجهة المستخدم
                ├─► showToast()
                ├─► showModal()
                └─► confirmDialog()
```

---

## ✅ التحديثات المُطبقة

### في manager-dashboard.php:
- ✅ إضافة `dashboard-integration.js`
- ✅ إضافة `advanced-forms.js`
- ✅ إضافة `dynamic-charts.js`
- ✅ إضافة Chart.js CDN
- ✅ تفعيل نظام الإشعارات
- ✅ تفعيل نظام الدردشة
- ✅ إضافة اختصارات لوحة المفاتيح
- ✅ إضافة وظائف الترابط

---

## 🚀 الخطوات التالية

### 1. تطبيق التكامل في اللوحات الأخرى

**في student-dashboard.php:**
```php
<!-- قبل </body> -->
<script src="../js/dashboard-integration.js"></script>
<script src="../js/advanced-forms.js"></script>
```

**في trainer-dashboard.php:**
```php
<!-- قبل </body> -->
<script src="../js/dashboard-integration.js"></script>
<script src="../js/advanced-forms.js"></script>
```

**في technical-dashboard.php:**
```php
<!-- قبل </body> -->
<script src="../js/dashboard-integration.js"></script>
<script src="../js/advanced-forms.js"></script>
```

---

### 2. اختبار التكامل

```bash
# افتح لوحة المدير
http://localhost/Ibdaa-Taiz/Manager/dashboards/manager-dashboard.php

# افتح Console (F12)
# يجب أن ترى:
# ✅ Dashboard Integration System Loaded Successfully!
# ✅ Charts System Initialized!
# ✅ Manager Dashboard Advanced Systems Loaded!
```

---

## 📞 الدعم والتوثيق

للمزيد من المعلومات، راجع:
- `IMPLEMENTATION_GUIDE_COMPLETE.md` - دليل التطبيق الشامل
- `COMPREHENSIVE_DEVELOPMENT_REPORT.md` - التقرير التطويري
- `ACTION_PLAN.md` - خطة العمل

---

**✍️ تم بواسطة:** GitHub Copilot  
**📅 التاريخ:** 10 نوفمبر 2025  
**✅ الحالة:** جاهز ومكتمل
