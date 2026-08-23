<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Models\Transaction;
use App\Services\Cart\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CheckoutTest extends TestCase
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
            'mobile'        => '09121110000',
            'name'          => 'مشتری آزمایشی',
            'price_tier_id' => PriceTier::retail()->id,
            'is_active'     => true,
        ]);

        $this->product = Product::published()->firstOrFail();

        Address::create([
            'customer_id'     => $this->customer->id,
            'receiver_name'   => 'مشتری آزمایشی',
            'receiver_mobile' => '09121110000',
            'province_id'     => Province::where('slug', 'tehran')->value('id'),
            'province_name'   => 'تهران',
            'city_name'       => 'تهران',
            'line'            => 'خیابان آزمایشی، کوچه تست',
            'is_default'      => true,
        ]);

        $this->actingAs($this->customer, 'customer');
    }

    protected function fillCart(int $qty = 2): void
    {
        app(CartService::class)->add($this->product, $qty);
    }

    public function test_checkout_page_renders_with_items(): void
    {
        $this->fillCart();

        $this->get(route('checkout.index'))
            ->assertOk()
            ->assertSee('روش ارسال', false)
            ->assertSee('پست پیشتاز', false);
    }

    public function test_empty_cart_redirects_to_cart(): void
    {
        $this->get(route('checkout.index'))->assertRedirect(route('cart.index'));
    }

    public function test_placing_an_order_snapshots_prices_and_adds_shipping(): void
    {
        Http::fake([
            '*payment/request.json' => Http::response([
                'data' => ['code' => 100, 'authority' => 'A0000000000000000000000000000012345'],
            ]),
        ]);

        // آستانه ارسال رایگان را برمی‌داریم تا هزینه ارسال واقعاً محاسبه شود
        config()->set('shop.shipping.free_over', 0);
        ShippingMethod::where('code', 'post_pishtaz')->update(['free_over' => null]);

        $this->fillCart(2);

        $method  = ShippingMethod::where('code', 'post_pishtaz')->firstOrFail();
        $address = $this->customer->defaultAddress();

        $this->post(route('checkout.place'), [
            'address_id'         => $address->id,
            'shipping_method_id' => $method->id,
            'payment_method'     => 'online',
        ])->assertRedirectContains('zarinpal.com');

        $order = Order::latest()->firstOrFail();

        $this->assertSame(Order::PENDING_PAYMENT, $order->status);
        $this->assertSame(2, $order->items->sum('qty'));
        $this->assertSame($this->product->name, $order->items->first()->name_snapshot);
        $this->assertGreaterThan(0, $order->shipping_cost);
        $this->assertSame(
            $order->subtotal - $order->discount_total + $order->shipping_cost,
            $order->grand_total,
        );

        // سبد پس از ثبت سفارش خالی می‌شود
        $this->assertSame(0, app(CartService::class)->count());
    }

    public function test_shipping_is_free_above_the_threshold(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'A-FREE']])]);

        $this->fillCart(2); // مبلغ سبد بالاتر از آستانه ارسال رایگان است

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'post_pishtaz')->value('id'),
            'payment_method'     => 'online',
        ]);

        $this->assertSame(0, Order::latest()->firstOrFail()->shipping_cost);
    }

    public function test_order_price_ignores_client_supplied_values(): void
    {
        Http::fake(['*' => Http::response(['data' => ['code' => 100, 'authority' => 'A123']])]);

        $this->fillCart(1);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
            'grand_total'        => 1, // تلاش برای دستکاری مبلغ
        ]);

        $order = Order::latest()->firstOrFail();

        $this->assertGreaterThan(1, $order->grand_total);
    }

    public function test_order_is_blocked_when_stock_is_insufficient(): void
    {
        $this->product->update(['stock' => 1, 'track_stock' => true, 'allow_backorder' => false]);

        $this->fillCart(5);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
        ])->assertSessionHasErrors('checkout');

        $this->assertSame(0, Order::count());
    }

    public function test_successful_gateway_callback_marks_order_paid(): void
    {
        Http::fake([
            '*payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'AUTH-OK']]),
            '*payment/verify.json'  => Http::response(['data' => ['code' => 100, 'ref_id' => 987654321]]),
        ]);

        $this->fillCart(1);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
        ]);

        $order = Order::latest()->firstOrFail();

        $this->get(route('payment.callback', ['gateway' => 'zarinpal']).'?Authority=AUTH-OK&Status=OK')
            ->assertRedirect(route('checkout.result', $order));

        $order->refresh();

        $this->assertSame(Order::PAID, $order->status);
        $this->assertSame('paid', $order->payment_status);
        $this->assertNotNull($order->paid_at);
        $this->assertSame('987654321', $order->transactions->first()->ref_id);
    }

    public function test_cancelled_payment_leaves_order_unpaid(): void
    {
        Http::fake(['*payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'AUTH-CANCEL']])]);

        $this->fillCart(1);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
        ]);

        $this->get(route('payment.callback', ['gateway' => 'zarinpal']).'?Authority=AUTH-CANCEL&Status=NOK');

        $order = Order::latest()->firstOrFail();

        $this->assertSame(Order::PENDING_PAYMENT, $order->status);
        $this->assertSame('unpaid', $order->payment_status);
        $this->assertSame(Transaction::FAILED, $order->transactions->first()->status);
    }

    public function test_double_callback_does_not_pay_twice(): void
    {
        Http::fake([
            '*payment/request.json' => Http::response(['data' => ['code' => 100, 'authority' => 'AUTH-DUP']]),
            '*payment/verify.json'  => Http::response(['data' => ['code' => 100, 'ref_id' => 111]]),
        ]);

        $this->fillCart(1);

        $this->post(route('checkout.place'), [
            'address_id'         => $this->customer->defaultAddress()->id,
            'shipping_method_id' => ShippingMethod::where('code', 'pickup')->value('id'),
            'payment_method'     => 'online',
        ]);

        $url = route('payment.callback', ['gateway' => 'zarinpal']).'?Authority=AUTH-DUP&Status=OK';

        $this->get($url);
        $this->get($url);

        $order = Order::latest()->firstOrFail();

        $this->assertSame(1, $order->statusLogs()->where('to_status', Order::PAID)->count());
    }
}
