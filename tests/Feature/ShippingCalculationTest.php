<?php

namespace Tests\Feature;

use App\Models\Province;
use App\Models\ShippingMethod;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShippingCalculationTest extends TestCase
{
    use RefreshDatabase;

    protected ShippingCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\ProvinceSeeder::class);
        $this->seed(\Database\Seeders\ShippingSeeder::class);
        $this->calculator = app(ShippingCalculator::class);
    }

    protected function province(string $slug): Province
    {
        return Province::where('slug', $slug)->firstOrFail();
    }

    public function test_flat_method_ignores_weight(): void
    {
        $courier = ShippingMethod::where('code', 'tehran_courier')->first();
        $tehran = $this->province('tehran')->id;

        $light = $this->calculator->quote($courier, 500, 1_000_000, $tehran);
        $heavy = $this->calculator->quote($courier, 15_000, 1_000_000, $tehran);

        $this->assertSame($light->cost, $heavy->cost);
        $this->assertSame(850_000, $light->cost);
    }

    public function test_weight_method_adds_per_kilo_cost(): void
    {
        $post = ShippingMethod::where('code', 'post_pishtaz')->first();
        $tehran = $this->province('tehran')->id;

        // نرخ تهران: پایه ۶۵۰٬۰۰۰ تا ۱ کیلو، هر کیلوی اضافه ۲۰۰٬۰۰۰
        $this->assertSame(650_000,   $this->calculator->quote($post, 1000, 1_000_000, $tehran)->cost);
        $this->assertSame(850_000,   $this->calculator->quote($post, 2000, 1_000_000, $tehran)->cost);
        $this->assertSame(1_050_000, $this->calculator->quote($post, 2100, 1_000_000, $tehran)->cost);
    }

    public function test_remote_province_costs_more_and_takes_longer(): void
    {
        $post = ShippingMethod::where('code', 'post_pishtaz')->first();

        $tehran = $this->calculator->quote($post, 2000, 1_000_000, $this->province('tehran')->id);
        $sistan = $this->calculator->quote($post, 2000, 1_000_000, $this->province('sistan-baluchestan')->id);

        $this->assertGreaterThan($tehran->cost, $sistan->cost);
        $this->assertGreaterThan($tehran->maxDays, $sistan->maxDays);
    }

    public function test_free_shipping_above_threshold(): void
    {
        $post = ShippingMethod::where('code', 'post_pishtaz')->first();
        $tehran = $this->province('tehran')->id;

        $quote = $this->calculator->quote($post, 2000, 50_000_000, $tehran);

        $this->assertTrue($quote->isFree);
        $this->assertSame(0, $quote->cost);
    }

    public function test_tehran_courier_is_not_offered_outside_its_zone(): void
    {
        $codes = $this->calculator
            ->quotes(2000, 1_000_000, $this->province('sistan-baluchestan')->id)
            ->map(fn ($q) => $q->method->code);

        $this->assertNotContains('tehran_courier', $codes);
        $this->assertContains('post_pishtaz', $codes);
    }

    public function test_method_rejects_orders_over_its_weight_limit(): void
    {
        $post = ShippingMethod::where('code', 'post_pishtaz')->first();

        $quote = $this->calculator->quote($post, 40_000, 1_000_000, $this->province('tehran')->id);

        $this->assertFalse($quote->isAvailable());
    }

    public function test_pickup_is_always_free(): void
    {
        $pickup = ShippingMethod::where('code', 'pickup')->first();

        $quote = $this->calculator->quote($pickup, 50_000, 1000, null);

        $this->assertTrue($quote->isFree);
    }
}
