<?php

namespace App\Providers;

use App\Services\Payment\PaymentManager;
use App\Services\Pricing\PriceResolver;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Sms\SmsManager;
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
        //
    }
}
