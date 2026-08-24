<?php

namespace Tests\Feature;

use App\Models\Coupon;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ShippingMethod;
use App\Models\User;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CouponTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(\Database\Seeders\DatabaseSeeder::class);
        config()->set('shop.sms.default', 'log');

        $this->customer = Customer::create([
            'mobile' => '09121110000', 'price_tier_id' => PriceTier::retail()->id, 'is_active' => true,
        ]);

        $this->product = Product::published()->firstOrFail();

        $this->actingAs($this->customer, 'customer');
        app(CartService::class)->add($this->product, 1);
    }

    public function test_percent_coupon_reduces_the_payable_amount(): void
    {
        Coupon::create(['code' => 'OFF10', 'type' => Coupon::PERCENT, 'value' => 10, 'is_active' => true]);

        $this->post(route('cart.coupon.apply'), ['code' => 'OFF10'])->assertSessionHasNoErrors();

        $summary = app(CartService::class)->summary($this->customer);

        $this->assertSame((int) floor($summary->subtotal() * 0.10), $summary->couponDiscount());
        $this->assertSame($summary->subtotal() - $summary->discountTotal(), $summary->payable());
    }

    public function test_max_discount_caps_a_percent_coupon(): void
    {
        Coupon::create([
            'code' => 'CAP', 'type' => Coupon::PERCENT, 'value' => 50,
            'max_discount' => 1_000_000, 'is_active' => true,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'CAP']);

        $this->assertSame(1_000_000, app(CartService::class)->summary($this->customer)->couponDiscount());
    }

    public function test_coupon_code_is_case_insensitive(): void
    {
        Coupon::create(['code' => 'NOWRUZ', 'type' => Coupon::PERCENT, 'value' => 5, 'is_active' => true]);

        $this->post(route('cart.coupon.apply'), ['code' => 'nowruz'])->assertSessionHasNoErrors();
    }

    public function test_expired_coupon_is_rejected(): void
    {
        Coupon::create([
            'code' => 'OLD', 'type' => Coupon::PERCENT, 'value' => 10,
            'is_active' => true, 'expires_at' => now()->subDay(),
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'OLD'])->assertSessionHasErrors('coupon');
        $this->assertSame(0, app(CartService::class)->summary($this->customer)->couponDiscount());
    }

    public function test_coupon_below_minimum_total_is_rejected(): void
    {
        Coupon::create([
            'code' => 'BIG', 'type' => Coupon::PERCENT, 'value' => 10,
            'min_total' => 9_999_999_999, 'is_active' => true,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'BIG'])->assertSessionHasErrors('coupon');
    }

    public function test_unknown_coupon_is_rejected(): void
    {
        $this->post(route('cart.coupon.apply'), ['code' => 'DOESNOTEXIST'])->assertSessionHasErrors('coupon');
    }

    public function test_coupon_restricted_to_another_tier_is_rejected(): void
    {
        Coupon::create([
            'code' => 'DEALERS', 'type' => Coupon::PERCENT, 'value' => 15, 'is_active' => true,
            'tier_scope' => [PriceTier::where('code', 'wholesale_1')->value('id')],
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'DEALERS'])->assertSessionHasErrors('coupon');
    }

    public function test_coupon_can_be_removed(): void
    {
        Coupon::create(['code' => 'OFF10', 'type' => Coupon::PERCENT, 'value' => 10, 'is_active' => true]);

        $this->post(route('cart.coupon.apply'), ['code' => 'OFF10']);
        $this->delete(route('cart.coupon.remove'));

        $this->assertSame(0, app(CartService::class)->summary($this->customer)->couponDiscount());
    }

    public function test_usage_is_recorded_on_checkout_and_blocks_reuse(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'A1']])]);

        $coupon = Coupon::create([
            'code' => 'ONCE', 'type' => Coupon::PERCENT, 'value' => 10,
            'is_active' => true, 'usage_limit_per_customer' => 1,
        ]);

        $this->customer->addresses()->create([
            'receiver_name' => 'تست', 'receiver_mobile' => '09121110000',
            'province_id' => \App\Models\Province::where('slug', 'tehran')->value('id'),
            'line' => 'نشانی تست', 'is_default' => true,
        ]);

        $this->post(route('cart.coupon.apply'), ['code' => 'ONCE']);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
        ]);

        $order = Order::latest()->firstOrFail();

        $this->assertGreaterThan(0, $order->coupon_discount);
        $this->assertSame('ONCE', $order->coupon_code);
        $this->assertSame(1, $coupon->refresh()->used_count);
        $this->assertSame(1, $coupon->usages()->count());

        // تلاش دوباره برای همان مشتری رد می‌شود
        app(CartService::class)->add($this->product, 1);
        $this->post(route('cart.coupon.apply'), ['code' => 'ONCE'])->assertSessionHasErrors('coupon');
    }

    public function test_admin_creates_coupon_with_toman_amounts_stored_as_rial(): void
    {
        $admin = User::firstOrFail();

        // گارد را صریح می‌دهیم چون setUp گارد پیش‌فرض را روی «مشتری» گذاشته است
        $this->actingAs($admin, 'web')
            ->post(route('admin.coupons.store'), [
                'code' => 'FIXED50', 'type' => Coupon::FIXED,
                'value' => 50_000, 'min_total' => 1_000_000,
            ])->assertSessionHasNoErrors()->assertRedirect();

        $coupon = Coupon::where('code', 'FIXED50')->firstOrFail();

        $this->assertSame(500_000, $coupon->value);      // ۵۰٬۰۰۰ تومان
        $this->assertSame(10_000_000, $coupon->min_total); // ۱٬۰۰۰٬۰۰۰ تومان
    }
}
