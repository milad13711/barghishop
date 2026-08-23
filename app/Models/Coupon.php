<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    public const PERCENT       = 'percent';
    public const FIXED         = 'fixed';
    public const FREE_SHIPPING = 'free_shipping';

    protected $guarded = [];

    protected $casts = [
        'tier_scope'     => 'array',
        'category_scope' => 'array',
        'is_active'      => 'boolean',
        'starts_at'      => 'datetime',
        'expires_at'     => 'datetime',
        'value'          => 'integer',
        'max_discount'   => 'integer',
        'min_total'      => 'integer',
    ];

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class);
    }

    public function isWithinWindow(): bool
    {
        return $this->is_active
            && (! $this->starts_at || $this->starts_at->isPast())
            && (! $this->expires_at || $this->expires_at->isFuture());
    }
}
