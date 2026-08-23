<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PriceTier extends Model
{
    public const RETAIL = 'retail';

    protected $guarded = [];

    protected $casts = [
        'is_default'        => 'boolean',
        'is_wholesale'      => 'boolean',
        'requires_approval' => 'boolean',
    ];

    public function prices(): HasMany
    {
        return $this->hasMany(Price::class);
    }

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    public static function retail(): self
    {
        return static::query()->firstOrCreate(
            ['code' => self::RETAIL],
            ['name' => 'خرده‌فروشی', 'is_default' => true]
        );
    }
}
