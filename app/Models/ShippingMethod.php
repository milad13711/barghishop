<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ShippingMethod extends Model
{
    public const MODE_FLAT   = 'flat';
    public const MODE_WEIGHT = 'weight';
    public const MODE_PICKUP = 'pickup';

    protected $guarded = [];

    protected $casts = [
        'is_active'            => 'boolean',
        'allowed_province_ids' => 'array',
        'cod_enabled' => 'boolean',
        'flat_cost'   => 'integer',
        'free_over'   => 'integer',
        'cod_fee'     => 'integer',
    ];

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class);
    }

    public function servesProvince(?int $provinceId): bool
    {
        if (blank($this->allowed_province_ids)) {
            return true;
        }

        return $provinceId !== null && in_array($provinceId, $this->allowed_province_ids, true);
    }

    public function scopeActive($q)
    {
        return $q->where('is_active', true)->orderBy('sort');
    }

    /** نرخ مخصوص استان، وگرنه نرخ پیش‌فرض (province_id = null). */
    public function rateFor(?int $provinceId): ?ShippingRate
    {
        return $this->rates
            ->where('is_active', true)
            ->sortBy(fn ($r) => $r->province_id === $provinceId ? 0 : 1)
            ->first(fn ($r) => $r->province_id === $provinceId || $r->province_id === null);
    }
}
