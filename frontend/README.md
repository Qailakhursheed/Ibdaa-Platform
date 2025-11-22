# Frontend - منصة إبداع

## 🚀 نظرة عامة

واجهة أمامية حديثة لمنصة إبداع مبنية باستخدام Vue 3 + Vite + Pinia + Tailwind CSS

### التقنيات المستخدمة
- **Vue 3** (Composition API)
- **Vite** (Build Tool)
- **Pinia** (State Management)
- **Vue Router** (Routing)
- **Tailwind CSS** (Styling)
- **Axios** (HTTP Client)
- **Vitest** (Testing)

---

## 🛠️ التثبيت والتشغيل

```bash
# تثبيت التبعيات
npm install

# تشغيل Development Server
npm run dev
# سيعمل على: http://localhost:5173/

# Build للإنتاج
npm run build

# تشغيل الاختبارات
npm test

# تغطية الاختبارات
npm run coverage
```

---

## 📁 هيكل المشروع

```
src/
├── api/                    # API clients (Axios)
├── components/             # Vue components
│   ├── common/            # Reusable components
│   └── layout/            # Layout components
├── composables/           # Composable functions (useWebSocket)
├── router/                # Vue Router configuration
├── stores/                # Pinia stores (auth, students, courses)
├── views/                 # Page components
└── utils/                 # Utility functions
```

---

## 🎯 الميزات

✅ نظام مصادقة متكامل  
✅ إدارة الطلاب (CRUD + Filters + Pagination)  
✅ إدارة الدورات  
✅ إشعارات فورية (WebSocket)  
✅ واجهة responsive  
✅ دعم RTL (العربية)  
✅ اختبارات آلية (Vitest)  
✅ CI/CD (GitHub Actions)

---

## 🔧 التكوين

قم بإنشاء ملف `.env`:

```env
VITE_API_BASE_URL=http://localhost/Ibdaa-Taiz
VITE_WS_URL=ws://localhost:8080
```

---

## 📝 المزيد

للحصول على التوثيق الكامل، راجع [MODERNIZATION_ROADMAP.md](../MODERNIZATION_ROADMAP.md)
