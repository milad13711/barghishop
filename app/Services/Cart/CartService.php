<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Pricing\PriceResolver;
use Illuminate\Support\Str;

/**
 * سبد خرید مبتنی بر دیتابیس (نه سشن) تا:
 *  - سبد رهاشده قابل پیگیری و یادآوری پیامکی باشد،
 *  - سبد مهمان پس از ورود به حساب مشتری منتقل شود.
 */
class CartService
{
    protected const COOKIE = 'bs_cart';

    protected ?Cart $cart = null;

    public function __construct(protected PriceResolver $prices) {}

    public function current(bool $createIfMissing = false): ?Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        $customer = auth('customer')->user();

        $cart = $customer
            ? Cart::firstWhere('customer_id', $customer->id)
            : Cart::firstWhere('session_token', $this->token());

        if (! $cart && $createIfMissing) {
            $cart = Cart::create([
                'customer_id'   => $customer?->id,
                'session_token' => $customer ? null : $this->token(),
            ]);
        }

        return $this->cart = $cart?->load(['items.product.media', 'items.product.prices', 'items.variant.prices', 'coupon']);
    }

    public function add(Product $product, int $qty = 1, ?ProductVariant $variant = null): CartItem
    {
        $cart = $this->current(createIfMissing: true);

        $item = $cart->items()->firstOrNew([
            'product_id'         => $product->id,
            'product_variant_id' => $variant?->id,
        ]);

        $item->qty = max(1, $item->exists ? $item->qty + $qty : $qty);
        $item->save();

        $this->cart = null;

        return $item;
    }

    public function updateQty(CartItem $item, int $qty): void
    {
        $qty <= 0 ? $item->delete() : $item->update(['qty' => $qty]);

        $this->cart = null;
    }

    public function remove(CartItem $item): void
    {
        $item->delete();
        $this->cart = null;
    }

    public function clear(): void
    {
        $this->current()?->items()->delete();
        $this->cart = null;
    }

    public function count(): int
    {
        return (int) ($this->current()?->items->sum('qty') ?? 0);
    }

    /** خلاصه محاسبه‌شده سبد — تنها منبع اعداد برای نمایش و تسویه. */
    public function summary(?Customer $customer = null): CartSummary
    {
        $customer ??= auth('customer')->user();
        $cart = $this->current();

        $lines = collect();

        foreach ($cart?->items ?? [] as $item) {
            $purchasable = $item->purchasable();
            $price = $this->prices->for($purchasable, $customer, $item->qty);

            $lines->push(new CartLine(
                item: $item,
                unitPrice: $price->amount,
                compareAt: $price->compareAt,
                lineTotal: $price->amount * $item->qty,
                weight: ($purchasable instanceof ProductVariant
                    ? $purchasable->effectiveWeight()
                    : $purchasable->effectiveWeight()) * $item->qty,
                priceHidden: $price->hidden,
            ));
        }

        return new CartSummary($cart, $lines, $customer);
    }

    /** انتقال سبد مهمان به حساب مشتری پس از ورود. */
    public function mergeIntoCustomer(Customer $customer): void
    {
        $guestCart = Cart::firstWhere('session_token', $this->token());

        if (! $guestCart) {
            return;
        }

        $customerCart = Cart::firstOrCreate(['customer_id' => $customer->id]);

        foreach ($guestCart->items as $item) {
            $existing = $customerCart->items()->firstOrNew([
                'product_id'         => $item->product_id,
                'product_variant_id' => $item->product_variant_id,
            ]);

            $existing->qty = ($existing->qty ?? 0) + $item->qty;
            $existing->save();
        }

        $guestCart->delete();
        $this->cart = null;
    }

    protected function token(): string
    {
        if ($token = request()->cookie(self::COOKIE)) {
            return $token;
        }

        $token = (string) Str::uuid();

        cookie()->queue(cookie(self::COOKIE, $token, 60 * 24 * 30));
        request()->cookies->set(self::COOKIE, $token);

        return $token;
    }
}
