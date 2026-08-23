<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceResolutionTest extends TestCase
{
    use RefreshDatabase;

    protected PriceResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\PriceTierSeeder::class);
        $this->resolver = app(PriceResolver::class);
    }

    protected function product(array $prices = [], array $attributes = []): Product
    {
        $product = Product::create(array_merge([
            'sku'    => 'TEST-'.uniqid(),
            'name'   => 'محصول آزمایشی',
            'status' => Product::PUBLISHED,
        ], $attributes));

        foreach ($prices as [$tierCode, $minQty, $amount]) {
            Price::create([
                'priceable_type' => $product->getMorphClass(),
                'priceable_id'   => $product->id,
                'price_tier_id'  => PriceTier::where('code', $tierCode)->value('id'),
                'min_qty'        => $minQty,
                'amount'         => $amount,
            ]);
        }

        return $product->fresh();
    }

    public function test_guest_gets_retail_price(): void
    {
        $product = $this->product([['retail', 1, 1_000_000]]);

        $this->assertSame(1_000_000, $this->resolver->for($product)->amount);
    }

    public function test_wholesale_tiers_step_down_with_quantity(): void
    {
        $product = $this->product([
            ['retail', 1, 1_000_000],
            ['wholesale_1', 1, 900_000],
            ['wholesale_1', 10, 800_000],
        ]);

        $customer = Customer::create([
            'mobile'           => '09120000001',
            'price_tier_id'    => PriceTier::where('code', 'wholesale_1')->value('id'),
            'wholesale_status' => Customer::WHOLESALE_APPROVED,
        ]);

        $this->assertSame(900_000, $this->resolver->for($product, $customer, 1)->amount);
        $this->assertSame(900_000, $this->resolver->for($product, $customer, 9)->amount);
        $this->assertSame(800_000, $this->resolver->for($product, $customer, 10)->amount);
        $this->assertSame(800_000, $this->resolver->for($product, $customer, 50)->amount);
    }

    public function test_unapproved_wholesaler_falls_back_to_retail(): void
    {
        $product = $this->product([
            ['retail', 1, 1_000_000],
            ['wholesale_1', 1, 900_000],
        ]);

        $customer = Customer::create([
            'mobile'           => '09120000002',
            'price_tier_id'    => PriceTier::where('code', 'wholesale_1')->value('id'),
            'wholesale_status' => Customer::WHOLESALE_PENDING,
        ]);

        $resolved = $this->resolver->for($product, $customer);

        $this->assertSame(1_000_000, $resolved->amount);
        $this->assertSame(PriceTier::RETAIL, $resolved->tier->code);
    }

    public function test_tier_without_explicit_price_uses_fallback_discount(): void
    {
        // wholesale_1 در سیدر ۱۲٪ تخفیف پیش‌فرض دارد
        $product = $this->product([['retail', 1, 1_000_000]]);

        $customer = Customer::create([
            'mobile'           => '09120000003',
            'price_tier_id'    => PriceTier::where('code', 'wholesale_1')->value('id'),
            'wholesale_status' => Customer::WHOLESALE_APPROVED,
        ]);

        $this->assertSame(880_000, $this->resolver->for($product, $customer)->amount);
    }

    public function test_price_is_hidden_from_guests_when_product_requires_login(): void
    {
        $product = $this->product(
            [['retail', 1, 1_000_000]],
            ['prices_require_login' => true],
        );

        $this->assertTrue($this->resolver->for($product)->hidden);
    }

    public function test_price_is_hidden_when_category_requires_login(): void
    {
        $category = Category::create(['name' => 'دسته محرمانه', 'prices_require_login' => true]);

        $product = $this->product(
            [['retail', 1, 1_000_000]],
            ['category_id' => $category->id],
        );

        $this->assertTrue($this->resolver->for($product->fresh())->hidden);
    }
}
