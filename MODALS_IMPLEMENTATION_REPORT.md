# 🎨 تقرير بناء النوافذ المنبثقة (Modals)
**منصة إبداع للتدريب - Ibdaa Training Platform**

تاريخ التحديث: 6 نوفمبر 2025

---

## 📌 ملخص التحديثات

تم بناء النوافذ المنبثقة (Modals) الكاملة للإضافة والتعديل للمدربين والدورات، مع ربطها بملفات API.

---

## ✅ المرحلة 1: بناء Modal الدورة (courseModal)

### 1️⃣ HTML Modal
📁 **موقع**: `Manager/dashboard.php` → قبل `<script>`

**المميزات**:
- ✅ نموذج كامل بجميع الحقول المطلوبة
- ✅ حقل مخفي `course_id` للتمييز بين الإضافة والتعديل
- ✅ قائمة منسدلة للمدربين (يتم ملؤها ديناميكياً)
- ✅ حقول التاريخ (start_date, end_date)
- ✅ حالة الدورة (active, inactive, completed)
- ✅ مساحة رسائل الأخطاء/النجاح

**الحقول الرئيسية**:
```html
<input type="hidden" id="course_id" name="course_id">
<input type="text" id="course_title" name="title" required>
<input type="text" id="course_slug" name="slug">
<select id="course_category" name="category" required>
<select id="course_trainer" name="trainer_id">  <!-- يتم ملؤها من API -->
<input type="text" id="course_duration" name="duration">
<input type="number" id="course_max_students" name="max_students">
<input type="date" id="course_start_date" name="start_date">
<input type="date" id="course_end_date" name="end_date">
<input type="number" id="course_fees" name="fees">
<select id="course_status" name="status">
<textarea id="course_short_desc" name="short_desc">
<textarea id="course_full_desc" name="full_desc">
```

---

### 2️⃣ JavaScript Logic

#### تحميل المدربين:
```javascript
let trainersListData = [];

const loadTrainers = async () => {
    const res = await fetch('api/manage_users.php?role=trainer');
    const data = await res.json();
    if (data.success) {
        trainersListData = data.data || [];
        populateTrainerSelect();
    }
};

const populateTrainerSelect = () => {
    const trainerSelect = document.getElementById('course_trainer');
    trainersListData.forEach(trainer => {
        const option = document.createElement('option');
        option.value = trainer.id;
        option.textContent = trainer.full_name;
        trainerSelect.appendChild(option);
    });
};

// تحميل المدربين عند بدء التشغيل
loadTrainers();
```

#### فتح Modal (إضافة أو تعديل):
```javascript
window.openCourseModal = async (course = null) => {
    courseForm.reset();
    courseMessageBox.classList.add('hidden');
    
    if (course) {
        // وضع التعديل - ملء جميع الحقول
        courseModalTitle.textContent = 'تعديل بيانات الدورة';
        document.getElementById('course_id').value = course.course_id || '';
        document.getElementById('course_title').value = course.title || '';
        document.getElementById('course_slug').value = course.slug || '';
        document.getElementById('course_category').value = course.category || '';
        document.getElementById('course_trainer').value = course.trainer_id || '';
        // ... باقي الحقول
    } else {
        // وضع الإضافة - حقول فارغة
        courseModalTitle.textContent = 'إضافة دورة جديدة';
        document.getElementById('course_id').value = '';
    }
    
    courseModal.classList.add('visible');
    lucide.createIcons();
};
```

#### حفظ النموذج:
```javascript
courseForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const courseId = document.getElementById('course_id').value;
    const action = courseId ? 'update' : 'create';
    
    const formData = {
        action: action,
        title: document.getElementById('course_title').value,
        slug: document.getElementById('course_slug').value,
        category: document.getElementById('course_category').value,
        trainer_id: document.getElementById('course_trainer').value || null,
        duration: document.getElementById('course_duration').value,
        max_students: document.getElementById('course_max_students').value,
        start_date: document.getElementById('course_start_date').value || null,
        end_date: document.getElementById('course_end_date').value || null,
        fees: document.getElementById('course_fees').value,
        status: document.getElementById('course_status').value,
        short_desc: document.getElementById('course_short_desc').value,
        full_desc: document.getElementById('course_full_desc').value
    };
    
    if (courseId) {
        formData.course_id = parseInt(courseId);
    }
    
    const res = await fetch('api/manage_courses.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    });
    
    const data = await res.json();
    if (data.success) {
        // عرض رسالة نجاح وإعادة تحميل الصفحة
        setTimeout(() => {
            closeCourseModal();
            location.reload();
        }, 1500);
    }
});
```

