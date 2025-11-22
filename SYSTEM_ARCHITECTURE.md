# 🏗️ البنية المعمارية للنظام المتكامل
## System Architecture Documentation

---

## 📊 نظرة عامة على البنية

### الطبقات الأساسية

```
┌─────────────────────────────────────────────────────────────────┐
│                        طبقة العرض (UI Layer)                      │
│  ┌──────────┐  ┌──────────┐  ┌──────────┐  ┌──────────┐        │
│  │ المدير    │  │ المشرف   │  │ المدرب   │  │ الطالب   │        │
│  └────┬─────┘  └────┬─────┘  └────┬─────┘  └────┬─────┘        │
└───────┼─────────────┼─────────────┼─────────────┼──────────────┘
        │             │             │             │
┌───────┼─────────────┼─────────────┼─────────────┼──────────────┐
│       │             │             │             │               │
│       └─────────────┴─────────────┴─────────────┘               │
│                    طبقة التطبيق (Application Layer)              │
│  ┌──────────────────────────────────────────────────────┐       │
│  │ Authentication │ Authorization │ Session Management  │       │
│  └──────────────────────────────────────────────────────┘       │
│  ┌──────────────────────────────────────────────────────┐       │
│  │ Business Logic │ Validation │ Notifications         │       │
│  └──────────────────────────────────────────────────────┘       │
└─────────────────────────┬────────────────────────────────────────┘
                          │
┌─────────────────────────┴────────────────────────────────────────┐
│                   طبقة الوصول للبيانات (Data Layer)              │
│  ┌──────────────────────────────────────────────────────┐       │
│  │         PDO │ MySQLi │ Query Builder                │       │
│  └──────────────────────────────────────────────────────┘       │
└─────────────────────────┬────────────────────────────────────────┘
                          │
┌─────────────────────────┴────────────────────────────────────────┐
│                     قاعدة البيانات (Database)                    │
│                         MySQL / MariaDB                          │
│  ┌──────────────────────────────────────────────────────┐       │
│  │ Users │ Courses │ Messages │ Notifications │ ...    │       │
│  └──────────────────────────────────────────────────────┘       │
└──────────────────────────────────────────────────────────────────┘
```

---

## 🗂️ هيكل قاعدة البيانات

### جداول المستخدمين والصلاحيات

```sql
users
├── id (PK)
├── username (UNIQUE)
├── email (UNIQUE)
├── password (HASHED)
├── role (admin|manager|supervisor|trainer|student)
├── full_name
├── phone
├── status (active|inactive|suspended)
└── created_at

roles (إن وجد)
├── id (PK)
├── name
└── permissions (JSON)
```

### جداول الأكاديميات

```sql
courses
├── id (PK)
├── title
├── description
├── trainer_id (FK → users.id)
├── category
├── level (beginner|intermediate|advanced)
├── duration_hours
├── price
├── max_students
├── start_date
├── end_date
├── status (active|completed|cancelled)
└── created_at

enrollments
├── id (PK)
├── student_id (FK → users.id)
├── course_id (FK → courses.id)
├── enrollment_date
├── status (active|completed|dropped)
├── payment_status (paid|partial|pending)
├── amount_paid
├── progress (0-100)
└── grade

schedules
├── id (PK)
├── course_id (FK → courses.id)
├── day_of_week
├── start_time
├── end_time
├── room
├── type (lecture|lab|practical)
└── is_active
```

### جداول الحضور والتقييم

```sql
attendance
├── id (PK)
├── student_id (FK → users.id)
├── course_id (FK → courses.id)
├── date
├── status (present|absent|late|excused)
├── notes
├── recorded_by (FK → users.id)
└── created_at

exams
├── id (PK)
├── course_id (FK → courses.id)
├── title
├── description
├── exam_date
├── duration_minutes
├── total_marks
├── passing_marks
├── type (quiz|midterm|final|project)
├── status (draft|scheduled|ongoing|completed)
├── created_by (FK → users.id)
└── created_at

exam_grades
├── id (PK)
├── exam_id (FK → exams.id)
├── student_id (FK → users.id)
├── score
├── feedback
├── graded_by (FK → users.id)
└── graded_at

assignments
├── id (PK)
├── course_id (FK → courses.id)
├── title
├── description
├── due_date
├── max_score
├── attachment
├── created_by (FK → users.id)
└── created_at

assignment_submissions
├── id (PK)
├── assignment_id (FK → assignments.id)
├── student_id (FK → users.id)
├── submission_text
├── attachment
├── submitted_at
├── score
├── feedback
├── graded_by (FK → users.id)
└── graded_at
```

