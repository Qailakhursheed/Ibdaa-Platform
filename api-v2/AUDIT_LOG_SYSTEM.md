# 🔍 نظام سجل التدقيق (Audit Log System)

**التاريخ:** 20 نوفمبر 2025  
**الحالة:** ✅ مكتمل ونشط  
**الإصدار:** 1.0.0

---

## 📋 نظرة عامة

نظام متكامل لتسجيل جميع العمليات الحساسة في المنصة، يوفر:
- ✅ تتبع كامل لجميع الإجراءات (من فعل ماذا ومتى)
- ✅ سجل تفصيلي للتغييرات (قبل وبعد)
- ✅ تصنيف حسب مستوى الخطورة
- ✅ تقارير وإحصائيات شاملة
- ✅ بحث وتصفية متقدمة

---

## 🎯 الميزات الرئيسية

### 1. **التسجيل التلقائي**
يسجل تلقائياً:
- ✅ جميع عمليات الإضافة (POST)
- ✅ جميع عمليات التحديث (PUT/PATCH)
- ✅ جميع عمليات الحذف (DELETE)
- ✅ محاولات الوصول الفاشلة
- ✅ الإجراءات الحرجة

### 2. **معلومات شاملة**
كل سجل يحتوي على:
```json
{
  "user_id": 1,
  "user_name": "أحمد محمد",
  "user_email": "manager@ibdaa.com",
  "user_role": "manager",
  "action": "delete",
  "model_type": "Student",
  "model_id": 123,
  "description": "حذف سجل طالب",
  "http_method": "DELETE",
  "url": "https://api.ibdaa.com/v1/students/123",
  "ip_address": "192.168.1.100",
  "user_agent": "Mozilla/5.0...",
  "old_values": {"name": "محمد علي", "status": "active"},
  "new_values": null,
  "metadata": {"route": "students.destroy", "status_code": 200},
  "severity": "critical",
  "created_at": "2025-11-20 14:32:45"
}
```

### 3. **مستويات الخطورة**
- 🔴 **Critical** - حذف سجلات
- 🟠 **High** - إضافة/تحديث سجلات
- 🟡 **Medium** - عمليات عادية
- 🟢 **Low** - مشاهدة البيانات

---

## 📡 API Endpoints

### 1. قائمة السجلات
```http
GET /api/v1/audit-logs
Authorization: Bearer {token}
```

**Query Parameters:**
- `user_id` - تصفية حسب المستخدم
- `action` - تصفية حسب نوع العملية (create, update, delete)
- `model_type` - تصفية حسب النموذج (Student, Course, User)
- `model_id` - تصفية حسب معرف محدد
- `severity` - تصفية حسب الخطورة (low, medium, high, critical)
- `start_date` - من تاريخ
- `end_date` - إلى تاريخ
- `search` - بحث في الوصف أو اسم المستخدم
- `per_page` - عدد السجلات في الصفحة (افتراضي: 20)

**مثال:**
```bash
GET /api/v1/audit-logs?action=delete&severity=critical&per_page=50
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "user_name": "أحمد محمد",
      "user_email": "manager@ibdaa.com",
      "user_role": "manager",
      "action": "delete",
      "model_type": "Student",
      "model_id": 123,
      "description": "حذف سجل طالب",
      "severity": "critical",
      "created_at": "2025-11-20T14:32:45.000000Z"
    }
  ],
  "pagination": {
    "current_page": 1,
    "per_page": 20,
    "total": 150,
    "last_page": 8
  }
}
```

### 2. تفاصيل سجل محدد
```http
GET /api/v1/audit-logs/{id}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": {
    "id": 1,
    "user_id": 1,
    "user_name": "أحمد محمد",
    "action": "update",
    "model_type": "Student",
    "model_id": 123,
    "old_values": {
      "full_name": "محمد علي",
      "status": "active"
    },
    "new_values": {
      "full_name": "محمد علي المحمدي",
      "status": "active"
    },
    "ip_address": "192.168.1.100",
    "created_at": "2025-11-20T14:32:45.000000Z"
  }
}
```

### 3. إحصائيات السجلات
```http
GET /api/v1/audit-logs/statistics
Authorization: Bearer {token}
```

**Query Parameters:**
- `start_date` - من تاريخ (افتراضي: آخر 30 يوم)
- `end_date` - إلى تاريخ (افتراضي: اليوم)

