<?php

namespace App\Services\Pricing;

use App\Models\PriceTier;

/** نتیجه محاسبه قیمت — تنها شکل قابل‌اعتماد قیمت در کل سیستم. */
final class ResolvedPrice
{
    public function __construct(
        public readonly int $amount,          // ریال، قیمت واحد
        public readonly ?int $compareAt,      // قیمت خط‌خورده
        public readonly PriceTier $tier,
        public readonly int $minQty = 1,
        public readonly bool $hidden = false, // قیمت برای این کاربر قابل نمایش نیست
    ) {}

    public function hasDiscount(): bool
    {
        return $this->compareAt !== null && $this->compareAt > $this->amount;
    }

    public function discountPercent(): int
    {
        if (! $this->hasDiscount()) {
            return 0;
        }

        return (int) round(($this->compareAt - $this->amount) / $this->compareAt * 100);
    }

    public static function hidden(PriceTier $tier): self
    {
        return new self(0, null, $tier, 1, true);
    }
}