### جداول التواصل

```sql
messages
├── id (PK)
├── sender_id (FK → users.id)
├── receiver_id (FK → users.id)
├── subject
├── message
├── is_read (BOOLEAN)
├── sent_at
└── read_at

notifications
├── id (PK)
├── user_id (FK → users.id)
├── title
├── message
├── type (enrollment|assignment|exam|grade|payment|message|announcement)
├── link
├── is_read (BOOLEAN)
└── created_at

announcements
├── id (PK)
├── title
├── content
├── type (general|academic|event|system)
├── target_audience (all|students|trainers|staff)
├── priority (low|medium|high|urgent)
├── published_by (FK → users.id)
├── published_at
├── expires_at
└── status (draft|published|expired)
```

### جداول المالية

```sql
payments
├── id (PK)
├── user_id (FK → users.id)
├── course_id (FK → courses.id)
├── amount
├── payment_method (cash|bank_transfer|online)
├── transaction_id
├── status (pending|completed|failed|refunded)
├── payment_date
└── notes
```

### جداول إضافية

```sql
student_cards
├── id (PK)
├── student_id (FK → users.id)
├── card_number (UNIQUE)
├── issue_date
├── expiry_date
├── status (active|expired|suspended)
├── photo
├── barcode
├── issued_by (FK → users.id)
└── created_at

certificates
├── id (PK)
├── student_id (FK → users.id)
├── course_id (FK → courses.id)
├── certificate_number (UNIQUE)
├── issue_date
├── grade
├── status (issued|revoked)
├── issued_by (FK → users.id)
└── template_id

course_materials
├── id (PK)
├── course_id (FK → courses.id)
├── title
├── description
├── type (video|pdf|document|link)
├── file_path
├── order_number
├── is_free (BOOLEAN)
├── uploaded_by (FK → users.id)
└── uploaded_at

course_reviews
├── id (PK)
├── course_id (FK → courses.id)
├── student_id (FK → users.id)
├── rating (1-5)
├── review
└── created_at

trainer_notes
├── id (PK)
├── trainer_id (FK → users.id)
├── student_id (FK → users.id)
├── course_id (FK → courses.id)
├── note
├── type (positive|negative|warning|improvement)
└── created_at

activities
├── id (PK)
├── title
├── description
├── type (workshop|seminar|exhibition|competition)
├── start_date
├── end_date
├── location
├── organizer_id (FK → users.id)
├── max_participants
├── status (upcoming|ongoing|completed|cancelled)
└── created_at

support_tickets
├── id (PK)
├── user_id (FK → users.id)
├── subject
├── description
├── priority (low|medium|high|urgent)
├── status (open|in_progress|resolved|closed)
├── category (technical|academic|billing|general)
├── assigned_to (FK → users.id)
├── created_at
└── updated_at
```

---

## 🔄 تدفق البيانات (Data Flow)

### 1. تسجيل الدخول (Authentication)

```
┌─────────┐         ┌──────────┐         ┌──────────┐
│ المستخدم │ ──(1)──>│ التحقق   │ ──(2)──>│ الجلسة   │
│         │         │ من        │         │          │
│         │         │ البيانات  │         │          │
└─────────┘         └──────────┘         └──────────┘
                         │
                         ▼
                    ┌──────────┐
                    │ قاعدة    │
                    │ البيانات │
                    └──────────┘

(1) إرسال username & password
(2) التحقق من الصلاحيات
(3) إنشاء جلسة Session
(4) إعادة توجيه للوحة المناسبة
```

### 2. إرسال رسالة (Messaging)

```
┌─────────┐         ┌──────────┐         ┌──────────┐
│ المرسل  │ ──(1)──>│ حفظ      │ ──(2)──>│ إشعار   │
│         │         │ الرسالة  │         │ فوري    │
└─────────┘         └──────────┘         └──────────┘
                         │                     │
                         ▼                     ▼
                    ┌──────────┐         ┌─────────┐
                    │ قاعدة    │         │ المستلم │
                    │ البيانات │         │         │
                    └──────────┘         └─────────┘

(1) إرسال الرسالة
(2) حفظ في جدول messages
(3) إنشاء إشعار في جدول notifications
(4) إرسال إشعار فوري للمستلم
```

### 3. تسليم واجب (Assignment Submission)

