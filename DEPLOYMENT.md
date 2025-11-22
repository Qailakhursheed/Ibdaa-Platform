# 🚀 دليل النشر - منصة إبداع

دليل شامل لنشر منصة إبداع على خوادم الإنتاج

---

## 📋 قائمة المحتويات

1. [متطلبات النشر](#متطلبات-النشر)
2. [إعداد الخادم](#إعداد-الخادم)
3. [نشر المشروع](#نشر-المشروع)
4. [إعدادات الأمان](#إعدادات-الأمان)
5. [المراقبة والصيانة](#المراقبة-والصيانة)
6. [Troubleshooting](#troubleshooting)

---

## ⚙️ متطلبات النشر

### 1. متطلبات الخادم

| المتطلب | الحد الأدنى | الموصى به |
|--------|-----------|---------|
| **CPU** | 1 Core | 2+ Cores |
| **RAM** | 512 MB | 2+ GB |
| **Disk** | 5 GB | 20+ GB |
| **Bandwidth** | 10 Mbps | 100+ Mbps |

### 2. البرمجيات المطلوبة

```bash
PHP >= 7.4
MySQL >= 5.7 أو MariaDB >= 10.3
Apache >= 2.4 مع mod_rewrite
Composer
Git
OpenSSL
```

### 3. شهادة SSL

- شهادة SSL صحيحة من Let's Encrypt أو CA آخر
- يجب تحديثها قبل انتهائها بـ 30 يوم

---

## 🖥️ إعداد الخادم

### الخطوة 1: تحديث النظام

```bash
# Linux (Ubuntu/Debian)
sudo apt update
sudo apt upgrade -y
sudo apt install -y curl wget git unzip

# Linux (CentOS/RHEL)
sudo yum update -y
sudo yum groupinstall -y "Development Tools"
```

### الخطوة 2: تثبيت PHP

```bash
# Ubuntu/Debian
sudo apt install -y php php-cli php-mysql php-curl php-gd php-mbstring php-json php-openssl php-zip

# التحقق من الإصدار
php -v
```

### الخطوة 3: تثبيت MySQL

```bash
# Ubuntu/Debian
sudo apt install -y mysql-server

# CentOS
sudo yum install -y mysql-server

# بدء الخدمة
sudo systemctl start mysql
sudo systemctl enable mysql

# تأمين التثبيت
sudo mysql_secure_installation
```

### الخطوة 4: تثبيت Apache

```bash
# Ubuntu/Debian
sudo apt install -y apache2 apache2-utils

# تفعيل المودولات المطلوبة
sudo a2enmod rewrite
sudo a2enmod ssl
sudo a2enmod headers

# إعادة تشغيل Apache
sudo systemctl restart apache2
```

### الخطوة 5: تثبيت Composer

```bash
# تحميل Composer
curl -sS https://getcomposer.org/installer | php

# نقل إلى المسار العام
sudo mv composer.phar /usr/local/bin/composer

# التحقق
composer --version
```

---

## 📦 نشر المشروع

### الخطوة 1: استنساخ المستودع

```bash
# الذهاب إلى مجلد المشاريع
cd /var/www

# استنساخ المشروع
sudo git clone https://github.com/Ibdaa/Ibdaa-Taiz.git
cd Ibdaa-Taiz

# تعيين الملكية
sudo chown -R www-data:www-data .
```

### الخطوة 2: تثبيت الحزم

```bash
# تثبيت حزم Composer
composer install --no-dev --optimize-autoloader

# تثبيت حزم npm (إذا لزم)
npm install --production
npm run build
```

### الخطوة 3: إعداد المتغيرات البيئية

```bash
# نسخ ملف المثال
cp .env.example .env

# تحرير الملف
sudo nano .env

# قيم مهمة يجب تحديثها:
# DB_HOST=localhost
# DB_NAME=ibdaa_prod
# DB_USER=ibdaa_user
# DB_PASSWORD=strong-password-here
# APP_ENV=production
# APP_DEBUG=false
# JWT_SECRET=your-secret-key
```

### الخطوة 4: إعداد قاعدة البيانات

```bash
# إنشاء قاعدة البيانات
mysql -u root -p -e "CREATE DATABASE ibdaa_prod CHARACTER SET utf8mb4;"

# إنشاء المستخدم
mysql -u root -p -e "
CREATE USER 'ibdaa_user'@'localhost' IDENTIFIED BY 'strong-password';
GRANT ALL PRIVILEGES ON ibdaa_prod.* TO 'ibdaa_user'@'localhost';
FLUSH PRIVILEGES;
"

# استيراد البيانات
mysql -u ibdaa_user -p ibdaa_prod < database/schema.sql
mysql -u ibdaa_user -p ibdaa_prod < database/initial_setup.sql
```

### الخطوة 5: إعداد Apache Virtual Host

```bash
# إنشاء ملف الإعدادات
sudo nano /etc/apache2/sites-available/ibdaa.com.conf

# أضف المحتوى التالي:
```

```apache
<VirtualHost *:80>
    ServerName ibdaa.com
    ServerAlias www.ibdaa.com
    ServerAdmin admin@ibdaa.com
    
    DocumentRoot /var/www/Ibdaa-Taiz/platform
    
    <Directory /var/www/Ibdaa-Taiz/platform>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # تفعيل mod_rewrite
        <IfModule mod_rewrite.c>
            RewriteEngine On
            RewriteBase /
            
            # رفع الطلبات إلى index.php
            RewriteCond %{REQUEST_FILENAME} !-f
            RewriteCond %{REQUEST_FILENAME} !-d
            RewriteRule ^ index.php [QSA,L]
        </IfModule>
    </Directory>
    
    # Redirect HTTP to HTTPS
    RewriteEngine On
    RewriteCond %{HTTPS} off
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
    
    ErrorLog ${APACHE_LOG_DIR}/ibdaa-error.log
    CustomLog ${APACHE_LOG_DIR}/ibdaa-access.log combined
</VirtualHost>
```

```bash
# تفعيل الموقع
sudo a2ensite ibdaa.com.conf

# التحقق من الإعدادات
sudo apache2ctl configtest

# إعادة تشغيل Apache
sudo systemctl reload apache2
```

### الخطوة 6: إعداد SSL مع Let's Encrypt

```bash
# تثبيت Certbot
sudo apt install -y certbot python3-certbot-apache

# إنشاء شهادة SSL
sudo certbot --apache -d ibdaa.com -d www.ibdaa.com

# تفعيل التجديد التلقائي
sudo systemctl enable certbot.timer
sudo systemctl start certbot.timer
```

---

## 🔐 إعدادات الأمان

### 1. تصحيح الأذونات

```bash
# مجلدات التخزين
sudo chmod 755 platform/uploads/
sudo chmod 755 logs/
sudo chmod 755 cache/

# ملفات الإعدادات
sudo chmod 640 .env
sudo chmod 640 includes/config.php

# تعيين الملكية
sudo chown -R www-data:www-data platform/uploads/
sudo chown -R www-data:www-data logs/
sudo chown -R www-data:www-data cache/
```

### 2. إخفاء ملفات حساسة

```apache
# في .htaccess
<FilesMatch "^\.env">
    Deny from all
</FilesMatch>

<FilesMatch "^composer\.(json|lock)">
    Deny from all
</FilesMatch>

<FilesMatch "^package\.(json|lock)">
    Deny from all
</FilesMatch>

<Directory ~/\.git>
    Deny from all
</Directory>
```

### 3. رؤوس الأمان

```apache
# في Apache config
<Directory /var/www/Ibdaa-Taiz/platform>
    # منع الوصول إلى الملفات الخطرة
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    
    # تفعيل HSTS
    Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
</Directory>
```

### 4. إعدادات PHP للأمان

```ini
# في php.ini
display_errors = Off
log_errors = On
error_log = /var/log/php-errors.log
expose_php = Off

# الحد من الملفات المرفوعة
post_max_size = 5M
upload_max_filesize = 5M

# تعطيل الدوال الخطرة
disable_functions = exec,passthru,shell_exec,system,proc_open,popen

# تأمين Sessions
session.use_strict_mode = 1
session.cookie_secure = 1
session.cookie_httponly = 1
session.cookie_samesite = "Strict"
```

---

## 📊 المراقبة والصيانة

### 1. نسخ احتياطي منتظمة

```bash
# إنشاء سكريبت النسخ الاحتياطية
cat > /usr/local/bin/backup-ibdaa.sh << 'EOF'
#!/bin/bash

BACKUP_DIR="/backups/ibdaa"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

# نسخ قاعدة البيانات
mysqldump -u ibdaa_user -p$DB_PASSWORD ibdaa_prod > $BACKUP_DIR/db_$TIMESTAMP.sql

# ضغط الملفات الرئيسية
tar -czf $BACKUP_DIR/files_$TIMESTAMP.tar.gz /var/www/Ibdaa-Taiz

# حذف النسخ القديمة (أكثر من 30 يوم)
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete

echo "Backup completed: $TIMESTAMP"
EOF

# جعل السكريبت قابل للتنفيذ
sudo chmod +x /usr/local/bin/backup-ibdaa.sh

# إضافة مهمة cron يومية
echo "0 2 * * * /usr/local/bin/backup-ibdaa.sh" | sudo crontab -
```

### 2. مراقبة الأداء

```bash
# تثبيت Monit
sudo apt install -y monit

# إعداد المراقبة
sudo nano /etc/monit/monitrc

# إضافة المراقبة للخدمات
# قم بإعادة تشغيل Monit
sudo systemctl restart monit
```

### 3. تسجيل الأخطاء

```bash
# التحقق من ملفات السجلات
tail -f /var/log/apache2/ibdaa-error.log
tail -f /var/www/Ibdaa-Taiz/logs/app.log
tail -f /var/log/php-errors.log
```

### 4. تحديثات الأمان

```bash
# التحقق من التحديثات
sudo apt list --upgradable

# تثبيت التحديثات
sudo apt upgrade -y

# التحقق من تحديثات Composer
composer outdated

# تحديث الحزم
composer update
```

---

## 🐛 Troubleshooting

### المشكلة: Permission Denied

```bash
# الحل:
sudo chown -R www-data:www-data /var/www/Ibdaa-Taiz
sudo chmod -R 755 /var/www/Ibdaa-Taiz
sudo chmod -R 777 /var/www/Ibdaa-Taiz/platform/uploads/
```

### المشكلة: Database Connection Error

```bash
# التحقق من الاتصال
mysql -u ibdaa_user -p -h localhost ibdaa_prod -e "SELECT 1"

# التحقق من خدمة MySQL
sudo systemctl status mysql

# إعادة تشغيل MySQL
sudo systemctl restart mysql
```

### المشكلة: 500 Internal Server Error

```bash
# التحقق من ملفات السجل
tail -50 /var/log/apache2/ibdaa-error.log

# التحقق من صلاحيات الملفات
ls -la /var/www/Ibdaa-Taiz/

# التحقق من إصدار PHP
php -v
```

### المشكلة: SSL Certificate Error

```bash
# تجديد الشهادة
sudo certbot renew --force-renewal

# التحقق من تاريخ انتهاء الصلاحية
sudo certbot certificates
```

---

## ✅ قائمة التحقق قبل الإطلاق

- [ ] تم تحديث متطلبات النظام
- [ ] تم تثبيت PHP وجميع الملحقات
- [ ] تم تثبيت MySQL وإنشاء قاعدة البيانات
- [ ] تم تثبيت Composer والحزم
- [ ] تم إعداد ملف .env بشكل صحيح
- [ ] تم تفعيل SSL/TLS
- [ ] تم ضبط الأذونات
- [ ] تم إعداد النسخ الاحتياطية
- [ ] تم اختبار الموقع بالكامل
- [ ] تم إعداد المراقبة والتنبيهات
- [ ] تم توثيق عملية الإصلاح السريع

---

## 📞 الدعم والمساعدة

- 📧 support@ibdaa.com
- 🐦 @IbdaaTraining
- 💬 chat.ibdaa.com

---

**آخر تحديث: 21 نوفمبر 2025**
