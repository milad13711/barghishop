<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use Illuminate\Support\Collection;

final class CartSummary
{
    public function __construct(
        public readonly ?Cart $cart,
        /** @var Collection<int, CartLine> */
        public readonly Collection $lines,
        public readonly ?Customer $customer = null,
    ) {}

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function itemCount(): int
    {
        return (int) $this->lines->sum(fn (CartLine $l) => $l->item->qty);
    }

    public function subtotal(): int
    {
        return (int) $this->lines->sum(fn (CartLine $l) => $l->lineTotal);
    }

    public function savings(): int
    {
        return (int) $this->lines->sum(fn (CartLine $l) => $l->savings());
    }

    public function weight(): int
    {
        return (int) $this->lines->sum(fn (CartLine $l) => $l->weight);
    }

    /** تخفیف سطح باشگاه مشتریان روی جمع کل. */
    public function loyaltyDiscount(): int
    {
        $percent = $this->customer?->loyaltyLevel?->discount_percent ?? 0;

        return $percent > 0 ? (int) floor($this->subtotal() * $percent / 100) : 0;
    }

    public function couponDiscount(): int
    {
        $coupon = $this->cart?->coupon;

        if (! $coupon || ! $coupon->isWithinWindow() || $this->subtotal() < $coupon->min_total) {
            return 0;
        }

        $discount = match ($coupon->type) {
            Coupon::PERCENT => (int) floor($this->subtotal() * $coupon->value / 100),
            Coupon::FIXED   => (int) $coupon->value,
            default         => 0,
        };

        if ($coupon->max_discount) {
            $discount = min($discount, (int) $coupon->max_discount);
        }

        return min($discount, $this->subtotal());
    }

    public function discountTotal(): int
    {
        return min($this->subtotal(), $this->loyaltyDiscount() + $this->couponDiscount());
    }

    public function payable(): int
    {
        return max(0, $this->subtotal() - $this->discountTotal());
    }

    public function hasHiddenPrices(): bool
    {
        return $this->lines->contains(fn (CartLine $l) => $l->priceHidden);
    }
}
