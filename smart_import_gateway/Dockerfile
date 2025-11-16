# Smart Ingestion Gateway - Docker Container
# بوابة الاستيراد الذكية - حاوية دوكر

FROM python:3.11-slim

# تعيين مجلد العمل
WORKDIR /app

# نسخ ملف المتطلبات
COPY requirements.txt .

# تثبيت المكتبات
RUN pip install --no-cache-dir -r requirements.txt

# نسخ الكود
COPY main.py .

# فتح المنفذ
EXPOSE 8008

# تشغيل الخادم
CMD ["uvicorn", "main:app", "--host", "0.0.0.0", "--port", "8008", "--reload"]
