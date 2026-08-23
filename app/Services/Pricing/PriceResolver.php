<?php

namespace App\Services\Pricing;

use App\Models\Customer;
use App\Models\Price;
use App\Models\PriceTier;
use App\Models\Product;
use App\Models\ProductVariant;

/**
 * تنها نقطه محاسبه قیمت در کل برنامه.
 * هیچ جای دیگری نباید مستقیماً از جدول prices بخواند.
 *
 * منطق:
 *  ۱. سطح مؤثر مشتری را می‌گیرد (عمده تأییدنشده ← خرده).
 *  ۲. بهترین ردیف قیمت آن سطح را با min_qty <= qty انتخاب می‌کند (پلکانی).
 *  ۳. اگر برای آن سطح قیمتی ثبت نشده باشد، به قیمت خرده با درصد تخفیف
 *     پیش‌فرض سطح (fallback_discount_percent) برمی‌گردد.
 *  ۴. اگر محصول/دسته «نمایش قیمت فقط برای کاربران وارد شده» باشد و کاربر
 *     مهمان باشد، قیمت پنهان برگردانده می‌شود.
 */
class PriceResolver
{
    public function for(
        Product|ProductVariant $item,
        ?Customer $customer = null,
        int $qty = 1,
    ): ResolvedPrice {
        $tier = $customer?->effectiveTier() ?? PriceTier::retail();
        $product = $item instanceof ProductVariant ? $item->product : $item;

        if ($customer === null && $product->hiddenPriceForGuests()) {
            return ResolvedPrice::hidden($tier);
        }

        if ($row = $this->bestRow($item, $tier, $qty)) {
            return new ResolvedPrice(
                amount: (int) $row->amount,
                compareAt: $row->compare_at ? (int) $row->compare_at : null,
                tier: $tier,
                minQty: (int) $row->min_qty,
            );
        }

        return $this->fallbackFromRetail($item, $tier, $qty);
    }

    /** قیمت پایه خرده‌فروشی — برای نمایش «قیمت مصرف‌کننده» کنار قیمت عمده. */
    public function retailFor(Product|ProductVariant $item, int $qty = 1): ?ResolvedPrice
    {
        $retail = PriceTier::retail();

        if (! $row = $this->bestRow($item, $retail, $qty)) {
            return null;
        }

        return new ResolvedPrice(
            amount: (int) $row->amount,
            compareAt: $row->compare_at ? (int) $row->compare_at : null,
            tier: $retail,
            minQty: (int) $row->min_qty,
        );
    }

    /** همه پله‌های قیمت یک سطح — برای جدول «خرید عمده» در صفحه محصول. */
    public function tiersFor(Product|ProductVariant $item, PriceTier $tier)
    {
        return $this->rows($item)
            ->where('price_tier_id', $tier->id)
            ->sortBy('min_qty')
            ->values();
    }

    protected function bestRow(Product|ProductVariant $item, PriceTier $tier, int $qty): ?Price
    {
        $row = $this->rows($item)
            ->where('price_tier_id', $tier->id)
            ->where('min_qty', '<=', max(1, $qty))
            ->sortByDesc('min_qty')
            ->first();

        // واریانت بدون قیمت اختصاصی ← قیمت محصول والد
        if (! $row && $item instanceof ProductVariant) {
            return $this->bestRow($item->product, $tier, $qty);
        }

        return $row;
    }

    protected function fallbackFromRetail(Product|ProductVariant $item, PriceTier $tier, int $qty): ResolvedPrice
    {
        $retailRow = $this->bestRow($item, PriceTier::retail(), $qty);

        if (! $retailRow) {
            return ResolvedPrice::hidden($tier);
        }

        $amount = (int) $retailRow->amount;

        if ($tier->fallback_discount_percent > 0) {
            $amount = (int) round($amount * (100 - $tier->fallback_discount_percent) / 100);
        }

        return new ResolvedPrice(
            amount: $amount,
            compareAt: $tier->fallback_discount_percent > 0 ? (int) $retailRow->amount : null,
            tier: $tier,
        );
    }

    /** ردیف‌های قیمت معتبر، با کش relation تا کوئری تکرار نشود. */
    protected function rows(Product|ProductVariant $item)
    {
        if (! $item->relationLoaded('prices')) {
            $item->load(['prices' => fn ($q) => $q->usable()]);

            return $item->prices;
        }

        return $item->prices->filter(function (Price $p) {
            $now = now();

            return $p->is_active
                && (! $p->starts_at || $p->starts_at <= $now)
                && (! $p->ends_at || $p->ends_at >= $now);
        });
    }
}
