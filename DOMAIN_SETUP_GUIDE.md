# 🌐 دليل ربط الدومين مع GitHub Pages

## الخطوة 1️⃣: إعداد GitHub Pages

### 1. فتح إعدادات المستودع:
```
https://github.com/Qailakhursheed/Ibdaa-Platform/settings/pages
```

### 2. تكوين GitHub Pages:
- **Source:** Deploy from a branch
- **Branch:** main
- **Folder:** / (root)
- اضغط **Save**

---

## الخطوة 2️⃣: إضافة الدومين المخصص

### 1. في نفس صفحة GitHub Pages:
- ابحث عن قسم **"Custom domain"**
- أدخل الدومين الخاص بك (مثال: `ibdaa-platform.com`)
- اضغط **Save**

### 2. إنشاء ملف CNAME:

أنشئ ملف `CNAME` في مجلد المشروع:

```bash
# في Terminal
echo "your-domain.com" > CNAME
git add CNAME
git commit -m "Add custom domain"
git push
```

**أو يدوياً:**
- أنشئ ملف اسمه `CNAME` (بدون امتداد)
- اكتب فيه: `your-domain.com`
- احفظه في جذر المشروع

---

## الخطوة 3️⃣: إعداد DNS في Namecheap

### 1. تسجيل الدخول إلى Namecheap:
```
https://www.namecheap.com/myaccount/login/
```

### 2. اذهب إلى Domain List:
- اختر الدومين الخاص بك
- اضغط **Manage**

### 3. اذهب إلى Advanced DNS:

### 4. أضف هذه السجلات:

#### أ) لـ Apex Domain (بدون www):

```
Type: A Record
Host: @
Value: 185.199.108.153
TTL: Automatic

Type: A Record
Host: @
Value: 185.199.109.153
TTL: Automatic

Type: A Record
Host: @
Value: 185.199.110.153
TTL: Automatic

Type: A Record
Host: @
Value: 185.199.111.153
TTL: Automatic
```

#### ب) لـ www Subdomain:

```
Type: CNAME Record
Host: www
Value: qailakhursheed.github.io
TTL: Automatic
```

---

## الخطوة 4️⃣: تفعيل HTTPS

### على GitHub Pages:

1. ارجع إلى:
   ```
   https://github.com/Qailakhursheed/Ibdaa-Platform/settings/pages
   ```

2. في قسم **"Enforce HTTPS"**:
   - ✅ فعّل الخيار
   - انتظر حتى تظهر ✅ بجانبه

**ملاحظة:** قد يستغرق تفعيل HTTPS من 5 دقائق إلى 24 ساعة

---

## 📝 ملف CNAME (مثال):

### إذا كان الدومين: `ibdaa-platform.com`

**محتوى ملف CNAME:**
```
ibdaa-platform.com
```

### إذا أردت استخدام subdomain: `www.ibdaa-platform.com`

**محتوى ملف CNAME:**
```
www.ibdaa-platform.com
```

---

## ⏱️ أوقات الانتظار المتوقعة:

```
GitHub Pages تفعيل:     2-5 دقائق
DNS تحديث:              5-48 ساعة (عادة 1-2 ساعة)
HTTPS شهادة:            5-24 ساعة
```

---

## ✅ التحقق من الإعداد:

### 1. تحقق من DNS:
```bash
nslookup your-domain.com
```

يجب أن يظهر:
```
Address: 185.199.108.153
Address: 185.199.109.153
Address: 185.199.110.153
Address: 185.199.111.153
```

### 2. تحقق من CNAME:
```bash
nslookup www.your-domain.com
```

يجب أن يظهر:
```
canonical name = qailakhursheed.github.io
```

### 3. تحقق من الموقع:
```
https://your-domain.com
https://www.your-domain.com
```

---

## 🔄 خطوات الرفع بعد إضافة CNAME:

```powershell
# 1. إضافة ملف CNAME
git add CNAME

# 2. عمل commit
git commit -m "Add custom domain configuration"

# 3. الرفع إلى GitHub
git push origin main

# 4. انتظر 2-3 دقائق
```