---

### 3️⃣ تحديث الأزرار

#### في renderCourses():
```javascript
// زر "إضافة دورة جديدة"
<button id="addCourseBtn" ...>
    <i data-lucide="plus" ...></i>
    <span>إضافة دورة جديدة</span>
</button>

// أزرار التحكم في كل بطاقة
<button onclick="editCourse(${course.course_id})" ...>
    <i data-lucide="edit" ...></i>
</button>
<button onclick="deleteCourse(${course.course_id})" ...>
    <i data-lucide="trash-2" ...></i>
</button>

// ربط الزر
const addCourseBtn = document.getElementById('addCourseBtn');
addCourseBtn.addEventListener('click', () => {
    openCourseModal();  // null = وضع الإضافة
});
```

#### دالة editCourse() المحدثة:
```javascript
window.editCourse = async (courseId) => {
    // جلب بيانات الدورة من API
    const res = await fetch('api/manage_courses.php?status=all');
    const data = await res.json();
    
    if (data.success && data.data) {
        const course = data.data.find(c => c.course_id == courseId);
        if (course) {
            openCourseModal(course);  // تمرير بيانات الدورة
        }
    }
};
```

---

## ✅ المرحلة 2: بناء Modal المدرب (trainerModal)

### 1️⃣ HTML Modal
📁 **موقع**: `Manager/dashboard.php` → بعد courseModal

**المميزات**:
- ✅ نموذج بسيط للبيانات الأساسية
- ✅ حقل كلمة المرور يظهر فقط في وضع الإضافة
- ✅ حقول المحافظة والمديرية
- ✅ التحقق من صحة البريد الإلكتروني

**الحقول الرئيسية**:
```html
<input type="hidden" id="trainer_user_id" name="user_id">
<input type="text" id="trainer_full_name" name="full_name" required>
<input type="email" id="trainer_email" name="email" required>
<input type="tel" id="trainer_phone" name="phone">
<div id="trainerPasswordField">  <!-- يُخفى في وضع التعديل -->
    <input type="password" id="trainer_password" name="password">
</div>
<select id="trainer_governorate" name="governorate">
<input type="text" id="trainer_district" name="district">
```

---

### 2️⃣ JavaScript Logic

#### فتح Modal (إضافة أو تعديل):
```javascript
window.openTrainerModal = (trainer = null) => {
    trainerForm.reset();
    trainerMessageBox.classList.add('hidden');
    
    if (trainer) {
        // وضع التعديل
        trainerModalTitle.textContent = 'تعديل بيانات المدرب';
        document.getElementById('trainer_user_id').value = trainer.id || '';
        document.getElementById('trainer_full_name').value = trainer.full_name || '';
        document.getElementById('trainer_email').value = trainer.email || '';
        document.getElementById('trainer_phone').value = trainer.phone || '';
        document.getElementById('trainer_governorate').value = trainer.governorate || '';
        document.getElementById('trainer_district').value = trainer.district || '';
        
        // إخفاء حقل كلمة المرور
        trainerPasswordField.style.display = 'none';
        document.getElementById('trainer_password').removeAttribute('required');
    } else {
        // وضع الإضافة
        trainerModalTitle.textContent = 'إضافة مدرب جديد';
        document.getElementById('trainer_user_id').value = '';
        
        // إظهار حقل كلمة المرور
        trainerPasswordField.style.display = 'block';
        document.getElementById('trainer_password').setAttribute('required', 'required');
    }
    
    trainerModal.classList.add('visible');
    lucide.createIcons();
};
```

#### حفظ النموذج:
```javascript
trainerForm.addEventListener('submit', async (e) => {
    e.preventDefault();
    
    const userId = document.getElementById('trainer_user_id').value;
    const action = userId ? 'update' : 'create';
    
    const formData = {
        action: action,
        full_name: document.getElementById('trainer_full_name').value,
        email: document.getElementById('trainer_email').value,
        phone: document.getElementById('trainer_phone').value,
        role: 'trainer',
        governorate: document.getElementById('trainer_governorate').value,
        district: document.getElementById('trainer_district').value
    };
    
    if (userId) {
        formData.user_id = parseInt(userId);
    } else {
        // إضافة كلمة المرور فقط في وضع الإنشاء
        formData.password = document.getElementById('trainer_password').value;
    }
    
    const res = await fetch('api/manage_users.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(formData)
    });
    
    const data = await res.json();
    if (data.success) {
        setTimeout(() => {
            closeTrainerModal();
            location.reload();
        }, 1500);
    }
});
```

