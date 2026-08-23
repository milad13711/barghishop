<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\Province;
use App\Models\ShippingMethod;
use App\Services\Cart\CartService;
use App\Services\Orders\CheckoutService;
use App\Services\Payment\PaymentManager;
use App\Services\Shipping\ShippingCalculator;
use App\Support\Casts\Mobile;
use Illuminate\Http\Request;

class CheckoutController extends Controller
{
    public function __construct(
        protected CartService $cart,
        protected CheckoutService $checkout,
        protected ShippingCalculator $shipping,
        protected PaymentManager $gateways,
    ) {}

    public function index(Request $request)
    {
        $customer = auth('customer')->user();
        $summary  = $this->cart->summary($customer);

        if ($summary->isEmpty()) {
            return redirect()->route('cart.index');
        }

        $address = $customer->defaultAddress();

        return view('shop.checkout', [
            'seo'       => ['title' => 'تسویه حساب | '.config('shop.name')],
            'summary'   => $summary,
            'addresses' => $customer->addresses()->latest()->get(),
            'address'   => $address,
            'provinces' => Province::orderBy('name')->get(),
            'quotes'    => $this->shipping->quotes(
                $summary->weight(),
                $summary->payable(),
                $address?->province_id,
            ),
        ]);
    }

    public function storeAddress(Request $request)
    {
        $data = $request->validate([
            'receiver_name'   => ['required', 'string', 'max:120'],
            'receiver_mobile' => ['required', 'string', 'max:20'],
            'province_id'     => ['required', 'exists:provinces,id'],
            'city_name'       => ['required', 'string', 'max:120'],
            'postal_code'     => ['nullable', 'string', 'max:10'],
            'line'            => ['required', 'string', 'max:500'],
            'plaque'          => ['nullable', 'string', 'max:20'],
            'unit'            => ['nullable', 'string', 'max:20'],
        ]);

        abort_unless(Mobile::isValid($data['receiver_mobile']), 422, 'شماره گیرنده معتبر نیست.');

        $customer = auth('customer')->user();

        $customer->addresses()->update(['is_default' => false]);

        $customer->addresses()->create($data + [
            'province_name' => Province::find($data['province_id'])?->name,
            'is_default'    => true,
        ]);

        return back()->with('success', 'آدرس ثبت شد.');
    }

    public function place(Request $request)
    {
        $data = $request->validate([
            'address_id'         => ['required', 'exists:addresses,id'],
            'shipping_method_id' => ['required', 'exists:shipping_methods,id'],
            'payment_method'     => ['required', 'in:online,cod'],
            'note'               => ['nullable', 'string', 'max:1000'],
        ]);

        $customer = auth('customer')->user();
        $address  = Address::findOrFail($data['address_id']);

        abort_unless($address->customer_id === $customer->id, 403);

        try {
            $order = $this->checkout->place(
                $customer,
                $address,
                ShippingMethod::findOrFail($data['shipping_method_id']),
                $data['payment_method'],
                $data['note'] ?? null,
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['checkout' => $e->getMessage()]);
        }

        if ($order->payment_method === 'cod') {
            return redirect()->route('checkout.result', $order);
        }

        $result = $this->gateways->driver()->request(
            $order,
            route('payment.callback', ['gateway' => config('shop.payment.default')]),
        );

        if (! $result->ok) {
            return redirect()->route('checkout.result', $order)
                ->withErrors(['payment' => $result->error]);
        }

        return redirect()->away($result->redirectUrl);
    }

    public function result(Order $order)
    {
        abort_unless($order->customer_id === auth('customer')->id(), 403);

        return view('shop.checkout-result', [
            'seo'   => ['title' => 'نتیجه سفارش | '.config('shop.name')],
            'order' => $order->load('transactions'),
        ]);
    }
}