```
┌─────────┐         ┌──────────┐         ┌──────────┐
│ الطالب  │ ──(1)──>│ رفع      │ ──(2)──>│ حفظ      │
│         │         │ الملف    │         │ البيانات │
└─────────┘         └──────────┘         └──────────┘
                                              │
                         ┌────────────────────┤
                         │                    │
                         ▼                    ▼
                    ┌──────────┐         ┌─────────┐
                    │ إشعار   │         │ المدرب  │
                    │ للمدرب  │         │         │
                    └──────────┘         └─────────┘

(1) رفع الواجب والملف المرفق
(2) حفظ في جدول assignment_submissions
(3) إنشاء إشعار للمدرب
(4) المدرب يستقبل التنبيه
```

### 4. تسجيل حضور (Attendance)

```
┌─────────┐         ┌──────────┐         ┌──────────┐
│ المدرب  │ ──(1)──>│ تسجيل    │ ──(2)──>│ تحديث   │
│         │         │ الحضور   │         │ الإحصائيات│
└─────────┘         └──────────┘         └──────────┘
                         │                     │
                         ▼                     ▼
                    ┌──────────┐         ┌─────────┐
                    │ قاعدة    │         │ الطلاب  │
                    │ البيانات │         │ والمشرف │
                    └──────────┘         └─────────┘

(1) تسجيل حضور/غياب الطلاب
(2) حفظ في جدول attendance
(3) تحديث نسبة الحضور في enrollments
(4) إرسال إشعارات للطلاب الغائبين
(5) تحديث الإحصائيات للمشرف
```

---

## 🔐 نظام الصلاحيات (Permissions)

### مصفوفة الصلاحيات

| الوظيفة | المدير | المشرف | المدرب | الطالب |
|---------|--------|--------|--------|--------|
| **إدارة المستخدمين** |
| إضافة مستخدم | ✅ | ❌ | ❌ | ❌ |
| تعديل مستخدم | ✅ | 🟡 | ❌ | ❌ |
| حذف مستخدم | ✅ | ❌ | ❌ | ❌ |
| **إدارة الدورات** |
| إضافة دورة | ✅ | ✅ | ❌ | ❌ |
| تعديل دورة | ✅ | ✅ | 🟡 | ❌ |
| حذف دورة | ✅ | ❌ | ❌ | ❌ |
| **الحضور والغياب** |
| تسجيل حضور | ✅ | ✅ | ✅ | ❌ |
| عرض سجل | ✅ | ✅ | ✅ | 🟡 |
| **التقييم** |
| إضافة واجب | ✅ | ✅ | ✅ | ❌ |
| تقييم واجب | ✅ | 🟡 | ✅ | ❌ |
| رؤية الدرجات | ✅ | ✅ | ✅ | 🟡 |
| **المدفوعات** |
| تسجيل دفع | ✅ | 🟡 | ❌ | ❌ |
| رؤية المدفوعات | ✅ | ✅ | ❌ | 🟡 |
| **الرسائل** |
| إرسال رسالة | ✅ | ✅ | ✅ | ✅ |
| قراءة رسائل الآخرين | ✅ | ❌ | ❌ | ❌ |

**الرموز:**
- ✅ صلاحية كاملة
- 🟡 صلاحية محدودة (فقط البيانات الخاصة به)
- ❌ لا توجد صلاحية

---

## 📱 واجهات المستخدم (User Interfaces)

### 1. لوحة المدير

**المكونات الرئيسية:**
```
┌───────────────────────────────────────────────────┐
│ Header (Logo | Navigation | User Menu)           │
├───────────┬───────────────────────────────────────┤
│ Sidebar   │ Main Content Area                     │
│           │                                       │
│ Dashboard │ ┌─────────────────────────────────┐  │
│ Users     │ │ Dashboard Stats                 │  │
│ Courses   │ │ - Total Students: 5             │  │
│ Payments  │ │ - Total Courses: 5              │  │
│ Reports   │ │ - Total Revenue: 385,000        │  │
│ Messages  │ │ - Active Trainers: 2            │  │
│ Settings  │ └─────────────────────────────────┘  │
│           │                                       │
│           │ ┌─────────────────────────────────┐  │
│           │ │ Recent Activities               │  │
│           │ │ - New enrollment: Student 1     │  │
│           │ │ - Payment received: 50,000      │  │
│           │ └─────────────────────────────────┘  │
└───────────┴───────────────────────────────────────┘
│ Footer                                            │
└───────────────────────────────────────────────────┘
```

### 2. لوحة المدرب