---

### 3️⃣ تحديث الأزرار

#### في renderTrainers():
```javascript
// زر "إضافة مدرب جديد"
<button id="addTrainerBtn" ...>
    <i data-lucide="plus" ...></i>
    <span>إضافة مدرب جديد</span>
</button>

// أزرار التحكم في كل بطاقة
<button onclick="editUser(${trainer.id}, 'trainer')" ...>
    <i data-lucide="edit" ...></i>
</button>
<button onclick="deleteUser(${trainer.id}, 'trainer')" ...>
    <i data-lucide="trash-2" ...></i>
</button>

// ربط الزر
const addTrainerBtn = document.getElementById('addTrainerBtn');
addTrainerBtn.addEventListener('click', () => {
    openTrainerModal();  // null = وضع الإضافة
});
```

#### دالة editUser() المحدثة:
```javascript
window.editUser = async (userId, userType) => {
    // جلب بيانات المستخدم من API
    const res = await fetch(`api/manage_users.php?role=${userType}`);
    const data = await res.json();
    
    if (data.success && data.data) {
        const user = data.data.find(u => u.id == userId);
        if (user) {
            if (userType === 'trainer') {
                openTrainerModal(user);  // تمرير بيانات المدرب
            } else if (userType === 'student') {
                // TODO: فتح modal الطالب
                alert('تعديل بيانات الطالب - قريباً');
            }
        }
    }
};
```

---

## ✅ المرحلة 3: تحديث traineeModal

### الحالة الحالية:
- ✅ Modal موجود بالفعل ويعمل للإضافة
- ⏳ يحتاج لتطوير دالة `editUser` للطلاب لتفعيل التعديل

### التحديث المطلوب (TODO):
```javascript
// في دالة editUser
if (userType === 'student') {
    // جلب بيانات الطالب
    const student = data.data.find(u => u.id == userId);
    
    // ملء حقول traineeModal
    document.getElementById('nameAr').value = student.full_name || '';
    document.getElementById('nameEn').value = student.name_en || '';
    document.getElementById('dob').value = student.dob || '';
    document.getElementById('phone').value = student.phone || '';
    // ... باقي الحقول
    
    // فتح Modal
    traineeModal.classList.add('visible');
}
```

---

## 📊 التدفق الكامل

### سيناريو 1: إضافة دورة جديدة
1. المستخدم يضغط "إضافة دورة جديدة"
2. يتم استدعاء `openCourseModal(null)`
3. Modal يفتح بحقول فارغة
4. المستخدم يملأ البيانات ويضغط "حفظ"
5. يتم إرسال `POST` إلى `api/manage_courses.php` مع `action=create`
6. عند النجاح: رسالة نجاح → إعادة تحميل الصفحة
7. الدورة الجديدة تظهر في القائمة

### سيناريو 2: تعديل دورة موجودة
1. المستخدم يضغط ✏️ بجانب دورة
2. يتم استدعاء `editCourse(courseId)`
3. جلب بيانات الدورة من `api/manage_courses.php`
4. يتم استدعاء `openCourseModal(course)` مع بيانات الدورة
5. Modal يفتح بحقول مملوءة مسبقاً
6. المستخدم يعدل البيانات ويضغط "حفظ"
7. يتم إرسال `POST` إلى `api/manage_courses.php` مع `action=update` و `course_id`
8. عند النجاح: رسالة نجاح → إعادة تحميل الصفحة

### سيناريو 3: إضافة مدرب جديد
1. المستخدم يضغط "إضافة مدرب جديد"
2. يتم استدعاء `openTrainerModal(null)`
3. Modal يفتح بحقول فارغة + حقل كلمة المرور ظاهر
4. المستخدم يملأ البيانات ويضغط "حفظ"
5. يتم إرسال `POST` إلى `api/manage_users.php` مع `action=create` و `role=trainer`
6. عند النجاح: رسالة نجاح → إعادة تحميل الصفحة

### سيناريو 4: تعديل مدرب موجود
1. المستخدم يضغط ✏️ في بطاقة مدرب
2. يتم استدعاء `editUser(trainerId, 'trainer')`
3. جلب بيانات المدرب من `api/manage_users.php?role=trainer`
4. يتم استدعاء `openTrainerModal(trainer)` مع بيانات المدرب
5. Modal يفتح بحقول مملوءة + حقل كلمة المرور مخفي
6. المستخدم يعدل البيانات ويضغط "حفظ"
7. يتم إرسال `POST` إلى `api/manage_users.php` مع `action=update` و `user_id`
8. عند النجاح: رسالة نجاح → إعادة تحميل الصفحة