---

## 🐛 حل المشاكل الشائعة:

### المشكلة 1: "Domain's DNS record could not be retrieved"
**الحل:**
- تأكد من إضافة A Records الأربعة
- انتظر ساعة واحدة
- جرّب مرة أخرى

### المشكلة 2: "HTTPS لا يعمل"
**الحل:**
- تأكد من صحة DNS
- انتظر 24 ساعة
- تأكد من تفعيل "Enforce HTTPS"

### المشكلة 3: "404 Error"
**الحل:**
- تأكد من وجود ملف `index.html` أو `README.md` في الجذر
- تأكد من Branch الصحيح (main)
- تأكد من Folder الصحيح (/)

### المشكلة 4: "CNAME already taken"
**الحل:**
- الدومين مستخدم من قبل مستودع آخر
- احذف CNAME من المستودع القديم
- أو استخدم subdomain مختلف

---

## 📦 هيكل المشروع للنشر:

```
Ibdaa-Platform/
├── CNAME                    # ملف الدومين المخصص
├── index.html              # الصفحة الرئيسية (إن وجدت)
├── README.md               # سيعرض كـ index إن لم يوجد index.html
├── Manager/                # لوحات التحكم
├── platform/               # المنصة الأساسية
└── ...                     # باقي الملفات
```

---

## 🎯 خيارات النشر:

### الخيار 1: GitHub Pages مباشرة
- ✅ مجاني تماماً
- ✅ HTTPS تلقائي
- ❌ فقط Static Files
- ❌ لا يدعم PHP

### الخيار 2: Netlify (مستحسن للمشاريع الديناميكية)
- ✅ مجاني
- ✅ يدعم Functions
- ✅ سهل الاستخدام
- ✅ CI/CD تلقائي

### الخيار 3: Vercel
- ✅ مجاني
- ✅ أداء عالي
- ✅ تكامل سهل مع GitHub

### الخيار 4: استضافة PHP كاملة
- استضافة مدفوعة تدعم PHP + MySQL
- مثل: Hostinger, Bluehost, SiteGround

---

## 💡 توصيات للمشروع الحالي:

**نظراً لأن المشروع يستخدم PHP + MySQL:**

### الحل الأمثل: استضافة PHP

**1. استضافات مجانية (للتجربة):**
- InfinityFree
- 000webhost
- AwardSpace

**2. استضافات مدفوعة (للإنتاج):**
- Hostinger (3-5$/شهر)
- Namecheap Hosting (2-4$/شهر)
- SiteGround (7-15$/شهر)

**3. VPS (للمشاريع الكبيرة):**
- DigitalOcean (5$/شهر)
- Linode (5$/شهر)
- Vultr (5$/شهر)

---

## 📞 خطوات إضافية بعد الربط:

### 1. تحديث الروابط في الكود:

```php
// في includes/config.php
'app' => [
    'url' => 'https://your-domain.com'
]
```

### 2. تحديث قاعدة البيانات:

```sql
UPDATE settings 
SET value = 'https://your-domain.com' 
WHERE key = 'site_url';
```

### 3. تحديث .env:

```env
APP_URL=https://your-domain.com
```

---

## ✅ قائمة التحقق النهائية:

```
[ ] تم تفعيل GitHub Pages
[ ] تم إضافة ملف CNAME
[ ] تم رفع CNAME إلى GitHub
[ ] تم إضافة A Records في Namecheap
[ ] تم إضافة CNAME Record في Namecheap
[ ] تم تفعيل HTTPS
[ ] تم اختبار الدومين (http + https)
[ ] تم اختبار www.domain
[ ] تم تحديث روابط المشروع
```

---

## 🎉 بعد النجاح:

موقعك سيكون متاح على:
```
https://your-domain.com
https://www.your-domain.com
```

**مدة الانتظار الإجمالية: 1-24 ساعة**

---

**📧 للدعم:**
- Namecheap Support: https://www.namecheap.com/support/
- GitHub Pages Docs: https://docs.github.com/pages
