# 🚀 دليل سريع - ربط الدومين

## ✅ ما تم إنجازه:

1. ✅ **رفع المشروع إلى GitHub** - مكتمل
2. ✅ **إنشاء صفحة رئيسية** - `index.html`
3. ✅ **إنشاء ملف CNAME** - جاهز للتخصيص
4. ✅ **دليل شامل** - `DOMAIN_SETUP_GUIDE.md`

---

## 📝 الخطوات المطلوبة منك:

### 1️⃣ **عدّل ملف CNAME:**

افتح ملف `CNAME` وغيّر محتواه إلى دومينك الفعلي:

```
your-actual-domain.com
```

**مثال:**
- إذا كان دومينك: `ibdaa-taiz.com`
- اكتب في CNAME: `ibdaa-taiz.com`

### 2️⃣ **ارفع التعديل:**

```powershell
git add CNAME
git commit -m "Update domain name"
git push
```

---

## 🌐 تفعيل GitHub Pages:

### الخطوات:

1. **افتح:** https://github.com/Qailakhursheed/Ibdaa-Platform/settings/pages

2. **اختر:**
   - Source: **Deploy from a branch**
   - Branch: **main**
   - Folder: **/ (root)**

3. **اضغط Save**

4. **في قسم Custom domain:**
   - أدخل دومينك (نفس ما في CNAME)
   - اضغط Save

5. **فعّل HTTPS:**
   - ✅ Enforce HTTPS

---

## 🔧 إعداد DNS في Namecheap:

### 1. اذهب إلى Namecheap Dashboard:
```
https://www.namecheap.com/myaccount/login/
```

### 2. اختر Domain → Manage → Advanced DNS

### 3. أضف هذه السجلات:

#### A Records (4 سجلات):
```
Type: A Record | Host: @ | Value: 185.199.108.153
Type: A Record | Host: @ | Value: 185.199.109.153
Type: A Record | Host: @ | Value: 185.199.110.153
Type: A Record | Host: @ | Value: 185.199.111.153
```

#### CNAME Record:
```
Type: CNAME | Host: www | Value: qailakhursheed.github.io
```

### 4. احذف أي سجلات قديمة متعارضة

---

## ⏱️ أوقات الانتظار:

- ✅ **GitHub Pages:** 2-5 دقائق
- ⏳ **DNS Updates:** 1-48 ساعة (عادة 1-2 ساعة)
- ⏳ **HTTPS Certificate:** 5-24 ساعة

---

## 🧪 اختبار الموقع:

بعد 1-2 ساعة، جرّب:

```
https://your-domain.com
https://www.your-domain.com
```

يجب أن تظهر الصفحة الرئيسية الجميلة! 🎉

---

## 📊 روابط مهمة:

- **المستودع:** https://github.com/Qailakhursheed/Ibdaa-Platform
- **الإعدادات:** https://github.com/Qailakhursheed/Ibdaa-Platform/settings/pages
- **الصفحة (بعد التفعيل):** https://qailakhursheed.github.io/Ibdaa-Platform/

---

## 🆘 المشاكل الشائعة:

### "Domain not found":
- تأكد من إضافة A Records الأربعة
- انتظر ساعة واحدة

### "404 Not Found":
- تأكد من وجود `index.html`
- تأكد من Branch = main

### "Not Secure":
- انتظر حتى يتم إصدار شهادة HTTPS
- قد يستغرق 24 ساعة

---

## 📞 الدعم:

**الدليل الشامل:** `DOMAIN_SETUP_GUIDE.md`

**موارد مفيدة:**
- GitHub Pages Docs: https://docs.github.com/pages
- Namecheap DNS Guide: https://www.namecheap.com/support/knowledgebase/article.aspx/9837/46/

---

## ✅ قائمة التحقق:

```
[ ] عدّلت ملف CNAME بدومينك الحقيقي
[ ] رفعت التعديل إلى GitHub
[ ] فعّلت GitHub Pages
[ ] أضفت Custom Domain في GitHub
[ ] أضفت A Records في Namecheap (4 سجلات)
[ ] أضفت CNAME Record في Namecheap
[ ] انتظرت 1-2 ساعة
[ ] اختبرت الموقع
[ ] فعّلت HTTPS
```

---

**🎉 بعد إتمام جميع الخطوات، موقعك سيكون جاهزاً على دومينك المخصص!**
