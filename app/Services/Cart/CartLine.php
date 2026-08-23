<?php

namespace App\Services\Cart;

use App\Models\CartItem;

final class CartLine
{
    public function __construct(
        public readonly CartItem $item,
        public readonly int $unitPrice,
        public readonly ?int $compareAt,
        public readonly int $lineTotal,
        public readonly int $weight,
        public readonly bool $priceHidden = false,
    ) {}

    public function savings(): int
    {
        return $this->compareAt && $this->compareAt > $this->unitPrice
            ? ($this->compareAt - $this->unitPrice) * $this->item->qty
            : 0;
    }
}
