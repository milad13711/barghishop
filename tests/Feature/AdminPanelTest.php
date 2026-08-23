<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\User;
use App\Services\Pricing\PriceResolver;
use App\Support\Money;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        config()->set('shop.sms.default', 'log');

        $this->admin = User::firstOrFail();
    }

    public function test_admin_area_requires_authentication(): void
    {
        $this->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
        $this->get(route('admin.products.index'))->assertRedirect(route('admin.login'));
    }

    public function test_customer_session_cannot_reach_admin(): void
    {
        $customer = Customer::create([
            'mobile' => '09121110000', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('admin.dashboard'))
            ->assertRedirect(route('admin.login'));
    }

    public function test_admin_pages_render(): void
    {
        $this->actingAs($this->admin);

        foreach ([
            route('admin.dashboard'), route('admin.orders.index'), route('admin.customers.index'),
            route('admin.products.index'), route('admin.products.create'), route('admin.categories.index'),
            route('admin.brands.index'), route('admin.posts.index'), route('admin.settings.index'),
        ] as $url) {
            $this->get($url)->assertOk();
        }

        $this->get(route('admin.products.edit', Product::first()))->assertOk();
    }

    public function test_creating_a_product_converts_toman_input_to_rial(): void
    {
        $this->actingAs($this->admin);

        $retailTier = PriceTier::retail();

        $this->post(route('admin.products.store'), [
            'name'   => 'آیفون تصویری آزمایشی',
            'sku'    => 'TEST-ADMIN-1',
            'status' => 'published',
            'stock'  => 10,
            'specs'  => [['key' => 'اندازه نمایشگر', 'value' => '۷ اینچ', 'is_filterable' => 1]],
            'prices' => [$retailTier->id => [['min_qty' => 1, 'amount' => '2,500,000', 'compare_at' => '2800000']]],
        ])->assertRedirect();

        $product = Product::where('sku', 'TEST-ADMIN-1')->firstOrFail();

        $price = app(PriceResolver::class)->for($product);

        // ۲٬۵۰۰٬۰۰۰ تومان = ۲۵٬۰۰۰٬۰۰۰ ریال
        $this->assertSame(25_000_000, $price->amount);
        $this->assertSame(28_000_000, $price->compareAt);
        $this->assertSame('2,500,000', number_format(Money::toToman($price->amount)));
        $this->assertSame('۷ اینچ', $product->specs->first()->value);
        $this->assertNotNull($product->published_at);
    }

    public function test_product_slug_is_generated_in_persian(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.products.store'), [
            'name' => 'پنل کدینگ آزمایشی', 'sku' => 'TEST-ADMIN-2', 'status' => 'draft',
        ]);

        $this->assertSame('پنل-کدینگ-آزمایشی', Product::where('sku', 'TEST-ADMIN-2')->value('slug'));
    }

    public function test_order_status_transition_is_logged(): void
    {
        $this->actingAs($this->admin);

        $customer = Customer::create([
            'mobile' => '09121110000', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $order = Order::create([
            'code' => Order::generateCode(), 'customer_id' => $customer->id,
            'status' => Order::PENDING_PAYMENT, 'grand_total' => 1_000_000,
        ]);

        $this->post(route('admin.orders.status', $order), [
            'status' => Order::PROCESSING,
            'note'   => 'شروع بسته‌بندی',
        ])->assertRedirect();

        $order->refresh();

        $this->assertSame(Order::PROCESSING, $order->status);
        $this->assertSame('شروع بسته‌بندی', $order->statusLogs()->first()->note);
    }

    public function test_illegal_status_transition_is_rejected(): void
    {
        $this->actingAs($this->admin);

        $order = Order::create([
            'code' => Order::generateCode(), 'status' => Order::PENDING_PAYMENT, 'grand_total' => 1000,
        ]);

        // از «در انتظار پرداخت» نمی‌توان مستقیم به «تحویل‌شده» رفت
        $this->post(route('admin.orders.status', $order), ['status' => Order::DELIVERED])
            ->assertSessionHasErrors('status');

        $this->assertSame(Order::PENDING_PAYMENT, $order->refresh()->status);
    }

    public function test_approving_wholesale_from_panel_unlocks_prices(): void
    {
        $this->actingAs($this->admin);

        $tier = PriceTier::where('code', 'wholesale_1')->firstOrFail();

        $customer = Customer::create([
            'mobile' => '09121110000', 'price_tier_id' => $tier->id,
            'wholesale_status' => Customer::WHOLESALE_PENDING, 'is_active' => true,
        ]);

        $this->patch(route('admin.customers.update', $customer), [
            'price_tier_id'    => $tier->id,
            'wholesale_status' => Customer::WHOLESALE_APPROVED,
            'is_active'        => 1,
        ])->assertRedirect();

        $this->assertTrue($customer->refresh()->isWholesaler());
        $this->assertSame('wholesale_1',
            app(PriceResolver::class)->for(Product::published()->first(), $customer)->tier->code);
    }

    public function test_settings_can_be_updated(): void
    {
        $this->actingAs($this->admin);

        $this->post(route('admin.settings.update'), [
            'settings' => ['support_phone' => '021-88887777'],
        ])->assertRedirect();

        $this->assertSame('021-88887777', \App\Models\Setting::get('support_phone'));
    }
}
