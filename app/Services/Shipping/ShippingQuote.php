<?php

namespace App\Services\Shipping;

use App\Models\ShippingMethod;
use App\Support\Digits;

final class ShippingQuote
{
    public function __construct(
        public readonly ShippingMethod $method,
        public readonly int $cost,
        public readonly bool $isFree,
        public readonly int $minDays,
        public readonly int $maxDays,
        public readonly ?string $unavailableReason = null,
    ) {}

    public function isAvailable(): bool
    {
        return $this->unavailableReason === null;
    }

    public function deliveryText(): string
    {
        if ($this->maxDays === 0) {
            return 'تحویل همان روز';
        }

        $text = $this->minDays === $this->maxDays || $this->minDays === 0
            ? "تا {$this->maxDays} روز کاری"
            : "{$this->minDays} تا {$this->maxDays} روز کاری";

        return Digits::toPersian($text);
    }
}
