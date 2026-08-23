# برقی‌شاپ — barghishop.com

فروشگاه اینترنتی تجهیزات برق ساختمان، نماینده فروش برند **سیماران**.

## استک

| لایه | فناوری |
|---|---|
| بک‌اند | Laravel 12 (PHP 8.2+) |
| پنل مدیریت | Filament |
| فرانت | Blade + Tailwind (RTL) |
| دیتابیس | SQLite در توسعه، MySQL 8 در تولید |
| درگاه | زرین‌پال (پشت اینترفیس `PaymentGateway`) |
| پیامک | لیمو اس‌ام‌اس / اکسیرپیامک (پشت اینترفیس `SmsProvider`) |

سند کامل معماری: [docs/SPEC.md](docs/SPEC.md)

## راه‌اندازی محلی

```bash
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan serve
```

- پنل مدیریت: `/admin` — `admin@barghishop.com` / `password`
- در محیط توسعه `SMS_DRIVER=log` است؛ پیامک واقعی ارسال نمی‌شود و در `storage/logs` ثبت می‌گردد.
- `ZARINPAL_SANDBOX=true` تا زمانی که Merchant ID واقعی گرفته شود.

## قواعد کدنویسی (رعایت اینها الزامی است)

1. **قیمت** فقط از `App\Services\Pricing\PriceResolver` — هیچ‌جا مستقیم از جدول `prices` نخوانید.
2. **تغییر وضعیت سفارش** فقط از `App\Services\Orders\OrderStatusService` — لاگ، پیامک و موجودی خودکار انجام می‌شوند.
3. **مبالغ** همیشه به **ریال** و **عدد صحیح** ذخیره می‌شوند. نمایش با `App\Support\Money`.
4. **شماره موبایل** با کست `App\Support\Casts\Mobile` نرمال می‌شود (`09xxxxxxxxx`).
5. **پیامک** فقط از `App\Services\Sms\SmsManager` تا در `sms_logs` ثبت شود.
6. متن‌های فارسی در `lang/fa/` — رشته هاردکد در ویو ننویسید.
7. هر مدل عمومی `HasSlug` و `HasSeo` می‌گیرد.

## داده نمونه

`CatalogSeeder` ده محصول نمونه سیماران می‌سازد. **نام مدل‌ها، مشخصات فنی و قیمت‌ها آزمایشی‌اند**
و باید پیش از راه‌اندازی واقعی با کاتالوگ رسمی و لیست قیمت روز جایگزین شوند
(پنل مدیریت ← محصولات ← ایمپورت اکسل/CSV).

## تست

```bash
php artisan test
```
