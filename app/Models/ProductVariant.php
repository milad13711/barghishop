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
