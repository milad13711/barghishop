<?php

namespace App\Models;

use App\Support\Concerns\HasSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LoyaltyLevel extends Model
{
    use HasSlug;

    protected $guarded = [];

    protected $casts = [
        'benefits'          => 'array',
        'discount_percent'  => 'float',
        'points_multiplier' => 'float',
    ];

    public function customers(): HasMany
    {
        return $this->hasMany(Customer::class);
    }

    /** بالاترین سطحی که مشتری با این امتیاز واجد آن است. */
    public static function forPoints(int $points): ?self
    {
        return static::query()
            ->where('min_points', '<=', $points)
            ->orderByDesc('min_points')
            ->first();
    }
}
