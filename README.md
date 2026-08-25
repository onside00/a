# Sports Live - PHP/MySQL

موقع عربي RTL لعرض المباريات وروابط التحويل المخفية خلف `go.php?id=...`.

## المتطلبات
- Ubuntu 22.04/24.04 أو ما شابه
- Apache 2.4+
- PHP 7.4+ (يفضل 8.1+)
- PHP extensions: mysqli, mbstring, fileinfo
- MariaDB/MySQL

## بيانات الدخول الافتراضية
- لوحة الإدارة: `/admin.php`
- كلمة المرور: `Admin@2026#Secure`

غيّر كلمة المرور بعد أول تشغيل.

## التثبيت السريع

### 1) تثبيت الحزم
```bash
sudo apt update
sudo apt install -y apache2 mariadb-server php php-mysql php-mbstring
sudo a2enmod rewrite headers
sudo systemctl restart apache2
```

### 2) نسخ الملفات
ضع محتويات المشروع داخل:
```bash
/var/www/html
```

### 3) إنشاء قاعدة البيانات والمستخدم
```bash
sudo mysql
```

ثم داخل MySQL:
```sql
CREATE DATABASE sports_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'sports_user'@'localhost' IDENTIFIED BY 'PUT_A_STRONG_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON sports_live.* TO 'sports_user'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 4) استيراد الجداول
```bash
mysql -u sports_user -p sports_live < /var/www/html/database.sql
```

### 5) تعديل config.php
غيّر:
- `DB_NAME`
- `DB_USER`
- `DB_PASS`

أو استخدم متغيرات البيئة:
- SPORTS_DB_HOST
- SPORTS_DB_NAME
- SPORTS_DB_USER
- SPORTS_DB_PASS
- SPORTS_ADMIN_PASSWORD_HASH

### 6) الصلاحيات
```bash
sudo chown -R www-data:www-data /var/www/html
sudo find /var/www/html -type d -exec chmod 755 {} \;
sudo find /var/www/html -type f -exec chmod 644 {} \;
sudo chmod 755 /var/www/html/uploads/teams
```

### 7) السماح بـ .htaccess
في إعداد VirtualHost الخاص بالدومين تأكد من:
```apache
<Directory /var/www/html>
    AllowOverride All
    Require all granted
</Directory>
```

ثم:
```bash
sudo apachectl configtest
sudo systemctl reload apache2
```

## تغيير كلمة مرور الإدارة
أنشئ hash جديد:
```bash
php -r "echo password_hash('YOUR_NEW_PASSWORD', PASSWORD_DEFAULT), PHP_EOL;"
```

انسخ الناتج وضعه مكان `ADMIN_PASSWORD_HASH` في `config.php`.

## ملاحظات
- التحويل يستخدم HTTP 302.
- الروابط الحقيقية لا تظهر داخل HTML للصفحة الرئيسية.
- أي مباراة مضى عليها أكثر من 24 ساعة تختفي من الواجهة العامة.
- الصور المرفوعة تقبل PNG فقط وبحد أقصى 2MB.
- مجلد uploads محمي من تنفيذ PHP.
