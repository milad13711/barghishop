<?php

namespace App\Services\Cart;

use App\Models\Cart;
use App\Models\Coupon;
use App\Models\Customer;
use RuntimeException;

/** اعتبارسنجی و اعمال کد تخفیف روی سبد. */
class CouponService
{
    public function apply(Cart $cart, string $code, ?Customer $customer, int $subtotal): Coupon
    {
        $coupon = Coupon::whereRaw('LOWER(code) = ?', [mb_strtolower(trim($code))])->first();

        if (! $coupon) {
            throw new RuntimeException('کد تخفیف نامعتبر است.');
        }

        $this->assertUsable($coupon, $customer, $subtotal);

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon;
    }

    public function remove(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }

    public function assertUsable(Coupon $coupon, ?Customer $customer, int $subtotal): void
    {
        if (! $coupon->isWithinWindow()) {
            throw new RuntimeException('این کد تخفیف منقضی یا غیرفعال است.');
        }

        if ($coupon->min_total > 0 && $subtotal < $coupon->min_total) {
            throw new RuntimeException(
                'این کد برای سبد بالای '.\App\Support\Money::format($coupon->min_total).' قابل استفاده است.'
            );
        }

        if ($coupon->usage_limit && $coupon->used_count >= $coupon->usage_limit) {
            throw new RuntimeException('ظرفیت استفاده از این کد تکمیل شده است.');
        }

        if ($coupon->tier_scope) {
            $tierId = $customer?->effectiveTier()->id;

            if (! $tierId || ! in_array($tierId, $coupon->tier_scope)) {
                throw new RuntimeException('این کد برای حساب شما قابل استفاده نیست.');
            }
        }

        if ($customer && $coupon->usage_limit_per_customer) {
            $used = $coupon->usages()->where('customer_id', $customer->id)->count();

            if ($used >= $coupon->usage_limit_per_customer) {
                throw new RuntimeException('شما قبلاً از این کد استفاده کرده‌اید.');
            }
        }
    }
}
