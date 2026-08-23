# barghishop.com — سند معماری و پرامپت اجرایی (نسخه ۱.۰)

## ۰. تصمیمات قفل‌شده
| موضوع | تصمیم |
|---|---|
| استک | Laravel 11 + Filament 3 (پنل مدیریت) + Blade/Livewire + Tailwind (RTL) |
| DB | MySQL 8 |
| درگاه | زرین‌پال (پشت اینترفیس `PaymentGateway`) |
| پیامک | لیمو اس‌ام‌اس / اکسیرپیامک (پشت اینترفیس `SmsProvider`) |
| دیتای اولیه | ایمپورت CSV/Excel + ۱۰ محصول نمونه سیماران در Seeder |
| دامنه | barghishop.com — فارسی، RTL، فونت ایران‌سنس/وزیرمتن |

## ۱. دامنه کسب‌وکار (Bounded Contexts)
1. **Catalog** — برند، دسته، محصول، متغیر، مشخصات فنی، گالری، فایل (کاتالوگ PDF)
2. **Pricing** — قیمت خرده / قیمت عمده (چند سطحی)، تخفیف، موجودی
3. **Ordering** — سبد، تسویه، سفارش، وضعیت‌ها، مرسوله/رهگیری پستی
4. **Payment** — تراکنش، وریفای، بازگشت وجه (درایور زرین‌پال)
5. **CRM/Loyalty** — مشتری، نقش (خرده/عمده)، امتیاز، سطح باشگاه، کیف پول
6. **Content/SEO** — مقاله، دسته مقاله، متا، اسکیما، سایت‌مپ
7. **Notification** — پیامک/ایمیل، قالب پیام، صف
8. **Admin** — کاربران پنل، نقش/دسترسی، تنظیمات

## ۲. مدل داده (خلاصه جداول کلیدی)
```
brands(id, name, slug, logo, description, meta)
categories(id, parent_id, name, slug, icon, description, seo_*)
products(id, brand_id, category_id, sku, name, slug, short_desc, body,
         status, is_featured, warranty_months, seo_title, seo_desc, schema_json)
product_variants(id, product_id, sku, attributes_json, stock, weight)
product_specs(id, product_id, group, key, value, sort)     ← جدول مشخصات فنی
product_media(id, product_id, path, type, sort, alt)

prices(id, priceable_type, priceable_id, tier_id, amount, compare_at, min_qty)
price_tiers(id, code, name)   ← retail | wholesale_1 | wholesale_2 ...
   ▸ قیمت عمده = ردیف جدا در prices، نه ستون جدا. با min_qty پلکانی می‌شود.

customers(id, mobile, name, email, tier_id, wallet_balance, points, level_id,
          company, national_id, is_wholesale_approved)
addresses(id, customer_id, province, city, postal_code, line, receiver, phone)

carts / cart_items
orders(id, code, customer_id, tier_id, status, subtotal, discount, shipping,
       tax, total, address_snapshot_json, tracking_code, note)
order_items(id, order_id, product_id, variant_id, name_snapshot, unit_price, qty)
order_status_logs(id, order_id, from, to, by, note, created_at)

transactions(id, order_id, gateway, authority, ref_id, amount, status, raw_json)

loyalty_levels(id, name, min_points, discount_percent, benefits_json)
loyalty_ledger(id, customer_id, points, type, reason, order_id)
coupons(id, code, type, value, min_total, tier_scope, usage_limit, expires_at)

posts(id, category_id, title, slug, excerpt, body, cover, status, published_at,
      seo_title, seo_desc, reading_time, author_id)
post_categories, tags, taggables

sms_logs, settings(key, value, group)
```

## ۳. قانون قیمت‌گذاری (مهم‌ترین منطق اختصاصی)
- هر مشتری یک `tier_id` دارد: پیش‌فرض `retail`.
- عمده‌فروش با فرم درخواست + تأیید ادمین به `wholesale_*` ارتقا می‌یابد.
- `PriceResolver::for($product, $customer, $qty)` تنها نقطه محاسبه قیمت است؛
  هیچ‌جای دیگر قیمت مستقیم خوانده نشود.
- قیمت عمده برای مهمان/خرده هرگز در HTML رندر نمی‌شود (نه مخفی با CSS).
- گزینه ادمین: «نمایش قیمت فقط پس از ورود» برای دسته/محصول خاص.

## ۴. نقشه صفحات (فرانت)
```
/                     خانه: هیرو، دسته‌ها، پرفروش‌ها، برند سیماران، مقالات، اعتماد
/category/{slug}      لیست + فیلتر (برند، قیمت، مشخصات، موجودی) + مرتب‌سازی
/product/{slug}       گالری، قیمت پویا، مشخصات فنی تب‌بندی، نقد/پرسش، مشابه‌ها
/search               جستجوی فارسی (نرمال‌سازی ی/ک، Scout)
/cart  /checkout      تسویه تک‌صفحه‌ای، آدرس، روش ارسال، درگاه
/blog  /blog/{slug}   مقالات سئویی + فهرست مطالب + اسکیما Article
/wholesale            صفحه ثبت‌نام همکار/عمده
/account/*            داشبورد مشتری
/sitemap.xml /robots.txt /feed
```

