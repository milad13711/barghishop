# استقرار برقی‌شاپ روی سرور

## پیش‌نیازها

- Ubuntu 22.04 یا بالاتر
- PHP 8.2+ با افزونه‌های: `mbstring gd zip xml curl intl bcmath mysql`
- MySQL 8 (یا MariaDB 10.6+)
- Composer، Node.js 20+، Nginx

```bash
sudo apt update
sudo apt install -y nginx mysql-server php8.3-fpm php8.3-mysql php8.3-mbstring \
     php8.3-xml php8.3-curl php8.3-gd php8.3-zip php8.3-intl php8.3-bcmath \
     composer nodejs npm git
```

## اولین استقرار

```bash
cd /var/www
sudo git clone git@github.com:milad13711/barghishop.git
sudo chown -R www-data:www-data barghishop
cd barghishop

composer install --no-dev --optimize-autoloader
npm ci && npm run build

cp .env.example .env
php artisan key:generate
```

### تنظیم `.env` روی سرور

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://barghishop.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=barghishop
DB_USERNAME=barghishop
DB_PASSWORD=«رمز قوی»

# درگاه واقعی (خروج از سندباکس)
ZARINPAL_MERCHANT_ID=«مرچنت کد واقعی»
ZARINPAL_SANDBOX=false

# پنل پیامکی واقعی
SMS_DRIVER=limo
LIMO_SMS_API_KEY=«کلید پنل»
LIMO_SMS_SENDER=«شماره خط»
SMS_PATTERN_OTP=«کد پترن»
SMS_PATTERN_ORDER_PLACED=«کد پترن»
SMS_PATTERN_PAYMENT_OK=«کد پترن»
SMS_PATTERN_SHIPPED=«کد پترن»
SMS_PATTERN_WHOLESALE_OK=«کد پترن»
```

> **مهم:** تا وقتی `SMS_DRIVER=log` باشد هیچ پیامک واقعی ارسال نمی‌شود
> و کد ورود فقط در `storage/logs` می‌افتد — یعنی مشتری نمی‌تواند وارد شود.

### ساخت دیتابیس و اجرای مهاجرت‌ها

```bash
sudo mysql -e "CREATE DATABASE barghishop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
sudo mysql -e "CREATE USER 'barghishop'@'localhost' IDENTIFIED BY 'رمز قوی';"
sudo mysql -e "GRANT ALL ON barghishop.* TO 'barghishop'@'localhost'; FLUSH PRIVILEGES;"

php artisan migrate --force
php artisan db:seed --class=Database\\Seeders\\PriceTierSeeder --force
php artisan db:seed --class=Database\\Seeders\\LoyaltyLevelSeeder --force
php artisan db:seed --class=Database\\Seeders\\ProvinceSeeder --force
php artisan db:seed --class=Database\\Seeders\\ShippingSeeder --force
php artisan db:seed --class=Database\\Seeders\\SettingSeeder --force

php artisan storage:link
```

> `CatalogSeeder` و `BlogSeeder` را روی سرور اجرا **نکنید** — داده نمونه‌اند.
> کاتالوگ واقعی را از پنل ← محصولات ← ایمپورت CSV بارگذاری کنید.

### ساخت کاربر مدیر

```bash
php artisan tinker --execute="\App\Models\User::create([
    'name' => 'مدیر',
    'email' => 'you@barghishop.com',
    'password' => 'یک رمز قوی',
]);"
```

## Nginx

```nginx
server {
    listen 80;
    server_name barghishop.com www.barghishop.com;
    root /var/www/barghishop/public;

    index index.php;
    charset utf-8;
    client_max_body_size 20M;

    location / { try_files $uri $uri/ /index.php?$query_string; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.3-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* { deny all; }
}
```

```bash
sudo ln -s /etc/nginx/sites-available/barghishop /etc/nginx/sites-enabled/
sudo nginx -t && sudo systemctl reload nginx

# HTTPS
sudo apt install -y certbot python3-certbot-nginx
sudo certbot --nginx -d barghishop.com -d www.barghishop.com
```

## کِرون و صف

```bash
# crontab -e -u www-data
* * * * * cd /var/www/barghishop && php artisan schedule:run >> /dev/null 2>&1
```

## استقرار نسخه‌های بعدی

```bash
cd /var/www/barghishop
php artisan down

git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

php artisan up
```

## چک‌لیست پیش از انتشار

- [ ] `APP_DEBUG=false` و `APP_ENV=production`
- [ ] رمز کاربر ادمین پیش‌فرض عوض شده باشد
- [ ] `ZARINPAL_SANDBOX=false` با مرچنت کد واقعی
- [ ] `SMS_DRIVER=limo` و کدهای پترن پر شده باشند
- [ ] HTTPS فعال و ریدایرکت `http → https`
- [ ] نماد اعتماد و ساماندهی در پنل ← تنظیمات ثبت شده باشد
- [ ] کاتالوگ واقعی جایگزین داده نمونه شده باشد
- [ ] `sitemap.xml` در سرچ کنسول گوگل ثبت شده باشد
- [ ] بکاپ روزانه دیتابیس تنظیم شده باشد