**Response:**
```json
{
  "success": true,
  "data": {
    "total_logs": 1542,
    "by_action": {
      "create": 542,
      "update": 687,
      "delete": 143,
      "failed_attempt": 170
    },
    "by_user_role": {
      "manager": 892,
      "technical": 450,
      "trainer": 200
    },
    "by_severity": {
      "low": 320,
      "medium": 687,
      "high": 392,
      "critical": 143
    },
    "critical_actions": 143,
    "failed_attempts": 170,
    "top_users": [
      {
        "user_name": "أحمد محمد",
        "user_email": "manager@ibdaa.com",
        "action_count": 245
      }
    ],
    "recent_critical": [...]
  }
}
```

### 4. سجل نشاط مستخدم
```http
GET /api/v1/audit-logs/user/{userId}
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 100,
      "action": "update",
      "model_type": "Student",
      "description": "تحديث سجل طالب",
      "created_at": "2025-11-20T14:30:00.000000Z"
    }
  ]
}
```

### 5. تاريخ سجل معين
```http
GET /api/v1/audit-logs/model-history?model_type=Student&model_id=123
Authorization: Bearer {token}
```

**Response:**
```json
{
  "success": true,
  "data": [
    {
      "id": 1,
      "action": "create",
      "user_name": "أحمد محمد",
      "description": "إضافة طالب جديد",
      "created_at": "2025-11-15T10:00:00.000000Z"
    },
    {
      "id": 45,
      "action": "update",
      "user_name": "سارة أحمد",
      "old_values": {"status": "pending"},
      "new_values": {"status": "active"},
      "created_at": "2025-11-16T11:30:00.000000Z"
    },
    {
      "id": 123,
      "action": "delete",
      "user_name": "أحمد محمد",
      "description": "حذف سجل طالب",
      "severity": "critical",
      "created_at": "2025-11-20T14:32:45.000000Z"
    }
  ]
}
```

---

## 🔒 الصلاحيات

### الوصول للسجلات:
- ✅ **Manager فقط** - يمكنه مشاهدة جميع السجلات
- ❌ **Technical/Trainer/Student** - لا يمكنهم الوصول

### ما يتم تسجيله:
- ✅ جميع المستخدمين المصادق عليهم
- ❌ الطلبات العامة (Public endpoints)

---

## 🎨 أمثلة الاستخدام

### 1. البحث عن من حذف طالب معين
```bash
GET /api/v1/audit-logs/model-history?model_type=Student&model_id=123
```

### 2. عرض جميع عمليات الحذف اليوم
```bash
GET /api/v1/audit-logs?action=delete&start_date=2025-11-20&end_date=2025-11-20
```

### 3. عرض العمليات الحرجة الأخيرة
```bash
GET /api/v1/audit-logs?severity=critical&per_page=10
```

### 4. تتبع نشاط مستخدم محدد
```bash
GET /api/v1/audit-logs/user/5
```

### 5. البحث في السجلات
```bash
GET /api/v1/audit-logs?search=محمد علي
```

### 6. إحصائيات آخر أسبوع
```bash
GET /api/v1/audit-logs/statistics?start_date=2025-11-13&end_date=2025-11-20
```

---

## 🗂️ البنية التقنية

### جدول قاعدة البيانات
```sql
CREATE TABLE `audit_logs` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT,
  `user_id` bigint unsigned DEFAULT NULL,
  `user_name` varchar(255) DEFAULT NULL,
  `user_email` varchar(255) DEFAULT NULL,
  `user_role` enum('manager','technical','trainer','student') DEFAULT NULL,
  `action` varchar(255) NOT NULL,
  `model_type` varchar(255) DEFAULT NULL,
  `model_id` bigint unsigned DEFAULT NULL,
  `description` varchar(255) NOT NULL,
  `http_method` varchar(10) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` text,
  `old_values` json DEFAULT NULL,
  `new_values` json DEFAULT NULL,
  `metadata` text,
  `severity` enum('low','medium','high','critical') DEFAULT 'medium',
  `created_at` timestamp NULL DEFAULT NULL,
  `updated_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `audit_logs_user_id_index` (`user_id`),
  KEY `audit_logs_action_index` (`action`),
  KEY `audit_logs_model_type_index` (`model_type`),
  KEY `audit_logs_model_type_model_id_index` (`model_type`,`model_id`),
  KEY `audit_logs_created_at_index` (`created_at`),
  KEY `audit_logs_severity_index` (`severity`)
);
```

### Middleware
```php
// app/Http/Middleware/AuditLogMiddleware.php
// يتم تطبيقه تلقائياً على جميع API routes
```