**المكونات الرئيسية:**
```
┌───────────────────────────────────────────────────┐
│ Header                                            │
├───────────┬───────────────────────────────────────┤
│ Sidebar   │ My Courses                            │
│           │                                       │
│ My Courses│ ┌─────────────────────────────────┐  │
│ Students  │ │ PHP Course                      │  │
│ Attendance│ │ Students: 3 | Progress: 45%     │  │
│ Assignments│ └─────────────────────────────────┘  │
│ Exams     │                                       │
│ Materials │ Students List                         │
│ Messages  │ ┌─────────────────────────────────┐  │
│           │ │ Name        | Attendance | Grade│  │
│           │ │ Student 1   | 90%        | A    │  │
│           │ │ Student 2   | 85%        | B+   │  │
│           │ └─────────────────────────────────┘  │
└───────────┴───────────────────────────────────────┘
```

### 3. لوحة الطالب

**المكونات الرئيسية:**
```
┌───────────────────────────────────────────────────┐
│ Header                                            │
├───────────┬───────────────────────────────────────┤
│ Sidebar   │ My Courses                            │
│           │                                       │
│ Dashboard │ ┌─────────────────────────────────┐  │
│ My Courses│ │ PHP Development                 │  │
│ Schedule  │ │ Progress: 45% | Grade: A        │  │
│ Assignments│ └─────────────────────────────────┘  │
│ Exams     │                                       │
│ Grades    │ Upcoming Assignments                  │
│ Messages  │ ┌─────────────────────────────────┐  │
│ Card      │ │ Assignment 1: Due in 3 days     │  │
│           │ │ Assignment 2: Due in 7 days     │  │
│           │ └─────────────────────────────────┘  │
└───────────┴───────────────────────────────────────┘
```

---

## 🔄 سير العمل (Workflows)

### سير عمل التسجيل في دورة

```
[الطالب يتصفح الدورات]
         │
         ▼
[يختار دورة ويضغط "تسجيل"]
         │
         ▼
[التحقق من الشروط]
    │         │
    │         └──> [الشروط غير مستوفاة] ──> [رسالة خطأ]
    │
    ▼
[حفظ في جدول enrollments]
    │
    ├──> [إرسال إشعار للطالب]
    │
    ├──> [إرسال إشعار للمدرب]
    │
    ├──> [إرسال إشعار للمدير]
    │
    └──> [تحديث الإحصائيات]
```

### سير عمل تقييم الواجب

```
[الطالب يسلم الواجب]
         │
         ▼
[حفظ في assignment_submissions]
         │
         ▼
[إشعار للمدرب بالتسليم]
         │
         ▼
[المدرب يراجع الواجب]
         │
         ▼
[المدرب يضع الدرجة والملاحظات]
         │
         ▼
[تحديث السجل في قاعدة البيانات]
         │
         ├──> [إشعار للطالب بالدرجة]
         │
         ├──> [تحديث متوسط الدرجات]
         │
         └──> [تحديث التقدم في الدورة]
```

---

## 🔧 التقنيات المستخدمة

### Backend
- **PHP 7.4+**: اللغة الأساسية
- **MySQL/MariaDB**: قاعدة البيانات
- **PDO**: للتعامل مع قاعدة البيانات
- **Sessions**: إدارة الجلسات
- **JWT** (اختياري): للمصادقة في API

### Frontend
- **HTML5**: البنية
- **CSS3**: التصميم
- **JavaScript**: التفاعل
- **Bootstrap 4/5**: إطار العمل
- **jQuery**: معالجة DOM
- **AJAX**: الاتصال غير المتزامن

### الأمان
- **Password Hashing**: تشفير كلمات المرور
- **CSRF Protection**: الحماية من CSRF
- **XSS Prevention**: منع XSS
- **SQL Injection Prevention**: استخدام Prepared Statements
- **Input Validation**: التحقق من المدخلات

---

## 📈 قابلية التوسع (Scalability)

### الخطوات المستقبلية

1. **تحسين الأداء**
   - Caching (Redis/Memcached)
   - Database Indexing
   - Query Optimization
   - CDN للملفات الثابتة

2. **التوسع الأفقي**
   - Load Balancing
   - Database Replication
   - Microservices Architecture

3. **ميزات إضافية**
   - تطبيق جوال (React Native/Flutter)
   - API RESTful كامل
   - WebSocket للدردشة الفورية
   - نظام الإشعارات Push
   - تكامل مع أنظمة خارجية

---

## 📊 مراقبة الأداء (Performance Monitoring)

### المقاييس الرئيسية

- **زمن الاستجابة**: < 200ms
- **معدل الخطأ**: < 1%
- **التوفر**: > 99.9%
- **الاستخدام الآني**: قابل لـ 100 مستخدم متزامن

### الأدوات

- **Logs**: ملفات السجلات
- **Monitoring**: New Relic / DataDog
- **Analytics**: Google Analytics
- **Error Tracking**: Sentry

---

**A-TEAM @ F.G.M**
**© 2024 Ibdaa Platform**