---

## 🧪 الاختبار

### اختبار إضافة دورة:
1. افتح `Manager/dashboard.php`
2. اذهب إلى "البرامج والدورات"
3. اضغط "إضافة دورة جديدة"
4. املأ البيانات:
   - العنوان: "دورة Python المتقدمة"
   - التصنيف: "برمجة"
   - المدرب: اختر من القائمة
   - المدة: "شهرين"
5. اضغط "حفظ الدورة"
6. يجب أن تظهر رسالة "تم إضافة الدورة بنجاح"
7. تحديث تلقائي للصفحة
8. الدورة الجديدة تظهر في القائمة

### اختبار تعديل دورة:
1. اضغط ✏️ بجانب أي دورة
2. Modal يفتح بالبيانات الحالية
3. عدّل العنوان أو المدرب
4. اضغط "حفظ الدورة"
5. رسالة نجاح → تحديث الصفحة
6. التعديلات تظهر

### اختبار إضافة مدرب:
1. اذهب إلى "إدارة المدربين"
2. اضغط "إضافة مدرب جديد"
3. املأ البيانات + كلمة المرور
4. اضغط "حفظ المدرب"
5. رسالة نجاح → المدرب الجديد يظهر

### اختبار تعديل مدرب:
1. اضغط ✏️ في بطاقة مدرب
2. Modal يفتح بالبيانات (بدون حقل كلمة المرور)
3. عدّل الاسم أو الهاتف
4. اضغط "حفظ المدرب"
5. رسالة نجاح → التعديلات تظهر

---

## 🐛 استكشاف الأخطاء

### المشكلة 1: قائمة المدربين فارغة في Modal الدورة
**السبب**: لم يتم تحميل المدربين من API
**الحل**: 
- تحقق من Console: هل هناك خطأ في `loadTrainers()`؟
- تحقق من `api/manage_users.php?role=trainer` في المتصفح
- تأكد من أن `loadTrainers()` يتم استدعاؤها عند بدء التشغيل

### المشكلة 2: حقل كلمة المرور يظهر في وضع التعديل
**السبب**: لم يتم إخفاؤه صحيحاً
**الحل**: تحقق من:
```javascript
trainerPasswordField.style.display = 'none';
```

### المشكلة 3: البيانات لا تُحفظ
**السبب**: خطأ في API أو في البيانات المُرسلة
**الحل**:
- افتح Console → Network Tab
- تحقق من الطلب POST
- تحقق من استجابة API
- تأكد من أن جميع الحقول المطلوبة مملوءة

### المشكلة 4: Modal لا يفتح عند الضغط على "تعديل"
**السبب**: خطأ في دالة `editCourse` أو `editUser`
**الحل**:
- افتح Console وابحث عن أخطاء JavaScript
- تحقق من أن `course_id` موجود في البيانات
- تحقق من استجابة API

---

## 📁 الملفات المعنية

```
Manager/
  └── dashboard.php
      ├── HTML Modals:
      │   ├── courseModal           ✅ جديد
      │   ├── trainerModal          ✅ جديد
      │   └── traineeModal          ✅ موجود مسبقاً
      │
      ├── JavaScript:
      │   ├── loadTrainers()        ✅ جديد
      │   ├── populateTrainerSelect() ✅ جديد
      │   ├── openCourseModal()     ✅ جديد
      │   ├── openTrainerModal()    ✅ جديد
      │   ├── editCourse() محدثة   ✅
      │   ├── editUser() محدثة     ✅
      │   └── courseForm/trainerForm submit ✅
      │
      └── PHP:
          └── coursesData[] محدثة   ✅ (أضيف course_id, trainer_id)
```

---

## ✅ نهاية التقرير

**الحالة**: ✅ المرحلة 1 والمرحلة 2 مكتملتان بالكامل
**الوظائف**: ✅ إضافة وتعديل الدورات والمدربين تعمل بنجاح
**المتبقي**: ⏳ تطوير تعديل الطلاب (traineeModal)

**التاريخ**: 6 نوفمبر 2025
**الإصدار**: 2.2.0

---

© 2024 منصة إبداع للتدريب | Ibdaa Training Platform
