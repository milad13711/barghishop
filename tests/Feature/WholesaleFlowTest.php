<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\PriceTier;
use App\Models\Product;
use App\Services\Pricing\PriceResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WholesaleFlowTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        config()->set('shop.sms.default', 'log');

        $this->customer = Customer::create([
            'mobile'        => '09121110000',
            'price_tier_id' => PriceTier::retail()->id,
            'is_active'     => true,
        ]);

        $this->actingAs($this->customer, 'customer');
    }

    protected function submitRequest(): void
    {
        $this->post(route('account.wholesale.store'), [
            'name'          => 'علی رضایی',
            'company'       => 'برق کاران تهران',
            'price_tier_id' => PriceTier::where('code', 'wholesale_1')->value('id'),
        ]);
    }

    public function test_request_is_recorded_as_pending(): void
    {
        $this->submitRequest();

        $this->customer->refresh();

        $this->assertSame(Customer::WHOLESALE_PENDING, $this->customer->wholesale_status);
        $this->assertNotNull($this->customer->wholesale_requested_at);
    }

    public function test_pending_request_does_not_unlock_wholesale_prices(): void
    {
        $this->submitRequest();

        $product = Product::published()->firstOrFail();
        $price = app(PriceResolver::class)->for($product, $this->customer->refresh());

        $this->assertSame(PriceTier::RETAIL, $price->tier->code);
    }

    public function test_approval_command_unlocks_wholesale_prices(): void
    {
        $this->submitRequest();

        $this->artisan('shop:wholesale', ['mobile' => '09121110000'])->assertSuccessful();

        $this->customer->refresh();

        $this->assertTrue($this->customer->isWholesaler());

        $product = Product::published()->firstOrFail();
        $resolver = app(PriceResolver::class);

        $wholesale = $resolver->for($product, $this->customer);
        $retail    = $resolver->retailFor($product);

        $this->assertSame('wholesale_1', $wholesale->tier->code);
        $this->assertLessThan($retail->amount, $wholesale->amount);
    }

    public function test_rejection_keeps_retail_pricing(): void
    {
        $this->submitRequest();

        $this->artisan('shop:wholesale', ['mobile' => '09121110000', '--reject' => true])->assertSuccessful();

        $this->assertFalse($this->customer->refresh()->isWholesaler());
    }

    public function test_retail_tier_cannot_be_requested(): void
    {
        $this->post(route('account.wholesale.store'), [
            'name'          => 'علی رضایی',
            'company'       => 'تست',
            'price_tier_id' => PriceTier::retail()->id,
        ])->assertStatus(422);
    }
}
