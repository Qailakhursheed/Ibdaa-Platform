# 🚀 رفع المشروع إلى GitHub - خطوة بخطوة

## الخطوة 1️⃣: إنشاء مستودع جديد على GitHub

### عبر الموقع:

1. اذهب إلى: https://github.com/new
2. املأ البيانات:
   ```
   Repository name: Ibdaa-Taiz
   Description: منصة إبداع للتدريب والتأهيل - Ibdaa Training Platform
   Visibility: Public (أو Private حسب رغبتك)
   
   ⚠️ لا تفعّل:
   [ ] Add a README file
   [ ] Add .gitignore
   [ ] Choose a license
   ```
3. اضغط **Create repository**

---

## الخطوة 2️⃣: ربط المشروع بالمستودع

### إذا كان المستودع موجود بالفعل:

```powershell
# إزالة remote القديم
git remote remove origin

# إضافة remote الجديد
git remote add origin https://github.com/Abdullah-Abbas-Dev/Ibdaa-Taiz.git

# التحقق
git remote -v
```

---

## الخطوة 3️⃣: رفع المشروع

```powershell
# رفع إلى main branch
git push -u origin main

# أو إذا كان اسم الفرع master
git branch -M main
git push -u origin main
```

---

## ✅ التحقق من الرفع

بعد الرفع، افتح:
```
https://github.com/Abdullah-Abbas-Dev/Ibdaa-Taiz
```

يجب أن ترى:
- ✅ 505 ملف
- ✅ جميع المجلدات
- ✅ آخر commit

---

## 🔐 في حالة طلب المصادقة

### خيار 1: GitHub CLI (مستحسن)

```powershell
# تثبيت GitHub CLI
winget install --id GitHub.cli

# تسجيل الدخول
gh auth login

# ثم رفع المشروع
git push -u origin main
```

### خيار 2: Personal Access Token

1. اذهب إلى: https://github.com/settings/tokens
2. Generate new token (classic)
3. اختر Scopes:
   - [x] repo
   - [x] workflow
4. انسخ التوكن
5. استخدمه كـ password عند الرفع

---

## 📊 حجم المشروع

```
505 ملف معدل
84,611 سطر مضاف
9,262 سطر محذوف

المجلدات الرئيسية:
- Manager/
- api-v2/ (Laravel)
- frontend/ (Vue 3)
- platform/
- database/
- includes/
```

---

## ⚠️ ملاحظات مهمة

### 1. الملفات المحمية (لن تُرفع):

```
✅ .env (محمي من .gitignore)
✅ includes/config.php (محمي)
✅ vendor/ (محمي)
✅ node_modules/ (محمي)
```

### 2. الملفات المرفوعة:

```
✅ .env.example (آمن للرفع)
✅ includes/config.example.php (آمن)
✅ جميع الكود البرمجي
✅ جميع التوثيق
```

---

## 🔄 الأوامر البديلة

### إذا فشل الرفع:

```powershell
# 1. التحقق من الاتصال
git ls-remote origin

# 2. رفع بالقوة (احذر!)
git push -f origin main

# 3. رفع فرع معين
git push origin HEAD:main
```

---

## 📞 الدعم

إذا واجهت مشكلة:

1. تأكد من إنشاء المستودع على GitHub
2. تأكد من الصلاحيات (Public/Private)
3. تأكد من تسجيل الدخول
4. استخدم GitHub CLI للتسهيل

---

**✅ بعد الرفع الناجح، سيكون المشروع متاح على:**

```
https://github.com/Abdullah-Abbas-Dev/Ibdaa-Taiz
```
