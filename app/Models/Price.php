<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Price extends Model
{
    protected $guarded = [];

    protected $casts = [
        'amount'     => 'integer',
        'compare_at' => 'integer',
        'min_qty'    => 'integer',
        'is_active'  => 'boolean',
        'starts_at'  => 'datetime',
        'ends_at'    => 'datetime',
    ];

    public function priceable(): MorphTo
    {
        return $this->morphTo();
    }

    public function tier(): BelongsTo
    {
        return $this->belongsTo(PriceTier::class, 'price_tier_id');
    }

    public function scopeUsable($q)
    {
        $now = now();

        return $q->where('is_active', true)
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', $now))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', $now));
    }
}
