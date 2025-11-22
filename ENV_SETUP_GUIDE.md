# 🔐 إعداد ملف البيئة - Environment Setup Guide

## 📋 الخطوات السريعة / Quick Steps

### 1️⃣ إنشاء ملف `.env`

```bash
# انسخ ملف المثال
cp .env.example .env

# أو في Windows PowerShell
Copy-Item .env.example .env
```

### 2️⃣ تعديل القيم في `.env`

افتح ملف `.env` وعدّل القيم التالية:

```env
# قاعدة البيانات
DB_HOST=localhost
DB_NAME=ibdaa_platform
DB_USER=root
DB_PASS=your-database-password

# بريد SMTP (Gmail مثلاً)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-app-password
SMTP_FROM_EMAIL=your-email@gmail.com
SMTP_FROM_NAME=منصة إبداع

# Gemini AI
GEMINI_API_KEY=your-gemini-api-key
```

---

## 🔑 الحصول على كلمة مرور تطبيق Gmail

### الطريقة:

1. اذهب إلى: https://myaccount.google.com/apppasswords
2. قم بتسجيل الدخول إلى حساب Gmail الخاص بك
3. اختر "تطبيق" → "آخر (اسم مخصص)"
4. اكتب اسم التطبيق: "Ibdaa Platform"
5. اضغط "إنشاء"
6. انسخ كلمة المرور المكونة من 16 حرف
7. ضعها في `SMTP_PASS` في ملف `.env`

### مثال:
```env
SMTP_PASS=abcd efgh ijkl mnop
```

**ملاحظة:** قد تحتاج إلى تفعيل "التحقق بخطوتين" في حساب Gmail أولاً.

---

## 🤖 الحصول على Gemini API Key

### الطريقة:

1. اذهب إلى: https://makersuite.google.com/app/apikey
2. قم بتسجيل الدخول بحساب Google الخاص بك
3. اضغط "Create API Key"
4. اختر مشروع أو أنشئ مشروع جديد
5. انسخ المفتاح
6. ضعه في `GEMINI_API_KEY` في ملف `.env`

### مثال:
```env
GEMINI_API_KEY=AIzaSyABC123DEF456GHI789JKL012MNO345PQR
```

---

## ✅ التحقق من الإعدادات

### اختبار البريد الإلكتروني:

قم بإنشاء ملف اختبار `test_email.php`:

```php
<?php
require_once 'includes/email_sender.php';
require_once 'database/db.php';

$emailSender = new EmailSender($conn);

$result = $emailSender->sendEmail(
    'test@example.com',
    'اختبار النظام',
    'هذه رسالة اختبار من منصة إبداع'
);

echo $result ? "✅ تم الإرسال بنجاح" : "❌ فشل الإرسال";
```

### اختبار Gemini AI:

```php
<?php
require_once 'includes/config.php';

echo "Gemini API Key: " . (GEMINI_API_KEY ? "✅ موجود" : "❌ غير موجود");
```

---

## 🔒 الأمان - Security

### ⚠️ **مهم جداً:**

1. **لا ترفع ملف `.env` إلى Git أبداً**
   ```bash
   # تأكد من وجود .env في .gitignore
   cat .gitignore | grep .env
   ```

2. **استخدم قيم مختلفة للإنتاج**
   - أنشئ ملف `.env.production` للسيرفر
   - استخدم كلمات مرور قوية
   - فعّل HTTPS في الإنتاج

3. **صلاحيات الملف**
   ```bash
   # Linux/Mac
   chmod 600 .env
   
   # يمنع الوصول من المستخدمين الآخرين
   ```

---

## 📝 بيئات متعددة

### Development (التطوير):
```env
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost/Ibdaa-Taiz
```

### Production (الإنتاج):
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com
```

### Testing (الاختبار):
```env
APP_ENV=testing
APP_DEBUG=true
APP_URL=http://test.localhost
DB_NAME=ibdaa_platform_test
```

---

## 🆘 استكشاف الأخطاء

### المشكلة: "Failed to load .env file"
**الحل:** تأكد من وجود ملف `.env` في المجلد الرئيسي

### المشكلة: "SMTP Error: Could not authenticate"
**الحل:** 
1. تأكد من صحة `SMTP_USER` و `SMTP_PASS`
2. تأكد من تفعيل "التحقق بخطوتين" في Gmail
3. أنشئ كلمة مرور تطبيق جديدة

### المشكلة: "Gemini API request failed"
**الحل:**
1. تأكد من صحة `GEMINI_API_KEY`
2. تأكد من تفعيل Gemini API في Google Cloud Console
3. تحقق من Quota والحدود

---

## 📚 المراجع

- [Gmail App Passwords](https://support.google.com/accounts/answer/185833)
- [Google AI Studio](https://makersuite.google.com/)
- [PHPMailer Documentation](https://github.com/PHPMailer/PHPMailer)

---

**✅ بعد إكمال هذه الخطوات، سيكون النظام جاهزاً للعمل!**
