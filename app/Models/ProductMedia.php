<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class ProductMedia extends Model
{
    protected $table = 'product_media';

    protected $guarded = [];

    protected $casts = ['is_primary' => 'boolean'];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function url(): string
    {
        // نسبی به ریشه سایت؛ به APP_URL و تفاوت www/بدون‌www وابسته نیست
        return str_starts_with($this->path, 'http')
            ? $this->path
            : '/storage/'.ltrim($this->path, '/');
    }
}
