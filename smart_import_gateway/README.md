# Smart Import Gateway - Quick Start Guide
# دليل البدء السريع

## 🚀 طريقة التشغيل

### الطريقة 1: تشغيل مباشر (بدون Docker)

```bash
# 1. التأكد من تثبيت Python 3.11+
python --version

# 2. الانتقال إلى مجلد البوابة
cd smart_import_gateway

# 3. تثبيت المكتبات
pip install -r requirements.txt

# 4. تشغيل الخادم
python main.py
```

الخادم الآن يعمل على: http://localhost:8008

### الطريقة 2: تشغيل باستخدام Docker

```bash
# 1. بناء الصورة
cd smart_import_gateway
docker build -t smart-import-gateway .

# 2. تشغيل الحاوية
docker run -d -p 8008:8008 --name ibdaa-import smart-import-gateway

# 3. عرض السجلات
docker logs -f ibdaa-import
```

### الطريقة 3: باستخدام uvicorn مباشرة

```bash
cd smart_import_gateway
uvicorn main:app --host 0.0.0.0 --port 8008 --reload
```

## 📖 الوثائق التفاعلية

بعد التشغيل، افتح:
- Swagger UI: http://localhost:8008/docs
- ReDoc: http://localhost:8008/redoc

## 🧪 اختبار النقاط النهائية

### 1. التحليل (Analyze)

```bash
curl -X POST "http://localhost:8008/analyze_spreadsheet" \
  -F "file=@sample_grades.csv"
```

### 2. المعالجة (Process)

```json
{
  "file_id": "file_20251109_123456_789012",
  "mapping": [
    {"source_column": "الاسم", "target_field": "student_name"},
    {"source_column": "الدرجة", "target_field": "grade_value"}
  ],
  "skip_empty": true
}
```

## 🔧 إعدادات متقدمة

### تغيير المنفذ

```bash
uvicorn main:app --host 0.0.0.0 --port 8080
```

### تمكين HTTPS

```bash
uvicorn main:app --host 0.0.0.0 --port 8008 --ssl-keyfile key.pem --ssl-certfile cert.pem
```

## 📝 ملاحظات

- الملفات المؤقتة تُحفظ في الذاكرة (في الإنتاج استخدم Redis)
- الخدمة تدعم: Excel (.xlsx, .xls) و CSV
- يمكن التكامل مع أي نظام PHP/Node.js/Python
