<?php

namespace App\Models;

use App\Support\Concerns\HasSeo;
use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasSlug, HasSeo, SoftDeletes;

    public const DRAFT     = 'draft';
    public const PUBLISHED = 'published';
    public const ARCHIVED  = 'archived';

    protected $guarded = [];

    protected $casts = [
        'is_featured'          => 'boolean',
        'track_stock'          => 'boolean',
        'allow_backorder'      => 'boolean',
        'prices_require_login' => 'boolean',
        'seo_schema'           => 'array',
        'published_at'         => 'datetime',
        'rating_avg'           => 'float',
    ];

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function specs(): HasMany
    {
        return $this->hasMany(ProductSpec::class)->orderBy('sort');
    }

    /** تصاویر عمومی محصول (مخصوص هیچ مدلی نیستند). */
    public function media(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->whereNull('product_variant_id')->orderBy('sort');
    }

    /** همه تصاویر، شامل تصاویر مدل‌ها — برای عملیات پنل. */
    public function allMedia(): HasMany
    {
        return $this->hasMany(ProductMedia::class)->orderBy('sort');
    }

    public function prices(): MorphMany
    {
        return $this->morphMany(Price::class, 'priceable');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(ProductQuestion::class);
    }

    public function scopePublished(Builder $q): Builder
    {
        return $q->where('status', self::PUBLISHED)
            ->where(fn ($q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeInStock(Builder $q): Builder
    {
        return $q->where(fn ($q) => $q->where('track_stock', false)
            ->orWhere('stock', '>', 0)
            ->orWhere('allow_backorder', true));
    }

    public function getPrimaryImageAttribute(): ?string
    {
        $general = $this->media->firstWhere('is_primary', true)?->path ?? $this->media->first()?->path;

        // محصولی که فقط تصویر مدل‌ها را دارد، با اولین تصویر یک مدل معرفی می‌شود
        return $general ?? ProductMedia::where('product_id', $this->id)->orderBy('sort')->orderBy('id')->value('path');
    }

    /** مدل‌های فعال (رنگ، تعداد کانال و…). محصول بدون مدل، مثل قبل با موجودی خودش کار می‌کند. */
    public function activeVariants(): \Illuminate\Support\Collection
    {
        return $this->variants->where('is_active', true)->values();
    }

    public function hasVariants(): bool
    {
        return $this->activeVariants()->isNotEmpty();
    }

    public function isAvailable(): bool
    {
        if ($this->hasVariants()) {
            return $this->activeVariants()->contains(fn (ProductVariant $v) => $v->isAvailable($this));
        }

        return ! $this->track_stock || $this->stock > 0 || $this->allow_backorder;
    }

    public function effectiveWeight(): int
    {
        return $this->weight_grams ?: (int) config('shop.shipping.default_weight_grams');
    }

    public function hiddenPriceForGuests(): bool
    {
        return $this->prices_require_login || (bool) $this->category?->prices_require_login;
    }
}
