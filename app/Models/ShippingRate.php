<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingRate extends Model
{
    protected $guarded = [];

    protected $casts = [
        'base_cost'         => 'integer',
        'per_kg_cost'       => 'integer',
        'base_weight_grams' => 'integer',
        'free_over'         => 'integer',
        'is_active'         => 'boolean',
    ];

    public function method(): BelongsTo
    {
        return $this->belongsTo(ShippingMethod::class, 'shipping_method_id');
    }

    public function province(): BelongsTo
    {
        return $this->belongsTo(Province::class);
    }
}