### داشبورد مشتری `/account`
سفارش‌ها و پیگیری وضعیت • جزئیات و فاکتور • آدرس‌ها • باشگاه مشتریان (امتیاز، سطح، تاریخچه) • کیف پول • علاقه‌مندی‌ها • تیکت/پیام • پروفایل

### پنل ادمین (Filament)
داشبورد آمار • محصولات (+ایمپورت CSV، مشخصات فنی repeater، گالری) • دسته/برند • قیمت‌ها و سطوح • سفارش‌ها (تغییر وضعیت + پیامک خودکار + کد رهگیری) • مشتریان و تأیید عمده‌فروش • باشگاه مشتریان و کوپن • مقالات و SEO • تنظیمات درگاه/پیامک/ارسال • لاگ پیامک و تراکنش • نقش و دسترسی

## ۵. لایه‌های قابل‌تعویض
```php
interface PaymentGateway { request(Order $o): RedirectResponse; verify(Request $r): Transaction; }
class ZarinpalGateway implements PaymentGateway {}

interface SmsProvider { send(string $mobile, string $text): SmsResult;
                        pattern(string $mobile, string $code, array $params): SmsResult; }
class LimoSmsProvider implements SmsProvider {}
```
انتخاب درایور از `config/shop.php` + جدول `settings`.

**رویدادهای پیامکی:** OTP ورود، ثبت سفارش، تأیید پرداخت، ارسال + کد رهگیری،
تأیید عمده‌فروشی، امتیاز/سطح جدید، سبد رهاشده.

## ۶. احراز هویت
ورود مشتری با **موبایل + OTP** (اصلی) و رمز اختیاری. ادمین: ایمیل+رمز، جدا از مشتری (guard جداگانه).

## ۷. SEO — الزامات غیرقابل‌مذاکره
- SSR کامل، URL فارسی‌فرندلی با slug لاتین، breadcrumb ساختاریافته
- JSON-LD: `Product` + `Offer` + `AggregateRating`, `Article`, `BreadcrumbList`, `Organization`, `FAQPage`
- متا و OG سفارشی هر صفحه از پنل، `canonical`، `hreflang fa-IR`
- سایت‌مپ داینامیک ایندکس‌دار، تصاویر WebP + lazy + ابعاد ثابت (CLS=0)
- هدف Lighthouse ≥ ۹۰ موبایل

## ۸. زبان طراحی
- رنگ پایه: سرمه‌ای عمیق `#0B1F3A` + آبی برقی `#0A84FF` + طلایی گرم `#C9A227` برای اعتماد
- خنثی‌ها: `#F7F8FA` پس‌زمینه، کارت سفید، سایه نرم، شعاع ۱۶px
- فونت: وزیرمتن (وب‌فونت لوکال، subset فارسی)، اعداد فارسی در نمایش
- الگو: هیرو با ویدیو/تصویر آیفون تصویری، نوار اعتماد (گارانتی، ارسال، پشتیبانی، نماد)
- دارک‌مود اختیاری فاز ۲؛ موبایل‌اول، Bottom-nav در موبایل
- میکرو‌اینتراکشن ملایم (hover lift، skeleton loading) — نه انیمیشن سنگین

## ۹. فازبندی اجرا
| فاز | خروجی | تعریف «تمام» |
|---|---|---|
| ۱ | اسکلت پروژه، دیتابیس، مایگریشن‌ها، Seeder با ۱۰ محصول سیماران | `migrate:fresh --seed` بدون خطا |
| ۲ | پنل Filament: محصول/دسته/برند/قیمت + ایمپورت CSV | ادمین می‌تواند محصول کامل بسازد |
| ۳ | فرانت: خانه، دسته، محصول، جستجو | ۱۰ محصول واقعی رندر می‌شوند |
| ۴ | سبد، تسویه، سفارش، زرین‌پال (سندباکس) | سفارش تست تا وریفای موفق |
| ۵ | داشبورد مشتری + OTP + پیامک لیمو | پیگیری سفارش کار می‌کند |
| ۶ | قیمت عمده + ثبت‌نام همکار + تأیید ادمین | دو کاربر، دو قیمت متفاوت |
| ۷ | باشگاه مشتریان، امتیاز، کوپن، کیف پول | امتیاز پس از سفارش ثبت می‌شود |
| ۸ | بلاگ + SEO کامل + سایت‌مپ + اسکیما | Rich Results Test سبز |
| ۹ | بهینه‌سازی، کش، بکاپ، دیپلوی | Lighthouse ≥۹۰ |

## ۱۰. قواعد کدنویسی (برای هر پرامپت بعدی)
1. هیچ منطق قیمتی خارج از `PriceResolver` نوشته نشود.
2. هر عملیات پولی داخل تراکنش DB + قفل ردیف سفارش.
3. تغییر وضعیت سفارش فقط از `OrderStatusService` (لاگ + پیامک خودکار).
4. تمام متن‌های فارسی در `lang/fa/*.php` — هیچ رشته هاردکد در ویو.
5. موبایل نرمال‌شده `09xxxxxxxxx` در یک `MobileCast`.
6. هر مدل عمومی دارای `slug` یکتا + `HasSeo` trait.
7. تست: حداقل Feature test برای checkout، verify، price resolution.
8. commit در پایان هر فاز، پیام فارسی-انگلیسی کوتاه.
