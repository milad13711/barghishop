<?php

namespace App\Providers;

use App\Services\Payment\PaymentManager;
use App\Services\Pricing\PriceResolver;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Sms\SmsManager;
use App\Support\Jalali;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;

class ShopServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(PriceResolver::class);
        $this->app->singleton(ShippingCalculator::class);
        $this->app->singleton(PaymentManager::class);
        $this->app->singleton(SmsManager::class);
    }

    public function boot(): void
    {
        // @jd($date)        → ۱۴۰۵/۰۶/۱۱
        // @jdt($date)       → ۱۴۰۵/۰۶/۱۱ ۱۴:۳۰
        // @jdlong($date)    → ۱۱ شهریور ۱۴۰۵
        Blade::directive('jd', fn ($expr) => "<?php echo \App\Support\Jalali::format($expr); ?>");
        // در متن راست‌به‌چپ، «تاریخ ساعت» دو دنباله عددی جدا هستند و مرورگر
        // ترتیبشان را برعکس نشان می‌دهد؛ با dir=ltr ایزوله می‌شوند.
        Blade::directive('jdt', fn ($expr) => "<?php echo '<span dir=\"ltr\" class=\"inline-block\">'"
            ." . \App\Support\Jalali::format($expr, 'Y/m/d H:i') . '</span>'; ?>");
        Blade::directive('jdlong', fn ($expr) => "<?php echo \App\Support\Jalali::long($expr); ?>");

        // پیام‌های نسبی Carbon («۳ روز پیش») به فارسی
        \Carbon\Carbon::setLocale('fa');
    }
}
