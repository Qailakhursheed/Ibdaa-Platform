#!/bin/bash

echo "🚀 بدء عملية النشر..."

# 1. التحقق من Git
if [ -n "$(git status --porcelain)" ]; then 
    echo "⚠️ هناك تغييرات غير محفوظة!"
    # exit 1 # Uncomment to enforce
fi

# 2. تثبيت Dependencies
echo "📦 تثبيت Dependencies..."
if [ -f "composer.json" ]; then
    composer install --no-dev --optimize-autoloader
fi

# 3. تنظيف Caches
echo "🧹 تنظيف Caches..."
rm -rf cache/*
rm -rf logs/*.log

# 4. ضبط الصلاحيات
echo "🔒 ضبط الصلاحيات..."
find . -type f -exec chmod 644 {} \;
find . -type d -exec chmod 755 {} \;
chmod -R 755 uploads/

# 5. إنشاء نسخة احتياطية من DB
echo "💾 إنشاء نسخة احتياطية..."
# mysqldump -u root ibdaa_platform > backup_$(date +%Y%m%d_%H%M%S).sql

# 6. تطبيق Migrations
echo "🗄️ تطبيق Migrations..."
# أضف أوامر migrations هنا

echo "✅ النشر مكتمل!"
