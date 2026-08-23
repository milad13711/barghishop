<?php

namespace App\Services\Orders;

use App\Models\Address;
use App\Models\Customer;
use App\Models\Order;
use App\Models\ShippingMethod;
use App\Services\Cart\CartLine;
use App\Services\Cart\CartService;
use App\Services\Shipping\ShippingCalculator;
use App\Services\Sms\SmsManager;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * ساخت سفارش از روی سبد خرید.
 *
 * قیمت‌ها اینجا دوباره از PriceResolver محاسبه می‌شوند (نه از فرم کاربر)
 * و به‌صورت snapshot در order_items ذخیره می‌شوند تا تغییر بعدی قیمت
 * روی سفارش ثبت‌شده اثر نگذارد.
 */
class CheckoutService
{
    public function __construct(
        protected CartService $cart,
        protected ShippingCalculator $shipping,
        protected SmsManager $sms,
    ) {}

    public function place(
        Customer $customer,
        Address $address,
        ShippingMethod $method,
        string $paymentMethod = 'online',
        ?string $note = null,
    ): Order {
        return DB::transaction(function () use ($customer, $address, $method, $paymentMethod, $note) {
            $summary = $this->cart->summary($customer);

            if ($summary->isEmpty()) {
                throw new RuntimeException('سبد خرید شما خالی است.');
            }

            if ($summary->hasHiddenPrices()) {
                throw new RuntimeException('قیمت برخی اقلام سبد قابل محاسبه نیست. با پشتیبانی تماس بگیرید.');
            }

            $this->assertStockAvailable($summary->lines);

            $quote = $this->shipping->quote(
                $method,
                $summary->weight(),
                $summary->payable(),
                $address->province_id,
            );

            if (! $quote->isAvailable()) {
                throw new RuntimeException($quote->unavailableReason);
            }

            $codFee = $paymentMethod === 'cod' ? (int) $method->cod_fee : 0;
            $taxable = $summary->payable() + $quote->cost + $codFee;
            $tax = config('shop.tax.enabled')
                ? (int) round($taxable * config('shop.tax.percent') / 100)
                : 0;

            $order = Order::create([
                'code'                => Order::generateCode(),
                'customer_id'         => $customer->id,
                'price_tier_id'       => $customer->effectiveTier()->id,
                'status'              => Order::PENDING_PAYMENT,
                'payment_method'      => $paymentMethod,
                'payment_status'      => 'unpaid',
                'subtotal'            => $summary->subtotal(),
                'discount_total'      => $summary->discountTotal(),
                'coupon_discount'     => $summary->couponDiscount(),
                'loyalty_discount'    => $summary->loyaltyDiscount(),
                'shipping_cost'       => $quote->cost,
                'cod_fee'             => $codFee,
                'tax_total'           => $tax,
                'grand_total'         => Money::round($taxable + $tax),
                'total_weight_grams'  => $summary->weight(),
                'coupon_id'           => $summary->cart?->coupon_id,
                'coupon_code'         => $summary->cart?->coupon?->code,
                'shipping_method_id'  => $method->id,
                'shipping_method_name' => $method->name,
                'address_snapshot'    => $address->snapshot(),
                'customer_note'       => $note,
                'ip'                  => request()->ip(),
            ]);

            foreach ($summary->lines as $line) {
                $purchasable = $line->item->purchasable();

                $order->items()->create([
                    'product_id'         => $line->item->product_id,
                    'product_variant_id' => $line->item->product_variant_id,
                    'name_snapshot'      => $line->item->product->name,
                    'sku_snapshot'       => $purchasable->sku,
                    'options_snapshot'   => $line->item->variant?->options,
                    'qty'                => $line->item->qty,
                    'unit_price'         => $line->unitPrice,
                    'unit_compare_at'    => $line->compareAt,
                    'line_total'         => $line->lineTotal,
                    'weight_grams'       => $line->weight,
                ]);
            }

            $order->statusLogs()->create([
                'to_status'  => Order::PENDING_PAYMENT,
                'actor_type' => 'customer',
                'actor_id'   => $customer->id,
            ]);

            $this->cart->clear();

            $this->sms->sendPattern($customer->mobile, 'order_placed', [
                'code'   => $order->code,
                'amount' => Money::format($order->grand_total, false),
            ], $order);

            return $order;
        });
    }

    /** @param \Illuminate\Support\Collection<int, CartLine> $lines */
    protected function assertStockAvailable($lines): void
    {
        foreach ($lines as $line) {
            $product = $line->item->product;

            if (! $product->track_stock || $product->allow_backorder) {
                continue;
            }

            if ($product->stock < $line->item->qty) {
                throw new RuntimeException(
                    "موجودی «{$product->name}» کافی نیست (موجود: {$product->stock} عدد)."
                );
            }
        }
    }
}
