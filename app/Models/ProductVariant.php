<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * ستون ویژگی‌ها عمداً `options` نام دارد نه `attributes`،
 * چون `attributes` با پراپرتی داخلی Eloquent تداخل می‌کند.
 */
class ProductVariant extends Model
{
    protected $guarded = [];

    protected $casts = [
        'options'   => 'array',
        'is_active' => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    /** موجود بودن این مدل؛ موجودی هر مدل مستقل از مدل‌های دیگر است. */
    public function isAvailable(?Product $product = null): bool
    {
        $product ??= $this->product;

        return $this->is_active
            && (! $product->track_stock || $this->stock > 0 || $product->allow_backorder);
    }

    /** بیشینه تعداد قابل سفارش؛ null یعنی محدودیتی نیست. */
    public function maxOrderable(?Product $product = null): ?int
    {
        $product ??= $this->product;

        return ($product->track_stock && ! $product->allow_backorder) ? max(0, $this->stock) : null;
    }

    public function label(): string
    {
        if ($this->name) {
            return $this->name;
        }

        return collect($this->options ?? [])
            ->map(fn ($v, $k) => "$k: $v")
            ->implode('، ');
    }

    public function effectiveWeight(): int
    {
        return $this->weight_grams ?: $this->product->effectiveWeight();
    }
}