### Model
```php
// app/Models/AuditLog.php
AuditLog::logAction(
    action: 'delete',
    description: 'حذف طالب',
    modelType: 'Student',
    modelId: 123,
    severity: 'critical'
);
```

---

## 📊 أنواع العمليات المسجلة

### Actions Types:
- `create` - إضافة سجل جديد
- `update` - تحديث سجل
- `delete` - حذف سجل
- `view` - مشاهدة سجل
- `login` - تسجيل دخول
- `logout` - تسجيل خروج
- `failed_attempt` - محاولة فاشلة
- `access` - وصول إلى مورد

### Model Types:
- `Student` - الطلاب
- `Course` - الدورات
- `User` - المستخدمين
- `Enrollment` - التسجيلات
- `Exam` - الاختبارات
- `Grade` - الدرجات

---

## 🔍 حالات الاستخدام

### 1. التحقيق في حذف غير مصرح
```
السؤال: من حذف الطالب محمد علي؟
الحل: GET /api/v1/audit-logs/model-history?model_type=Student&model_id=123
النتيجة: أحمد محمد (manager) قام بالحذف في 2025-11-20 14:32
```

### 2. مراجعة تغييرات الدرجات
```
السؤال: من عدّل درجة الطالب؟
الحل: GET /api/v1/audit-logs?model_type=Grade&model_id=456&action=update
النتيجة: سارة أحمد (trainer) غيرت الدرجة من 75 إلى 85
```

### 3. تتبع نشاط مستخدم مشبوه
```
السؤال: ماذا فعل المستخدم X اليوم؟
الحل: GET /api/v1/audit-logs/user/5
النتيجة: قائمة بجميع العمليات مع التفاصيل
```

### 4. تقرير أمني شهري
```
السؤال: ما هي العمليات الحرجة هذا الشهر؟
الحل: GET /api/v1/audit-logs/statistics
النتيجة: إحصائيات شاملة مع أهم المستخدمين
```

---

## ⚡ الأداء والتحسينات

### Indexes المتوفرة:
- ✅ `user_id` - للبحث حسب المستخدم
- ✅ `action` - للتصفية حسب نوع العملية
- ✅ `model_type` - للتصفية حسب النموذج
- ✅ `model_type + model_id` - للبحث عن سجل معين
- ✅ `created_at` - للتصفية حسب التاريخ
- ✅ `severity` - للعمليات الحرجة

### سياسة الحذف:
- الاحتفاظ بالسجلات لمدة **365 يوم**
- حذف السجلات القديمة تلقائياً
- نسخ احتياطي قبل الحذف

---

## 🛡️ الأمان

### الحماية:
- ✅ الوصول للمدراء فقط
- ✅ تشفير البيانات الحساسة
- ✅ تسجيل IP Address
- ✅ تسجيل User Agent
- ✅ لا يمكن تعديل أو حذف السجلات

### الخصوصية:
- ❌ لا يتم تسجيل كلمات المرور
- ❌ لا يتم تسجيل Tokens
- ✅ يتم تسجيل البيانات الضرورية فقط

---

## 📈 التقارير المتاحة

### 1. تقرير النشاط اليومي
- عدد العمليات
- أنواع العمليات
- أكثر المستخدمين نشاطاً

### 2. تقرير الأمان
- العمليات الحرجة
- محاولات الوصول الفاشلة
- الأنشطة المشبوهة

### 3. تقرير المستخدم
- جميع عمليات مستخدم محدد
- إحصائيات شخصية
- Timeline كامل

### 4. تقرير السجل
- تاريخ كامل لسجل معين
- من أضافه، من عدله، من حذفه
- جميع التغييرات

---

## 🚀 التطوير المستقبلي

### المخطط:
- [ ] تصدير التقارير (PDF, Excel)
- [ ] إشعارات فورية للعمليات الحرجة
- [ ] Dashboard تفاعلي
- [ ] AI للكشف عن الأنشطة المشبوهة
- [ ] تكامل مع أنظمة SIEM

---

## ✅ الخلاصة

**نظام سجل التدقيق الآن:**
- ✅ نشط ويعمل تلقائياً
- ✅ يسجل جميع العمليات الحساسة
- ✅ يوفر تتبع كامل للتغييرات
- ✅ يدعم البحث والتصفية المتقدمة
- ✅ يوفر تقارير وإحصائيات
- ✅ آمن ومحمي
- ✅ محسّن للأداء

**المساءلة والشفافية مضمونة! 🔒**
